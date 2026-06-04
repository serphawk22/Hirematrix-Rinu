<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ApplicationModel;
use App\Models\JobModel;
use App\Models\RecruiterCandidateActionModel;
use App\Models\CandidateResumeVersionModel;

class ApiApplicationsController extends ResourceController
{
    protected $format = 'json';

    public function getApplications($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $applicationModel = model('ApplicationModel');
        $db = \Config\Database::connect();
        
        $hasPolicyColumn = $db->fieldExists('ai_interview_policy', 'jobs');
        $hasResumeVersions = $db->tableExists('candidate_resume_versions') && $db->fieldExists('resume_version_id', 'applications');
        $hasClientInfo = $db->fieldExists('client_company_name', 'jobs') && $db->fieldExists('client_disclosure', 'jobs') && $db->fieldExists('posted_for', 'jobs');
        $hasFeeInfo = $db->fieldExists('candidate_fee_allowed', 'jobs');

        $policySelect = $hasPolicyColumn
            ? 'jobs.ai_interview_policy'
            : "'REQUIRED_HARD' as ai_interview_policy";
            
        $resumeSelect = $hasResumeVersions
            ? 'candidate_resume_versions.title as resume_version_title,
                candidate_resume_versions.target_role as resume_version_target_role,
                candidate_resume_versions.summary as resume_version_summary,
                candidate_resume_versions.highlight_skills as resume_version_highlight_skills,
                candidate_resume_versions.content as resume_version_content,
                candidate_resume_versions.updated_at as resume_version_updated_at,'
            : "'' as resume_version_title, '' as resume_version_target_role, '' as resume_version_summary, '' as resume_version_highlight_skills, '' as resume_version_content, NULL as resume_version_updated_at,";
            
        $clientSelect = $hasClientInfo 
            ? 'jobs.posted_for, jobs.client_company_name, jobs.client_disclosure,' 
            : "'' as posted_for, '' as client_company_name, 'visible' as client_disclosure,";
            
        $feeSelect = $hasFeeInfo 
            ? 'jobs.candidate_fee_allowed,' 
            : "0 as candidate_fee_allowed,";
        
        $builder = $applicationModel
            ->select('
                applications.*,
                jobs.title as job_title,
                jobs.company,
                jobs.location,
                jobs.salary_range,
                ' . $clientSelect . '
                ' . $feeSelect . '
                jobs.description as job_description,
                jobs.required_skills,
                jobs.experience_level,
                ' . $resumeSelect . '
                ' . $policySelect . ',
                0 as technical_score,
                0 as communication_score,
                0 as overall_rating,
                NULL as ai_interview_completed
            ')
            ->join('jobs', 'jobs.id = applications.job_id', 'left');

        if ($hasResumeVersions) {
            $builder->join('candidate_resume_versions', 'candidate_resume_versions.id = applications.resume_version_id', 'left');
        }

        $applications = $builder
            ->where('applications.candidate_id', $candidateId)
            ->orderBy('applications.applied_at', 'DESC')
            ->findAll();

        $applicationIds = array_values(array_filter(array_map(static function ($app) {
            return (int) ($app['id'] ?? 0);
        }, $applications)));

        $activitySummary = [];
        if (!empty($applicationIds)) {
            $activitySummary = (new RecruiterCandidateActionModel())
                ->getSummaryByApplicationIds($candidateId, $applicationIds);
        }

        foreach ($applications as &$application) {
            if (($application['posted_for'] ?? '') === 'client') {
                if (($application['client_disclosure'] ?? '') === 'visible' && !empty($application['client_company_name'])) {
                    $application['company'] = $application['client_company_name'];
                } else {
                    $application['company'] = ($application['company'] ?? 'Recruiter') . ' (Hiring for a Client)';
                }
            }

            $appId = (int) ($application['id'] ?? 0);
            $application['recruiter_activity'] = $activitySummary[$appId] ?? [
                'profile_unique_recruiters' => 0,
                'contact_unique_recruiters' => 0,
                'resume_unique_recruiters' => 0,
                'profile_viewed_count' => 0,
                'contact_viewed_count' => 0,
                'resume_downloaded_count' => 0,
                'last_recruiter_activity_at' => null,
            ];

            $application['status_label'] = $this->getStatusLabel((string) ($application['status'] ?? ''));
            $application['status_message'] = $this->getStatusMessage((string) ($application['status'] ?? ''));
            $application['timeline'] = $this->buildApplicationTimeline((string) ($application['status'] ?? ''));
        }

        return $this->respond([
            'status' => 'success',
            'data' => $applications
        ]);
    }

    public function withdraw($applicationId)
    {
        $applicationId = (int) $applicationId;
        if ($applicationId <= 0) {
            return $this->fail('Invalid Application ID');
        }

        $rawBody = $this->request->getBody();
        $inputData = json_decode($rawBody ?? '', true);
        $candidateId = (int) ($inputData['candidate_id'] ?? 0);

        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $applicationModel = new ApplicationModel();
        $application = $applicationModel
            ->where('id', $applicationId)
            ->where('candidate_id', $candidateId)
            ->first();

        if (!$application) {
            return $this->failNotFound('Application not found.');
        }

        $status = (string) ($application['status'] ?? '');
        if ($status === 'withdrawn') {
            return $this->respond([
                'status' => 'success',
                'message' => 'Application is already withdrawn.',
                'data' => [
                    'application_id' => $applicationId,
                    'status' => 'withdrawn',
                ]
            ]);
        }

        if (in_array($status, ['filtered_out', 'rejected', 'selected', 'hired'], true)) {
            return $this->fail('This application can no longer be withdrawn.', 422);
        }

        if ($status === 'interview_slot_booked' || !empty($application['booking_id'])) {
            return $this->fail('Booked interview applications cannot be withdrawn.', 422);
        }

        $applicationModel->update($applicationId, ['status' => 'withdrawn']);
        
        $stageModel = model('StageHistoryModel');
        if ($stageModel) {
            $stageModel->moveToStage($applicationId, 'Withdrawn by Candidate');
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Application withdrawn successfully.',
            'data' => [
                'application_id' => $applicationId,
                'status' => 'withdrawn',
            ]
        ]);
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'applied' => 'Applied',
            'shortlisted' => 'Shortlisted',
            'hold' => 'On Hold',
            'filtered_out' => 'Filtered Out',
            'rejected' => 'Rejected',
            'interview_slot_booked' => 'Interview Booked',
            'selected' => 'Selected',
            'withdrawn' => 'Withdrawn',
            'hired' => 'Hired',
        ];

