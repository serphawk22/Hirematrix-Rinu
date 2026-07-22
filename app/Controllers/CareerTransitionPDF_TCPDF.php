<?php

namespace App\Controllers;

use App\Models\CareerTransitionModel;
use App\Models\CourseModuleModel;
use App\Models\CourseLessonModel;
use App\Models\DailyTaskModel;
use App\Models\CandidateSkillsModel;
use App\Models\WorkExperienceModel;
use App\Models\UserModel;
use App\Libraries\CareerTransitionAI;

class CareerTransitionPDF_TCPDF extends BaseController
{
    /**
     * Download entire course as PDF using PHP only (no Python required)
     */
    public function downloadCoursePDF()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }

        $candidateId = (int) session()->get('user_id');
        helper('premium');
        requirePremiumForFeature($candidateId, 'career transition');

        $transitionModel = new CareerTransitionModel();
        $moduleModel = new CourseModuleModel();
        $lessonModel = new CourseLessonModel();
        $taskModel = new DailyTaskModel();

        // Get active transition
        $activeTransition = $transitionModel->getActiveTransition($candidateId);
        
        if (!$activeTransition) {
            return redirect()->to('career-transition')->with('error', 'No active career transition found.');
        }

        // Get all course data
        $modules = $moduleModel->getModulesByTransition($activeTransition['id']);
        $tasks = $taskModel->getTasksByTransition($activeTransition['id']);

        // Add lessons to each module
        foreach ($modules as &$module) {
            $module['lessons'] = $lessonModel->getLessonsByModule($module['id']);
        }
        unset($module);

        if ($this->courseContainsBriefLessons($modules)) {
            return redirect()->to('career-transition/course')->with(
                'error',
                'Prepare the full course from the PDF button before downloading.'
            );
        }

        // Generate PDF
        try {
            $pdfPath = $this->generateCoursePDF($activeTransition, $modules, $tasks);
            
            // Download the PDF
            $filename = $this->sanitizeFilename(
                $activeTransition['current_role'] . '_to_' . $activeTransition['target_role'] . '_Course.pdf'
            );
            
            return $this->response->download($pdfPath, null)->setFileName($filename);
        } catch (\Throwable $e) {
            log_message('error', 'PDF Generation Error: ' . $e->getMessage());
            return redirect()->to('career-transition')->with('error', 'Failed to generate PDF. Please try again.');
        }
    }

    /**
     * Prepare one lesson per request. This avoids making several slow AI calls
     * inside the file-download response and keeps each request below the server timeout.
     */
    public function prepareCoursePDF()
    {
        if (session()->get('role') !== 'candidate') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied.']);
        }

        $candidateId = (int) session()->get('user_id');
        helper('premium');
        requirePremiumForFeature($candidateId, 'career transition');

        $transition = (new CareerTransitionModel())->getActiveTransition($candidateId);
        if (!$transition) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'No active career transition found.']);
        }

        $moduleModel = new CourseModuleModel();
        $lessonModel = new CourseLessonModel();
        $modules = $moduleModel->getModulesByTransition((int) $transition['id']);
        $total = 0;
        $prepared = 0;
        $nextModule = null;
        $nextLesson = null;

        foreach ($modules as $module) {
            foreach ($lessonModel->getLessonsByModule((int) $module['id']) as $lesson) {
                $total++;
                if ($this->lessonNeedsExpansion((string) ($lesson['content'] ?? ''))) {
                    if ($nextLesson === null) {
                        $nextModule = $module;
                        $nextLesson = $lesson;
                    }
                } else {
                    $prepared++;
                }
            }
        }

        if ($nextLesson === null) {
            return $this->pdfPreparationResponse(true, $prepared, $total);
        }

        $skillGaps = json_decode((string) ($transition['skill_gaps'] ?? '[]'), true);
        $skillGaps = is_array($skillGaps) ? array_values(array_filter(array_map('trim', $skillGaps))) : [];
        $metadata = json_decode((string) ($nextModule['content'] ?? ''), true);
        $moduleGaps = is_array($metadata) && is_array($metadata['covered_skill_gaps'] ?? null)
            ? array_values(array_filter(array_map('trim', $metadata['covered_skill_gaps'])))
            : $skillGaps;

        $candidateSkills = (new CandidateSkillsModel())->where('candidate_id', $candidateId)->findAll();
        $candidateSkillNames = array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['skill_name'] ?? '')),
            $candidateSkills
        )));
        $workModel = new WorkExperienceModel();
        $latestWork = $workModel->where('user_id', $candidateId)->where('is_current', 1)->first()
            ?: $workModel->where('user_id', $candidateId)->orderBy('start_date', 'DESC')->first();
        $user = (new UserModel())->find($candidateId) ?? [];
        $context = [
            'current_role' => (string) ($transition['current_role'] ?? ''),
            'target_role' => (string) ($transition['target_role'] ?? ''),
            'candidate_skills' => $candidateSkillNames,
            'current_company' => (string) ($latestWork['company_name'] ?? ''),
            'candidate_bio' => trim((string) ($user['bio'] ?? '')),
        ];

        // Do not lock the candidate's session while waiting for the AI service.
        session_write_close();

        try {
            $generated = (new CareerTransitionAI())->generateLessonContent(
                (string) ($transition['current_role'] ?? ''),
                (string) ($transition['target_role'] ?? ''),
                $moduleGaps ?: $skillGaps,
                $nextModule,
                $nextLesson,
                $context
            );
            $content = $this->normalizeGeneratedLessonContent((string) ($generated['content'] ?? ''));
            if (!$this->isGeneratedLessonUsable($content)) {
                $wordCount = str_word_count(trim(strip_tags($content)));
                throw new \RuntimeException('The generated lesson was too brief or missing its teaching sections (words: ' . $wordCount . ').');
            }

            $lessonModel->update((int) $nextLesson['id'], [
                'content' => $content,
                'resources' => json_encode($generated['resources'] ?? []),
                'exercises' => json_encode($generated['exercises'] ?? []),
            ]);

            $prepared++;
            return $this->pdfPreparationResponse($prepared >= $total, $prepared, $total);
        } catch (\Throwable $e) {
            log_message('error', 'PDF lesson preparation failed for lesson ' . (int) $nextLesson['id'] . ': ' . $e->getMessage());
            return $this->response->setStatusCode($e instanceof \RuntimeException ? 422 : 500)->setJSON([
                'success' => false,
                'retryable' => true,
                'prepared' => $prepared,
                'total' => $total,
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
                'message' => $e instanceof \RuntimeException
                    ? 'A lesson response was incomplete. Please try again.'
                    : 'The lesson service was temporarily unavailable. Please try again.',
            ]);
        }
    }

    private function normalizeGeneratedLessonContent(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        $sectionNames = 'What You Will Build|Lesson|Guided Walkthrough|Worked Example|Practice Lab|Common Mistakes|Career Application|Knowledge Check|Completion Checklist';
        $content = preg_replace(
            '/^\s*\*\*\s*(' . $sectionNames . ')\s*:?\s*\*\*\s*$/mi',
            '## $1',
            $content
        ) ?? $content;
        $content = preg_replace(
            '/^\s*#{1,4}\s+(' . $sectionNames . ')\s*:?\s*$/mi',
            '## $1',
            $content
        ) ?? $content;

        return trim($content);
    }

    private function pdfPreparationResponse(bool $ready, int $prepared, int $total)
    {
        return $this->response->setJSON([
            'success' => true,
            'ready' => $ready,
            'prepared' => $prepared,
            'total' => $total,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function expandBriefLessonsForPdf(array $transition, array $modules): array
    {
        $skillGaps = json_decode((string) ($transition['skill_gaps'] ?? '[]'), true);
        $skillGaps = is_array($skillGaps)
            ? array_values(array_filter(array_map('trim', $skillGaps)))
            : [];
        $candidateId = (int) ($transition['candidate_id'] ?? session()->get('user_id'));

        $candidateSkills = (new CandidateSkillsModel())
            ->where('candidate_id', $candidateId)
            ->findAll();
        $candidateSkillNames = array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['skill_name'] ?? '')),
            $candidateSkills
        )));

        $workModel = new WorkExperienceModel();
        $latestWork = $workModel->where('user_id', $candidateId)->where('is_current', 1)->first();
        if (empty($latestWork)) {
            $latestWork = $workModel->where('user_id', $candidateId)->orderBy('start_date', 'DESC')->first();
        }
        $user = (new UserModel())->find($candidateId) ?? [];
        $context = [
            'current_role' => (string) ($transition['current_role'] ?? ''),
            'target_role' => (string) ($transition['target_role'] ?? ''),
            'candidate_skills' => $candidateSkillNames,
            'current_company' => (string) ($latestWork['company_name'] ?? ''),
            'candidate_bio' => trim((string) ($user['bio'] ?? '')),
        ];

        $ai = new CareerTransitionAI();
        foreach ($modules as &$module) {
            $metadata = json_decode((string) ($module['content'] ?? ''), true);
            $moduleGaps = is_array($metadata) && is_array($metadata['covered_skill_gaps'] ?? null)
                ? array_values(array_filter(array_map('trim', $metadata['covered_skill_gaps'])))
                : $skillGaps;
            $module['covered_skill_gaps'] = $moduleGaps;

            foreach ($module['lessons'] as &$lesson) {
                if (!$this->lessonNeedsExpansion((string) ($lesson['content'] ?? ''))) {
                    continue;
                }

                $db = \Config\Database::connect();
                try {
                    $db->close();
                    $generated = $ai->generateLessonContent(
                        (string) ($transition['current_role'] ?? ''),
                        (string) ($transition['target_role'] ?? ''),
                        $moduleGaps ?: $skillGaps,
                        $module,
                        $lesson,
                        $context
                    );
                    $db->reconnect();

                    if (!empty($generated['content'])) {
                        $lesson['content'] = trim((string) $generated['content']);
                        $lesson['resources'] = json_encode($generated['resources'] ?? []);
                        $lesson['exercises'] = json_encode($generated['exercises'] ?? []);
                        (new CourseLessonModel())->update((int) $lesson['id'], [
                            'content' => $lesson['content'],
                            'resources' => $lesson['resources'],
                            'exercises' => $lesson['exercises'],
                        ]);
                    }
                } catch (\Throwable $e) {
                    $db->reconnect();
                    log_message('error', 'Full lesson generation failed during PDF export for lesson '
                        . (int) ($lesson['id'] ?? 0) . ': ' . $e->getMessage());
                }
            }
            unset($lesson);
        }
        unset($module);

        return $modules;
    }

    private function lessonNeedsExpansion(string $content): bool
    {
        $plainText = trim(strip_tags($content));
        if ($plainText === '') {
            return true;
        }

        if (str_word_count($plainText) < 350) {
            return true;
        }

        return preg_match('/\b(?:in this lesson|you will learn|this lesson covers)\b/i', $plainText) === 1
            && str_word_count($plainText) < 650;
    }

    private function isGeneratedLessonUsable(string $content): bool
    {
        $plainText = trim(strip_tags($content));
        if (str_word_count($plainText) < 350) {
            return false;
        }

        preg_match_all(
            '/^#{1,4}\s+(?:What You Will Build|Lesson|Guided Walkthrough|Worked Example|Practice Lab|Common Mistakes|Career Application|Knowledge Check|Completion Checklist)\s*$/mi',
            $content,
            $matches
        );

        return count(array_unique(array_map('strtolower', $matches[0] ?? []))) >= 4;
    }

    private function courseContainsBriefLessons(array $modules): bool
    {
        foreach ($modules as $module) {
            foreach ((array) ($module['lessons'] ?? []) as $lesson) {
                if ($this->lessonNeedsExpansion((string) ($lesson['content'] ?? ''))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate PDF using TCPDF (PHP library)
     */
    private function generateCoursePDF($transition, $modules, $tasks)
    {
        // Load TCPDF library
        require_once APPPATH . '../vendor/autoload.php';
        
        // Create new PDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Career Transition AI');
        $pdf->SetAuthor('Career Transition Platform');
        $pdf->SetTitle($transition['current_role'] . ' to ' . $transition['target_role'] . ' - Course');
        $pdf->SetSubject('Career Transition Course');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Set font
        $pdf->SetFont('helvetica', '', 11);

        // Add a page
        $pdf->AddPage();

        // Cover page
        $pdf->SetFont('helvetica', 'B', 28);
        $pdf->SetTextColor(102, 126, 234);
        $pdf->Cell(0, 20, '', 0, 1); // Spacer
        $pdf->Cell(0, 20, 'Career Transition Course', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetTextColor(118, 75, 162);
        $pdf->Cell(0, 15, $transition['current_role'] . ' to ' . $transition['target_role'], 0, 1, 'C');

        // Skill gaps
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(102, 126, 234);
        $pdf->Ln(10);
        $pdf->Cell(0, 10, 'Skill Gaps to Address:', 0, 1);
        
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        
        $skillGaps = json_decode($transition['skill_gaps'], true);
        if ($skillGaps && is_array($skillGaps)) {
            foreach ($skillGaps as $gap) {
                $pdf->Cell(10, 7, '-', 0, 0);
                $pdf->MultiCell(0, 7, $gap, 0, 'L');
            }
        }

        // New page for Table of Contents
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->SetTextColor(102, 126, 234);
        $pdf->Cell(0, 15, 'Table of Contents', 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        
        foreach ($modules as $module) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetTextColor(102, 126, 234);
            $pdf->Cell(0, 8, 'Module ' . $module['module_number'] . ': ' . $module['title'], 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(10, 6, '', 0, 0);
            $pdf->MultiCell(0, 6, $module['description'], 0, 'L');
            $pdf->Ln(2);
        }

        // Daily Tasks
        if (!empty($tasks)) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->SetTextColor(102, 126, 234);
            $pdf->Cell(0, 15, 'Daily Learning Tasks', 0, 1);
            
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->MultiCell(0, 7, 'Quick 5-10 minute tasks to reinforce your learning:', 0, 'L');
            $pdf->Ln(5);

            // Table header
            $pdf->SetFillColor(102, 126, 234);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 11);
            
            $pdf->Cell(20, 8, 'Day', 1, 0, 'C', true);
            $pdf->Cell(135, 8, 'Task', 1, 0, 'L', true);
            $pdf->Cell(25, 8, 'Duration', 1, 1, 'C', true);

            // Table content
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 10);
            
            $fill = false;
            foreach (array_slice($tasks, 0, 30) as $task) {
                if ($fill) {
                    $pdf->SetFillColor(248, 249, 250);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                }
                
                $pdf->Cell(20, 7, $task['day_number'], 1, 0, 'C', true);
                $pdf->Cell(135, 7, substr($task['task_title'], 0, 80), 1, 0, 'L', true);
                $pdf->Cell(25, 7, $task['duration_minutes'] . ' min', 1, 1, 'C', true);
                
                $fill = !$fill;
            }
        }

        // Modules and Lessons
        foreach ($modules as $module) {
            $pdf->AddPage();
            
            // Module header
            $pdf->SetFont('helvetica', 'B', 22);
            $pdf->SetTextColor(102, 126, 234);
            $pdf->Cell(0, 15, 'Module ' . $module['module_number'] . ': ' . $module['title'], 0, 1);
            
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->MultiCell(0, 7, $module['description'], 0, 'L');
            
            if (!empty($module['duration_weeks'])) {
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(0, 7, 'Duration: ' . $module['duration_weeks'] . ' weeks', 0, 1);
            }
            
            $pdf->Ln(5);

            // Lessons
            if (!empty($module['lessons'])) {
                foreach ($module['lessons'] as $lesson) {
                    $pdf->SetFont('helvetica', 'B', 16);
                    $pdf->SetTextColor(51, 51, 51);
                    $pdf->Cell(0, 10, 'Lesson ' . $lesson['lesson_number'] . ': ' . $lesson['title'], 0, 1);
                    
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->SetTextColor(0, 0, 0);
                    
                    // Lesson content
                    if (!empty($lesson['content'])) {
                        $pdf->writeHTMLCell(
                            0,
                            0,
                            '',
                            '',
                            $this->markdownToPdfHtml((string) $lesson['content']),
                            0,
                            1,
                            false,
                            true,
                            'L',
                            true
                        );
                        $pdf->Ln(3);
                    }

                    // Resources
                    if (!empty($lesson['resources'])) {
                        $resources = is_string($lesson['resources']) 
                            ? json_decode($lesson['resources'], true) 
                            : $lesson['resources'];
                        
                        if (is_array($resources) && count($resources) > 0) {
                            $pdf->SetFont('helvetica', 'B', 12);
                            $pdf->SetTextColor(102, 126, 234);
                            $pdf->Cell(0, 8, 'Learning Resources', 0, 1);
                            
                            $pdf->SetFont('helvetica', '', 10);
                            $pdf->SetTextColor(0, 0, 0);
                            
                            foreach ($resources as $resource) {
                                $pdf->Cell(10, 6, '-', 0, 0);
                                $pdf->MultiCell(0, 6, $resource, 0, 'L');
                            }
                            $pdf->Ln(2);
                        }
                    }

                    // Exercises
                    if (!empty($lesson['exercises'])) {
                        $exercises = is_string($lesson['exercises']) 
                            ? json_decode($lesson['exercises'], true) 
                            : $lesson['exercises'];
                        
                        if (is_array($exercises) && count($exercises) > 0) {
                            $pdf->SetFont('helvetica', 'B', 12);
                            $pdf->SetTextColor(102, 126, 234);
                            $pdf->Cell(0, 8, 'Practice Exercises', 0, 1);
                            
                            $pdf->SetFont('helvetica', '', 10);
                            $pdf->SetTextColor(0, 0, 0);
                            $pdf->SetFillColor(255, 243, 205);
                            
                            foreach ($exercises as $index => $exercise) {
                                $pdf->Cell(10, 6, ($index + 1) . '.', 0, 0);
                                $pdf->MultiCell(0, 6, $exercise, 0, 'L', true);
                                $pdf->Ln(1);
                            }
                            $pdf->Ln(3);
                        }
                    }
                    
                    $pdf->Ln(5);
                }
            }
        }

        // Save PDF to file
        $filename = 'course_' . $transition['id'] . '_' . time() . '.pdf';
        $filepath = WRITEPATH . 'uploads/' . $filename;
        
        // Ensure directory exists
        if (!is_dir(WRITEPATH . 'uploads/')) {
            mkdir(WRITEPATH . 'uploads/', 0755, true);
        }
        
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }

    private function markdownToPdfHtml(string $markdown): string
    {
        $lines = preg_split('/\R/u', trim($markdown)) ?: [];
        $html = '';
        $listType = '';
        $inCode = false;
        $codeLines = [];

        $closeList = static function () use (&$html, &$listType): void {
            if ($listType !== '') {
                $html .= '</' . $listType . '>';
                $listType = '';
            }
        };

        $inline = static function (string $text): string {
            $text = esc($text);
            $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text) ?? $text;
            return preg_replace('/`([^`]+)`/u', '<code style="background-color:#eef2f7;color:#0f172a;">$1</code>', $text) ?? $text;
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '```')) {
                $closeList();
                if ($inCode) {
                    $html .= '<pre style="background-color:#0f172a;color:#f8fafc;padding:9px;font-family:courier;font-size:9pt;line-height:1.45;">'
                        . esc(implode("\n", $codeLines)) . '</pre>';
                    $codeLines = [];
                    $inCode = false;
                } else {
                    $inCode = true;
                }
                continue;
            }

            if ($inCode) {
                $codeLines[] = $line;
                continue;
            }

            if ($trimmed === '') {
                $closeList();
                continue;
            }

            if (preg_match('/^(#{1,4})\s+(.+)$/u', $trimmed, $match)) {
                $closeList();
                $level = min(4, strlen($match[1]) + 1);
                $size = [2 => '15pt', 3 => '12.5pt', 4 => '11pt'][$level] ?? '10.5pt';
                $html .= '<h' . $level . ' style="color:#0d8a90;font-size:' . $size . ';margin:12px 0 6px;">'
                    . $inline($match[2]) . '</h' . $level . '>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/u', $trimmed, $match)) {
                if ($listType !== 'ul') {
                    $closeList();
                    $html .= '<ul style="margin:4px 0 8px 18px;">';
                    $listType = 'ul';
                }
                $html .= '<li style="font-size:10.5pt;line-height:1.55;">' . $inline($match[1]) . '</li>';
                continue;
            }

            if (preg_match('/^\d+[.)]\s+(.+)$/u', $trimmed, $match)) {
                if ($listType !== 'ol') {
                    $closeList();
                    $html .= '<ol style="margin:4px 0 8px 18px;">';
                    $listType = 'ol';
                }
                $html .= '<li style="font-size:10.5pt;line-height:1.55;">' . $inline($match[1]) . '</li>';
                continue;
            }

            $closeList();
            $html .= '<p style="font-size:10.5pt;line-height:1.6;color:#1f2937;margin:0 0 8px;">'
                . $inline($trimmed) . '</p>';
        }

        $closeList();
        if ($inCode && $codeLines !== []) {
            $html .= '<pre style="background-color:#0f172a;color:#f8fafc;padding:9px;font-family:courier;font-size:9pt;line-height:1.45;">'
                . esc(implode("\n", $codeLines)) . '</pre>';
        }

        return $html;
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        return substr($filename, 0, 200) . '.pdf';
    }
}
