<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use App\Models\CandidateSkillsModel;
use App\Models\CompanyModel;
use App\Models\JobModel;
use App\Models\RecruiterCandidateActionModel;
use App\Models\ApplicationModel;
use App\Models\InterviewBookingModel;
use App\Models\NotificationModel;
use App\Libraries\AiJobSearchStrategyCoach;

class ApiDashboardController extends ResourceController
{
    protected $format = 'json';

    public function getDashboard($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        // 1. Applications with details
        $applications = $this->getApplicationsWithDetails($candidateId);

        // 2. Stats
        $stats = $this->calculateStats($candidateId);
        $profileStrength = $this->calculateProfileStrength($candidateId);

        // 3. Top Suggested Jobs
        $topSuggestedJobs = $this->getTopSuggestedJobs($candidateId, 3);

        // 4. Top Hiring Companies
        $topHiringCompanies = $this->getTopHiringCompanies(8);

        // 5. Job Categories (Explore by Role)
        $jobCategories = $this->getJobCategoryShortcuts();

        // 6. User profile details
        $userModel = model('UserModel');
        $userProfile = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId);

        // Format dates or make sure asset URLs are complete
        $baseUrl = base_url();
        if ($userProfile && !empty($userProfile['profile_photo'])) {
            if (!preg_match('/^https?:\/\//i', $userProfile['profile_photo'])) {
                $userProfile['profile_photo_url'] = $baseUrl . ltrim($userProfile['profile_photo'], '/');
            } else {
                $userProfile['profile_photo_url'] = $userProfile['profile_photo'];
            }
        } else {
            $userProfile['profile_photo_url'] = '';
        }

        // Generate Job Search Strategy Coach data
        $jobSearchStrategy = $this->buildJobSearchStrategyCoach($candidateId, $applications, $topSuggestedJobs);

        // Fetch active subscription
        $subscriptionModel = model('SubscriptionModel');
        $currentSubscription = $subscriptionModel->getUserActiveSubscription($candidateId);

        // Fetch recent blog posts
        $blogPosts = [];
        $db = \Config\Database::connect();
        if ($db->tableExists('blog_posts')) {
            $blogModel = model('BlogModel');
            $blogPosts = $blogModel->getPublishedPosts(3);
            foreach ($blogPosts as &$post) {
                if (!empty($post['cover_image']) && !preg_match('/^https?:\/\//i', $post['cover_image'])) {
                    $post['cover_image'] = base_url(ltrim($post['cover_image'], '/'));
                }
            }
        }

