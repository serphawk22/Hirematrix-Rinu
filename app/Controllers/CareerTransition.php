<?php

namespace App\Controllers;

use App\Models\CareerTransitionModel;
use App\Models\DailyTaskModel;
use App\Models\CourseModuleModel;
use App\Models\CourseLessonModel;
use App\Libraries\CareerTransitionAI;

class CareerTransition extends BaseController
{
    private function requireCareerTransitionPremium(int $candidateId): void
    {
        helper('premium');
        requirePremiumForFeature($candidateId, 'career transition');
    }

    public function index()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $candidateId = (int) session()->get('user_id');
        $this->requireCareerTransitionPremium($candidateId);
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
        $this->requireCareerTransitionPremium($candidateId);

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
            $db->query(
                "UPDATE course_lessons SET is_completed = 0 WHERE module_id IN (SELECT id FROM course_modules WHERE transition_id = ?)",
                [$existingTransition['id']]
            );
            $courseRefreshed = $this->refreshCourseContentIfBrief($existingTransition);

            return redirect()->to('career-transition/course')
                ->with('success', $courseRefreshed
                    ? 'Welcome back! Your learning path was restored and the course content was refreshed with more detailed lessons.'
                    : 'Welcome back! Your learning path has been instantly restored - no AI generation needed!');
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

        $ai         = new CareerTransitionAI();
        $analysis   = $ai->analyzeTransition($savedCurrentRole, $savedTargetRole);

        $skillsModel = new \App\Models\CandidateSkillsModel();
        $workExpModel = new \App\Models\WorkExperienceModel();
        $userModel = new \App\Models\UserModel();

        $candidateSkills = $skillsModel->where('candidate_id', $savedCandidateId)->findAll();
        $candidateSkillNames = array_values(array_filter(array_map(static function (array $row): string {
            return trim((string) ($row['skill_name'] ?? ''));
        }, $candidateSkills)));

        $currentCompany = '';
        $latestWork = $workExpModel->where('user_id', $savedCandidateId)->where('is_current', 1)->first();
        if (empty($latestWork)) {
            $latestWork = $workExpModel->where('user_id', $savedCandidateId)->orderBy('start_date', 'DESC')->first();
        }
        if (!empty($latestWork['company_name'])) {
            $currentCompany = (string) $latestWork['company_name'];
        }

        $user = $userModel->find($savedCandidateId);
        $candidateBio = trim((string) ($user['bio'] ?? ''));

