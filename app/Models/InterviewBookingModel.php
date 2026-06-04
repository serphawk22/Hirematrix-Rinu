<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\GoogleCalendarService;

class InterviewBookingModel extends Model
{
    protected $table = 'interview_bookings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'application_id',
        'user_id',
        'job_id',
        'slot_id',
        'slot_datetime',
        'booking_status',
        'reschedule_count',
        'max_reschedules',
        'can_reschedule',
        'booked_at',
        'last_rescheduled_at',
        'google_event_id',
        'calendar_add_link',
        'calendar_html_link',
        'last_synced_at'
    ];
    
    protected $useTimestamps = false;
    
    /**
     * Get booking by application ID
     */
    public function getByApplicationId(int $applicationId): ?array
    {
        return $this->where('application_id', $applicationId)->first();
    }
    
    /**
     * Check if user can reschedule
     */
    public function canReschedule(int $bookingId): array
    {
        $booking = $this->find($bookingId);
        
        if (!$booking) {
            return ['can_reschedule' => false, 'reason' => 'Booking not found'];
        }
        
        // Check if already at max reschedules
        if ($booking['reschedule_count'] >= $booking['max_reschedules']) {
            return [
                'can_reschedule' => false,
                'reason' => "Maximum reschedule limit ({$booking['max_reschedules']}) reached"
            ];
        }
        
        // Check if slot is in the past
        if (strtotime($booking['slot_datetime']) < time()) {
            return ['can_reschedule' => false, 'reason' => 'Cannot reschedule past interviews'];
        }
        
        // Check if within 24 hours
        if (strtotime($booking['slot_datetime']) - time() < 86400) {
            return ['can_reschedule' => false, 'reason' => 'Cannot reschedule within 24 hours of interview'];
        }
        
        if (!$booking['can_reschedule']) {
            return ['can_reschedule' => false, 'reason' => 'Rescheduling disabled for this booking'];
        }
        
        return [
            'can_reschedule' => true,
            'remaining_reschedules' => $booking['max_reschedules'] - $booking['reschedule_count']
        ];
    }
    
    /**
     * Reschedule booking
     */
    public function rescheduleBooking(int $bookingId, int $newSlotId, ?string $reason = null): bool
    {
        $booking = $this->find($bookingId);
        $slotModel = model('InterviewSlotModel');
        $rescheduleHistoryModel = model('RescheduleHistoryModel');
        
        if (!$booking) {
            return false;
        }
        
        // Check if can reschedule
        $canReschedule = $this->canReschedule($bookingId);
        if (!$canReschedule['can_reschedule']) {
            return false;
        }
        
        // Check if new slot is available
        if (!$slotModel->isSlotAvailable($newSlotId)) {
            return false;
        }
        
        $newSlot = $slotModel->find($newSlotId);
        
        // Start transaction
        $db = \Config\Database::connect();
        $db->transStart();
        
        // Decrement old slot count
        $slotModel->decrementBookedCount($booking['slot_id']);
        
        // Increment new slot count
        $slotModel->incrementBookedCount($newSlotId);
        
        // Update booking
        $this->update($bookingId, [
            'slot_id' => $newSlotId,
            'slot_datetime' => $newSlot['slot_datetime'],
            'reschedule_count' => $booking['reschedule_count'] + 1,
            'booking_status' => 'rescheduled',
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
            'rescheduled_by' => 'candidate',
            'rescheduled_at' => date('Y-m-d H:i:s')
        ]);
        
        $db->transComplete();
        
        // Sync to Google Calendar after successful reschedule
        if ($db->transStatus()) {
            $calendarService = new GoogleCalendarService();
            $userModel = model('UserModel');
            $user = $userModel->find($booking['user_id']);
            
            if ($user && !empty($user['google_refresh_token']) && !empty($user['calendar_sync_enabled'])) {
                $tokens = $calendarService->refreshAccessToken($user['google_refresh_token']);
                if ($tokens) {
                    $calendarService->syncReschedule($bookingId, $tokens['access_token']);
                }
            } else {
                // Just update the add link
                $calendarService->syncBooking($bookingId);
            }
        }
        
        return $db->transStatus();
    }
    
    /**
     * Get user's bookings
     */
    public function getUserBookings(int $userId): array
    {
        return $this->select('interview_bookings.*, applications.status as application_status, jobs.title as job_title, interview_slots.slot_date, interview_slots.slot_time, interview_booking_reviews.attendance_status as review_attendance_status, interview_booking_reviews.decision as review_decision, interview_booking_reviews.notes as review_notes, interview_booking_reviews.reviewed_at as review_reviewed_at')
                    ->join('applications', 'applications.id = interview_bookings.application_id', 'left')
                    ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
                    ->join('interview_slots', 'interview_slots.id = interview_bookings.slot_id', 'left')
                    ->join('interview_booking_reviews', 'interview_booking_reviews.booking_id = interview_bookings.id', 'left')
                    ->where('interview_bookings.user_id', $userId)
                    ->orderBy('interview_bookings.slot_datetime', 'ASC')
                    ->findAll();
    }
    
    /**
     * Sync booking to Google Calendar
     */
    public function syncToCalendar(int $bookingId): bool
    {
        $calendarService = new GoogleCalendarService();
        $userModel = model('UserModel');
        
        $booking = $this->find($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        $user = $userModel->find($booking['user_id']);
        
        if ($user && !empty($user['google_refresh_token']) && !empty($user['calendar_sync_enabled'])) {
            $tokens = $calendarService->refreshAccessToken($user['google_refresh_token']);
            if ($tokens) {
                return $calendarService->syncBooking($bookingId, $tokens['access_token']);
            }
        }
        
        // Fallback: just generate the add link
        return $calendarService->syncBooking($bookingId);
    }
}