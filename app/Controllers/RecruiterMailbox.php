<?php

namespace App\Controllers;

use App\Libraries\RecruiterMailboxService;
use App\Models\RecruiterMailboxConnectionModel;
use GuzzleHttp\Client;

class RecruiterMailbox extends BaseController
{
    private const PROVIDERS = ['google', 'microsoft'];

    public function connect(string $provider)
    {
        if (!in_array($provider, self::PROVIDERS, true)) {
            return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('error', 'Unsupported mailbox provider.');
        }
        $recruiter = model('UserModel')->findRecruiterWithProfile((int) session()->get('user_id'));
        if (!$recruiter || empty($recruiter['email_verified_at'])) {
            return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('error', 'Verify your company email before connecting a mailbox.');
        }
        $providerConfig = config('RecruiterMailbox')->{$provider};
        if (empty($providerConfig['client_id']) || empty($providerConfig['client_secret'])) {
            return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('error', ucfirst($provider) . ' OAuth is not configured yet.');
        }
        $state = bin2hex(random_bytes(24));
        session()->set('mailbox_oauth', ['state' => $state, 'provider' => $provider, 'created_at' => time()]);
        $redirectUri = base_url('recruiter/mailbox/callback/' . $provider);
        if ($provider === 'google') {
            $url = $providerConfig['authorize_url'] . '?' . http_build_query([
                'client_id' => $providerConfig['client_id'], 'redirect_uri' => $redirectUri,
                'response_type' => 'code', 'scope' => $providerConfig['scopes'], 'state' => $state,
                'access_type' => 'offline', 'prompt' => 'consent', 'login_hint' => $this->verifiedEmail($recruiter),
            ]);
        } else {
            $url = 'https://login.microsoftonline.com/' . rawurlencode($providerConfig['tenant']) . '/oauth2/v2.0/authorize?' . http_build_query([
                'client_id' => $providerConfig['client_id'], 'redirect_uri' => $redirectUri,
                'response_type' => 'code', 'response_mode' => 'query', 'scope' => $providerConfig['scopes'], 'state' => $state,
                'login_hint' => $this->verifiedEmail($recruiter),
            ]);
        }
        return redirect()->to($url);
    }

