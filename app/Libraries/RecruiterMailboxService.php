<?php

namespace App\Libraries;

use App\Models\RecruiterEmailActivityModel;
use App\Models\RecruiterMailboxConnectionModel;
use GuzzleHttp\Client;

class RecruiterMailboxService
{
    private const DEFAULT_SYNC_LOOKBACK_SECONDS = 604800; // 7 days for first sync.
    private const RECENT_SYNC_LOOKBACK_SECONDS = 259200; // Re-check 3 days to recover late/missed matches.

    private Client $http;
    private RecruiterMailboxConnectionModel $connections;
    private RecruiterEmailActivityModel $activities;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 15, 'http_errors' => false]);
        $this->connections = new RecruiterMailboxConnectionModel();
        $this->activities = new RecruiterEmailActivityModel();
    }

    public function encryptToken(string $token): string
    {
        return base64_encode(service('encrypter')->encrypt($token));
    }

    public function decryptToken(?string $token): string
    {
        if (!$token) {
            return '';
        }
        return service('encrypter')->decrypt(base64_decode($token, true) ?: '');
    }

    public function sendForRecruiter(int $recruiterId, string $to, string $subject, string $html, array $context = []): bool
    {
        $connection = $this->connections->getConnectedForRecruiter($recruiterId);
        if (!$connection) {
            return false;
        }

        $provider = (string) $connection['provider'];
        $messageId = '';
        $threadId = null;
        if ($provider === 'custom') {
            if (!$this->sendCustom($connection, $to, $subject, $html)) {
                return false;
            }
            $messageId = 'smtp-' . bin2hex(random_bytes(12));
        } else {
            $connection = $this->ensureAccessToken($connection);
            $token = $this->decryptToken($connection['access_token'] ?? '');
            if ($token === '') {
                return false;
            }
        }

        if ($provider === 'google') {
            $boundary = 'hm_' . bin2hex(random_bytes(8));
            $raw = "From: " . $connection['email'] . "\r\n"
                . "To: {$to}\r\nSubject: {$subject}\r\nMIME-Version: 1.0\r\n"
                . "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n"
                . "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n"
                . trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)))
                . "\r\n--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n--{$boundary}--";
            $response = $this->http->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'json' => ['raw' => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=')],
            ]);
            $data = json_decode((string) $response->getBody(), true) ?: [];
            if ($response->getStatusCode() >= 300 || empty($data['id'])) {
                $this->recordError($connection, 'Google send failed: HTTP ' . $response->getStatusCode());
                return false;
            }
            $messageId = (string) $data['id'];
            $threadId = (string) ($data['threadId'] ?? '');
        } elseif ($provider === 'microsoft') {
            $response = $this->http->post('https://graph.microsoft.com/v1.0/me/sendMail', [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'json' => ['message' => [
                    'subject' => $subject,
                    'body' => ['contentType' => 'HTML', 'content' => $html],
                    'toRecipients' => [['emailAddress' => ['address' => $to]]],
                ], 'saveToSentItems' => true],
            ]);
            if ($response->getStatusCode() >= 300) {
                $this->recordError($connection, 'Microsoft send failed: HTTP ' . $response->getStatusCode());
                return false;
            }
            $messageId = 'sent-' . bin2hex(random_bytes(12));
        } elseif ($provider !== 'custom') {
            return false;
        }

        $this->activities->insert([
            'connection_id' => (int) $connection['id'],
            'recruiter_id' => $recruiterId,
            'candidate_id' => $context['candidate_id'] ?? null,
            'application_id' => $context['application_id'] ?? null,
            'job_id' => $context['job_id'] ?? null,
            'provider_message_id' => $messageId,
            'provider_thread_id' => $threadId ?: null,
            'direction' => 'outbound',
            'from_email' => (string) $connection['email'],
            'to_email' => $to,
            'subject' => $subject,
            'body_text' => trim(strip_tags($html)),
            'status' => 'sent',
            'occurred_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function syncConnection(array $connection): array
    {
        try {
            $this->reconcileInboundActivities($connection);
            $connection = $this->ensureAccessToken($connection);
            if ($connection['provider'] === 'google') {
                $count = $this->syncGoogle($connection);
            } elseif ($connection['provider'] === 'microsoft') {
                $count = $this->syncMicrosoft($connection);
            } elseif ($connection['provider'] === 'custom') {
                $count = $this->syncCustom($connection);
            } else {
                throw new \RuntimeException('Unsupported mailbox provider.');
            }
            $this->connections->update((int) $connection['id'], [
                'last_synced_at' => date('Y-m-d H:i:s'),
                'last_error' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return ['ok' => true, 'count' => $count];
        } catch (\Throwable $e) {
            $this->recordError($connection, $e->getMessage());
            return ['ok' => false, 'count' => 0, 'error' => $e->getMessage()];
        }
    }

    public function syncRecruiterIfStale(int $recruiterId, int $minimumAgeSeconds = 300): ?array
    {
        $connection = $this->connections->getConnectedForRecruiter($recruiterId);
        if (!$connection) {
            return null;
        }

        $lastSyncedAt = strtotime((string) ($connection['last_synced_at'] ?? '')) ?: 0;
        if ($lastSyncedAt > 0 && (time() - $lastSyncedAt) < $minimumAgeSeconds) {
            return ['ok' => true, 'count' => 0, 'skipped' => true];
        }

        return $this->syncConnection($connection);
    }

    public function ensureAccessToken(array $connection): array
    {
        if (($connection['provider'] ?? '') === 'custom') {
            return $connection;
        }
        $expiresAt = strtotime((string) ($connection['token_expires_at'] ?? '')) ?: 0;
        if ($expiresAt > time() + 120) {
            return $connection;
        }
        $refreshToken = $this->decryptToken($connection['refresh_token'] ?? '');
        if ($refreshToken === '') {
            throw new \RuntimeException('Mailbox authorization expired. Reconnect the mailbox.');
        }
        $config = config('RecruiterMailbox');
        if ($connection['provider'] === 'google') {
            $provider = $config->google;
            $url = $provider['token_url'];
            $form = ['client_id' => $provider['client_id'], 'client_secret' => $provider['client_secret'], 'refresh_token' => $refreshToken, 'grant_type' => 'refresh_token'];
        } else {
            $provider = $config->microsoft;
            $url = 'https://login.microsoftonline.com/' . rawurlencode($provider['tenant']) . '/oauth2/v2.0/token';
            $form = ['client_id' => $provider['client_id'], 'client_secret' => $provider['client_secret'], 'refresh_token' => $refreshToken, 'grant_type' => 'refresh_token', 'scope' => $provider['scopes']];
        }
        $response = $this->http->post($url, ['form_params' => $form]);
        $data = json_decode((string) $response->getBody(), true) ?: [];
        if ($response->getStatusCode() >= 300 || empty($data['access_token'])) {
            throw new \RuntimeException('Mailbox token refresh failed.');
        }
        $update = [
            'access_token' => $this->encryptToken((string) $data['access_token']),
            'token_expires_at' => date('Y-m-d H:i:s', time() + (int) ($data['expires_in'] ?? 3600)),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['refresh_token'])) {
            $update['refresh_token'] = $this->encryptToken((string) $data['refresh_token']);
        }
        $this->connections->update((int) $connection['id'], $update);
        return array_merge($connection, $update);
    }

    public function customSettings(array $connection): array
    {
        return [
            'imap_host' => (string) ($connection['imap_host'] ?? ''),
            'imap_port' => (int) ($connection['imap_port'] ?? 993),
            'imap_encryption' => (string) ($connection['imap_encryption'] ?? 'ssl'),
            'smtp_host' => (string) ($connection['smtp_host'] ?? ''),
            'smtp_port' => (int) ($connection['smtp_port'] ?? 465),
            'smtp_encryption' => (string) ($connection['smtp_encryption'] ?? 'ssl'),
            'username' => (string) ($connection['mailbox_username'] ?? ''),
            'password' => $this->decryptToken($connection['mailbox_password'] ?? ''),
        ];
    }

    private function sendCustom(array $connection, string $to, string $subject, string $html): bool
    {
        try {
            $settings = $this->customSettings($connection);
            $mailConfig = new \Config\Email();
            $mailConfig->protocol = 'smtp';
            $mailConfig->SMTPHost = $settings['smtp_host'];
            $mailConfig->SMTPUser = $settings['username'];
            $mailConfig->SMTPPass = $settings['password'];
            $mailConfig->SMTPPort = $settings['smtp_port'];
            $mailConfig->SMTPCrypto = $settings['smtp_encryption'] === 'none' ? '' : $settings['smtp_encryption'];
            $mailConfig->SMTPTimeout = 15;
            $mailConfig->mailType = 'html';
            $mailConfig->charset = 'UTF-8';
            $mailConfig->newline = "\r\n";
            $mailConfig->CRLF = "\r\n";
            $email = \Config\Services::email($mailConfig, false);
            $email->clear(true);
            $email->setFrom((string) $connection['email']);
            $email->setTo($to);
            $email->setSubject($subject);
            $email->setMessage($html);
            if (!$email->send(false)) {
                $this->recordError($connection, 'Custom SMTP send failed: ' . strip_tags($email->printDebugger(['headers'])));
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            $this->recordError($connection, 'Custom SMTP send failed: ' . $e->getMessage());
            return false;
        }
    }

    private function syncCustom(array $connection): int
    {
        $settings = $this->customSettings($connection);
        $since = $this->syncWindowStart($connection);
        $messages = (new CustomMailboxClient())->fetchInbox($settings, $since, 50);
        $count = 0;
        foreach ($messages as $message) {
            $from = (string) ($message['from'] ?? '');
            $to = (string) ($message['to'] ?? '');
            $inbound = strcasecmp($from, (string) $connection['email']) !== 0;
            if (!$inbound) {
                continue;
            }
            $threadId = (string) ($message['thread_id'] ?? '');
            $context = $this->matchThreadContext((int) $connection['id'], $threadId)
                ?: $this->matchSubjectContext((int) $connection['id'], $from, (string) ($message['subject'] ?? ''));
            if (!$context) {
                continue;
            }
            $messageId = (string) ($message['message_id'] ?? '');
            if ($messageId === '') {
                $messageId = 'imap-uid-' . (string) ($message['uid'] ?? bin2hex(random_bytes(8)));
            }
            $count += $this->storeSynced(
                $connection, $messageId, $threadId, true, $from, $to,
                (string) ($message['subject'] ?? ''), (string) ($message['body'] ?? ''),
                (int) ($message['timestamp'] ?? time()), $context
            );
        }
        return $count;
    }

    private function syncGoogle(array $connection): int
    {
        $token = $this->decryptToken($connection['access_token']);
        $after = $this->syncWindowStart($connection);
        $response = $this->http->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'query' => ['q' => 'after:' . $after, 'maxResults' => 50],
        ]);
        $rows = json_decode((string) $response->getBody(), true)['messages'] ?? [];
        $count = 0;
        foreach ($rows as $row) {
            $detail = $this->http->get('https://gmail.googleapis.com/gmail/v1/users/me/messages/' . rawurlencode((string) $row['id']), [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query' => ['format' => 'metadata', 'metadataHeaders' => ['From', 'To', 'Subject', 'Date']],
            ]);
            $message = json_decode((string) $detail->getBody(), true) ?: [];
            $headers = [];
            foreach (($message['payload']['headers'] ?? []) as $header) {
                $headers[strtolower((string) ($header['name'] ?? ''))] = (string) ($header['value'] ?? '');
            }
            $from = $this->extractEmail($headers['from'] ?? '');
            $to = $this->extractEmail($headers['to'] ?? '');
            $inbound = strcasecmp($from, (string) $connection['email']) !== 0;
            $counterpart = $inbound ? $from : $to;
            $threadId = (string) ($message['threadId'] ?? '');
            $context = $this->matchThreadContext((int) $connection['id'], $threadId)
                ?: $this->matchSubjectContext((int) $connection['id'], $counterpart, (string) ($headers['subject'] ?? ''));
            if (!$context) {
                continue;
            }
            $count += $this->storeSynced($connection, (string) $message['id'], $threadId, $inbound, $from, $to, $headers['subject'] ?? '', (string) ($message['snippet'] ?? ''), (int) (($message['internalDate'] ?? 0) / 1000), $context);
        }
        return $count;
    }

    private function syncMicrosoft(array $connection): int
    {
        $token = $this->decryptToken($connection['access_token']);
        $since = $this->syncWindowStart($connection);
        $response = $this->http->get('https://graph.microsoft.com/v1.0/me/messages', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'query' => ['$top' => 50, '$select' => 'id,conversationId,from,toRecipients,subject,bodyPreview,receivedDateTime,sentDateTime', '$filter' => 'receivedDateTime ge ' . gmdate('Y-m-d\TH:i:s\Z', $since)],
        ]);
        $rows = json_decode((string) $response->getBody(), true)['value'] ?? [];
        $count = 0;
        foreach ($rows as $row) {
            $from = strtolower((string) ($row['from']['emailAddress']['address'] ?? ''));
            $to = strtolower((string) ($row['toRecipients'][0]['emailAddress']['address'] ?? ''));
            $inbound = strcasecmp($from, (string) $connection['email']) !== 0;
            $threadId = (string) ($row['conversationId'] ?? '');
            $context = $this->matchThreadContext((int) $connection['id'], $threadId)
                ?: $this->matchSubjectContext((int) $connection['id'], $inbound ? $from : $to, (string) ($row['subject'] ?? ''));
            if (!$context) {
                continue;
            }
            $occurred = strtotime((string) ($inbound ? ($row['receivedDateTime'] ?? '') : ($row['sentDateTime'] ?? ''))) ?: time();
            $count += $this->storeSynced($connection, (string) $row['id'], $threadId, $inbound, $from, $to, (string) ($row['subject'] ?? ''), (string) ($row['bodyPreview'] ?? ''), $occurred, $context);
        }
        return $count;
    }

    private function storeSynced(array $connection, string $messageId, string $threadId, bool $inbound, string $from, string $to, string $subject, string $body, int $occurred, array $context): int
    {
        if ($messageId === '' || $this->activities->where('connection_id', (int) $connection['id'])->where('provider_message_id', $messageId)->first()) {
            return 0;
        }
        $this->activities->insert(array_merge($context, [
            'connection_id' => (int) $connection['id'], 'recruiter_id' => (int) $connection['recruiter_id'],
            'provider_message_id' => $messageId, 'provider_thread_id' => $threadId ?: null,
            'direction' => $inbound ? 'inbound' : 'outbound', 'from_email' => $from, 'to_email' => $to,
            'subject' => $subject, 'body_text' => $body, 'status' => 'synced',
            'occurred_at' => date('Y-m-d H:i:s', $occurred), 'created_at' => date('Y-m-d H:i:s'),
        ]));
        $activityId = (int) $this->activities->getInsertID();
        if ($inbound && $activityId > 0) {
            $notified = $this->createRecruiterNotification($context, [
                'id' => $activityId,
                'subject' => $subject,
                'body_text' => $body,
                'occurred_at' => date('Y-m-d H:i:s', $occurred),
            ], $connection);
            if ($notified) {
                $this->activities->update($activityId, ['notified_at' => date('Y-m-d H:i:s')]);
            }
        }
        return 1;
    }

    private function matchCandidateContext(int $recruiterId, string $email): ?array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $db = \Config\Database::connect();
        $row = $db->table('users')
            ->select('users.id candidate_id, applications.id application_id, applications.job_id')
            ->join('applications', 'applications.candidate_id = users.id', 'left')
            ->join('jobs', 'jobs.id = applications.job_id AND jobs.recruiter_id = ' . $recruiterId, 'left', false)
            ->where('LOWER(users.email)', strtolower($email))
            ->where('users.role', 'candidate')
            ->where('jobs.id IS NOT NULL', null, false)
            ->orderBy('applications.applied_at', 'DESC')
            ->get()->getRowArray();
        if (!$row || empty($row['application_id'])) {
            return null;
        }
        return ['candidate_id' => (int) $row['candidate_id'], 'application_id' => (int) $row['application_id'], 'job_id' => (int) $row['job_id']];
    }

    private function matchThreadContext(int $connectionId, string $threadId): ?array
    {
        if ($threadId === '') {
            return null;
        }
        $row = $this->activities->where('connection_id', $connectionId)
            ->where('provider_thread_id', $threadId)
            ->where('direction', 'outbound')
            ->where('candidate_id IS NOT NULL', null, false)
            ->orderBy('id', 'DESC')
            ->first();
        if (!$row) {
            return null;
        }
        return [
            'candidate_id' => (int) $row['candidate_id'],
            'application_id' => !empty($row['application_id']) ? (int) $row['application_id'] : null,
            'job_id' => !empty($row['job_id']) ? (int) $row['job_id'] : null,
        ];
    }

    private function matchSubjectContext(int $connectionId, string $counterpartEmail, string $subject): ?array
    {
        $normalizedSubject = $this->normalizeSubject($subject);
        if ($normalizedSubject === '' || !filter_var($counterpartEmail, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $rows = $this->activities->where('connection_id', $connectionId)
            ->where('direction', 'outbound')
            ->where('LOWER(to_email)', strtolower($counterpartEmail))
            ->where('candidate_id IS NOT NULL', null, false)
            ->orderBy('occurred_at', 'DESC')
            ->findAll(100);
        foreach ($rows as $row) {
            if ($this->normalizeSubject((string) ($row['subject'] ?? '')) === $normalizedSubject) {
                return [
                    'candidate_id' => (int) $row['candidate_id'],
                    'application_id' => !empty($row['application_id']) ? (int) $row['application_id'] : null,
                    'job_id' => !empty($row['job_id']) ? (int) $row['job_id'] : null,
                ];
            }
        }
        return null;
    }

    private function normalizeSubject(string $subject): string
    {
        $decoded = function_exists('iconv_mime_decode')
            ? @iconv_mime_decode($subject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8')
            : $subject;
        $decoded = is_string($decoded) ? $decoded : $subject;
        do {
            $before = $decoded;
            $decoded = preg_replace('/^\s*(re|fw|fwd)\s*:\s*/i', '', $decoded) ?? $decoded;
        } while ($decoded !== $before);
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $decoded) ?? $decoded));
    }

    private function syncWindowStart(array $connection): int
    {
        $lastSyncedAt = strtotime((string) ($connection['last_synced_at'] ?? '')) ?: 0;
        if ($lastSyncedAt <= 0) {
            return time() - self::DEFAULT_SYNC_LOOKBACK_SECONDS;
        }

        return max(0, $lastSyncedAt - self::RECENT_SYNC_LOOKBACK_SECONDS);
    }

    private function reconcileInboundActivities(array $connection): void
    {
        $rows = $this->activities->where('connection_id', (int) $connection['id'])
            ->where('direction', 'inbound')
            ->orderBy('occurred_at', 'DESC')
            ->findAll(200);
        foreach ($rows as $row) {
            $context = $this->matchThreadContext((int) $connection['id'], (string) ($row['provider_thread_id'] ?? ''))
                ?: $this->matchSubjectContext((int) $connection['id'], (string) ($row['from_email'] ?? ''), (string) ($row['subject'] ?? ''));
            if (!$context) {
                continue;
            }
            $this->activities->update((int) $row['id'], $context);
            if (empty($row['notified_at']) || !$this->hasExistingReplyNotification($context, $row, $connection)) {
                if ($this->createRecruiterNotification($context, $row, $connection)) {
                    $this->activities->update((int) $row['id'], ['notified_at' => date('Y-m-d H:i:s')]);
                }
            }
        }
    }

    private function createRecruiterNotification(array $context, array $activity, array $connection): bool
    {
        $candidateId = (int) ($context['candidate_id'] ?? 0);
        if ($candidateId <= 0) {
            return false;
        }
        $candidate = model('UserModel')->find($candidateId) ?? [];
        $candidateName = trim((string) ($candidate['name'] ?? 'Candidate')) ?: 'Candidate';
        $applicationId = !empty($context['application_id']) ? (int) $context['application_id'] : null;
        $jobId = !empty($context['job_id']) ? (int) $context['job_id'] : null;
        $link = base_url('recruiter/candidate/' . $candidateId)
            . '?application_id=' . (int) ($applicationId ?? 0)
            . '&job_id=' . (int) ($jobId ?? 0)
            . (!empty($activity['id']) ? '&email_activity_id=' . (int) $activity['id'] : '');
        $notificationModel = new \App\Models\NotificationModel();
        $title = 'Email reply from ' . $candidateName;
        $message = trim((string) ($activity['subject'] ?? '')) !== '' ? (string) $activity['subject'] : 'A candidate replied to your email.';

        if ($this->hasExistingReplyNotification($context, array_merge($activity, [
            'title' => $title,
            'message' => $message,
            'action_link' => $link,
        ]), $connection)) {
            return true;
        }

        $notificationId = $notificationModel->insert([
            'user_id' => (int) $connection['recruiter_id'],
            'application_id' => $applicationId,
            'type' => 'candidate_email_reply',
            'title' => $title,
            'message' => $message,
            'action_link' => $link,
            'is_read' => 0,
            'created_at' => (string) ($activity['occurred_at'] ?? date('Y-m-d H:i:s')),
        ]);
        return $notificationId !== false;
    }

    private function hasExistingReplyNotification(array $context, array $activity, array $connection): bool
    {
        $candidateId = (int) ($context['candidate_id'] ?? 0);
        if ($candidateId <= 0) {
            return false;
        }

        $candidate = model('UserModel')->find($candidateId) ?? [];
        $candidateName = trim((string) ($candidate['name'] ?? 'Candidate')) ?: 'Candidate';
        $applicationId = !empty($context['application_id']) ? (int) $context['application_id'] : null;
        $jobId = !empty($context['job_id']) ? (int) $context['job_id'] : null;
        $link = (string) ($activity['action_link'] ?? (
            base_url('recruiter/candidate/' . $candidateId)
            . '?application_id=' . (int) ($applicationId ?? 0)
            . '&job_id=' . (int) ($jobId ?? 0)
            . (!empty($activity['id']) ? '&email_activity_id=' . (int) $activity['id'] : '')
        ));
        $message = (string) ($activity['message'] ?? (
            trim((string) ($activity['subject'] ?? '')) !== '' ? (string) $activity['subject'] : 'A candidate replied to your email.'
        ));

        $title = (string) ($activity['title'] ?? ('Email reply from ' . $candidateName));
        $notificationModel = new \App\Models\NotificationModel();
        $builder = $notificationModel
            ->where('user_id', (int) $connection['recruiter_id'])
            ->where('type', 'candidate_email_reply')
            ->where('title', $title)
            ->where('message', $message)
            ->where('action_link', $link);

        if ($applicationId !== null) {
            $builder->where('application_id', $applicationId);
        } else {
            $builder->where('application_id IS NULL', null, false);
        }

        if ($builder->first()) {
            return true;
        }

        if (empty($activity['id']) || empty($activity['occurred_at'])) {
            return false;
        }

        $legacyLink = base_url('recruiter/candidate/' . $candidateId)
            . '?application_id=' . (int) ($applicationId ?? 0)
            . '&job_id=' . (int) ($jobId ?? 0);
        $legacyBuilder = (new \App\Models\NotificationModel())
            ->where('user_id', (int) $connection['recruiter_id'])
            ->where('type', 'candidate_email_reply')
            ->where('title', $title)
            ->where('message', $message)
            ->where('action_link', $legacyLink)
            ->where('created_at', (string) $activity['occurred_at']);

        if ($applicationId !== null) {
            $legacyBuilder->where('application_id', $applicationId);
        } else {
            $legacyBuilder->where('application_id IS NULL', null, false);
        }

        return (bool) $legacyBuilder->first();
    }

    private function extractEmail(string $value): string
    {
        if (preg_match('/<([^>]+)>/', $value, $matches)) {
            return strtolower(trim($matches[1]));
        }
        return strtolower(trim(explode(',', $value)[0] ?? ''));
    }

    private function recordError(array $connection, string $message): void
    {
        if (!empty($connection['id'])) {
            $this->connections->update((int) $connection['id'], ['last_error' => mb_substr($message, 0, 2000), 'updated_at' => date('Y-m-d H:i:s')]);
        }
        log_message('error', 'Recruiter mailbox: ' . $message);
    }
}
