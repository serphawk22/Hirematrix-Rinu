<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CareerTransitionModel;
use App\Models\DailyTaskModel;
use App\Models\CourseModuleModel;
use App\Models\CourseLessonModel;
use App\Models\UserModel;
use App\Models\CandidateSkillsModel;
use App\Libraries\CareerTransitionAI;

class ApiCareerTransitionController extends ResourceController
{
    protected $format = 'json';

    public function getTransition($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $transitionModel = new CareerTransitionModel();
        $taskModel = new DailyTaskModel();
        $skillsModel = new CandidateSkillsModel();
        $userModel = new UserModel();

        $activeTransition = $transitionModel->getActiveTransition($candidateId);
        $tasks = $activeTransition ? $taskModel->getTasksByTransition($activeTransition['id']) : [];

        // Fetch candidate details for the current role fallback
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

        return $this->respond([
            'status' => 'success',
            'data' => [
                'transition' => $activeTransition,
                'tasks' => $tasks,
                'currentRole' => $currentRole,
                'pdfUrl' => base_url('career-transition/download-pdf/' . $candidateId)
            ]
        ]);
    }

    public function create()
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        $currentRole = trim((string) $this->request->getPost('current_role'));
        $targetRole  = trim((string) $this->request->getPost('target_role'));

        if ($candidateId <= 0 || empty($currentRole) || empty($targetRole)) {
            return $this->fail('Invalid inputs. candidate_id, current_role, and target_role are required.');
        }

        $db = \Config\Database::connect();

        // 1. Check if an identical transition already exists
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
            // Reuse it
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

            return $this->respond([
                'status' => 'success',
                'message' => 'Welcome back! Your learning path has been instantly restored - no AI generation needed!',
                'data' => [
                    'transition_id' => $existingTransition['id']
                ]
            ]);
        }

        // Deactivate currently active transitions
        $db->query(
            "UPDATE career_transitions SET status = 'inactive', deactivated_at = NOW() WHERE candidate_id = ? AND status = 'active'",
            [$candidateId]
        );

        $db->close();

        // AI API Call
        $ai         = new CareerTransitionAI();
        $analysis   = $ai->analyzeTransition($currentRole, $targetRole);
        $courseData = $ai->generateCourseContent($currentRole, $targetRole, $analysis['skill_gaps'] ?? []);

        $db->reconnect();

        $transitionModel = new CareerTransitionModel();
        $moduleModel     = new CourseModuleModel();
        $lessonModel     = new CourseLessonModel();
        $taskModel       = new DailyTaskModel();

        $transitionId = $transitionModel->insert([
            'candidate_id'       => $candidateId,
            'current_role'       => $currentRole,
            'target_role'        => $targetRole,
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

        return $this->respond([
            'status' => 'success',
            'message' => 'Career transition plan created! AI-powered course content is ready.',
            'data' => [
                'transition_id' => $transitionId
            ]
        ]);
    }

    public function getModules($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $transitionModel = new CareerTransitionModel();
        $moduleModel = new CourseModuleModel();

        $activeTransition = $transitionModel->getActiveTransition($candidateId);
        $modules = $activeTransition ? $moduleModel->getModulesByTransition($activeTransition['id']) : [];

        return $this->respond([
            'status' => 'success',
            'data' => [
                'transition' => $activeTransition,
                'modules' => $modules
            ]
        ]);
    }

    public function getLessons($moduleId)
    {
        $moduleId = (int) $moduleId;
        if ($moduleId <= 0) {
            return $this->fail('Invalid Module ID');
        }

        $lessonModel = new CourseLessonModel();
        $lessons = $lessonModel->getLessonsByModule($moduleId);

        // Decode exercises and resources for mobile use
        foreach ($lessons as &$lesson) {
            if (isset($lesson['resources']) && is_string($lesson['resources'])) {
                $decoded = json_decode($lesson['resources'], true);
                $lesson['resources'] = is_array($decoded) ? $decoded : [$lesson['resources']];
            }
            if (isset($lesson['exercises']) && is_string($lesson['exercises'])) {
                $decoded = json_decode($lesson['exercises'], true);
                $lesson['exercises'] = is_array($decoded) ? $decoded : [$lesson['exercises']];
            }
        }

        return $this->respond([
            'status' => 'success',
            'data' => [
                'lessons' => $lessons
            ]
        ]);
    }

    public function completeTask($taskId)
    {
        $taskId = (int) $taskId;
        if ($taskId <= 0) {
            return $this->fail('Invalid Task ID');
        }

        $taskModel = new DailyTaskModel();
        $taskModel->markComplete($taskId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Task completed successfully'
        ]);
    }

    public function reset()
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $db = \Config\Database::connect();
        $db->query(
            "UPDATE career_transitions SET status = 'inactive', deactivated_at = NOW() WHERE candidate_id = ? AND status = 'active'",
            [$candidateId]
        );

        return $this->respond([
            'status' => 'success',
            'message' => 'Career path saved to history. You can now start a new journey!'
        ]);
    }

    public function history($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $transitionModel = new CareerTransitionModel();
        $transitions = $transitionModel
            ->where('candidate_id', $candidateId)
            ->orderBy('status', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => [
                'transitions' => $transitions
            ]
        ]);
    }

    public function reactivate()
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        $transitionId = (int) $this->request->getPost('transition_id');

        if ($candidateId <= 0 || $transitionId <= 0) {
            return $this->fail('Invalid inputs. candidate_id and transition_id are required.');
        }

        $transitionModel = new CareerTransitionModel();
        $db = \Config\Database::connect();
        $transition = $transitionModel->find($transitionId);

        if (!$transition || $transition['candidate_id'] != $candidateId) {
            return $this->fail('Invalid transition selected.');
        }

        $db->query("UPDATE career_transitions SET status = 'inactive', deactivated_at = NOW() WHERE candidate_id = ? AND status = 'active'", [$candidateId]);
        $db->query("UPDATE career_transitions SET status = 'active', reactivated_at = NOW(), reactivation_count = reactivation_count + 1 WHERE id = ?", [$transitionId]);
        $db->query("UPDATE daily_tasks SET is_completed = 0, completed_at = NULL WHERE transition_id = ?", [$transitionId]);

        return $this->respond([
            'status' => 'success',
            'message' => 'Career path reactivated! Progress has been reset for a fresh start.'
        ]);
    }
}
