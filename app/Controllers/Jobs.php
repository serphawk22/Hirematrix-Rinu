<?php

namespace App\Controllers;

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

class Jobs extends BaseController
{
    public function index()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }

        $jobModel = new JobModel();
        $candidateId = (int) session()->get('user_id');
        $db = \Config\Database::connect();

        // Get resume information for ATS analysis
        $hasBaseResume = false;
        $primaryResumeId = 0;
        if ($candidateId > 0) {
            $userProfile = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
            $hasBaseResume = !empty($userProfile['resume_path']);
            
            if ($db->tableExists('candidate_resume_versions')) {
                $primary = (new CandidateResumeVersionModel())->where('candidate_id', $candidateId)->where('is_primary', 1)->first();
                $primaryResumeId = (int) ($primary['id'] ?? 0);
            }
        }

        $filters = [
            'search'           => $this->request->getGet('search'),
            'designation'      => $this->request->getGet('designation'),
            'company'          => $this->request->getGet('company'),
            'location'         => $this->request->getGet('location'),
            'category'         => $this->request->getGet('category'),
            'experience_level' => $this->request->getGet('experience_level'),
            'employment_type'  => $this->request->getGet('employment_type'),
            'work_mode'        => (string) ($this->request->getGet('work_mode') ?? $this->request->getGet('remote') ?? ''),
            'salary_range'     => (string) ($this->request->getGet('salary_range') ?? ''),
            'posted_within'    => $this->request->getGet('posted_within'),
            'skills_match'     => $this->request->getGet('skills_match'),
            'sort'             => $this->request->getGet('sort') ?: 'newest',
        ];
        $filters['employment_type'] = $this->normalizeSelectedFilterValues($filters['employment_type'], 'employment');
        $filters['experience_level'] = $this->normalizeSelectedFilterValues($filters['experience_level'], 'experience');

        $filterContextKeys = [
            'search',
            'designation',
            'company',
            'location',
            'category',
            'experience_level',
            'employment_type',
            'work_mode',
            'salary_range',
            'posted_within',
            'skills_match',
        ];
        $showFilters = false;
        foreach ($filterContextKeys as $searchKey) {
            $value = $filters[$searchKey] ?? null;
            if (is_array($value) && !empty(array_filter($value, static fn ($v) => trim((string) $v) !== ''))) {
                $showFilters = true;
                break;
            }
            if (!is_array($value) && trim((string) $value) !== '') {
                $showFilters = true;
                break;
            }
        }
        $activeTab = $showFilters ? 'all' : 'recommended';

        $builder = $jobModel->where('status', 'open');
        
        // Implicitly treat expired jobs as closed by hiding them from search
        JobModel::applyApplicationDeadlineFilter($builder);

        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('title', $filters['search'])
                    ->orLike('company', $filters['search'])
                    ->orLike('required_skills', $filters['search'])
                    ->orLike('description', $filters['search'])
                    ->groupEnd();
        }

        if (!empty($filters['designation'])) {
            $builder->like('title', $filters['designation']);
        }

        if (!empty($filters['company'])) {
            $builder->like('company', $filters['company']);
        }

        if (!empty($filters['location'])) {
            $builder->like('location', $filters['location']);
        }

        if (!empty($filters['category'])) {
            $builder->like('TRIM(category)', trim((string) $filters['category']));
        }

        if (!empty($filters['experience_level'])) {
            $this->applyNormalizedFieldFilter($builder, 'experience_level', $filters['experience_level'], 'experience');
        }

        if (!empty($filters['employment_type'])) {
            $this->applyNormalizedFieldFilter($builder, 'employment_type', $filters['employment_type'], 'employment');
        }

        if (!empty($filters['work_mode'])) {
            $this->applyWorkModeFilter($builder, (string) $filters['work_mode']);
        }

        if (!empty($filters['salary_range'])) {
            $this->applySalaryRangeFilter($builder, (string) $filters['salary_range']);
        }

        if (!empty($filters['posted_within'])) {
            $days = (int) $filters['posted_within'];
            $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
        }

        if (!empty($filters['skills_match']) && $candidateId > 0) {
            $userSkills = $this->getUserSkills($candidateId);
            if (!empty($userSkills)) {
                $builder->groupStart();
                foreach ($userSkills as $skill) {
                    $builder->orLike('required_skills', $skill);
                }
                $builder->groupEnd();
            }
        }

        switch ($filters['sort']) {
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

        $jobs = [];
        $pager = null;
        $totalJobs = 0;
        $locations = [];
        $categories = [];
        $employmentTypes = [];
        $experienceLevels = [];

        if ($showFilters) {
            $jobs = $builder->paginate(perPage: 4);
            $pager = $jobModel->pager;
            $totalJobs = $pager->getTotal();

            $locationBuilder      = $jobModel->select('location')->where('status', 'open')->where('location IS NOT NULL')->where('location !=', '');
            $categoryBuilder      = $jobModel->select('category')->where('status', 'open')->where('category IS NOT NULL')->where('category !=', '');
            $employmentTypeBuilder = $jobModel->select('employment_type')->where('status', 'open')->where('employment_type IS NOT NULL')->where('employment_type !=', '');
            $experienceBuilder    = $jobModel->select('experience_level')->where('status', 'open')->where('experience_level IS NOT NULL')->where('experience_level !=', '');

            foreach ([$locationBuilder, $categoryBuilder, $employmentTypeBuilder, $experienceBuilder] as $fb) {
                if (!empty($filters['search'])) {
                    $fb->groupStart()->like('title', $filters['search'])->orLike('company', $filters['search'])->orLike('required_skills', $filters['search'])->groupEnd();
                }
                if (!empty($filters['company']))    { $fb->like('company', $filters['company']); }
                if (!empty($filters['designation'])) { $fb->like('title', $filters['designation']); }
                if (!empty($filters['posted_within'])) {
                    $days = (int) $filters['posted_within'];
                    $fb->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
                }
            }

            $locations        = $locationBuilder->groupBy('location')->findAll();
            $categories       = $categoryBuilder->groupBy('category')->findAll();
            $employmentTypes  = $employmentTypeBuilder->groupBy('employment_type')->findAll();
            $experienceLevels = $experienceBuilder->groupBy('experience_level')->findAll();
            $employmentTypes  = $this->buildNormalizedFilterOptions($employmentTypes, 'employment_type', 'employment');
            $experienceLevels = $this->buildNormalizedFilterOptions($experienceLevels, 'experience_level', 'experience');
        }

        $allJobsAreExternal = !empty($jobs) && count(array_filter($jobs, static fn($j) => (int)($j['is_external'] ?? 0) !== 1)) === 0;

        $recommendationType = strtolower((string) $this->request->getGet('rec'));
        $allowedRecommendationTypes = ['applies', 'skills', 'preferences', 'ai'];
        if (!in_array($recommendationType, $allowedRecommendationTypes, true)) {
            $recommendationType = 'skills';
        }

        $suggestedJobsByApplies = [];
        $suggestedJobsBySkills = [];
        $suggestedJobsByPreferences = [];
        $suggestedJobsByAi = [];
        $suggestedJobs = [];
        $loadedRecommendationTypes = [];
        $recommendationCounts = [
            'applies' => null,
            'skills' => null,
            'preferences' => null,
            'ai' => null,
        ];

        $candidateSkills = [];
        $candidateInterests = [];
        $behavior = [];

        if ($candidateId > 0 && !$showFilters) {
            $skillsModel = new \App\Models\CandidateSkillsModel();
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

            if ($recommendationType === 'applies' || $recommendationType === 'preferences') {
                $behavior = $jobModel->getCandidateBehaviorProfile($candidateId);
            }

            switch ($recommendationType) {
                case 'applies':
                    $suggestedJobsByApplies = $this->rankJobsByApplicationBehavior($candidateId, $behavior, 20);
                    $suggestedJobs = $suggestedJobsByApplies;
                    break;

                case 'preferences':
                    $suggestedJobsByPreferences = $this->rankJobsByPreferences($candidateId, $behavior, $candidateInterests, 20);
                    $suggestedJobs = $suggestedJobsByPreferences;
                    break;

                case 'ai':
                    $aiPrimarySuggestions = (new AiJobMatcher())->generateSuggestions($candidateId, 20);
                    $suggestedJobsByAi = $this->buildOtherRecommendations($aiPrimarySuggestions, [], [], 20);
                    $suggestedJobs = $suggestedJobsByAi;
                    break;

                case 'skills':
                default:
                    $suggestedJobsBySkills = $jobModel->getSuggestedJobsBasic($candidateId, 20);
                    $suggestedJobs = $suggestedJobsBySkills;
                    break;
            }

            $loadedRecommendationTypes = [$recommendationType];
            $recommendationCounts[$recommendationType] = count($suggestedJobs);
        }

        $companyIds = [];
        $companyNamesToLookup = [];

        $gatherLookups = static function (array $jobSet) use (&$companyIds, &$companyNamesToLookup) {
            foreach ($jobSet as $job) {
                $id = (int) ($job['company_id'] ?? 0);
                if ($id > 0) {
                    $companyIds[] = $id;
                } elseif (!empty($job['company'])) {
                    $companyNamesToLookup[] = trim($job['company']);
                }
            }
        };

        $gatherLookups($jobs);
        $gatherLookups($suggestedJobsByApplies);
        $gatherLookups($suggestedJobsBySkills);
        $gatherLookups($suggestedJobsByPreferences);
        $gatherLookups($suggestedJobsByAi);

        $companyIds = array_values(array_unique($companyIds));
        $companyNamesToLookup = array_values(array_unique($companyNamesToLookup));

        $companyModel = new CompanyModel();
        $companyInfoMap = [];

        if (!empty($companyIds)) {
            $companies = $companyModel->select('id, name, logo, website')
                ->whereIn('id', $companyIds)
                ->findAll();
            foreach ($companies as $co) {
                $companyInfoMap['id_' . $co['id']] = $co;
                $companyInfoMap['name_' . strtolower(trim($co['name']))] = $co;
            }
        }

        if (!empty($companyNamesToLookup)) {
            $companiesByName = $companyModel->select('id, name, logo, website')
                ->whereIn('name', $companyNamesToLookup)
                ->findAll();
            foreach ($companiesByName as $co) {
                $companyInfoMap['name_' . strtolower(trim($co['name']))] = $co;
            }
        }

        $resolveCompanyBranding = function (array $jobSet) use ($companyInfoMap): array {
            foreach ($jobSet as $index => $job) {
                $companyId = (int) ($job['company_id'] ?? 0);
                $companyName = trim((string) ($job['company'] ?? ''));
                $info = null;

                if ($companyId > 0 && isset($companyInfoMap['id_' . $companyId])) {
                    $info = $companyInfoMap['id_' . $companyId];
                } elseif ($companyName !== '' && isset($companyInfoMap['name_' . strtolower($companyName)])) {
                    $info = $companyInfoMap['name_' . strtolower($companyName)];
                }

                $logo = (string) ($info['logo'] ?? '');
                $website = (string) ($info['website'] ?? '');

                if ($website === '' && $companyName !== '') {
                    $website = $this->guessCompanyDomain($companyName);
                }

                $jobSet[$index]['company_logo'] = $logo;
                $jobSet[$index]['company_website'] = $website;
            }
            return $jobSet;
        };

        $jobs = $resolveCompanyBranding($jobs);
        $suggestedJobs = $resolveCompanyBranding($suggestedJobs);
        $suggestedJobsByApplies = $resolveCompanyBranding($suggestedJobsByApplies);
        $suggestedJobsBySkills = $resolveCompanyBranding($suggestedJobsBySkills);
        $suggestedJobsByPreferences = $resolveCompanyBranding($suggestedJobsByPreferences);
        $suggestedJobsByAi = $resolveCompanyBranding($suggestedJobsByAi);

        $recommendationSets = [
            $suggestedJobs,
            $suggestedJobsByApplies,
            $suggestedJobsBySkills,
            $suggestedJobsByPreferences,
            $suggestedJobsByAi,
        ];

        $savedJobIds = [];
        $appliedJobMap = [];
        $visitedJobIds = [];
        if ($candidateId > 0) {
            $displayJobIds = [];
            foreach ($jobs as $job) {
                $displayJobIds[] = (int) ($job['id'] ?? 0);
            }
            foreach ($recommendationSets as $jobSet) {
                foreach ($jobSet as $job) {
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

                $appliedRows = model('ApplicationModel')
                    ->select('job_id, status')
                    ->where('candidate_id', $candidateId)
                    ->where('status !=', 'withdrawn')
                    ->whereIn('job_id', $displayJobIds)
                    ->findAll();
                foreach ($appliedRows as $row) {
                    $appliedJobMap[(int) ($row['job_id'] ?? 0)] = (string) ($row['status'] ?? 'applied');
                }

                if ($this->ensureCandidateJobVisitsTable($db)) {
                    $visitedRows = $db->table('candidate_job_visits')
                        ->select('job_id')
                        ->where('candidate_id', $candidateId)
                        ->whereIn('job_id', $displayJobIds)
                        ->get()
                        ->getResultArray();
                    $visitedJobIds = array_map('intval', array_column($visitedRows, 'job_id'));
                }
            }
        }

        $applyVisitedFlags = static function (array $jobSet) use ($visitedJobIds): array {
            if (empty($visitedJobIds)) {
                return $jobSet;
            }

            $visitedLookup = array_fill_keys($visitedJobIds, true);
            foreach ($jobSet as $index => $job) {
                $jobId = (int) ($job['id'] ?? 0);
                if ($jobId > 0 && isset($visitedLookup[$jobId])) {
                    $jobSet[$index]['visited_flag'] = 1;
                }
            }

            return $jobSet;
        };

        $jobs = $applyVisitedFlags($jobs);
        $suggestedJobs = $applyVisitedFlags($suggestedJobs);
        $suggestedJobsByApplies = $applyVisitedFlags($suggestedJobsByApplies);
        $suggestedJobsBySkills = $applyVisitedFlags($suggestedJobsBySkills);
        $suggestedJobsByPreferences = $applyVisitedFlags($suggestedJobsByPreferences);
        $suggestedJobsByAi = $applyVisitedFlags($suggestedJobsByAi);

        $suggestedJobs = match ($recommendationType) {
            'applies' => $suggestedJobsByApplies,
            'preferences' => $suggestedJobsByPreferences,
            'ai' => $suggestedJobsByAi,
            default => $suggestedJobsBySkills,
        };

        return view('candidate/smart_jobs', [
            'jobs' => $jobs,
            'totalJobs' => $totalJobs,
            'filters' => $filters,
            'locations' => $locations,
            'experienceLevels' => $experienceLevels,
            'employmentTypes' => $employmentTypes,
            'categories' => $categories,
            'pager' => $pager,
            'activeTab' => $activeTab,
            'recommendationType' => $recommendationType,
            'suggestedJobs' => $suggestedJobs,
            'suggestedJobsByApplies' => $suggestedJobsByApplies,
            'suggestedJobsBySkills' => $suggestedJobsBySkills,
            'suggestedJobsByPreferences' => $suggestedJobsByPreferences,
            'suggestedJobsByAi' => $suggestedJobsByAi,
            'loadedRecommendationTypes' => $loadedRecommendationTypes,
            'recommendationCounts' => $recommendationCounts,
            'candidateSkills' => $candidateSkills,
            'candidateInterests' => $candidateInterests,
            'behavior' => $behavior,
            'showFilters' => $showFilters,
            'savedJobIds' => $savedJobIds,
            'appliedJobMap' => $appliedJobMap,
            'allJobsAreExternal' => $allJobsAreExternal,
            'hasBaseResume' => $hasBaseResume,
            'primaryResumeId' => $primaryResumeId,
        ]);
    }

    private function normalizeSelectedFilterValues($value, string $type): array
    {
        $values = is_array($value) ? $value : [$value];
        $normalized = [];

        foreach ($values as $item) {
            $label = $type === 'employment'
                ? $this->normalizeEmploymentTypeLabel((string) $item)
                : $this->normalizeExperienceLevelLabel((string) $item);

            if ($label !== '') {
                $normalized[$label] = $label;
            }
        }

        return array_values($normalized);
    }

    private function buildNormalizedFilterOptions(array $rows, string $field, string $type): array
    {
        $options = [];

        foreach ($rows as $row) {
            $rawValue = trim((string) ($row[$field] ?? ''));
            if ($rawValue === '') {
                continue;
            }

            $labels = $type === 'employment'
                ? [$this->normalizeEmploymentTypeLabel($rawValue)]
                : $this->normalizeExperienceLevelOptionLabels($rawValue);

            foreach ($labels as $label) {
                if ($label === '') {
                    continue;
                }

                $options[$label] = [$field => $label];
            }
        }

        $order = $type === 'employment'
            ? ['Full-time', 'Part-time', 'Contract', 'Freelance', 'Internship', 'Temporary', 'Permanent']
            : ['Fresher', 'Junior', 'Mid Level', 'Senior'];

        uksort($options, static function (string $a, string $b) use ($order): int {
            $aIndex = array_search($a, $order, true);
            $bIndex = array_search($b, $order, true);
            $aIndex = $aIndex === false ? PHP_INT_MAX : $aIndex;
            $bIndex = $bIndex === false ? PHP_INT_MAX : $bIndex;

            if ($aIndex === $bIndex) {
                return strcasecmp($a, $b);
            }

            return $aIndex <=> $bIndex;
        });

        return array_values($options);
    }

    private function applyNormalizedFieldFilter($builder, string $field, $selectedValues, string $type): void
    {
        $selectedValues = $this->normalizeSelectedFilterValues($selectedValues, $type);
        if (empty($selectedValues)) {
            return;
        }

        $builder->groupStart();
        foreach ($selectedValues as $index => $selectedValue) {
            $aliases = $type === 'employment'
                ? $this->employmentTypeAliases($selectedValue)
                : $this->experienceLevelAliases($selectedValue);

            if (empty($aliases)) {
                continue;
            }

            $method = $index === 0 ? 'whereIn' : 'orWhereIn';
            $builder->{$method}($field, $aliases);
        }
        $builder->groupEnd();
    }

    private function normalizeEmploymentTypeLabel(string $value): string
    {
        $key = $this->normalizeFilterKey($value);

        $labels = [
            'full-time' => 'Full-time',
            'fulltime' => 'Full-time',
            'part-time' => 'Part-time',
            'parttime' => 'Part-time',
            'contract' => 'Contract',
            'freelance' => 'Freelance',
            'internship' => 'Internship',
            'intern' => 'Internship',
            'temporary' => 'Temporary',
            'temp' => 'Temporary',
            'permanent' => 'Permanent',
        ];

        return $labels[$key] ?? trim($value);
    }

    private function normalizeExperienceLevelLabel(string $value): string
    {
        $labels = $this->normalizeExperienceLevelOptionLabels($value);

        return $labels[0] ?? '';
    }

    private function normalizeExperienceLevelOptionLabels(string $value): array
    {
        $key = $this->normalizeFilterKey($value);
        if ($key === '' || in_array($key, ['not-specified', 'not-specified-', 'multiple-levels'], true)) {
            return [];
        }

        $labels = [];
        if (strpos($key, 'fresher') !== false || strpos($key, 'entry') !== false) {
            $labels[] = 'Fresher';
        }
        if (strpos($key, 'junior') !== false) {
            $labels[] = 'Junior';
        }
        if (strpos($key, 'mid') !== false || strpos($key, 'middle') !== false) {
            $labels[] = 'Mid Level';
        }
        if (strpos($key, 'senior') !== false || preg_match('/\bsr\b/', $key)) {
            $labels[] = 'Senior';
        }

        return array_values(array_unique($labels));
    }

    private function employmentTypeAliases(string $label): array
    {
        $aliases = [
            'Full-time' => ['Full-time', 'Full Time', 'Full_time', 'full-time', 'full time', 'full_time', 'fulltime'],
            'Part-time' => ['Part-time', 'Part Time', 'Part_time', 'part-time', 'part time', 'part_time', 'parttime'],
            'Contract' => ['Contract', 'contract'],
            'Freelance' => ['Freelance', 'freelance'],
            'Internship' => ['Internship', 'Intern', 'internship', 'intern'],
            'Temporary' => ['Temporary', 'Temp', 'temporary', 'temp'],
            'Permanent' => ['Permanent', 'permanent'],
        ];

        return $aliases[$label] ?? [$label];
    }

    private function experienceLevelAliases(string $label): array
    {
        $aliases = [
            'Fresher' => ['Fresher', 'Freshers', 'fresher', 'freshers', 'Entry Level', 'Entry-Level', 'entry_level', 'entry level', 'Fresher/Mid', 'fresher/mid', 'Fresher-Mid'],
            'Junior' => ['Junior', 'junior', 'Junior/Mid', 'junior/mid', 'Junior-Mid'],
            'Mid Level' => ['Mid Level', 'Mid-Level', 'Mid level', 'mid level', 'mid-level', 'Mid', 'mid', 'middle', 'Fresher/Mid', 'fresher/mid', 'Fresher-Mid', 'Junior/Mid', 'junior/mid', 'Junior-Mid', 'Mid/Senior', 'mid/senior', 'Mid-Senior'],
            'Senior' => ['Senior', 'senior', 'Sr', 'sr', 'Mid/Senior', 'mid/senior', 'Mid-Senior'],
        ];

        return $aliases[$label] ?? [$label];
    }

    private function normalizeFilterKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['_', '/', '\\'], ['-', '-', '-'], $value);
        $value = preg_replace('/\s+/', '-', $value) ?? $value;
        $value = preg_replace('/-+/', '-', $value) ?? $value;

        return trim($value, '-');
    }

    private function getUserSkills($userId)
    {
        $skillsModel = model('CandidateSkillsModel');
        $userSkills = $skillsModel->where('candidate_id', $userId)->first();

        if ($userSkills && !empty($userSkills['skill_name'])) {
            return array_map('trim', explode(',', (string) $userSkills['skill_name']));
        }

        return [];
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

            // Weigh category most (50%)
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
            
            // Weigh location (30%)
            foreach ($topLocations as $loc) {
                if ($loc !== '' && (str_contains($jobLocation, $loc) || str_contains($loc, $jobLocation))) {
                    $locationScore = 30.0;
                    break;
                }
            }

            // Weigh employment type (20%)
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
        $userModel = new \App\Models\UserModel();

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
            
            // Preferred Titles (35%)
            foreach ($preferredJobTitles as $preferredJobTitle) {
                if ($preferredJobTitle !== '' && (str_contains($jobTitle, $preferredJobTitle) || str_contains($preferredJobTitle, $jobTitle))) {
                    $titleScore = 35.0;
                    break;
                }
            }
            
            // Explicit Interests (25%)
            foreach ($interests as $interest) {
                if ($interest !== '' && str_contains($blob, $interest)) {
                    $interestScore = 25.0;
                    break;
                }
            }

            // Preferred Locations (25%)
            $jobLocation = strtolower(trim((string) ($job['location'] ?? '')));
            foreach ($preferredLocations as $loc) {
                if ($loc !== '' && (str_contains($jobLocation, $loc) || str_contains($loc, $jobLocation))) {
                    $locationScore = 25.0;
                    break;
                }
            }

            // Employment Types (15%)
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

    public function jobDetail($id)
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }

        $jobModel = new JobModel();
        $companyModel = new CompanyModel();
        $applicationModel = model('ApplicationModel');
        $candidateId = (int) session()->get('user_id');

        $job = $jobModel
            ->where('id', $id)
            ->where('status', 'open')
            ->first();

        if (!$job) {
            return redirect()->to(base_url('jobs'))
                ->with('error', 'Job not found');
        }

        $this->markJobVisited((int) $id, $candidateId);
        $job['visited_flag'] = 1;

        // Flag as expired for UI components
        $isExpired = false;
        if (!empty($job['application_deadline'])) {
            if (strtotime($job['application_deadline'] . ' 23:59:59') < time()) {
                $isExpired = true;
            }
        }

        // External jobs: skip the blank detail page and go straight to the source
        if ((int) ($job['is_external'] ?? 0) === 1) {
            $applyUrl = trim((string) ($job['external_apply_url'] ?? ''));
            if ($applyUrl !== '' && filter_var($applyUrl, FILTER_VALIDATE_URL)) {
                return redirect()->to($applyUrl);
            }
        }

        // Handle Client Company Visibility and Declaration
        if (($job['posted_for'] ?? '') === 'client') {
            if (($job['client_disclosure'] ?? '') === 'visible' && !empty($job['client_company_name'])) {
                $job['company'] = $job['client_company_name'];
            } else {
                $job['company'] = ($job['company'] ?? 'Recruiter') . ' (Hiring for a Client)';
            }
        }


        $application = $applicationModel
            ->where('job_id', $id)
            ->where('candidate_id', $candidateId)
            ->where('status !=', 'withdrawn')
            ->first();

        $alreadyApplied = $application ? true : false;
        $application = $this->maskFilteredStatus($application);

        $interviewId = null;

        $isSaved = (bool) (new SavedJobModel())
            ->where('candidate_id', $candidateId)
            ->where('job_id', (int) $id)
            ->first();

        $company = null;
        $companyId = (int) ($job['company_id'] ?? 0);
        if ($companyId > 0) {
            $company = $companyModel->find($companyId);
        }

        if (!$company && !empty($job['company'])) {
            $company = $companyModel->where('name', $job['company'])->first();
        }

        $resumeCoach = JobModel::isExternalJob($job) ? [] : $this->buildResumeCoach($candidateId, $job);
        $invitation = $this->resolveInvitationContext($candidateId, (int) $id);
        $applicationQuestionnaire = $this->decodeApplicationQuestionnaire((string) ($job['application_questionnaire'] ?? ''));

        return view('candidate/job_details', [
            'title' => 'Job Details',
            'job' => $job,
            'company' => $company,
            'alreadyApplied' => $alreadyApplied,
            'interviewId' => $interviewId,
            'isSaved' => $isSaved,
            'resumeCoach' => $resumeCoach,
            'invitation' => $invitation,
            'applicationQuestionnaire' => $applicationQuestionnaire,
            'application' => $application,
            'isExpired' => $isExpired,
        ]);
    }

    private function markJobVisited(int $jobId, int $candidateId): void
    {
        if ($jobId <= 0) {
            return;
        }

        try {
            $db = \Config\Database::connect();
            if ($candidateId > 0 && $this->ensureCandidateJobVisitsTable($db)) {
                $now = date('Y-m-d H:i:s');
                $db->query(
                    'INSERT INTO candidate_job_visits (candidate_id, job_id, visited_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE visited_at = VALUES(visited_at), updated_at = VALUES(updated_at)',
                    [$candidateId, $jobId, $now, $now, $now]
                );
            }

            if ($db->fieldExists('visited_flag', 'jobs')) {
                $db->table('jobs')
                    ->where('id', $jobId)
                    ->update(['visited_flag' => 1]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Unable to mark job visited: ' . $e->getMessage());
        }
    }

    private function ensureCandidateJobVisitsTable($db): bool
    {
        try {
            if ($db->tableExists('candidate_job_visits')) {
                return true;
            }

            $db->query(
                'CREATE TABLE IF NOT EXISTS candidate_job_visits (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    candidate_id INT NOT NULL,
                    job_id INT NOT NULL,
                    visited_at DATETIME NOT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY candidate_job_visits_candidate_job_unique (candidate_id, job_id),
                    KEY candidate_job_visits_candidate_id_index (candidate_id),
                    KEY candidate_job_visits_job_id_index (job_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'Unable to ensure candidate job visits table: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeApplicationQuestionnaire(string $rawQuestionnaire): array
    {
        if (trim($rawQuestionnaire) === '') {
            return [];
        }

        $decoded = json_decode($rawQuestionnaire, true);
        if (!is_array($decoded)) {
            return [];
        }

        $questions = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = trim((string) ($row['id'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            $type = strtolower(trim((string) ($row['type'] ?? 'textarea')));

            if ($id === '' || $label === '' || !in_array($type, ['text', 'textarea'], true)) {
                continue;
            }

            $questions[] = [
                'id' => $id,
                'label' => $label,
                'type' => $type,
                'placeholder' => trim((string) ($row['placeholder'] ?? '')),
                'required' => (bool) ($row['required'] ?? false),
                'knockout' => (bool) ($row['knockout'] ?? false),
            ];
        }

        return $questions;
    }

    private function resolveInvitationContext(int $candidateId, int $jobId): ?array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('recruiter_job_invitations')) {
            return null;
        }

        $invitation = (new RecruiterJobInvitationModel())->getLatestForCandidateJob($candidateId, $jobId);
        if (!$invitation) {
            return null;
        }

        if (($invitation['status'] ?? '') === RecruiterJobInvitationModel::STATUS_SENT) {
            (new RecruiterJobInvitationModel())->markViewed((int) $invitation['id']);
            $invitation['status'] = RecruiterJobInvitationModel::STATUS_VIEWED;
            $invitation['viewed_at'] = date('Y-m-d H:i:s');
        }

        return $invitation;
    }

    private function buildResumeCoach(int $candidateId, array $job): array
    {
        $user = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
        $skillsRow = (new CandidateSkillsModel())->where('candidate_id', $candidateId)->first();
        $resumeVersion = (new CandidateResumeVersionModel())->getPreferredVersionForJob($candidateId, (int) ($job['id'] ?? 0));
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
            'resume_studio_url' => base_url('candidate/resume-studio?generation_mode=job&job_id=' . (int) ($job['id'] ?? 0)),
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

    /**
     * Mask the 'filtered_out' status for candidate views.
     */
    private function maskFilteredStatus(?array $application): ?array
    {
        if ($application && ($application['status'] ?? '') === 'filtered_out') {
            $application['status'] = 'applied';
        }
        return $application;
    }

    private function guessCompanyDomain(string $name): string
    {
        $clean = strtolower(trim($name));
        $clean = preg_replace('/\b(limited|ltd|inc|llc|llp|plc|corp|corporation|company|co|solutions|services|technologies|technology)\b/i', '', $clean);
        $clean = preg_replace('/[^a-z0-9]/', '', $clean);
        if ($clean === '') return '';
        return 'https://' . $clean . '.com';
    }
}
