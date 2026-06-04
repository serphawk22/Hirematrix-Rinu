                        <?= view('Layouts/candidate_header', ['title' => 'Dashboard']) ?>


<?php
$applicationCount = count($applications ?? []);
$recentApps = array_slice($applications ?? [], 0, 5);
$topSuggestedJobs = $topSuggestedJobs ?? [];
$avgScore = (int) round((float) ($stats['average_ai_score'] ?? 0));
$profileStrength = (int) ($profileStrength ?? 0);
$activeMatches = count($topSuggestedJobs);
$candidateId = (int) (session()->get('user_id') ?? 0);
$activeSuggestions = session()->get('career_suggestions') ?? [];
$activeSuggestions = array_filter($activeSuggestions, static function ($suggestion): bool {
    return isset($suggestion['expires_at']) && time() < (int) $suggestion['expires_at'];
});
$activeSuggestionsCount = count($activeSuggestions);
$topRecommendedCount = count($topSuggestedJobs);
$savedJobsCount = $candidateId > 0
    ? (int) model('SavedJobModel')->where('candidate_id', $candidateId)->countAllResults()
    : 0;
$jobAlertsCount = $candidateId > 0
    ? (int) model('JobAlertModel')->where('candidate_id', $candidateId)->where('is_active', 1)->countAllResults()
    : 0;
$unreadNotificationCount = $candidateId > 0
    ? (int) model('NotificationModel')->getUnreadCount($candidateId)
    : 0;
$profilePrompt = $profileStrength >= 80
    ? 'Recruiter-ready profile. Keep momentum with fresh applications.'
    : ($profileStrength >= 50
        ? 'Complete a few more profile details to get sharper matches.'
        : 'Complete your profile to unlock stronger matches and recruiter visibility.');
$nextActionUrl = $topRecommendedCount > 0 ? base_url('jobs?tab=suggested') : base_url('jobs');
$nextActionCta = $topRecommendedCount > 0 ? 'View matches' : 'Browse jobs';
$formatCompactCount = static function (int $count): string {
    return $count > 99 ? '99+' : (string) $count;
};
$dashboardStrategy = is_array($jobSearchStrategy ?? null) ? $jobSearchStrategy : [];
$dashboardStrategySource = (string) ($dashboardStrategy['source'] ?? 'fallback');
$dashboardStrategyHeading = $dashboardStrategySource === 'ai' ? 'AI-generated strategy' : 'Job Search Strategy Coach';
$dashboardStrategyBadge = $dashboardStrategySource === 'ai' ? 'AI-generated' : 'Strategy preview';
$dashboardStrategyRoles = array_values(array_filter(array_map('trim', (array) ($dashboardStrategy['target_roles'] ?? []))));
$dailyReminder = is_array($dailyReminder ?? null) ? $dailyReminder : [];
if (empty($dashboardStrategyRoles)) {
    $dashboardStrategyRoles = array_slice(array_values(array_filter(array_map(static function (array $job): string {
        return trim((string) ($job['title'] ?? ''));
    }, $topSuggestedJobs))), 0, 3);
}
if (empty($dashboardStrategyRoles)) {
    $dashboardStrategyRoles = ['Web Developer', 'Software Developer', 'Frontend Developer'];
}

$pickJobIcon = static function (string $title): string {
    $needle = strtolower($title);
    if (str_contains($needle, 'data')) {
        return 'fas fa-database';
    }
    if (str_contains($needle, 'design')) {
        return 'fas fa-pencil-ruler';
    }
    if (str_contains($needle, 'manager') || str_contains($needle, 'product')) {
        return 'fas fa-chart-line';
    }
    if (str_contains($needle, 'engineer') || str_contains($needle, 'developer') || str_contains($needle, 'backend')) {
        return 'fas fa-code';
    }

    return 'fas fa-briefcase';
};

