<?php

namespace App\Controllers;

use App\Models\CareerTransitionModel;
use App\Models\DailyTaskModel;
use App\Models\CourseModuleModel;
use App\Models\CourseLessonModel;
use App\Libraries\CareerTransitionAI;

class CareerTransition extends BaseController
{
    public function index()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $candidateId = (int) session()->get('user_id');
        helper('premium');
        requirePremiumForFeature($candidateId, 'career transition');
        $transitionModel = new CareerTransitionModel();
        $taskModel = new DailyTaskModel();
        $userModel = new \App\Models\UserModel();
        $skillsModel = new \App\Models\CandidateSkillsModel();

        if ($this->request->getGet('reset') === '1') {
            $transitionModel->where('candidate_id', $candidateId)
                           ->where('status', 'active')
                           ->set(['status' => 'inactive', 'deactivated_at' => date('Y-m-d H:i:s')])
                           ->update();
            session()->remove('career_suggestions');
            $targetRole = $this->request->getGet('target');
            if ($targetRole) {
                return redirect()->to('career-transition')->with('target_role', urldecode($targetRole));
            }
            return redirect()->to('career-transition');
        }

        $activeTransition = $transitionModel->getActiveTransition($candidateId);
        $tasks = $activeTransition ? $taskModel->getTasksByTransition($activeTransition['id']) : [];

        $user = $userModel->find($candidateId);
        $workExpModel = new \App\Models\WorkExperienceModel();
        $latestWork = $workExpModel->where('user_id', $candidateId)->where('is_current', 1)->first();
        if (!$latestWork) {
            $latestWork = $workExpModel->where('user_id', $candidateId)->orderBy('start_date', 'DESC')->first();
        }
        $currentRole = $latestWork['job_title'] ?? ($user['work_experience'] ?? '');
        $skills = $skillsModel->where('candidate_id', $candidateId)->first();
        if (!$currentRole && $skills) {
            $currentRole = $skills['skill_name'];
        }
        $targetRole = session()->getFlashdata('target_role') ?? $this->request->getGet('target');

