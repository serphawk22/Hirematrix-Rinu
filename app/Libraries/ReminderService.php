<?php

namespace App\Libraries;

use App\Models\InterviewBookingModel;
use App\Models\UserModel;
use App\Models\JobModel;

/**
 * Reminder Service for Interview Notifications
 * 
 * Handles sending email and notification reminders
 * before scheduled interviews
 */
class ReminderService
{
    /**
     * Send reminders for upcoming interviews
     * 
     * @param string|null $secret Optional cron secret for validation
     */
    public function sendUpcomingReminders(?string $secret = null): array
    {
        // Validate cron secret if provided
        if ($secret !== null) {
            $configuredSecret = getenv('cron.secret');
            if (!empty($configuredSecret) && $secret !== $configuredSecret) {
                log_message('error', 'Unauthorized attempt to run reminders with invalid secret.');
                return ['sent' => 0, 'failed' => 0, 'error' => 'Unauthorized'];
            }
        }

        $bookingModel = model('InterviewBookingModel');
        $userModel = model('UserModel');
        $jobModel = model('JobModel');
        $notificationModel = model('NotificationModel');
        
        $results = ['sent' => 0, 'failed' => 0];
        
        // Get bookings that are in the next 24 hours
        $now = time();
        $twentyFourHours = $now + 86400;
        
        $bookings = $bookingModel
            ->select('interview_bookings.*, users.name as candidate_name, users.email as candidate_email, jobs.title as job_title')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->whereIn('interview_bookings.booking_status', ['booked', 'rescheduled'])
            ->where('interview_bookings.slot_datetime >=', date('Y-m-d H:i:s', $now))
            ->where('interview_bookings.slot_datetime <=', date('Y-m-d H:i:s', $twentyFourHours))
            ->findAll();
        
        foreach ($bookings as $booking) {
            // Check if 24-hour reminder was already sent
            if (!empty($booking['last_synced_at'])) {
                $lastSent = strtotime($booking['last_synced_at']);
                // Only send if not sent in the last 20 hours (avoid duplicate)
                if ($now - $lastSent < 72000) {
                    continue;
                }
            }
            
            $slotTime = date('M d, Y h:i A', strtotime($booking['slot_datetime']));
            $jobTitle = isset($booking['job_title']) ? $booking['job_title'] : 'the position';
            
            // Send email reminder
            $emailSent = $this->sendEmailReminder($booking, $slotTime);
            
            // Create in-app notification
            $notificationModel->createNotification(
                $booking['user_id'],
                (int) $booking['application_id'],
                'interview_reminder_24h',
                "Your interview for " . $jobTitle . " is scheduled for tomorrow at " . $slotTime . ". Please prepare accordingly.",
                base_url('candidate/my-bookings'),
                true
            );
            
            if ($emailSent) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
            
            // Update last reminder sent timestamp
            $bookingModel->update($booking['id'], ['last_synced_at' => date('Y-m-d H:i:s')]);
        }
        
        // Also send 1-hour reminders
        $oneHour = $now + 3600;
        $oneHourFifteen = $now + 4500;
        
        $oneHourBookings = $bookingModel
            ->select('interview_bookings.*, users.name as candidate_name, users.email as candidate_email, jobs.title as job_title')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->whereIn('interview_bookings.booking_status', ['booked', 'rescheduled'])
            ->where('interview_bookings.slot_datetime >=', date('Y-m-d H:i:s', $oneHour))
            ->where('interview_bookings.slot_datetime <=', date('Y-m-d H:i:s', $oneHourFifteen))
            ->findAll();
        
        foreach ($oneHourBookings as $booking) {
            $slotTime = date('h:i A', strtotime($booking['slot_datetime']));
            $jobTitle = isset($booking['job_title']) ? $booking['job_title'] : 'the position';
            
            $notificationModel->createNotification(
                $booking['user_id'],
                (int) $booking['application_id'],
                'interview_reminder_1h',
                "Your interview for " . $jobTitle . " starts in about 1 hour at " . $slotTime . ". Good luck!",
                base_url('candidate/my-bookings'),
                true
            );
            
            $results['sent']++;
        }
        
        return $results;
    }
    