$formatDate = static function ($value, string $fallback = 'Recently'): string {
    if (empty($value)) {
        return $fallback;
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? date('M d, Y', $timestamp) : $fallback;
};

$resolveAssetUrl = static function (string $path): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
        return $path;
    }
    return base_url(ltrim($path, '/'));
};
?>
<div class="dashboard-jobboard">
<div class="dash-grid">

    <!-- CENTER: main content -->
    <div class="dash-grid__main">
    <section class="dashboard-section pt-0">
        <div class="container-fluid px-lg-5">
            <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="section-title">Jobs Matching Your Profile</h2>
                    <p class="section-subtitle">Based on your skills, target roles, and work preferences</p>
                </div>
                <a href="<?= base_url('jobs?tab=suggested') ?>" class="btn btn-ghost text-primary">View all jobs <i class="fas fa-arrow-right ms-2"></i></a>
            </div>

            <div class="dashboard-jobs-grid">
                <?php if (!empty($topSuggestedJobs)): ?>
                    <?php foreach (array_slice($topSuggestedJobs, 0, 6) as $job): ?>
                        <?php
                        $score = (int) round((float) ($job['match_score'] ?? 0));
                        $title = (string) ($job['title'] ?? 'Untitled Role');
                        $company = (string) ($job['company'] ?? 'Company');
                        $location = (string) ($job['location'] ?? 'N/A');
                        $experience = trim((string) ($job['experience_level'] ?? ''));
                        $salary = trim((string) ($job['salary_range'] ?? ''));
                        $postedAt = isset($job['posted_at']) ? $formatDate($job['posted_at']) : 'Recently';
                        $companyInitial = strtoupper(substr($company, 0, 1) ?: 'C');
                        $companyLogo = trim((string) ($job['company_logo'] ?? ''));
                        $website = trim((string) ($job['company_website'] ?? ''));
                        $websiteHost = $website !== '' ? (parse_url($website, PHP_URL_HOST) ?: $website) : '';
                        $websiteHost = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
                        $googleLogoUrl = $websiteHost !== '' ? 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96' : '';
                        $companyLogoResolved = $companyLogo !== '' ? $resolveAssetUrl($companyLogo) : $googleLogoUrl;

                        $fallbackHtml = '<span>' . esc($companyInitial) . '</span>';
                        $logoErrorJs = "if(this.dataset.googleLogo&&this.src!==this.dataset.googleLogo){this.src=this.dataset.googleLogo;}else{this.parentNode.innerHTML='" . $fallbackHtml . "';}";

                        $matchPct = max(10, min(100, $score));
                        $matchLabel = $score > 0 ? $matchPct . '% match' : 'Open role';
                        $isExternalJob = (int) ($job['is_external'] ?? 0) === 1;
                        $externalSource = trim((string) ($job['external_source'] ?? ''));
                        ?>
                        <div class="job-card dashboard-card">
                            <div class="job-card-icon">
                                <?php if ($companyLogoResolved !== ''): ?>
                                    <img src="<?= esc($companyLogoResolved) ?>" alt="<?= esc($company) ?>" data-google-logo="<?= esc($googleLogoUrl) ?>" onerror="<?= esc($logoErrorJs, 'attr') ?>">
                                <?php else: ?>
                                    <span><?= esc($companyInitial) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="job-card-title"><?= esc($title) ?></h3>
                            <p class="job-card-company"><?= esc($company) ?></p>
                            <div class="job-card-meta">
                                <span><i class="fas fa-map-pin"></i> <?= esc($location) ?></span>
                                <?php if ($experience !== ''): ?>
                                    <span><i class="fas fa-briefcase"></i> <?= esc($experience) ?></span>
                                <?php endif; ?>
                                <?php if ($salary !== ''): ?>
                                    <span><i class="fas fa-rupee-sign"></i> <?= esc($salary) ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-clock"></i> <?= esc($postedAt) ?></span>
                            </div>
                            <div class="job-card-tags">
                                <span class="badge badge-primary"><?= esc($job['employment_type'] ?: 'Full-time') ?></span>
                                <span class="badge badge-secondary"><?= esc(substr($title, 0, 15) ?: 'Role') ?></span>
                            </div>
                            <a href="<?= base_url('job/' . (int) $job['id']) ?>" class="view-details">View Details &rarr;</a>
                        </div>
                        <?php endforeach; ?>
                <?php else: ?>
                    <div class="dashboard-panel" style="grid-column:1/-1">
                        <div class="panel-body text-center py-5">
                            <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                            <h4 class="mb-2">No recommended jobs yet</h4>
                            <p class="text-muted mb-0">Once your profile matches live openings, they will appear here automatically.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="dashboard-section pt-0">
        <div class="container-fluid px-lg-5">
            <div class="dashboard-strategy-banner-inner">
                <div class="dashboard-strategy-copy">
                    <h2 class="dashboard-strategy-title"><?= esc((string) ($dashboardStrategy['title'] ?? 'Job Search Strategy Coach')) ?></h2>
                    <p class="dashboard-strategy-text">
                        <?= esc((string) ($dashboardStrategy['summary'] ?? 'Use a focused plan to refine your resume, prioritize applications, and target roles that align with your strongest skills.')) ?>
                    </p>
                    <ul class="dashboard-strategy-list">
                        <?php foreach (array_slice((array) ($dashboardStrategy['priority_actions'] ?? []), 0, 3) as $item): ?>
                            <li><?= esc($item) ?></li>
                        <?php endforeach; ?>
                        <?php if (empty($dashboardStrategy['priority_actions'])): ?>
                            <li>Refine your resume around the skills that matter most.</li>
                            <li>Focus on applications with the highest match potential.</li>
                            <li>Set weekly priorities instead of applying broadly.</li>
                        <?php endif; ?>
                    </ul>
                    <a href="<?= base_url('candidate/job-search-strategy') ?>" class="btn btn-primary dashboard-strategy-btn">
                        Open Full Strategy <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
                <div class="dashboard-strategy-panel">
                    <span class="dashboard-strategy-badge"><?= esc($dashboardStrategyBadge) ?></span>
                    <div class="dashboard-strategy-panel-label">Target Roles</div>
                    <div class="dashboard-strategy-role-list">
                        <?php foreach ($dashboardStrategyRoles as $role): ?>
                            <span class="dashboard-strategy-role-pill"><?= esc($role) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-section pt-0">
        <div class="container-fluid px-lg-5">
            <?php if (empty($premiumSubscription ?? null)): ?>
            <div class="dashboard-cta-banner">
                <div class="dashboard-cta-banner-inner">
                    <div class="dashboard-cta-copy">
                        <h2 class="dashboard-cta-title">Unlock all AI career tools</h2>
                        <div class="pro-ad-services">
                            <div class="pro-ad-service">
                                <div class="pro-ad-service-title"><i class="fas fa-route"></i> Career Transition AI</div>
                                <ul class="pro-ad-features">
                                    <li>Personalized roadmap</li>
                                    <li>Skill gap analysis</li>
                                </ul>
                            </div>
                            <div class="pro-ad-service">
                                <div class="pro-ad-service-title"><i class="fas fa-file-alt"></i> Resume Studio</div>
                                <ul class="pro-ad-features">
                                    <li>ATS-friendly resumes</li>
                                    <li>Job-specific versions</li>
                                </ul>
                            </div>
                            <div class="pro-ad-service">
                                <div class="pro-ad-service-title"><i class="fas fa-robot"></i> AI Career Mentor</div>
                                <ul class="pro-ad-features">
                                    <li>Unlimited mentor chats</li>
                                    <li>Interview preparation</li>
                                </ul>
                            </div>
                        </div>
                        <a href="<?= base_url('premium/plans') ?>" class="btn btn-primary dashboard-strategy-btn mt-3">
                            View Plans <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                    <div class="dashboard-cta-art d-none d-lg-flex" aria-hidden="true">
                        <div class="dashboard-cta-orb">
                            <i class="fas fa-crown"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="dashboard-section pt-0">
        <div class="container-fluid px-lg-5">
            <?php if (!empty($jobCategories)): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="section-title mb-0">Explore by Role</h2>
                    <p class="section-subtitle mb-0">Quickly find openings in your preferred specialized domains.</p>
                </div>
            </div>
            <div class="top-companies-grid mb-5">
                <?php foreach ($jobCategories as $category): ?>
                    <a href="<?= base_url('jobs?category=' . urlencode((string)($category['name'] ?? ''))) ?>" class="top-company-card">
                        <div class="top-company-logo">
                            <i class="<?= esc((string)($category['icon'] ?? 'fas fa-briefcase')) ?> text-primary candidate-icon-lg"></i>
                        </div>
                        <div class="top-company-info">
                            <div class="top-company-name"><?= esc((string)($category['name'] ?? 'Role')) ?></div>
                            <div class="top-company-jobs"><?= (int)($category['job_count'] ?? 0) ?> <?= (int)($category['job_count'] ?? 0) === 1 ? 'opening' : 'openings' ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