        return view('candidate/career_transition', [
            'transition'  => $activeTransition,
            'tasks'       => $tasks,
            'currentRole' => $currentRole,
            'targetRole'  => $targetRole ? urldecode($targetRole) : ''
        ]);
    }

    public function create()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $currentRole = trim($this->request->getPost('current_role'));
        $targetRole  = trim($this->request->getPost('target_role'));
        $candidateId = (int) session()->get('user_id');

        $suggestions = session()->get('career_suggestions') ?? [];
        $suggestions = array_filter($suggestions, function($s) use ($targetRole) {
            return strcasecmp($s['job_title'] ?? '', $targetRole) !== 0;
        });
        session()->set('career_suggestions', array_values($suggestions));

        $db = \Config\Database::connect();

        // ── STEP 1: Fetch all transitions and compare in PHP (avoids all SQL binding issues) ──
        $allTransitions = $db->query(
            "SELECT * FROM career_transitions WHERE candidate_id = ? ORDER BY created_at DESC",
            [$candidateId]
        )->getResultArray();

        $existingTransition = null;
        foreach ($allTransitions as $row) {
            if (strtolower(trim($row['current_role'])) === strtolower(trim($currentRole)) &&
                strtolower(trim($row['target_role']))  === strtolower(trim($targetRole))) {
                $existingTransition = $row;
                break;
            }
        }

        if ($existingTransition) {
            // ── FOUND: Reuse it, no AI call needed ──
            $db->query(
                "UPDATE career_transitions SET status = 'inactive', deactivated_at = NOW() WHERE candidate_id = ? AND status = 'active' AND id != ?",
                [$candidateId, $existingTransition['id']]
            );
            $db->query(
                "UPDATE career_transitions SET status = 'active', reactivated_at = NOW(), reactivation_count = reactivation_count + 1 WHERE id = ?",
                [$existingTransition['id']]
            );
            $db->query(
                "UPDATE daily_tasks SET is_completed = 0, completed_at = NULL WHERE transition_id = ?",
                [$existingTransition['id']]
            );

            return redirect()->to('career-transition')
                ->with('success', 'Welcome back! Your learning path has been instantly restored - no AI generation needed!');
        }

        // ── STEP 2: Not found - deactivate current, call AI ──

        // Mark current active as inactive BEFORE closing DB
        $db->query(
            "UPDATE career_transitions SET status = 'inactive', deactivated_at = NOW() WHERE candidate_id = ? AND status = 'active'",
            [$candidateId]
        );

        // Save values before closing DB (PHP variables are fine after close)
        $savedCurrentRole = $currentRole;
        $savedTargetRole  = $targetRole;
        $savedCandidateId = $candidateId;

        // Close DB, call AI, reconnect
        $db->close();

        helper('premium');
        requirePremiumForFeature($savedCandidateId, 'career transition AI');
        $ai         = new CareerTransitionAI();
        $analysis   = $ai->analyzeTransition($savedCurrentRole, $savedTargetRole);
        $courseData = $ai->generateCourseContent($savedCurrentRole, $savedTargetRole, $analysis['skill_gaps'] ?? []);

        $db->reconnect();

        // ✅ Create ALL models FRESH after reconnect - critical!
        $transitionModel = new CareerTransitionModel();
        $moduleModel     = new CourseModuleModel();
        $lessonModel     = new CourseLessonModel();
        $taskModel       = new DailyTaskModel();

        $transitionId = $transitionModel->insert([
            'candidate_id'       => $savedCandidateId,
            'current_role'       => $savedCurrentRole,
            'target_role'        => $savedTargetRole,
            'skill_gaps'         => json_encode($analysis['skill_gaps'] ?? []),
            'learning_roadmap'   => json_encode($analysis['roadmap'] ?? []),
            'status'             => 'active',
            'reactivation_count' => 0
        ]);

        if (!empty($courseData['modules'])) {
            foreach ($courseData['modules'] as $module) {
                $moduleId = $moduleModel->insert([
                    'transition_id'  => $transitionId,
                    'module_number'  => $module['number'],
                    'title'          => $module['title'],
                    'description'    => $module['description'],
                    'duration_weeks' => $module['weeks']
                ]);
                if (!empty($module['lessons'])) {
                    foreach ($module['lessons'] as $lesson) {
                        $lessonModel->insert([
                            'module_id'     => $moduleId,
                            'lesson_number' => $lesson['number'],
                            'title'         => $lesson['title'],
                            'content'       => $lesson['content'],
                            'resources'     => is_array($lesson['resources']) ? json_encode($lesson['resources']) : $lesson['resources'],
                            'exercises'     => is_array($lesson['exercises']) ? json_encode($lesson['exercises']) : $lesson['exercises']
                        ]);
                    }
                }
            }
        }

        $dailyTasks = $courseData['daily_tasks'] ?? $analysis['daily_tasks'] ?? [];
        foreach ($dailyTasks as $index => $task) {
            $taskModel->insert([
                'transition_id'    => $transitionId,
                'task_title'       => $task['title'] ?? 'Task',
                'task_description' => $task['description'] ?? '',
                'duration_minutes' => $task['duration'] ?? 10,
                'day_number'       => $task['day'] ?? ($index + 1),
                'module_number'    => $task['module'] ?? null,
                'lesson_number'    => $task['lesson'] ?? null
            ]);
        }

        return redirect()->to('career-transition')
            ->with('success', 'Career transition plan created! AI-powered course content is ready.');
    }

    public function course()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $candidateId = session()->get('user_id');
        $transitionModel = new CareerTransitionModel();
        $moduleModel = new CourseModuleModel();
        $activeTransition = $transitionModel->getActiveTransition($candidateId);
        $modules = $activeTransition ? $moduleModel->getModulesByTransition($activeTransition['id']) : [];
        return view('candidate/course_modules', ['transition' => $activeTransition, 'modules' => $modules]);
    }

    public function module($moduleId)
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $candidateId = session()->get('user_id');
        $transitionModel = new CareerTransitionModel();
        $moduleModel = new CourseModuleModel();
        $lessonModel = new CourseLessonModel();
        $activeTransition = $transitionModel->getActiveTransition($candidateId);
        $module = $moduleModel->find($moduleId);

        if (!$activeTransition) {
            return redirect()->to('career-transition')
                ->with('error', 'Please create or reactivate a career transition before opening module content.');
        }

        if (!$module || $module['transition_id'] != $activeTransition['id']) {
            return redirect()->to('career-transition/course')
                ->with('error', 'That module is not available for your active career transition.');
        }

        $lessons = $lessonModel->getLessonsByModule($moduleId);
        return view('candidate/course_content', ['transition' => $activeTransition, 'module' => $module, 'lessons' => $lessons]);
    }

    public function completeTask($taskId)
    {
        $taskModel = new DailyTaskModel();
        $taskModel->markComplete($taskId);
        return $this->response->setJSON(['success' => true]);
    }

    public function dismissSuggestion()
    {
        session()->remove('career_suggestions');
        return $this->response->setJSON(['success' => true]);
    }

    public function reset()
    {
        $candidateId = session()->get('user_id');
        $db = \Config\Database::connect();
        $db->query(
            "UPDATE career_transitions SET status = 'inactive', deactivated_at = NOW() WHERE candidate_id = ? AND status = 'active'",
            [$candidateId]
        );
        return redirect()->to('career-transition')
            ->with('success', 'Career path saved to history. You can now start a new journey!');
    }

    public function history()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $candidateId = session()->get('user_id');
        $transitionModel = new CareerTransitionModel();
        $transitions = $transitionModel
            ->where('candidate_id', $candidateId)
            ->orderBy('status', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll();
        return view('candidate/career_history', ['transitions' => $transitions]);
    }

    public function reactivate($transitionId)
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $candidateId = session()->get('user_id');
        $transitionModel = new CareerTransitionModel();
        $db = \Config\Database::connect();
        $transition = $transitionModel->find($transitionId);
        if (!$transition || $transition['candidate_id'] != $candidateId) {
            return redirect()->to('career-transition')->with('error', 'Invalid transition selected.');
        }
        $db->query("UPDATE career_transitions SET status = 'inactive', deactivated_at = NOW() WHERE candidate_id = ? AND status = 'active'", [$candidateId]);
        $db->query("UPDATE career_transitions SET status = 'active', reactivated_at = NOW(), reactivation_count = reactivation_count + 1 WHERE id = ?", [$transitionId]);
        $db->query("UPDATE daily_tasks SET is_completed = 0, completed_at = NULL WHERE transition_id = ?", [$transitionId]);
        return redirect()->to('career-transition')
            ->with('success', 'Career path reactivated! Progress has been reset for a fresh start.');
    }
}