        $courseData = $ai->generateCourseContent(
            $savedCurrentRole,
            $savedTargetRole,
            $analysis['skill_gaps'] ?? [],
            [
                'current_role' => $savedCurrentRole,
                'target_role' => $savedTargetRole,
                'candidate_skills' => $candidateSkillNames,
                'current_company' => $currentCompany,
                'candidate_bio' => $candidateBio,
            ]
        );

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
                $moduleCoveredGaps = array_values(array_filter((array) ($module['covered_skill_gaps'] ?? [])));
                $moduleId = $moduleModel->insert([
                    'transition_id'  => $transitionId,
                    'module_number'  => $module['number'],
                    'title'          => $module['title'],
                    'description'    => $module['description'],
                    'duration_weeks' => $module['weeks'],
                    'content'        => !empty($moduleCoveredGaps) ? json_encode(['covered_skill_gaps' => $moduleCoveredGaps]) : null,
                ]);
                if (!empty($module['lessons'])) {
                    foreach ($module['lessons'] as $lesson) {
                        $lessonCoveredGaps = array_values(array_filter((array) ($lesson['covered_skill_gaps'] ?? [])));
                        $lessonContent = (string) ($lesson['content'] ?? '');
                        if (!empty($lessonCoveredGaps)) {
                            $lessonContent = "## Skill Gaps Covered\n- " . implode("\n- ", $lessonCoveredGaps) . "\n\n" . $lessonContent;
                        }
                        $lessonModel->insert([
                            'module_id'     => $moduleId,
                            'lesson_number' => $lesson['number'],
                            'title'         => $lesson['title'],
                            'content'       => $lessonContent,
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

        return redirect()->to('career-transition/course')
            ->with('success', 'Career transition plan created! AI-powered course content is ready.');
    }

    public function course()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $candidateId = (int) session()->get('user_id');
        $this->requireCareerTransitionPremium($candidateId);
        $transitionModel = new CareerTransitionModel();
        $moduleModel = new CourseModuleModel();
        $activeTransition = $transitionModel->getActiveTransition($candidateId);
        $modules = $activeTransition ? $moduleModel->getModulesByTransition($activeTransition['id']) : [];

        if (!$activeTransition || empty($modules)) {
            return redirect()->to('career-transition')
                ->with('error', 'No course content is available yet. Generate your career transition roadmap first.');
        }

        return redirect()->to('career-transition/module/' . (int) $modules[0]['id']);
    }

    public function module($moduleId)
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $candidateId = (int) session()->get('user_id');
        $this->requireCareerTransitionPremium($candidateId);
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

        $modules = $moduleModel->getModulesByTransition($activeTransition['id']);
        $lessons = $lessonModel->getLessonSummariesByModule($moduleId);
        $skillGaps = $this->parseSkillGaps($activeTransition['skill_gaps'] ?? '[]');
        $modules = $this->attachModuleSkillGaps($modules, $skillGaps);
        $module = $this->attachModuleSkillGaps([$module], $skillGaps)[0] ?? $module;
        $lessons = $this->attachLessonSkillGaps($lessons, $module['covered_skill_gaps'] ?? $skillGaps);
        $lessons = $this->ensureModuleHasEnoughLessons($activeTransition, $module, $lessons, $skillGaps);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'module' => [
                    'id' => (int) $module['id'],
                    'module_number' => (int) $module['module_number'],
                    'title' => (string) ($module['title'] ?? ''),
                    'description' => (string) ($module['description'] ?? ''),
                    'duration_weeks' => (int) ($module['duration_weeks'] ?? 0),
                    'covered_skill_gaps' => array_values((array) ($module['covered_skill_gaps'] ?? [])),
                ],
                'lessons' => array_map(static function (array $lesson): array {
                    return [
                        'id' => (int) $lesson['id'],
                        'lesson_number' => (int) $lesson['lesson_number'],
                        'title' => (string) ($lesson['title'] ?? ''),
                        'covered_skill_gaps' => array_values((array) ($lesson['covered_skill_gaps'] ?? [])),
                        'is_completed' => !empty($lesson['is_completed']),
                    ];
                }, $lessons),
            ]);
        }

        return view('candidate/course_content', [
            'transition' => $activeTransition,
            'modules' => $modules,
            'module' => $module,
            'lessons' => $lessons,
            'skillGaps' => $skillGaps,
        ]);
    }

    private function ensureModuleHasEnoughLessons(array $transition, array $module, array $lessons, array $skillGaps): array
    {
        if (count($lessons) >= 2 || !$this->moduleNeedsMultipleLessons($module, $lessons)) {
            return $lessons;
        }

        $candidateId = (int) ($transition['candidate_id'] ?? session()->get('user_id'));
        $context = $this->getCandidateCourseContext($candidateId, (string) ($transition['current_role'] ?? ''), (string) ($transition['target_role'] ?? ''));
        $moduleGaps = array_values(array_filter((array) ($module['covered_skill_gaps'] ?? $skillGaps)));

        $db = \Config\Database::connect();
        $db->close();

        $ai = new CareerTransitionAI();
        $generatedLessons = $ai->generateModuleLessons(
            (string) ($transition['current_role'] ?? ''),
            (string) ($transition['target_role'] ?? ''),
            $moduleGaps,
            $module,
            $context
        );

        $db->reconnect();

        if (count($generatedLessons) < 2) {
            return $lessons;
        }

        $lessonModel = new CourseLessonModel();
        $existingFirstLesson = $lessons[0] ?? null;

        foreach ($generatedLessons as $index => $generatedLesson) {
            $lessonData = [
                'module_id' => (int) $module['id'],
                'lesson_number' => $index + 1,
                'title' => $generatedLesson['title'] ?? ('Lesson ' . ($index + 1)),
                'content' => $generatedLesson['content'] ?? '',
                'resources' => json_encode($generatedLesson['resources'] ?? []),
                'exercises' => json_encode($generatedLesson['exercises'] ?? []),
            ];

            if ($index === 0 && !empty($existingFirstLesson['id'])) {
                $lessonModel->update((int) $existingFirstLesson['id'], $lessonData);
                continue;
            }

            $lessonData['is_completed'] = 0;
            $lessonModel->insert($lessonData);
        }

        $refreshedLessons = $lessonModel->getLessonSummariesByModule((int) $module['id']);
        return $this->attachLessonSkillGaps($refreshedLessons, $moduleGaps);
    }

    private function moduleNeedsMultipleLessons(array $module, array $lessons): bool
    {
        if (count($lessons) === 0) {
            return true;
        }

        $coveredGaps = array_values(array_filter((array) ($module['covered_skill_gaps'] ?? [])));
        $title = strtolower((string) ($module['title'] ?? ''));
        $description = strtolower((string) ($module['description'] ?? ''));
        $broadKeywords = ['database', 'backend', 'frontend', 'framework', 'cloud', 'security', 'analytics', 'data', 'api', 'integration', 'javascript', 'react', 'node', 'sql', 'nosql'];

        if (count($coveredGaps) > 1 || (int) ($module['duration_weeks'] ?? 0) >= 2) {
            return true;
        }

        foreach ($broadKeywords as $keyword) {
            if (str_contains($title, $keyword) || str_contains($description, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function lesson($lessonId)
    {
        if (session()->get('role') !== 'candidate') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false]);
        }

        $candidateId = (int) session()->get('user_id');
        $this->requireCareerTransitionPremium($candidateId);
        $transitionModel = new CareerTransitionModel();
        $moduleModel = new CourseModuleModel();
        $lessonModel = new CourseLessonModel();

        $activeTransition = $transitionModel->getActiveTransition($candidateId);
        $lesson = $lessonModel->find($lessonId);
        $module = $lesson ? $moduleModel->find($lesson['module_id']) : null;

        if (!$activeTransition || !$lesson || !$module || (int) $module['transition_id'] !== (int) $activeTransition['id']) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $skillGaps = $this->parseSkillGaps($activeTransition['skill_gaps'] ?? '[]');
        $module = $this->attachModuleSkillGaps([$module], $skillGaps)[0] ?? $module;
        $lesson = $this->attachLessonSkillGaps([$lesson], $module['covered_skill_gaps'] ?? $skillGaps)[0] ?? $lesson;

        if ($this->lessonNeedsFullCourseContent($lesson)) {
            $generatedLesson = $this->generateFullLessonOnDemand($activeTransition, $module, $lesson);
            if (!empty($generatedLesson['content'])) {
                $lesson['content'] = $generatedLesson['content'];
                if (array_key_exists('resources', $generatedLesson)) {
                    $lesson['resources'] = $generatedLesson['resources'];
                }
                if (array_key_exists('exercises', $generatedLesson)) {
                    $lesson['exercises'] = $generatedLesson['exercises'];
                }
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'lesson' => [
                'id' => (int) $lesson['id'],
                'lesson_number' => (int) $lesson['lesson_number'],
                'title' => (string) ($lesson['title'] ?? ''),
                'content' => (string) ($lesson['content'] ?? ''),
                'resources' => $this->decodeCourseList($lesson['resources'] ?? []),
                'exercises' => $this->decodeCourseList($lesson['exercises'] ?? []),
                'covered_skill_gaps' => array_values((array) ($lesson['covered_skill_gaps'] ?? [])),
                'is_completed' => !empty($lesson['is_completed']),
            ],
        ]);
    }

    private function lessonNeedsFullCourseContent(array $lesson): bool
    {
        $content = trim(strip_tags((string) ($lesson['content'] ?? '')));
        if ($content === '') {
            return true;
        }

        $wordCount = str_word_count($content);
        if ($wordCount < 900) {
            return true;
        }

        $outlineSignals = 0;
        foreach (['Concepts Covered:', 'Example:', 'Steps:', 'Exercise:', 'Checklist:', 'Resources:'] as $signal) {
            if (stripos((string) ($lesson['content'] ?? ''), $signal) !== false) {
                $outlineSignals++;
            }
        }

        return $outlineSignals >= 3 && $wordCount < 1300;
    }

    private function generateFullLessonOnDemand(array $transition, array $module, array $lesson): array
    {
        $candidateId = (int) ($transition['candidate_id'] ?? session()->get('user_id'));
        $this->requireCareerTransitionPremium($candidateId);

        $skillGaps = array_values(array_filter((array) ($lesson['covered_skill_gaps'] ?? $module['covered_skill_gaps'] ?? $this->parseSkillGaps($transition['skill_gaps'] ?? '[]'))));
        $context = $this->getCandidateCourseContext($candidateId, (string) ($transition['current_role'] ?? ''), (string) ($transition['target_role'] ?? ''));

        $db = \Config\Database::connect();
        $db->close();

        $ai = new CareerTransitionAI();
        $generated = $ai->generateLessonContent(
            (string) ($transition['current_role'] ?? ''),
            (string) ($transition['target_role'] ?? ''),
            $skillGaps,
            $module,
            $lesson,
            $context
        );

        $db->reconnect();

        if (empty($generated['content'])) {
            return [];
        }

        $lessonModel = new CourseLessonModel();
        $lessonModel->update((int) $lesson['id'], [
            'content' => $generated['content'],
            'resources' => json_encode($generated['resources'] ?? []),
            'exercises' => json_encode($generated['exercises'] ?? []),
        ]);

        return $generated;
    }

    private function getCandidateCourseContext(int $candidateId, string $currentRole, string $targetRole): array
    {
        $skillsModel = new \App\Models\CandidateSkillsModel();
        $workExpModel = new \App\Models\WorkExperienceModel();
        $userModel = new \App\Models\UserModel();

        $candidateSkills = $skillsModel->where('candidate_id', $candidateId)->findAll();
        $candidateSkillNames = array_values(array_filter(array_map(static function (array $row): string {
            return trim((string) ($row['skill_name'] ?? ''));
        }, $candidateSkills)));

        $latestWork = $workExpModel->where('user_id', $candidateId)->where('is_current', 1)->first();
        if (empty($latestWork)) {
            $latestWork = $workExpModel->where('user_id', $candidateId)->orderBy('start_date', 'DESC')->first();
        }

        $user = $userModel->find($candidateId);

        return [
            'current_role' => $currentRole,
            'target_role' => $targetRole,
            'candidate_skills' => $candidateSkillNames,
            'current_company' => !empty($latestWork['company_name']) ? (string) $latestWork['company_name'] : '',
            'candidate_bio' => trim((string) ($user['bio'] ?? '')),
        ];
    }

    public function completeTask($taskId)
    {
        if (session()->get('role') !== 'candidate') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false]);
        }

        $this->requireCareerTransitionPremium((int) session()->get('user_id'));
        $taskModel = new DailyTaskModel();
        $taskModel->markComplete($taskId);
        return $this->response->setJSON(['success' => true]);
    }

    public function completeLesson($lessonId)
    {
        if (session()->get('role') !== 'candidate') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false]);
        }

        $candidateId = (int) session()->get('user_id');
        $this->requireCareerTransitionPremium($candidateId);
        $transitionModel = new CareerTransitionModel();
        $moduleModel = new CourseModuleModel();
        $lessonModel = new CourseLessonModel();
        $taskModel = new DailyTaskModel();

        $activeTransition = $transitionModel->getActiveTransition($candidateId);
        $lesson = $lessonModel->find($lessonId);
        $module = $lesson ? $moduleModel->find($lesson['module_id']) : null;

        if (!$activeTransition || !$lesson || !$module || (int) $module['transition_id'] !== (int) $activeTransition['id']) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $lessonModel->update($lessonId, ['is_completed' => 1]);
        $taskModel
            ->where('transition_id', $activeTransition['id'])
            ->where('module_number', $module['module_number'])
            ->where('lesson_number', $lesson['lesson_number'])
            ->set(['is_completed' => 1, 'completed_at' => date('Y-m-d H:i:s')])
            ->update();

        return $this->response->setJSON(['success' => true]);
    }

    public function dismissSuggestion()
    {
        if (session()->get('role') !== 'candidate') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false]);
        }

        $this->requireCareerTransitionPremium((int) session()->get('user_id'));
        session()->remove('career_suggestions');
        return $this->response->setJSON(['success' => true]);
    }

    public function reset()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }

        $candidateId = (int) session()->get('user_id');
        $this->requireCareerTransitionPremium($candidateId);
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
        
        $candidateId = (int) session()->get('user_id');
        $this->requireCareerTransitionPremium($candidateId);
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
        
        $candidateId = (int) session()->get('user_id');
        $this->requireCareerTransitionPremium($candidateId);
        $transitionModel = new CareerTransitionModel();
        $db = \Config\Database::connect();
        $transition = $transitionModel->find($transitionId);
        if (!$transition || $transition['candidate_id'] != $candidateId) {
            return redirect()->to('career-transition')->with('error', 'Invalid transition selected.');
        }
        $db->query("UPDATE career_transitions SET status = 'inactive', deactivated_at = NOW() WHERE candidate_id = ? AND status = 'active'", [$candidateId]);
        $db->query("UPDATE career_transitions SET status = 'active', reactivated_at = NOW(), reactivation_count = reactivation_count + 1 WHERE id = ?", [$transitionId]);
        $db->query("UPDATE daily_tasks SET is_completed = 0, completed_at = NULL WHERE transition_id = ?", [$transitionId]);
        $db->query("UPDATE course_lessons SET is_completed = 0 WHERE module_id IN (SELECT id FROM course_modules WHERE transition_id = ?)", [$transitionId]);
        return redirect()->to('career-transition')
            ->with('success', 'Career path reactivated! Progress has been reset for a fresh start.');
    }

    private function parseSkillGaps($skillGaps): array
    {
        $decoded = is_string($skillGaps) ? json_decode($skillGaps, true) : $skillGaps;
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($gap): string {
            return trim((string) $gap);
        }, $decoded)));
    }

    private function decodeCourseList($value): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item): string {
            return trim((string) $item);
        }, $decoded)));
    }

    private function attachModuleSkillGaps(array $modules, array $skillGaps): array
    {
        if (empty($modules) || empty($skillGaps)) {
            return $modules;
        }

        $moduleCount = max(1, count($modules));
        foreach ($modules as $index => &$module) {
            $searchText = strtolower(($module['title'] ?? '') . ' ' . ($module['description'] ?? '') . ' ' . ($module['content'] ?? ''));
            $covered = [];
            $metadata = is_string($module['content'] ?? null) ? json_decode($module['content'], true) : null;

            if (is_array($metadata) && !empty($metadata['covered_skill_gaps']) && is_array($metadata['covered_skill_gaps'])) {
                $covered = array_values(array_filter(array_map('trim', $metadata['covered_skill_gaps'])));
            }

            if (empty($covered)) {
                foreach ($skillGaps as $gap) {
                    $gapText = strtolower($gap);
                    if ($gapText !== '' && str_contains($searchText, $gapText)) {
                        $covered[] = $gap;
                    }
                }
            }

            if (empty($covered)) {
                foreach ($skillGaps as $gapIndex => $gap) {
                    if ($gapIndex % $moduleCount === $index % $moduleCount) {
                        $covered[] = $gap;
                    }
                }
            }

            $module['covered_skill_gaps'] = array_values(array_unique($covered ?: array_slice($skillGaps, 0, 2)));
        }

        return $modules;
    }

    private function attachLessonSkillGaps(array $lessons, array $skillGaps): array
    {
        if (empty($lessons) || empty($skillGaps)) {
            return $lessons;
        }

        $lessonCount = max(1, count($lessons));
        foreach ($lessons as $index => &$lesson) {
            $searchText = strtolower(($lesson['title'] ?? '') . ' ' . ($lesson['content'] ?? ''));
            $covered = [];

            foreach ($skillGaps as $gap) {
                $gapText = strtolower($gap);
                if ($gapText !== '' && str_contains($searchText, $gapText)) {
                    $covered[] = $gap;
                }
            }

            if (empty($covered)) {
                foreach ($skillGaps as $gapIndex => $gap) {
                    if ($gapIndex % $lessonCount === $index % $lessonCount) {
                        $covered[] = $gap;
                    }
                }
            }

            $lesson['covered_skill_gaps'] = array_values(array_unique($covered ?: array_slice($skillGaps, 0, 2)));
        }

        return $lessons;
    }

    private function refreshCourseContentIfBrief(array $transition): bool
    {
        $transitionId = (int) ($transition['id'] ?? 0);
        if ($transitionId <= 0 || !$this->transitionCourseIsTooBrief($transitionId)) {
            return false;
        }

        $skillGaps = $this->parseSkillGaps($transition['skill_gaps'] ?? '[]');
        $db = \Config\Database::connect();
        $db->close();

        helper('premium');
        requirePremiumForFeature((int) ($transition['candidate_id'] ?? session()->get('user_id')), 'career transition AI');
        $ai = new CareerTransitionAI();

        $skillsModel = new \App\Models\CandidateSkillsModel();
        $workExpModel = new \App\Models\WorkExperienceModel();
        $userModel = new \App\Models\UserModel();

        $candidateSkills = $skillsModel->where('candidate_id', (int) ($transition['candidate_id'] ?? 0))->findAll();
        $candidateSkillNames = array_values(array_filter(array_map(static function (array $row): string {
            return trim((string) ($row['skill_name'] ?? ''));
        }, $candidateSkills)));

        $latestWork = $workExpModel->where('user_id', (int) ($transition['candidate_id'] ?? 0))->where('is_current', 1)->first();
        if (empty($latestWork)) {
            $latestWork = $workExpModel->where('user_id', (int) ($transition['candidate_id'] ?? 0))->orderBy('start_date', 'DESC')->first();
        }
        $currentCompany = !empty($latestWork['company_name']) ? (string) $latestWork['company_name'] : '';
        $user = $userModel->find((int) ($transition['candidate_id'] ?? 0));
        $candidateBio = trim((string) ($user['bio'] ?? ''));

        $courseData = $ai->generateCourseContent(
            (string) ($transition['current_role'] ?? ''),
            (string) ($transition['target_role'] ?? ''),
            $skillGaps,
            [
                'current_role' => (string) ($transition['current_role'] ?? ''),
                'target_role' => (string) ($transition['target_role'] ?? ''),
                'candidate_skills' => $candidateSkillNames,
                'current_company' => $currentCompany,
                'candidate_bio' => $candidateBio,
            ]
        );

        $db->reconnect();
        if (empty($courseData['modules'])) {
            return false;
        }

        $this->replaceTransitionCourse($transitionId, $courseData);
        return true;
    }

    private function transitionCourseIsTooBrief(int $transitionId): bool
    {
        $moduleModel = new CourseModuleModel();
        $lessonModel = new CourseLessonModel();
        $modules = $moduleModel->getModulesByTransition($transitionId);

        if (empty($modules)) {
            return true;
        }

        $lessonCount = 0;
        $totalWords = 0;

        foreach ($modules as $module) {
            $lessons = $lessonModel->getLessonsByModule((int) $module['id']);
            foreach ($lessons as $lesson) {
                $lessonCount++;
                $wordCount = str_word_count(strip_tags((string) ($lesson['content'] ?? '')));
                $totalWords += $wordCount;
                if ($wordCount < 800) {
                    return true;
                }
            }
        }

        if ($lessonCount === 0) {
            return true;
        }

        return ($totalWords / $lessonCount) < 950;
    }

    private function replaceTransitionCourse(int $transitionId, array $courseData): void
    {
        $moduleModel = new CourseModuleModel();
        $lessonModel = new CourseLessonModel();
        $taskModel = new DailyTaskModel();

        $taskModel->where('transition_id', $transitionId)->delete();
        $moduleModel->where('transition_id', $transitionId)->delete();

        foreach (($courseData['modules'] ?? []) as $module) {
            $moduleCoveredGaps = array_values(array_filter((array) ($module['covered_skill_gaps'] ?? [])));
            $moduleId = $moduleModel->insert([
                'transition_id'  => $transitionId,
                'module_number'  => $module['number'] ?? 1,
                'title'          => $module['title'] ?? 'Course Module',
                'description'    => $module['description'] ?? '',
                'duration_weeks' => $module['weeks'] ?? 2,
                'content'        => !empty($moduleCoveredGaps) ? json_encode(['covered_skill_gaps' => $moduleCoveredGaps]) : null,
            ]);

            foreach (($module['lessons'] ?? []) as $lesson) {
                $lessonCoveredGaps = array_values(array_filter((array) ($lesson['covered_skill_gaps'] ?? [])));
                $lessonContent = (string) ($lesson['content'] ?? '');
                if (!empty($lessonCoveredGaps)) {
                    $lessonContent = "## Skill Gaps Covered\n- " . implode("\n- ", $lessonCoveredGaps) . "\n\n" . $lessonContent;
                }

                $lessonModel->insert([
                    'module_id'     => $moduleId,
                    'lesson_number' => $lesson['number'] ?? 1,
                    'title'         => $lesson['title'] ?? 'Lesson',
                    'content'       => $lessonContent,
                    'resources'     => is_array($lesson['resources'] ?? null) ? json_encode($lesson['resources']) : ($lesson['resources'] ?? '[]'),
                    'exercises'     => is_array($lesson['exercises'] ?? null) ? json_encode($lesson['exercises']) : ($lesson['exercises'] ?? '[]'),
                    'is_completed'  => 0,
                ]);
            }
        }

        $dailyTasks = $courseData['daily_tasks'] ?? [];
        foreach ($dailyTasks as $index => $task) {
            $taskModel->insert([
                'transition_id'    => $transitionId,
                'task_title'       => $task['title'] ?? 'Lesson',
                'task_description' => $task['description'] ?? 'Complete the lesson and checklist',
                'duration_minutes' => $task['duration'] ?? 45,
                'day_number'       => $task['day'] ?? ($index + 1),
                'module_number'    => $task['module'] ?? null,
                'lesson_number'    => $task['lesson'] ?? null,
            ]);
        }
    }
}
