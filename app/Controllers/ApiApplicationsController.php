<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ApplicationModel;
use App\Models\JobModel;
use App\Models\RecruiterCandidateActionModel;
use App\Models\CandidateResumeVersionModel;
use App\Libraries\AiInterviewPrepCoach;

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
            $application['interview_prep'] = $this->buildInterviewPrepCoach($application);
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

    private function buildInterviewPrepCoach(array $application): array
    {
        if (in_array((string) ($application['status'] ?? ''), ['filtered_out', 'rejected', 'withdrawn', 'selected', 'hired'], true)) {
            return [];
        }

        $requiredSkills = $this->tokenizeCsv((string) ($application['required_skills'] ?? ''));
        $focusSkills = array_slice($requiredSkills, 0, 5);
        $jobTitle = trim((string) ($application['job_title'] ?? 'this role'));
        $targetRole = trim((string) ($application['resume_version_target_role'] ?? ''));
        $resumeTitle = trim((string) ($application['resume_version_title'] ?? ''));
        $policy = strtoupper((string) ($application['ai_interview_policy'] ?? JobModel::AI_POLICY_REQUIRED_HARD));
        $status = (string) ($application['status'] ?? '');

        $coachTitle = 'Pre-interview Preparation Coach';
        if ($status === 'shortlisted' || $status === 'interview_slot_booked') {
            $coachTitle = 'HR Interview Preparation Coach';
        } elseif ($policy !== JobModel::AI_POLICY_OFF) {
            $coachTitle = 'AI Interview Preparation Coach';
        }

        $talkingPoints = [];
        if ($targetRole !== '') {
            $talkingPoints[] = 'Explain why your background fits the target role "' . $targetRole . '".';
        }
        if ($resumeTitle !== '') {
            $talkingPoints[] = 'Use examples from your saved resume version "' . $resumeTitle . '".';
        }
        if (!empty($focusSkills)) {
            $talkingPoints[] = 'Prepare project stories around ' . implode(', ', array_slice($focusSkills, 0, 3)) . '.';
        }
        if (!empty($application['experience_level'])) {
            $talkingPoints[] = 'Be ready to justify your experience level: ' . trim((string) $application['experience_level']) . '.';
        }
        if (empty($talkingPoints)) {
            $talkingPoints[] = 'Prepare two role-relevant examples with measurable outcomes.';
        }

        $checklist = [
            'Review the job description and map your strongest experience to the role.',
            'Prepare concise STAR-format examples for one challenge, one achievement, and one collaboration story.',
            'Keep your resume, project examples, and skill claims consistent.',
        ];

        if (!empty($focusSkills)) {
            $checklist[] = 'Revise the top skills recruiters are likely to test: ' . implode(', ', array_slice($focusSkills, 0, 4)) . '.';
        }

        if ($policy !== JobModel::AI_POLICY_OFF && $status === 'applied') {
            $checklist[] = 'Practice answering clearly on camera with short, structured responses for the AI round.';
        }

        $likelyQuestions = [];
        foreach (array_slice($focusSkills, 0, 3) as $skill) {
            $likelyQuestions[] = 'Describe a real example where you used ' . $skill . '.';
        }
        $likelyQuestions[] = 'Why are you interested in this ' . $jobTitle . ' role?';
        $likelyQuestions[] = 'What problem did you solve recently that best shows your fit for this job?';

        $fallback = [
            'title' => $coachTitle,
            'focus_skills' => $focusSkills,
            'talking_points' => $talkingPoints,
            'checklist' => $checklist,
            'likely_questions' => array_slice($likelyQuestions, 0, 5),
            'source' => 'fallback',
        ];

        return (new AiInterviewPrepCoach())->generate($application, $fallback);
    }

    private function tokenizeCsv(string $value): array
    {
        $parts = preg_split('/[,|\\/]+/', strtolower($value)) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $tokens[] = $part;
            }
        }

        return array_values(array_unique($tokens));
    }

    public function getAvailableSlots($applicationId)
    {
        $applicationId = (int) $applicationId;
        if ($applicationId <= 0) {
            return $this->fail('Invalid Application ID');
        }

        $applicationModel = model('ApplicationModel');
        $jobModel = model('JobModel');
        $slotModel = model('InterviewSlotModel');
        $bookingModel = model('InterviewBookingModel');

        $application = $applicationModel->find($applicationId);
        if (!$application) {
            return $this->failNotFound('Application not found');
        }

        $job = $jobModel->find($application['job_id']);
        if (!$job) {
            return $this->failNotFound('Job not found');
        }
        $application['job_title'] = $job['title'] ?? null;
        $aiPolicy = JobModel::normalizeAiPolicy($job['ai_interview_policy'] ?? JobModel::AI_POLICY_REQUIRED_HARD);

        // check if eligible
        if (!$this->canBookSlotForStatus($application['status'], $aiPolicy)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'You are not eligible to book a slot yet'
            ]);
        }

        // Check if already booked
        $existingBooking = $bookingModel->getByApplicationId($applicationId);
        if ($existingBooking) {
            return $this->respond([
                'status' => 'info',
                'message' => 'You have already booked an interview slot',
                'booking' => $existingBooking
            ]);
        }

        $availableSlots = $slotModel->getAvailableSlots((int) $application['job_id']);

        return $this->respond([
            'status' => 'success',
            'application' => $application,
            'available_slots' => $availableSlots
        ]);
    }

    private function canBookSlotForStatus(string $status, string $aiPolicy): bool
    {
        if ($status === 'shortlisted') {
            return true;
        }

        if ($aiPolicy === JobModel::AI_POLICY_OPTIONAL) {
            return $status === 'applied';
        }

        if ($aiPolicy === JobModel::AI_POLICY_OFF) {
            return $status === 'applied';
        }

        return false;
    }

    public function processBooking()
    {
        $rawBody = $this->request->getBody();
        $inputData = json_decode($rawBody ?? '', true);

        $applicationId = (int) ($inputData['application_id'] ?? 0);
        $slotId = (int) ($inputData['slot_id'] ?? 0);
        $candidateId = (int) ($inputData['candidate_id'] ?? 0);

        if ($applicationId <= 0 || $slotId <= 0 || $candidateId <= 0) {
            return $this->fail('Invalid arguments');
        }

        $applicationModel = model('ApplicationModel');
        $jobModel = model('JobModel');
        $slotModel = model('InterviewSlotModel');
        $bookingModel = model('InterviewBookingModel');
        $notificationModel = model('NotificationModel');
        $userModel = model('UserModel');

        $candidate = $userModel->find($candidateId);
        $candidateName = $candidate ? (string) ($candidate['name'] ?? 'A candidate') : 'A candidate';

        // Verify application
        $application = $applicationModel->find($applicationId);
        if (!$application || $application['candidate_id'] != $candidateId) {
            return $this->fail('Invalid application or ownership');
        }

        $job = $jobModel->find($application['job_id']);
        if (!$job) {
            return $this->failNotFound('Job not found');
        }

        $aiPolicy = JobModel::normalizeAiPolicy($job['ai_interview_policy'] ?? JobModel::AI_POLICY_REQUIRED_HARD);
        if (!$this->canBookSlotForStatus($application['status'], $aiPolicy)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'You are not eligible to book a slot yet'
            ]);
        }

        // Check if slot is available
        if (!$slotModel->isSlotAvailable($slotId)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Selected slot is no longer available'
            ]);
        }

        $slot = $slotModel->find($slotId);

        // Check if already booked
        if ($bookingModel->getByApplicationId($applicationId)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Already booked'
            ]);
        }

        // Create booking
        $db = \Config\Database::connect();
        $db->transStart();

        $bookingId = $bookingModel->insert([
            'application_id' => $applicationId,
            'user_id' => $candidateId,
            'job_id' => $application['job_id'],
            'slot_id' => $slotId,
            'slot_datetime' => $slot['slot_datetime'],
            'booking_status' => 'booked',
            'reschedule_count' => 0,
            'max_reschedules' => 2, // Configurable
            'can_reschedule' => 1,
            'booked_at' => date('Y-m-d H:i:s')
        ]);

        // Increment slot booked count
        $slotModel->incrementBookedCount($slotId);

        // Update application status and booking_id
        $applicationModel->update($applicationId, [
            'status' => 'interview_slot_booked',
            'interview_slot' => $slot['slot_datetime'],
            'booking_id' => $bookingId
        ]);

        $stageModel = model('StageHistoryModel');
        $stageModel->moveToStage($applicationId, 'Interview Slot Booked');

        $slotLabel = date('M d, Y h:i A', strtotime($slot['slot_datetime']));

        // Notify candidate
        $notificationModel->createNotification(
            $candidateId,
            (int) $applicationId,
            'interview_booked',
            "Your interview has been booked for {$slotLabel}.",
            base_url('candidate/my-bookings'),
            true
        );

        // Notify recruiter
        if (!empty($job['recruiter_id'])) {
            $notificationModel->createNotification(
                (int) $job['recruiter_id'],
                (int) $applicationId,
                'interview_booked',
                "{$candidateName} booked an interview for {$job['title']} on {$slotLabel}.",
                base_url('recruiter/slots/bookings'),
                true
            );
        }

        $db->transComplete();

        if ($db->transStatus()) {
            // Sync to Google Calendar
            try {
                $bookingModel->syncToCalendar($bookingId);
            } catch (\Exception $e) {}

            // Send confirmation reminder
            try {
                $reminderService = new \App\Libraries\ReminderService();
                $reminderService->sendBookingConfirmation($bookingId);
            } catch (\Exception $e) {}

            return $this->respond([
                'status' => 'success',
                'message' => 'Interview slot booked successfully!'
            ]);
        } else {
            return $this->respond([
                'status' => 'error',
                'message' => 'Failed to book slot. Please try again.'
            ]);
        }
    }

    public function getMyBookings($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $bookingModel = model('InterviewBookingModel');
        $bookings = $bookingModel->getUserBookings($candidateId);

        return $this->respond([
            'status' => 'success',
            'bookings' => $bookings
        ]);
    }

    public function getRescheduleInfo($applicationId)
    {
        $applicationId = (int) $applicationId;
        if ($applicationId <= 0) {
            return $this->fail('Invalid Application ID');
        }

        $applicationModel = model('ApplicationModel');
        $jobModel = model('JobModel');
        $slotModel = model('InterviewSlotModel');
        $bookingModel = model('InterviewBookingModel');
        $rescheduleHistoryModel = model('RescheduleHistoryModel');

        $application = $applicationModel->find($applicationId);
        if (!$application) {
            return $this->failNotFound('Application not found');
        }

        $job = $jobModel->find($application['job_id']);
        if (!$job) {
            return $this->failNotFound('Job not found');
        }
        $application['job_title'] = $job['title'] ?? null;

        $booking = $bookingModel->getByApplicationId($applicationId);
        if (!$booking) {
            return $this->failNotFound('No booking found');
        }

        $canReschedule = $bookingModel->canReschedule($booking['id']);
        $availableSlots = $slotModel->getAvailableSlots((int) $application['job_id']);
        $history = $rescheduleHistoryModel->getBookingHistory($booking['id']);

        return $this->respond([
            'status' => 'success',
            'application' => $application,
            'booking' => $booking,
            'available_slots' => $availableSlots,
            'can_reschedule_info' => $canReschedule,
            'history' => $history
        ]);
    }

    public function processReschedule()
    {
        $rawBody = $this->request->getBody();
        $inputData = json_decode($rawBody ?? '', true);

        $applicationId = (int) ($inputData['application_id'] ?? 0);
        $newSlotId = (int) ($inputData['slot_id'] ?? 0);
        $candidateId = (int) ($inputData['candidate_id'] ?? 0);
        $reason = trim((string) ($inputData['reason'] ?? ''));

        if ($applicationId <= 0 || $newSlotId <= 0 || $candidateId <= 0) {
            return $this->fail('Invalid arguments');
        }

        $applicationModel = model('ApplicationModel');
        $bookingModel = model('InterviewBookingModel');
        $notificationModel = model('NotificationModel');
        $jobModel = model('JobModel');
        $userModel = model('UserModel');

        $candidate = $userModel->find($candidateId);
        $candidateName = $candidate ? (string) ($candidate['name'] ?? 'A candidate') : 'A candidate';

        $application = $applicationModel->find($applicationId);
        if (!$application || $application['candidate_id'] != $candidateId) {
            return $this->fail('Invalid application or ownership');
        }

        $booking = $bookingModel->getByApplicationId($applicationId);
        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        $success = $bookingModel->rescheduleBooking($booking['id'], $newSlotId, $reason);

        if ($success) {
            $updatedBooking = $bookingModel->find($booking['id']);
            $applicationModel->update($applicationId, [
                'interview_slot' => $updatedBooking['slot_datetime']
            ]);

            $slotLabel = date('M d, Y h:i A', strtotime($updatedBooking['slot_datetime']));

            $notificationModel->createNotification(
                $candidateId,
                (int) $applicationId,
                'interview_rescheduled',
                "Your interview has been rescheduled to {$slotLabel}.",
                base_url('candidate/my-bookings'),
                true
            );

            $job = $jobModel->find((int) ($application['job_id'] ?? 0));
            if ($job && !empty($job['recruiter_id'])) {
                $notificationModel->createNotification(
                    (int) $job['recruiter_id'],
                    (int) $applicationId,
                    'interview_rescheduled',
                    "{$candidateName} rescheduled the interview for {$job['title']} to {$slotLabel}.",
                    base_url('recruiter/slots/bookings'),
                    true
                );
            }

            return $this->respond([
                'status' => 'success',
                'message' => 'Interview rescheduled successfully!'
            ]);
        } else {
            return $this->respond([
                'status' => 'error',
                'message' => 'Failed to reschedule. Please check slot availability and limits.'
            ]);
        }
    }
}
