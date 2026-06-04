<?php

namespace App\Controllers;

class NotificationController extends BaseController
{
    
    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $userId = session()->get('user_id');
        $notificationModel = model('NotificationModel');
        $isAjax = $this->request->isAJAX();
        
        $notification = $notificationModel->find($id);
        
        // Verify ownership
        if ($notification && $notification['user_id'] == $userId) {
            $notificationModel->markAsRead($id);
            
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Notification marked as read',
                    'notification_id' => (int) $id,
                    'is_read' => true,
                    'unread_count' => (int) $notificationModel->getUnreadCount($userId),
                    'csrf_token_name' => csrf_token(),
                    'csrf_hash' => csrf_hash(),
                ]);
            }

            // Redirect to action link if exists
            if (!empty($notification['action_link'])) {
                return redirect()->to($notification['action_link']);
            }
        }
        
        if ($isAjax) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Notification not found',
                'csrf_token_name' => csrf_token(),
                'csrf_hash' => csrf_hash(),
            ]);
        }

        return redirect()->back()->with('success', 'Notification marked as read');
    }
    
    /**
     * Mark all as read
     */
    public function markAllAsRead()
    {
        $userId = session()->get('user_id');
        $notificationModel = model('NotificationModel');
        $isAjax = $this->request->isAJAX();
        
        $notificationModel->markAllAsRead($userId);

        if ($isAjax) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'All notifications marked as read',
                'unread_count' => 0,
                'csrf_token_name' => csrf_token(),
                'csrf_hash' => csrf_hash(),
            ]);
        }
        
        return redirect()->back()->with('success', '');
    }
    
    /**
     * View all notifications
     */
    public function index()
    {
        $userId = session()->get('user_id');
        $role = (string) session()->get('role');
        $notificationModel = model('NotificationModel');
        
        $notifications = $notificationModel->getUserNotifications($userId, 50);
        $unreadCount = $notificationModel->getUnreadCount($userId);

        $view = $role === 'recruiter' ? 'recruiter/notifications' : 'candidate/notifications';

        return view($view, [
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }
    
    /**
     * Delete notification
     */
    public function delete($id)
    {
        $userId = session()->get('user_id');
        $notificationModel = model('NotificationModel');
        $isAjax = $this->request->isAJAX();
        
        $notification = $notificationModel->find($id);
        
        // Verify ownership
        if ($notification && $notification['user_id'] == $userId) {
            $notificationModel->delete($id);
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Notification deleted',
                    'notification_id' => (int) $id,
                    'unread_count' => (int) $notificationModel->getUnreadCount($userId),
                    'csrf_token_name' => csrf_token(),
                    'csrf_hash' => csrf_hash(),
                ]);
            }
            return redirect()->back()->with('success', 'Notification deleted');
        }
        
        if ($isAjax) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Notification not found',
                'csrf_token_name' => csrf_token(),
                'csrf_hash' => csrf_hash(),
            ]);
        }

        return redirect()->back()->with('error', 'Notification not found');
    }
}