    /**
     * Send email reminder for interview
     */
    private function sendEmailReminder(array $booking, string $slotTime): bool
    {
        if (empty($booking['candidate_email'])) {
            return false;
        }
        
        $candidateName = isset($booking['candidate_name']) ? $booking['candidate_name'] : 'Candidate';
        $jobTitle = isset($booking['job_title']) ? $booking['job_title'] : 'the position';
        
        $subject = "Reminder: Interview for " . $jobTitle . " tomorrow";
        
        $message = "
        <h2>Interview Reminder</h2>
        
        <p>Hi " . $candidateName . ",</p>
        
        <p>This is a reminder that your interview for <strong>" . $jobTitle . "</strong> is scheduled for:</p>
        
        <p><strong>Date & Time:</strong> " . $slotTime . "</p>
        
        <p>Please make sure you:</p>
        <ul>
            <li>Review the job description</li>
            <li>Prepare your introduction and relevant examples</li>
            <li>Check your audio/video equipment if applicable</li>
            <li>Join 5-10 minutes early</li>
        </ul>
        
        <p>You can also add this to your calendar using the link in your booking confirmation.</p>
        
        <p>Good luck!</p>
        
        <p>Best regards,<br>
        The AI Job Portal Team</p>
        ";
        
        return $this->sendEmail($booking['candidate_email'], $subject, $message);
    }
    
    /**
     * Send email via CodeIgniter email service
     */
    private function sendEmail(string $to, string $subject, string $message): bool
    {
        $email = \Config\Services::email();
        
        $email->setTo($to);
        $email->setFrom(getenv('email.from') ?: 'noreply@aijobportal.com', 'AI Job Portal');
        $email->setSubject($subject);
        $email->setMessage($message);
        $email->setMailType('html'); // Ensure HTML rendering
        
        return $email->send(false); // false to avoid exceptions
    }
    
    /**
     * Send immediate notification for new booking
     */
    public function sendBookingConfirmation(int $bookingId): bool
    {
        $bookingModel = model('InterviewBookingModel');
        $userModel = model('UserModel');
        $jobModel = model('JobModel');
        $notificationModel = model('NotificationModel');
        
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        $user = $userModel->find($booking['user_id']);
        $job = $jobModel->find($booking['job_id']);
        
        if (!$user || !$job) {
            return false;
        }
        
        $slotTime = date('M d, Y h:i A', strtotime($booking['slot_datetime']));
        $userName = isset($user['name']) ? $user['name'] : 'User';
        $jobTitle = isset($job['title']) ? $job['title'] : 'the position';
        $calendarLink = isset($booking['calendar_add_link']) ? $booking['calendar_add_link'] : '#';
        $maxReschedules = isset($booking['max_reschedules']) ? $booking['max_reschedules'] : 2;
        
        // Create in-app notification
        $notificationModel->createNotification(
            $booking['user_id'],
            (int) $booking['application_id'],
            'interview_booked',
            "Your interview for " . $jobTitle . " has been booked for " . $slotTime . ". Add it to your calendar!",
            base_url('candidate/my-bookings'),
            true
        );
        
        // Send email confirmation
        $subject = "Interview Confirmed: " . $jobTitle;
        
        $message = "
        <h2>Interview Booked!</h2>
        
        <p>Hi " . $userName . ",</p>
        
        <p>Your interview has been successfully scheduled.</p>
        
        <p><strong>Position:</strong> " . $jobTitle . "</p>
        <p><strong>Date & Time:</strong> " . $slotTime . "</p>
        
        <p>Add to your calendar:</p>
        <p><a href='" . $calendarLink . "'>Add to Google Calendar</a></p>
        
        <p>If you need to reschedule, you have " . $maxReschedules . " reschedule(s) available (24+ hours before the interview).</p>
        
        <p>Best of luck with your interview!</p>
        ";
        
        return $this->sendEmail($user['email'], $subject, $message);
    }
    