    public function callback(string $provider)
    {
        $oauth = (array) session()->get('mailbox_oauth');
        session()->remove('mailbox_oauth');
        if (!in_array($provider, self::PROVIDERS, true) || ($oauth['provider'] ?? '') !== $provider || empty($oauth['state']) || !hash_equals((string) $oauth['state'], (string) $this->request->getGet('state')) || time() - (int) ($oauth['created_at'] ?? 0) > 900) {
            return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('error', 'Mailbox authorization expired or was invalid.');
        }
        $code = trim((string) $this->request->getGet('code'));
        if ($code === '') {
            return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('error', 'Mailbox authorization was cancelled.');
        }
        $config = config('RecruiterMailbox')->{$provider};
        $redirectUri = base_url('recruiter/mailbox/callback/' . $provider);
        $http = new Client(['timeout' => 15, 'http_errors' => false]);
        $tokenUrl = $provider === 'google' ? $config['token_url'] : 'https://login.microsoftonline.com/' . rawurlencode($config['tenant']) . '/oauth2/v2.0/token';
        $response = $http->post($tokenUrl, ['form_params' => [
            'client_id' => $config['client_id'], 'client_secret' => $config['client_secret'],
            'code' => $code, 'redirect_uri' => $redirectUri, 'grant_type' => 'authorization_code',
            'scope' => $provider === 'microsoft' ? $config['scopes'] : null,
        ]]);
        $tokens = json_decode((string) $response->getBody(), true) ?: [];
        if ($response->getStatusCode() >= 300 || empty($tokens['access_token'])) {
            return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('error', 'Could not connect the mailbox. Check the OAuth configuration.');
        }
        $headers = ['Authorization' => 'Bearer ' . $tokens['access_token']];
        $profileResponse = $http->get($provider === 'google' ? $config['profile_url'] : 'https://graph.microsoft.com/v1.0/me?$select=mail,userPrincipalName', ['headers' => $headers]);
        $profile = json_decode((string) $profileResponse->getBody(), true) ?: [];
        $mailboxEmail = strtolower(trim((string) ($provider === 'google' ? ($profile['email'] ?? '') : ($profile['mail'] ?? $profile['userPrincipalName'] ?? ''))));
        $recruiter = model('UserModel')->findRecruiterWithProfile((int) session()->get('user_id'));
        if (!$recruiter || $mailboxEmail === '' || strcasecmp($mailboxEmail, $this->verifiedEmail($recruiter)) !== 0) {
            return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('error', 'Connect the same verified company email used by your recruiter account.');
        }
        $service = new RecruiterMailboxService();
        $model = new RecruiterMailboxConnectionModel();
        $recruiterId = (int) session()->get('user_id');
        \Config\Database::connect()->table('recruiter_mailbox_connections')
            ->where('recruiter_id', $recruiterId)
            ->update(['status' => 'disconnected', 'updated_at' => date('Y-m-d H:i:s')]);
        $existing = $model->where('recruiter_id', $recruiterId)->where('provider', $provider)->first();
        $payload = [
            'recruiter_id' => $recruiterId, 'provider' => $provider, 'email' => $mailboxEmail,
            'access_token' => $service->encryptToken((string) $tokens['access_token']),
            'refresh_token' => !empty($tokens['refresh_token']) ? $service->encryptToken((string) $tokens['refresh_token']) : ($existing['refresh_token'] ?? null),
            'token_expires_at' => date('Y-m-d H:i:s', time() + (int) ($tokens['expires_in'] ?? 3600)),
            'scopes' => (string) ($tokens['scope'] ?? $config['scopes']), 'status' => 'connected', 'last_error' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($existing) {
            $model->update((int) $existing['id'], $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $model->insert($payload);
        }
        return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('success', 'Company mailbox connected successfully.');
    }

    public function connectCustom()
    {
        $recruiterId = (int) session()->get('user_id');
        $recruiter = model('UserModel')->findRecruiterWithProfile($recruiterId);
        if (!$recruiter || empty($recruiter['email_verified_at'])) {
            return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('error', 'Verify your company email before connecting a mailbox.');
        }

        $verifiedEmail = $this->verifiedEmail($recruiter);
        $username = strtolower(trim((string) $this->request->getPost('mailbox_username')));
        $password = (string) $this->request->getPost('mailbox_password');
        $settings = [
            'imap_host' => strtolower(trim((string) $this->request->getPost('imap_host'))),
            'imap_port' => (int) $this->request->getPost('imap_port'),
            'imap_encryption' => strtolower(trim((string) $this->request->getPost('imap_encryption'))),
            'smtp_host' => strtolower(trim((string) $this->request->getPost('smtp_host'))),
            'smtp_port' => (int) $this->request->getPost('smtp_port'),
            'smtp_encryption' => strtolower(trim((string) $this->request->getPost('smtp_encryption'))),
            'username' => $username,
            'password' => $password,
        ];

        if ($username !== $verifiedEmail) {
            return redirect()->back()->withInput()->with('error', 'The mailbox username must be your verified company email.');
        }
        if ($password === '') {
            return redirect()->back()->withInput()->with('error', 'Mailbox password or app password is required.');
        }
        if (!in_array($settings['imap_port'], [143, 993], true) || !in_array($settings['smtp_port'], [465, 587], true)) {
            return redirect()->back()->withInput()->with('error', 'Use IMAP port 143/993 and SMTP port 465/587.');
        }
        if (!in_array($settings['imap_encryption'], ['ssl', 'tls'], true) || !in_array($settings['smtp_encryption'], ['ssl', 'tls'], true)) {
            return redirect()->back()->withInput()->with('error', 'Encrypted SSL/TLS connections are required.');
        }
        foreach ([$settings['imap_host'], $settings['smtp_host']] as $host) {
            $hostError = $this->validateMailHost($host);
            if ($hostError !== null) {
                return redirect()->back()->withInput()->with('error', $hostError);
            }
        }

        try {
            (new \App\Libraries\CustomMailboxClient())->test($settings);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Mailbox connection failed: ' . $e->getMessage());
        }

        $service = new RecruiterMailboxService();
        $model = new RecruiterMailboxConnectionModel();
        \Config\Database::connect()->table('recruiter_mailbox_connections')
            ->where('recruiter_id', $recruiterId)
            ->update(['status' => 'disconnected', 'updated_at' => date('Y-m-d H:i:s')]);
        $existing = $model->where('recruiter_id', $recruiterId)->where('provider', 'custom')->first();
        $payload = [
            'recruiter_id' => $recruiterId,
            'provider' => 'custom',
            'email' => $verifiedEmail,
            'imap_host' => $settings['imap_host'],
            'imap_port' => $settings['imap_port'],
            'imap_encryption' => $settings['imap_encryption'],
            'smtp_host' => $settings['smtp_host'],
            'smtp_port' => $settings['smtp_port'],
            'smtp_encryption' => $settings['smtp_encryption'],
            'mailbox_username' => $username,
            'mailbox_password' => $service->encryptToken($password),
            'access_token' => '',
            'refresh_token' => null,
            'token_expires_at' => null,
            'scopes' => 'imap smtp',
            'status' => 'connected',
            'last_error' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($existing) {
            $model->update((int) $existing['id'], $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $model->insert($payload);
        }

        return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('success', 'Company IMAP/SMTP mailbox connected successfully.');
    }

    public function disconnect()
    {
        $payload = ['status' => 'disconnected', 'access_token' => '', 'refresh_token' => null, 'updated_at' => date('Y-m-d H:i:s')];
        if (\Config\Database::connect()->fieldExists('mailbox_password', 'recruiter_mailbox_connections')) {
            $payload['mailbox_password'] = null;
        }
        \Config\Database::connect()->table('recruiter_mailbox_connections')
            ->where('recruiter_id', (int) session()->get('user_id'))
            ->update($payload);
        return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('success', 'Mailbox disconnected.');
    }

    public function sync()
    {
        $connection = (new RecruiterMailboxConnectionModel())->getConnectedForRecruiter((int) session()->get('user_id'));
        if (!$connection) {
            return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with('error', 'Connect a mailbox first.');
        }
        $result = (new RecruiterMailboxService())->syncConnection($connection);
        $type = !empty($result['ok']) ? 'success' : 'error';
        $message = !empty($result['ok']) ? ((int) $result['count'] . ' new candidate email(s) imported. Previously imported emails remain saved.') : ('Synchronization failed: ' . ($result['error'] ?? 'Unknown error'));
        return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with($type, $message);
    }

    public function poll()
    {
        $recruiterId = (int) session()->get('user_id');
        if ($recruiterId <= 0 || session()->get('role') !== 'recruiter') {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'error' => 'Unauthorized',
            ]);
        }

        try {
            $result = (new RecruiterMailboxService())->syncRecruiterIfStale($recruiterId, 60);
            $unreadCount = (int) model('NotificationModel')->getUnreadCount($recruiterId);

            return $this->response->setJSON([
                'success' => $result === null || !empty($result['ok']),
                'connected' => $result !== null,
                'skipped' => !empty($result['skipped']),
                'imported' => (int) ($result['count'] ?? 0),
                'unread_count' => $unreadCount,
                'error' => !empty($result['ok']) || $result === null ? null : (string) ($result['error'] ?? 'Sync failed'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Recruiter mailbox browser poll failed: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'Mailbox poll failed',
            ]);
        }
    }

    private function verifiedEmail(array $recruiter): string
    {
        return strtolower(trim((string) ($recruiter['official_email'] ?? $recruiter['email'] ?? '')));
    }

    private function validateMailHost(string $host): ?string
    {
        if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return 'Enter a mail-server hostname, not localhost or a raw IP address.';
        }
        if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return 'Enter a valid mail-server hostname, for example mail.example.com.';
        }

        $addresses = gethostbynamel($host) ?: [];
        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];
            foreach ($records as $record) {
                if (!empty($record['ip'])) {
                    $addresses[] = (string) $record['ip'];
                }
                if (!empty($record['ipv6'])) {
                    $addresses[] = (string) $record['ipv6'];
                }
            }
        }
        $addresses = array_values(array_unique(array_filter($addresses)));
        if ($addresses === []) {
            return 'The deployed server cannot resolve ' . $host . '. Please fix DNS for this hostname or ask hosting support to enable DNS resolution.';
        }
        foreach ($addresses as $address) {
            if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return 'The hostname ' . $host . ' resolves to a private/reserved IP address. Use a public mail-server hostname.';
            }
        }
        return null;
    }
}
