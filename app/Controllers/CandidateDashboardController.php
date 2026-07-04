<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\AiInterviewPrepCoach;
use App\Libraries\AiJobSearchStrategyCoach;
use App\Libraries\AtsScoreService;
use App\Models\CandidateSkillsModel;
use App\Models\BlogModel;
use App\Models\CompanyModel;
use App\Models\JobModel;
use App\Models\MncJobModel;
use App\Models\RecruiterCandidateActionModel;
use App\Models\SubscriptionModel;
use App\Models\UserModel;

class CandidateDashboardController extends BaseController
{
    public function index()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $candidateId = session()->get('user_id');
        
        if (!$candidateId) {
            return redirect()->to('/login')->with('error', 'Please login to continue');
        }
        
        // Get all applications for this candidate without generating interview-prep content on the dashboard.
        $applications = $this->getApplicationsWithDetails($candidateId, false);
        
        // Get statistics
        $stats = $this->calculateStats($candidateId);
        $profileStrength = $this->calculateProfileStrength($candidateId);
        
        // Get pending actions
        $pendingActions = $this->getPendingActions($candidateId);
        
        // Get notifications
        $notifications = $this->getRecentNotifications($candidateId);

        // Get base profile info to check for uploaded resume
        $userModel = model('UserModel');
        $userProfile = $userModel->findCandidateWithProfile((int) $candidateId) ?? [];
        $hasBaseResume = !empty($userProfile['resume_path']);

        // Get primary resume ID for ATS analysis
        $primaryResumeId = 0;
        if ($candidateId > 0 && \Config\Database::connect()->tableExists('candidate_resume_versions')) {
            $primary = model('CandidateResumeVersionModel')
                ->where('candidate_id', $candidateId)
                ->where('is_primary', 1)
                ->first();
            $primaryResumeId = (int) ($primary['id'] ?? 0);
        }

            // Check subscription status to determine if we should show trial promotions
            $subscriptionModel = model(SubscriptionModel::class);
            $activeSubscription = $subscriptionModel->getUserActiveSubscription($candidateId);
            $hasActiveSubscription = !empty($activeSubscription);

        // Top suggested jobs for dashboard (best matches only)
        $topSuggestedJobs = $this->getTopSuggestedJobs($candidateId, 4);
        $dailyReminder = $this->buildDailyReminder($candidateId, $applications, $topSuggestedJobs);
            $engagementBanners = $this->buildDashboardEngagementBanners($candidateId, $applications, $topSuggestedJobs, (string) ($dailyReminder['key'] ?? ''), $hasActiveSubscription);
        $applications = $this->maskApplicationsList($applications);

        // Top hiring companies for dashboard
        $topHiringCompanies = $this->getTopHiringCompanies(8);

        // Fetch recent blog posts for the dashboard (Naukri style)
        $blogPosts = [];
        $db = \Config\Database::connect();
        if ($db->tableExists('blog_posts')) {
            $blogPosts = model('BlogModel')->getPublishedPosts(3);
        }

        // Get job category shortcuts (Data Science, ML, etc.) for the dashboard
        $jobCategories = $this->getJobCategoryShortcuts();

        $calculatedExperience = $userModel->calculateExperienceLevel((int) $candidateId);

