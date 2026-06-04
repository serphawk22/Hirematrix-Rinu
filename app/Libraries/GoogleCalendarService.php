<?php

namespace App\Libraries;

use CodeIgniter\Config\Services;
use App\Models\InterviewBookingModel;
use App\Models\UserModel;

/**
 * Google Calendar Integration Service
 * 
 * Handles calendar event creation, updates, and deletion
 * as well as generating Google Calendar links for events
 */
class GoogleCalendarService
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $apiKey;
    
    public function __construct()
    {
        $this->clientId = getenv('google_client_id') ?: (getenv('google.clientId') ?: '');
        $this->clientSecret = getenv('google_client_secret') ?: (getenv('google.clientSecret') ?: '');
        $this->redirectUri = getenv('google.redirectUri') ?: (getenv('google_redirect_uri') ?: base_url('auth/google-calendar/callback'));
        $this->apiKey = getenv('google_api_key') ?: (getenv('google.apiKey') ?: '');
    }
    
    /**
     * Check if Google Calendar integration is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }
    
    /**
     * Get OAuth authorization URL
     */
    public function getAuthUrl(string $state = ''): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        if (!empty($state)) {
            $params['state'] = $state;
        }
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $code): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code'
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        $accessToken = isset($data['access_token']) ? $data['access_token'] : null;
        
        if ($accessToken) {
            return [
                'access_token' => $accessToken,
                'refresh_token' => isset($data['refresh_token']) ? $data['refresh_token'] : null,
                'expires_in' => isset($data['expires_in']) ? $data['expires_in'] : 3600
            ];
        }
        
        return null;
    }
    
    /**
     * Refresh access token
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token'
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        $accessToken = isset($data['access_token']) ? $data['access_token'] : null;
        
        if ($accessToken) {
            return [
                'access_token' => $accessToken,
                'expires_in' => isset($data['expires_in']) ? $data['expires_in'] : 3600
            ];
        }
        
        return null;
    }
    
    /**
     * Create a calendar event
     */
    public function createEvent(array $booking, array $candidate, array $job, ?string $accessToken = null): ?array
    {
        $timezone = config('App')->appTimezone ?? 'UTC';
        $startTime = strtotime($booking['slot_datetime']);
        $endTime = $startTime + 3600; // 1 hour duration
        
        $candidateName = isset($candidate['name']) ? $candidate['name'] : 'Candidate';
        $jobTitle = isset($job['title']) ? $job['title'] : 'Interview';
        $location = isset($job['location']) ? $job['location'] : 'TBD';
        
        $event = [
            'summary' => "Interview: " . $jobTitle,
            'location' => $location,
            'description' => $this->buildEventDescription($booking, $candidate, $job),
            'start' => [
                'dateTime' => date('c', $startTime),
                'timeZone' => $timezone
            ],
            'end' => [
                'dateTime' => date('c', $endTime),
                'timeZone' => $timezone
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => 'interview-' . ($booking['id'] ?? uniqid()),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
                ]
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 1440], // 24 hours
                    ['method' => 'popup', 'minutes' => 60],   // 1 hour
                    ['method' => 'popup', 'minutes' => 15]   // 15 minutes
                ]
            ],
            'colorId' => '9' // Purple for interviews
        ];
        
        // Add attendees if email available
        if (!empty($candidate['email'])) {
            $event['attendees'] = [
                ['email' => $candidate['email']]
            ];
        }
        
        if ($accessToken) {
            return $this->sendToGoogleCalendar($event, $accessToken);
        }
        
        return null;
    }
    
    /**
     * Send event to Google Calendar API
     */
    private function sendToGoogleCalendar(array $event, string $accessToken): ?array
    {
        // conferenceDataVersion=1 is required to trigger Google Meet generation
        $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($event),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer {$accessToken}"
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'event_id' => $data['id'],
                'html_link' => isset($data['htmlLink']) ? $data['htmlLink'] : null,
                'meet_link' => $data['hangoutLink'] ?? null
            ];
        }
        
        log_message('error', 'Google Calendar API error: ' . $response);
        return null;
    }
    
    /**
     * Update a calendar event
     */
    public function updateEvent(string $eventId, array $booking, array $candidate, array $job, string $accessToken): ?array
    {
        $startTime = strtotime($booking['slot_datetime']);
        $endTime = $startTime + 3600;
        
        $jobTitle = isset($job['title']) ? $job['title'] : 'Interview';
        $location = isset($job['location']) ? $job['location'] : 'TBD';
        
        $event = [
            'summary' => "Interview: " . $jobTitle,
            'location' => $location,
            'description' => $this->buildEventDescription($booking, $candidate, $job),
            'start' => [
                'dateTime' => date('c', $startTime),
                'timeZone' => 'UTC'
            ],
            'end' => [
                'dateTime' => date('c', $endTime),
                'timeZone' => 'UTC'
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://www.googleapis.com/calendar/v3/calendars/primary/events/{$eventId}",
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($event),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer {$accessToken}"
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'event_id' => $data['id'],
                'html_link' => isset($data['htmlLink']) ? $data['htmlLink'] : null
            ];
        }
        
        return null;
    }
    
    /**
     * Delete a calendar event
     */
    public function deleteEvent(string $eventId, string $accessToken): bool
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://www.googleapis.com/calendar/v3/calendars/primary/events/{$eventId}",
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$accessToken}"
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 204;
    }
    
    /**
     * Generate Google Calendar add link (for users without OAuth)
     */
    public function generateAddLink(array $booking, array $job, array $candidate = []): string
    {
        $startTime = strtotime($booking['slot_datetime']);
        $endTime = $startTime + 3600;
        
        $jobTitle = isset($job['title']) ? $job['title'] : 'Interview';
        $location = isset($job['location']) ? $job['location'] : '';
        
        $params = [
            'action' => 'TEMPLATE',
            'text' => "Interview: " . $jobTitle,
            'dates' => date('Ymd\THis\Z', $startTime) . '/' . date('Ymd\THis\Z', $endTime),
            'details' => $this->buildCalendarDescription($booking, $job, $candidate),
            'location' => $location
        ];
        
        // Truncate details if too long
        if (strlen($params['details']) > 300) {
            $params['details'] = substr($params['details'], 0, 297) . '...';
        }
        
        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }
    
    /**
     * Build event description
     */
    private function buildEventDescription(array $booking, array $candidate, array $job): string
    {
        $candidateName = isset($candidate['name']) ? $candidate['name'] : 'Candidate';
        $jobTitle = isset($job['title']) ? $job['title'] : 'Position';
        $company = isset($job['company']) ? $job['company'] : (isset($job['recruiter_company']) ? $job['recruiter_company'] : 'N/A');
        
        $description = "Interview with " . $candidateName . "\n\n";
        $description .= "Position: " . $jobTitle . "\n";
        $description .= "Company: " . $company . "\n\n";
        
        if (!empty($candidate['email'])) {
            $description .= "Candidate Email: " . $candidate['email'] . "\n";
        }
        
        $description .= "\n---\n";
        $description .= "Booked via AI Job Portal";
        
        return $description;
    }
    
    /**
     * Build calendar description for add link
     */
    private function buildCalendarDescription(array $booking, array $job, array $candidate): string
    {
        $jobTitle = isset($job['title']) ? $job['title'] : 'Interview';
        $desc = "Interview for " . $jobTitle . "\n";
        
        if (!empty($job['company'])) {
            $desc .= "Company: " . $job['company'] . "\n";
        }
        
        if (!empty($booking['max_reschedules'])) {
            $desc .= "Reschedules remaining: " . $booking['max_reschedules'] . "\n";
        }
        
        return $desc;
    }
    
    /**
     * Sync booking to Google Calendar
     */
    public function syncBooking(int $bookingId, ?string $accessToken = null): bool
    {
        $bookingModel = model('InterviewBookingModel');
        $userModel = model('UserModel');
        $jobModel = model('JobModel');
        
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        $candidate = $userModel->find($booking['user_id']);
        $job = $jobModel->find($booking['job_id']);
        
        if (!$candidate || !$job) {
            return false;
        }
        
        // Create or update event
        $hasEventId = !empty($booking['google_event_id']);
        $result = null;
        
        if ($hasEventId && $accessToken) {
            $result = $this->updateEvent($booking['google_event_id'], $booking, $candidate, $job, $accessToken);
        } elseif ($accessToken) {
            $result = $this->createEvent($booking, $candidate, $job, $accessToken);
        }
        
        // Generate public add link regardless
        $addLink = $this->generateAddLink($booking, $job, $candidate);
        
        // Update booking with calendar info
        $updateData = [
            'calendar_add_link' => $addLink,
            'last_synced_at' => date('Y-m-d H:i:s')
        ];
        
        if ($result) {
            $updateData['google_event_id'] = $result['event_id'];
            $updateData['calendar_html_link'] = $result['html_link'];
        }
        
        return $bookingModel->update($bookingId, $updateData);
    }
    
    /**
     * Handle reschedule sync
     */
    public function syncReschedule(int $bookingId, ?string $accessToken = null): bool
    {
        return $this->syncBooking($bookingId, $accessToken);
    }
    
    /**
     * Cancel calendar event
     */
    public function cancelEvent(int $bookingId, ?string $accessToken = null): bool
    {
        $bookingModel = model('InterviewBookingModel');
        
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking || empty($booking['google_event_id'])) {
            return false;
        }
        
        if ($accessToken) {
            $this->deleteEvent($booking['google_event_id'], $accessToken);
        }
        
        return $bookingModel->update($bookingId, [
            'google_event_id' => null,
            'calendar_add_link' => null,
            'calendar_html_link' => null
        ]);
    }
}            