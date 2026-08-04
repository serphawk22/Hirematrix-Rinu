<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\NotificationModel;

class ApiNotificationController extends ResourceController
{
    protected $format = 'json';

    /**
     * GET api/notifications/(:num)
     * Get all notifications for candidate
     */
    public function getNotifications($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $notificationModel = new NotificationModel();
        $notifications = $notificationModel->getUserNotifications($candidateId, 50);
        $unreadCount = $notificationModel->getUnreadCount($candidateId);

        // Enrich notifications with config data (icons, colors, action_texts)
        foreach ($notifications as &$notification) {
            $config = $notificationModel->getNotificationConfig($notification['type']);
            $notification['icon'] = $config['icon'] ?? 'fas fa-bell';
            $notification['color'] = $config['color'] ?? 'info';
            $notification['action_text'] = $config['action_text'] ?? 'Take Action';
        }
        unset($notification);

        return $this->respond([
            'status' => 'success',
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]
        ]);
    }

    /**
     * POST api/notifications/mark-read/(:num)
     * Mark notification as read
     */
    public function markAsRead($notificationId)
    {
        $notificationId = (int) $notificationId;
        if ($notificationId <= 0) {
            return $this->fail('Invalid Notification ID');
        }

        $json = $this->request->getJSON();
        $candidateId = (int) ($json->candidate_id ?? 0);
        if ($candidateId <= 0) {
            return $this->fail('Invalid or missing Candidate ID');
        }

        $notificationModel = new NotificationModel();
        $notification = $notificationModel->find($notificationId);

        if (!$notification || (int)$notification['user_id'] !== $candidateId) {
            return $this->failNotFound('Notification not found or access denied');
        }

        $notificationModel->markAsRead($notificationId);
        $unreadCount = $notificationModel->getUnreadCount($candidateId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Notification marked as read',
            'data' => [
                'notification_id' => $notificationId,
                'unread_count' => $unreadCount
            ]
        ]);
    }

    /**
     * POST api/notifications/mark-all-read/(:num)
     * Mark all notifications as read
     */
    public function markAllAsRead($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $notificationModel = new NotificationModel();
        $notificationModel->markAllAsRead($candidateId);
        $unreadCount = $notificationModel->getUnreadCount($candidateId);

        return $this->respond([
            'status' => 'success',
            'message' => 'All notifications marked as read',
            'data' => [
                'unread_count' => $unreadCount
            ]
        ]);
    }

    /**
     * POST api/notifications/delete/(:num)
     * Delete notification
     */
    public function deleteNotification($notificationId)
    {
        $notificationId = (int) $notificationId;
        if ($notificationId <= 0) {
            return $this->fail('Invalid Notification ID');
        }

        $json = $this->request->getJSON();
        $candidateId = (int) ($json->candidate_id ?? 0);
        if ($candidateId <= 0) {
            return $this->fail('Invalid or missing Candidate ID');
        }

        $notificationModel = new NotificationModel();
        $notification = $notificationModel->find($notificationId);

        if (!$notification || (int)$notification['user_id'] !== $candidateId) {
            return $this->failNotFound('Notification not found or access denied');
        }

        $notificationModel->delete($notificationId);
        $unreadCount = $notificationModel->getUnreadCount($candidateId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Notification deleted successfully',
            'data' => [
                'notification_id' => $notificationId,
                'unread_count' => $unreadCount
            ]
        ]);
    }
}
