<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\AiResumeCoach;
use App\Libraries\AtsScoreService;
use App\Libraries\AiJobMatcher;
use App\Models\CompanyModel;
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
            $analysis = (new AtsScoreService())->analyzeCandidateJob($candidateId, $job, $resumeVersionId);
            if (!($analysis['has_resume_source'] ?? false)) {
                return $this->fail('No resume content found. Please upload a resume first.', 404);
            }

            $result = [
                'success' => true,
                'score' => (int) ($analysis['score'] ?? 0),
                'keywords' => (array) ($analysis['missing_keywords'] ?? []),
                'suggestions' => (array) ($analysis['suggestions'] ?? []),
                'gap' => (string) ($analysis['critical_gap'] ?? 'No major gaps found.'),
            ];

            return $this->respond($result);

        } catch (\Throwable $e) {
            log_message('error', '[ATS Analysis Exception] ' . $e->getMessage());
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
}
