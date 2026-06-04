<?php

namespace App\Controllers;

use App\Models\CareerTransitionModel;
use App\Models\CourseModuleModel;
use App\Models\CourseLessonModel;
use App\Models\DailyTaskModel;

class CareerTransitionPDF_TCPDF extends BaseController
{
    /**
     * Download entire course as PDF using PHP only (no Python required)
     */
    public function downloadCoursePDF()
    {
        $candidateId = session()->get('user_id');
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

        // Generate PDF
        try {
            $pdfPath = $this->generateCoursePDF($activeTransition, $modules, $tasks);
            
            // Download the PDF
            $filename = $this->sanitizeFilename(
                $activeTransition['current_role'] . '_to_' . $activeTransition['target_role'] . '_Course.pdf'
            );
            
            return $this->response->download($pdfPath, null)->setFileName($filename);
        } catch (\Exception $e) {
            log_message('error', 'PDF Generation Error: ' . $e->getMessage());
            return redirect()->to('career-transition')->with('error', 'Failed to generate PDF. Please try again.');
        }
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
        $pdf->Cell(0, 15, $transition['current_role'] . ' → ' . $transition['target_role'], 0, 1, 'C');

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
                $pdf->Cell(10, 7, '•', 0, 0);
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
                        $pdf->SetFillColor(248, 249, 250);
                        $content = strip_tags($lesson['content']);
                        $pdf->MultiCell(0, 6, $content, 0, 'L', true);
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
                            $pdf->Cell(0, 8, '📚 Learning Resources:', 0, 1);
                            
                            $pdf->SetFont('helvetica', '', 10);
                            $pdf->SetTextColor(0, 0, 0);
                            
                            foreach ($resources as $resource) {
                                $pdf->Cell(10, 6, '•', 0, 0);
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
                            $pdf->Cell(0, 8, '✏️ Practice Exercises:', 0, 1);
                            
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

    /**
     * Sanitize filename
     */
    private function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        return substr($filename, 0, 200) . '.pdf';
    }
}