</div>
    </section>
    
    <section class="dashboard-section pt-0">
        <div class="container-fluid px-lg-5">
            <?php
            $topHiringCompanies = $topHiringCompanies ?? [];
            ?>
            <?php if (!empty($topHiringCompanies)): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title mb-0">Top Companies Hiring Now</h2>
                <a href="<?= base_url('candidate/company-job-discovery') ?>" class="btn btn-ghost text-primary">View all <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <div class="top-companies-grid">
                <?php foreach ($topHiringCompanies as $co): ?>
                    <?php
                    $coName    = trim((string) ($co['name'] ?? 'Company'));
                    $dbLogo    = trim((string) ($co['logo'] ?? ''));
                    $website   = trim((string) ($co['website'] ?? ''));
                    $websiteHost = $website !== '' ? (parse_url($website, PHP_URL_HOST) ?: $website) : '';
                    $websiteHost = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
                    $googleLogoUrl = $websiteHost !== '' ? 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96' : '';
                    $coLogoResolved = $dbLogo !== '' ? $resolveAssetUrl($dbLogo) : $googleLogoUrl;

                    $coIndustry = trim((string) ($co['industry'] ?? ''));
                    $coJobs    = (int) ($co['job_count'] ?? 0);
                    $coInitial = strtoupper(substr($coName, 0, 1) ?: 'C');
                    $coId      = (int) ($co['company_id'] ?? 0);
                    $coUrl     = $coId > 0 ? base_url('company/' . $coId) : base_url('jobs?company=' . urlencode($coName));

                    $fallbackHtml = '<span>' . esc($coInitial) . '</span>';
                    $logoErrorJs = "if(this.dataset.googleLogo&&this.src!==this.dataset.googleLogo){this.src=this.dataset.googleLogo;}else{this.parentNode.innerHTML='" . $fallbackHtml . "';}";
                    ?>
                    <a href="<?= esc($coUrl) ?>" class="top-company-card">
                        <div class="top-company-logo">
                            <?php if ($coLogoResolved !== ''): ?>
                                <img src="<?= esc($coLogoResolved) ?>" alt="<?= esc($coName) ?>" data-google-logo="<?= esc($googleLogoUrl) ?>" onerror="<?= esc($logoErrorJs, 'attr') ?>">
                            <?php else: ?>
                                <span><?= esc($coInitial) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="top-company-info">
                            <div class="top-company-name"><?= esc($coName) ?></div>
                            <?php if ($coIndustry !== ''): ?>
                                <div class="top-company-industry"><?= esc($coIndustry) ?></div>
                            <?php endif; ?>
                            <div class="top-company-jobs"><?= $coJobs ?> <?= $coJobs === 1 ? 'opening' : 'openings' ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($blogPosts)): ?>
        <?= view('candidate/dashboard_blog_section', ['blogPosts' => $blogPosts]) ?>
    <?php endif; ?>

    <section class="dashboard-section pt-0">
        <div class="container-fluid px-lg-5">
            <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="section-title">Recent Applications</h2>
                    <p class="section-subtitle">Track your application status and next steps</p>
                </div>
                <a href="<?= base_url('candidate/applications') ?>" class="btn btn-ghost text-primary">View all applications <i class="fas fa-arrow-right ms-2"></i></a>
            </div>

            <div class="dashboard-panel dashboard-table-wrap">
                <div class="panel-body">
                    <?php if (empty($recentApps)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4 class="mb-2">No applications yet</h4>
                            <p class="text-muted mb-4">Start exploring opportunities and submit your first application.</p>
                            <a href="<?= base_url('jobs') ?>" class="btn btn-primary btn-lg">Browse Jobs</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Company</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentApps as $application): ?>
                                        <tr>
                                            <td><strong><?= esc($application['job_title'] ?? '-') ?></strong></td>
                                            <td><?= esc($application['company_name'] ?? '-') ?></td>
                                            <td><?= !empty($application['applied_at']) ? $formatDate($application['applied_at']) : '-' ?></td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?= esc(ucwords(str_replace('_', ' ', (string) ($application['status'] ?? 'applied')))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('job/' . (int) ($application['job_id'] ?? 0)) ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    </div><!-- /.dash-grid__main -->

</div><!-- /.dash-grid -->
</div><!-- /.dashboard-jobboard -->


<?= view('Layouts/candidate_footer') ?>
            