        return $this->respond([
            'status' => 'success',
            'data' => [
                'user' => $userProfile,
                'applications' => $applications,
                'stats' => $stats,
                'profileStrength' => $profileStrength,
                'topSuggestedJobs' => $topSuggestedJobs,
                'topHiringCompanies' => $topHiringCompanies,
                'jobCategories' => $jobCategories,
                'jobSearchStrategy' => $jobSearchStrategy,
                'currentSubscription' => $currentSubscription,
                'blogPosts' => $blogPosts
            ]
        ]);
    }

    private function getApplicationsWithDetails($candidateId)
    {
        $applicationModel = model('ApplicationModel');
        $db = \Config\Database::connect();
        $hasPolicyColumn = $db->fieldExists('ai_interview_policy', 'jobs');
        $hasResumeVersions = $db->tableExists('candidate_resume_versions') && $db->fieldExists('resume_version_id', 'applications');
        $hasClientInfo = $db->fieldExists('client_company_name', 'jobs') && $db->fieldExists('client_disclosure', 'jobs') && $db->fieldExists('posted_for', 'jobs');
        $hasFeeInfo = $db->fieldExists('candidate_fee_allowed', 'jobs');

        $policySelect = $hasPolicyColumn
            ? 'jobs.ai_interview_policy'
            : "'REQUIRED_HARD' as ai_interview_policy";
        $resumeSelect = $hasResumeVersions
            ? 'candidate_resume_versions.title as resume_version_title,
                candidate_resume_versions.target_role as resume_version_target_role,
                candidate_resume_versions.summary as resume_version_summary,
                candidate_resume_versions.highlight_skills as resume_version_highlight_skills,
                candidate_resume_versions.content as resume_version_content,
                candidate_resume_versions.updated_at as resume_version_updated_at,'
            : "'' as resume_version_title, '' as resume_version_target_role, '' as resume_version_summary, '' as resume_version_highlight_skills, '' as resume_version_content, NULL as resume_version_updated_at,";
        $clientSelect = $hasClientInfo 
            ? 'jobs.posted_for, jobs.client_company_name, jobs.client_disclosure,' 
            : "'' as posted_for, '' as client_company_name, 'visible' as client_disclosure,";
        $feeSelect = $hasFeeInfo 
            ? 'jobs.candidate_fee_allowed,' 
            : "0 as candidate_fee_allowed,";
        
        $builder = $applicationModel
            ->select('
                applications.*,
                jobs.title as job_title,
                jobs.company,
                jobs.location,
                jobs.salary_range,
                ' . $clientSelect . '
                ' . $feeSelect . '
                jobs.description as job_description,
                jobs.required_skills,
                jobs.experience_level,
                ' . $resumeSelect . '
                ' . $policySelect . ',
                0 as technical_score,
                0 as communication_score,
                0 as overall_rating,
                NULL as ai_interview_completed
            ')
            ->join('jobs', 'jobs.id = applications.job_id', 'left');

        if ($hasResumeVersions) {
            $builder->join('candidate_resume_versions', 'candidate_resume_versions.id = applications.resume_version_id', 'left');
        }

        $applications = $builder
            ->where('applications.candidate_id', $candidateId)
            ->orderBy('applications.applied_at', 'DESC')
            ->findAll();
        
        foreach ($applications as &$application) {
            if (($application['posted_for'] ?? '') === 'client') {
                if (($application['client_disclosure'] ?? '') === 'visible' && !empty($application['client_company_name'])) {
                    $application['company'] = $application['client_company_name'];
                } else {
                    $application['company'] = ($application['company'] ?? 'Recruiter') . ' (Hiring for a Client)';
                }
            }

            $application['next_action'] = $this->getNextAction($application);
            $application['interview_review_label'] = $this->formatInterviewReviewStatus((string) ($application['interview_review_status'] ?? ''));
        }
        
        return $applications;
    }

    private function getNextAction($application)
    {
        switch ($application['status']) {
            case 'applied':
                $policy = strtoupper((string) ($application['ai_interview_policy'] ?? 'REQUIRED_HARD'));
                return $policy === JobModel::AI_POLICY_OFF
                    ? 'Your application is under recruiter review.'
                    : 'Start your AI interview to move forward.';

            case 'shortlisted':
                return 'Congratulations! You\'ve been shortlisted. Book your HR interview slot.';
                
            case 'interview_slot_booked':
                return 'Your interview slot is booked. Check your bookings for the schedule.';
                
            case 'selected':
                return 'Congratulations! You\'ve been selected. Check your email for next steps.';

            case 'hired':
                return 'Congratulations! Your hiring process is complete.';

            case 'hold':
                return 'Your application is on hold. The recruiter may revisit it later.';

            case 'filtered_out':
                return 'Your application is under recruiter review.';
                
            case 'rejected':
                return 'Unfortunately, we are proceeding with other candidates at this time.';
                
            case 'withdrawn':
                return 'You have withdrawn this application.';
                
            default:
                return 'Application in progress.';
        }
    }

    private function formatInterviewReviewStatus(string $status): string
    {
        return match ($status) {
            'submitted' => 'Submitted',
            'under_review' => 'Under review',
            'finalized' => 'Finalized',
            'candidate_notified' => 'Candidate notified',
            'pending_evaluation' => 'Pending evaluation',
            'completed' => 'Completed',
            'evaluated' => 'Evaluated',
            default => '',
        };
    }

    private function calculateStats($candidateId)
    {
        $applicationModel = model('ApplicationModel');
        $bookingModel = model('InterviewBookingModel');
        $notificationModel = model('NotificationModel');
        
        $totalApplications = $applicationModel
            ->where('candidate_id', $candidateId)
            ->countAllResults();
        
        $activeApplications = $applicationModel
            ->where('candidate_id', $candidateId)
            ->whereNotIn('status', ['filtered_out', 'rejected', 'withdrawn'])
            ->countAllResults();
        
        $interviewsScheduled = $bookingModel
            ->where('user_id', $candidateId)
            ->where('slot_datetime >=', date('Y-m-d H:i:s'))
            ->whereIn('booking_status', ['booked', 'rescheduled'])
            ->countAllResults();
        
        $unreadNotifications = $notificationModel
            ->where('user_id', $candidateId)
            ->where('is_read', 0)
            ->countAllResults();

        $savedJobs = model('SavedJobModel')
            ->where('candidate_id', $candidateId)
            ->countAllResults();

        $activeAlerts = model('JobAlertModel')
            ->where('candidate_id', $candidateId)
            ->where('is_active', 1)
            ->countAllResults();
        
        return [
            'total_applications' => $totalApplications,
            'active_applications' => $activeApplications,
            'interviews_scheduled' => $interviewsScheduled,
            'unread_notifications' => $unreadNotifications,
            'saved_jobs' => $savedJobs,
            'active_alerts' => $activeAlerts
        ];
    }

    private function calculateProfileStrength(int $candidateId): int
    {
        $userModel = model('UserModel');
        $skillsModel = new CandidateSkillsModel();
        $githubModel = model('GithubAnalysisModel');
        $user = $userModel->findCandidateWithProfile($candidateId) ?? [];
        $skillsRow = $skillsModel->where('candidate_id', $candidateId)->first() ?? [];
        $github = $githubModel->where('candidate_id', $candidateId)->first() ?? [];

        $profileFields = [
            !empty($user['name']),
            !empty($user['email']),
            !empty($user['phone']),
            !empty($user['profile_photo']),
            !empty($user['resume_path']),
            !empty($user['intro_video_path']),
            !empty($github['github_username']),
            !empty($skillsRow['skill_name']),
            !empty($user['location']),
            !empty($user['bio'])
        ];

        $filled = array_sum($profileFields);
        $total = max(1, count($profileFields));

        return (int) round(($filled / $total) * 100);
    }

    private function getTopSuggestedJobs(int $candidateId, int $limit = 3): array
    {
        $jobModel = new JobModel();
        $suggestedJobs = $jobModel->getSuggestedJobsBasic($candidateId, $limit);

        if (empty($suggestedJobs)) {
            return [];
        }

        $companyIds = [];
        $companyNames = [];
        foreach ($suggestedJobs as $job) {
            $companyId = (int) ($job['company_id'] ?? 0);
            if ($companyId > 0) {
                $companyIds[] = $companyId;
            }
            $cName = strtolower(trim((string) ($job['company'] ?? '')));
            if ($cName !== '') {
                $companyNames[] = $cName;
            }
        }

        $db = \Config\Database::connect();
        $companyLogoMap = [];
        $companyWebsiteMap = [];
        $companyNameLogoMap = [];
        $companyNameWebsiteMap = [];
        
        if (!empty($companyIds) || !empty($companyNames)) {
            $builder = $db->table('companies')->select('id, name, logo, website');
            if (!empty($companyIds) && !empty($companyNames)) {
                $builder->groupStart()
                        ->whereIn('id', array_unique($companyIds))
                        ->orWhereIn('LOWER(name)', array_unique($companyNames))
                        ->groupEnd();
            } elseif (!empty($companyIds)) {
                $builder->whereIn('id', array_unique($companyIds));
            } else {
                $builder->whereIn('LOWER(name)', array_unique($companyNames));
            }
            
            $companies = $builder->get()->getResultArray();
            foreach ($companies as $company) {
                $id = (int) $company['id'];
                $name = strtolower(trim((string) ($company['name'] ?? '')));
                $logo = (string) ($company['logo'] ?? '');
                $website = (string) ($company['website'] ?? '');
                
                $companyLogoMap[$id] = $logo;
                $companyWebsiteMap[$id] = $website;
                if ($name !== '') {
                    $companyNameLogoMap[$name] = $logo;
                    $companyNameWebsiteMap[$name] = $website;
                }
            }
        }

        foreach ($suggestedJobs as $idx => $job) {
            if (($job['posted_for'] ?? '') === 'client') {
                if (($job['client_disclosure'] ?? '') === 'visible' && !empty($job['client_company_name'])) {
                    $suggestedJobs[$idx]['company'] = $job['client_company_name'];
                } else {
                    $suggestedJobs[$idx]['company'] = ($job['company'] ?? 'Recruiter') . ' (Hiring for a Client)';
                }
            }

            $coId = (int) ($job['company_id'] ?? 0);
            $cName = strtolower(trim((string) ($job['company'] ?? '')));
            
            $logo = '';
            $website = '';
            
            if ($coId > 0 && isset($companyLogoMap[$coId])) {
                $logo = $companyLogoMap[$coId];
                $website = $companyWebsiteMap[$coId] ?? '';
            } elseif ($cName !== '' && isset($companyNameLogoMap[$cName])) {
                $logo = $companyNameLogoMap[$cName];
                $website = $companyNameWebsiteMap[$cName] ?? '';
            }

            if ($logo !== '') {
                if (!preg_match('/^https?:\/\//i', $logo)) {
                    $logo = base_url(ltrim($logo, '/'));
                }
            } else {
                $websiteHost = $website !== '' ? (parse_url($website, PHP_URL_HOST) ?: $website) : '';
                $websiteHost = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
                if ($websiteHost !== '') {
                    $logo = 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96';
                }
            }
            
            $suggestedJobs[$idx]['company_logo'] = $logo;
        }

        return $suggestedJobs;
    }

    private function getTopHiringCompanies(int $limit = 8): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('jobs')
            ->select('jobs.company as name, jobs.company_id, COUNT(jobs.id) as job_count, companies.logo, companies.industry, companies.hq, companies.website')
            ->join('companies', 'companies.id = jobs.company_id', 'left')
            ->where('jobs.status', 'open')
            ->groupBy('jobs.company')
            ->orderBy('job_count', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $filtered = array_values(array_filter($rows, static function (array $row): bool {
            return trim((string) ($row['name'] ?? '')) !== '';
        }));

        foreach ($filtered as &$row) {
            if (!empty($row['logo'])) {
                if (!preg_match('/^https?:\/\//i', $row['logo'])) {
                    $row['logo'] = base_url(ltrim($row['logo'], '/'));
                }
            } else {
                $website = (string) ($row['website'] ?? '');
                $websiteHost = $website !== '' ? (parse_url($website, PHP_URL_HOST) ?: $website) : '';
                $websiteHost = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
                if ($websiteHost !== '') {
                    $row['logo'] = 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96';
                }
            }
        }

        return $filtered;
    }

    private function getJobCategoryShortcuts(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('jobs')
            ->select('category as name, COUNT(id) as job_count')
            ->where('status', 'open')
            ->groupBy('category')
            ->orderBy('job_count', 'DESC')
            ->limit(8)
            ->get()
            ->getResultArray();

        $iconMap = [
            'data science' => 'fas fa-chart-line',
            'machine learning' => 'fas fa-microchip',
            'artificial intelligence' => 'fas fa-robot',
            'software engineering' => 'fas fa-code',
            'web development' => 'fas fa-laptop-code',
            'ui/ux design' => 'fas fa-bezier-curve',
            'cloud computing' => 'fas fa-cloud',
        ];

        foreach ($rows as &$row) {
            $row['icon'] = $iconMap[strtolower(trim((string)$row['name']))] ?? 'fas fa-briefcase';
        }
        unset($row);

        return array_values(array_filter($rows, fn($r) => trim((string)($r['name'] ?? '')) !== ''));
    }

    private function buildJobSearchStrategyCoach(int $candidateId, array $applications, array $topSuggestedJobs): array
    {
        $user = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
        $skillsRow = (new CandidateSkillsModel())->where('candidate_id', $candidateId)->first() ?? [];
        $skills = $this->tokenizeCsv((string) ($skillsRow['skill_name'] ?? ''));
        $behavior = (new JobModel())->getCandidateBehaviorProfile($candidateId);

        $activeApplications = count(array_filter($applications, static function (array $application): bool {
            return !in_array((string) ($application['status'] ?? ''), ['filtered_out', 'rejected', 'withdrawn', 'selected', 'hired'], true);
        }));
        $appliedCount = count($applications);
        $shortlistedCount = count(array_filter($applications, static function (array $application): bool {
            return in_array((string) ($application['status'] ?? ''), ['shortlisted', 'interview_slot_booked', 'selected', 'hired'], true);
        }));

        $topCategories = array_values(array_filter(array_map(static function (array $row): string {
            return trim((string) ($row['category'] ?? ''));
        }, (array) ($behavior['top_categories'] ?? []))));
        $topLocations = array_values(array_filter(array_map(static function (array $row): string {
            return trim((string) ($row['location'] ?? ''));
        }, (array) ($behavior['top_locations'] ?? []))));

        $suggestedJobsContext = array_map(static function (array $job): array {
            return [
                'id' => (int) ($job['id'] ?? 0),
                'title' => (string) ($job['title'] ?? ''),
                'company' => (string) ($job['company'] ?? ''),
                'location' => (string) ($job['location'] ?? ''),
                'match_score' => (float) ($job['match_score'] ?? 0),
                'required_skills' => (string) ($job['required_skills'] ?? ''),
                'experience_level' => (string) ($job['experience_level'] ?? ''),
            ];
        }, $topSuggestedJobs);

        $context = [
            'profile' => [
                'resume_headline' => (string) ($user['resume_headline'] ?? ''),
                'bio_present' => !empty($user['bio']),
                'preferred_job_titles' => (string) ($user['preferred_job_titles'] ?? ''),
                'preferred_locations' => (string) ($user['preferred_locations'] ?? ''),
                'preferred_employment_type' => (string) ($user['preferred_employment_type'] ?? ''),
                'resume_uploaded' => !empty($user['resume_path']),
                'skills' => array_slice($skills, 0, 12),
            ],
            'behavior' => [
                'top_categories' => array_slice($topCategories, 0, 4),
                'top_locations' => array_slice($topLocations, 0, 4),
                'top_experience_levels' => array_slice((array) ($behavior['top_experience_levels'] ?? []), 0, 3),
                'top_employment_types' => array_slice((array) ($behavior['top_employment_types'] ?? []), 0, 3),
                'applied_skill_frequency' => array_slice((array) ($behavior['applied_skill_frequency'] ?? []), 0, 8, true),
            ],
            'pipeline' => [
                'applied_count' => $appliedCount,
                'active_count' => $activeApplications,
                'shortlisted_count' => $shortlistedCount,
            ],
            'suggested_jobs' => $suggestedJobsContext,
        ];

        $recommendedJobIds = array_values(array_filter(array_map(static function (array $job): int {
            return (int) ($job['id'] ?? 0);
        }, array_slice($topSuggestedJobs, 0, 3))));

        $fallback = [
            'title' => 'Job Search Strategy Coach',
            'summary' => 'Use your strongest matching roles and current application behavior to focus on a narrower, higher-conversion search over the next 1 to 2 weeks.',
            'target_roles' => $this->deriveTargetRoles($user, $topSuggestedJobs, $topCategories),
            'priority_actions' => $this->buildPriorityActions($user, $skills, $topSuggestedJobs),
            'profile_fixes' => $this->buildProfileFixes($user, $skills),
            'application_strategy' => $this->buildApplicationStrategy($topSuggestedJobs, $activeApplications, $shortlistedCount),
            'weekly_plan' => $this->buildWeeklyPlan($topSuggestedJobs),
            'watchouts' => $this->buildWatchouts($activeApplications, $skills, $topSuggestedJobs),
            'recommended_job_ids' => $recommendedJobIds,
            'source' => 'fallback',
        ];

        return (new AiJobSearchStrategyCoach())->generate($candidateId, $context, $fallback);
    }

    private function tokenizeCsv(string $value): array
    {
        $parts = preg_split('/[,|\\/]+/', strtolower($value)) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $tokens[] = $part;
            }
        }
        return $tokens;
    }

    private function deriveTargetRoles(array $user, array $topSuggestedJobs, array $topCategories): array
    {
        $roles = [];
        $preferredTitles = preg_split('/[,|\\/]+/', (string) ($user['preferred_job_titles'] ?? '')) ?: [];
        foreach ($preferredTitles as $title) {
            $title = trim((string) $title);
            if ($title !== '') {
                $roles[] = $title;
            }
        }
        foreach ($topSuggestedJobs as $job) {
            $title = trim((string) ($job['title'] ?? ''));
            if ($title !== '') {
                $roles[] = $title;
            }
        }
        foreach ($topCategories as $category) {
            if ($category !== '') {
                $roles[] = $category . ' roles';
            }
        }

        return array_values(array_slice(array_unique($roles), 0, 4));
    }

    private function buildPriorityActions(array $user, array $skills, array $topSuggestedJobs): array
    {
        $actions = [];
        if (!empty($topSuggestedJobs)) {
            $bestJob = $topSuggestedJobs[0];
            $actions[] = 'Prioritize roles similar to "' . trim((string) ($bestJob['title'] ?? 'your strongest match')) . '" where your match score is already highest.';
        }
        if (empty($user['preferred_locations'])) {
            $actions[] = 'Set clear preferred locations so job suggestions and recruiter visibility become more targeted.';
        }
        if (count($skills) < 5) {
            $actions[] = 'Add more verified skills to your profile so matching is not driven by a narrow keyword set.';
        } else {
            $actions[] = 'Center your search around the 3 to 5 skills that appear most often in your strongest matching jobs.';
        }
        $actions[] = 'Apply selectively to roles with strong skill overlap instead of spreading effort across loosely related openings.';

        return array_values(array_slice($actions, 0, 5));
    }

    private function buildProfileFixes(array $user, array $skills): array
    {
        $fixes = [];
        if (empty($user['resume_headline'])) {
            $fixes[] = 'Add a sharper resume headline that states your target role and strongest stack.';
        }
        if (empty($user['bio'])) {
            $fixes[] = 'Complete your profile bio so recruiters can understand your fit beyond keywords.';
        }
        if (empty($user['resume_path'])) {
            $fixes[] = 'Upload a base resume so role-based resume versions and recruiter downloads have a stronger source.';
        }
        if (count($skills) > 0) {
            $fixes[] = 'Keep profile skills and resume skills aligned so your visible profile tells the same story as your applications.';
        }
        if (empty($user['preferred_employment_type'])) {
            $fixes[] = 'Set preferred employment type to reduce noise in recommendations.';
        }

        return array_values(array_slice($fixes, 0, 5));
    }

    private function buildApplicationStrategy(array $topSuggestedJobs, int $activeApplications, int $shortlistedCount): array
    {
        $strategy = [];
        if (!empty($topSuggestedJobs)) {
            $strategy[] = 'Start with the highest-match suggested jobs and tailor your resume before applying.';
        }
        if ($activeApplications > 5) {
            $strategy[] = 'Reduce parallel active applications and spend more time on interview readiness for the strongest ones.';
        } else {
            $strategy[] = 'Maintain a focused application pipeline with a small number of strong-fit roles each week.';
        }
        if ($shortlistedCount <= 0) {
            $strategy[] = 'Track which role types are not converting and narrow your search toward jobs with stronger skill overlap.';
        } else {
            $strategy[] = 'Use shortlisted roles as your benchmark and apply to similar jobs in title, stack, and experience band.';
        }
        $strategy[] = 'Avoid applying with one generic resume when the job clearly emphasizes a specific stack or domain.';

        return array_values(array_slice($strategy, 0, 5));
    }

    private function buildWeeklyPlan(array $topSuggestedJobs): array
    {
        $plan = [
            'Review your top suggested jobs and shortlist the 3 best-fit roles for focused applications.',
            'Improve one resume version for your highest-priority role before applying.',
            'Apply to a small batch of strong-match roles instead of a broad volume of weak matches.',
            'Review any active applications and move time into interview preparation for the ones with momentum.',
            'Refresh profile skills, preferences, and headline based on the latest target roles.',
        ];

        if (!empty($topSuggestedJobs)) {
            $plan[1] = 'Improve one resume version for "' . trim((string) ($topSuggestedJobs[0]['title'] ?? 'your top role')) . '" before applying.';
        }

        return $plan;
    }

    private function buildWatchouts(int $activeApplications, array $skills, array $topSuggestedJobs): array
    {
        $watchouts = [];
        if ($activeApplications > 7) {
            $watchouts[] = 'Too many open applications can dilute preparation quality and make follow-up weaker.';
        }
        if (count($skills) < 4) {
            $watchouts[] = 'A thin visible skill set can suppress matching even when you have stronger experience.';
        }
        if (empty($topSuggestedJobs)) {
            $watchouts[] = 'Weak suggestions usually mean profile targeting is too broad or skill coverage is unclear.';
        } else {
            $watchouts[] = 'Do not ignore low match gaps like missing frameworks if they appear repeatedly in your best-fit roles.';
        }

        return array_values(array_slice($watchouts, 0, 4));
    }

    public function getJobSearchStrategy($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $applications = $this->getApplicationsWithDetails($candidateId);
        $topSuggestedJobs = $this->getTopSuggestedJobs($candidateId, 6);
        $jobSearchStrategy = $this->buildJobSearchStrategyCoach($candidateId, $applications, $topSuggestedJobs);

        return $this->respond([
            'status' => 'success',
            'data' => [
                'jobSearchStrategy' => $jobSearchStrategy,
                'topSuggestedJobs' => $topSuggestedJobs,
            ]
        ]);
    }

    public function getPlans($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $subscriptionModel = model('SubscriptionModel');
        $plans = $subscriptionModel->getActivePlans();
        $currentSubscription = $subscriptionModel->getUserActiveSubscription($candidateId);

        $db = \Config\Database::connect();
        $existingTrial = $db->table('user_subscriptions')->where('user_id', $candidateId)->where('amount_paid', 0.00)->get()->getRowArray();
        $hasUsedTrial = !empty($existingTrial);

        return $this->respond([
            'status' => 'success',
            'data' => [
                'plans' => $plans,
                'currentSubscription' => $currentSubscription,
                'trial_days' => defined('SUBSCRIPTION_TRIAL_DAYS') ? SUBSCRIPTION_TRIAL_DAYS : 7,
                'has_used_trial' => $hasUsedTrial
            ]
        ]);
    }

    public function startTrial()
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        $planId = (int) $this->request->getPost('plan_id');

        if ($candidateId <= 0 || $planId <= 0) {
            return $this->fail('Invalid candidate or plan ID');
        }

        $subscriptionModel = model('SubscriptionModel');
        $plan = $subscriptionModel->find($planId);
        if (!$plan) {
            return $this->fail('Invalid plan selected.');
        }

        $db = \Config\Database::connect();
        $existingTrial = $db->table('user_subscriptions')
                            ->where('user_id', $candidateId)
                            ->where('amount_paid', 0.00)
                            ->get()->getRowArray();
        if ($existingTrial) {
            return $this->fail('You have already used your free trial option.');
        }

        $trialDays = defined('SUBSCRIPTION_TRIAL_DAYS') ? SUBSCRIPTION_TRIAL_DAYS : 7;
        
        $subscriptionData = [
            'user_id' => $candidateId,
            'plan_id' => $planId,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime("+$trialDays days")),
            'status' => 'active',
            'amount_paid' => 0.00,
            'payment_id' => 'TRIAL_' . time(),
            'order_id' => 'ORDER_TRIAL_' . time(),
        ];

        $subscriptionModel->insert($subscriptionData);

        return $this->respond([
            'status' => 'success',
            'message' => "Your $trialDays-day free trial has started!"
        ]);
    }

    public function subscribe()
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        $planId = (int) $this->request->getPost('plan_id');

        if ($candidateId <= 0 || $planId <= 0) {
            return $this->fail('Invalid Candidate ID or Plan ID');
        }

        $subscriptionModel = model('SubscriptionModel');
        $plan = $subscriptionModel->find($planId);

        if (empty($plan)) {
            return $this->fail('Invalid Plan');
        }

        // Deactivate existing subscriptions
        $db = \Config\Database::connect();
        $db->table('user_subscriptions')
            ->where('user_id', $candidateId)
            ->where('status', 'active')
            ->update(['status' => 'superseded']);

        // Save new active subscription
        $subscriptionModel->saveSubscription([
            'user_id'    => $candidateId,
            'plan_id'    => $plan['id'],
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+' . (int) $plan['duration_days'] . ' days')),
            'amount_paid'=> $plan['price'],
            'payment_id' => 'mock_payment_' . time(),
            'order_id'   => 'mock_order_' . time(),
            'status'     => 'active',
        ]);

        return $this->respond([
            'status' => 'success',
            'message' => 'Subscription activated successfully'
        ]);
    }
    public function askChatbot()
    {
        $candidateId = (int) ($this->request->getVar('candidate_id') ?? $this->request->getPost('candidate_id') ?? $this->request->getJSON(true)['candidate_id'] ?? 0);

        if (!$candidateId) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'answer'  => 'Candidate ID is required.',
                ]);
        }

        $question = trim((string) ($this->request->getPost('question') ?? $this->request->getJSON(true)['question'] ?? ''));
        if ($question === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'answer'  => 'Please enter a question.',
                ]);
        }

        $service = new \App\Libraries\CandidateChatbotService();
        $result  = $service->answer($candidateId, $question);

        return $this->response->setJSON([
            'success' => true,
            'answer'  => $result['answer'],
        ]);
    }

    public function getChatbotSuggestions()
    {
        $candidateId = (int) ($this->request->getVar('candidate_id') ?? 0);

        if (!$candidateId) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $suggestions = [
            'Find matching jobs from my profile',
            'Show remote PHP jobs',
            'Find hybrid jobs in Bangalore',
            'Save job #ID',
            'Apply to job #ID',
            'Compare job #ID and job #ID',
            'Explain why job #ID matches me',
        ];

        return $this->response->setJSON([
            'success'     => true,
            'suggestions' => $suggestions,
        ]);
    }

    public function getCompanyDiscovery()
    {
        $candidateId = (int) ($this->request->getVar('candidate_id') ?? 0);
        
        if (!$candidateId) {
            return $this->failUnauthorized('Candidate ID is required');
        }

        $companyModel = new \App\Models\CompanyModel();
        $request = service('request');

        $filters = [
            'q'        => trim((string) $request->getGet('q')),
            'industry' => trim((string) $request->getGet('industry')),
            'location' => trim((string) $request->getGet('location')),
            'segment'  => trim((string) $request->getGet('segment')),
            'jobs'     => trim((string) $request->getGet('jobs')),
        ];

        $db = \Config\Database::connect();
        $companyFields = $db->getFieldNames('companies') ?: [];
        $hasCompanyType = in_array('company_type', $companyFields, true);
        $hasCompanyTags = in_array('company_tags', $companyFields, true);
        $hasVerified = in_array('is_verified', $companyFields, true);
        $hasFeatured = in_array('is_featured', $companyFields, true);
        $hasProfileStatus = in_array('profile_status', $companyFields, true);

        $segments = $this->companyDiscoverySegments();
        $activeSegment = $segments[$filters['segment']] ?? null;

        $companiesBuilder = $companyModel
            ->select('companies.*, COUNT(DISTINCT jobs.id) AS open_jobs_count')
            ->join('jobs', "jobs.company_id = companies.id AND jobs.status = 'open'", 'left')
            ->groupBy('companies.id');

        if (!empty($filters['q'])) {
            $companiesBuilder->groupStart()
                ->like('companies.name', $filters['q'], 'both')
                ->orLike('companies.industry', $filters['q'], 'both')
                ->orLike('companies.short_description', $filters['q'], 'both')
                ->groupEnd();
        }
        if (!empty($filters['industry'])) {
            $companiesBuilder->where('companies.industry', $filters['industry']);
        }
        if (!empty($filters['location'])) {
            $companiesBuilder->groupStart()
                             ->like('companies.hq', $filters['location'], 'both')
                             ->orLike('companies.branches', $filters['location'], 'both')
                             ->groupEnd();
        }
        if ($activeSegment) {
            $companiesBuilder->groupStart();
            foreach ($activeSegment['terms'] as $index => $term) {
                if ($index === 0) {
                    $companiesBuilder->like('companies.industry', $term, 'both');
                } else {
                    $companiesBuilder->orLike('companies.industry', $term, 'both');
                }
                if ($hasCompanyType) {
                    $companiesBuilder->orLike('companies.company_type', $term, 'both');
                }
                if ($hasCompanyTags) {
                    $companiesBuilder->orLike('companies.company_tags', $term, 'both');
                }
                $companiesBuilder->orLike('companies.short_description', $term, 'both');
            }
            $companiesBuilder->groupEnd();
        }
        if ($filters['jobs'] === 'active') {
            $companiesBuilder->having('open_jobs_count >', 0);
        }

        if ($hasFeatured) {
            $companiesBuilder->orderBy('companies.is_featured', 'DESC');
        }
        if ($hasVerified) {
            $companiesBuilder->orderBy('companies.is_verified', 'DESC');
        }
        $companiesBuilder
            ->orderBy('open_jobs_count', 'DESC')
            ->orderBy('companies.name', 'ASC');

        $page = (int) $request->getGet('page');
        if ($page <= 0) $page = 1;
        
        $companiesPerPage = 16;
        $totalCompaniesBuilder = clone $companiesBuilder;
        $totalCompanies = $totalCompaniesBuilder->countAllResults(false);
        $companies = $companiesBuilder->paginate($companiesPerPage, 'default', $page);
        
        // Ensure URLs are absolute for mobile
        foreach ($companies as &$company) {
            $company['open_jobs_count'] = (int) ($company['open_jobs_count'] ?? 0);
            if (!empty($company['logo'])) {
                $company['logo'] = preg_match('/^https?:\/\//i', $company['logo'])
                    ? $company['logo']
                    : base_url(ltrim($company['logo'], '/'));
            } else {
                $website = (string) ($company['website'] ?? '');
                $websiteHost = $website !== '' ? (parse_url($website, PHP_URL_HOST) ?: $website) : '';
                $websiteHost = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
                if ($websiteHost !== '') {
                    $company['logo'] = 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96';
                } else {
                    $company['logo'] = '';
                }
            }
            $company['discovery_tags'] = $this->buildCompanyDiscoveryTags($company, $hasCompanyType, $hasCompanyTags, $hasVerified, $hasProfileStatus);
        }
        unset($company);

        $industries = $companyModel->select('industry')->distinct()->where('industry IS NOT NULL')->where('industry !=', '')->orderBy('industry', 'ASC')->findAll();
        $industries = array_column($industries, 'industry');
        $segmentCards = $this->companyDiscoverySegmentCards($segments, $hasCompanyType, $hasCompanyTags);
        $allCompanyCount = (int) $db->table('companies')->countAllResults();

        return $this->respond([
            'success' => true,
            'companies' => $companies,
            'filters' => $filters,
            'industries' => $industries,
            'segments' => $segmentCards,
            'allCompanyCount' => $allCompanyCount,
            'currentPage' => $page,
            'totalPages' => ceil($totalCompanies / $companiesPerPage),
            'totalItems' => $totalCompanies,
        ]);
    }

    private function companyDiscoverySegments(): array
    {
        return [
            'indian-mnc' => ['label' => 'Indian MNCs', 'icon' => 'fa-building-flag', 'terms' => ['indian mnc', 'mnc', 'enterprise', 'corporate']],
            'global-indian' => ['label' => 'Global Indian', 'icon' => 'fa-globe-asia', 'terms' => ['global indian', 'global', 'export', 'international']],
            'corporate' => ['label' => 'Corporate', 'icon' => 'fa-city', 'terms' => ['corporate', 'enterprise', 'large company']],
            'startups' => ['label' => 'Startups', 'icon' => 'fa-rocket', 'terms' => ['startup', 'saas', 'product']],
            'product' => ['label' => 'Product Companies', 'icon' => 'fa-cube', 'terms' => ['product', 'saas', 'platform']],
            'service' => ['label' => 'Service Companies', 'icon' => 'fa-people-carry-box', 'terms' => ['service', 'services', 'consulting', 'agency']],
            'remote-friendly' => ['label' => 'Remote Friendly', 'icon' => 'fa-laptop-house', 'terms' => ['remote', 'hybrid', 'distributed']],
            'freshers' => ['label' => 'Freshers Hiring', 'icon' => 'fa-user-graduate', 'terms' => ['fresher', 'graduate', 'entry level', 'junior']],
        ];
    }

    private function companyDiscoverySegmentCards(array $segments, bool $hasCompanyType, bool $hasCompanyTags): array
    {
        $db = \Config\Database::connect();
        $cards = [];

        foreach ($segments as $key => $segment) {
            $builder = $db->table('companies');
            $builder->groupStart();
            foreach ($segment['terms'] as $index => $term) {
                if ($index === 0) {
                    $builder->like('industry', $term, 'both');
                } else {
                    $builder->orLike('industry', $term, 'both');
                }
                if ($hasCompanyType) {
                    $builder->orLike('company_type', $term, 'both');
                }
                if ($hasCompanyTags) {
                    $builder->orLike('company_tags', $term, 'both');
                }
                $builder->orLike('short_description', $term, 'both');
            }
            $builder->groupEnd();
            $count = (int) $builder->countAllResults();

            $cards[] = [
                'key' => $key,
                'label' => $segment['label'],
                'icon' => $segment['icon'],
                'count' => $count,
            ];
        }

        return $cards;
    }

    private function buildCompanyDiscoveryTags(array $company, bool $hasCompanyType, bool $hasCompanyTags, bool $hasVerified, bool $hasProfileStatus): array
    {
        $tags = [];
        if ($hasCompanyType && trim((string) ($company['company_type'] ?? '')) !== '') {
            $tags[] = trim((string) $company['company_type']);
        }
        if (trim((string) ($company['industry'] ?? '')) !== '') {
            $tags[] = trim((string) $company['industry']);
        }
        if ($hasCompanyTags && trim((string) ($company['company_tags'] ?? '')) !== '') {
            foreach (preg_split('/[,|;\n]+/', (string) $company['company_tags']) ?: [] as $tag) {
                $tag = trim($tag);
                if ($tag !== '') {
                    $tags[] = $tag;
                }
            }
        }
        if ($hasVerified && (int) ($company['is_verified'] ?? 0) === 1) {
            $tags[] = 'Verified';
        } elseif ($hasProfileStatus) {
            $status = trim((string) ($company['profile_status'] ?? ''));
            if ($status !== '') {
                $tags[] = ucfirst(str_replace('_', ' ', $status));
            }
        }

        return array_slice(array_values(array_unique($tags)), 0, 4);
    }
}