        return view('candidate/dashboard', [
            'applications' => $applications,
            'stats' => $stats,
            'profileStrength' => $profileStrength,
            'pendingActions' => $pendingActions,
            'notifications' => $notifications,
            'dailyReminder' => $dailyReminder,
            'engagementBanners' => $engagementBanners,
            'topSuggestedJobs' => $topSuggestedJobs,
            'topHiringCompanies' => $topHiringCompanies,
            'primaryResumeId' => $primaryResumeId,
            'hasBaseResume' => $hasBaseResume,
            'blogPosts' => $blogPosts,
            'jobCategories' => $jobCategories,
            'calculatedExperience' => $calculatedExperience,
        ]);
    }
    
    /**
     * Get applications with all details including scores
     */
    private function getApplicationsWithDetails($candidateId, bool $includeInterviewPrep = false)
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
        
        // Add next action for each application
        foreach ($applications as &$application) {
            $application = $this->enrichApplicationData($application, $includeInterviewPrep);
        }

        $applicationIds = array_values(array_filter(array_map(static function ($application) {
            return (int) ($application['id'] ?? 0);
        }, $applications)));
        $applicationJobMap = [];
        foreach ($applications as $application) {
            $appId = (int) ($application['id'] ?? 0);
            $jobId = (int) ($application['job_id'] ?? 0);
            if ($appId > 0 && $jobId > 0) {
                $applicationJobMap[$appId] = $jobId;
            }
        }

        $actionModel = new RecruiterCandidateActionModel();
        $activitySummary = $actionModel->getSummaryByApplicationIds((int) $candidateId, $applicationIds);
        $jobActivitySummary = $actionModel->getSummaryByApplicationJobMap((int) $candidateId, $applicationJobMap);

        foreach ($jobActivitySummary as $appId => $jobActivity) {
            if (!isset($activitySummary[$appId])) {
                $activitySummary[$appId] = $jobActivity;
                continue;
            }

            foreach (['profile_unique_recruiters', 'contact_unique_recruiters', 'resume_unique_recruiters', 'profile_viewed_count', 'contact_viewed_count', 'resume_downloaded_count'] as $key) {
                $activitySummary[$appId][$key] = (int) ($activitySummary[$appId][$key] ?? 0) + (int) ($jobActivity[$key] ?? 0);
            }

            $existingLast = strtotime((string) ($activitySummary[$appId]['last_recruiter_activity_at'] ?? '')) ?: 0;
            $jobLast = strtotime((string) ($jobActivity['last_recruiter_activity_at'] ?? '')) ?: 0;
            if ($jobLast > $existingLast) {
                $activitySummary[$appId]['last_recruiter_activity_at'] = $jobActivity['last_recruiter_activity_at'] ?? null;
            }
        }

        foreach ($applications as &$application) {
            $appId = (int) ($application['id'] ?? 0);
            $application['recruiter_activity'] = $activitySummary[$appId] ?? [
                'profile_unique_recruiters' => 0,
                'contact_unique_recruiters' => 0,
                'resume_unique_recruiters' => 0,
                'profile_viewed_count' => 0,
                'contact_viewed_count' => 0,
                'resume_downloaded_count' => 0,
                'last_recruiter_activity_at' => null,
            ];
        }
        unset($application);
        
        return $applications;
    }

    protected function enrichApplicationData(array $application, bool $includeInterviewPrep = false): array
    {
        // Handle Client Company Visibility
        if (($application['posted_for'] ?? '') === 'client') {
            if (($application['client_disclosure'] ?? '') === 'visible' && !empty($application['client_company_name'])) {
                $application['company'] = $application['client_company_name'];
            } else {
                $application['company'] = ($application['company'] ?? 'Recruiter') . ' (Hiring for a Client)';
            }
        }

        // Handle Candidate Fee Disclaimer
        if (isset($application['candidate_fee_allowed']) && (int) $application['candidate_fee_allowed'] === 0) {
            $application['fee_disclaimer'] = 'Candidate fees are never allowed on this portal.';
        }

        $application['next_action'] = $this->getNextAction($application);
        $application['interview_prep'] = $includeInterviewPrep ? $this->buildInterviewPrepCoach($application) : [];
        $application['interview_review_label'] = $this->formatInterviewReviewStatus((string) ($application['interview_review_status'] ?? ''));

        return $application;
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

    private function buildInterviewPrepCoach(array $application, bool $useAiGeneration = true): array
    {
        if (in_array((string) ($application['status'] ?? ''), ['filtered_out', 'rejected', 'withdrawn', 'selected', 'hired'], true)) {
            return [];
        }

        $requiredSkills = $this->tokenizeCsv((string) ($application['required_skills'] ?? ''));
        $focusSkills = array_slice($requiredSkills, 0, 5);
        $jobTitle = trim((string) ($application['job_title'] ?? 'this role'));
        $targetRole = trim((string) ($application['resume_version_target_role'] ?? ''));
        $resumeTitle = trim((string) ($application['resume_version_title'] ?? ''));
        $policy = strtoupper((string) ($application['ai_interview_policy'] ?? JobModel::AI_POLICY_REQUIRED_HARD));
        $status = (string) ($application['status'] ?? '');

        $coachTitle = 'Pre-interview Preparation Coach';
        if ($status === 'shortlisted' || $status === 'interview_slot_booked') {
            $coachTitle = 'HR Interview Preparation Coach';
        } elseif ($policy !== JobModel::AI_POLICY_OFF) {
            $coachTitle = 'AI Interview Preparation Coach';
        }

        $talkingPoints = [];
        if ($targetRole !== '') {
            $talkingPoints[] = 'Explain why your background fits the target role "' . $targetRole . '".';
        }
        if ($resumeTitle !== '') {
            $talkingPoints[] = 'Use examples from your saved resume version "' . $resumeTitle . '".';
        }
        if (!empty($focusSkills)) {
            $talkingPoints[] = 'Prepare project stories around ' . implode(', ', array_slice($focusSkills, 0, 3)) . '.';
        }
        if (!empty($application['experience_level'])) {
            $talkingPoints[] = 'Be ready to justify your experience level: ' . trim((string) $application['experience_level']) . '.';
        }
        if (empty($talkingPoints)) {
            $talkingPoints[] = 'Prepare two role-relevant examples with measurable outcomes.';
        }

        $checklist = [
            'Review the job description and map your strongest experience to the role.',
            'Prepare concise STAR-format examples for one challenge, one achievement, and one collaboration story.',
            'Keep your resume, project examples, and skill claims consistent.',
        ];

        if (!empty($focusSkills)) {
            $checklist[] = 'Revise the top skills recruiters are likely to test: ' . implode(', ', array_slice($focusSkills, 0, 4)) . '.';
        }

        if ($policy !== JobModel::AI_POLICY_OFF && $status === 'applied') {
            $checklist[] = 'Practice answering clearly on camera with short, structured responses for the AI round.';
        }

        $likelyQuestions = [];
        foreach (array_slice($focusSkills, 0, 3) as $skill) {
            $likelyQuestions[] = 'Describe a real example where you used ' . $skill . '.';
        }
        $likelyQuestions[] = 'Why are you interested in this ' . $jobTitle . ' role?';
        $likelyQuestions[] = 'What problem did you solve recently that best shows your fit for this job?';

        $fallback = [
            'title' => $coachTitle,
            'focus_skills' => $focusSkills,
            'talking_points' => $talkingPoints,
            'checklist' => $checklist,
            'likely_questions' => array_slice($likelyQuestions, 0, 5),
            'source' => 'fallback',
        ];

        if (!$useAiGeneration) {
            return $fallback;
        }

        return (new AiInterviewPrepCoach())->generate($application, $fallback);
    }

    private function buildDailyReminder(int $candidateId, array $applications, array $topSuggestedJobs): array
    {
        $userModel = model('UserModel');
        $notificationModel = model('NotificationModel');
        $skillsModel = new CandidateSkillsModel();

        $user = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId) ?? [];
        $skillsRow = $skillsModel->where('candidate_id', $candidateId)->first() ?? [];
        $profileStrength = $this->calculateProfileStrength($candidateId);
        $unreadNotifications = $notificationModel
            ->where('user_id', $candidateId)
            ->where('is_read', 0)
            ->countAllResults();
        $activeApplications = array_values(array_filter($applications, static function (array $application): bool {
            return !in_array((string) ($application['status'] ?? ''), ['filtered_out', 'rejected', 'withdrawn', 'selected', 'hired'], true);
        }));
        $practiceApplication = $this->findBestPracticeApplication($applications);
        $suggestedJob = $topSuggestedJobs[0] ?? [];

        $tasks = [
            [
                'key' => 'resume_tip',
                'notification_type' => 'daily_resume_tip',
                'title' => 'Strengthen one resume point today',
                'message' => 'Rewrite one bullet so it shows what you changed and what improved.',
                'detail' => 'A clear action-and-result bullet gives recruiters proof, not just claims.',
                'motivation' => 'Small edits like this make your profile feel stronger and more credible.',
                'action_text' => 'Improve resume',
                'action_link' => base_url('candidate/profile'),
                'score' => 0,
            ],
            [
                'key' => 'interview_practice',
                'notification_type' => 'daily_interview_practice',
                'title' => 'Turn your experience into a strong answer',
                'message' => 'Practice one 60-second answer using a real example from your work.',
                'detail' => 'When your interview story matches your resume, your profile feels more convincing.',
                'motivation' => 'This is how effort starts turning into confidence during interviews.',
                'action_text' => 'Practice answer',
                'action_link' => base_url('candidate/applications'),
                'score' => 0,
            ],
            [
                'key' => 'job_search_task',
                'notification_type' => 'daily_job_search_task',
                'title' => 'Apply where your profile has the best chance',
                'message' => 'Pick one relevant role that matches the strengths already visible in your profile.',
                'detail' => 'Focused applications usually perform better than sending many weak ones.',
                'motivation' => 'The right application is proof that your preparation is moving you forward.',
                'action_text' => 'View best match',
                'action_link' => base_url('jobs?tab=suggested'),
                'score' => 0,
            ],
            [
                'key' => 'skill_prompt',
                'notification_type' => 'daily_skill_prompt',
                'title' => 'Close one skill gap step by step',
                'message' => 'Review one skill your target role needs and decide how you will strengthen it.',
                'detail' => 'Even a small learning step makes your direction clearer and your profile more focused.',
                'motivation' => 'Progress becomes motivating when you can see exactly what to improve next.',
                'action_text' => 'Review skill plan',
                'action_link' => base_url('candidate/job-search-strategy'),
                'score' => 0,
            ],
            [
                'key' => 'followup_reminder',
                'notification_type' => 'daily_followup_reminder',
                'title' => 'Stay close to recruiter momentum',
                'message' => 'Check whether any recruiter has viewed, replied, or moved your application forward.',
                'detail' => 'Following up helps you spot progress and respond while interest is still warm.',
                'motivation' => 'Seeing movement is motivating because it shows your effort is being noticed.',
                'action_text' => 'Check updates',
                'action_link' => base_url('notifications'),
                'score' => 0,
            ],
            [
                'key' => 'cover_letter_task',
                'notification_type' => 'daily_career_reminder',
                'title' => 'Personalize your pitch',
                'message' => 'Create a tailored cover letter for your top matching job.',
                'detail' => 'A personalized note shows recruiters you have researched the role specifically.',
                'motivation' => 'Tailored applications have a 40% higher response rate than generic ones.',
                'action_text' => 'Generate Letter',
                'action_link' => base_url('jobs?tab=suggested'),
                'score' => 0,
            ],
        ];

        $hasResume = !empty($user['resume_path']);
        $hasBio = !empty($user['bio']);
        $hasPreferredTitles = !empty($user['preferred_job_titles']);
        $hasSkills = !empty($skillsRow['skill_name']);

        foreach ($tasks as &$task) {
            switch ($task['key']) {
                case 'resume_tip':
                    if (!$hasResume) {
                        $task['score'] += 40;
                        $task['message'] = 'Upload your resume so recruiters can start evaluating your experience.';
                        $task['detail'] = 'A complete profile gets considered more seriously than an incomplete one.';
                        $task['motivation'] = 'Finishing this step gives your applications a real base to grow from.';
                        $task['action_text'] = 'Upload resume';
                    }
                    if (!$hasBio) {
                        $task['score'] += 15;
                    }
                    if ($profileStrength < 70) {
                        $task['score'] += 10;
                    }
                    if (!empty($activeApplications)) {
                        $task['score'] += 5;
                    }
                    break;

                case 'interview_practice':
                    if (!empty($activeApplications)) {
                        $task['score'] += 30;
                    }
                    foreach ($activeApplications as $application) {
                        if (in_array((string) ($application['status'] ?? ''), ['applied', 'ai_interview_completed', 'shortlisted', 'interview_slot_booked'], true)) {
                            $task['score'] += 15;
                            break;
                        }
                    }
                    foreach ($activeApplications as $application) {
                        if (strtoupper((string) ($application['ai_interview_policy'] ?? JobModel::AI_POLICY_REQUIRED_HARD)) !== JobModel::AI_POLICY_OFF) {
                            $task['score'] += 10;
                            $jobTitle = trim((string) ($application['job_title'] ?? 'your target role'));
                            $task['message'] = 'Practice one short answer for ' . $jobTitle . ' using a real result from your work.';
                            break;
                        }
                    }
                    if ($practiceApplication) {
                        $task['action_link'] = base_url('candidate/applications/' . (int) $practiceApplication['id'] . '/mock-interview');
                    }
                    break;

                case 'job_search_task':
                    if (!empty($topSuggestedJobs)) {
                        $task['score'] += 30;
                        $jobTitle = trim((string) ($suggestedJob['title'] ?? 'a strong-fit role'));
                        $task['message'] = 'Apply to ' . $jobTitle . ' if it matches the experience you already present well.';
                    }
                    if (count($applications) < 3) {
                        $task['score'] += 10;
                    }
                    if (empty($activeApplications)) {
                        $task['score'] += 10;
                    }
                    break;

                case 'skill_prompt':
                    if (!$hasPreferredTitles) {
                        $task['score'] += 25;
                        $task['message'] = 'Define your target role so your profile and job search point in the same direction.';
                        $task['detail'] = 'Clear targets help the portal suggest better roles and better next steps.';
                        $task['action_text'] = 'Set target role';
                    }
                    if (!$hasSkills) {
                        $task['score'] += 15;
                        $task['message'] = 'Add your core skills so recruiters can match your profile more accurately.';
                        $task['detail'] = 'Skills make your strengths easier to notice in search and screening.';
                        $task['action_text'] = 'Add strengths';
                        $task['action_link'] = base_url('candidate/profile');
                    }
                    if ($profileStrength < 80) {
                        $task['score'] += 10;
                    }
                    break;

                case 'followup_reminder':
                    if ($unreadNotifications > 0) {
                        $task['score'] += 35;
                    }
                    foreach ($applications as $application) {
                        $activity = (array) ($application['recruiter_activity'] ?? []);
                        $activityCount = (int) ($activity['profile_viewed_count'] ?? 0)
                            + (int) ($activity['contact_viewed_count'] ?? 0)
                            + (int) ($activity['resume_downloaded_count'] ?? 0);
                        if ($activityCount > 0) {
                            $task['score'] += 15;
                            $task['message'] = 'A recruiter has already shown interest. Check the latest updates and respond quickly if needed.';
                            break;
                        }
                    }
                    if (!empty($activeApplications)) {
                        $task['score'] += 5;
                    }
                    break;
                
                case 'cover_letter_task':
                    if (!empty($topSuggestedJobs)) {
                        $task['score'] += 20;
                    }
                    if ($profileStrength > 80) {
                        $task['score'] += 10;
                    }
                    break;
            }

            $task['tie_breaker'] = abs(crc32(date('Y-m-d') . '|' . $candidateId . '|' . $task['key']));
        }
        unset($task);

        usort($tasks, static function (array $left, array $right): int {
            $scoreCompare = ($right['score'] ?? 0) <=> ($left['score'] ?? 0);
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return ($left['tie_breaker'] ?? 0) <=> ($right['tie_breaker'] ?? 0);
        });

        $task = $tasks[0];
        if ($task['key'] === 'followup_reminder' && $unreadNotifications <= 0) {
            $task['action_text'] = 'Open updates';
        }

        $task['banner_label'] = 'Next best action';
        $task['tips'] = $this->buildDashboardActionTips($task['key']);

        return $task;
    }

    private function buildDashboardActionTips(string $taskKey): array
    {
        return match ($taskKey) {
            'resume_tip' => [
                'Strong resume bullets help recruiters see outcomes, not just responsibilities.',
                'One better project point can make the whole profile feel more credible.',
                'Clear results build confidence because they show what you can really deliver.',
            ],
            'interview_practice' => [
                'When your answer matches your resume, your story feels more trustworthy.',
                'Short, real examples usually make a stronger impression than long explanations.',
                'Practice turns past effort into confident communication.',
            ],
            'job_search_task' => [
                'Focused applications usually perform better than applying everywhere.',
                'The best-fit roles are where your current strengths can stand out fastest.',
                'Applying with direction feels better than applying with pressure.',
            ],
            'skill_prompt' => [
                'A visible learning direction makes your profile look more intentional.',
                'Closing one skill gap is often more useful than trying to improve everything at once.',
                'Candidates stay motivated when the next improvement step is clear.',
            ],
            'followup_reminder' => [
                'Quick follow-up keeps momentum alive when recruiter interest is already there.',
                'Progress feels real when you can see who viewed or moved your application.',
                'Responding at the right time can turn interest into the next step.',
            ],
            'cover_letter_task' => [
                'A cover letter is your chance to explain "Why you?" beyond just your skills.',
                'Focus on one specific problem the company has and how you can solve it.',
                'Keep it brief—recruiters usually scan cover letters in under 30 seconds.',
            ],
            default => [
                'Small, focused improvements help your profile get stronger over time.',
                'A clear next step keeps your job search active without feeling overwhelming.',
                'Consistent effort matters more than trying to do everything at once.',
            ],
        };
    }

     function buildDashboardEngagementBanners(int $candidateId, array $applications, array $topSuggestedJobs, string $activeKey = '', bool $hasActiveSubscription = false): array
    {
        $user = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
        $skillsRow = (new CandidateSkillsModel())->where('candidate_id', $candidateId)->first() ?? [];
        $candidateSkills = $this->tokenizeCsv((string) ($skillsRow['skill_name'] ?? ''));
        $practiceApplication = $this->findBestPracticeApplication($applications);
        $suggestedJob = $topSuggestedJobs[0] ?? [];
        $suggestedJobId = (int) ($suggestedJob['id'] ?? 0);
        $suggestedJobTitle = trim((string) ($suggestedJob['title'] ?? 'relevant jobs'));
        $suggestedJobCompany = trim((string) ($suggestedJob['company'] ?? ''));
        $resumePrompt = $this->buildResumeImprovementPrompt($candidateId, $applications, $topSuggestedJobs, $candidateSkills);
        $interviewPrompt = $this->buildInterviewPracticePrompt($candidateId, $practiceApplication);
        $skillPrompt = $this->buildSkillGapPrompt($candidateId, $user, $candidateSkills, $topSuggestedJobs);
        $followupPrompt = $this->buildFollowupPrompt($applications);

        $banners = [
            [
                'key' => 'resume_tip',
                'label' => 'Daily resume tip',
                'title' => $resumePrompt['title'],
                'message' => $resumePrompt['message'],
                'action_text' => 'Open Resume',
                'action_link' => base_url('candidate/profile'),
            ],
            [
                'key' => 'interview_practice',
                'label' => 'Daily interview practice',
                'title' => $interviewPrompt['title'],
                'message' => $interviewPrompt['message'],
                'action_text' => 'Start Practice',
                'action_link' => $practiceApplication
                    ? base_url('candidate/applications/' . (int) $practiceApplication['id'] . '/mock-interview')
                    : base_url('candidate/applications'),
            ],
            [
                'key' => 'job_search_task',
                'label' => 'Daily job search task',
                'title' => $suggestedJobId > 0
                    ? 'Apply to ' . $suggestedJobTitle . ($suggestedJobCompany !== '' ? ' at ' . $suggestedJobCompany : '')
                    : 'Apply to 1 relevant job',
                'message' => $suggestedJobId > 0
                    ? 'Start with ' . $suggestedJobTitle . ' if it matches the strengths already visible in your profile.'
                    : 'Choose one strong-fit role so your search stays focused and easier to manage.',
                'action_text' => $suggestedJobId > 0 ? 'View Matching Job' : 'Browse Jobs',
                'action_link' => $suggestedJobId > 0
                    ? base_url('job/' . $suggestedJobId)
                    : base_url('jobs?tab=suggested'),
            ],
            [
                'key' => 'skill_prompt',
                'label' => 'Daily skill prompt',
                'title' => $skillPrompt['title'],
                'message' => $skillPrompt['message'],
                'action_text' => 'Review Skill Plan',
                'action_link' => $skillPrompt['action_link'],
            ],
            [
                'key' => 'followup_reminder',
                'label' => 'Daily follow-up reminder',
                'title' => $followupPrompt['title'],
                'message' => $followupPrompt['message'],
                'action_text' => $followupPrompt['action_text'],
                'action_link' => $followupPrompt['action_link'],
            ],
        ];

        // Inject a Free Trial promotion if the candidate is not currently a premium member
        if (!$hasActiveSubscription) {
            array_unshift($banners, [
                'key' => 'free_trial_promo',
                'label' => 'Limited Offer',
                'title' => 'Try AI Career Mentor for Free',
                'message' => 'Get 7 days of full access to personalized career roadmaps and interview coaching at no cost.',
                'action_text' => 'Start Free Trial',
                'action_link' => base_url('premium/plans?service=mentor'),
            ]);
        }

        $activeIndex = 0;
        foreach ($banners as $index => $banner) {
            if ($activeKey !== '' && $banner['key'] === $activeKey) {
                $activeIndex = $index;
                break;
            }
        }

        return [
            'active_index' => $activeIndex,
            'items' => $banners,
        ];
    }

    private function buildResumeImprovementPrompt(int $candidateId, array $applications, array $topSuggestedJobs, array $candidateSkills): array
    {
        $role = trim((string) ($topSuggestedJobs[0]['title'] ?? $applications[0]['job_title'] ?? 'your target role'));
        $seedKey = 'resume';
        $focusTerms = array_values(array_filter([
            trim((string) ($applications[0]['resume_version_target_role'] ?? '')),
            trim((string) ($applications[0]['job_title'] ?? '')),
            trim((string) ($topSuggestedJobs[0]['title'] ?? '')),
            !empty($candidateSkills) ? ucwords((string) $candidateSkills[0]) : '',
        ]));
        $focus = $this->pickDailyString($focusTerms, $candidateId, $seedKey) ?: $role;

        return [
            'title' => 'Improve one bullet for ' . $focus . ' today',
            'message' => 'Rewrite it like this: "Improved [feature or process] by [what you did], leading to [result or impact]."',
        ];
    }

    private function buildInterviewPracticePrompt(int $candidateId, ?array $practiceApplication): array
    {
        $questions = [];
        if ($practiceApplication) {
            $summaryPrep = $practiceApplication['interview_prep'] ?? $this->buildInterviewPrepCoach($practiceApplication, false);
            $questions = array_values(array_filter(array_map('trim', (array) ($summaryPrep['likely_questions'] ?? []))));
        }

        if (empty($questions)) {
            $questions = [
                'Tell me about a time you handled conflicting priorities.',
                'Describe a project where you had to solve a tough problem under pressure.',
                'Tell me about a time you improved a process or feature.',
                'Describe a situation where you had to communicate a difficult tradeoff.',
            ];
        }

        $question = $this->pickDailyString($questions, $candidateId, 'interview');

        return [
            'title' => $question !== '' ? $question : 'Answer one behavioral question in 60 seconds',
            'message' => 'Keep it to one real example with clear context, action, and result.',
        ];
    }

    private function buildSkillGapPrompt(int $candidateId, array $user, array $candidateSkills, array $topSuggestedJobs): array
    {
        $preferredRoles = array_values(array_filter(array_map('trim', preg_split('/[,|\\/]+/', (string) ($user['preferred_job_titles'] ?? '')) ?: [])));
        $targetRole = $this->pickDailyString(array_merge($preferredRoles, array_values(array_filter(array_map(static function (array $job): string {
            return trim((string) ($job['title'] ?? ''));
        }, $topSuggestedJobs)))), $candidateId, 'target-role');

        $requiredSkills = [];
        foreach ($topSuggestedJobs as $job) {
            $requiredSkills = array_merge($requiredSkills, $this->tokenizeCsv((string) ($job['required_skills'] ?? '')));
        }
        $requiredSkills = array_values(array_unique($requiredSkills));
        $candidateSkillMap = array_map('strtolower', $candidateSkills);
        $skillGaps = array_values(array_filter($requiredSkills, static function (string $skill) use ($candidateSkillMap): bool {
            return $skill !== '' && !in_array(strtolower($skill), $candidateSkillMap, true);
        }));
        $gap = $this->pickDailyString($skillGaps, $candidateId, 'skill-gap');

        if ($gap !== '') {
            return [
                'title' => 'Review your gap in ' . ucwords($gap),
                'message' => 'Check how ' . ucwords($gap) . ' appears in your target roles and decide what proof or practice to add next.',
                'action_link' => base_url('candidate/job-search-strategy'),
            ];
        }

        return [
            'title' => 'Review one skill gap for your target role',
            'message' => $targetRole !== ''
                ? 'Use ' . $targetRole . ' as your target and decide which skill deserves the next improvement step.'
                : 'Choose one skill that deserves the next improvement step in your profile.',
            'action_link' => base_url('candidate/job-search-strategy'),
        ];
    }

    private function buildFollowupPrompt(array $applications): array
    {
        $bestActivity = null;
        $bestScore = -1;
        foreach ($applications as $application) {
            $activity = (array) ($application['recruiter_activity'] ?? []);
            $score = (int) ($activity['profile_viewed_count'] ?? 0)
                + (int) ($activity['contact_viewed_count'] ?? 0)
                + (int) ($activity['resume_downloaded_count'] ?? 0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestActivity = [
                    'job_title' => trim((string) ($application['job_title'] ?? 'your application')),
                    'score' => $score,
                ];
            }
        }

        if ($bestActivity && $bestActivity['score'] > 0) {
            return [
                'title' => 'Check updates for ' . $bestActivity['job_title'],
                'message' => 'Recruiter activity is happening here, so this is the best place to respond quickly and stay visible.',
                'action_text' => 'Check Updates',
                'action_link' => base_url('notifications'),
            ];
        }

        return [
            'title' => 'Check if you got a response from recruiters',
            'message' => 'Open your updates and stay ready for replies, profile views, or the next application step.',
            'action_text' => 'Open Updates',
            'action_link' => base_url('notifications'),
        ];
    }

    private function pickDailyString(array $values, int $candidateId, string $seedKey): string
    {
        $values = array_values(array_filter(array_map(static function ($value): string {
            return trim((string) $value);
        }, $values)));

        if (empty($values)) {
            return '';
        }

        $seed = date('Y-m-d') . '|' . $candidateId . '|' . $seedKey;
        $index = abs(crc32($seed)) % count($values);

        return (string) $values[$index];
    }

    private function findBestPracticeApplication(array $applications): ?array
    {
        foreach ($applications as $application) {
            $status = (string) ($application['status'] ?? '');
            if (!in_array($status, ['filtered_out', 'rejected', 'withdrawn', 'selected', 'hired'], true)) {
                return $application;
            }
        }

        return !empty($applications) ? $applications[0] : null;
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

        return array_values(array_unique($tokens));
    }
    
    /**
     * Calculate dashboard statistics
     */
    private function calculateStats($candidateId)
    {
        $applicationModel = model('ApplicationModel');
        $bookingModel = model('InterviewBookingModel');
        $notificationModel = model('NotificationModel');
        
        // Total applications
        $totalApplications = $applicationModel
            ->where('candidate_id', $candidateId)
            ->countAllResults();
        
        // Active applications (not rejected or withdrawn)
        $activeApplications = $applicationModel
            ->where('candidate_id', $candidateId)
            ->whereNotIn('status', ['filtered_out', 'rejected', 'withdrawn'])
            ->countAllResults();
        
        // Scheduled interviews are the real slot bookings in the current lifecycle. 
        // We filter for future datetimes to ensure the counter remains relevant to the candidate.
        $interviewsScheduled = $bookingModel
            ->where('user_id', $candidateId)
            ->where('slot_datetime >=', date('Y-m-d H:i:s'))
            ->whereIn('booking_status', ['booked', 'rescheduled'])
            ->countAllResults();
        
        // Unread notifications
        $unreadNotifications = $notificationModel
            ->where('user_id', $candidateId)
            ->where('is_read', 0)
            ->countAllResults();
        
        return [
            'total_applications' => $totalApplications,
            'active_applications' => $activeApplications,
            'interviews_scheduled' => $interviewsScheduled,
            'unread_notifications' => $unreadNotifications
        ];
    }

    /**
     * Calculate a simple profile strength score from the core profile fields.
     */
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
    
    /**
     * Get pending actions for candidate
     */
    private function getPendingActions($candidateId)
    {
        $actions = [];
        $applicationModel = model('ApplicationModel');
        $bookingModel = model('InterviewBookingModel');
        
        // Python interview flow starts from applied state as needed.
        $aiInterviewsPending = $applicationModel
            ->select('applications.*, jobs.title as job_title, jobs.ai_interview_policy')
            ->join('jobs', 'jobs.id = applications.job_id', 'left')
            ->where('applications.candidate_id', $candidateId)
            ->where('applications.status', 'applied')
            ->where('jobs.ai_interview_policy !=', JobModel::AI_POLICY_OFF)
            ->findAll();
        
        foreach ($aiInterviewsPending as $app) {
            $actions[] = [
                'title' => 'AI Interview Pending',
                'description' => 'Start your AI interview for ' . $app['job_title'],
                'link' => base_url('interview/start/' . $app['id']),
                'button_text' => 'Start Interview',
                'priority' => 'high'
            ];
        }
        
        // Check for HR interviews to book
        $hrInterviewsToBook = $applicationModel
            ->select('applications.*, jobs.title as job_title')
            ->join('jobs', 'jobs.id = applications.job_id', 'left')
            ->where('applications.candidate_id', $candidateId)
            ->where('applications.status', 'shortlisted')
            ->whereNotIn('applications.id', function($builder) {
                $builder->select('application_id')->from('interview_bookings');
            })
            ->findAll();
        
        foreach ($hrInterviewsToBook as $app) {
            $actions[] = [
                'title' => 'Book HR Interview',
                'description' => 'Schedule your HR interview for ' . $app['job_title'],
                'link' => base_url('candidate/book-slot/' . $app['id']),
                'button_text' => 'Book Now',
                'priority' => 'high'
            ];
        }
        
        // Check for upcoming interviews today
        $interviewsToday = $bookingModel
            ->select('interview_bookings.*, jobs.title as job_title, interview_slots.slot_time')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->join('interview_slots', 'interview_slots.id = interview_bookings.slot_id', 'left')
            ->where('interview_bookings.user_id', $candidateId)
            ->where('DATE(interview_bookings.slot_datetime)', date('Y-m-d'))
            ->whereIn('interview_bookings.booking_status', ['booked', 'rescheduled'])
            ->findAll();
        
        foreach ($interviewsToday as $interview) {
            $actions[] = [
                'title' => 'Interview Today',
                'description' => 'You have an interview for ' . $interview['job_title'] . ' at ' . date('h:i A', strtotime($interview['slot_time'])),
                'link' => base_url('candidate/my-bookings'),
                'button_text' => 'View Details',
                'priority' => 'urgent'
            ];
        }
        
        // Check for profile completion
        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);
        
        if (empty($user['resume_path']) || empty($user['phone']) || empty($user['bio']) || empty($user['email'])) {
            $actions[] = [
                'title' => 'Complete Your Profile',
                'description' => 'Add missing information to improve your chances',
                'link' => base_url('candidate/profile'),
                'button_text' => 'Update Profile',
                'priority' => 'medium'
            ];
        }
        
        return $actions;
    }
    
    /**
     * Get recent notifications
     */
    private function getRecentNotifications($candidateId)
    {
        $notificationModel = model('NotificationModel');
        
        return $notificationModel
            ->where('user_id', $candidateId)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();
    }

    /**
     * Get top job suggestions for dashboard (limited list).
     */
    private function getTopSuggestedJobs(int $candidateId, int $limit = 3): array
    {
        $jobModel = new JobModel();
        $suggestedJobs = $jobModel->getSuggestedJobsBasic($candidateId, $limit);

        if (empty($suggestedJobs)) {
            return [];
        }

        $companyIds = [];
        $companyNamesToLookup = [];
        foreach ($suggestedJobs as $job) {
            $companyId = (int) ($job['company_id'] ?? 0);
            if ($companyId > 0) {
                $companyIds[] = $companyId;
            } elseif (!empty($job['company'])) {
                $companyNamesToLookup[] = trim($job['company']);
            }
        }

        $companyModel = new CompanyModel();
        $companyInfoMap = [];

        if (!empty($companyIds)) {
            $companies = $companyModel->select('id, name, logo, website')
                ->whereIn('id', array_values(array_unique($companyIds)))
                ->findAll();
            foreach ($companies as $co) {
                $companyInfoMap['id_' . $co['id']] = $co;
                $companyInfoMap['name_' . strtolower(trim($co['name']))] = $co;
            }
        }

        if (!empty($companyNamesToLookup)) {
            $companiesByName = $companyModel->select('id, name, logo, website')
                ->whereIn('name', array_values(array_unique($companyNamesToLookup)))
                ->findAll();
            foreach ($companiesByName as $co) {
                $companyInfoMap['name_' . strtolower(trim($co['name']))] = $co;
            }
        }

        foreach ($suggestedJobs as $idx => $job) {
            // Handle Client Company Visibility
            if (($job['posted_for'] ?? '') === 'client') {
                if (($job['client_disclosure'] ?? '') === 'visible' && !empty($job['client_company_name'])) {
                    $suggestedJobs[$idx]['company'] = $job['client_company_name'];
                } else {
                    $suggestedJobs[$idx]['company'] = ($job['company'] ?? 'Recruiter') . ' (Hiring for a Client)';
                }
            }

            // Handle Candidate Fee Disclaimer
            if (isset($job['candidate_fee_allowed']) && (int)$job['candidate_fee_allowed'] === 0) {
                $suggestedJobs[$idx]['fee_disclaimer'] = 'Candidate fees are never allowed on this portal.';
            }

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

            $suggestedJobs[$idx]['company_logo'] = $logo;
            $suggestedJobs[$idx]['company_website'] = $website;
        }

        return $suggestedJobs;
    }
    
    /**
     * Determine next action for an application
     */
    private function getNextAction($application)
    {
        switch ($application['status']) {
            case 'applied':
                $policy = strtoupper((string) ($application['ai_interview_policy'] ?? 'REQUIRED_HARD'));
                return $policy === JobModel::AI_POLICY_OFF
                    ? 'Your application is under recruiter review.'
                    : 'Start your AI interview to move forward.';

            case 'ai_interview_completed':
                return 'Your AI interview is complete. Recruiter review is now in progress.';

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

    /**
     * View all applications
     */
    public function applications()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $candidateId = session()->get('user_id');
        
        if (!$candidateId) {
            return redirect()->to('/login')->with('error', 'Please login to continue');
        }
        
        $applications = $this->getApplicationsWithDetails($candidateId, true);
        $applications = $this->maskApplicationsList($applications);
        $calculatedExperience = model('UserModel')->calculateExperienceLevel((int) $candidateId);

        return view('candidate/applications', [
            'applications' => $applications,
            'calculatedExperience' => $calculatedExperience,
        ]);
    }

    public function jobSearchStrategy()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }

        $candidateId = (int) session()->get('user_id');
        if ($candidateId <= 0) {
            return redirect()->to('/login')->with('error', 'Please login to continue');
        }

        $applications = $this->getApplicationsWithDetails($candidateId, true);
        $topSuggestedJobs = $this->getTopSuggestedJobs($candidateId, 6);
        $jobSearchStrategy = $this->buildJobSearchStrategyCoach($candidateId, $applications, $topSuggestedJobs);
        $applications = $this->maskApplicationsList($applications);

        return view('candidate/job_search_strategy', [
            'applications' => $applications,
            'topSuggestedJobs' => $topSuggestedJobs,
            'jobSearchStrategy' => $jobSearchStrategy,
        ]);
    }

    public function mockInterview(int $applicationId)
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }

        $candidateId = (int) session()->get('user_id');
        if ($candidateId <= 0) {
            return redirect()->to('/login')->with('error', 'Please login to continue');
        }

        $application = $this->getApplicationDetail($candidateId, $applicationId);
        if (empty($application)) {
            return redirect()->to(base_url('candidate/applications'))->with('error', 'Application not found.');
        }

        if (in_array((string) ($application['status'] ?? ''), ['filtered_out', 'rejected', 'withdrawn', 'selected', 'hired'], true)) {
            return redirect()->to(base_url('candidate/applications'))->with('error', 'Detailed mock interview is available only for active applications.');
        }

        return view('candidate/mock_interview', [
            'application' => $application,
            'mockInterview' => $this->buildDetailedMockInterview($application),
            'calculatedExperience' => model('UserModel')->calculateExperienceLevel($candidateId),
        ]);
    }

    private function getApplicationDetail(int $candidateId, int $applicationId): ?array
    {
        $applications = $this->getApplicationsWithDetails($candidateId);
        foreach ($applications as $application) {
            if ((int) ($application['id'] ?? 0) === $applicationId) {
                return $application;
            }
        }

        return null;
    }

    private function buildDetailedMockInterview(array $application): array
    {
        $summaryPrep = $application['interview_prep'] ?? $this->buildInterviewPrepCoach($application, false);
        $policy = strtoupper((string) ($application['ai_interview_policy'] ?? JobModel::AI_POLICY_REQUIRED_HARD));
        $jobTitle = trim((string) ($application['job_title'] ?? 'this role'));
        $experienceLevel = trim((string) ($application['experience_level'] ?? ''));
        $focusSkills = array_values(array_slice((array) ($summaryPrep['focus_skills'] ?? []), 0, 6));

        $rounds = [
            [
                'name' => $policy === JobModel::AI_POLICY_OFF ? 'Recruiter Screen Round' : 'AI Screening Round',
                'objective' => 'Give crisp, structured answers that prove baseline fit for ' . $jobTitle . '.',
                'questions' => array_map(static function (string $skill): array {
                    return [
                        'question' => 'Give a short, clear example of where you used ' . $skill . '.',
                        'why_it_matters' => 'This checks whether your resume claims are backed by real work.',
                        'answer_tip' => 'Answer in 60 to 90 seconds with context, action, and result.',
                    ];
                }, array_slice($focusSkills, 0, 3)),
            ],
            [
                'name' => 'Technical Depth Round',
                'objective' => 'Show how you make technical decisions and solve production-level problems.',
                'questions' => [
                    [
                        'question' => 'Walk through a recent project that best matches this role.',
                        'why_it_matters' => 'Interviewers want evidence of relevant execution, not only tool familiarity.',
                        'answer_tip' => 'Cover the problem, architecture, tradeoffs, and the measurable outcome.',
                    ],
                    [
                        'question' => 'What technical challenge did you face recently, and how did you resolve it?',
                        'why_it_matters' => 'This reveals debugging discipline and ownership.',
                        'answer_tip' => 'Focus on the issue, your reasoning path, the fix, and what changed after.',
                    ],
                    [
                        'question' => 'How do you keep code quality and maintainability strong under deadlines?',
                        'why_it_matters' => 'This tests maturity, not just coding speed.',
                        'answer_tip' => 'Mention review habits, testing, refactoring decisions, and risk management.',
                    ],
                ],
            ],
            [
                'name' => 'Behavioral and Role Fit Round',
                'objective' => 'Connect your working style and career direction to the team and role.',
                'questions' => [
                    [
                        'question' => 'Why are you interested in this ' . $jobTitle . ' role right now?',
                        'why_it_matters' => 'Recruiters want a coherent reason, not a generic answer.',
                        'answer_tip' => 'Tie the role to your proven strengths, growth direction, and the company context.',
                    ],
                    [
                        'question' => 'Tell me about a time you handled conflicting priorities or stakeholder pressure.',
                        'why_it_matters' => 'This tests communication and judgment under real work conditions.',
                        'answer_tip' => 'Use STAR and show how you aligned people, not just tasks.',
                    ],
                ],
            ],
        ];

        if ($policy === JobModel::AI_POLICY_OFF) {
            $rounds[0]['questions'][] = [
                'question' => 'How would your previous manager describe your working style?',
                'why_it_matters' => 'This gives an early signal of communication and team fit.',
                'answer_tip' => 'Stay specific and support the claim with one short example.',
            ];
        }

        if ($experienceLevel !== '') {
            $rounds[2]['questions'][] = [
                'question' => 'How does your experience level of ' . $experienceLevel . ' translate into value for this role?',
                'why_it_matters' => 'Interviewers will test whether your years map to real ownership.',
                'answer_tip' => 'Link your experience to depth, scope, and outcomes rather than just time served.',
            ];
        }

        $fallback = [
            'title' => $jobTitle . ' Mock Interview',
            'intro' => 'Use this detailed interview rehearsal to practice the exact stories, technical decisions, and role-fit points most likely to come up next.',
            'focus_skills' => $focusSkills,
            'rounds' => $rounds,
            'answer_framework' => [
                'Start with the context in one or two lines before describing your action.',
                'Quantify outcomes where possible instead of describing work in vague terms.',
                'Keep each answer tightly aligned to the job requirements and your submitted resume version.',
                'When discussing tradeoffs, explain why you chose a path, not just what you built.',
            ],
            'evaluation_points' => [
                'Clear evidence that your strongest projects match the job scope.',
                'Confident explanation of the top required skills and how you used them.',
                'Strong communication structure without rambling.',
                'Consistent story between resume, application, and spoken examples.',
            ],
            'final_checklist' => [
                'Rehearse your strongest project walkthrough until it is concise and outcome-focused.',
                'Prepare one challenge story, one collaboration story, and one ownership story.',
                'Review the required skills and keep one concrete example ready for each major skill.',
                'Keep your submitted resume version open while practicing so your answers stay consistent.',
                $policy === JobModel::AI_POLICY_OFF
                    ? 'Prepare a recruiter-facing introduction that explains why this role is the right next step.'
                    : 'Practice delivering short, camera-friendly answers for the AI or recorded screening round.',
            ],
            'source' => 'fallback',
        ];

        return (new AiInterviewPrepCoach())->generateMockInterview($application, $fallback);
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

        $targetRoles = $this->deriveTargetRoles($user, $topSuggestedJobs, $topCategories);
        $jobDecisions = $this->buildStrategyJobDecisions($topSuggestedJobs, $skills, $targetRoles, (string) ($user['preferred_job_titles'] ?? ''));
        $recommendedJobIds = array_values(array_filter(array_map(static function (array $job): int {
            return (int) ($job['id'] ?? 0);
        }, $jobDecisions['worth_applying'])));

        $fallback = [
            'title' => 'Practical Job Search Strategy',
            'summary' => 'Use only role-relevant matches first, fix the profile gaps that block conversion, and avoid low-fit jobs that dilute your applications.',
            'target_roles' => $targetRoles,
            'priority_actions' => $this->buildPriorityActions($user, $skills, $topSuggestedJobs),
            'profile_fixes' => $this->buildProfileFixes($user, $skills),
            'application_strategy' => $this->buildApplicationStrategy($topSuggestedJobs, $activeApplications, $shortlistedCount),
            'weekly_plan' => $this->buildWeeklyPlan($topSuggestedJobs),
            'watchouts' => $this->buildWatchouts($activeApplications, $skills, $topSuggestedJobs),
            'recommended_job_ids' => $recommendedJobIds,
            'source' => 'fallback',
        ];

        $strategy = (new AiJobSearchStrategyCoach())->generate($candidateId, $context, $fallback);
        $strategy['jobs_worth_applying'] = $jobDecisions['worth_applying'];
        $strategy['jobs_to_avoid'] = $jobDecisions['avoid'];
        $strategy['profile_blockers'] = $this->buildProfileBlockers($strategy['profile_fixes'] ?? [], $jobDecisions['common_missing_skills']);
        $strategy['recommended_job_ids'] = $recommendedJobIds;

        return $strategy;
    }

    private function buildStrategyJobDecisions(array $jobs, array $candidateSkills, array $targetRoles, string $preferredTitles): array
    {
        $roleKeywords = $this->buildTargetRoleKeywords($targetRoles, $preferredTitles);
        $candidateSkillSet = array_fill_keys(array_map('strtolower', $candidateSkills), true);
        $enriched = [];
        $missingFrequency = [];

        foreach ($jobs as $job) {
            $requiredSkills = $this->tokenizeCsv((string) ($job['required_skills'] ?? ''));
            $matchedSkills = array_values(array_filter($requiredSkills, static fn (string $skill): bool => isset($candidateSkillSet[$skill])));
            $missingSkills = array_values(array_filter($requiredSkills, static fn (string $skill): bool => !isset($candidateSkillSet[$skill])));
            foreach (array_slice($missingSkills, 0, 8) as $skill) {
                $missingFrequency[$skill] = ($missingFrequency[$skill] ?? 0) + 1;
            }

            $roleScore = $this->scoreJobTitleRelevance((string) ($job['title'] ?? ''), $roleKeywords);
            $matchScore = (float) ($job['match_score'] ?? 0);
            $job['role_relevance_score'] = $roleScore;
            $job['matched_skills'] = $matchedSkills;
            $job['missing_skills'] = array_slice($missingSkills, 0, 6);
            $job['why_this_role'] = $this->buildJobWhyThisRole($job, $matchedSkills, $roleScore, $matchScore);
            $job['fix_before_applying'] = $this->buildJobFixBeforeApplying($job['missing_skills'], $job);
            $job['avoid_reason'] = $this->buildJobAvoidReason($job, $roleScore, $matchScore);
            $job['apply_later_if'] = $this->buildApplyLaterCondition($job['missing_skills'], $roleScore, $matchScore);
            $enriched[] = $job;
        }

        usort($enriched, static function (array $a, array $b): int {
            $scoreA = ((int) ($a['role_relevance_score'] ?? 0) * 100) + (float) ($a['match_score'] ?? 0);
            $scoreB = ((int) ($b['role_relevance_score'] ?? 0) * 100) + (float) ($b['match_score'] ?? 0);
            return $scoreB <=> $scoreA;
        });

        $worth = array_values(array_filter($enriched, static function (array $job): bool {
            return (int) ($job['role_relevance_score'] ?? 0) > 0 || (float) ($job['match_score'] ?? 0) >= 72;
        }));
        $avoid = array_values(array_filter($enriched, static function (array $job): bool {
            return (int) ($job['role_relevance_score'] ?? 0) === 0 && (float) ($job['match_score'] ?? 0) < 72;
        }));

        if (empty($worth)) {
            $worth = array_slice($enriched, 0, 3);
            $avoid = array_slice($enriched, 3, 3);
        }

        arsort($missingFrequency);

        return [
            'worth_applying' => array_slice($worth, 0, 4),
            'avoid' => array_slice($avoid, 0, 4),
            'common_missing_skills' => array_slice(array_keys($missingFrequency), 0, 6),
        ];
    }

    private function buildTargetRoleKeywords(array $targetRoles, string $preferredTitles): array
    {
        $roles = array_merge($targetRoles, preg_split('/[,|\\/]+/', $preferredTitles) ?: []);
        $specificStopwords = ['developer', 'engineer', 'senior', 'junior', 'lead', 'software', 'role', 'roles'];
        $phrases = [];
        $tokens = [];

        foreach ($roles as $role) {
            $normalized = trim(strtolower(str_replace(['-', '_'], ' ', (string) $role)));
            if ($normalized === '') {
                continue;
            }
            $phrases[] = $normalized;
            foreach (preg_split('/\s+/', $normalized) ?: [] as $token) {
                $token = trim($token);
                if ($token !== '' && !in_array($token, $specificStopwords, true) && strlen($token) > 2) {
                    $tokens[] = $token;
                }
            }
        }

        return [
            'phrases' => array_values(array_unique($phrases)),
            'tokens' => array_values(array_unique($tokens)),
        ];
    }

    private function scoreJobTitleRelevance(string $title, array $roleKeywords): int
    {
        $normalizedTitle = trim(strtolower(str_replace(['-', '_'], ' ', $title)));
        $score = 0;
        foreach ((array) ($roleKeywords['phrases'] ?? []) as $phrase) {
            if ($phrase !== '' && str_contains($normalizedTitle, $phrase)) {
                $score += 3;
            }
        }
        foreach ((array) ($roleKeywords['tokens'] ?? []) as $token) {
            if ($token !== '' && preg_match('/\b' . preg_quote($token, '/') . '\b/', $normalizedTitle)) {
                $score++;
            }
        }

        return $score;
    }

    private function buildJobWhyThisRole(array $job, array $matchedSkills, int $roleScore, float $matchScore): string
    {
        if ($roleScore > 0 && !empty($matchedSkills)) {
            return 'Role title aligns with your target search and matches ' . implode(', ', array_slice($matchedSkills, 0, 3)) . '.';
        }
        if ($roleScore > 0) {
            return 'Role title aligns with your target search; verify the skill requirements before applying.';
        }
        if (!empty($matchedSkills) && $matchScore >= 72) {
            return 'Skill overlap is strong through ' . implode(', ', array_slice($matchedSkills, 0, 3)) . ', but title fit needs review.';
        }

        return (string) ($job['match_reason'] ?? 'Use this only if the responsibilities match your target direction.');
    }

    private function buildJobFixBeforeApplying(array $missingSkills, array $job): string
    {
        if (!empty($missingSkills)) {
            return 'Add evidence or a small project for ' . implode(', ', array_slice($missingSkills, 0, 3)) . ' before applying.';
        }

        return 'Tailor your resume headline and top project to mirror this role before applying.';
    }

    private function buildJobAvoidReason(array $job, int $roleScore, float $matchScore): string
    {
        if ($roleScore === 0) {
            return 'Title does not clearly match your selected target roles.';
        }
        if ($matchScore < 60) {
            return 'Match score is low compared with stronger suggested jobs.';
        }

        return 'Apply later only after stronger-fit roles are handled.';
    }

    private function buildApplyLaterCondition(array $missingSkills, int $roleScore, float $matchScore): string
    {
        if ($roleScore === 0) {
            return 'Apply only if you intentionally expand your target role toward this title.';
        }
        if (!empty($missingSkills)) {
            return 'Reconsider after adding proof for ' . implode(', ', array_slice($missingSkills, 0, 3)) . '.';
        }
        if ($matchScore < 60) {
            return 'Reconsider when your match score improves above 70%.';
        }

        return 'Reconsider after your stronger-fit jobs are already applied to.';
    }

    private function buildProfileBlockers(array $profileFixes, array $commonMissingSkills): array
    {
        $blockers = array_values(array_filter(array_map('trim', (array) $profileFixes)));
        foreach ($commonMissingSkills as $skill) {
            $blockers[] = 'Repeated missing skill in suggested jobs: ' . $skill . '. Add proof if it is part of your target role.';
        }

        return array_values(array_slice(array_unique($blockers), 0, 7));
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

        if (empty($roles)) {
            foreach ($topSuggestedJobs as $job) {
                $title = trim((string) ($job['title'] ?? ''));
                if ($title !== '') {
                    $roles[] = $title;
                }
            }
        }

        if (empty($roles)) {
            foreach ($topCategories as $category) {
                if ($category !== '') {
                    $roles[] = $category . ' roles';
                }
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

        $rows = array_values(array_filter($rows, static function (array $row): bool {
            return trim((string) ($row['name'] ?? '')) !== '';
        }));

        $namesMissingInfo = [];
        foreach ($rows as $row) {
            if (empty($row['logo']) && empty($row['website'])) {
                $namesMissingInfo[] = $row['name'];
            }
        }

        if (!empty($namesMissingInfo)) {
            $companyModel = new CompanyModel();
            $fallbacks = $companyModel->select('name, logo, website')
                ->whereIn('name', array_values(array_unique($namesMissingInfo)))
                ->findAll();
            
            $fallbackMap = [];
            foreach ($fallbacks as $fb) {
                $fallbackMap[strtolower(trim($fb['name']))] = $fb;
            }

            foreach ($rows as &$row) {
                $key = strtolower(trim($row['name']));
                if (empty($row['logo']) && empty($row['website']) && isset($fallbackMap[$key])) {
                    $row['logo'] = $fallbackMap[$key]['logo'] ?? '';
                    $row['website'] = $fallbackMap[$key]['website'] ?? '';
                }
            }
        }

        foreach ($rows as &$row) {
            if (empty($row['website']) && !empty($row['name'])) {
                $row['website'] = $this->guessCompanyDomain($row['name']);
            }
        }

        return $rows;
    }

    private function guessCompanyDomain(string $name): string
    {
        $clean = strtolower(trim($name));
        $clean = preg_replace('/\b(limited|ltd|inc|llc|llp|plc|corp|corporation|company|co|solutions|services|technologies|technology)\b/i', '', $clean);
        $clean = preg_replace('/[^a-z0-9]/', '', $clean);
        if ($clean === '') return '';
        return 'https://' . $clean . '.com';
    }

    /**
     * Real-time ATS Scorer: Compares a candidate's resume version against a specific job.
     */
    public function analyzeAtsMatch()
    {
        try {
            $candidateId = (int) session()->get('user_id');
            $jobId = (int) $this->request->getGet('job_id');
            $resumeVersionId = (int) $this->request->getGet('resume_id');

            if ($candidateId <= 0 || $jobId <= 0) {
                return $this->response->setJSON(['error' => 'Invalid parameters (Missing user or job ID)'])->setStatusCode(400);
            }

            $jobModel = new JobModel();
            $job = $jobModel->find($jobId);

            if (!$job) {
                return $this->response->setJSON(['error' => 'Job description not found.'])->setStatusCode(404);
            }
            $analysis = (new AtsScoreService())->analyzeCandidateJob($candidateId, $job, $resumeVersionId);
            if (!($analysis['has_resume_source'] ?? false)) {
                return $this->response->setJSON(['error' => 'No resume content found. Please upload a resume first.'])->setStatusCode(404);
            }

            $result = [
                'success' => true,
                'score' => (int) ($analysis['score'] ?? 0),
                'keywords' => (array) ($analysis['missing_keywords'] ?? []),
                'suggestions' => (array) ($analysis['suggestions'] ?? []),
                'gap' => (string) ($analysis['critical_gap'] ?? 'No major gaps found.'),
            ];

            return $this->response->setJSON($result);

        } catch (\Throwable $e) {
            log_message('error', '[ATS Analysis Exception] ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false, 
                'error' => 'Server Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            ])->setStatusCode(500);
        }
    }

    /**
     * AI Cover Letter Generator
     * Generates a tailored cover letter based on Job Description and Candidate Profile
     */
    public function generateAiCoverLetter()
    {
        try {
            $candidateId = (int) session()->get('user_id');
            $jobId = (int) $this->request->getGet('job_id');

            if ($candidateId <= 0 || $jobId <= 0) {
                return $this->response->setJSON(['error' => 'Missing Job or User ID'])->setStatusCode(400);
            }

            $jobModel = new JobModel();
            $job = $jobModel->find($jobId);
            
            $userModel = new UserModel();
            $profile = $userModel->findCandidateWithProfile($candidateId);

            if (!$job || !$profile) {
                return $this->response->setJSON(['error' => 'Data not found'])->setStatusCode(404);
            }

            $apiKey = getenv('OPENAI_API_KEY');
            if (!$apiKey) {
                return $this->response->setJSON(['error' => 'AI Service unavailable'])->setStatusCode(500);
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
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7
                ]),
                CURLOPT_TIMEOUT => 60,
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            $content = $data['choices'][0]['message']['content'] ?? '';

            return $this->response->setJSON([
                'success' => true,
                'cover_letter' => trim($content),
                'job_title' => $job['title'],
                'company' => $job['company']
            ]);

        } catch (\Throwable $e) {
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(500);
        }
    }

    /**
     * View saved or recently generated cover letters
     */
    /*public function coverLetters()
    {
        $candidateId = (int) session()->get('user_id');
        $applicationModel = model('ApplicationModel');
        
        // Fetch recent applications to populate the 'History' tab in the view
        $recentApplications = $applicationModel
            ->select('applications.*, jobs.title as job_title, jobs.company')
            ->join('jobs', 'jobs.id = applications.job_id', 'left')
            ->where('applications.candidate_id', $candidateId)
            ->orderBy('applications.applied_at', 'DESC')
            ->limit(10)
            ->findAll();

        $data = [
            'title' => 'My Cover Letters',
            'recent_suggestions' => session()->get('career_suggestions') ?? [],
            'stats' => $this->calculateStats($candidateId),
            'profileStrength' => $this->calculateProfileStrength($candidateId),
            'recentApplications' => $recentApplications,
        ];

        return view('candidate/cover_letters', $data);
    }*/
    
     /**
     * Displays a full blog post, restricted to logged-in candidates.
     */
    public function blogDetail(int $id)
    {
        // Ensure user is a candidate and logged in
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('login'))->with('error', 'Please login to read the full article.');
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('blog_posts')) {
            return redirect()->back()->with('error', 'Blog feature is currently unavailable.');
        }

        $blogModel = model('BlogModel');
        $post = $blogModel->where('status', 'published')->find($id);

        if (!$post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Blog post not found.');
        }

        $relatedPosts = $blogModel->where('status', 'published')
                                  ->where('id !=', $id)
                                  ->orderBy('created_at', 'DESC')
                                  ->limit(3)
                                  ->findAll();

        return view('candidate/blog_detail', [
            'post' => $post,
            'title' => $post['title'] ?? 'Blog',
            'relatedPosts' => $relatedPosts
        ]);
    }

    /**
     * Fetches top job categories with job counts for dashboard shortcuts.
     */
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

    /**
     * Masks the 'filtered_out' status in a list of applications.
     */
    private function maskApplicationsList(array $applications): array
    {
        foreach ($applications as &$app) {
            if (($app['status'] ?? '') === 'filtered_out') {
                $app['status'] = 'applied';
            }
        }
        unset($app);
        return $applications;
    }
    
     /**
     * Combines Company Directory and MNC Job Discovery into a single UI page.
     */
    public function companyJobDiscovery()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }

        $candidateId = session()->get('user_id');
        if (!$candidateId) {
            return redirect()->to('/login')->with('error', 'Please login to continue');
        }

        $companyModel = new CompanyModel();
        $request = service('request');

        // --- Company Directory Logic (adapted from CompanyController) ---
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

        $viewAll = $this->request->getGet('view_all') === '1';
        $companiesPerPage = $viewAll ? 500 : 16;
        $companies = $companiesBuilder->paginate($companiesPerPage);

        foreach ($companies as &$company) {
            $company['open_jobs_count'] = (int) ($company['open_jobs_count'] ?? 0);
            $company['profile_url'] = base_url('company/' . $company['id']);
            $company['jobs_url'] = base_url('candidate/company-jobs/' . rawurlencode((string) ($company['name'] ?? '')));
            $company['discovery_tags'] = $this->buildCompanyDiscoveryTags($company, $hasCompanyType, $hasCompanyTags, $hasVerified, $hasProfileStatus);
        }
        unset($company);

        $hasSearchQuery = !empty($filters['q']);
        $foundRegisteredCompanies = !empty($companies);
        $shouldAutoTriggerAiSearch = false;
        $industries = $companyModel->select('industry')->distinct()->where('industry IS NOT NULL')->where('industry !=', '')->orderBy('industry', 'ASC')->findAll();
        $industries = array_column($industries, 'industry');
        $segmentCards = $this->companyDiscoverySegmentCards($segments, $hasCompanyType, $hasCompanyTags);
        $allCompanyCount = (int) $db->table('companies')->countAllResults();

        return view('candidate/combined_job_company_discovery', [
            'companies' => $companies,
            'filters' => $filters,
            'industries' => $industries,
            'segments' => $segmentCards,
            'allCompanyCount' => $allCompanyCount,
            'activeSegmentKey' => $filters['segment'],
            'pager' => $companyModel->pager,
            'hasSearchQuery' => $hasSearchQuery,
            'foundRegisteredCompanies' => $foundRegisteredCompanies,
            'shouldAutoTriggerAiSearch' => $shouldAutoTriggerAiSearch,
            'viewAll' => $viewAll,
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
                