    /**
     * Send reschedule notification
     */
    public function sendRescheduleNotification(int $bookingId): bool
    {
        $bookingModel = model('InterviewBookingModel');
        $userModel = model('UserModel');
        $jobModel = model('JobModel');
        $notificationModel = model('NotificationModel');
        
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        $user = $userModel->find($booking['user_id']);
        $job = $jobModel->find($booking['job_id']);
        
        if (!$user || !$job) {
            return false;
        }
        
        $slotTime = date('M d, Y h:i A', strtotime($booking['slot_datetime']));
        $maxReschedules = isset($booking['max_reschedules']) ? $booking['max_reschedules'] : 2;
        $rescheduleCount = isset($booking['reschedule_count']) ? $booking['reschedule_count'] : 0;
        $remaining = $maxReschedules - $rescheduleCount;
        $userName = isset($user['name']) ? $user['name'] : 'User';
        $jobTitle = isset($job['title']) ? $job['title'] : 'the position';
        $calendarLink = isset($booking['calendar_add_link']) ? $booking['calendar_add_link'] : '#';
        
        // Create notification
        $notificationModel->createNotification(
            $booking['user_id'],
            (int) $booking['application_id'],
            'interview_rescheduled',
            "Your interview for " . $jobTitle . " has been rescheduled to " . $slotTime . ". " . $remaining . " reschedule(s) remaining.",
            base_url('candidate/my-bookings'),
            true
        );
        
        // Update calendar event
        $calendarService = new GoogleCalendarService();
        $calendarService->syncReschedule($bookingId);
        
        // Send email
        $subject = "Interview Rescheduled: " . $jobTitle;
        
        $message = "
        <h2>Interview Rescheduled</h2>
        
        <p>Hi " . $userName . ",</p>
        
        <p>Your interview for <strong>" . $jobTitle . "</strong> has been rescheduled.</p>
        
        <p><strong>New Date & Time:</strong> " . $slotTime . "</p>
        <p><strong>Remaining Reschedules:</strong> " . $remaining . "</p>
        
        <p>Updated calendar link: <a href='" . $calendarLink . "'>Add to Google Calendar</a></p>
        
        <p>Good luck!</p>
        ";
        
        return $this->sendEmail($user['email'], $subject, $message);
    }

    /**
     * Send alerts to recruiters for interviews that haven't been reviewed
     * 24 hours after the slot time.
     */
    public function sendOverdueReviewReminders(): array
    {
        $bookingModel = model('InterviewBookingModel');
        $notificationModel = model('NotificationModel');
        
        $results = ['sent' => 0, 'skipped' => 0];
        $now = time();
        $twentyFourHoursAgo = date('Y-m-d H:i:s', $now - 86400);

        // Find bookings where slot has passed > 24h ago and no review exists
        $overdue = $bookingModel
            ->select('interview_bookings.*, jobs.recruiter_id, jobs.title as job_title, users.name as candidate_name')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->join('interview_booking_reviews', 'interview_booking_reviews.booking_id = interview_bookings.id', 'left')
            ->whereIn('interview_bookings.booking_status', ['booked', 'rescheduled'])
            ->where('interview_bookings.slot_datetime <=', $twentyFourHoursAgo)
            ->where('interview_booking_reviews.id IS NULL')
            ->findAll();

        foreach ($overdue as $booking) {
            $slotTs = strtotime($booking['slot_datetime']);
            $lastSentTs = !empty($booking['last_synced_at']) ? strtotime($booking['last_synced_at']) : 0;

            // Only notify if we haven't sent an overdue nudge yet (last_synced_at > slotTs)
            if ($lastSentTs > $slotTs) {
                $results['skipped']++;
                continue;
            }

            if (!empty($booking['recruiter_id'])) {
                $slotTime = date('M d, Y h:i A', $slotTs);
                
                $notificationModel->createNotification(
                    (int) $booking['recruiter_id'],
                    (int) $booking['application_id'],
                    'interview_review_overdue',
                    "The interview for " . $booking['job_title'] . " with " . $booking['candidate_name'] . " (held on " . $slotTime . ") has been pending review for over 24 hours.",
                    base_url('recruiter/slots/bookings'),
                    true
                );

                // Update timestamp to mark this overdue nudge as sent
                $bookingModel->update($booking['id'], ['last_synced_at' => date('Y-m-d H:i:s')]);
                $results['sent']++;
            }
        }

        return $results;
    }
}