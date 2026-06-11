<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\CandidateSkillsModel;
use App\Models\EducationModel;
use App\Models\WorkExperienceModel;
use App\Libraries\ResumeParser;
use CodeIgniter\RESTful\ResourceController;

class ApiOnboardingController extends ResourceController
{
    protected $format = 'json';

    public function saveStep($step)
    {
        if ($step === 'parse-resume') {
            return $this->parseResume();
        }

        if ($step === 'resume') {
            return $this->saveResume((int) $this->request->getPost('user_id'));
        }

        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $candidateId = (int) $json->user_id;

        switch ($step) {
            case 'personal':
                return $this->savePersonal($candidateId, $json);
            case 'skills':
                return $this->saveSkills($candidateId, $json);
            case 'education':
                return $this->saveEducation($candidateId, $json);
            case 'experience':
                return $this->saveExperience($candidateId, $json);
            case 'preferences':
                return $this->savePreferences($candidateId, $json);
            case 'review':
                return $this->completeOnboarding($candidateId, $json);
            default:
                return $this->fail('Invalid step');
        }
    }

    private function savePersonal(int $candidateId, $json)
    {
        $rules = [
            'name'          => 'required|min_length[3]',
            'phone'         => 'required|min_length[10]',
            'location'      => 'required|min_length[2]',
            'bio'           => 'required|min_length[20]',
            'gender'        => 'required',
            'date_of_birth' => 'required',
        ];

        $data = (array) $json;
        if (!$this->validateData($data, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $userModel = new UserModel();
        $userModel->update($candidateId, [
            'name'  => trim((string) ($json->name ?? '')),
            'phone' => trim((string) ($json->phone ?? '')),
            'onboarding_step' => 'skills',
        ]);

        $userModel->upsertCandidateProfile($candidateId, [
            'location'      => trim((string) ($json->location ?? '')),
            'bio'           => trim((string) ($json->bio ?? '')),
            'gender'        => trim((string) ($json->gender ?? '')),
            'date_of_birth' => trim((string) ($json->date_of_birth ?? '')),
        ]);

        return $this->respond(['status' => 'success']);
    }

    private function saveResume(int $candidateId)
    {
        if (!$candidateId) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $file = $this->request->getFile('resume');
        
        if (!$file || !$file->isValid()) {
            $user = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
            if (!empty($user['resume_path'])) {
                (new UserModel())->update($candidateId, ['onboarding_step' => 'skills']);
                return $this->respond(['status' => 'success', 'message' => 'Existing resume kept.']);
            }
            return $this->fail('Resume upload is required.');
        }

        $allowedTypes = ['pdf', 'docx', 'doc'];
        if (!in_array(strtolower((string) $file->getExtension()), $allowedTypes, true)) {
            return $this->fail('Only PDF, DOCX, or DOC files are allowed.');
        }

        $uploadPath = WRITEPATH . 'uploads/resumes/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        if (!$file->move($uploadPath)) {
            return $this->fail('Failed to save the file on server.');
        }

        $fcPath = FCPATH . 'uploads/resumes/';
        if (!is_dir($fcPath)) {
            mkdir($fcPath, 0755, true);
        }
        @copy($uploadPath . $file->getName(), $fcPath . $file->getName());

        (new UserModel())->upsertCandidateProfile($candidateId, [
            'resume_path' => 'uploads/resumes/' . $file->getName(),
        ]);
        (new UserModel())->update($candidateId, ['onboarding_step' => 'skills']);

        return $this->respond(['status' => 'success', 'path' => 'uploads/resumes/' . $file->getName()]);
    }

    private function saveSkills(int $candidateId, $json)
    {
        $skillsValue = trim((string) ($json->skills ?? ''));
        if ($skillsValue === '') {
            return $this->fail('Add at least one skill to continue.');
        }

        $skills = array_values(array_filter(array_map('trim', explode(',', $skillsValue))));
        if (empty($skills)) {
            return $this->fail('Add valid comma-separated skills to continue.');
        }
        
        $model = new CandidateSkillsModel();
        $existing = $model->where('candidate_id', $candidateId)->first();
        $payload = [
            'candidate_id' => $candidateId,
            'skill_name' => implode(', ', $skills),
        ];

        if ($existing) {
            $model->update((int) $existing['id'], $payload);
        } else {
            $model->insert($payload);
        }

        (new UserModel())->update($candidateId, ['onboarding_step' => 'education']);
        return $this->respond(['status' => 'success']);
    }

    private function saveEducation(int $candidateId, $json)
    {
        $rows = [];
        $educationsList = $json->educations ?? [];
        if (empty($educationsList)) {
            return $this->fail('Add at least one education entry to continue.');
        }

        foreach ($educationsList as $edu) {
            $degree = trim((string) ($edu->degree ?? ''));
            $field = trim((string) ($edu->field_of_study ?? ''));
            $institution = trim((string) ($edu->institution ?? ''));
            $startYear = trim((string) ($edu->start_year ?? ''));
            $endYear = trim((string) ($edu->end_year ?? ''));
            
            if ($degree === '' || mb_strlen($degree) < 2 || $field === '' || mb_strlen($field) < 2 || $institution === '' || mb_strlen($institution) < 2 || $startYear === '' || !is_numeric($startYear) || $endYear === '' || !is_numeric($endYear)) {
                return $this->fail('Each education entry must include valid degree, field of study, institution, start year, and end year.');
            }

            $rows[] = [
                'user_id' => $candidateId,
                'degree' => $degree,
                'field_of_study' => $field,
                'institution' => $institution,
                'start_year' => (int) $startYear,
                'end_year' => (int) $endYear,
                'grade' => trim((string) ($edu->grade ?? '')),
            ];
        }

        $model = new EducationModel();
        $model->where('user_id', $candidateId)->delete();
        foreach ($rows as $row) {
            $model->insert($row);
        }

        (new UserModel())->update($candidateId, ['onboarding_step' => 'experience']);
        return $this->respond(['status' => 'success']);
    }

    private function saveExperience(int $candidateId, $json)
    {
        $isFresher = ($json->is_fresher ?? false) === true;
        $userModel = new UserModel();

        if ($isFresher) {
            $userModel->upsertCandidateProfile($candidateId, ['is_fresher_candidate' => 1]);
            $model = new WorkExperienceModel();
            $model->where('user_id', $candidateId)->delete();
        } else {
            $userModel->upsertCandidateProfile($candidateId, ['is_fresher_candidate' => 0]);
            
            $rows = [];
            $experiencesList = $json->experiences ?? [];
            if (empty($experiencesList)) {
                return $this->fail('Add at least one experience entry or mark yourself as a fresher.');
            }

            foreach ($experiencesList as $exp) {
                $jobTitle = trim((string) ($exp->job_title ?? ''));
                $companyName = trim((string) ($exp->company_name ?? ''));
                $startDate = trim((string) ($exp->start_date ?? ''));

                if ($jobTitle === '' || mb_strlen($jobTitle) < 2 || $companyName === '' || mb_strlen($companyName) < 2 || $startDate === '') {
                    return $this->fail('Each experience entry must include valid job title, company name, and start date.');
                }

                $rows[] = [
                    'user_id' => $candidateId,
                    'job_title' => $jobTitle,
                    'company_name' => $companyName,
                    'employment_type' => trim((string) ($exp->employment_type ?? 'Full-time')),
                    'location' => trim((string) ($exp->location ?? '')),
                    'start_date' => $startDate,
                    'end_date' => ($exp->is_current ?? false) ? null : (trim((string) ($exp->end_date ?? '')) !== '' ? trim((string) ($exp->end_date ?? '')) : null),
                    'is_current' => ($exp->is_current ?? false) ? 1 : 0,
                    'description' => trim((string) ($exp->description ?? '')),
                ];
            }

            $model = new WorkExperienceModel();
            $model->where('user_id', $candidateId)->delete();
            foreach ($rows as $row) {
                $model->insert($row);
            }
        }

        (new UserModel())->update($candidateId, ['onboarding_step' => 'review']);
        return $this->respond(['status' => 'success']);
    }

    private function savePreferences(int $candidateId, $json)
    {
        (new UserModel())->upsertCandidateProfile($candidateId, [
            'resume_headline' => trim((string) ($json->resume_headline ?? '')),
            'preferred_job_titles' => trim((string) ($json->preferred_job_titles ?? '')),
            'preferred_locations' => trim((string) ($json->preferred_locations ?? '')),
            'preferred_employment_type' => trim((string) ($json->preferred_employment_type ?? '')),
            'notice_period' => trim((string) ($json->notice_period ?? '')),
            'expected_salary' => isset($json->expected_salary) ? (float)$json->expected_salary : null,
        ]);

        (new UserModel())->update($candidateId, ['onboarding_step' => 'review']);
        return $this->respond(['status' => 'success']);
    }

    private function completeOnboarding(int $candidateId, $json)
    {
        (new UserModel())->update($candidateId, [
            'onboarding_completed' => 1,
            'onboarding_step' => 'review',
            'onboarding_completed_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->respond(['status' => 'success']);
    }

    public function parseResume()
    {
        $candidateId = (int) $this->request->getPost('user_id');
        if (!$candidateId) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $file = $this->request->getFile('resume');
        if (!$file || !$file->isValid()) {
            return $this->fail('Invalid file upload');
        }

        $extension = strtolower((string) $file->getExtension());
        if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            return $this->fail('Upload a PDF, DOC, or DOCX resume.');
        }

        $uploadPath = WRITEPATH . 'uploads/resumes/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $newName = $file->getRandomName();
        if (!$file->move($uploadPath, $newName)) {
            return $this->fail('Could not save uploaded resume.');
        }

        $fcPath = FCPATH . 'uploads/resumes/';
        if (!is_dir($fcPath)) {
            mkdir($fcPath, 0755, true);
        }
        @copy($uploadPath . $newName, $fcPath . $newName);

        $resumePath = $uploadPath . $newName;
        $parsedResume = (new ResumeParser())->parse($resumePath);
        $text = trim((string) ($parsedResume['raw_text'] ?? ''));

        if (mb_strlen($text) < 30) {
            return $this->fail('Could not read text from this resume. Please try a text-based PDF or DOCX file.');
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

        $model = new UserModel();
        $path = 'uploads/resumes/' . $newName;
        $model->upsertCandidateProfile($candidateId, ['resume_path' => $path]);

        return $this->respond([
            'status' => 'success',
            'data' => $parsedData
        ]);
    }

    private function callOpenAIForParsing($prompt, $apiKey)
    {
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.1,
            'max_tokens' => 3000,
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
            CURLOPT_TIMEOUT => 60,
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
            list($degree, $fieldOfStudy) = $this->normalizeEducationDegreeAndField($degree, $fieldOfStudy);

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
}
