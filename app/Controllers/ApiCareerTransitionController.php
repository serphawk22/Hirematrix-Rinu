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

        $moduleModel = new CourseModuleModel();
        $module = $moduleModel->find($moduleId);

        if (!$module) {
            return $this->fail('Module not found');
        }

        $transitionModel = new CareerTransitionModel();
        $activeTransition = $transitionModel->find($module['transition_id']);

        if (!$activeTransition) {
            return $this->fail('Transition not found');
        }

        $skillGaps = $this->parseSkillGaps($activeTransition['skill_gaps'] ?? '[]');

        $lessonModel = new CourseLessonModel();
        $lessons = $lessonModel->getLessonsByModule($moduleId);
        $lessons = $this->ensureModuleHasEnoughLessons($activeTransition, $module, $lessons, $skillGaps);

        // Expand brief lessons and decode JSON arrays for mobile use
        foreach ($lessons as &$lesson) {
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

    private function ensureModuleHasEnoughLessons(array $transition, array $module, array $lessons, array $skillGaps): array
    {
        if (count($lessons) >= 2 || !$this->moduleNeedsMultipleLessons($module, $lessons)) {
            return $lessons;
        }

        $candidateId = (int) ($transition['candidate_id']);
        $context = $this->getCandidateCourseContext($candidateId, (string) ($transition['current_role'] ?? ''), (string) ($transition['target_role'] ?? ''));
        $moduleGaps = array_values(array_filter((array) ($module['covered_skill_gaps'] ?? $skillGaps)));

        $db = \Config\Database::connect();
        $db->close();

        $ai = new \App\Libraries\CareerTransitionAI();
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

        return $lessonModel->getLessonsByModule((int) $module['id']);
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
        $candidateId = (int) ($transition['candidate_id']);

        $skillGaps = array_values(array_filter((array) ($lesson['covered_skill_gaps'] ?? $module['covered_skill_gaps'] ?? $this->parseSkillGaps($transition['skill_gaps'] ?? '[]'))));
        $context = $this->getCandidateCourseContext($candidateId, (string) ($transition['current_role'] ?? ''), (string) ($transition['target_role'] ?? ''));

        $db = \Config\Database::connect();
        $db->close();

        $ai = new \App\Libraries\CareerTransitionAI();
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

    public function completeLesson($lessonId)
    {
        $lessonId = (int) $lessonId;
        if ($lessonId <= 0) {
            return $this->fail('Invalid Lesson ID');
        }

        $lessonModel = new CourseLessonModel();
        $lesson = $lessonModel->find($lessonId);
        
        if (!$lesson) {
            return $this->fail('Lesson not found');
        }

        $moduleModel = new CourseModuleModel();
        $module = $moduleModel->find($lesson['module_id']);

        if (!$module) {
            return $this->fail('Module not found');
        }

        $lessonModel->update($lessonId, ['is_completed' => 1]);

        $taskModel = new DailyTaskModel();
        $taskModel
            ->where('transition_id', $module['transition_id'])
            ->where('module_number', $module['module_number'])
            ->where('lesson_number', $lesson['lesson_number'])
            ->set(['is_completed' => 1, 'completed_at' => date('Y-m-d H:i:s')])
            ->update();

        return $this->respond([
            'status' => 'success',
            'message' => 'Lesson marked as complete'
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