        return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'applied' => 'Your application has been submitted and is under recruiter review.',
            'shortlisted' => 'You have been shortlisted. Book your next interview slot to continue.',
            'hold' => 'Your application is on hold for now. Recruiters may review it again later.',
            'filtered_out' => 'This application did not meet one or more mandatory screening criteria.',
            'interview_slot_booked' => 'Your interview slot is booked. Check your schedule for timing details.',
            'selected' => 'You have been selected for this role.',
            'hired' => 'You have been marked as hired for this role.',
            'rejected' => 'This application has been closed by the recruiter.',
            'withdrawn' => 'You withdrew this application.',
            default => 'Your application is being processed.',
        };
    }

    private function buildApplicationTimeline(string $status): array
    {
        $steps = [
            ['key' => 'applied', 'label' => 'Applied', 'note' => 'Application submitted successfully.'],
            ['key' => 'shortlisted', 'label' => 'Shortlisted', 'note' => 'Recruiter moved your profile forward.'],
            ['key' => 'interview_slot_booked', 'label' => 'Interview Booked', 'note' => 'Interview slot scheduled.'],
            ['key' => 'selected', 'label' => 'Selected', 'note' => 'You cleared the hiring process.'],
        ];

        $progressMap = [
            'applied' => 0,
            'hold' => 0,
            'filtered_out' => 0,
            'rejected' => 0,
            'withdrawn' => 0,
            'shortlisted' => 1,
            'interview_slot_booked' => 2,
            'selected' => 3,
            'hired' => 3,
        ];

        $currentIndex = $progressMap[$status] ?? 0;

        foreach ($steps as $index => &$step) {
            $step['is_done'] = $index < $currentIndex;
            $step['is_current'] = $index === $currentIndex && !in_array($status, ['filtered_out', 'rejected', 'withdrawn', 'hold'], true);
        }
        unset($step);

        if ($status === 'hold') {
            $steps[] = ['key' => 'hold', 'label' => 'On Hold', 'note' => 'Recruiter has paused the application.', 'is_done' => false, 'is_current' => true];
        } elseif ($status === 'filtered_out') {
            $steps[] = ['key' => 'filtered_out', 'label' => 'Filtered Out', 'note' => 'Mandatory screening criteria were not met.', 'is_done' => false, 'is_current' => true];
        } elseif ($status === 'rejected') {
            $steps[] = ['key' => 'rejected', 'label' => 'Rejected', 'note' => 'Application was not moved forward.', 'is_done' => false, 'is_current' => true];
        } elseif ($status === 'withdrawn') {
            $steps[] = ['key' => 'withdrawn', 'label' => 'Withdrawn', 'note' => 'You withdrew this application.', 'is_done' => false, 'is_current' => true];
        } elseif ($status === 'hired') {
            $steps[] = ['key' => 'hired', 'label' => 'Hired', 'note' => 'You joined the role successfully.', 'is_done' => false, 'is_current' => true];
        }

        return $steps;
    }
}
