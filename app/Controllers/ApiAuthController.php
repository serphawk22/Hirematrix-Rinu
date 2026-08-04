<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\CompanyModel;
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

    
    public function registerRecruiter()
    {
        $rules = [
            'company_name'   => 'required|min_length[2]',
            'recruiter_type' => 'required|in_list[direct_employer,consultancy]',
            'name'           => 'required|min_length[3]',
            'designation'    => 'required|min_length[2]',
            'email'          => 'required|valid_email|is_unique[users.email]',
            'phone'          => 'required|numeric|min_length[10]|max_length[15]',
            'password'       => 'required|min_length[6]',
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
            $json = (object) $this->request->getPost();
        }

        $email = strtolower(trim((string) $json->email));
        $companyName = trim((string) $json->company_name);
        $designation = trim((string) $json->designation);
        $recruiterType = (string) $json->recruiter_type;
        $recruiterType = in_array($recruiterType, ['direct_employer', 'consultancy'], true) ? $recruiterType : 'direct_employer';
        $agencyRegistrationNumber = trim((string) ($json->agency_registration_number ?? ''));
        $gstNumber = trim((string) ($json->gst_number ?? ''));
        $website = trim((string) ($json->website ?? ''));
        $officialEmail = trim((string) ($json->official_email ?? ''));

        // Skipping free email domain validation for mobile app as requested.

        $companyModel = new \App\Models\CompanyModel();
        $company = $companyModel->where('LOWER(name)', strtolower($companyName))->first();
        if (!$company) {
            $companyModel->insert([
                'name' => $companyName,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $companyId = (int) $companyModel->getInsertID();
        } else {
            $companyId = (int) $company['id'];
        }

        $model = new \App\Models\UserModel();
        $verificationToken = (string) random_int(100000, 999999);

        $model->insert([
            'company_id'               => $companyId,
            'name'                     => $json->name,
            'email'                    => $email,
            'phone'                    => $json->phone,
            'password'                 => password_hash($json->password, PASSWORD_DEFAULT),
            'role'                     => 'recruiter',
            'email_verification_token' => $verificationToken
        ]);

        $newRecruiterId = (int) $model->getInsertID();
        $model->upsertRecruiterProfile($newRecruiterId, [
            'name'                       => (string) $json->name,
            'phone'                      => (string) $json->phone,
            'designation'                => $designation,
            'company_name'               => $companyName,
            'recruiter_type'             => $recruiterType,
            'verification_status'        => $recruiterType === 'consultancy' ? 'pending' : 'verified',
            'agency_registration_number' => $agencyRegistrationNumber !== '' ? $agencyRegistrationNumber : null,
            'gst_number'                 => $gstNumber !== '' ? $gstNumber : null,
            'website'                    => $website !== '' ? $website : null,
            'official_email'             => $officialEmail !== '' ? $officialEmail : null,
        ]);

        // Send verification email
        $emailService = \Config\Services::email();
        $emailService->setTo($email);
        $emailService->setSubject('Verify your Recruiter Account');
        $emailService->setMessage("Your verification token is: <strong>{$verificationToken}</strong>");
        $emailService->send();

        return $this->respondCreated([
            'status'  => 'success',
            'message' => 'Recruiter registered successfully. Please verify your email.',
            'data'    => [
                'user_id' => $newRecruiterId
            ]
        ]);
    }


    public function verifyRecruiterEmail()
    {
        $rules = [
            'user_id' => 'required|numeric',
            'token'   => 'required'
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
            $json = (object) $this->request->getPost();
        }

        $userId = (int) $json->user_id;
        $token = (string) $json->token;

        $model = new \App\Models\UserModel();
        $user = $model->find($userId);

        if (!$user || $user['role'] !== 'recruiter') {
            return $this->failNotFound('Recruiter not found.');
        }

        if (!empty($user['email_verified_at'])) {
            return $this->respond([
                'status'  => 'success',
                'message' => 'Email is already verified.'
            ]);
        }

        if ($user['email_verification_token'] !== $token) {
            return $this->fail('Invalid verification code.');
        }

        $model->update($userId, [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'email_verification_token' => null
        ]);

        return $this->respond([
            'status'  => 'success',
            'message' => 'Email successfully verified!'
        ]);
    }


    public function resendRecruiterVerification()
    {
        $json = $this->request->getJSON();
        $userId = (int) ($json->user_id ?? $this->request->getVar('user_id'));

        if (!$userId) {
            return $this->fail('user_id is required.');
        }

        $model = new \App\Models\UserModel();
        $user = $model->find($userId);

        if (!$user || $user['role'] !== 'recruiter') {
            return $this->failNotFound('Recruiter not found.');
        }

        if (!empty($user['email_verified_at'])) {
            return $this->respond([
                'status'  => 'success',
                'message' => 'Email is already verified.'
            ]);
        }

        $verificationToken = (string) random_int(100000, 999999);
        $model->update($userId, ['email_verification_token' => $verificationToken]);

        try {
            $emailConfig = config('Email');
            $emailService = \Config\Services::email(null, false);
            $emailService->clear(true);
            $emailService->setMailType('html');

            if ($emailConfig->fromEmail !== '') {
                $emailService->setFrom($emailConfig->fromEmail, $emailConfig->fromName ?: 'HireMatrix');
            }

            $emailService->setTo((string) $user['email']);
            $emailService->setSubject('Your HireMatrix Verification Code');
            $emailService->setMessage("
                <div style='font-family:sans-serif;padding:24px;'>
                    <h2>Recruiter Email Verification</h2>
                    <p>Hi {$user['name']},</p>
                    <p>Your new verification code is:</p>
                    <div style='font-size:32px;font-weight:800;letter-spacing:8px;padding:16px;background:#f8fafc;border-radius:8px;display:inline-block;'>
                        {$verificationToken}
                    </div>
                    <p style='margin-top:16px;color:#64748b;'>This code expires once a new one is requested.</p>
                </div>
            ");
            $emailService->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'Resend verification email failed: ' . $e->getMessage());
        }

        return $this->respond([
            'status'  => 'success',
            'message' => 'A new verification code has been sent to your email.'
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

