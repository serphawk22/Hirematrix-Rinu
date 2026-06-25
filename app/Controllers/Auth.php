<?php

namespace App\Controllers;

use App\Libraries\CandidateOnboardingService;
use App\Libraries\RememberMeService;
use App\Libraries\ResumeParser;
use App\Libraries\UsageAnalyticsService;
use App\Models\CompanyModel;
use App\Models\UserModel;

class Auth extends BaseController
{
    private const RECRUITER_PHONE_OTP_SESSION_KEY = 'recruiter_phone_otp';
    private const RECRUITER_PHONE_OTP_TTL_SECONDS = 600;
    private const RECRUITER_PHONE_OTP_RESEND_SECONDS = 45;
    private const RECRUITER_PHONE_OTP_MAX_ATTEMPTS = 5;

    /* ================= LOGIN ================= */

    public function login()
    {
        (new RememberMeService())->attemptAutoLogin();

        $session = session();
        $next = (string) $this->request->getGet('next');

        if ($session->get('logged_in')) {
            $default = $session->get('role') === 'recruiter'
                ? base_url('recruiter/dashboard')
                : $this->resolveCandidateTarget([
                    'id' => (int) $session->get('user_id'),
                    'role' => 'candidate',
                ]);

            return redirect()->to($this->resolveNextUrl($next, $default));
        }

        return view('Auth/login', ['next' => $next]);
    }

    public function authenticate()
    {
        // Validate input
        if (!$this->validate([
            'email' => 'required|valid_email',
            'password' => 'required|min_length[6]'
        ])) {
            return redirect()->back()->with('error', 'Invalid input');
        }

        $model = new UserModel();
        $session = session();

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = $model->where('email', $email)->first();

        // Constant-time comparison to prevent timing attacks
        if (!$user || !password_verify($password, $user['password'])) {
            return redirect()->back()->with('error', 'Invalid email or password');
        }

        if ($user['role'] === 'recruiter' && !$this->isRecruiterFullyVerified($user)) {
            return redirect()->to(base_url('recruiter/verification?email=' . urlencode($user['email'])))
                ->with('error', $this->getRecruiterVerificationMessage($user));
        }

        $profilePhoto = '';
        if (($user['role'] ?? '') === 'candidate') {
            $candidateRecord = $model->findCandidateWithProfile((int) $user['id']);
            $profilePhoto = (string) ($candidateRecord['profile_photo'] ?? '');
        }

        // Regenerate session to prevent fixation
        $session->regenerate();

        $session->set([
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'role' => $user['role'],
            'profile_photo' => $profilePhoto,
            'logged_in' => true,
            'login_perf_pending' => 1,
            'login_started_at_ms' => (int) round(microtime(true) * 1000),
            'login_at' => date('Y-m-d H:i:s'),
        ]);

        $rememberRequested = (string) $this->request->getPost('remember_me') === '1';
        $rememberService = new RememberMeService();
        if ($rememberRequested) {
            $rememberService->issueForUser($user);
        } else {
            $rememberService->clearCurrentToken();
        }

        $defaultTarget = ($user['role'] === 'recruiter')
            ? base_url('recruiter/dashboard')
            : $this->resolveCandidateTarget($user);

        $next = (string) $this->request->getPost('next');

        return redirect()->to($this->resolveNextUrl($next, $defaultTarget));

    }

    public function logout()
    {
        (new RememberMeService())->clearCurrentToken();
        session()->destroy();
        return redirect()->to(base_url('login'));
    }

