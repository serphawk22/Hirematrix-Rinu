<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\RecruiterCandidateMessageModel;

class RecruiterApplications extends BaseController
{
    public function index()
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('candidate/dashboard'))->with('error', 'Access denied.');
        }
        
        return redirect()->to(base_url('recruiter/jobs'));
    }

   public function viewByJob($jobId)
{
    $jobModel = model('JobModel');
    $applicationModel = model('ApplicationModel');
    $currentUserId = session()->get('user_id');

    // Verify job belongs to recruiter
    $job = $jobModel->where('id', $jobId)
                   ->where('recruiter_id', $currentUserId)
                   ->first();

    if (!$job) {
        return redirect()->to('recruiter/jobs')->with('error', 'Job not found');
    }

    // ✅ Filters (INCLUDING last_active)
    $filters = [
        'skills' => trim((string) $this->request->getGet('skills')),
        'experience' => trim((string) $this->request->getGet('experience')),
        'location' => trim((string) $this->request->getGet('location')),
        'status' => trim((string) $this->request->getGet('status')),
        'score_min' => $this->request->getGet('score_min'),
        'score_max' => $this->request->getGet('score_max'),
        'ats_min' => $this->request->getGet('ats_min'),
        'ats_max' => $this->request->getGet('ats_max'),
        'sort' => trim((string) $this->request->getGet('sort')),
        'last_active' => trim((string) $this->request->getGet('last_active')), // 🔥 NEW
    ];

    // ✅ Score validation
    $scoreMin = is_numeric($filters['score_min']) ? (float) $filters['score_min'] : null;
    $scoreMax = is_numeric($filters['score_max']) ? (float) $filters['score_max'] : null;

    if ($scoreMin !== null) $scoreMin = max(0, min(10, $scoreMin));
    if ($scoreMax !== null) $scoreMax = max(0, min(10, $scoreMax));

    if ($scoreMin !== null && $scoreMax !== null && $scoreMin > $scoreMax) {
        [$scoreMin, $scoreMax] = [$scoreMax, $scoreMin];
    }

    // ✅ ATS validation
    $atsMin = is_numeric($filters['ats_min']) ? (int) $filters['ats_min'] : null;
    $atsMax = is_numeric($filters['ats_max']) ? (int) $filters['ats_max'] : null;

    if ($atsMin !== null) $atsMin = max(0, min(100, $atsMin));
    if ($atsMax !== null) $atsMax = max(0, min(100, $atsMax));

    if ($atsMin !== null && $atsMax !== null && $atsMin > $atsMax) {
        [$atsMin, $atsMax] = [$atsMax, $atsMin];
    }

    // ✅ Sort validation
    $validSort = ['applied_desc', 'ats_desc', 'ats_asc'];
    if (!in_array($filters['sort'], $validSort, true)) {
        $filters['sort'] = 'applied_desc';
    }

    // ✅ Status validation
    $validStatuses = [
        'applied',
        'ai_interview_completed',
        'pending',
        'filtered_out',
        'shortlisted',
        'interview_slot_booked','selected','rejected',
    ];
    if ($filters['status'] !== '' && !in_array($filters['status'], $validStatuses, true)) {
        $filters['status'] = '';
    }

    // ✅ Last Active validation
    $validActiveFilters = ['7','30','90'];
    if (!in_array($filters['last_active'], $validActiveFilters, true)) {
        $filters['last_active'] = '';
    }

    // ✅ Experience subquery
    $experienceSubQuery = '(SELECT user_id, 
        SUM(TIMESTAMPDIFF(MONTH, start_date, COALESCE(NULLIF(end_date, \'\'), CURDATE()))) 
        AS total_experience_months 
        FROM work_experiences GROUP BY user_id) candidate_experience';

    // ✅ Last login subquery (🔥 KEY FIX)
    $lastLoginSubQuery = '(SELECT user_id, MAX(login_at) as last_login 
                          FROM user_login_performance_logs 
                          GROUP BY user_id) last_login_table';

    // ✅ Main Query
    $builder = $applicationModel
        ->select('applications.*, users.name, users.email,
            last_login_table.last_login,
            candidate_profiles.location as candidate_location,
            candidate_profiles.resume_path as resume_path,
            0 as overall_rating,
            candidate_skills.skill_name,
            COALESCE(candidate_experience.total_experience_months, 0) as total_experience_months,
            recruiter_candidate_notes.tags as recruiter_tags,
            recruiter_candidate_notes.notes as recruiter_notes')
        ->join('users', 'users.id = applications.candidate_id', 'left')
        ->join('candidate_profiles', 'candidate_profiles.user_id = applications.candidate_id', 'left')
        ->join('candidate_skills', 'candidate_skills.candidate_id = applications.candidate_id', 'left')
        ->join(
            'recruiter_candidate_notes',
            'recruiter_candidate_notes.candidate_id = applications.candidate_id 
             AND recruiter_candidate_notes.recruiter_id = ' . (int)$currentUserId,
            'left',
            false
        )
        ->join($experienceSubQuery, 'candidate_experience.user_id = applications.candidate_id', 'left', false)
        ->join($lastLoginSubQuery, 'last_login_table.user_id = applications.candidate_id', 'left', false)
        ->where('applications.job_id', $jobId);

    // ✅ Apply filters

    if ($filters['skills'] !== '') {
        $builder->like('candidate_skills.skill_name', $filters['skills']);
    }

    if ($filters['experience'] !== '') {
        preg_match('/\d+(\.\d+)?/', $filters['experience'], $matches);
        if (!empty($matches[0])) {
            $minMonths = (int) round(((float)$matches[0]) * 12);
            $builder->where('COALESCE(candidate_experience.total_experience_months, 0) >= ' . $minMonths, null, false);
        }
    }

    if ($filters['location'] !== '') {
        $builder->where(
            'candidate_profiles.location LIKE ' . $builder->db->escape('%' . $filters['location'] . '%'),
            null,
            false
        );
    }

    if ($filters['status'] !== '') {
        $builder->where('applications.status', $filters['status']);
    }

    // 🔥 LAST ACTIVE FILTER
    if ($filters['last_active'] !== '') {
        $days = (int)$filters['last_active'];
        $builder->where('last_login_table.last_login >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)', null, false);
    }

    $applications = $builder
        ->groupBy('applications.id')
        ->orderBy('applications.applied_at', 'DESC')
        ->findAll();

    // ✅ Process results
    foreach ($applications as &$application) {

        // Experience display
        $months = (int)($application['total_experience_months'] ?? 0);

        if ($months <= 0) {
            $application['experience_display'] = '-';
        } else {
            $years = floor($months / 12);
            $remainingMonths = $months % 12;

            if ($years > 0 && $remainingMonths > 0) {
                $application['experience_display'] = $years . 'y ' . $remainingMonths . 'm';
            } elseif ($years > 0) {
                $application['experience_display'] = $years . 'y';
            } else {
                $application['experience_display'] = $remainingMonths . 'm';
            }
        }

        // ATS score
        $application['ats_score'] = $this->calculateAtsScore($application, $job);

        // Permission
        $application['can_manual_decision'] = $this->canTakeManualDecision((string)($application['status'] ?? ''));
    }
    unset($application);

    // ✅ ATS filtering
    if ($atsMin !== null || $atsMax !== null) {
        $applications = array_values(array_filter($applications, function ($app) use ($atsMin, $atsMax) {
            $score = (int)($app['ats_score'] ?? 0);
            if ($atsMin !== null && $score < $atsMin) return false;
            if ($atsMax !== null && $score > $atsMax) return false;
            return true;
        }));
    }

    // ✅ Sorting
    if ($filters['sort'] === 'ats_desc') {
        usort($applications, fn($a, $b) => $b['ats_score'] <=> $a['ats_score']);
    } elseif ($filters['sort'] === 'ats_asc') {
        usort($applications, fn($a, $b) => $a['ats_score'] <=> $b['ats_score']);
    } else {
        usort($applications, fn($a, $b) => strcmp($b['applied_at'], $a['applied_at']));
    }

    return redirect()->to(base_url('recruiter/jobs/view/' . (int) $jobId));
}

    public function shortlist($applicationId)
    {
        return $this->updateApplicationStatus($applicationId, 'shortlisted');
    }

    public function reject($applicationId)
    {
        return $this->updateApplicationStatus($applicationId, 'rejected');
    }

    public function bulkAction($jobId)
    {
        $applicationModel = model('ApplicationModel');
        $jobModel = model('JobModel');
        $stageModel = model('StageHistoryModel');
        $currentUserId = (int) session()->get('user_id');
        $isAjax = $this->request->isAJAX();

        $job = $jobModel->where('id', (int) $jobId)->where('recruiter_id', $currentUserId)->first();
        if (!$job) {
            return $this->respondBulkAction(false, 'Job not found', $isAjax, base_url('recruiter/jobs'), 404);
        }

        $applicationIds = $this->request->getPost('application_ids');
        $bulkAction = trim((string) $this->request->getPost('bulk_action'));
        $messageText = trim((string) $this->request->getPost('bulk_message'));

        if (!is_array($applicationIds) || empty($applicationIds)) {
            return $this->respondBulkAction(false, 'Please select at least one candidate.', $isAjax, null, 422);
        }

        $applicationIds = array_values(array_unique(array_map('intval', $applicationIds)));
        $applicationIds = array_filter($applicationIds, static fn ($id) => $id > 0);

        if (empty($applicationIds)) {
            return $this->respondBulkAction(false, 'Invalid selection.', $isAjax, null, 422);
        }

        if (!in_array($bulkAction, ['shortlist', 'reject', 'message'], true)) {
            return $this->respondBulkAction(false, 'Invalid bulk action.', $isAjax, null, 422);
        }

        $applications = $applicationModel
            ->select('applications.*, jobs.recruiter_id, users.name as candidate_name')
            ->join('jobs', 'jobs.id = applications.job_id')
            ->join('users', 'users.id = applications.candidate_id', 'left')
            ->where('applications.job_id', (int) $jobId)
            ->whereIn('applications.id', $applicationIds)
            ->findAll();

        if (empty($applications)) {
            return $this->respondBulkAction(false, 'No matching applications found.', $isAjax, null, 404);
        }

        if ($bulkAction === 'message') {
            if ($messageText === '') {
                return $this->respondBulkAction(false, 'Please enter a message for selected candidates.', $isAjax, null, 422);
            }
            if (mb_strlen($messageText) > 1000) {
                return $this->respondBulkAction(false, 'Message is too long. Max 1000 characters.', $isAjax, null, 422);
            }

            $messageModel = new RecruiterCandidateMessageModel();
            $notificationModel = new NotificationModel();
            $recruiterName = (string) (session()->get('user_name') ?? 'Recruiter');
            $sent = 0;

            foreach ($applications as $application) {
                if ((int) $application['recruiter_id'] !== $currentUserId) {
                    continue;
                }

                $messageModel->insert([
                    'candidate_id' => (int) $application['candidate_id'],
                    'recruiter_id' => $currentUserId,
                    'application_id' => (int) $application['id'],
                    'job_id' => (int) $application['job_id'],
                    'sender_id' => $currentUserId,
                    'sender_role' => 'recruiter',
                    'message' => $messageText,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $notificationModel->insert([
                    'user_id' => (int) $application['candidate_id'],
                    'application_id' => (int) $application['id'],
                    'type' => 'recruiter_message',
                    'title' => 'Message from Recruiter',
                    'message' => "{$recruiterName} sent you a message. Open conversation to read it.",
                    'action_link' => base_url('candidate/messages/' . $currentUserId . '?application_id=' . (int) $application['id']),
                    'is_read' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $sent++;
            }

            return $this->respondBulkAction(true, "Message sent to {$sent} selected candidate(s).", $isAjax);
        }

        $targetStatus = $bulkAction === 'shortlist' ? 'shortlisted' : 'rejected';
        $updated = 0;
        $skipped = 0;
        $notificationModel = new NotificationModel();

        foreach ($applications as $application) {
            if ((int) $application['recruiter_id'] !== $currentUserId) {
                $skipped++;
                continue;
            }

            if (($application['status'] ?? '') === 'interview_slot_booked') {
                $skipped++;
                continue;
            }

            if (!$this->canTakeManualDecision((string) ($application['status'] ?? ''))) {
                $skipped++;
                continue;
            }

            $applicationModel->update((int) $application['id'], ['status' => $targetStatus]);
            $stageModel->moveToStage(
                (int) $application['id'],
                $targetStatus === 'shortlisted' ? 'Shortlisted (Recruiter Override)' : 'Rejected (Recruiter Override)'
            );
            $this->notifyApplicationStatusChange(
                $notificationModel,
                (int) $application['candidate_id'],
                (int) $application['id'],
                $targetStatus
            );
            if ($targetStatus === 'rejected') {
                (new \App\Libraries\ApplicationRejectionMailer())->sendIfEnabled((int) $application['id'], $currentUserId);
            }
            $updated++;
        }

        if ($updated === 0) {
            return $this->respondBulkAction(false, 'No selected applications were eligible for this action.', $isAjax, null, 422);
        }

        $statusLabel = $this->formatApplicationStatusLabel($targetStatus);
        $suffix = $skipped > 0 ? " ({$skipped} skipped)" : '';
        return $this->respondBulkAction(true, "Bulk {$statusLabel} applied to {$updated} candidate(s){$suffix}.", $isAjax);
    }

    private function respondBulkAction(bool $success, string $message, bool $isAjax, ?string $redirectUrl = null, int $statusCode = 200)
    {
        if ($isAjax) {
            return $this->response->setStatusCode($statusCode)->setJSON([
                'success' => $success,
                'message' => $message,
                'csrf_token_name' => csrf_token(),
                'csrf_hash' => csrf_hash(),
            ]);
        }

        if ($redirectUrl !== null) {
            return redirect()->to($redirectUrl)->with($success ? 'success' : 'error', $message);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    private function updateApplicationStatus(int $applicationId, string $status)
    {
        $applicationModel = model('ApplicationModel');
        $notificationModel = model('NotificationModel');
        $currentUserId = session()->get('user_id');
        $isAjax = $this->request->isAJAX();

        $application = $applicationModel
            ->select('applications.*, jobs.recruiter_id')
            ->join('jobs', 'jobs.id = applications.job_id')
            ->where('applications.id', $applicationId)
            ->first();

        if (!$application || (int) $application['recruiter_id'] !== (int) $currentUserId) {
            if ($isAjax) {
                return $this->respondApplicationStatus(false, 'Application not found', null, 404);
            }

            return redirect()->back()->with('error', 'Application not found');
        }

        if ($application['status'] === 'interview_slot_booked') {
            if ($isAjax) {
                return $this->respondApplicationStatus(false, 'Booked interview applications cannot be changed here', null, 422);
            }

            return redirect()->back()->with('error', 'Booked interview applications cannot be changed here');
        }

        if (!$this->canTakeManualDecision((string) ($application['status'] ?? ''))) {
            $message = 'This application is not eligible for recruiter action right now.';
            if ($isAjax) {
                return $this->respondApplicationStatus(false, $message, null, 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $applicationModel->update($applicationId, ['status' => $status]);

        $stageModel = model('StageHistoryModel');
        $stageModel->moveToStage($applicationId, $status === 'shortlisted' ? 'Shortlisted' : 'Rejected');

        $this->notifyApplicationStatusChange(
            $notificationModel,
            (int) $application['candidate_id'],
            $applicationId,
            $status
        );
        if ($status === 'rejected') {
            (new \App\Libraries\ApplicationRejectionMailer())->sendIfEnabled($applicationId, (int) $currentUserId);
        }

        $statusLabel = $this->formatApplicationStatusLabel($status);
        if ($isAjax) {
            return $this->respondApplicationStatus(true, 'Application status updated to ' . $statusLabel, [
                'application_id' => $applicationId,
                'status' => $status,
                'status_label' => $statusLabel,
                'status_badge' => $this->getApplicationStatusBadgeClass($status),
            ]);
        }

        return redirect()->back()->with('success', 'Application status updated to ' . $statusLabel);
    }

    private function respondApplicationStatus(bool $success, string $message, ?array $data = null, int $statusCode = 200)
    {
        $payload = [
            'success' => $success,
            'message' => $message,
            'csrf_token_name' => csrf_token(),
            'csrf_hash' => csrf_hash(),
        ];

        if ($data !== null) {
            $payload = array_merge($payload, $data);
        }

        return $this->response->setStatusCode($statusCode)->setJSON($payload);
    }

    private function getApplicationStatusBadgeClass(string $status): string
    {
        $statusColors = [
            'pending' => 'warning',
            'applied' => 'warning',
            'ai_interview_completed' => 'info',
            'shortlisted' => 'success',
            'hold' => 'secondary',
            'filtered_out' => 'dark',
            'interview_slot_booked' => 'success',
            'selected' => 'success',
            'rejected' => 'danger',
        ];

        return $statusColors[$status] ?? 'secondary';
    }

    private function formatApplicationStatusLabel(string $status): string
    {
        $labels = [
            'ai_interview_completed' => 'AI Interview Completed',
            'interview_slot_booked' => 'Interview Booked',
            'filtered_out' => 'Filtered Out',
        ];

        return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    private function canTakeManualDecision(string $applicationStatus): bool
    {
        if (in_array($applicationStatus, ['interview_slot_booked', 'selected'], true)) {
            return false;
        }

        return in_array($applicationStatus, ['applied', 'ai_interview_completed', 'shortlisted', 'rejected', 'pending', 'hold'], true);
    }

    private function notifyApplicationStatusChange(
        NotificationModel $notificationModel,
        int $candidateId,
        int $applicationId,
        string $status
    ): void {
        $label = $this->formatApplicationStatusLabel($status);
        $type = in_array($status, ['selected', 'hired'], true) ? 'offer_sent' : 'application_status_changed';
        $message = match ($status) {
            'shortlisted' => 'Good news! Your application has been shortlisted.',
            'selected' => 'Congratulations! Your application has moved to the offer stage.',
            'hired' => 'Congratulations! Your application has been marked as hired.',
            'rejected' => 'Your application has been updated to Rejected.',
            'filtered_out' => 'Your application did not meet one or more mandatory screening criteria.',
            'hold' => 'Your application has been placed on hold for future review.',
            default => 'Your application status was updated to ' . $label . '.',
        };

        $notificationModel->createNotification(
            $candidateId,
            $applicationId,
            $type,
            $message,
            base_url('candidate/applications'),
            true
        );
    }

    private function calculateAtsScore(array $application, array $job): int
    {
        $candidateSkills = $this->normalizeSkillTokens((string) ($application['skill_name'] ?? ''));
        $requiredSkills = $this->normalizeSkillTokens((string) ($job['required_skills'] ?? ''));

        // Skill fit (60 points)
        if (empty($requiredSkills)) {
            $skillScore = 60;
        } else {
            $matched = 0;
            foreach ($requiredSkills as $requiredSkill) {
                if (in_array($requiredSkill, $candidateSkills, true)) {
                    $matched++;
                }
            }
            $skillScore = (int) round(($matched / max(1, count($requiredSkills))) * 60);
        }

        // Experience fit (20 points)
        $requiredMonths = $this->extractRequiredExperienceMonths((string) ($job['experience_level'] ?? ''));
        $candidateMonths = max(0, (int) ($application['total_experience_months'] ?? 0));
        if ($requiredMonths === null || $requiredMonths <= 0) {
            $experienceScore = 20;
        } else {
            $experienceScore = (int) round(min(1, $candidateMonths / $requiredMonths) * 20);
        }

        // Profile readiness (20 points): resume uploaded
        $profileScore = !empty($application['resume_path']) ? 5 : 0;

        return max(0, min(100, $skillScore + $experienceScore + $profileScore));
    }

    private function normalizeSkillTokens(string $skills): array
    {
        $parts = preg_split('/[,|\\/]+/', strtolower($skills)) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $value = trim($part);
            if ($value !== '') {
                $tokens[] = $value;
            }
        }
        return array_values(array_unique($tokens));
    }

    private function extractRequiredExperienceMonths(string $experienceLevel): ?int
    {
        $value = strtolower(trim($experienceLevel));
        if ($value === '') {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)/', $value, $matches)) {
            return (int) round(((float) $matches[1]) * 12);
        }

        if (preg_match('/(\d+(?:\.\d+)?)/', $value, $matches)) {
            return (int) round(((float) $matches[1]) * 12);
        }

        return null;
    }

}
    
