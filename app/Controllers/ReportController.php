<?php

namespace App\Controllers;

use App\Models\JobModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ReportController extends BaseController
{
    protected JobModel $jobModel;

    public function __construct()
    {
        $this->jobModel = new JobModel();
    }

    /**
     * GET /recruiter/reports
     *
     * One route, one controller, one view — handles:
     *   - initial page load (form only)
     *   - "One Click Report" (period = yesterday|week|month), browser preview
     *   - "Customised Report" (date_from/date_to + filters), browser preview
     *   - Excel export (format=excel) for either of the above
     *
     * Examples:
     *   /recruiter/reports
     *   /recruiter/reports?generate=1&period=week
     *   /recruiter/reports?generate=1&format=browser&date_from=2026-06-01&date_to=2026-07-09&status=open
     *   /recruiter/reports?generate=1&format=excel&date_from=2026-06-01&date_to=2026-07-09
     */
    public function index()
    {
        $currentUserId = session()->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/login')->with('error', 'Please log in to view reports.');
        }

        $generate = $this->request->getGet('generate');
        $period   = $this->request->getGet('period');

        $filters = [
            'recruiter_id'    => $currentUserId,
            'status'          => $this->request->getGet('status'),
            'category'        => $this->request->getGet('category'),
            'employment_type' => $this->request->getGet('employment_type'),
            'keyword'         => $this->request->getGet('keyword'),
            'date_from'       => $this->request->getGet('date_from'),
            'date_to'         => $this->request->getGet('date_to'),
        ];

        if ($period) {
            [$from, $to] = $this->resolvePeriod($period);
            $filters['date_from'] = $from;
            $filters['date_to']   = $to;
        }

        // Every "Generate" click is now a direct Excel download — no browser preview.
        if ($generate) {
            $this->streamExcel($filters);
            exit; // headers + file already written to output
        }

        return view('recruiter/jobs_report', [
            'filters' => $filters,
            'period'  => $period,
        ]);
    }

    /**
     * Builds and streams the .xlsx file for the given filters.
     *
     * Columns: Job ID, Title, Recruiter Name, Category, Company, Posted For,
     * Client Company, Location, Employment Type, Experience Level, Openings,
     * Status, AI Interview Policy, Min AI Cutoff Score, Salary Range,
     * Application Deadline, Description, Application Questionnaire, Posted On.
     *
     * (Candidate Fee Allowed intentionally removed.)
     */
    public function streamExcel(array $filters): void
    {
        $jobs = $this->jobModel->getReportRows($filters);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jobs Report');

        $headers = [
            'A1' => 'Job ID',
            'B1' => 'Title',
            'C1' => 'Recruiter Name',
            'D1' => 'Category',
            'E1' => 'Company',
            'F1' => 'Posted For',
            'G1' => 'Client Company',
            'H1' => 'Location',
            'I1' => 'Employment Type',
            'J1' => 'Experience Level',
            'K1' => 'Openings',
            'L1' => 'Status',
            'M1' => 'AI Interview Policy',
            'N1' => 'Min AI Cutoff Score',
            'O1' => 'Salary Range',
            'P1' => 'Application Deadline',
            'Q1' => 'Description',
            'R1' => 'Application Questionnaire',
            'S1' => 'Posted On',
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        $headerRange = 'A1:S1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1FB7B5'); // brand primary
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $row = 2;
        foreach ($jobs as $job) {
            $sheet->setCellValue("A{$row}", $job['id']);
            $sheet->setCellValue("B{$row}", $job['title']);
            $sheet->setCellValue("C{$row}", $job['recruiter_name'] ?? '');
            $sheet->setCellValue("D{$row}", $job['category']);
            $sheet->setCellValue("E{$row}", $job['company']);
            $sheet->setCellValue("F{$row}", $job['posted_for']);
            $sheet->setCellValue("G{$row}", ($job['client_disclosure'] ?? '') === 'visible' ? ($job['client_company_name'] ?? '') : 'Confidential');
            $sheet->setCellValue("H{$row}", $job['location']);
            $sheet->setCellValue("I{$row}", $job['employment_type']);
            $sheet->setCellValue("J{$row}", $job['experience_level']);
            $sheet->setCellValue("K{$row}", $job['openings']);
            $sheet->setCellValue("L{$row}", ucfirst((string) $job['status']));
            $sheet->setCellValue("M{$row}", $job['ai_interview_policy']);
            $sheet->setCellValue("N{$row}", $job['min_ai_cutoff_score']);
            $sheet->setCellValue("O{$row}", $job['salary_range'] ?? ($job['salary'] ?? ''));
            $sheet->setCellValue("P{$row}", $job['application_deadline']);
            $sheet->setCellValueExplicit("Q{$row}", strip_tags((string) $job['description']), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("R{$row}", strip_tags((string) $job['application_questionnaire']), DataType::TYPE_STRING);
            $sheet->setCellValue("S{$row}", $job['created_at']);
            $row++;
        }

        foreach (range('A', 'S') as $col) {
            if (in_array($col, ['Q', 'R'], true)) {
                $sheet->getColumnDimension($col)->setWidth(50);
                $sheet->getStyle($col)->getAlignment()->setWrapText(true);
            } else {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $sheet->setAutoFilter($headerRange);
        $sheet->freezePane('A2');

        $filename = 'jobs_report_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    /**
     * Converts a "One Click Report" period into a [from, to] date pair.
     */
    public function resolvePeriod(string $period): array
    {
        $today = new \DateTime();

        return match ($period) {
            'yesterday' => [
                (clone $today)->modify('-1 day')->format('Y-m-d'),
                (clone $today)->modify('-1 day')->format('Y-m-d'),
            ],
            'week' => [
                (clone $today)->modify('monday this week')->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            'month' => [
                (clone $today)->modify('first day of this month')->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            default => [null, null],
        };
    }
}