    public function changePassword()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to(base_url('login'));
        }

        return view('Auth/change_password');
    }

    public function saveChangedPassword()
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to(base_url('login'));
        }

        if (!$this->validate([
            'current_password' => 'required',
            'password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]',
        ])) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $userId = (int) $session->get('user_id');
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || empty($user['password']) || !password_verify((string) $this->request->getPost('current_password'), (string) $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Current password is incorrect.');
        }

        $newPassword = (string) $this->request->getPost('password');
        if (password_verify($newPassword, (string) $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'New password must be different from the current password.');
        }

        $userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        return redirect()->to(base_url('account/change-password'))
            ->with('success', 'Password changed successfully.');
    }

    public function forgotPassword()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(base_url('login'));
        }

        return view('Auth/forgot_password');
    }

    public function sendPasswordResetLink()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Enter a valid email address.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $userModel->update((int) $user['id'], [
                'password_reset_token' => $token,
                'password_reset_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            ]);

            $updatedUser = $userModel->find((int) $user['id']);
            $mailError = null;
            if ($updatedUser) {
                $this->sendPasswordResetEmail($updatedUser, $mailError);
            }
        }

        return redirect()->to(base_url('forgot-password'))
            ->with('success', 'If that email exists in our system, a password reset link has been sent.');
    }

    public function resetPassword($token)
    {
        $user = $this->findValidPasswordResetUser((string) $token);
        if (!$user) {
            return redirect()->to(base_url('forgot-password'))
                ->with('error', 'Invalid or expired password reset link.');
        }

        return view('Auth/reset_password', ['token' => (string) $token]);
    }

    public function updatePassword($token)
    {
        $user = $this->findValidPasswordResetUser((string) $token);
        if (!$user) {
            return redirect()->to(base_url('forgot-password'))
                ->with('error', 'Invalid or expired password reset link.');
        }

        if (!$this->validate([
            'password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]',
        ])) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        (new UserModel())->update((int) $user['id'], [
            'password' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
        ]);

        return redirect()->to(base_url('login'))
            ->with('success', 'Password reset successful. You can now log in.');
    }

    /* ================= CANDIDATE REGISTRATION ================= */

    public function registerCandidate()
    {
        return view('Auth/register_candidate');
    }

    public function saveCandidate()
    {
        // Validate input
        if (!$this->validate([
            'name' => 'required|min_length[3]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'phone' => 'required|numeric|min_length[10]',
            'password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]'
        ], [
            'email' => [
                'is_unique' => 'This email address is already registered. Please log in or use a different email.'
            ]
        ])) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $model = new UserModel();
        $model->insert([
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'password' => password_hash(
                $this->request->getPost('password'),
                PASSWORD_DEFAULT
            ),
            'role' => 'candidate',
            'onboarding_completed' => 0,
            'onboarding_step' => 'personal',
        ]);

        $userId = (int) $model->getInsertID();
        session()->regenerate();
        session()->set([
            'user_id' => $userId,
            'user_name' => (string) $this->request->getPost('name'),
            'user_email' => (string) $this->request->getPost('email'),
            'role' => 'candidate',
            'logged_in' => true,
            'login_perf_pending' => 1,
            'login_started_at_ms' => (int) round(microtime(true) * 1000),
            'login_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url('candidate/onboarding/personal'));
    }


    public function googleCandidateStart()
    {
        $clientId = trim((string) (env('google.clientId') ?? env('GOOGLE_CLIENT_ID') ?? ''));
        $redirectUri = base_url('auth/google/callback');

        if ($clientId === '') {
            return redirect()->to(base_url('register'))
                ->with('error', 'Google sign-up is not configured. Please contact support.');
        }

        $state = bin2hex(random_bytes(16));
        session()->set('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->to('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function googleCandidateCallback()
    {
        $request = $this->request;
        $session = session();

        if ($request->getGet('error')) {
            return redirect()->to(base_url('register'))
                ->with('error', 'Google sign-up was cancelled or denied.');
        }

        $state = (string) $request->getGet('state');
        $expectedState = (string) $session->get('google_oauth_state');
        $session->remove('google_oauth_state');

        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            return redirect()->to(base_url('register'))
                ->with('error', 'Invalid Google sign-up state. Please try again.');
        }

        $code = (string) $request->getGet('code');
        if ($code === '') {
            return redirect()->to(base_url('register'))
                ->with('error', 'Missing Google authorization code.');
        }

        $clientId = trim((string) (env('google.clientId') ?? env('GOOGLE_CLIENT_ID') ?? ''));
        $clientSecret = trim((string) (env('google.clientSecret') ?? env('GOOGLE_CLIENT_SECRET') ?? ''));
        $redirectUri = base_url('auth/google/callback');

        if ($clientId === '' || $clientSecret === '') {
            return redirect()->to(base_url('register'))
                ->with('error', 'Google sign-up is not configured.');
        }

        try {
            $http = \Config\Services::curlrequest();
            $usageAnalytics = new UsageAnalyticsService();

            $tokenStart = microtime(true);
            $tokenResponse = $http->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ]);
            $tokenLatencyMs = (int) round((microtime(true) - $tokenStart) * 1000);
            $usageAnalytics->logExternalApiUsage(
                'google',
                '/oauth2/token',
                'oauth_token_exchange',
                (int) $tokenResponse->getStatusCode(),
                $tokenLatencyMs,
                1,
                ((int) $tokenResponse->getStatusCode()) >= 200 && ((int) $tokenResponse->getStatusCode()) < 400
            );

            $tokenData = json_decode((string) $tokenResponse->getBody(), true) ?: [];
            $accessToken = (string) ($tokenData['access_token'] ?? '');

            if ($accessToken === '') {
                return redirect()->to(base_url('register'))
                    ->with('error', 'Google sign-up failed while getting access token.');
            }

            $userInfoStart = microtime(true);
            $userResponse = $http->get('https://www.googleapis.com/oauth2/v3/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);
            $userInfoLatencyMs = (int) round((microtime(true) - $userInfoStart) * 1000);
            $usageAnalytics->logExternalApiUsage(
                'google',
                '/oauth2/v3/userinfo',
                'oauth_userinfo',
                (int) $userResponse->getStatusCode(),
                $userInfoLatencyMs,
                1,
                ((int) $userResponse->getStatusCode()) >= 200 && ((int) $userResponse->getStatusCode()) < 400
            );

            $googleUser = json_decode((string) $userResponse->getBody(), true) ?: [];
        } catch (\Throwable $e) {
            (new UsageAnalyticsService())->logExternalApiUsage(
                'google',
                '/oauth2/token|/oauth2/v3/userinfo',
                'oauth_flow_exception',
                null,
                null,
                1,
                false
            );
            log_message('error', 'Google OAuth callback failed: ' . $e->getMessage());
            return redirect()->to(base_url('register'))
                ->with('error', 'Unable to connect to Google right now. Please try again.');
        }

        $googleId = trim((string) ($googleUser['sub'] ?? ''));
        $email = strtolower(trim((string) ($googleUser['email'] ?? '')));
        $name = trim((string) ($googleUser['name'] ?? ''));
        $picture = trim((string) ($googleUser['picture'] ?? ''));
        $emailVerified = (bool) ($googleUser['email_verified'] ?? false);

        if ($googleId === '' || $email === '' || !$emailVerified) {
            return redirect()->to(base_url('register'))
                ->with('error', 'Google did not return a verified email address.');
        }

        $userModel = new UserModel();

        $user = $userModel
            ->groupStart()
                ->where('google_id', $googleId)
                ->orWhere('email', $email)
            ->groupEnd()
            ->first();

        if ($user) {
            if (($user['role'] ?? '') !== 'candidate') {
                return redirect()->to(base_url('login'))
                    ->with('error', 'This Google account is linked to a recruiter account. Please use recruiter login.');
            }

            $updates = [];
            if (empty($user['google_id'])) {
                $updates['google_id'] = $googleId;
            }
            if ($name !== '' && (empty($user['name']) || (string) ($user['google_id'] ?? '') === $googleId)) {
                $updates['name'] = $name;
            }
            if (!empty($updates)) {
                $userModel->update((int) $user['id'], $updates);
                $user = $userModel->find((int) $user['id']) ?? $user;
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

            $userModel->insert($insertData);
            $newId = (int) $userModel->getInsertID();
            $user = $userModel->find($newId);

            if (!$user) {
                return redirect()->to(base_url('register'))
                    ->with('error', 'Failed to create candidate account from Google profile.');
            }
        }

        $candidateRecord = $userModel->findCandidateWithProfile((int) $user['id']) ?? [];
        $profilePhoto = (string) ($candidateRecord['profile_photo'] ?? '');

        if ($picture !== '' && ($profilePhoto === '' || $this->isGoogleHostedImageUrl($profilePhoto))) {
            $userModel->upsertCandidateProfile((int) $user['id'], ['profile_photo' => $picture]);
            $profilePhoto = $picture;
        }

        $session->regenerate();
        $session->set([
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'role' => $user['role'],
            'profile_photo' => $profilePhoto,
            'logged_in' => true,
            'login_perf_pending' => 1,
            'login_started_at_ms' => (int) round(microtime(true) * 1000),
            'login_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to($this->resolveCandidateTarget($user));
    }

    /**
     * AI Resume Parser for Onboarding Prefill
     */
    public function parseResumeForOnboarding()
    {
        if (!session()->get('logged_in')) {
            return $this->response->setJSON(['error' => 'Not authenticated'])->setStatusCode(401);
        }

        $file = $this->request->getFile('resume');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['error' => 'Invalid file upload'])->setStatusCode(400);
        }

        $extension = strtolower((string) $file->getExtension());
        if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            return $this->response->setJSON(['error' => 'Upload a PDF, DOC, or DOCX resume.'])->setStatusCode(400);
        }

        $uploadPath = WRITEPATH . 'uploads/resumes/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $newName = $file->getRandomName();
        if (!$file->move($uploadPath, $newName)) {
            return $this->response->setJSON(['error' => 'Could not save uploaded resume.'])->setStatusCode(500);
        }

        $resumePath = $uploadPath . $newName;
        $parsedResume = (new ResumeParser())->parse($resumePath);
        $text = trim((string) ($parsedResume['raw_text'] ?? ''));

        if (mb_strlen($text) < 30) {
            return $this->response->setJSON(['error' => 'Could not read enough text from this resume. Please try a text-based PDF or DOCX file.'])->setStatusCode(422);
        }
        
        $prompt = "Extract professional details from the following resume text. 
                   Return ONLY a valid JSON object with these keys: 
                   name, email, phone, location, bio, skills (comma separated string), 
                   education (array of objects with: degree, field_of_study, institution, start_year, end_year, grade),
                   experience (array of objects with: job_title, company_name, location, start_date, end_date, is_current (boolean), description).
                   Use YYYY-MM-DD for experience dates when month/year can be inferred, otherwise use an empty string.
                   Resume Text: \n" . substr($text, 0, 4000);

        $apiKey = getenv('OPENAI_API_KEY');
        $parsedData = !empty($apiKey) ? $this->callOpenAIForParsing($prompt, $apiKey) : null;
        if (!$parsedData) {
            $parsedData = $this->buildResumePrefillFallback($text, $parsedResume);
        }
        $parsedData = $this->normalizeOnboardingPrefillData($parsedData);

        // Save resume path so Step 2 is also handled
        $model = new UserModel();
        $userId = (int) session()->get('user_id');
        $path = 'uploads/resumes/' . $newName;
        $model->upsertCandidateProfile($userId, ['resume_path' => $path]);

        // Store in session to assist cross-step persistence if needed
        session()->set('onboarding_prefill', $parsedData);

        return $this->response->setJSON([
            'success' => true,
            'data' => $parsedData
        ]);
    }

    private function buildResumePrefillFallback(string $text, array $parsedResume): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $text) ?: [])));
        $firstLines = array_slice($lines, 0, 10);
        $name = '';
        foreach ($firstLines as $line) {
            if (
                !filter_var($line, FILTER_VALIDATE_EMAIL)
                && !preg_match('/\d{5,}/', $line)
                && mb_strlen($line) >= 3
                && mb_strlen($line) <= 80
            ) {
                $name = preg_replace('/[^A-Za-z\s\.\'-]/', '', $line);
                $name = trim((string) preg_replace('/\s+/', ' ', (string) $name));
                if ($name !== '') {
                    break;
                }
            }
        }

        preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $emailMatch);
        preg_match('/(?:\+?\d[\d\s\-\(\)]{8,}\d)/', $text, $phoneMatch);

        $skills = [];
        foreach ((array) ($parsedResume['skills'] ?? []) as $skill) {
            $skillName = trim((string) ($skill['name'] ?? ''));
            if ($skillName !== '') {
                $skills[] = $skillName;
            }
        }

        return [
            'name' => $name,
            'email' => $emailMatch[0] ?? '',
            'phone' => isset($phoneMatch[0]) ? preg_replace('/[^\d+]/', '', $phoneMatch[0]) : '',
            'location' => '',
            'bio' => $this->extractResumeSummary($text),
            'skills' => implode(', ', array_values(array_unique($skills))),
            'education' => [],
            'experience' => [],
        ];
    }

    private function extractResumeSummary(string $text): string
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', $text));
        if ($normalized === '') {
            return '';
        }

        if (preg_match('/(?:summary|profile|objective)\s*[:\-]?\s*(.{80,600}?)(?=\s(?:skills|education|experience|projects|employment)\b|$)/i', $normalized, $match)) {
            return trim($match[1]);
        }

        return trim(mb_substr($normalized, 0, 450));
    }

    private function normalizeOnboardingPrefillData(array $data): array
    {
        $normalized = [
            'name' => trim((string) ($data['name'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'phone' => preg_replace('/[^\d+]/', '', (string) ($data['phone'] ?? '')),
            'location' => trim((string) ($data['location'] ?? '')),
            'bio' => trim((string) ($data['bio'] ?? '')),
            'skills' => trim(is_array($data['skills'] ?? null) ? implode(', ', $data['skills']) : (string) ($data['skills'] ?? '')),
            'education' => [],
            'experience' => [],
        ];

        foreach ((array) ($data['education'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $degree = trim((string) ($row['degree'] ?? ''));
            $fieldOfStudy = trim((string) ($row['field_of_study'] ?? ''));
            [$degree, $fieldOfStudy] = $this->normalizeEducationDegreeAndField($degree, $fieldOfStudy);

            $normalized['education'][] = [
                'degree' => $degree,
                'field_of_study' => $fieldOfStudy,
                'institution' => trim((string) ($row['institution'] ?? '')),
                'start_year' => $this->extractYear($row['start_year'] ?? ''),
                'end_year' => $this->extractYear($row['end_year'] ?? ''),
                'grade' => trim((string) ($row['grade'] ?? '')),
            ];
        }

        foreach ((array) ($data['experience'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized['experience'][] = [
                'job_title' => trim((string) ($row['job_title'] ?? '')),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
                'location' => trim((string) ($row['location'] ?? '')),
                'start_date' => $this->normalizeResumeDate($row['start_date'] ?? ''),
                'end_date' => $this->normalizeResumeDate($row['end_date'] ?? ''),
                'is_current' => (bool) ($row['is_current'] ?? false),
                'description' => trim((string) ($row['description'] ?? '')),
            ];
        }

        return $normalized;
    }

    private function normalizeEducationDegreeAndField(string $degree, string $fieldOfStudy): array
    {
        $combined = trim($degree . ' ' . $fieldOfStudy);
        $lower = strtolower($combined);

        $degreeMap = [
            'plus two' => 'Plus Two / 12th',
            '12th' => 'Plus Two / 12th',
            'higher secondary' => 'Higher Secondary',
            'intermediate' => 'Intermediate',
            'sslc' => 'SSLC / 10th',
            '10th' => 'SSLC / 10th',
        ];

        foreach ($degreeMap as $needle => $label) {
            if (str_contains($lower, $needle)) {
                $degree = $label;
                break;
            }
        }

        if ($fieldOfStudy === '') {
            $fieldMap = [
                'computer science' => 'Computer Science',
                'information technology' => 'Information Technology',
                'biology' => 'Biology',
                'science' => 'Science',
                'commerce' => 'Commerce',
                'arts' => 'Arts',
                'mathematics' => 'Mathematics',
                'physics' => 'Physics',
            ];

            foreach ($fieldMap as $needle => $label) {
                if (str_contains($lower, $needle)) {
                    $fieldOfStudy = $label;
                    break;
                }
            }
        }

        return [trim($degree), trim($fieldOfStudy)];
    }

    private function extractYear($value): string
    {
        return preg_match('/(?:19|20)\d{2}/', (string) $value, $match) ? $match[0] : '';
    }

    private function normalizeResumeDate($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/present|current|ongoing/i', $value)) {
            return '';
        }
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }

    private function callOpenAIForParsing($prompt, $apiKey)
    {
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.1,
            'max_tokens' => 3000, // Ensure enough room for complex resumes
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim((string)$apiKey),
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 60, // Increased timeout for deep parsing
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            log_message('error', 'AI Resume Parser cURL Error: ' . $curlError);
            return null;
        }

        $result = json_decode((string)$response, true);

        if ($httpCode !== 200) {
            $errorMsg = $result['error']['message'] ?? 'Unknown OpenAI API Error';
            log_message('error', "OpenAI API Error ($httpCode): " . $errorMsg);
            return null;
        }

        $content = $result['choices'][0]['message']['content'] ?? '';
        if (trim($content) === '') {
            log_message('error', 'OpenAI returned empty content for resume parsing.');
            return null;
        }

        $parsed = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'AI Resume Parser JSON decode error: ' . json_last_error_msg());
            return null;
        }

        return $parsed;
    }

    /* ================= ADMIN REGISTRATION ================= */

    public function registerAdmin()
    {
        return view('Auth/register_admin');
    }

    public function saveAdmin()
    {
        // Validate input
        if (!$this->validate([
            'company_name' => 'required|min_length[2]',
            'recruiter_type' => 'required|in_list[direct_employer,consultancy]',
            'name' => 'required|min_length[3]',
            'designation' => 'required|min_length[2]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'official_email' => 'permit_empty|valid_email',
            'website' => 'permit_empty|max_length[255]',
            'agency_registration_number' => 'permit_empty|max_length[100]',
            'gst_number' => 'permit_empty|max_length[30]',
            'phone' => 'required|numeric|min_length[10]|max_length[15]',
            'password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]'
        ], [
            'email' => [
                'is_unique' => 'This email is already associated with a recruiter account. Please log in instead.'
            ]
        ])) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $companyName = trim((string) $this->request->getPost('company_name'));
        $designation = trim((string) $this->request->getPost('designation'));
        $recruiterType = (string) $this->request->getPost('recruiter_type');
        $recruiterType = in_array($recruiterType, ['direct_employer', 'consultancy'], true) ? $recruiterType : 'direct_employer';
        $officialEmail = strtolower(trim((string) $this->request->getPost('official_email')));
        $officialEmail = $officialEmail !== '' ? $officialEmail : $email;
        $website = trim((string) $this->request->getPost('website'));
        $agencyRegistrationNumber = trim((string) $this->request->getPost('agency_registration_number'));
        $gstNumber = trim((string) $this->request->getPost('gst_number'));
        $domain = substr(strrchr($email, "@"), 1);
        if ($this->isFreeEmailDomain($domain)) {
            return redirect()->back()->withInput()->with(
                'error',
                'Please use your company email address (free email providers are not allowed for recruiters).'
            );
        }

        $officialDomain = substr(strrchr($officialEmail, "@"), 1);
        if ($officialDomain && $this->isFreeEmailDomain($officialDomain)) {
            return redirect()->back()->withInput()->with(
                'error',
                'Please use an official business email address for recruiter verification.'
            );
        }

        $companyModel = new CompanyModel();
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

        $model = new UserModel();
        $verificationToken = (string) random_int(100000, 999999);

        $model->insert([
            'company_id' => $companyId,
            'name' => $this->request->getPost('name'),
            'email' => $email,
            'phone' => $this->request->getPost('phone'),
            'password' => password_hash(
                $this->request->getPost('password'),
                PASSWORD_DEFAULT
            ),
            'role' => 'recruiter',
            'email_verification_token' => $verificationToken
        ]);

        $newRecruiterId = (int) $model->getInsertID();
        $model->upsertRecruiterProfile($newRecruiterId, [
            'name' => (string) $this->request->getPost('name'),
            'phone' => (string) $this->request->getPost('phone'),
            'designation' => $designation,
            'company_name' => $companyName,
            'recruiter_type' => $recruiterType,
            'verification_status' => $recruiterType === 'consultancy' ? 'pending' : 'verified',
            'agency_registration_number' => $agencyRegistrationNumber !== '' ? $agencyRegistrationNumber : null,
            'gst_number' => $gstNumber !== '' ? $gstNumber : null,
            'website' => $website !== '' ? $website : null,
            'official_email' => $officialEmail,
            'can_post_jobs' => $recruiterType === 'consultancy' ? 0 : 1,
        ]);

        $db = \Config\Database::connect();
        if ($db->tableExists('recruiter_company_map')) {
            $exists = $db->table('recruiter_company_map')
                ->where('recruiter_user_id', $newRecruiterId)
                ->where('company_id', $companyId)
                ->get()
                ->getRowArray();

            if (!$exists) {
                $db->table('recruiter_company_map')->insert([
                    'recruiter_user_id' => $newRecruiterId,
                    'company_id' => $companyId,
                    'is_admin' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $recruiter = $model->find($newRecruiterId);
        $emailError = null;
        $emailSent = $recruiter ? $this->sendRecruiterVerificationEmail($recruiter, $emailError) : false;

        $redirect = redirect()->to(base_url('recruiter/verification?email=' . urlencode($email)));
        if (!$emailSent) {
            return $redirect->with(
                'error',
                'Account created, but the verification email could not be sent. '
                . ($emailError ?? 'Use the resend option below.')
            );
        }

        $successMessage = $recruiterType === 'consultancy'
            ? 'Consultancy account created. Verify your email; job posting will unlock after admin verification.'
            : 'Account created. Check your inbox to verify your company email.';

        return redirect()->to(base_url('recruiter/verification?email=' . urlencode($email)))
            ->with('success', $successMessage);
    }

    public function recruiterVerification()
    {
        $email = strtolower(trim((string) ($this->request->getGet('email') ?? old('email') ?? '')));
        $phone = '';
        $isEmailVerified = false;
        $isPhoneVerified = false;
        $hasPendingPhoneOtp = false;

        if ($email !== '') {
            $model = new UserModel();
            $user = $model->where('email', $email)->where('role', 'recruiter')->first();
            $phone = (string) ($user['phone'] ?? '');
            $isEmailVerified = !empty($user['email_verified_at']);
            $isPhoneVerified = !empty($user['phone_verified_at']);
            $pendingOtp = session()->get(self::RECRUITER_PHONE_OTP_SESSION_KEY);
            $hasPendingPhoneOtp = is_array($pendingOtp)
                && ($pendingOtp['email'] ?? '') === $email
                && (int) ($pendingOtp['expires_at'] ?? 0) >= time();

            if ($isEmailVerified) {
                return redirect()->to(base_url('login'))
                    ->with('success', 'Email verified successfully. You can now log in.');
            }
        }

        return view('Auth/recruiter_verification', [
            'email' => $email,
            'phone' => $phone,
            'isEmailVerified' => $isEmailVerified,
            'isPhoneVerified' => $isPhoneVerified,
            'canVerifyPhone' => $email !== '' && $phone !== '' && !$isPhoneVerified,
            'hasPendingPhoneOtp' => $hasPendingPhoneOtp,
        ]);
    }

    public function resendRecruiterVerificationEmail()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $model = new UserModel();
        $user = $model->where('email', $email)->where('role', 'recruiter')->first();

        if (!$user) {
            return redirect()->back()->with('error', 'Recruiter account not found.');
        }

        if (!empty($user['email_verified_at'])) {
            return redirect()->to(base_url('login'))
                ->with('success', 'Email is already verified. You can now log in.');
        }

        $newToken = (string) random_int(100000, 999999);

        $model->update($user['id'], [
            'email_verification_token' => $newToken,
        ]);

        $updatedUser = $model->find($user['id']);
        $emailError = null;
        $emailSent = $this->sendRecruiterVerificationEmail($updatedUser, $emailError);

        if (!$emailSent) {
            return redirect()->to(base_url('recruiter/verification?email=' . urlencode($email)))
                ->with('error', 'Verification email could not be sent. ' . ($emailError ?? 'Please try again in a minute.'));
        }

        return redirect()->to(base_url('recruiter/verification?email=' . urlencode($email)))
            ->with('success', 'Verification email resent.');
    }

    public function sendRecruiterPhoneOtp()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $model = new UserModel();
        $user = $model->where('email', $email)->where('role', 'recruiter')->first();

        if (!$user) {
            return redirect()->back()->with('error', 'Recruiter account not found.');
        }

        if (!empty($user['phone_verified_at'])) {
            return redirect()->to(base_url('recruiter/verification?email=' . urlencode($email)))
                ->with('success', 'Phone number is already verified.');
        }

        $error = null;
        if (!$this->issueRecruiterPhoneOtp($user, $error)) {
            return redirect()->to(base_url('recruiter/verification?email=' . urlencode($email)))
                ->with('error', 'WhatsApp OTP could not be sent. ' . ($error ?? 'Please try again in a minute.'));
        }

        return redirect()->to(base_url('recruiter/verification?email=' . urlencode($email)))
            ->with('success', 'WhatsApp OTP sent to your registered phone number.');
    }

    public function submitRecruiterPhoneOtp()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $code = preg_replace('/\D/', '', (string) $this->request->getPost('phone_code'));

        if ($email === '' || $code === '') {
            return redirect()->back()->with('error', 'Email and WhatsApp OTP are required.');
        }

        $model = new UserModel();
        $user = $model->where('email', $email)->where('role', 'recruiter')->first();

        if (!$user) {
            return redirect()->back()->with('error', 'Recruiter account not found.');
        }

        if (!empty($user['phone_verified_at'])) {
            return redirect()->to(base_url('recruiter/verification?email=' . urlencode($email)))
                ->with('success', 'Phone number is already verified.');
        }

        $pending = session()->get(self::RECRUITER_PHONE_OTP_SESSION_KEY);
        if (!is_array($pending)
            || ($pending['email'] ?? '') !== $email
            || ($pending['phone'] ?? '') !== $this->normalizeWhatsAppPhone((string) ($user['phone'] ?? ''))
        ) {
            return redirect()->back()->withInput()->with('error', 'WhatsApp OTP expired or missing. Please resend the OTP.');
        }

        if ((int) ($pending['expires_at'] ?? 0) < time()) {
            session()->remove(self::RECRUITER_PHONE_OTP_SESSION_KEY);
            return redirect()->back()->withInput()->with('error', 'WhatsApp OTP expired. Please request a new OTP.');
        }

        $attempts = (int) ($pending['attempts'] ?? 0) + 1;
        if ($attempts > self::RECRUITER_PHONE_OTP_MAX_ATTEMPTS) {
            session()->remove(self::RECRUITER_PHONE_OTP_SESSION_KEY);
            return redirect()->back()->withInput()->with('error', 'Too many incorrect OTP attempts. Please request a new OTP.');
        }

        if (!hash_equals((string) ($pending['code_hash'] ?? ''), hash('sha256', $code))) {
            $pending['attempts'] = $attempts;
            session()->set(self::RECRUITER_PHONE_OTP_SESSION_KEY, $pending);
            return redirect()->back()->withInput()->with('error', 'Invalid WhatsApp OTP.');
        }

        $model->update((int) $user['id'], [
            'phone_verified_at' => date('Y-m-d H:i:s'),
        ]);
        session()->remove(self::RECRUITER_PHONE_OTP_SESSION_KEY);

        $message = !empty($user['email_verified_at'])
            ? 'Phone verified successfully. You can now log in.'
            : 'Phone verified successfully. Please complete email verification.';

        return redirect()->to(base_url('recruiter/verification?email=' . urlencode($email)))
            ->with('success', $message);
    }

    public function submitRecruiterEmailCode()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $code = trim((string) $this->request->getPost('code'));

        if ($email === '' || $code === '') {
            return redirect()->back()->with('error', 'Email and verification code are required.');
        }

        $model = new UserModel();
        $user = $model->where('email', $email)
            ->where('email_verification_token', $code)
            ->where('role', 'recruiter')
            ->first();

        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'Invalid or expired verification code.');
        }

        $model->update($user['id'], [
            'email_verification_token' => null,
            'email_verified_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->to(base_url('login'))
            ->with('success', 'Email verified successfully. You can now log in.');
    }

    private function sendRecruiterVerificationEmail(array $user, ?string &$error = null): bool
    {
        if (empty($user['email']) || empty($user['email_verification_token'])) {
            $error = 'Missing recipient email or verification token.';
            return false;
        }

        $verificationCode = $user['email_verification_token'];
        $verifyPageUrl = base_url('recruiter/verification?email=' . urlencode($user['email']));
        $subject = 'Verify your recruiter account - HireMatrix';
        
        $name = esc($user['name'] ?? 'Recruiter');
        
        $body = '
            <div style="margin:0;padding:24px;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;color:#0f172a;">
                <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 25px rgba(0,0,0,0.05);">
                    <div style="padding:32px;background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);color:#ffffff;text-align:center;">
                        <h1 style="margin:0;font-size:24px;font-weight:700;letter-spacing:-0.02em;">Welcome to HireMatrix</h1>
                        <p style="margin:8px 0 0;font-size:14px;opacity:0.8;">Verification code for your recruiter account</p>
                    </div>
                    <div style="padding:40px 32px;text-align:center;">
                        <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#334155;text-align:left;">Hi ' . $name . ',</p>
                        <p style="margin:0 0 32px;font-size:16px;line-height:1.6;color:#334155;text-align:left;">Thank you for joining HireMatrix. Use the code below to verify your professional email address.</p>
                        <div style="display:inline-block;padding:16px 40px;background:#f8fafc;color:#0f172a;border:2px dashed #cbd5e1;border-radius:12px;font-size:32px;font-weight:800;letter-spacing:8px;margin-bottom:32px;">' . esc($verificationCode) . '</div>
                        <p style="margin:0 0 32px;font-size:14px;color:#64748b;">This code is required to enable your job posting capabilities.</p>
                        <p style="margin:32px 0 0;font-size:12px;color:#94a3b8;text-align:left;border-top:1px solid #f1f5f9;padding-top:24px;">
                            If you need to return to the verification page, use the link below:<br>
                            <a href="' . esc($verifyPageUrl) . '" style="color:#2563eb;text-decoration:none;">' . esc($verifyPageUrl) . '</a>
                        </p>
                    </div>
                    <div style="padding:24px 32px;background:#f8fafc;text-align:center;font-size:12px;color:#94a3b8;">
                        &copy; ' . date('Y') . ' HireMatrix. All rights reserved.<br>
                        This is an automated message, please do not reply.
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
            $email->setTo($user['email']);
            $email->setSubject($subject);
            $email->setMessage($body);
            log_message('info', 'Recruiter verification email send attempt to: ' . (string) $user['email']);
            $sent = $email->send(false);
            log_message('info', 'Recruiter verification email send status: ' . ($sent ? 'sent' : 'failed'));

            if (!$sent) {
                $debug = $email->printDebugger(['headers', 'subject']);
                $error = trim(strip_tags($debug));
                log_message('error', 'Email send failed for recruiter verification (send=false): ' . $debug);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            log_message('error', 'Email send failed for recruiter verification: ' . $e->getMessage());
            return false;
        }
    }

    private function issueRecruiterPhoneOtp(array $user, ?string &$error = null, bool $allowImmediateResend = false): bool
    {
        $email = strtolower(trim((string) ($user['email'] ?? '')));
        $phone = $this->normalizeWhatsAppPhone((string) ($user['phone'] ?? ''));

        if ($email === '' || $phone === '') {
            $error = 'Registered phone number must be a valid mobile number with country code.';
            return false;
        }

        $pending = session()->get(self::RECRUITER_PHONE_OTP_SESSION_KEY);
        if (!$allowImmediateResend
            && is_array($pending)
            && ($pending['email'] ?? '') === $email
            && (time() - (int) ($pending['sent_at'] ?? 0)) < self::RECRUITER_PHONE_OTP_RESEND_SECONDS
        ) {
            $error = 'Please wait before requesting another OTP.';
            return false;
        }

        $code = (string) random_int(100000, 999999);

        if (!$this->sendWhatsAppOtp($phone, $code, $error)) {
            return false;
        }

        session()->set(self::RECRUITER_PHONE_OTP_SESSION_KEY, [
            'email' => $email,
            'phone' => $phone,
            'code_hash' => hash('sha256', $code),
            'expires_at' => time() + self::RECRUITER_PHONE_OTP_TTL_SECONDS,
            'sent_at' => time(),
            'attempts' => 0,
        ]);

        return true;
    }

    private function sendWhatsAppOtp(string $phone, string $code, ?string &$error = null): bool
    {
        $credentials = $this->whatsAppCloudCredentials($error);
        if ($credentials === null) {
            return false;
        }

        $endpoint = 'https://graph.facebook.com/' . rawurlencode($credentials['api_version']) . '/'
            . rawurlencode($credentials['phone_number_id']) . '/messages';
        $to = ltrim($phone, '+');

        try {
            $http = \Config\Services::curlrequest([
                'timeout' => 15,
                'http_errors' => false,
            ]);

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $credentials['template_name'],
                    'language' => [
                        'code' => $credentials['template_language'],
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $code,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $response = $http->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $credentials['access_token'],
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => json_encode($payload),
            ]);

            $status = (int) $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if ($status >= 200 && $status < 300 && is_array($data) && !empty($data['messages'][0]['id'])) {
                return true;
            }

            $error = is_array($data)
                ? (string) ($data['error']['message'] ?? 'WhatsApp rejected the OTP request.')
                : 'WhatsApp rejected the OTP request.';
            log_message('error', 'WhatsApp OTP send failed: HTTP ' . $status . ' ' . $body);
            return false;
        } catch (\Throwable $e) {
            $error = 'WhatsApp Cloud API could not be reached.';
            log_message('error', 'WhatsApp OTP send exception: ' . $e->getMessage());
            return false;
        }
    }

    private function whatsAppCloudCredentials(?string &$error = null): ?array
    {
        $accessToken = trim((string) (env('whatsapp.cloudAccessToken') ?: env('WHATSAPP_CLOUD_ACCESS_TOKEN') ?: ''));
        $phoneNumberId = trim((string) (env('whatsapp.phoneNumberId') ?: env('WHATSAPP_PHONE_NUMBER_ID') ?: ''));
        $templateName = trim((string) (env('whatsapp.otpTemplateName') ?: env('WHATSAPP_OTP_TEMPLATE_NAME') ?: 'recruiter_phone_otp'));
        $templateLanguage = trim((string) (env('whatsapp.templateLanguage') ?: env('WHATSAPP_TEMPLATE_LANGUAGE') ?: 'en_US'));
        $apiVersion = trim((string) (env('whatsapp.apiVersion') ?: env('WHATSAPP_API_VERSION') ?: 'v20.0'));

        if ($accessToken === '' || $phoneNumberId === '' || $templateName === '') {
            $error = 'WhatsApp Cloud API access token, phone number ID, and OTP template name must be configured.';
            return null;
        }

        return [
            'access_token' => $accessToken,
            'phone_number_id' => $phoneNumberId,
            'template_name' => $templateName,
            'template_language' => $templateLanguage,
            'api_version' => $apiVersion,
        ];
    }

    private function normalizeWhatsAppPhone(string $phone): string
    {
        $trimmed = trim($phone);
        $digits = preg_replace('/\D/', '', $trimmed);

        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }

        if (strlen($digits) >= 11 && strlen($digits) <= 15) {
            return '+' . $digits;
        }

        return '';
    }

    private function sendPasswordResetEmail(array $user, ?string &$error = null): bool
    {
        if (empty($user['email']) || empty($user['password_reset_token'])) {
            $error = 'Missing recipient email or reset token.';
            return false;
        }

        $resetUrl = base_url('reset-password/' . $user['password_reset_token']);
        $subject = 'Reset your HireMatrix password';

        $name = esc($user['name'] ?? 'User');

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
                            We received a request to reset your password for your HireMatrix account. Click the button below to choose a new password. This link is valid for 1 hour.
                        </p>
                        <a href="' . esc($resetUrl) . '" style="display:inline-block;padding:16px 32px;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;border-radius:8px;font-size:16px;">
                            Reset Password
                        </a>
                        <p style="margin:32px 0 0;font-size:14px;line-height:1.6;color:#64748b;text-align:left;border-top:1px solid #f1f5f9;padding-top:24px;">
                            If you did not request a password reset, you can safely ignore this email.
                        </p>
                    </div>
                    <div style="padding:24px 32px;background:#f8fafc;text-align:center;font-size:12px;color:#94a3b8;">
                        &copy; ' . date('Y') . ' HireMatrix. All rights reserved.
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
                $error = trim(strip_tags($debug));
                log_message('error', 'Password reset email failed: ' . $debug);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            log_message('error', 'Password reset email exception: ' . $e->getMessage());
            return false;
        }
    }

    private function findValidPasswordResetUser(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $user = (new UserModel())
            ->where('password_reset_token', $token)
            ->first();

        if (!$user) {
            return null;
        }

        $expiresAt = (string) ($user['password_reset_expires_at'] ?? '');
        if ($expiresAt === '' || strtotime($expiresAt) < time()) {
            return null;
        }

        return $user;
    }

    private function isRecruiterFullyVerified(array $user): bool
    {
        return !empty($user['email_verified_at']);
    }

    private function getRecruiterVerificationMessage(array $user): string
    {
        $emailVerified = !empty($user['email_verified_at']);

        return $emailVerified ? '' : 'Please verify your company email address before logging in.';
    }

    private function isFreeEmailDomain(string $domain): bool
    {
        $freeDomains = [
            'gmail.com',
            'yahoo.com',
            'hotmail.com',
            'outlook.com',
            'live.com',
            'aol.com',
            'icloud.com',
            'protonmail.com',
            'gmx.com',
            'mail.com'
        ];

        return in_array(strtolower(trim($domain)), $freeDomains, true);
    }

    private function resolveNextUrl(string $next, string $default): string
    {
        $next = trim($next);
        if ($next === '') {
            return $default;
        }

        $parsedNext = parse_url($next);
        $parsedBase = parse_url(base_url());
        
        if (isset($parsedNext['host']) && isset($parsedBase['host']) && $parsedNext['host'] === $parsedBase['host']) {
            return $next;
        }

        return $default;
    }

    private function resolveCandidateTarget(array $user): string
    {
        $candidateId = (int) ($user['id'] ?? 0);
        if ($candidateId <= 0) {
            return base_url('candidate/dashboard');
        }

        $onboarding = new CandidateOnboardingService();
        if (!$onboarding->isComplete($candidateId)) {
            return base_url('candidate/onboarding/' . $onboarding->getNextStep($candidateId));
        }

        return base_url('candidate/dashboard');
    }

    private function isGoogleHostedImageUrl(string $url): bool
    {
        $url = strtolower(trim($url));
        if ($url === '') {
            return false;
        }

        return str_contains($url, 'googleusercontent.com')
            || str_contains($url, 'googleapis.com');
    }
}
