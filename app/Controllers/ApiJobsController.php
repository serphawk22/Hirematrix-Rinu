<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\AiResumeCoach;
use App\Libraries\AtsScoreService;
use App\Libraries\AiJobMatcher;
use App\Models\CompanyModel;
use App\Models\CompanyReviewModel;
use App\Models\CandidateResumeVersionModel;
use App\Models\CandidateSkillsModel;
use App\Models\JobModel;
use App\Models\RecruiterJobInvitationModel;
use App\Models\SavedJobModel;
use App\Models\UserModel;

class ApiJobsController extends ResourceController
{
    protected $format = 'json';

    public function getJobs($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $jobModel = new JobModel();
        
        $hasBaseResume = false;
        $primaryResumeId = 0;
        $userProfile = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
        $hasBaseResume = !empty($userProfile['resume_path']);
        
        if (\Config\Database::connect()->tableExists('candidate_resume_versions')) {
            $primary = (new CandidateResumeVersionModel())->where('candidate_id', $candidateId)->where('is_primary', 1)->first();
            $primaryResumeId = (int) ($primary['id'] ?? 0);
        }

        // Get filter inputs
        $search = $this->request->getGet('search');
        $designation = $this->request->getGet('designation');
        $company = $this->request->getGet('company');
        $location = $this->request->getGet('location');
        $category = $this->request->getGet('category');
        $experience_level = $this->request->getGet('experience_level');
        $employment_type = $this->request->getGet('employment_type');
        $work_mode = (string) ($this->request->getGet('work_mode') ?? '');
        $salary_range = (string) ($this->request->getGet('salary_range') ?? '');
        $posted_within = $this->request->getGet('posted_within');
        $sort = $this->request->getGet('sort') ?: 'newest';

        $filters = [
            'search'           => $search,
            'designation'      => $designation,
            'company'          => $company,
            'location'         => $location,
            'category'         => $category,
            'experience_level' => $experience_level,
            'employment_type'  => $employment_type,
            'work_mode'        => $work_mode,
            'salary_range'     => $salary_range,
            'posted_within'    => $posted_within,
            'sort'             => $sort,
        ];

        $builder = $jobModel->where('status', 'open');
        JobModel::applyApplicationDeadlineFilter($builder);

        if (!empty($search)) {
            $builder->groupStart()
                    ->like('title', $search)
                    ->orLike('company', $search)
                    ->orLike('required_skills', $search)
                    ->orLike('description', $search)
                    ->groupEnd();
        }

        if (!empty($designation)) {
            $builder->like('title', $designation);
        }

        if (!empty($company)) {
            $builder->like('company', $company);
        }

        if (!empty($location)) {
            $builder->like('location', $location);
        }

        if (!empty($category)) {
            $builder->like('TRIM(category)', trim((string) $category));
        }

        if (!empty($experience_level)) {
            $expArray = is_string($experience_level) ? explode(',', $experience_level) : $experience_level;
            if (is_array($expArray) && !empty($expArray)) {
                $builder->whereIn('experience_level', $expArray);
            } else {
                $builder->where('experience_level', $experience_level);
            }
        }

        if (!empty($employment_type)) {
            $typeArray = is_string($employment_type) ? explode(',', $employment_type) : $employment_type;
            if (is_array($typeArray) && !empty($typeArray)) {
                $builder->whereIn('employment_type', $typeArray);
            } else {
                $builder->where('employment_type', $employment_type);
            }
        }

        if (!empty($work_mode)) {
            $this->applyWorkModeFilter($builder, $work_mode);
        }

        if (!empty($salary_range)) {
            $this->applySalaryRangeFilter($builder, $salary_range);
        }

        if (!empty($posted_within)) {
            $days = (int) $posted_within;
            $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
        }

        switch ($sort) {
            case 'newest':
                $builder->orderBy('created_at', 'DESC');
                break;
            case 'relevance':
                $builder->orderBy('title', 'ASC');
                break;
            case 'location':
                $builder->orderBy('location', 'ASC');
                break;
        }

        // Paginate results
        $jobs = $builder->paginate(perPage: 20);
        $pager = $jobModel->pager;
        $totalJobs = $pager->getTotal();

        // Get filter options from open jobs
        $locationBuilder = $jobModel->select('location')->where('status', 'open')->where('location IS NOT NULL')->where('location !=', '');
        $categoryBuilder = $jobModel->select('category')->where('status', 'open')->where('category IS NOT NULL')->where('category !=', '');
        $employmentTypeBuilder = $jobModel->select('employment_type')->where('status', 'open')->where('employment_type IS NOT NULL')->where('employment_type !=', '');
        $experienceBuilder = $jobModel->select('experience_level')->where('status', 'open')->where('experience_level IS NOT NULL')->where('experience_level !=', '');

        $locations = array_values(array_filter(array_unique(array_column($locationBuilder->groupBy('location')->findAll(), 'location'))));
        $categories = array_values(array_filter(array_unique(array_column($categoryBuilder->groupBy('category')->findAll(), 'category'))));
        $employmentTypes = array_values(array_filter(array_unique(array_column($employmentTypeBuilder->groupBy('employment_type')->findAll(), 'employment_type'))));
        $experienceLevels = array_values(array_filter(array_unique(array_column($experienceBuilder->groupBy('experience_level')->findAll(), 'experience_level'))));

        // Recommendations
        $suggestedJobsByApplies = [];
        $suggestedJobsBySkills = [];
        $suggestedJobsByPreferences = [];
        $suggestedJobsByAi = [];
        
        $candidateSkills = [];
        $candidateInterests = [];
        $behavior = [];

        $skillsModel = new CandidateSkillsModel();
        $interestsModel = new \App\Models\CandidateInterestsModel();

        $skillRow = $skillsModel->where('candidate_id', $candidateId)->first();
        if ($skillRow && !empty($skillRow['skill_name'])) {
            $candidateSkills = array_values(
                array_filter(array_map('trim', explode(',', (string) $skillRow['skill_name'])))
            );
        }

        $interestRow = $interestsModel->where('candidate_id', $candidateId)->first();
        if ($interestRow && !empty($interestRow['interest'])) {
            $candidateInterests = array_values(
                array_filter(array_map('trim', explode(',', (string) $interestRow['interest'])))
            );
        }

        $behavior = $jobModel->getCandidateBehaviorProfile($candidateId);

        $suggestedJobsBySkills = $jobModel->getSuggestedJobsBasic($candidateId, 20);
        $suggestedJobsByApplies = $this->rankJobsByApplicationBehavior($candidateId, $behavior, 20);
        $suggestedJobsByPreferences = $this->rankJobsByPreferences($candidateId, $behavior, $candidateInterests, 20);
        $aiPrimarySuggestions = (new AiJobMatcher())->generateSuggestions($candidateId, 20);
        $suggestedJobsByAi = $this->buildOtherRecommendations(
            $aiPrimarySuggestions,
            $suggestedJobsBySkills,
            $suggestedJobsByPreferences,
            20
        );

        // Fetch company logos
        $companyIds = [];
        $jobSets = [$jobs, $suggestedJobsBySkills, $suggestedJobsByApplies, $suggestedJobsByPreferences, $suggestedJobsByAi];
        foreach ($jobSets as $set) {
            foreach ($set as $job) {
                $id = (int) ($job['company_id'] ?? 0);
                if ($id > 0) {
                    $companyIds[] = $id;
                }
            }
        }
        $companyIds = array_values(array_unique($companyIds));

        $companyLogoMap = [];
        if (!empty($companyIds)) {
            $companies = (new CompanyModel())
                ->select('id, logo')
                ->whereIn('id', $companyIds)
                ->findAll();
            foreach ($companies as $company) {
                $logo = (string) ($company['logo'] ?? '');
                if ($logo !== '' && !preg_match('/^https?:\/\//i', $logo)) {
                    $logo = base_url(ltrim($logo, '/'));
                }
                $companyLogoMap[(int) $company['id']] = $logo;
            }
        }

        $applyLogosAndClientInfo = function (array $jobSet) use ($companyLogoMap): array {
            foreach ($jobSet as $index => $job) {
                $id = (int) ($job['company_id'] ?? 0);
                $jobSet[$index]['company_logo'] = $companyLogoMap[$id] ?? '';

                if (($job['posted_for'] ?? '') === 'client') {
                    if (($job['client_disclosure'] ?? '') === 'visible' && !empty($job['client_company_name'])) {
                        $jobSet[$index]['company'] = $job['client_company_name'];
                    } else {
                        $jobSet[$index]['company'] = ($job['company'] ?? 'Recruiter') . ' (Hiring for a Client)';
                    }
                }
            }
            return $jobSet;
        };

        $jobs = $applyLogosAndClientInfo($jobs);
        $suggestedJobsBySkills = $applyLogosAndClientInfo($suggestedJobsBySkills);
        $suggestedJobsByApplies = $applyLogosAndClientInfo($suggestedJobsByApplies);
        $suggestedJobsByPreferences = $applyLogosAndClientInfo($suggestedJobsByPreferences);
        $suggestedJobsByAi = $applyLogosAndClientInfo($suggestedJobsByAi);

        // Calculate saved job IDs
        $savedJobIds = [];
        $displayJobIds = [];
        foreach ($jobSets as $set) {
            foreach ($set as $job) {
                $displayJobIds[] = (int) ($job['id'] ?? 0);
            }
        }
        $displayJobIds = array_values(array_filter(array_unique($displayJobIds)));

        if (!empty($displayJobIds)) {
            $savedRows = (new SavedJobModel())
                ->select('job_id')
                ->where('candidate_id', $candidateId)
                ->whereIn('job_id', $displayJobIds)
                ->findAll();
            $savedJobIds = array_map('intval', array_column($savedRows, 'job_id'));
        }

        return $this->respond([
            'status' => 'success',
            'data' => [
                'jobs' => $jobs,
                'totalJobs' => $totalJobs,
                'candidateSkills' => $candidateSkills,
                'candidateInterests' => $candidateInterests,
                'savedJobIds' => $savedJobIds,
                'filterOptions' => [
                    'locations' => $locations,
                    'categories' => $categories,
                    'experienceLevels' => $experienceLevels,
                    'employmentTypes' => $employmentTypes,
                ],
                'recommendations' => [
                    'skills' => $suggestedJobsBySkills,
                    'applies' => $suggestedJobsByApplies,
                    'preferences' => $suggestedJobsByPreferences,
                    'ai' => $suggestedJobsByAi,
                ],
                'hasBaseResume' => $hasBaseResume,
                'primaryResumeId' => $primaryResumeId,
            ]
        ]);
    }

