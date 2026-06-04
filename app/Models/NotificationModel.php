<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'user_id',
        'application_id',
        'type',
        'title',
        'message',
        'action_link',
        'is_read',
        'created_at',
        'read_at'
    ];
    
    protected $useTimestamps = false;
    
    /**
     * Notification type configurations
     */
    private $notificationConfig = [
        'resume_not_uploaded' => [
            'title' => 'Resume Upload Required',
            'icon' => 'fas fa-file-upload',
            'color' => 'warning',
            'priority' => 1
        ],
        'ai_not_started' => [
            'title' => 'Application Submitted',
            'icon' => 'fas fa-paper-plane',
            'color' => 'info',
            'priority' => 2
        ],
        'ai_incomplete' => [
            'title' => 'Application Update',
            'icon' => 'fas fa-info-circle',
            'color' => 'warning',
            'priority' => 3
        ],
        'slot_not_booked' => [
            'title' => 'Book Interview Slot',
            'icon' => 'fas fa-calendar-plus',
            'color' => 'info',
            'priority' => 4
        ],
        'reschedule_required' => [
            'title' => 'Interview Reschedule Needed',
            'icon' => 'fas fa-calendar-times',
            'color' => 'danger',
            'priority' => 5
        ],
        'interview_scheduled' => [
            'title' => 'Interview Scheduled',
            'icon' => 'fas fa-calendar-check',
            'color' => 'success',
            'priority' => 6
        ],
        'interview_booked' => [
            'title' => 'Interview Booked',
            'icon' => 'fas fa-calendar-check',
            'color' => 'success',
            'priority' => 6
        ],
        'interview_rescheduled' => [
            'title' => 'Interview Rescheduled',
            'icon' => 'fas fa-calendar-alt',
            'color' => 'warning',
            'priority' => 6
        ],
        'interview_reviewed' => [
            'title' => 'Interview Reviewed',
            'icon' => 'fas fa-clipboard-check',
            'color' => 'info',
            'priority' => 6
        ],
        'application_status_changed' => [
            'title' => 'Application Status Updated',
            'icon' => 'fas fa-layer-group',
            'color' => 'primary',
            'priority' => 6
        ],
        'offer_sent' => [
            'title' => 'Offer Sent',
            'icon' => 'fas fa-file-signature',
            'color' => 'success',
            'priority' => 6
        ],
        'result_published' => [
            'title' => 'Interview Result Available',
            'icon' => 'fas fa-check-circle',
            'color' => 'success',
            'priority' => 7
        ],
        'recruiter_profile_viewed' => [
            'title' => 'Profile Viewed',
            'icon' => 'fas fa-user-check',
            'color' => 'info',
            'priority' => 8,
            'action_text' => 'View'
        ],
        'recruiter_contact_viewed' => [
            'title' => 'Contact Viewed',
            'icon' => 'fas fa-address-card',
            'color' => 'primary',
            'priority' => 9,
            'action_text' => 'View'
        ],
        'recruiter_resume_downloaded' => [
            'title' => 'Resume Downloaded',
            'icon' => 'fas fa-file-download',
            'color' => 'success',
            'priority' => 10,
            'action_text' => 'View'
        ],
        'recruiter_message' => [
            'title' => 'Message from Recruiter',
            'icon' => 'icon-mail_outline',
            'color' => 'primary',
            'priority' => 11
        ],
        'candidate_message_reply' => [
            'title' => 'Candidate Replied',
            'icon' => 'icon-reply',
            'color' => 'info',
            'priority' => 12
        ],
        'job_alert_match' => [
            'title' => 'Job Alert Match',
            'icon' => 'fas fa-bell',
            'color' => 'success',
            'priority' => 13,
            'action_text' => 'View'
        ],
        'job_invitation' => [
            'title' => 'Invitation to Apply',
            'icon' => 'fas fa-paper-plane',
            'color' => 'primary',
            'priority' => 13,
            'action_text' => 'Review invitation'
        ],
        'daily_resume_tip' => [
            'title' => 'Daily Resume Tip',
            'icon' => 'fas fa-file-lines',
            'color' => 'primary',
            'priority' => 14,
            'action_text' => 'Open resume'
        ],
        'daily_interview_practice' => [
            'title' => 'Daily Interview Practice',
            'icon' => 'fas fa-video',
            'color' => 'success',
            'priority' => 14,
            'action_text' => 'Practice now'
        ],
        'daily_job_search_task' => [
            'title' => 'Daily Job Search Task',
            'icon' => 'fas fa-briefcase',
            'color' => 'warning',
            'priority' => 14,
            'action_text' => 'Browse jobs'
        ],
        'daily_skill_prompt' => [
            'title' => 'Daily Skill Prompt',
            'icon' => 'fas fa-lightbulb',
            'color' => 'info',
            'priority' => 14,
            'action_text' => 'Review strategy'
        ],
        'daily_followup_reminder' => [
            'title' => 'Daily Follow-up Reminder',
            'icon' => 'fas fa-reply',
            'color' => 'primary',
            'priority' => 14,
            'action_text' => 'Check replies'
        ],
        'daily_career_reminder' => [
            'title' => 'Daily Career Reminder',
            'icon' => 'fas fa-sun',
            'color' => 'primary',
            'priority' => 14,
            'action_text' => 'Open'
        ]
    ];
    
    /**
     * Create a notification
     */
    public function createNotification(int $userId, ?int $applicationId, string $type, string $message, ?string $actionLink = null, bool $sendEmail = false): bool
    {
        // Build query for duplicate check
        $builder = $this->where('user_id', $userId)
                        ->where('type', $type)
                        ->where('is_read', 0);
        
        // Only check application_id if it's provided
        if ($applicationId !== null) {
            $builder->where('application_id', $applicationId);
        } else {
            $builder->where('application_id IS NULL');
        }
        
        $exists = $builder->first();
        
        if ($exists) {
            return false; // Don't create duplicate
        }
        
        $config = $this->notificationConfig[$type] ?? [
            'title' => 'Notification',
            'icon' => 'fas fa-bell'
        ];
        
        $created = (bool) $this->insert([
            'user_id' => $userId,
            'application_id' => $applicationId,
            'type' => $type,
            'title' => $config['title'],
            'message' => $message,
            'action_link' => $actionLink,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($created && $sendEmail) {
            $this->sendEmailNotification($userId, (string) ($config['title'] ?? 'Notification'), $message, $actionLink);
        }

        return $created;
    }

    
    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications(int $userId, int $limit = 10): array
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
    
    /**
     * Get all notifications for user
     */
    public function getUserNotifications(int $userId, int $limit = 50): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('is_read', 'ASC')
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): bool
    {
        return $this->update($notificationId, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(int $userId): bool
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
                    ->update();
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->countAllResults();
    }
    
    /**
     * Delete old notifications
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->where('is_read', 1)
                    ->where('created_at <', $cutoffDate)
                    ->delete();
    }
    
    /**
     * Get notification config
     */
    public function getNotificationConfig(string $type): array
    {
        return $this->notificationConfig[$type] ?? [
            'title' => 'Notification',
            'icon' => 'fas fa-bell',
            'color' => 'info',
            'priority' => 99
        ];
    }
    public function getNotificationByApplicationStatus(int $userId, int $applicationId, string $status): ?array
    {
        // Map status to notification type
        $statusToType = [
            'applied' => 'ai_not_started',
            'shortlisted' => 'slot_not_booked',
            'interview_slot_booked' => 'interview_booked',
            'reschedule_required' => 'reschedule_required',
            'interview_scheduled' => 'interview_scheduled',
            'selected' => 'offer_sent',
            'hired' => 'offer_sent',
        ];
        
        $type = $statusToType[$status] ?? null;
        
        if (!$type) {
            return null;
        }
        
        return $this->where('user_id', $userId)
                    ->where('application_id', $applicationId)
                    ->where('type', $type)
                    ->where('is_read', 0)
                    ->first();
    }

    /**
     * Trigger notifications based on application status
     */
   public function triggerApplicationNotifications(int $userId, array $application): void
    {
        $applicationId = $application['id'];
        $status = $application['status'];

        // Map status to notification type
        $statusToType = [
            'applied' => 'ai_not_started',
            'shortlisted' => 'slot_not_booked',
            'interview_slot_booked' => 'interview_booked',
            'reschedule_required' => 'reschedule_required',
            'interview_scheduled' => 'interview_scheduled',
            'selected' => 'offer_sent',
            'hired' => 'offer_sent'
        ];

        $currentType = $statusToType[$status] ?? null;

        if (!$currentType) {
            return; // No notification for this status
        }

        $this->where('user_id', $userId)
             ->where('application_id', $applicationId)
             ->where('type !=', $currentType)
             ->whereIn('type', array_values($statusToType))
             ->delete();

        // Now create notification based on status
        switch ($status) {
            case 'applied':
                $this->createNotification(
                    $userId,
                    $applicationId,
                    'ai_not_started',
                    'Your application has been submitted and is now under recruiter review.',
                    base_url('candidate/applications')
                );
                break;

            case 'shortlisted':
                if (empty($application['interview_slot'])) {
                    $this->createNotification(
                        $userId,
                        $applicationId,
                        'slot_not_booked',
                        'Congratulations! You are shortlisted. Please book your interview slot.',
                        base_url('candidate/book-slot/' . $applicationId)
                    );
                }
                break;

            case 'interview_slot_booked':
                $slotDate = !empty($application['interview_slot'])
                    ? date('M d, Y h:i A', strtotime($application['interview_slot']))
                    : 'your scheduled slot';
                $this->createNotification(
                    $userId,
                    $applicationId,
                    'interview_booked',
                    "Your interview has been booked for {$slotDate}.",
                    base_url('candidate/my-bookings'),
                    true
                );
                break;

            case 'reschedule_required':
                $this->createNotification(
                    $userId,
                    $applicationId,
                    'reschedule_required',
                    'Your interview needs to be rescheduled. Please select a new slot.',
                    base_url('candidate/reschedule-slot/' . $applicationId)
                );
                break;

            case 'interview_scheduled':
                $slotDate = date('M d, Y h:i A', strtotime($application['interview_slot']));
                $this->createNotification(
                    $userId,
                    $applicationId,
                    'interview_scheduled',
                    "Your interview is scheduled for {$slotDate}. Be prepared!",
                    base_url('candidate/interview-details/' . $applicationId)
                );
                break;

            case 'selected':
            case 'hired':
                $this->createNotification(
                    $userId,
                    $applicationId,
                    'offer_sent',
                    $status === 'hired'
                        ? 'Congratulations! Your offer stage has been updated to hired.'
                        : 'Congratulations! Your offer has been sent or your application has moved to the final stage.',
                    base_url('candidate/applications'),
                    true
                );
                break;
        }
    }

    /**
     * Send a simple plain-text email for a notification.
     */
    private function sendEmailNotification(int $userId, string $subject, string $message, ?string $actionLink = null): void
    {
        $user = model('UserModel')->find($userId);
        $recipient = trim((string) ($user['email'] ?? ''));
        if ($recipient === '') {
            return;
        }

        try {
            $email = service('email');
            $config = config('Email');

            $email->setFrom($config->fromEmail, $config->fromName);
            $email->setTo($recipient);
            $email->setSubject($subject);

            $body = $subject . "\n\n" . $message;
            if (!empty($actionLink)) {
                $body .= "\n\nOpen link: " . $actionLink;
            }
            $body .= "\n\n--\nHireMatrix";

            $email->setMessage($body);
            $email->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'Failed sending notification email to user ' . $userId . ': ' . $e->getMessage());
        }
    }


}
?>
