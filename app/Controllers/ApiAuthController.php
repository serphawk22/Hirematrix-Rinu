<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class ApiAuthController extends ResourceController
{
    protected $format = 'json';

    public function login()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]'
        ];

        $json = $this->request->getJSON();
        if ($json) {
            $data = (array) $json;
            if (!$this->validateData($data, $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
        } else {
            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
        }

        $json = $this->request->getJSON();
        if ($json) {
            $email = $json->email ?? '';
            $password = $json->password ?? '';
        } else {
            $email = $this->request->getVar('email');
            $password = $this->request->getVar('password');
        }

        $model = new UserModel();
        $user  = $model->where('email', $email)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Invalid email or password');
        }

        $profilePhoto = '';
        if (($user['role'] ?? '') === 'candidate') {
            $candidateRecord = $model->findCandidateWithProfile((int) $user['id']);
            $profilePhoto = (string) ($candidateRecord['profile_photo'] ?? '');
        }

        // Generate a simple API token if you don't have one, or just return user details.
        // For standard session-based or token-based, we can just return the user data.
        return $this->respond([
            'status'  => 'success',
            'message' => 'Login successful',
            'data'    => [
                'user' => [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'] ?? '',
                    'role'  => $user['role'],
                    'profile_photo' => $profilePhoto,
                    'onboarding_completed' => (int)($user['onboarding_completed'] ?? 0),
                    'onboarding_step' => $user['onboarding_step'] ?? 'personal',
                ],
            ]
        ]);
    }

    public function googleLogin()
    {
        $rules = [
            'google_id' => 'required',
            'email'     => 'required|valid_email',
        ];

        $json = $this->request->getJSON();
        if ($json) {
            $data = (array) $json;
            if (!$this->validateData($data, $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
            $googleId = trim((string)($json->google_id ?? ''));
            $email = strtolower(trim((string)($json->email ?? '')));
            $name = trim((string)($json->name ?? ''));
            $picture = trim((string)($json->picture ?? ''));
        } else {
            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
            $googleId = trim((string)$this->request->getVar('google_id'));
            $email = strtolower(trim((string)$this->request->getVar('email')));
            $name = trim((string)$this->request->getVar('name'));
            $picture = trim((string)$this->request->getVar('picture'));
        }

        $model = new UserModel();

        $user = $model
            ->groupStart()
                ->where('google_id', $googleId)
                ->orWhere('email', $email)
            ->groupEnd()
            ->first();

        if ($user) {
            if (($user['role'] ?? '') !== 'candidate') {
                return $this->failUnauthorized('This Google account is linked to a recruiter account.');
            }

            $updates = [];
            if (empty($user['google_id'])) {
                $updates['google_id'] = $googleId;
            }
            if ($name !== '' && (empty($user['name']) || (string) ($user['google_id'] ?? '') === $googleId)) {
                $updates['name'] = $name;
            }
            if (!empty($updates)) {
                $model->update((int) $user['id'], $updates);
                $user = $model->find((int) $user['id']) ?? $user;
            }
        } else {
            $insertData = [
                'name' => $name !== '' ? $name : 'Google User',
                'email' => $email,
                'phone' => '',
                'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'role' => 'candidate',
                'google_id' => $googleId,
                'onboarding_completed' => 0,
                'onboarding_step' => 'personal',
            ];

            $model->insert($insertData);
            $newId = (int) $model->getInsertID();
            $user = $model->find($newId);

            if (!$user) {
                return $this->fail('Failed to create candidate account from Google profile.');
            }
        }

        $candidateRecord = $model->findCandidateWithProfile((int) $user['id']) ?? [];
        $profilePhoto = (string) ($candidateRecord['profile_photo'] ?? '');

        $isGoogleHosted = str_contains(strtolower($profilePhoto), 'googleusercontent.com') || str_contains(strtolower($profilePhoto), 'googleapis.com');
        if ($picture !== '' && ($profilePhoto === '' || $isGoogleHosted)) {
            $model->upsertCandidateProfile((int) $user['id'], ['profile_photo' => $picture]);
            $profilePhoto = $picture;
        }

        return $this->respond([
            'status'  => 'success',
            'message' => 'Login successful',
            'data'    => [
                'user' => [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'] ?? '',
                    'role'  => $user['role'],
                    'profile_photo' => $profilePhoto,
                    'onboarding_completed' => (int)($user['onboarding_completed'] ?? 0),
                    'onboarding_step' => $user['onboarding_step'] ?? 'personal',
                ],
            ]
        ]);
    }

    public function register()
    {
        $rules = [
            'name'             => 'required|min_length[3]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'phone'            => 'required|numeric|min_length[10]',
            'password'         => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]'
        ];

        $json = $this->request->getJSON();
        if ($json) {
            // For JSON requests, manually feed data into validation
            $data = (array) $json;
            if (!$this->validateData($data, $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
            $name = $json->name ?? '';
            $email = $json->email ?? '';
            $phone = $json->phone ?? '';
            $password = $json->password ?? '';
        } else {
            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
            $name = $this->request->getVar('name');
            $email = $this->request->getVar('email');
            $phone = $this->request->getVar('phone');
            $password = $this->request->getVar('password');
        }

        $model = new UserModel();
        $model->insert([
            'name'                 => $name,
            'email'                => $email,
            'phone'                => $phone,
            'password'             => password_hash($password, PASSWORD_DEFAULT),
            'role'                 => 'candidate',
            'onboarding_completed' => 0,
            'onboarding_step'      => 'personal',
        ]);

        $userId = $model->getInsertID();

        return $this->respondCreated([
            'status'  => 'success',
            'message' => 'Candidate registered successfully',
            'data'    => [
                'user_id' => $userId
            ]
        ]);
    }

    public function changePassword()
    {
        $rules = [
            'user_id'          => 'required',
            'current_password' => 'required',
            'new_password'     => 'required|min_length[6]',
            'confirm_password' => 'required|matches[new_password]'
        ];

        $json = $this->request->getJSON();
        if ($json) {
            $data = (array) $json;
            if (!$this->validateData($data, $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
            $userId = (int) $json->user_id;
            $currentPassword = $json->current_password ?? '';
            $newPassword = $json->new_password ?? '';
        } else {
            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
            $userId = (int) $this->request->getVar('user_id');
            $currentPassword = $this->request->getVar('current_password');
            $newPassword = $this->request->getVar('new_password');
        }

        $model = new UserModel();
        $user = $model->find($userId);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return $this->failUnauthorized('Incorrect current password');
        }

        $model->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);

        return $this->respond([
            'status'  => 'success',
            'message' => 'Password updated successfully'
        ]);
    }

    public function forgotPassword()
    {
        $rules = [
            'email' => 'required|valid_email'
        ];

        $json = $this->request->getJSON();
        if ($json) {
            $data = (array) $json;
            if (!$this->validateData($data, $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
            $email = strtolower(trim((string)($json->email ?? '')));
        } else {
            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
            $email = strtolower(trim((string)$this->request->getVar('email')));
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if ($user) {
            $token = (string) random_int(100000, 999999);
            $userModel->update((int) $user['id'], [
                'password_reset_token' => $token,
                'password_reset_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            ]);

            $updatedUser = $userModel->find((int) $user['id']);
            if ($updatedUser) {
                $this->sendPasswordResetEmail($updatedUser);
            }
        }

        return $this->respond([
            'status'  => 'success',
            'message' => 'If that email exists in our system, a password reset link has been sent.'
        ]);
    }

    public function resetPassword()
    {
        $rules = [
            'email'            => 'required|valid_email',
            'token'            => 'required',
            'password'         => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]'
        ];

        $json = $this->request->getJSON();
        if ($json) {
            $data = (array) $json;
            if (!$this->validateData($data, $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
            $email = strtolower(trim((string)($json->email ?? '')));
            $token = trim((string)($json->token ?? ''));
            $password = (string)($json->password ?? '');
        } else {
            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }
            $email = strtolower(trim((string)$this->request->getVar('email')));
            $token = trim((string)$this->request->getVar('token'));
            $password = (string)$this->request->getVar('password');
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)
                          ->where('password_reset_token', $token)
                          ->first();

        if (!$user) {
            return $this->fail('Invalid email or reset code.');
        }

        $expiresAt = (string) ($user['password_reset_expires_at'] ?? '');
        if ($expiresAt === '' || strtotime($expiresAt) < time()) {
            return $this->fail('The reset code has expired. Please request a new one.');
        }

        $userModel->update((int) $user['id'], [
            'password'                  => password_hash($password, PASSWORD_DEFAULT),
            'password_reset_token'      => null,
            'password_reset_expires_at' => null,
        ]);

        return $this->respond([
            'status'  => 'success',
            'message' => 'Password reset successfully'
        ]);
    }

    private function sendPasswordResetEmail(array $user): bool
    {
        if (empty($user['email']) || empty($user['password_reset_token'])) {
            return false;
        }

        $resetUrl = base_url('reset-password/' . $user['password_reset_token']);
        $subject = 'Reset your HireMatrix password';

        $name = esc($user['name'] ?? 'User');
        $code = esc($user['password_reset_token']);

        $body = '
            <div style="margin:0;padding:24px;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;color:#0f172a;">
                <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 25px rgba(0,0,0,0.05);">
                    <div style="padding:32px;background:#0f172a;color:#ffffff;text-align:center;">
                        <h1 style="margin:0;font-size:24px;font-weight:700;">Password Reset Request</h1>
                    </div>
                    <div style="padding:40px 32px;text-align:center;">
                        <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#334155;text-align:left;">
                            Hi ' . $name . ',
                        </p>
                        <p style="margin:0 0 32px;font-size:16px;line-height:1.6;color:#334155;text-align:left;">
                            We received a request to reset your password for your HireMatrix account. Enter the verification code below in your mobile app:
                        </p>
                        <div style="display:inline-block;padding:16px 40px;background:#f8fafc;color:#0f172a;border:2px dashed #cbd5e1;border-radius:12px;font-size:32px;font-weight:800;letter-spacing:8px;margin-bottom:32px;">' . $code . '</div>
                        <p style="margin:0 0 32px;font-size:16px;line-height:1.6;color:#334155;text-align:left;">
                            Or, if you are on a browser, click the button below to reset your password:
                        </p>
                        <a href="' . esc($resetUrl) . '" style="display:inline-block;padding:16px 32px;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;border-radius:8px;font-size:16px;">
                            Reset Password
                        </a>
                        <p style="margin:32px 0 0;font-size:14px;line-height:1.6;color:#64748b;text-align:left;border-top:1px solid #f1f5f9;padding-top:24px;">
                            If you did not request a password reset, you can safely ignore this email.
                        </p>
                    </div>
                    <div style="padding:24px 32px;background:#f8fafc;text-align:center;font-size:12px;color:#94a3b8;">
                        &copy; ' . date("Y") . ' HireMatrix. All rights reserved.
                    </div>
                </div>
            </div>';

        try {
            $emailConfig = config('Email');
            $email = \Config\Services::email(null, false);
            $email->clear(true);
            $email->setMailType('html');

            if ($emailConfig->fromEmail !== '') {
                $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName ?: 'HireMatrix');
            }

            $email->setTo((string) $user['email']);
            $email->setSubject($subject);
            $email->setMessage($body);

            $sent = $email->send(false);
            if (!$sent) {
                $debug = $email->printDebugger(['headers', 'subject']);
                log_message('error', 'API Password reset email failed: ' . $debug);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'API Password reset email exception: ' . $e->getMessage());
            return false;
        }
    }
}