    public function saveJob()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->candidate_id) || !isset($json->job_id)) {
            return $this->fail('Invalid payload');
        }

        $candidateId = (int) $json->candidate_id;
        $jobId = (int) $json->job_id;
        $isExternal = !empty($json->is_external);

        $savedJobModel = new SavedJobModel();
        $builder = $savedJobModel->where('candidate_id', $candidateId);
        if ($isExternal) {
            $builder->where('mnc_external_job_id', $jobId);
        } else {
            $builder->where('job_id', $jobId);
        }
        $alreadySaved = $builder->first();

        if (!$alreadySaved) {
            $insertData = ['candidate_id' => $candidateId];
            if ($isExternal) {
                $insertData['mnc_external_job_id'] = $jobId;
            } else {
                $insertData['job_id'] = $jobId;
            }
            $savedJobModel->insert($insertData);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Job saved successfully'
        ]);
    }

    public function unsaveJob()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->candidate_id) || !isset($json->job_id)) {
            return $this->fail('Invalid payload');
        }

        $candidateId = (int) $json->candidate_id;
        $jobId = (int) $json->job_id;
        $isExternal = !empty($json->is_external);

        $builder = (new SavedJobModel())->where('candidate_id', $candidateId);
        if ($isExternal) {
            $builder->where('mnc_external_job_id', $jobId);
        } else {
            $builder->where('job_id', $jobId);
        }
        $builder->delete();

        return $this->respond([
            'status' => 'success',
            'message' => 'Job unsaved successfully'
        ]);
    }

    public function generateAiCoverLetter()
    {
        try {
            $candidateId = (int) $this->request->getGet('candidate_id');
            $jobId = (int) $this->request->getGet('job_id');

            if ($candidateId <= 0 || $jobId <= 0) {
                return $this->fail('Missing Job or User ID', 400);
            }

            $jobModel = new JobModel();
            $job = $jobModel->find($jobId);
            
            $userModel = new UserModel();
            $profile = $userModel->findCandidateWithProfile($candidateId);

            if (!$job || !$profile) {
                return $this->fail('Data not found', 404);
            }

            $apiKey = defined('OPENAI_KEY') ? OPENAI_KEY : getenv('OPENAI_API_KEY');
            $model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o';
            if (!$apiKey) {
                return $this->fail('AI Service unavailable', 500);
            }

            // Gather candidate context
            $skillsRow = (new CandidateSkillsModel())->where('candidate_id', $candidateId)->first();
            $skills = $skillsRow['skill_name'] ?? 'Relevant industry skills';
            
            $prompt = "Act as a professional career coach. Write a highly persuasive, tailored cover letter for:
            JOB TITLE: {$job['title']}
            COMPANY: {$job['company']}
            JOB DESCRIPTION: " . substr($job['description'], 0, 1000) . "
            
            CANDIDATE STRENGTHS: {$skills}
            CANDIDATE BIO: {$profile['bio']}
            
            INSTRUCTIONS:
            1. Use a professional yet enthusiastic tone.
            2. Length: 250-300 words.
            3. Focus on how the candidate's skills specifically solve the needs mentioned in the job description.
            4. Do not use placeholders like '[Name]'; write it as a ready-to-edit draft.
            5. Format with clear paragraphs.";

            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . trim((string)$apiKey),
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7
                ]),
                CURLOPT_TIMEOUT => 60,
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            $content = $data['choices'][0]['message']['content'] ?? '';

            return $this->respond([
                'success' => true,
                'cover_letter' => trim($content),
                'job_title' => $job['title'],
                'company' => $job['company']
            ]);

        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    public function analyzeAtsMatch()
    {
        try {
            $candidateId = (int) $this->request->getGet('candidate_id');
            $jobId = (int) $this->request->getGet('job_id');
            $resumeVersionId = (int) $this->request->getGet('resume_id');

            if ($candidateId <= 0 || $jobId <= 0) {
                return $this->fail('Invalid parameters (Missing user or job ID)', 400);
            }

            $jobModel = new JobModel();
            $job = $jobModel->find($jobId);

            if (!$job) {
                return $this->fail('Job description not found.', 404);
            }

            $userModel = new UserModel();
            $user = $userModel->findCandidateWithProfile($candidateId);
            if (empty($user['resume_path'])) {
                return $this->fail('No resume content found. Please upload a resume first.', 404);
            }

            $resumeCoach = $this->buildResumeCoach($candidateId, $job, $resumeVersionId);

            $result = [
                'success' => true,
                'score' => (int) ($resumeCoach['score'] ?? 0),
                'resume_version_title' => (string) ($resumeCoach['resume_version']['title'] ?? 'Primary Resume'),
                'matched_skills' => (array) ($resumeCoach['matched_skills'] ?? []),
                'missing_skills' => (array) ($resumeCoach['missing_skills'] ?? []),
                'suggestions' => (array) ($resumeCoach['suggestions'] ?? []),
                'summary_suggestion' => (string) ($resumeCoach['summary_suggestion'] ?? ''),
                'gap' => (string) ($resumeCoach['gap'] ?? 'No major gaps found.'),
            ];

            return $this->respond($result);

        } catch (\Throwable $e) {
            log_message('error', '[ATS Analysis Exception] ' . $e->getMessage());
            return $this->fail('Server Exception: ' . $e->getMessage(), 500);
        }
    }

    public function getJobInvitation()
    {
        try {
            $candidateId = (int) $this->request->getGet('candidate_id');
            $jobId = (int) $this->request->getGet('job_id');

            if ($candidateId <= 0 || $jobId <= 0) {
                return $this->fail('Invalid candidate or job ID', 400);
            }

            $db = \Config\Database::connect();
            if (!$db->tableExists('recruiter_job_invitations')) {
                return $this->respond([
                    'status' => 'success',
                    'data' => null
                ]);
            }

            $invitationModel = new RecruiterJobInvitationModel();
            $invitation = $invitationModel->getLatestForCandidateJob($candidateId, $jobId);

            if (!$invitation) {
                return $this->respond([
                    'status' => 'success',
                    'data' => null
                ]);
            }

            // If it was sent, mark as viewed
            if (($invitation['status'] ?? '') === RecruiterJobInvitationModel::STATUS_SENT) {
                $invitationModel->markViewed((int) $invitation['id']);
                $invitation['status'] = RecruiterJobInvitationModel::STATUS_VIEWED;
            }

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'id' => (int) $invitation['id'],
                    'recruiter_id' => (int) $invitation['recruiter_id'],
                    'recruiter_name' => (string) ($invitation['recruiter_name'] ?? 'A recruiter'),
                    'status' => (string) $invitation['status'],
                    'message' => (string) ($invitation['message'] ?? 'A recruiter believes your profile aligns with this role.'),
                    'created_at' => (string) $invitation['created_at'],
                ]
            ]);

        } catch (\Throwable $e) {
            return $this->fail('Server Exception: ' . $e->getMessage(), 500);
        }
    }

    public function getCompanyProfile($id)
    {
        try {
            $companyModel = new CompanyModel();
            $jobModel = new JobModel();
            $companyReviewModel = new CompanyReviewModel();

            $company = $companyModel->find($id);
            if (!$company) {
                $userModel = new UserModel();
                $recruiter = $userModel->findRecruiterWithProfile((int) $id);
                if ($recruiter) {
                    $companyId = (int) ($recruiter['company_id'] ?? 0);
                    if ($companyId > 0) {
                        $company = $companyModel->find($companyId);
                    }
                }
            }

            if (!$company) {
                return $this->failNotFound('Company profile not found.');
            }

            $companyId = (int) $company['id'];

            // Fetch open jobs
            $openJobs = $jobModel->where('company_id', $companyId)->where('status', 'open')->orderBy('created_at', 'DESC')->findAll(10);
            
            // Format company logo
            $logo = (string) ($company['logo'] ?? '');
            if ($logo !== '' && !preg_match('/^https?:\/\//i', $logo)) {
                $logo = base_url(ltrim($logo, '/'));
            }
            $company['logo_url'] = $logo;

            // Format workplace photos
            $photos = [];
            $photosRaw = $company['workplace_photos'] ?? '';
            if (is_string($photosRaw) && trim($photosRaw) !== '') {
                $decoded = json_decode($photosRaw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $p) {
                        $p = trim((string) $p);
                        if ($p !== '') {
                            if (!preg_match('/^https?:\/\//i', $p)) {
                                $p = base_url(ltrim($p, '/'));
                            }
                            $photos[] = $p;
                        }
                    }
                }
            }
            $company['workplace_photos_urls'] = $photos;

            // Review summary
            $reviewSummary = $companyReviewModel
                ->select('COUNT(*) as total_reviews, AVG(rating) as average_rating')
                ->where('company_id', $companyId)
                ->where('status', 'published')
                ->first();

            $reviews = $companyReviewModel
                ->select('company_reviews.*, users.name as candidate_name')
                ->join('users', 'users.id = company_reviews.candidate_id', 'left')
                ->where('company_reviews.company_id', $companyId)
                ->where('company_reviews.status', 'published')
                ->orderBy('company_reviews.updated_at', 'DESC')
                ->findAll(8);

            $candidateId = (int) $this->request->getGet('candidate_id');
            $canInterviewReview = false;
            $canEmployeeReview = false;
            if ($candidateId > 0) {
                $db = \Config\Database::connect();
                $canInterviewReview = $db->table('applications')
                    ->join('jobs', 'jobs.id = applications.job_id', 'inner')
                    ->where('applications.candidate_id', $candidateId)
                    ->where('jobs.company_id', $companyId)
                    ->where('applications.status !=', 'withdrawn')
                    ->countAllResults() > 0;

                $canEmployeeReview = $db->table('applications')
                    ->join('jobs', 'jobs.id = applications.job_id', 'inner')
                    ->where('applications.candidate_id', $candidateId)
                    ->where('jobs.company_id', $companyId)
                    ->whereIn('applications.status', ['selected', 'hired'])
                    ->countAllResults() > 0;
            }

            $currentUserReview = null;
            if ($candidateId > 0) {
                $currentUserReview = $companyReviewModel
                    ->where('company_id', $companyId)
                    ->where('candidate_id', $candidateId)
                    ->first();
            }

            return $this->respond([
                'success' => true,
                'company' => $company,
                'open_jobs' => $openJobs,
                'review_summary' => [
                    'total_reviews' => (int) ($reviewSummary['total_reviews'] ?? 0),
                    'average_rating' => (float) ($reviewSummary['average_rating'] ?? 0.0),
                ],
                'reviews' => $reviews,
                'eligibility' => [
                    'can_interview_review' => $canInterviewReview,
                    'can_employee_review' => $canEmployeeReview,
                ],
                'current_user_review' => $currentUserReview
            ]);

        } catch (\Throwable $e) {
            return $this->fail('Server Exception: ' . $e->getMessage(), 500);
        }
    }

    public function submitCompanyReview($companyId)
    {
        try {
            $companyId = (int) $companyId;
            $json = $this->request->getJSON();
            if (!$json) {
                return $this->fail('Invalid JSON payload', 400);
            }

            $candidateId = (int) ($json->candidate_id ?? 0);
            if ($candidateId <= 0) {
                return $this->fail('Invalid Candidate ID', 400);
            }

            $companyModel = new CompanyModel();
            $company = $companyModel->find($companyId);
            if (!$company) {
                return $this->failNotFound('Company not found.');
            }

            $rating = (int) ($json->rating ?? 0);
            $reviewType = trim((string) ($json->review_type ?? ''));
            $headline = trim((string) ($json->headline ?? ''));
            $reviewText = trim((string) ($json->review_text ?? ''));
            $pros = trim((string) ($json->pros ?? ''));
            $cons = trim((string) ($json->cons ?? ''));

            if ($rating < 1 || $rating > 5) {
                return $this->fail('Please select a rating between 1 and 5.', 400);
            }

            if (!in_array($reviewType, ['interview', 'employee'], true)) {
                return $this->fail('Please choose a valid review type (interview or employee).', 400);
            }

            // check eligibility
            $db = \Config\Database::connect();
            $canInterviewReview = $db->table('applications')
                ->join('jobs', 'jobs.id = applications.job_id', 'inner')
                ->where('applications.candidate_id', $candidateId)
                ->where('jobs.company_id', $companyId)
                ->where('applications.status !=', 'withdrawn')
                ->countAllResults() > 0;

            $canEmployeeReview = $db->table('applications')
                ->join('jobs', 'jobs.id = applications.job_id', 'inner')
                ->where('applications.candidate_id', $candidateId)
                ->where('jobs.company_id', $companyId)
                ->whereIn('applications.status', ['selected', 'hired'])
                ->countAllResults() > 0;

            if (!$canInterviewReview) {
                return $this->fail('You can review this company only after applying or interviewing with them.', 403);
            }

            if ($reviewType === 'employee' && !$canEmployeeReview) {
                return $this->fail('Employee reviews are available only for candidates with a selected or hired outcome at this company.', 403);
            }

            if ($headline === '' || mb_strlen($headline) < 4) {
                return $this->fail('Review headline must be at least 4 characters.', 400);
            }

            if ($reviewText === '' || mb_strlen($reviewText) < 20) {
                return $this->fail('Review text must be at least 20 characters.', 400);
            }

            $payload = [
                'company_id' => $companyId,
                'candidate_id' => $candidateId,
                'review_type' => $reviewType,
                'rating' => $rating,
                'headline' => $headline,
                'review_text' => $reviewText,
                'pros' => $pros,
                'cons' => $cons,
                'status' => 'published',
            ];

            $companyReviewModel = new CompanyReviewModel();
            $existingReview = $companyReviewModel
                ->where('company_id', $companyId)
                ->where('candidate_id', $candidateId)
                ->first();

            if ($existingReview) {
                $updated = $companyReviewModel->update((int) $existingReview['id'], $payload);
                if (!$updated) {
                    $errorText = implode(' ', $companyReviewModel->errors());
                    if ($errorText === '') {
                        $dbError = $companyReviewModel->db->error();
                        $errorText = trim((string) ($dbError['message'] ?? 'Unable to update review.'));
                    }
                    return $this->fail($errorText, 500);
                }
                return $this->respond([
                    'success' => true,
                    'message' => 'Your review has been updated.'
                ]);
            }

            $inserted = $companyReviewModel->insert($payload);
            if ($inserted === false) {
                $errorText = implode(' ', $companyReviewModel->errors());
                if ($errorText === '') {
                    $dbError = $companyReviewModel->db->error();
                    $errorText = trim((string) ($dbError['message'] ?? 'Unable to publish review.'));
                }
                return $this->fail($errorText, 500);
            }

            return $this->respond([
                'success' => true,
                'message' => 'Your review has been published.'
            ]);

        } catch (\Throwable $e) {
            return $this->fail('Server Exception: ' . $e->getMessage(), 500);
        }
    }


    private function applyWorkModeFilter($builder, string $workMode): void
    {
        $mode = strtolower(trim($workMode));
        if ($mode === '') {
            return;
        }

        switch ($mode) {
            case 'remote':
                $builder->groupStart()
                    ->like('location', 'Remote')
                    ->orLike('location', 'Work from home')
                    ->orLike('location', 'WFH')
                    ->groupEnd();
                break;

            case 'hybrid':
                $builder->groupStart()
                    ->like('location', 'Hybrid')
                    ->groupEnd();
                break;

            case 'onsite':
            case 'on-site':
            case 'office':
                $builder->groupStart()
                    ->notLike('location', 'Remote')
                    ->notLike('location', 'Hybrid')
                    ->groupEnd();
                break;
        }
    }

    private function applySalaryRangeFilter($builder, string $salaryRange): void
    {
        $range = strtolower(trim($salaryRange));
        if ($range === '') {
            return;
        }

        $salaryExpr = "CAST(TRIM(SUBSTRING_INDEX(REPLACE(COALESCE(salary_range, ''), '-', ' '), ' ', 1)) AS DECIMAL(10,2))";

        switch ($range) {
            case 'under_3':
                $builder->groupStart()
                    ->where('salary_range IS NOT NULL', null, false)
                    ->where('salary_range !=', '')
                    ->where($salaryExpr . ' <', 3, false)
                    ->groupEnd();
                break;

            case '3_5':
                $builder->groupStart()
                    ->where('salary_range IS NOT NULL', null, false)
                    ->where('salary_range !=', '')
                    ->where($salaryExpr . ' >=', 3, false)
                    ->where($salaryExpr . ' <', 5, false)
                    ->groupEnd();
                break;

            case '5_8':
                $builder->groupStart()
                    ->where('salary_range IS NOT NULL', null, false)
                    ->where('salary_range !=', '')
                    ->where($salaryExpr . ' >=', 5, false)
                    ->where($salaryExpr . ' <', 8, false)
                    ->groupEnd();
                break;

            case '8_12':
                $builder->groupStart()
                    ->where('salary_range IS NOT NULL', null, false)
                    ->where('salary_range !=', '')
                    ->where($salaryExpr . ' >=', 8, false)
                    ->where($salaryExpr . ' <', 12, false)
                    ->groupEnd();
                break;

            case '12_plus':
                $builder->groupStart()
                    ->where('salary_range IS NOT NULL', null, false)
                    ->where('salary_range !=', '')
                    ->where($salaryExpr . ' >=', 12, false)
                    ->groupEnd();
                break;
        }
    }

    private function rankJobsByApplicationBehavior(int $candidateId, array $behavior, int $limit): array
    {
        $jobModel = new JobModel();
        $jobsBuilder = $jobModel->where('status', 'open')
            ->whereNotIn('id', static function ($builder) use ($candidateId) {
                return $builder->select('job_id')->from('applications')->where('candidate_id', $candidateId);
            })
            ->orderBy('created_at', 'DESC');

        JobModel::applyApplicationDeadlineFilter($jobsBuilder);
        $jobs = $jobsBuilder->findAll(200);

        $topCategories = array_map('strtolower', array_column((array) ($behavior['top_categories'] ?? []), 'category'));
        $topLocations = array_map('strtolower', array_column((array) ($behavior['top_locations'] ?? []), 'location'));
        $topEmploymentTypes = array_map('strtolower', array_column((array) ($behavior['top_employment_types'] ?? []), 'employment_type'));

        $ranked = [];
        foreach ($jobs as $job) {
            $categoryScore = 0.0;
            $locationScore = 0.0;
            $typeScore = 0.0;

            $jobCategory = strtolower(trim((string) ($job['category'] ?? '')));
            $jobLocation = strtolower(trim((string) ($job['location'] ?? '')));
            $jobType = strtolower(trim((string) ($job['employment_type'] ?? '')));

            if ($jobCategory !== '' && !empty($topCategories)) {
                if (in_array($jobCategory, $topCategories, true)) {
                    $categoryScore = 50.0;
                } else {
                    foreach ($topCategories as $topCat) {
                        if (str_contains($jobCategory, $topCat) || str_contains($topCat, $jobCategory)) {
                            $categoryScore = 35.0;
                            break;
                        }
                    }
                }
            }
            
            foreach ($topLocations as $loc) {
                if ($loc !== '' && (str_contains($jobLocation, $loc) || str_contains($loc, $jobLocation))) {
                    $locationScore = 30.0;
                    break;
                }
            }

            if ($jobType !== '' && in_array($jobType, $topEmploymentTypes, true)) {
                $typeScore = 20.0;
            }

            $totalScore = $categoryScore + $locationScore + $typeScore;
            if ($totalScore <= 15) {
                continue;
            }

            $job['match_score'] = round(min(100, $totalScore), 1);
            $job['match_reason'] = 'Matches your interests in ' . ($job['category'] ?? 'this category') . ' based on your previous applications.';
            $ranked[] = $job;
        }

        usort($ranked, static fn (array $a, array $b): int => ((float) ($b['match_score'] ?? 0.0)) <=> ((float) ($a['match_score'] ?? 0.0)));
        return array_slice($ranked, 0, $limit);
    }

    private function rankJobsByPreferences(int $candidateId, array $behavior, array $candidateInterests, int $limit): array
    {
        $jobModel = new JobModel();
        $userModel = new UserModel();

        $profile = $userModel->findCandidateWithProfile($candidateId) ?? [];
        $preferredJobTitles = array_values(array_filter(array_map(
            'trim',
            array_map(
                'strtolower',
                preg_split('/[,|\\/]+/', (string) ($profile['preferred_job_titles'] ?? '')) ?: []
            )
        )));
        $preferredLocations = array_filter(array_map('trim', explode(',', strtolower((string) ($profile['preferred_locations'] ?? '')))));
        $preferredEmploymentTypes = array_values(array_filter(array_map(
            'trim',
            array_map(
                'strtolower',
                preg_split('/[,|\\/]+/', (string) ($profile['preferred_employment_type'] ?? '')) ?: []
            )
        )));
        $interests = array_values(array_filter(array_map('strtolower', array_map('trim', $candidateInterests))));

        $jobsBuilder = $jobModel->where('status', 'open')
            ->whereNotIn('id', static function ($builder) use ($candidateId) {
                return $builder->select('job_id')->from('applications')->where('candidate_id', $candidateId);
            })
            ->orderBy('created_at', 'DESC');

        JobModel::applyApplicationDeadlineFilter($jobsBuilder);
        $jobs = $jobsBuilder->findAll(200);

        $ranked = [];
        foreach ($jobs as $job) {
            $titleScore = 0.0;
            $interestScore = 0.0;
            $locationScore = 0.0;
            $typeScore = 0.0;

            $blob = strtolower(trim((string) ($job['title'] ?? '') . ' ' . (string) ($job['category'] ?? '') . ' ' . (string) ($job['description'] ?? '')));
            $jobTitle = strtolower(trim((string) ($job['title'] ?? '')));
            
            foreach ($preferredJobTitles as $preferredJobTitle) {
                if ($preferredJobTitle !== '' && (str_contains($jobTitle, $preferredJobTitle) || str_contains($preferredJobTitle, $jobTitle))) {
                    $titleScore = 35.0;
                    break;
                }
            }
            
            foreach ($interests as $interest) {
                if ($interest !== '' && str_contains($blob, $interest)) {
                    $interestScore = 25.0;
                    break;
                }
            }

            $jobLocation = strtolower(trim((string) ($job['location'] ?? '')));
            foreach ($preferredLocations as $loc) {
                if ($loc !== '' && (str_contains($jobLocation, $loc) || str_contains($loc, $jobLocation))) {
                    $locationScore = 25.0;
                    break;
                }
            }

            $jobType = strtolower(trim((string) ($job['employment_type'] ?? '')));
            if ($jobType !== '' && in_array($jobType, $preferredEmploymentTypes, true)) {
                $typeScore = 15.0;
            }

            $totalScore = $titleScore + $interestScore + $locationScore + $typeScore;
            if ($totalScore <= 15) {
                continue;
            }

            $job['match_score'] = round(min(100, $totalScore), 1);
            $job['match_reason'] = 'High alignment with your profile career preferences and interests.';
            $ranked[] = $job;
        }

        usort($ranked, static fn (array $a, array $b): int => ((float) ($b['match_score'] ?? 0.0)) <=> ((float) ($a['match_score'] ?? 0.0)));
        return array_slice($ranked, 0, $limit);
    }

    private function buildOtherRecommendations(array $aiJobs, array $skillsJobs, array $preferencesJobs, int $limit): array
    {
        $merged = [];

        foreach ($aiJobs as $job) {
            $id = (int) ($job['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            if (empty($job['match_reason'])) {
                $job['match_reason'] = 'AI-assisted recommendation';
            }
            $job['match_score'] = (float) ($job['match_score'] ?? 0.0);
            $merged[$id] = $job;
        }

        $secondarySets = [$skillsJobs, $preferencesJobs];
        foreach ($secondarySets as $set) {
            foreach ($set as $job) {
                $id = (int) ($job['id'] ?? 0);
                if ($id <= 0 || isset($merged[$id])) {
                    continue;
                }

                $baseScore = (float) ($job['match_score'] ?? 0.0);
                $job['match_score'] = round(min(100, $baseScore * 0.9), 1);
                $job['match_reason'] = 'Matched using profile relevance signals';
                $merged[$id] = $job;
            }
        }

        $result = array_values($merged);
        usort($result, static fn (array $a, array $b): int => ((float) ($b['match_score'] ?? 0.0)) <=> ((float) ($a['match_score'] ?? 0.0)));
        return array_slice($result, 0, $limit);
    }

    public function getSavedJobs($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $savedRows = (new SavedJobModel())
            ->select('saved_jobs.created_at as saved_at, jobs.*')
            ->join('jobs', 'jobs.id = saved_jobs.job_id', 'inner')
            ->where('saved_jobs.candidate_id', $candidateId)
            ->where('jobs.status', 'open')
            ->orderBy('saved_jobs.created_at', 'DESC')
            ->findAll();

        foreach ($savedRows as $index => $job) {
            $savedRows[$index]['is_external'] = false;
        }

        $savedMncRows = (new SavedJobModel())
            ->select(
                'saved_jobs.created_at as saved_at, ' .
                'mnc_external_jobs.id, ' .
                'mnc_external_jobs.company_name as company, ' .
                'mnc_external_jobs.title, ' .
                'mnc_external_jobs.location, ' .
                'mnc_external_jobs.apply_url, ' .
                'mnc_external_jobs.source_platform, ' .
                'mnc_external_jobs.posted_at_raw, ' .
                'mnc_external_jobs.last_sync_at'
            )
            ->join('mnc_external_jobs', 'mnc_external_jobs.id = saved_jobs.mnc_external_job_id', 'inner')
            ->where('saved_jobs.candidate_id', $candidateId)
            ->where('mnc_external_jobs.is_active', 1)
            ->orderBy('saved_jobs.created_at', 'DESC')
            ->findAll();

        foreach ($savedMncRows as $index => $job) {
            $savedMncRows[$index]['is_external'] = true;
            $savedMncRows[$index]['created_at'] = $job['saved_at'] ?? null;
            $savedMncRows[$index]['employment_type'] = trim((string) ($job['source_platform'] ?? '')) ?: 'External';
        }

        $savedRows = array_merge($savedRows, $savedMncRows);
        usort($savedRows, static function (array $left, array $right): int {
            return strtotime((string) ($right['saved_at'] ?? '')) <=> strtotime((string) ($left['saved_at'] ?? ''));
        });

        $companyIds = [];
        foreach ($savedRows as $job) {
            $id = (int) ($job['company_id'] ?? 0);
            if ($id > 0) {
                $companyIds[] = $id;
            }
        }

        $companyLogoMap = [];
        $companyIds = array_values(array_unique($companyIds));
        if (!empty($companyIds)) {
            $companies = (new CompanyModel())
                ->select('id, logo')
                ->whereIn('id', $companyIds)
                ->findAll();
            foreach ($companies as $company) {
                $companyLogoMap[(int) $company['id']] = (string) ($company['logo'] ?? '');
            }
        }

        foreach ($savedRows as $index => $job) {
            $id = (int) ($job['company_id'] ?? 0);
            $logo = $companyLogoMap[$id] ?? '';
            if ($logo !== '' && !preg_match('#^https?://#i', $logo) && !str_starts_with($logo, '//')) {
                $logo = base_url(ltrim($logo, '/'));
            }
            $savedRows[$index]['company_logo'] = $logo;
        }

        return $this->respond([
            'status' => 'success',
            'data' => $savedRows
        ]);
    }

    public function getFeaturedJobs()
    {
        $jobModel = new JobModel();
        $featuredJobs = $jobModel
            ->where('status', 'open')
            ->orderBy('created_at', 'DESC')
            ->findAll(6);

        $companyIds = [];
        $companyNames = [];
        foreach ($featuredJobs as $job) {
            $id = (int) ($job['company_id'] ?? 0);
            if ($id > 0) {
                $companyIds[] = $id;
            }

            $companyName = trim((string) ($job['company'] ?? ''));
            if ($companyName !== '') {
                $companyNames[] = $companyName;
            }
        }

        $companyLogoMap = [];
        $companyNameLogoMap = [];
        if (!empty($companyIds) || !empty($companyNames)) {
            $companyModel = new CompanyModel();
            $builder = $companyModel->select('id, name, logo');

            if (!empty($companyIds) && !empty($companyNames)) {
                $builder->groupStart()
                    ->whereIn('id', array_values(array_unique($companyIds)))
                    ->orWhereIn('name', array_values(array_unique($companyNames)))
                    ->groupEnd();
            } elseif (!empty($companyIds)) {
                $builder->whereIn('id', array_values(array_unique($companyIds)));
            } else {
                $builder->whereIn('name', array_values(array_unique($companyNames)));
            }

            $companies = $builder->findAll();
            foreach ($companies as $company) {
                $logo = trim((string) ($company['logo'] ?? ''));
                if ($logo === '') {
                    continue;
                }

                if ($logo !== '' && !preg_match('/^https?:\/\//i', $logo)) {
                    $logo = base_url(ltrim($logo, '/'));
                }

                $companyLogoMap[(int) $company['id']] = $logo;
                $name = strtolower(trim((string) ($company['name'] ?? '')));
                if ($name !== '') {
                    $companyNameLogoMap[$name] = $logo;
                }
            }
        }

        $formatAge = static function ($value): string {
            if ($value === null || $value === '') {
                return 'Recently';
            }

            $date = strtotime((string) $value);
            if ($date === false) {
                return 'Recently';
            }

            return date('M d, Y', $date);
        };

        foreach ($featuredJobs as $index => $job) {
            $id = (int) ($job['company_id'] ?? 0);
            if (!empty($companyLogoMap[$id])) {
                $featuredJobs[$index]['company_logo'] = $companyLogoMap[$id];
            } else {
                $jobCompanyName = strtolower(trim((string) ($job['company'] ?? '')));
                $featuredJobs[$index]['company_logo'] = $companyNameLogoMap[$jobCompanyName] ?? '';
            }

            $featuredJobs[$index]['posted_at_formatted'] = $formatAge($job['created_at'] ?? $job['posted_at'] ?? null);
            $featuredJobs[$index]['match_score'] = (int) round((float) ($job['match_score'] ?? 85));
        }

        return $this->respond([
            'status' => 'success',
            'data' => $featuredJobs
        ]);
    }

    public function applyJob($jobId)
    {
        $jobId = (int) $jobId;
        if ($jobId <= 0) {
            return $this->fail('Invalid Job ID');
        }

        $body = $this->request->getJSON(true);
        if (!$body || !isset($body['candidate_id'])) {
            return $this->fail('Invalid payload');
        }

        $candidateId = (int) $body['candidate_id'];
        $jobModel = new JobModel();
        $job = $jobModel
            ->where('id', $jobId)
            ->where('status', 'open')
            ->first();

        if (!$job) {
            return $this->failNotFound('Job not found or no longer open.');
        }

        // Enforce application deadline
        if (!empty($job['application_deadline'])) {
            if (strtotime($job['application_deadline'] . ' 23:59:59') < time()) {
                return $this->fail('The application deadline for this job has passed.', 410);
            }
        }

        if (JobModel::isExternalJob($job)) {
            return $this->fail('This listing uses an external application flow.', 400);
        }

        // Check if resume is uploaded
        $userModel = new UserModel();
        $user = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId);
        
        if (empty($user['resume_path'])) {
            return $this->fail('Please upload your resume to continue your job application.', 422);
        }

        $applicationModel = model('ApplicationModel');
        $alreadyApplied = $applicationModel
            ->where('job_id', $jobId)
            ->where('candidate_id', $candidateId)
            ->where('status !=', 'withdrawn')
            ->first();

        if ($alreadyApplied) {
            return $this->fail('You have already applied for this job.', 409);
        }

        // Skills match check
        $skillsModel = new CandidateSkillsModel();
        $githubModel = model('GithubAnalysisModel');
        
        $candidateSkills = $skillsModel->where('candidate_id', $candidateId)->first();
        $githubStats = $githubModel ? $githubModel->where('candidate_id', $candidateId)->first() : null;
        
        $jobTitle = strtolower($job['title'] ?? '');
        $jobSkills = strtolower($job['required_skills'] ?? '');
        $resumeSkills = strtolower($candidateSkills['skill_name'] ?? '');
        $githubLanguages = strtolower($githubStats['languages_used'] ?? '');
        
        $allCandidateSkills = $resumeSkills . ' ' . $githubLanguages;
        
        $hasJobTitleSkill = stripos($allCandidateSkills, $jobTitle) !== false;
        $hasRequiredSkills = false;
        
        $requiredSkillsList = explode(',', $jobSkills);
        foreach ($requiredSkillsList as $skill) {
            $skill = trim($skill);
            if (!empty($skill) && stripos($allCandidateSkills, $skill) !== false) {
                $hasRequiredSkills = true;
                break;
            }
        }
        
        $mismatch = !empty($jobTitle) && !empty($allCandidateSkills) && 
                    (!$hasJobTitleSkill && !$hasRequiredSkills);

        $aiPolicy = JobModel::normalizeAiPolicy($job['ai_interview_policy'] ?? JobModel::AI_POLICY_REQUIRED_HARD);
        $initialStatus = $aiPolicy === JobModel::AI_POLICY_OFF ? 'shortlisted' : 'applied';
        
        $db = \Config\Database::connect();
        $resumeVersion = null;
        if ($db->tableExists('candidate_resume_versions') && $db->fieldExists('resume_version_id', 'applications')) {
            $resumeVersion = (new CandidateResumeVersionModel())->getPreferredVersionForJob($candidateId, $jobId);
        }

        $payload = [
            'job_id' => $jobId,
            'candidate_id' => $candidateId,
            'status' => $initialStatus,
            'applied_at' => date('Y-m-d H:i:s')
        ];

        // Questionnaire handling
        $questionnaire = [];
        $rawQuestionnaire = (string) ($job['application_questionnaire'] ?? '');
        if (trim($rawQuestionnaire) !== '') {
            $decoded = json_decode($rawQuestionnaire, true);
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    if (!is_array($row)) continue;
                    $id = trim((string) ($row['id'] ?? ''));
                    $label = trim((string) ($row['label'] ?? ''));
                    $type = strtolower(trim((string) ($row['type'] ?? 'textarea')));
                    if ($id === '' || $label === '' || !in_array($type, ['text', 'textarea'], true)) continue;
                    $questionnaire[] = [
                        'id' => $id,
                        'label' => $label,
                        'type' => $type,
                        'placeholder' => trim((string) ($row['placeholder'] ?? '')),
                        'required' => (bool) ($row['required'] ?? false),
                        'knockout' => (bool) ($row['knockout'] ?? false),
                        'knockout_answer' => trim((string) ($row['knockout_answer'] ?? '')),
                        'knockout_match' => strtolower(trim((string) ($row['knockout_match'] ?? 'exact'))),
                    ];
                }
            }
        }

        $questionnaireResponses = [];
        if (!empty($questionnaire) && $db->fieldExists('questionnaire_responses', 'applications')) {
            $rawResponses = $body['questionnaire_responses'] ?? [];
            
            foreach ($questionnaire as $question) {
                $qId = (string) ($question['id'] ?? '');
                $answer = trim((string) ($rawResponses[$qId] ?? ''));

                if (!empty($question['required']) && $answer === '') {
                    return $this->fail('"' . (string) ($question['label'] ?? 'This question') . '" is required.', 422);
                }

                if ($answer === '') {
                    continue;
                }

                if (mb_strlen($answer) > 5000) {
                    return $this->fail('Each application response must be 5000 characters or fewer.', 422);
                }

                // Check knockout
                $knockoutPassed = null;
                if (!empty($question['knockout'])) {
                    $knockoutPassed = $this->answerMatchesKnockout($answer, (string) ($question['knockout_answer'] ?? ''), (string) ($question['knockout_match'] ?? 'exact'));
                }

                $questionnaireResponses[] = [
                    'question_id' => $qId,
                    'label' => (string) ($question['label'] ?? ''),
                    'type' => (string) ($question['type'] ?? 'textarea'),
                    'answer' => $answer,
                    'required' => (bool) ($question['required'] ?? false),
                    'knockout' => (bool) ($question['knockout'] ?? false),
                    'knockout_passed' => $knockoutPassed,
                ];
            }

            $payload['questionnaire_responses'] = !empty($questionnaireResponses) ? json_encode($questionnaireResponses) : null;
        }

        // Find knockout failure
        $knockoutFailed = false;
        foreach ($questionnaireResponses as $resp) {
            if ($resp['knockout'] && !$resp['knockout_passed']) {
                $knockoutFailed = true;
                break;
            }
        }

        if ($knockoutFailed) {
            $payload['status'] = 'filtered_out';
            $initialStatus = 'filtered_out';
        }

        if ($db->fieldExists('resume_version_id', 'applications')) {
            $payload['resume_version_id'] = (int) ($resumeVersion['id'] ?? 0) > 0 ? (int) $resumeVersion['id'] : null;
        }

        $applicationModel->insert($payload);
        if ($db->tableExists('recruiter_job_invitations')) {
            (new RecruiterJobInvitationModel())->markAppliedForCandidateJob($candidateId, $jobId);
        }
        
        $applicationId = $applicationModel->getInsertID();
        $stageModel = model('StageHistoryModel');
        if ($stageModel) {
            $stageModel->moveToStage($applicationId, 'Applied');
            if ($initialStatus === 'filtered_out') {
                $stageModel->moveToStage($applicationId, 'Filtered Out (Knock-out Question)');
            }
            if ($initialStatus === 'shortlisted') {
                $stageModel->moveToStage($applicationId, 'Shortlisted (AI Policy OFF)');
            }
        }

        $displayStatus = ($initialStatus === 'filtered_out') ? 'applied' : $initialStatus;

        $applySuccessMessage = $knockoutFailed
            ? 'Application submitted successfully. It is now under recruiter review.'
            : ($aiPolicy === JobModel::AI_POLICY_OFF 
                ? 'Job applied successfully. This job skips AI interview and moved to shortlist stage.'
                : ($aiPolicy === JobModel::AI_POLICY_OPTIONAL 
                    ? 'Job applied successfully. AI interview is optional for this job.' 
                    : 'Job applied successfully.'));

        return $this->respond([
            'status' => 'success',
            'message' => $applySuccessMessage,
            'data' => [
                'application_id' => $applicationId,
                'status' => $displayStatus,
                'mismatch' => $mismatch,
            ]
        ]);
    }

    private function answerMatchesKnockout(string $answer, string $expectedRaw, string $matchType): bool
    {
        $answer = $this->normalizeScreeningAnswer($answer);
        $expectedAnswers = preg_split('/[,\r\n|;]+/', $expectedRaw) ?: [];
        $expectedAnswers = array_values(array_filter(array_map(fn (string $value): string => $this->normalizeScreeningAnswer($value), $expectedAnswers)));

        if ($answer === '' || empty($expectedAnswers)) {
            return false;
        }

        foreach ($expectedAnswers as $expected) {
            if ($matchType === 'contains') {
                if (str_contains($answer, $expected)) {
                    return true;
                }
                continue;
            }

            if ($answer === $expected) {
                return true;
            }
        }

        return false;
    }

    private function normalizeScreeningAnswer(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9+#.]+/', ' ', $value) ?? '';
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function buildResumeCoach(int $candidateId, array $job, int $resumeVersionId = 0): array
    {
        $user = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
        $skillsRow = (new CandidateSkillsModel())->where('candidate_id', $candidateId)->first();
        
        $resumeVersionModel = new CandidateResumeVersionModel();
        if ($resumeVersionId > 0) {
            $resumeVersion = $resumeVersionModel->find($resumeVersionId);
        } else {
            $resumeVersion = $resumeVersionModel->getPreferredVersionForJob($candidateId, (int) ($job['id'] ?? 0));
        }

        $atsAnalysis = (new AtsScoreService())->analyzeCandidateJob($candidateId, $job, (int) ($resumeVersion['id'] ?? 0));

        $requiredSkills = $this->tokenizeSkills((string) ($job['required_skills'] ?? ''));
        $profileSkills = $this->tokenizeSkills((string) ($skillsRow['skill_name'] ?? ''));
        $resumeSkills = $this->tokenizeSkills((string) ($resumeVersion['highlight_skills'] ?? ''));
        $candidateSkills = array_values(array_unique(array_merge($profileSkills, $resumeSkills)));

        $matchedSkills = array_values(array_intersect($requiredSkills, $candidateSkills));
        $missingSkills = array_values(array_diff($requiredSkills, $candidateSkills));

        $summaryText = strtolower(trim((string) ($resumeVersion['summary'] ?? '')));
        $jobTitle = trim((string) ($job['title'] ?? 'this role'));
        $titleTokens = $this->tokenizeSkills($jobTitle);
        $summaryAlignment = 0;
        foreach ($titleTokens as $token) {
            if ($token !== '' && str_contains($summaryText, strtolower($token))) {
                $summaryAlignment++;
            }
        }

        $profileReadiness = 0;
        if (!empty($user['resume_path'])) {
            $profileReadiness += 6;
        }
        if (!empty($user['bio'])) {
            $profileReadiness += 4;
        }
        if (!empty($user['location'])) {
            $profileReadiness += 2;
        }
        if (!empty($candidateSkills)) {
            $profileReadiness += 3;
        }

        $skillScore = empty($requiredSkills) ? 50 : (int) round((count($matchedSkills) / max(1, count($requiredSkills))) * 50);
        $summaryScore = empty($titleTokens) ? 15 : (int) round((min(count($titleTokens), $summaryAlignment) / max(1, count($titleTokens))) * 20);
        $readinessScore = max(0, min(100, $skillScore + $summaryScore + $profileReadiness + (!empty($resumeVersion) ? 15 : 5)));

        $suggestions = [];
        if (!empty($missingSkills)) {
            $suggestions[] = 'Add missing job keywords like ' . implode(', ', array_slice($missingSkills, 0, 4)) . ' where you have real experience.';
        }
        if ($summaryAlignment === 0) {
            $suggestions[] = 'Rewrite your summary to mention the target role "' . $jobTitle . '" and the strongest matching skills.';
        }
        if (empty($resumeVersion)) {
            $suggestions[] = 'Generate a job-specific AI resume version for this role instead of using a generic resume.';
        }
        if (empty($user['bio'])) {
            $suggestions[] = 'Complete your profile bio so your resume and profile tell the same story to recruiters.';
        }
        if (empty($suggestions)) {
            $suggestions[] = 'Your resume already aligns reasonably well. Focus on sharper achievement bullets and measurable impact.';
        }

        $emphasisSkills = !empty($matchedSkills)
            ? array_slice($matchedSkills, 0, 5)
            : array_slice($requiredSkills, 0, 5);

        $summarySuggestion = 'Tailor your opening summary for ' . $jobTitle . ' by highlighting '
            . (!empty($emphasisSkills) ? implode(', ', array_slice($emphasisSkills, 0, 3)) : 'your most relevant experience')
            . ' and one measurable result from past work.';

        $fallback = [
            'score' => (int) ($atsAnalysis['score'] ?? $readinessScore),
            'required_skills' => $requiredSkills,
            'matched_skills' => !empty($atsAnalysis['matched_skills']) ? (array) $atsAnalysis['matched_skills'] : $matchedSkills,
            'missing_skills' => !empty($atsAnalysis['missing_keywords']) ? (array) $atsAnalysis['missing_keywords'] : $missingSkills,
            'emphasis_skills' => $emphasisSkills,
            'suggestions' => !empty($atsAnalysis['suggestions']) ? (array) $atsAnalysis['suggestions'] : $suggestions,
            'summary_suggestion' => $summarySuggestion,
            'resume_version' => $resumeVersion,
            'source' => 'fallback',
        ];

        $resumeContext = [
            'profile' => [
                'headline' => (string) ($user['resume_headline'] ?? ''),
                'bio' => (string) ($user['bio'] ?? ''),
                'location' => (string) ($user['location'] ?? ''),
                'has_resume' => !empty($user['resume_path']),
            ],
            'profile_skills' => $profileSkills,
            'resume_version' => [
                'title' => (string) ($resumeVersion['title'] ?? ''),
                'target_role' => (string) ($resumeVersion['target_role'] ?? ''),
                'summary' => (string) ($resumeVersion['summary'] ?? ''),
                'highlight_skills' => $resumeSkills,
                'content' => (string) ($resumeVersion['content'] ?? ''),
            ],
            'candidate_skills' => $candidateSkills,
        ];

        $coach = (new AiResumeCoach())->generate($candidateId, $job, $resumeContext, $fallback);
        $coach['score'] = (int) ($atsAnalysis['score'] ?? $fallback['score']);
        $coach['resume_version'] = $resumeVersion;

        return $coach;
    }

    private function tokenizeSkills(string $value): array
    {
        $parts = preg_split('/[,|\\/]+/', strtolower($value)) ?: [];
        $tokens = [];

        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed !== '') {
                $tokens[] = $trimmed;
            }
        }

        return array_values(array_unique($tokens));
    }
}
