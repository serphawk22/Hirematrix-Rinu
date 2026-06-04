<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\CandidateSkillsModel;
use App\Models\EducationModel;
use App\Models\WorkExperienceModel;
use App\Models\CertificationModel;
use App\Models\CandidateProjectModel;
use App\Models\CandidateInterestsModel;
use App\Models\GithubAnalysisModel;
use CodeIgniter\RESTful\ResourceController;

class ApiProfileController extends ResourceController
{
    protected $format = 'json';

    public function getProfile($candidateId)
    {
        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        $baseUrl = base_url();
        if ($user && !empty($user['profile_photo'])) {
            if (!preg_match('/^https?:\/\//i', $user['profile_photo'])) {
                $user['profile_photo_url'] = $baseUrl . ltrim($user['profile_photo'], '/');
            } else {
                $user['profile_photo_url'] = $user['profile_photo'];
            }
        } else {
            $user['profile_photo_url'] = '';
        }

        $githubModel = model('GithubAnalysisModel');
        $github = $githubModel->where('candidate_id', $candidateId)->first();

        $skillsModel = model('CandidateSkillsModel');
        $skills = $skillsModel->where('candidate_id', $candidateId)->first();

        $interestsModel = new CandidateInterestsModel();
        $interestRow    = $interestsModel->where('candidate_id', $candidateId)->first();
        $interests = [];
        if ($interestRow && !empty($interestRow['interest'])) {
            $interests = array_values(array_filter(array_map('trim', explode(',', $interestRow['interest']))));
        }

        $workExpModel = new WorkExperienceModel();
        $educationModel = new EducationModel();
        $certificationModel = new CertificationModel();
        
        $workExperiences = $workExpModel->getByUser($candidateId);
        $education = $educationModel->getByUser($candidateId);
        
        $dbCertifications = $certificationModel->getByUser($candidateId);
        $certifications = [];
        foreach ($dbCertifications as $cert) {
            $certifications[] = [
                'id' => $cert['id'],
                'user_id' => $cert['user_id'],
                'name' => $cert['certification_name'],
                'issuing_organization' => $cert['issuing_organization'],
                'issue_date' => $cert['issue_date'],
                'created_at' => $cert['created_at'],
                'updated_at' => $cert['updated_at']
            ];
        }

        // Optional table
        $db = \Config\Database::connect();
        $projects = [];
        if ($db->tableExists('candidate_projects')) {
            $projectModel = new CandidateProjectModel();
            $dbProjects = $projectModel->getByUser((int) $candidateId);
            foreach ($dbProjects as $proj) {
                $projects[] = [
                    'id' => $proj['id'],
                    'user_id' => $proj['user_id'],
                    'title' => $proj['project_name'],
                    'project_url' => $proj['project_url'],
                    'description' => $proj['project_summary'],
                    'created_at' => $proj['created_at'],
                    'updated_at' => $proj['updated_at']
                ];
            }
        }

        // Application stats
        $applicationModel = model('ApplicationModel');
        $bookingModel = model('InterviewBookingModel');
        
        $totalApplications = $applicationModel->where('candidate_id', $candidateId)->countAllResults();
        $totalInterviews = $bookingModel->where('user_id', $candidateId)->countAllResults();
        $totalOffers = $applicationModel->where('candidate_id', $candidateId)
                                      ->whereIn('status', ['selected', 'hired'])
                                      ->countAllResults();

        // Calculate total experience
        $totalExperienceMonths = 0;
        foreach ($workExperiences as $exp) {
            if (empty($exp['start_date'])) continue;
            
            try {
                $startDate = new \DateTime($exp['start_date']);
                $endDate = ($exp['is_current'] || empty($exp['end_date'])) ? new \DateTime() : new \DateTime($exp['end_date']);
                $interval = $startDate->diff($endDate);
                $totalExperienceMonths += ($interval->y * 12) + $interval->m;
            } catch (\Exception $e) {
                // Ignore parse errors for invalid dates
            }
        }

        // Calculate profile completion
        $completionFields = [
            'name' => !empty($user['name']),
            'email' => !empty($user['email']),
            'phone' => !empty($user['phone']),
            'profile_photo' => !empty($user['profile_photo']),
            'resume' => !empty($user['resume_path']),
            'intro_video' => !empty($user['intro_video_path']),
            'github' => !empty($github['github_username']),
            'skills' => !empty($skills['skill_name']),
            'location' => !empty($user['location']),
            'bio' => !empty($user['bio'])
        ];
        
        $completedFields = array_sum($completionFields);
        $totalFields = count($completionFields);
        $completionPercentage = round(($completedFields / $totalFields) * 100);

        return $this->respond([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'github' => $github,
                'skills' => $skills,
                'interests' => $interests,
                'workExperiences' => $workExperiences,
                'education' => $education,
                'certifications' => $certifications,
                'projects' => $projects,
                'stats' => [
                    'applications' => $totalApplications,
                    'interviews'   => $totalInterviews,
                    'offers'       => $totalOffers
                ],
                'completion' => [
                    'percentage' => $completionPercentage,
                ],
                'profileStrength' => $this->calculateProfileStrength($candidateId),
                'totalExperienceMonths' => $totalExperienceMonths,
                'isFresherCandidate' => (int)($user['is_fresher_candidate'] ?? 0) === 1,
            ]
        ]);
    }

    public function updatePersonal()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $userId = (int) $json->user_id;
        $userModel = model('UserModel');
        
        $userModel->update($userId, [
            'name' => trim((string) ($json->name ?? '')),
            'email' => trim((string) ($json->email ?? '')),
            'phone' => trim((string) ($json->phone ?? '')),
        ]);
        
        $userModel->upsertCandidateProfile($userId, [
            'location' => trim((string) ($json->location ?? '')),
            'bio' => trim((string) ($json->bio ?? '')),
            'gender' => trim((string) ($json->gender ?? '')),
            'date_of_birth' => trim((string) ($json->date_of_birth ?? '')),
        ]);

        return $this->respond(['status' => 'success', 'message' => 'Personal info updated']);
    }

    public function updateCareer()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $userId = (int) $json->user_id;
        $userModel = model('UserModel');
        
        $userModel->upsertCandidateProfile($userId, [
            'resume_headline' => trim((string) ($json->resume_headline ?? '')),
            'current_salary' => isset($json->current_salary) ? (float)$json->current_salary : null,
            'notice_period' => trim((string) ($json->notice_period ?? '')),
            'is_fresher_candidate' => ($json->is_fresher_candidate ?? false) ? 1 : 0
        ]);
        
        return $this->respond(['status' => 'success', 'message' => 'Career details updated']);
    }

    public function updatePreferences()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $userId = (int) $json->user_id;
        $userModel = model('UserModel');
        
        $userModel->upsertCandidateProfile($userId, [
            'preferred_job_titles' => trim((string) ($json->preferred_job_titles ?? '')),
            'preferred_locations' => trim((string) ($json->preferred_locations ?? '')),
            'preferred_employment_type' => trim((string) ($json->preferred_employment_type ?? '')),
            'expected_salary' => isset($json->expected_salary) ? (float)$json->expected_salary : null,
        ]);

        return $this->respond(['status' => 'success', 'message' => 'Preferences updated']);
    }

    public function addSkill()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $userId = (int) $json->user_id;
        $skillName = trim((string) ($json->skill_name ?? ''));

        if (empty($skillName)) {
            return $this->fail('Skill name is required');
        }

        $skillsModel = model('CandidateSkillsModel');
        $existing = $skillsModel->where('candidate_id', $userId)->first();

        if ($existing) {
            $currentSkills = array_filter(array_map('trim', explode(',', $existing['skill_name'])));
            if (!in_array($skillName, $currentSkills)) {
                $currentSkills[] = $skillName;
                $skillsModel->update((int)$existing['id'], [
                    'skill_name' => implode(', ', $currentSkills)
                ]);
            }
        } else {
            $skillsModel->insert([
                'candidate_id' => $userId,
                'skill_name' => $skillName
            ]);
        }

        return $this->respond(['status' => 'success', 'message' => 'Skill added']);
    }

    public function removeSkill()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $userId = (int) $json->user_id;
        $skillName = trim((string) ($json->skill_name ?? ''));

        $skillsModel = model('CandidateSkillsModel');
        $existing = $skillsModel->where('candidate_id', $userId)->first();

        if ($existing) {
            $currentSkills = array_filter(array_map('trim', explode(',', $existing['skill_name'])));
            $updatedSkills = array_filter($currentSkills, function($skill) use ($skillName) {
                return $skill !== $skillName;
            });
            $skillsModel->update((int)$existing['id'], [
                'skill_name' => implode(', ', $updatedSkills)
            ]);
        }

        return $this->respond(['status' => 'success', 'message' => 'Skill removed']);
    }

    public function analyzeGithub()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id) || !isset($json->github_username)) {
            return $this->failUnauthorized('Missing user_id or github_username');
        }

        $userId = (int) $json->user_id;
        $username = trim((string)$json->github_username);

        $github = new \App\Libraries\GithubAnalyzer();
        $data = $github->analyze($username);

        if (empty($data['languages'])) {
            return $this->fail('GitHub profile not found or API blocked');
        }

        $githubModel = new GithubAnalysisModel();
        $githubModel->where('candidate_id', $userId)->delete();

        $githubModel->insert([
            'candidate_id' => $userId,
            'github_username' => $username,
            'repo_count' => $data['repo_count'],
            'commit_count' => $data['commit_count'],
            'languages_used' => implode(',', $data['languages']),
            'github_score' => min(10, round($data['commit_count'] / 20))
        ]);

        return $this->respond(['status' => 'success', 'message' => 'GitHub profile analyzed successfully']);
    }

    public function updateInterests()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $userId = (int) $json->user_id;
        $interests = trim((string) ($json->interests ?? ''));

        $interestsModel = new CandidateInterestsModel();
        $existing = $interestsModel->where('candidate_id', $userId)->first();

        if ($existing) {
            $interestsModel->update((int)$existing['id'], ['interest' => $interests]);
        } else {
            $interestsModel->insert(['candidate_id' => $userId, 'interest' => $interests]);
        }

        return $this->respond(['status' => 'success', 'message' => 'Interests updated']);
    }

    public function saveExperience()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) return $this->failUnauthorized('Missing user_id');

        $model = new WorkExperienceModel();
        $data = [
            'user_id' => (int)$json->user_id,
            'job_title' => $json->job_title ?? '',
            'company_name' => $json->company_name ?? '',
            'start_date' => $json->start_date ?? '',
            'end_date' => !empty($json->end_date) ? $json->end_date : null,
            'is_current' => ($json->is_current ?? false) ? 1 : 0,
            'description' => $json->description ?? ''
        ];

        if (isset($json->id) && $json->id > 0) {
            $model->update((int)$json->id, $data);
        } else {
            $model->insert($data);
        }
        return $this->respond(['status' => 'success', 'message' => 'Experience saved']);
    }

    public function deleteExperience($id)
    {
        $model = new WorkExperienceModel();
        $model->delete((int)$id);
        return $this->respond(['status' => 'success', 'message' => 'Experience deleted']);
    }

    public function saveProject()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) return $this->failUnauthorized('Missing user_id');

        $model = new CandidateProjectModel();
        $data = [
            'user_id' => (int)$json->user_id,
            'project_name' => $json->title ?? '',
            'project_url' => $json->project_url ?? '',
            'project_summary' => $json->description ?? ''
        ];

        if (isset($json->id) && $json->id > 0) {
            $model->update((int)$json->id, $data);
        } else {
            $model->insert($data);
        }
        return $this->respond(['status' => 'success', 'message' => 'Project saved']);
    }

    public function deleteProject($id)
    {
        $model = new CandidateProjectModel();
        $model->delete((int)$id);
        return $this->respond(['status' => 'success', 'message' => 'Project deleted']);
    }

    public function saveEducation()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) return $this->failUnauthorized('Missing user_id');

        $model = new EducationModel();
        $data = [
            'user_id' => (int)$json->user_id,
            'degree' => $json->degree ?? '',
            'field_of_study' => $json->field_of_study ?? '',
            'institution' => $json->institution ?? '',
            'start_year' => !empty($json->start_year) ? (int)$json->start_year : null,
            'end_year' => !empty($json->end_year) ? (int)$json->end_year : null,
            'grade' => $json->grade ?? ''
        ];

        if (isset($json->id) && $json->id > 0) {
            $model->update((int)$json->id, $data);
        } else {
            $model->insert($data);
        }
        return $this->respond(['status' => 'success', 'message' => 'Education saved']);
    }

    public function deleteEducation($id)
    {
        $model = new EducationModel();
        $model->delete((int)$id);
        return $this->respond(['status' => 'success', 'message' => 'Education deleted']);
    }

    public function saveCertification()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) return $this->failUnauthorized('Missing user_id');

        $model = new CertificationModel();
        $data = [
            'user_id' => (int)$json->user_id,
            'certification_name' => $json->name ?? '',
            'issuing_organization' => $json->issuing_organization ?? '',
            'issue_date' => !empty($json->issue_date) ? $json->issue_date : null
        ];

        if (isset($json->id) && $json->id > 0) {
            $model->update((int)$json->id, $data);
        } else {
            $model->insert($data);
        }
        return $this->respond(['status' => 'success', 'message' => 'Certification saved']);
    }

    public function deleteCertification($id)
    {
        $model = new CertificationModel();
        $model->delete((int)$id);
        return $this->respond(['status' => 'success', 'message' => 'Certification deleted']);
    }

    public function uploadResume()
    {
        $userId = $this->request->getPost('user_id');
        if (!$userId) return $this->failUnauthorized('Missing user_id');

        $file = $this->request->getFile('resume');
        if (!$file || !$file->isValid()) return $this->fail('Invalid or missing resume file');

        $allowedTypes = ['pdf', 'doc', 'docx'];
        if (!in_array(strtolower($file->getExtension()), $allowedTypes)) {
            return $this->fail('Only PDF, DOC, DOCX files allowed');
        }

        $uploadPath = FCPATH . 'uploads/resumes/';
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

        $newName = $userId . '_' . time() . '.' . $file->getExtension();
        if (!$file->move($uploadPath, $newName)) {
            return $this->fail('File upload failed');
        }

        // Copy file to WRITEPATH for synchronization with the website dashboard
        $writePath = WRITEPATH . 'uploads/resumes/';
        if (!is_dir($writePath)) {
            mkdir($writePath, 0755, true);
        }
        @copy($uploadPath . $newName, $writePath . $newName);

        $userModel = model('UserModel');
        $resumePath = 'uploads/resumes/' . $newName;
        $userModel->upsertCandidateProfile((int)$userId, ['resume_path' => $resumePath]);

        return $this->respond(['status' => 'success', 'message' => 'Resume uploaded successfully', 'path' => $resumePath]);
    }

    public function uploadVideo()
    {
        $userId = $this->request->getPost('user_id');
        if (!$userId) return $this->failUnauthorized('Missing user_id');

        $file = $this->request->getFile('intro_video');
        if (!$file || !$file->isValid()) return $this->fail('Invalid or missing video file');

        $allowedTypes = ['mp4', 'webm', 'mov'];
        if (!in_array(strtolower($file->getExtension()), $allowedTypes)) {
            return $this->fail('Only MP4, WEBM, MOV files allowed');
        }

        $uploadPath = FCPATH . 'uploads/videos/';
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

        $newName = $userId . '_' . time() . '.' . $file->getExtension();
        if (!$file->move($uploadPath, $newName)) {
            return $this->fail('Video upload failed');
        }

        $userModel = model('UserModel');
        $videoPath = 'uploads/videos/' . $newName;
        $userModel->upsertCandidateProfile((int)$userId, ['intro_video_path' => $videoPath]);

        return $this->respond(['status' => 'success', 'message' => 'Video uploaded successfully', 'path' => $videoPath]);
    }

    public function uploadPhoto()
    {
        $userId = $this->request->getPost('user_id');
        if (!$userId) return $this->failUnauthorized('Missing user_id');

        $file = $this->request->getFile('profile_photo');
        if (!$file || !$file->isValid()) return $this->fail('Invalid or missing photo file');

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($file->getExtension()), $allowedTypes)) {
            return $this->fail('Only JPG, JPEG, PNG, GIF, WEBP files allowed');
        }

        $uploadPath = FCPATH . 'uploads/profiles/';
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

        $newName = $userId . '_' . time() . '.' . $file->getExtension();
        if (!$file->move($uploadPath, $newName)) {
            return $this->fail('File upload failed');
        }

        $userModel = model('UserModel');
        $photoPath = 'uploads/profiles/' . $newName;
        $userModel->upsertCandidateProfile((int)$userId, ['profile_photo' => $photoPath]);

        return $this->respond([
            'status' => 'success',
            'message' => 'Profile photo uploaded successfully',
            'path' => $photoPath
        ]);
    }

    public function deletePhoto()
    {
        $userId = $this->request->getVar('user_id');
        if (!$userId) return $this->failUnauthorized('Missing user_id');

        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile((int) $userId) ?? $userModel->find($userId);

        if ($user && !empty($user['profile_photo'])) {
            $photoPath = FCPATH . $user['profile_photo'];
            if (is_file($photoPath)) {
                @unlink($photoPath);
            }
        }

        $userModel->upsertCandidateProfile((int)$userId, ['profile_photo' => '']);

        return $this->respond([
            'status' => 'success',
            'message' => 'Profile photo deleted successfully'
        ]);
    }

    private function calculateProfileStrength(int $candidateId): int
    {
        $userModel = model('UserModel');
        $skillsModel = new CandidateSkillsModel();
        $user = $userModel->findCandidateWithProfile($candidateId) ?? [];
        $skillsRow = $skillsModel->where('candidate_id', $candidateId)->first() ?? [];

        $profileFields = [
            !empty($user['name']),
            !empty($user['email']),
            !empty($user['phone']),
            !empty($user['profile_photo']),
            !empty($user['resume_path']),
            !empty($user['bio']),
            !empty($user['location']),
            !empty($user['preferred_job_titles']),
            !empty($user['preferred_locations']),
            !empty($user['preferred_employment_type']),
            !empty($skillsRow['skill_name']),
        ];

        $filled = array_sum($profileFields);
        $total = max(1, count($profileFields));

        return (int) round(($filled / $total) * 100);
    }

    public function updateSettings()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $userId = (int) $json->user_id;
        $userModel = model('UserModel');

        $data = [];
        if (isset($json->allow_public_recruiter_visibility)) {
            $data['allow_public_recruiter_visibility'] = $json->allow_public_recruiter_visibility ? 1 : 0;
        }
        if (isset($json->job_alerts_enabled)) {
            $data['job_alerts_enabled'] = $json->job_alerts_enabled ? 1 : 0;
        }
        if (isset($json->job_alert_notify_in_app)) {
            $data['job_alert_notify_in_app'] = $json->job_alert_notify_in_app ? 1 : 0;
        }
        if (isset($json->job_alert_notify_email)) {
            $data['job_alert_notify_email'] = $json->job_alert_notify_email ? 1 : 0;
        }

        if (!empty($data)) {
            $userModel->upsertCandidateProfile($userId, $data);
            if (isset($data['job_alerts_enabled']) || isset($data['job_alert_notify_in_app']) || isset($data['job_alert_notify_email'])) {
                model('JobAlertModel')->syncFromCandidateProfile($userId);
            }
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Settings updated successfully'
        ]);
    }
}
