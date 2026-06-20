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
                <a href="<?= base_url('jobs?tab=suggested') ?>" class="btn btn-ghost text-primary">View all jobs</a>
            </div>

            <div class="dashboard-jobs-grid">
                <?php if (!empty($topSuggestedJobs)): ?>
                    <?php foreach (array_slice($topSuggestedJobs, 0, 4) as $job): ?>
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
                                <div class="pro-ad-service-title"><i class="fas fa-map-signs"></i> Career Transition AI</div>
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
                        <a href="<?= base_url('premium/plans') ?>" class="btn btn-primary dashboard-cta-btn">
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
            <div class="top-companies-grid dashboard-tile-grid dashboard-role-grid mb-5">
                <?php foreach (array_slice($jobCategories, 0, 10) as $category): ?>
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
                <a href="<?= base_url('candidate/company-job-discovery') ?>" class="btn btn-ghost text-primary">View all</a>
            </div>
            <div class="top-companies-grid dashboard-tile-grid dashboard-company-grid">
                <?php foreach (array_slice($topHiringCompanies, 0, 10) as $co): ?>
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
                <a href="<?= base_url('candidate/applications') ?>" class="btn btn-ghost text-primary">View all applications</a>
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
                                            <td><?= esc($application['company'] ?? $application['company_name'] ?? '-') ?></td>
                                            <td><?= !empty($application['applied_at']) ? $formatDate($application['applied_at']) : '-' ?></td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?= esc(ucwords(str_replace('_', ' ', (string) ($application['status'] ?? 'applied')))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('job/' . (int) ($application['job_id'] ?? 0)) ?>" class="dashboard-table-link dashboard-table-icon-link" aria-label="View <?= esc((string) ($application['job_title'] ?? 'application'), 'attr') ?>">
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
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



<!-- ═══════════════ FEATURE TOUR POPUP ═══════════════ -->
<style>
#hm-backdrop{
  position:fixed;inset:0;z-index:9998;
  background:rgba(22,33,43,0.55);
  display:none;align-items:center;justify-content:center;padding:20px;
  backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
}
#hm-backdrop.visible{display:flex;animation:hmFadeIn .22s ease;}
@keyframes hmFadeIn{from{opacity:0}to{opacity:1}}
#hm-modal{
  background:var(--card,#fff);border:1px solid var(--border,#D9ECE5);
  border-radius:10px;width:100%;max-width:440px;overflow:hidden;
  animation:hmPop .3s cubic-bezier(.34,1.56,.64,1);
}
@keyframes hmPop{from{opacity:0;transform:scale(.93) translateY(18px)}to{opacity:1;transform:none}}
.hm-header{padding:18px 20px 0;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;}
.hm-header-left{display:flex;flex-direction:column;gap:4px;}
.hm-eyebrow{font-size:18px !important;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--primary);}
.hm-counter{font-size:10px;font-weight:600;color:var(--text-light);letter-spacing:.04em;}
.hm-close{
  width:26px;height:26px;border-radius:5px;flex-shrink:0;
  border:1px solid var(--border);background:transparent;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  color:var(--text-light);font-size:13px;margin-top:1px;
  transition:background .15s,color .15s,border-color .15s;
}
.hm-close:hover{background:var(--muted);color:var(--foreground);border-color:var(--primary);}
.hm-progress{padding:14px 20px 0;display:flex;gap:4px;}
.hm-pip{height:2px;flex:1;border-radius:2px;background:var(--border);transition:background .3s;}
.hm-pip.active{background:var(--primary);}
.hm-pip.past{background:var(--secondary);}
.hm-slides{overflow:hidden;}
.hm-slide{display:none;animation:hmSlide .2s ease;}
.hm-slide.active{display:block;}
@keyframes hmSlide{from{opacity:0;transform:translateX(16px)}to{opacity:1;transform:none}}
.hm-body{padding:16px 20px 0;}
.hm-slide-title{font-size:19px;font-weight:700;color:var(--foreground);line-height:1.3;margin-bottom:8px;}
.hm-slide-title em{
  font-style:normal;background:var(--gradient-primary);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.hm-slide-desc{font-size:13px;color:var(--muted-foreground);line-height:1.65;margin-bottom:14px;}
.hm-rills2{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:18px;}
.hm-rill2{
  display:flex;align-items:center;gap:5px;padding:5px 10px;border-radius:999px;
  background:var(--muted);border:1px solid var(--border);
  font-size:12px;font-weight:600;color:var(--primary-dark);white-space:nowrap;
}
.hm-rill-dot{width:5px;height:5px;border-radius:50%;background:var(--primary);flex-shrink:0;}
.hm-divider{height:1px;background:var(--border);}
.hm-footer{padding:14px 20px;display:flex;align-items:center;gap:8px;}
.hm-btn-cta{
  padding:8px 18px;border-radius:6px;background:var(--gradient-primary);
  color:#fff !important;font-size:13px;font-weight:600;border:none;cursor:pointer;
  text-decoration:none !important;display:inline-block;white-space:nowrap;
  transition:opacity .15s,transform .15s;
}
.hm-btn-cta:hover{opacity:.88;transform:translateY(-1px);}
.hm-btn-next{
  padding:8px 16px;border-radius:6px;background:transparent;
  border:1px solid var(--border);color:var(--muted-foreground);
  font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap;
  transition:border-color .15s,color .15s,background .15s;
}
.hm-btn-next:hover{border-color:var(--primary);color:var(--primary);background:var(--muted);}
.hm-btn-skip{
  margin-left:auto;font-size:12px;color:var(--text-light);
  background:none;border:none;cursor:pointer;padding:2px;transition:color .15s;
}
.hm-btn-skip:hover{color:var(--muted-foreground);} 
  body.dark #hm-backdrop{background:#000000 !important;}
  body.dark #hm-modal{background:#000000 !important;border-color:#23343A;}
  body.dark .hm-close{border-color:#23343A;color:#7A8B96;}
  body.dark .hm-close:hover{background:#000000;color:#F8FAFC;border-color:#1FB7B5;}
  body.dark .hm-pip{background:#000000 !important;}  
  body.dark .hm-rill2{background:#000000 !important;border-color:#23343A;color:#1FB7B5;}
  body.dark .hm-divider{background:#000000;}
  body.dark .hm-btn-next{border-color:#23343A;color:#94A3B8;}
  body.dark .hm-btn-next:hover{border-color:#1FB7B5;color:#1FB7B5;background:#000000;}
  body.dark .hm-btn-skip{color:#7A8B96;}
  body.dark .hm-slide-title{color:#F8FAFC;}
  body.dark .hm-slide-desc{color:#94A3B8;} 
  /* Dark Theme */ 
</style>

<div id="hm-backdrop" role="dialog" aria-modal="true" aria-label="HireMatrix Feature Tour">
 <div id="hm-modal">
  <div class="hm-header">
   <div class="hm-header-left">
    <span class="hm-eyebrow">HireMatrix AI Features</span>
    <span class="hm-counter" id="hmCounter"></span>
   </div>
   <button class="hm-close" id="hmClose" aria-label="Close">✕</button>
  </div>
  <div class="hm-progress" id="hmProgress"></div>
  <div class="hm-slides">

   <div class="hm-slide active" id="hm-s1">
    <div class="hm-body">
     <h3 class="hm-slide-title">Your personalised path to a <em>new career</em></h3>
     <p class="hm-slide-desc">Not sure how to switch roles? AI maps your current skills against your target role and hands you a complete, step-by-step transition plan.</p>
     <div class="hm-rills2">
      <span class="hm-rill2"> Skill gap analysis</span>
      <span class="hm-rill2"> Learning path</span>
      <span class="hm-rill2"> Certification guide</span>
      <span class="hm-rill2"> Role roadmap</span>
     </div>
    </div>
    <div class="hm-divider"></div>
    <div class="hm-footer">
     <a href="<?= base_url('career-transition') ?>" class="btn btn-outline-primary">Generate my roadmap</a>
     <button class="hm-btn-next" onclick="hmGoTo(2)">Next →</button>
     <button class="hm-btn-skip" onclick="hmClose()">Skip tour</button>
    </div>
   </div>

   <div class="hm-slide" id="hm-s2">
    <div class="hm-body">
     <h3 class="hm-slide-title">Walk in prepared, <em>walk out confident</em></h3>
     <p class="hm-slide-desc">Practice with AI-driven mock interviews tailored to your exact role — real questions, structured answer guidance, and instant post-round feedback.</p>
     <div class="hm-rills2">
      <span class="hm-rill2"> Role-specific questions</span>
      <span class="hm-rill2"> Mock rounds</span>
      <span class="hm-rill2">  Answer frameworks</span>
      <span class="hm-rill2"> Instant feedback</span>
     </div>
    </div>
    <div class="hm-divider"></div>
    <div class="hm-footer">
     <a href="<?= base_url('register') ?>" class="btn btn-outline-primary">Start practising</a>
     <button class="hm-btn-next" onclick="hmGoTo(3)">Next →</button>
     <button class="hm-btn-skip" onclick="hmClose()">Skip tour</button>
    </div>
   </div>

   <div class="hm-slide" id="hm-s3">
    <div class="hm-body">
     <h3 class="hm-slide-title">A tailored resume for <em>every application</em></h3>
     <p class="hm-slide-desc">Stop sending one CV to every job. Resume Studio adapts your resume per role, highlights what recruiters want, and gets you past ATS filters.</p>
     <div class="hm-rills2">
      <span class="hm-rill2"> Role-targeted CVs</span>
      <span class="hm-rill2"> ATS optimisation</span>
      <span class="hm-rill2"> AI improvement tips</span>
      <span class="hm-rill2"> Job fit scoring</span>
     </div>
    </div>
    <div class="hm-divider"></div>
    <div class="hm-footer">
     <a href="<?= base_url('register') ?>" class="btn btn-outline-primary">Build my resume</a>
     <button class="hm-btn-next" onclick="hmClose()">Done ✓</button>
    </div>
   </div>

  </div>
 </div>
</div>

<script>
(function(){
  if(sessionStorage.getItem('hm_tour_seen')){document.getElementById('hm-backdrop').remove();return;}
  var cur=1,tot=3,labels=['Career Transition AI','AI Interview Practice','Resume Studio'];
  function pips(){
    var c=document.getElementById('hmProgress');c.innerHTML='';
    for(var i=1;i<=tot;i++){
      var p=document.createElement('div');
      p.className='hm-pip'+(i<cur?' past':i===cur?' active':'');
      c.appendChild(p);
    }
    document.getElementById('hmCounter').textContent=cur+' of '+tot+' — '+labels[cur-1];
  }
  window.hmGoTo=function(n){
    document.getElementById('hm-s'+cur).classList.remove('active');
    cur=n;document.getElementById('hm-s'+cur).classList.add('active');pips();
  };
  window.hmClose=function(){
    sessionStorage.setItem('hm_tour_seen','1');
    var b=document.getElementById('hm-backdrop');
    b.style.transition='opacity .18s';b.style.opacity='0';
    setTimeout(function(){b.remove();},200);
  };
  document.getElementById('hmClose').addEventListener('click',hmClose);
  document.getElementById('hm-backdrop').addEventListener('click',function(e){if(e.target===this)hmClose();});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')hmClose();});
  pips();
  setTimeout(function(){document.getElementById('hm-backdrop').classList.add('visible');},1200);
})();
</script>

<?= view('Layouts/candidate_footer') ?>
            

