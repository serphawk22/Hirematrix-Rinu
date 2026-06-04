<?php

namespace App\Controllers;

use App\Libraries\GoogleCalendarService;
use App\Libraries\ReminderService;

/**
 * Calendar Sync Controller
 * 
 * Handles Google Calendar OAuth and sync operations
 */
class CalendarSyncController extends BaseController
{
    private GoogleCalendarService $calendarService;
    private ReminderService $reminderService;
    
    public function __construct()
    {
        $this->calendarService = new GoogleCalendarService();
        $this->reminderService = new ReminderService();
    }
    
    /**
     * Connect Google Calendar
     */
    public function connect()
    {
        if (!$this->calendarService->isConfigured()) {
            return redirect()->back()->with('error', 'Google Calendar integration is not configured');
        }
        
        $userId = session()->get('user_id');
        $state = base64_encode(json_encode(['user_id' => $userId, 'return_url' => current_url()]));
        
        return redirect()->to($this->calendarService->getAuthUrl($state));
    }
    
    /**
     * OAuth Callback
     */
    public function callback()
    {
        $code = $this->request->getGet('code');
        $state = $this->request->getGet('state');
        
        if (empty($code)) {
            return redirect()->to('/candidate/settings')->with('error', 'Authorization failed');
        }
        
        // Parse state
        $stateData = json_decode(base64_decode($state ?? ''), true);
        $userId = $stateData['user_id'] ?? session()->get('user_id');
        
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Session expired');
        }
        
        // Get tokens
        $tokens = $this->calendarService->getAccessToken($code);
        
        if (!$tokens) {
            return redirect()->to('/candidate/settings')->with('error', 'Failed to get access token');
        }
        
        // Save tokens to user
        $userModel = model('UserModel');
        
        $expiresAt = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));
        
        $userModel->update($userId, [
            'google_refresh_token' => $tokens['refresh_token'] ?? '',
            'google_token_expires_at' => $expiresAt,
            'calendar_sync_enabled' => 1
        ]);
        
        // Sync existing bookings
        $bookingModel = model('InterviewBookingModel');
        $userBookings = $bookingModel->getUserBookings($userId);
        
        foreach ($userBookings as $booking) {
            if (in_array($booking['booking_status'], ['booked', 'rescheduled'])) {
                $bookingModel->syncToCalendar($booking['id']);
            }
        }
        
        return redirect()->to('/candidate/settings')->with('success', 'Google Calendar connected successfully!');
    }
    
    /**
     * Disconnect Google Calendar
     */
    public function disconnect()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return redirect()->to('/login');
        }
        
        $userModel = model('UserModel');
        
        $userModel->update($userId, [
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'calendar_sync_enabled' => 0
        ]);
        
        return redirect()->to('/candidate/settings')->with('info', 'Google Calendar disconnected');
    }
    
    /**
     * Sync specific booking to calendar
     */
    public function syncBooking($bookingId)
    {
        $userId = session()->get('user_id');
        
        $bookingModel = model('InterviewBookingModel');
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking || $booking['user_id'] != $userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Booking not found']);
        }
        
        $result = $bookingModel->syncToCalendar($bookingId);
        
        return $this->response->setJSON([
            'success' => $result,
            'message' => $result ? 'Calendar updated' : 'Failed to sync'
        ]);
    }
    
    /**
     * Send test notification (for debugging)
     */
    public function testReminder($bookingId)
    {
        // Only allow in development
        if (ENVIRONMENT !== 'development') {
            return $this->response->setJSON(['error' => 'Not available in production']);
        }
        
        $result = $this->reminderService->sendBookingConfirmation((int) $bookingId);
        
        return $this->response->setJSON(['success' => $result]);
    }
    
    /**
     * Run all pending reminders (called by cron)
     */
    public function runReminders()
    {
        // Verify cron secret if set
        $cronSecret = getenv('cron.secret');
        if ($cronSecret && $this->request->getGet('secret') !== $cronSecret) {
            return $this->response->setJSON(['error' => 'Unauthorized']);
        }
        
        $upcoming = $this->reminderService->sendUpcomingReminders();
        $overdue = $this->reminderService->sendOverdueReviewReminders();
        
        return $this->response->setJSON([
            'success' => true,
            'upcoming_reminders' => $upcoming,
            'overdue_reviews' => $overdue,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}