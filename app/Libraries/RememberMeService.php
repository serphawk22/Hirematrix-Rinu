<?php

namespace App\Libraries;

use App\Models\UserModel;

class RememberMeService
{
    private const COOKIE_NAME = 'remember_token';
    private const TOKEN_DAYS = 30;

    public function issueForUser(array $user): void
    {
        if (!$this->isStorageReady()) {
            return;
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        helper('cookie');
        $this->clearForUser($userId);

        $selector = bin2hex(random_bytes(8));
        $validator = random_bytes(32);
        $validatorHex = bin2hex($validator);
        $tokenHash = hash('sha256', $validatorHex);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_DAYS . ' days'));
        $now = date('Y-m-d H:i:s');

        $db = \Config\Database::connect();
        $db->table('remember_login_tokens')->insert([
            'user_id' => $userId,
            'selector' => $selector,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'last_used_at' => $now,
            'ip_address' => (string) service('request')->getIPAddress(),
            'user_agent' => substr((string) service('request')->getUserAgent()->getAgentString(), 0, 255),
        ]);

        set_cookie([
            'name' => self::COOKIE_NAME,
            'value' => $selector . ':' . $validatorHex,
            'expire' => self::TOKEN_DAYS * DAY,
            'httponly' => true,
            'secure' => (bool) service('request')->isSecure(),
            'path' => '/',
        ]);
    }

    public function clearCurrentToken(): void
    {
        if (!$this->isStorageReady()) {
            return;
        }

        helper('cookie');
        $cookieValue = (string) get_cookie(self::COOKIE_NAME);
        if ($cookieValue !== '') {
            [$selector] = explode(':', $cookieValue, 2);
            if ($selector !== '') {
                \Config\Database::connect()
                    ->table('remember_login_tokens')
                    ->where('selector', $selector)
                    ->delete();
            }
        }

        delete_cookie(self::COOKIE_NAME);
    }

    public function attemptAutoLogin(): bool
    {
        if (session()->get('logged_in')) {
            return true;
        }

        if (!$this->isStorageReady()) {
            return false;
        }

        helper('cookie');
        $cookieValue = (string) get_cookie(self::COOKIE_NAME);
        if ($cookieValue === '' || !str_contains($cookieValue, ':')) {
            return false;
        }

        [$selector, $validatorHex] = explode(':', $cookieValue, 2);
        $selector = trim((string) $selector);
        $validatorHex = trim((string) $validatorHex);

        if ($selector === '' || $validatorHex === '' || !ctype_xdigit($validatorHex)) {
            $this->clearCurrentToken();
            return false;
        }

        $db = \Config\Database::connect();
        $row = $db->table('remember_login_tokens')
            ->where('selector', $selector)
            ->get()
            ->getRowArray();

        if (empty($row)) {
            $this->clearCurrentToken();
            return false;
        }

        if (strtotime((string) ($row['expires_at'] ?? '')) < time()) {
            $db->table('remember_login_tokens')->where('id', (int) $row['id'])->delete();
            $this->clearCurrentToken();
            return false;
        }

        $incomingHash = hash('sha256', $validatorHex);
        if (!hash_equals((string) ($row['token_hash'] ?? ''), $incomingHash)) {
            $db->table('remember_login_tokens')->where('id', (int) $row['id'])->delete();
            $this->clearCurrentToken();
            return false;
        }

        $user = (new UserModel())->find((int) ($row['user_id'] ?? 0));
        if (empty($user)) {
            $db->table('remember_login_tokens')->where('id', (int) $row['id'])->delete();
            $this->clearCurrentToken();
            return false;
        }

        $session = session();
        $profilePhoto = '';
        if (($user['role'] ?? '') === 'candidate') {
            $candidateRecord = (new UserModel())->findCandidateWithProfile((int) $user['id']);
            $profilePhoto = (string) ($candidateRecord['profile_photo'] ?? '');
        }
        $session->regenerate();
        $session->set([
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'user_email' => $user['email'] ?? '',
            'role' => $user['role'],
            'profile_photo' => $profilePhoto,
            'logged_in' => true,
            'login_perf_pending' => 1,
            'login_started_at_ms' => (int) round(microtime(true) * 1000),
            'login_at' => date('Y-m-d H:i:s'),
        ]);

        // Rotate token on every successful cookie login.
        $db->table('remember_login_tokens')->where('id', (int) $row['id'])->delete();
        $this->issueForUser($user);

        return true;
    }

    private function clearForUser(int $userId): void
    {
        \Config\Database::connect()
            ->table('remember_login_tokens')
            ->where('user_id', $userId)
            ->delete();
    }

    private function isStorageReady(): bool
    {
        return \Config\Database::connect()->tableExists('remember_login_tokens');
    }
}
