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

    public function disconnect()
    {
        \Config\Database::connect()->table('recruiter_mailbox_connections')
            ->where('recruiter_id', (int) session()->get('user_id'))
            ->update(['status' => 'disconnected', 'access_token' => '', 'refresh_token' => null, 'updated_at' => date('Y-m-d H:i:s')]);
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
        $message = !empty($result['ok']) ? ((int) $result['count'] . ' candidate email(s) synchronized.') : ('Synchronization failed: ' . ($result['error'] ?? 'Unknown error'));
        return redirect()->to(base_url('recruiter/settings?tab=mailbox'))->with($type, $message);
    }

    private function verifiedEmail(array $recruiter): string
    {
        return strtolower(trim((string) ($recruiter['official_email'] ?? $recruiter['email'] ?? '')));
    }
}
