<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class SlotManagementController extends BaseController
{
    private $recruiterJobIds = null;

    /**
     * Get recruiter's job IDs
     */
    private function getRecruiterJobIds()
    {
        if ($this->recruiterJobIds !== null) {
            return $this->recruiterJobIds;
        }

        $userRole = session()->get('role');
        $currentUserId = session()->get('user_id');

        if ($userRole !== 'recruiter') {
            return $this->recruiterJobIds = null; // Admin - no filtering
        }

        $jobModel = model('JobModel');
        $jobs = $jobModel->where('recruiter_id', $currentUserId)->findAll();

        return $this->recruiterJobIds = array_column($jobs, 'id');
    }

    /**
     * Check if recruiter has access to job
     */
    private function hasAccessToJob($jobId)
    {
        $jobIds = $this->getRecruiterJobIds();

        // Admin has access to all
        if ($jobIds === null) {
            return true;
        }

        return in_array($jobId, $jobIds, true);
    }


    /**
     * View all interview slots
     */
    public function index()
    {
        $slotModel = model('InterviewSlotModel');
        $jobModel = model('JobModel');
        // Get recruiter's job IDs
        $jobIds = $this->getRecruiterJobIds();

        // If recruiter has no jobs, show empty state
        if (is_array($jobIds) && empty($jobIds)) {
            return view('recruiter/slots/index', [
                'slots' => [],
                'pager' => $slotModel->pager,
                'jobs' => [],
                'stats' => ['total_slots' => 0, 'available_slots' => 0, 'fully_booked' => 0, 'total_bookings' => 0],
                'filters' => [],
                'noJobs' => true
            ]);
        }

        // Get filter parameters
        $jobId = $this->request->getGet('job_id');
        $date = $this->request->getGet('date');
        $status = $this->request->getGet('status');

        $builder = $slotModel
            ->select('interview_slots.*, jobs.title as job_title, users.name as created_by_name')
            ->join('jobs', 'jobs.id = interview_slots.job_id', 'left')
            ->join('users', 'users.id = interview_slots.created_by', 'left')
            ->orderBy('interview_slots.slot_datetime', 'ASC');

        // Filter by recruiter's jobs
        if ($jobIds !== null) {
            $builder->whereIn('interview_slots.job_id', $jobIds);
        }

        // Apply filters
        if ($jobId) {
            if (!$this->hasAccessToJob($jobId)) {
                return redirect()->back()->with('error', 'Unauthorized access to this job');
            }
            $builder->where('interview_slots.job_id', $jobId);
        }


        if ($date) {
            $builder->where('interview_slots.slot_date', $date);
        }

        if ($status === 'available') {
            $builder->where('interview_slots.is_available', 1)
                ->where('interview_slots.booked_count < interview_slots.capacity');
        } elseif ($status === 'full') {
            $builder->where('interview_slots.is_available', 0);
        } elseif ($status === 'past') {
            $builder->where('interview_slots.slot_datetime <', date('Y-m-d H:i:s'));
        }

        $slots = $builder->paginate(20);
        $pager = $slotModel->pager;

        // Get jobs for filter (only recruiter's jobs)
        if ($jobIds !== null) {
            $jobs = $jobModel->whereIn('id', $jobIds)->findAll();
        } else {
            $jobs = $jobModel->findAll();
        }

        // Statistics (filtered by recruiter's jobs)
        $bookingModel = model('InterviewBookingModel');

        if ($jobIds !== null) {

            // Statistics
            $stats = [
                'total_slots' => $slotModel->whereIn('job_id', $jobIds)->countAllResults(),
                'available_slots' => $slotModel->whereIn('job_id', $jobIds)
                    ->where('is_available', 1)
                    ->where('slot_datetime >', date('Y-m-d H:i:s'))
                    ->countAllResults(),
                'fully_booked' => $slotModel->whereIn('job_id', $jobIds)->where('is_available', 0)->countAllResults(),
                'total_bookings' => $bookingModel->whereIn('job_id', $jobIds)->countAllResults()

            ];
        } else {
            $stats = [
                'total_slots' => $slotModel->countAll(),
                'available_slots' => $slotModel->where('is_available', 1)
                    ->where('slot_datetime >', date('Y-m-d H:i:s'))
                    ->countAllResults(),
                'fully_booked' => $slotModel->where('is_available', 0)->countAllResults(),
                'total_bookings' => $bookingModel->countAll()
            ];
        }

        return view('recruiter/slots/index', [
            'slots' => $slots,
            'pager' => $pager,
            'jobs' => $jobs,
            'stats' => $stats,
            'filters' => [
                'job_id' => $jobId,
                'date' => $date,
                'status' => $status
            ]
        ]);
    }

    /**
     * Create slots form
     */
    public function create()
    {
        $jobModel = model('JobModel');
        $jobIds = $this->getRecruiterJobIds();
        
        // Debug: Check what's happening
        log_message('debug', 'User Role: ' . session()->get('role'));
        log_message('debug', 'User ID: ' . session()->get('user_id'));
        log_message('debug', 'Job IDs: ' . json_encode($jobIds));

        // Get only recruiter's jobs (guard empty arrays to avoid SQL IN ()).
        if (is_array($jobIds) && empty($jobIds)) {
            $jobs = [];
        } elseif ($jobIds !== null) {
            $jobs = $jobModel->whereIn('id', $jobIds)->findAll();
        } else {
            $jobs = $jobModel->findAll();
        }
        
        log_message('debug', 'Jobs count: ' . count($jobs));

        return view('recruiter/slots/create', [
            'jobs' => $jobs
        ]);
    }

    /**
     * Save new slots
     */
    public function store()
    {
        $slotModel = model('InterviewSlotModel');

        $jobId = $this->request->getPost('job_id');
        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');
        $times = $this->request->getPost('times');
        $capacity = $this->request->getPost('capacity');
        $excludeWeekends = $this->request->getPost('exclude_weekends');

        // Validation
        if (empty($jobId) || empty($startDate) || empty($times)) {
            return redirect()->back()->with('error', 'Please fill all required fields');
        }

        // Check if recruiter has access to this job
        if (!$this->hasAccessToJob($jobId)) {
            return redirect()->back()->with('error', 'Unauthorized: You cannot create slots for this job');
        }


        // Generate date range
        $dates = [];
        $current = strtotime($startDate);
        $end = $endDate ? strtotime($endDate) : $current;

        while ($current <= $end) {
            $dayOfWeek = date('N', $current);

            // Skip weekends if option is checked
            if ($excludeWeekends && ($dayOfWeek == 6 || $dayOfWeek == 7)) {
                $current = strtotime('+1 day', $current);
                continue;
            }

            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        // Create slots
        $totalCreated = 0;
        foreach ($dates as $date) {
            $created = $slotModel->createBulkSlots(
                $jobId,
                $date,
                $times,
                session()->get('user_id'),
                $capacity
            );
            $totalCreated += $created;
        }

        return redirect()->to('/recruiter/slots')->with('success', "Successfully created {$totalCreated} interview slots");
    }

    /**
     * Edit slot
     */
    public function edit($id)
    {
        $slotModel = model('InterviewSlotModel');
        $jobModel = model('JobModel');

        $slot = $slotModel->find($id);

        if (!$slot) {
            return redirect()->to('/recruiter/slots')->with('error', 'Slot not found');
        }

        // Check if recruiter has access to this slot's job
        if (!$this->hasAccessToJob($slot['job_id'])) {
            return redirect()->to('/recruiter/slots')->with('error', 'Unauthorized access');
        }

        $jobIds = $this->getRecruiterJobIds();
        if ($jobIds !== null) {
            $jobs = $jobModel->whereIn('id', $jobIds)->findAll();
        } else {
            $jobs = $jobModel->findAll();
        }


        return view('recruiter/slots/edit', [
            'slot' => $slot,
            'jobs' => $jobs
        ]);
    }

    /**
     * Update slot
     */
    public function update($id)
    {
        $slotModel = model('InterviewSlotModel');

        $slot = $slotModel->find($id);

        if (!$slot) {
            return redirect()->back()->with('error', 'Slot not found');
        }

        // Check if recruiter has access to this slot's job
        if (!$this->hasAccessToJob($slot['job_id'])) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }


        // Don't allow editing if already booked
        if ($slot['booked_count'] > 0) {
            return redirect()->back()->with('error', 'Cannot edit slot with existing bookings');
        }

        $date = $this->request->getPost('slot_date');
        $time = $this->request->getPost('slot_time');
        $capacity = $this->request->getPost('capacity');

        $slotModel->update($id, [
            'slot_date' => $date,
            'slot_time' => $time,
            'slot_datetime' => $date . ' ' . $time,
            'capacity' => $capacity
        ]);

        return redirect()->to('/recruiter/slots')->with('success', 'Slot updated successfully');
    }

    /**
     * Delete slot
     */
    public function delete($id)
    {
        $slotModel = model('InterviewSlotModel');

        $slot = $slotModel->find($id);

        if (!$slot) {
            return redirect()->back()->with('error', 'Slot not found');
        }

        // Check if recruiter has access to this slot's job
        if (!$this->hasAccessToJob($slot['job_id'])) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        // Don't allow deletion if already booked
        if ($slot['booked_count'] > 0) {
            return redirect()->back()->with('error', 'Cannot delete slot with existing bookings');
        }

        $slotModel->delete($id);

        return redirect()->to('/recruiter/slots')->with('success', 'Slot deleted successfully');
    }

    /**
     * View all bookings
     */
    public function bookings()
    {
        $bookingModel = model('InterviewBookingModel');
        $jobModel = model('JobModel');
        // Get recruiter's job IDs
        $jobIds = $this->getRecruiterJobIds();

        // If recruiter has no jobs, show empty state and avoid whereIn(..., []) SQL.
        if (is_array($jobIds) && empty($jobIds)) {
            return view('recruiter/slots/bookings', [
                'bookings' => [],
                'pager' => $bookingModel->pager,
                'jobs' => [],
                'stats' => [
                    'total_bookings' => 0,
                    'upcoming' => 0,
                    'completed' => 0,
                    'rescheduled' => 0,
                ],
                'filters' => [
                    'status' => $this->request->getGet('status'),
                    'job_id' => $this->request->getGet('job_id'),
                ],
                'noJobs' => true,
            ]);
        }

        // Get filters
        $status = $this->request->getGet('status');
        $jobId = $this->request->getGet('job_id');

        $builder = $bookingModel
            ->select('interview_bookings.*, users.name as candidate_name, users.email, jobs.title as job_title, interview_slots.slot_date, interview_slots.slot_time, interview_booking_reviews.id as review_id, interview_booking_reviews.attendance_status as review_attendance_status, interview_booking_reviews.decision as review_decision, interview_booking_reviews.notes as review_notes, interview_booking_reviews.reviewed_at as review_reviewed_at')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->join('interview_slots', 'interview_slots.id = interview_bookings.slot_id', 'left')
            ->join('interview_booking_reviews', 'interview_booking_reviews.booking_id = interview_bookings.id', 'left')
            ->orderBy('interview_bookings.slot_datetime', 'ASC');
        // Filter by recruiter's jobs
        if ($jobIds !== null) {
            $builder->whereIn('interview_bookings.job_id', $jobIds);
        }

        if ($status) {
            $builder->where('interview_bookings.booking_status', $status);
        }

        if ($jobId) {
            if (!$this->hasAccessToJob($jobId)) {
                return redirect()->back()->with('error', 'Unauthorized access');
            }
            $builder->where('interview_bookings.job_id', $jobId);
        }

        $bookings = $builder->paginate(20);
        $pager = $bookingModel->pager;

        // Get jobs for filter
        if ($jobIds !== null) {
            $jobs = $jobModel->whereIn('id', $jobIds)->findAll();
        } else {
            $jobs = $jobModel->findAll();
        }

        // Statistics
        $stats = $this->getBookingStats($jobIds);

        return view('recruiter/slots/bookings', [
            'bookings' => $bookings,
            'pager' => $pager,
            'jobs' => $jobs,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'job_id' => $jobId
            ]
        ]);
    }

    /**
     * Open booking review form.
     */
    public function review($bookingId)
    {
        $bookingModel = model('InterviewBookingModel');
        $bookingReviewModel = model('InterviewBookingReviewModel');
        $currentUserId = (int) session()->get('user_id');

        $booking = $bookingModel
            ->select('interview_bookings.*, users.name as candidate_name, users.email, jobs.title as job_title, jobs.recruiter_id, interview_slots.slot_date, interview_slots.slot_time')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->join('interview_slots', 'interview_slots.id = interview_bookings.slot_id', 'left')
            ->where('interview_bookings.id', (int) $bookingId)
            ->first();

        if (!$booking) {
            return redirect()->back()->with('error', 'Booking not found.');
        }

        if ((int) ($booking['recruiter_id'] ?? 0) !== $currentUserId && session()->get('role') === 'recruiter') {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $review = $bookingReviewModel->getByBookingId((int) $bookingId);

        return view('recruiter/slots/review', [
            'booking' => $booking,
            'review' => $review,
        ]);
    }

    /**
     * Save booking review, attendance and recruiter decision.
     */
    public function saveReview($bookingId)
    {
        $bookingModel = model('InterviewBookingModel');
        $bookingReviewModel = model('InterviewBookingReviewModel');
        $applicationModel = model('ApplicationModel');
        $stageModel = model('StageHistoryModel');
        $currentUserId = (int) session()->get('user_id');

        $booking = $bookingModel
            ->select('interview_bookings.*, jobs.recruiter_id, jobs.title as job_title, users.name as candidate_name, users.email')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->where('interview_bookings.id', (int) $bookingId)
            ->first();

        if (!$booking) {
            return redirect()->back()->with('error', 'Booking not found.');
        }

        if ((int) ($booking['recruiter_id'] ?? 0) !== $currentUserId && session()->get('role') === 'recruiter') {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $attendanceStatus = (string) $this->request->getPost('attendance_status');
        $decision = trim((string) $this->request->getPost('decision'));
        $notes = trim((string) $this->request->getPost('notes'));
        $strengths = trim((string) $this->request->getPost('strengths'));
        $concerns = trim((string) $this->request->getPost('concerns'));

        $allowedAttendance = ['attended', 'late', 'no_show'];
        $allowedDecision = ['shortlisted', 'hold', 'selected', 'rejected'];

        if (!in_array($attendanceStatus, $allowedAttendance, true)) {
            return redirect()->back()->with('error', 'Please choose a valid attendance status.');
        }

        if ($attendanceStatus !== 'no_show' && !in_array($decision, $allowedDecision, true)) {
            return redirect()->back()->with('error', 'Please choose a valid recruiter decision.');
        }

        if (mb_strlen($notes) > 5000 || mb_strlen($strengths) > 3000 || mb_strlen($concerns) > 3000) {
            return redirect()->back()->with('error', 'One or more review fields are too long.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $reviewData = [
            'booking_id' => (int) $bookingId,
            'application_id' => (int) ($booking['application_id'] ?? 0),
            'candidate_id' => (int) ($booking['user_id'] ?? 0),
            'job_id' => (int) ($booking['job_id'] ?? 0),
            'recruiter_id' => $currentUserId,
            'attendance_status' => $attendanceStatus,
            'decision' => $attendanceStatus === 'no_show' ? 'rejected' : $decision,
            'strengths' => $strengths !== '' ? $strengths : null,
            'concerns' => $concerns !== '' ? $concerns : null,
            'notes' => $notes !== '' ? $notes : null,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];

        $existingReview = $bookingReviewModel->getByBookingId((int) $bookingId);
        if ($existingReview) {
            $bookingReviewModel->update((int) $existingReview['id'], $reviewData);
        } else {
            $bookingReviewModel->insert($reviewData);
        }

        $bookingStatus = $attendanceStatus === 'no_show' ? 'no_show' : 'completed';
        $bookingModel->update((int) $bookingId, [
            'booking_status' => $bookingStatus,
        ]);

        if (!empty($booking['application_id'])) {
            $applicationStatus = $attendanceStatus === 'no_show'
                ? 'rejected'
                : $decision;

            $applicationModel->update((int) $booking['application_id'], [
                'status' => $applicationStatus,
            ]);

            $stageLabel = $attendanceStatus === 'no_show'
                ? 'HR Interview No Show'
                : ('HR Interview Reviewed - ' . ucwords(str_replace('_', ' ', $decision)));
            $stageModel->moveToStage((int) $booking['application_id'], $stageLabel);

            $notificationModel = model('NotificationModel');
            $candidateId = (int) ($booking['user_id'] ?? 0);
            $applicationId = (int) $booking['application_id'];

            $notificationModel->createNotification(
                $candidateId,
                $applicationId,
                'interview_reviewed',
                $attendanceStatus === 'no_show'
                    ? 'Your interview was marked as no show by the recruiter.'
                    : 'Your interview review is complete. Final status: ' . ucwords(str_replace('_', ' ', $applicationStatus)) . '.',
                base_url('candidate/applications'),
                true
            );

            $notificationModel->createNotification(
                $candidateId,
                $applicationId,
                in_array($applicationStatus, ['selected', 'hired'], true) ? 'offer_sent' : 'application_status_changed',
                in_array($applicationStatus, ['selected', 'hired'], true)
                    ? 'Congratulations! Your application has moved to the offer stage.'
                    : 'Your application status was updated to ' . ucwords(str_replace('_', ' ', $applicationStatus)) . '.',
                base_url('candidate/applications'),
                true
            );
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'Failed to save interview review.');
        }

        return redirect()->to('/recruiter/slots/bookings')->with('success', 'Interview review saved successfully.');
    }

    /**
     * Get statistics based on job filtering
     */
    private function getBookingStats($jobIds = null)
    {
        $bookingModel = model('InterviewBookingModel');

        if (is_array($jobIds) && empty($jobIds)) {
            return [
                'total_bookings' => 0,
                'upcoming' => 0,
                'completed' => 0,
                'rescheduled' => 0,
            ];
        }

        if ($jobIds !== null) {
            return [
                'total_bookings' => $bookingModel->whereIn('job_id', $jobIds)->countAllResults(),
                'upcoming' => $bookingModel->whereIn('job_id', $jobIds)->where('slot_datetime >', date('Y-m-d H:i:s'))->countAllResults(),
                'completed' => $bookingModel->whereIn('job_id', $jobIds)->where('booking_status', 'completed')->countAllResults(),
                'rescheduled' => $bookingModel->whereIn('job_id', $jobIds)->where('booking_status', 'rescheduled')->countAllResults()
            ];
        } else {
            return [
                'total_bookings' => $bookingModel->countAll(),
                'upcoming' => $bookingModel->where('slot_datetime >', date('Y-m-d H:i:s'))->countAllResults(),
                'completed' => $bookingModel->where('booking_status', 'completed')->countAllResults(),
                'rescheduled' => $bookingModel->where('booking_status', 'rescheduled')->countAllResults()
            ];
        }
    }

    /**
     * Admin reschedule booking
     */
    public function adminReschedule($bookingId)
    {
        $bookingModel = model('InterviewBookingModel');
        $slotModel = model('InterviewSlotModel');

        $booking = $bookingModel->find($bookingId);

        if (!$booking) {
            return redirect()->back()->with('error', 'Booking not found');
        }

        $availableSlots = $slotModel->getAvailableSlotsGrouped($booking['job_id']);

        return view('recruiter/slots/reschedule', [
            'booking' => $booking,
            'available_slots' => $availableSlots
        ]);
    }

    /**
     * Process admin reschedule
     */
    public function processAdminReschedule()
    {
        $bookingId = $this->request->getPost('booking_id');
        $newSlotId = $this->request->getPost('slot_id');
        $reason = $this->request->getPost('reason');

        $bookingModel = model('InterviewBookingModel');
        $slotModel = model('InterviewSlotModel');
        $rescheduleHistoryModel = model('RescheduleHistoryModel');
        $notificationModel = model('NotificationModel');

        $booking = $bookingModel->find($bookingId);

        if (!$booking) {
            return redirect()->back()->with('error', 'Booking not found');
        }

        // Check if new slot is available
        if (!$slotModel->isSlotAvailable($newSlotId)) {
            return redirect()->back()->with('error', 'Selected slot is not available');
        }

        $newSlot = $slotModel->find($newSlotId);

        // Start transaction
        $db = \Config\Database::connect();
        $db->transStart();

        // Decrement old slot count
        $slotModel->decrementBookedCount($booking['slot_id']);

        // Increment new slot count
        $slotModel->incrementBookedCount($newSlotId);

        // Update booking (don't increment reschedule_count for admin)
        $bookingModel->update($bookingId, [
            'slot_id' => $newSlotId,
            'slot_datetime' => $newSlot['slot_datetime'],
            'last_rescheduled_at' => date('Y-m-d H:i:s')
        ]);

        // Save reschedule history
        $rescheduleHistoryModel->insert([
            'booking_id' => $bookingId,
            'old_slot_id' => $booking['slot_id'],
            'new_slot_id' => $newSlotId,
            'old_slot_datetime' => $booking['slot_datetime'],
            'new_slot_datetime' => $newSlot['slot_datetime'],
            'reason' => $reason,
            'rescheduled_by' => 'admin',
            'rescheduled_at' => date('Y-m-d H:i:s')
        ]);

        // Update application
        model('ApplicationModel')->update($booking['application_id'], [
            'interview_slot' => $newSlot['slot_datetime'],
            'status' => 'reschedule_required'
        ]);

        // Notify candidate
        $notificationModel->createNotification(
            $booking['user_id'],
            $booking['application_id'],
            'interview_rescheduled',
            'Your interview has been rescheduled by the admin.',
            base_url('candidate/my-bookings'),
            true
        );

        $db->transComplete();

        if ($db->transStatus()) {
            return redirect()->to('/recruiter/slots/bookings')->with('success', 'Booking rescheduled successfully');
        } else {
            return redirect()->back()->with('error', 'Failed to reschedule booking');
        }
    }

    /**
     * Mark interview as completed
     */
    public function markCompleted($bookingId)
    {
        $bookingModel = model('InterviewBookingModel');
        $currentUserId = (int) session()->get('user_id');

        // Verify that the booking belongs to a job posted by this recruiter
        $booking = $bookingModel
            ->select('interview_bookings.*, jobs.recruiter_id')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->where('interview_bookings.id', (int) $bookingId)
            ->first();

        if (!$booking) {
            return redirect()->back()->with('error', 'Booking not found.');
        }

        if ((int) ($booking['recruiter_id'] ?? 0) !== $currentUserId && session()->get('role') === 'recruiter') {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $bookingModel->update($bookingId, [
            'booking_status' => 'completed'
        ]);

        return redirect()->back()->with('success', 'Interview marked as completed');
    }

    /**
     * Shortlist candidates (bulk action)
     */
    public function bulkShortlist()
    {
        $applicationIds = $this->request->getPost('application_ids');

        if (empty($applicationIds)) {
            return redirect()->back()->with('error', 'No applications selected');
        }

        $applicationModel = model('ApplicationModel');
        $notificationModel = model('NotificationModel');

        foreach ($applicationIds as $appId) {
            $application = $applicationModel->find($appId);

            if ($application && in_array($application['status'], ['applied', 'shortlisted'], true)) {
                // Update to shortlisted
                $applicationModel->update($appId, [
                    'status' => 'shortlisted'
                ]);

                // Trigger notification
                $notificationModel->triggerApplicationNotifications(
                    $application['candidate_id'],
                    $applicationModel->find($appId)
                );
            }
        }

        return redirect()->back()->with('success', count($applicationIds) . ' candidates shortlisted');
    }
}