                <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="google-adsense-account" content="ca-pub-5380525657635231">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="csrf-name" content="<?= csrf_token() ?>">
    <title><?= esc($title ?? 'Candidate Portal') ?></title>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5380525657635231"
     crossorigin="anonymous"></script>
    <script>
        (function () {
            try {
                if (localStorage.getItem('theme') === 'dark') {
                    document.documentElement.classList.add('hm-dark-preload');
                }
            } catch (error) {}
        })();
    </script>
    <head>
    <!-- Inside the <head> tag -->
    <link rel="icon" type="image/png" href="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>">

    <?php
        $candidateAssetPath = '/' . trim((string) parse_url(current_url(), PHP_URL_PATH), '/');
        $candidateAssetOptions = is_array($candidateAssets ?? null) ? $candidateAssets : [];
        $candidateAssetEnabled = static function (string $key, bool $default = false) use ($candidateAssetOptions): bool {
            return array_key_exists($key, $candidateAssetOptions) ? (bool) $candidateAssetOptions[$key] : $default;
        };
        $candidateNeedsFancybox = $candidateAssetEnabled('fancybox');
        $candidateNeedsBootstrapSelect = $candidateAssetEnabled('bootstrap-select');
        $candidateNeedsOwlCarousel = $candidateAssetEnabled('owl-carousel');
        $candidateNeedsAnimate = $candidateAssetEnabled('animate');
        $candidateNeedsAtsCircle = $candidateAssetEnabled(
            'ats-circle',
            str_ends_with($candidateAssetPath, '/jobs') || str_contains($candidateAssetPath, '/candidate/smart-jobs')
        );
    ?>

    <link rel="stylesheet" href="<?= base_url('jobboard/css/theme-colors.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/custom-bs.css') ?>">
    <?php if ($candidateNeedsFancybox): ?>
        <link rel="stylesheet" href="<?= base_url('jobboard/css/jquery.fancybox.min.css') ?>">
    <?php endif; ?>
    <?php if ($candidateNeedsBootstrapSelect): ?>
        <link rel="stylesheet" href="<?= base_url('jobboard/css/bootstrap-select.min.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/icomoon/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/line-icons/style.css') ?>">
    <?php if ($candidateNeedsOwlCarousel): ?>
        <link rel="stylesheet" href="<?= base_url('jobboard/css/owl.carousel.min.css') ?>">
    <?php endif; ?>
    <?php if ($candidateNeedsAnimate): ?>
        <link rel="stylesheet" href="<?= base_url('jobboard/css/animate.min.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('jobboard/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/candidate-bundle.min.css?v=' . @filemtime(FCPATH . 'jobboard/css/candidate-bundle.min.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/fontawesome-all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/responsive.min.css?v=' . @filemtime(FCPATH . 'jobboard/css/responsive.min.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/candidate-pages.css?v=' . @filemtime(FCPATH . 'jobboard/css/candidate-pages.css')) ?>">
    <?php if ($candidateNeedsAtsCircle): ?>
        <!-- CSS Circle Progress (Required for visual ATS Score) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/css-percentage-circle/0.0.3/css/circle.min.css">
    <?php endif; ?>
    <style>
        html.hm-dark-preload,
        html.hm-dark-preload body,
        body.dark.candidate-app {
            background: #0f0f0f;
        }
        html.hm-dark-preload #overlayer,
        body.dark.candidate-app #overlayer {
            background: #0f0f0f !important;
        }
        html.hm-dark-preload .loader .spinner-border,
        body.dark.candidate-app .loader .spinner-border {
            color: #1FB7B5 !important;
        }
 
    </style>
</head>
<body id="top" class="hirematrix-app candidate-app">

<div id="overlayer"></div>
<div class="loader">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>

<div class="site-wrap">
    <?php
    $candidateId = (int) (session()->get('user_id') ?? 0);
    $candidateName = (string) (session()->get('user_name') ?? 'User');
    $candidateInitial = strtoupper(substr(trim($candidateName), 0, 1) ?: 'U');
    $request = service('request');
    $activeTab = (string) ($request->getGet('tab') ?? '');
    $headerSearch = (string) ($request->getGet('search') ?? '');
    $headerDesignation = (string) ($request->getGet('designation') ?? '');
    $headerCompany = (string) ($request->getGet('company') ?? '');
    $headerLocation = (string) ($request->getGet('location') ?? '');
    $headerExperienceRaw = $request->getGet('experience_level');
    $headerExperience = is_array($headerExperienceRaw)
        ? implode(', ', array_filter(array_map('strval', $headerExperienceRaw)))
        : (string) ($headerExperienceRaw ?? '');
    $currentPath = '/' . trim((string) parse_url(current_url(), PHP_URL_PATH), '/');
    $pathEndsWith = static function (string $suffix) use ($currentPath): bool {
        return $currentPath === $suffix || str_ends_with($currentPath, $suffix);
    };
    $candidatePhoto = (string) ($user['profile_photo'] ?? session()->get('profile_photo') ?? '');
    $unreadNotificationCount = $candidateId > 0
        ? (int) model('NotificationModel')->getUnreadCount($candidateId)
        : 0;
    $applicationCount = $candidateId > 0
        ? (int) model('ApplicationModel')->where('candidate_id', $candidateId)->countAllResults()
        : 0;
    $savedJobsCount = $candidateId > 0
        ? (int) model('SavedJobModel')->where('candidate_id', $candidateId)->countAllResults()
        : 0;
    $jobAlertsCount = $candidateId > 0
        ? (int) model('JobAlertModel')->where('candidate_id', $candidateId)->where('is_active', 1)->countAllResults()
        : 0;
    $profileStrength = (int) ($profileStrength ?? 0);
    $formatCompactCount = static function (int $count): string {
        return $count > 99 ? '99+' : (string) $count;
    };
    $profilePrompt = $profileStrength >= 80
        ? 'Your profile is recruiter-ready. Keep it active with fresh applications.'
        : ($profileStrength >= 50
            ? 'Complete a few more profile details to unlock better job matches.'
            : 'Complete your profile to improve visibility and get more relevant jobs.');
    $profilePromptCta = $profileStrength >= 80 ? 'View Profile' : 'Complete Profile';
    $profilePromptUrl = base_url('candidate/profile');
    $recommendationTitle = $savedJobsCount > 0 ? 'Revisit your saved jobs' : 'Jobs picked for your profile';
    $recommendationText = $savedJobsCount > 0
        ? 'You already have jobs shortlisted. Re-open them and apply before they go stale.'
        : 'Explore recommended roles matched to your profile, skills, and recent activity.';
    $recommendationUrl = $savedJobsCount > 0 ? base_url('candidate/saved-jobs') : base_url('jobs?tab=suggested');
    $recommendationCta = $savedJobsCount > 0 ? 'View Saved Jobs' : 'See Recommended Jobs';
    $isHomeActive = $pathEndsWith('/candidate') || $pathEndsWith('/candidate/dashboard');
    $isJobDetailsActive = str_contains($currentPath, '/job/');
    $isJobsListActive = $pathEndsWith('/jobs');
    $isRecommendedActive = $isJobsListActive && $activeTab === 'suggested';
    $isApplicationStatusActive = $pathEndsWith('/candidate/applications');
    $isSavedJobsActive = $pathEndsWith('/candidate/saved-jobs');
    $isJobAlertsActive = false;
    $isCompaniesActive = str_contains($currentPath, '/company/') || str_contains($currentPath, '/candidate/company-job-discovery');
    $isJobsRoot = $isJobsListActive || $isSavedJobsActive || $isJobDetailsActive;
    $isJobsActive = $isJobsRoot || $isApplicationStatusActive || $isJobAlertsActive;
    $isCareerTransitionActive = str_contains($currentPath, '/career-transition');
    $isResumeStudioActive = $pathEndsWith('/candidate/resume-studio');
    $isPremiumMentorActive = str_contains($currentPath, '/premium-mentor');
    $isJobStrategyActive = str_contains($currentPath, '/job-strategy');
    $isServicesActive = $isCareerTransitionActive || $isResumeStudioActive || $isPremiumMentorActive || $isJobStrategyActive ;
    $activeCompanySegment = trim((string) service('request')->getGet('segment'));
    $companyNavSegments = [
        '' => ['label' => 'All Companies', 'icon' => 'fas fa-building'],
        'indian-mnc' => ['label' => 'Indian MNCs', 'icon' => 'fas fa-building-flag'],
        'global-indian' => ['label' => 'Global Indian', 'icon' => 'fas fa-globe-asia'],
        'corporate' => ['label' => 'Corporate', 'icon' => 'fas fa-city'],
        'startups' => ['label' => 'Startups', 'icon' => 'fas fa-rocket'],
        'product' => ['label' => 'Product Companies', 'icon' => 'fas fa-cube'],
        'service' => ['label' => 'Service Companies', 'icon' => 'fas fa-people-carry-box'],
        'remote-friendly' => ['label' => 'Remote Friendly', 'icon' => 'fas fa-laptop-house'],
        'freshers' => ['label' => 'Freshers Hiring', 'icon' => 'fas fa-user-graduate'],
    ];
    $companySegmentUrl = static function (string $segment): string {
        return $segment === ''
            ? base_url('candidate/company-job-discovery')
            : base_url('candidate/company-job-discovery') . '?' . http_build_query(['segment' => $segment]);
    };

    $homeNavClass = $isHomeActive ? 'nav-link active' : 'nav-link';
    $jobsNavClass = $isJobsActive ? 'nav-link active' : 'nav-link';
    $companiesNavClass = $isCompaniesActive ? 'nav-link active' : 'nav-link';
    $servicesNavClass = $isServicesActive ? 'nav-link active' : 'nav-link';
    $recommendedClass = $isRecommendedActive ? 'active' : '';
    $applicationStatusClass = $isApplicationStatusActive ? 'active' : '';
    $savedJobsClass = $isSavedJobsActive ? 'active' : '';
    $careerTransitionClass = $isCareerTransitionActive ? 'active' : '';
    $resumeStudioClass = $isResumeStudioActive ? 'active' : '';
    $jobStrategyClass = $isJobStrategyActive ? 'active' : '';
    $companyJobDiscoveryClass = str_contains($currentPath, '/candidate/company-job-discovery') ? 'active' : '';
    $premiumMentorClass = $isPremiumMentorActive ? 'active' : '';
    
    if ($candidatePhoto === '' && $candidateId > 0) {
        $candidateRecord = model('UserModel')->findCandidateWithProfile($candidateId);
        $candidatePhoto = (string) ($candidateRecord['profile_photo'] ?? '');
    }
    $candidatePhotoUrl = '';
    if ($candidatePhoto !== '') {
        if (preg_match('/^https?:\/\//i', $candidatePhoto)) {
            $candidatePhotoUrl = $candidatePhoto;
        } else {
            $candidatePhotoPath = ltrim(str_replace('\\', '/', $candidatePhoto), '/');
            if ($candidatePhotoPath !== '' && is_file(FCPATH . $candidatePhotoPath)) {
                $candidatePhotoUrl = base_url($candidatePhotoPath);
            }
        }
    }
    $premiumSubscription = null;
    if ($candidateId > 0) {
        try {
            $premiumSubscription = model('SubscriptionModel')->getUserActiveSubscription($candidateId);
        } catch (\Throwable $e) {
            $premiumSubscription = null;
        }
    }
    $premiumLocked = !$premiumSubscription;
    $careerTransitionUrl = $premiumLocked ? base_url('premium/plans?service=career-transition') : base_url('career-transition');
    $resumeStudioUrl = $premiumLocked ? base_url('premium/plans?service=resume-studio') : base_url('candidate/resume-studio');
    $mentorUrl = $premiumLocked ? base_url('premium/plans?service=mentor') : base_url('premium-mentor');
    ?>
    <div class="site-mobile-menu site-navbar-target">
        <div class="site-mobile-menu-header">
            <div class="site-mobile-menu-close mt-3">
                <span class="icon-close2 js-menu-toggle"></span>
            </div>
        </div>
        <div class="site-mobile-menu-body"></div>
    </div>

    <!-- Naukri-style hamburger drawer -->
    <div class="hm-drawer" id="hmDrawer" aria-hidden="true">
        <div class="hm-drawer-inner">

            <!-- Header: profile card -->
            <div class="hm-drawer-head">
                <div class="hm-drawer-avatar">
                    <?php if ($candidatePhotoUrl !== ''): ?>
                        <img src="<?= esc($candidatePhotoUrl) ?>" alt="<?= esc($candidateName) ?>">
                    <?php else: ?>
                        <span><?= esc($candidateInitial) ?></span>
                    <?php endif; ?>
                </div>
                <div class="hm-drawer-user">
                    <strong><?= esc($candidateName) ?></strong>
                    <span><?= esc($profileHeadline ?? 'Candidate') ?></span>
                </div>
                <button class="hm-drawer-close" id="hmDrawerClose" aria-label="Close menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Profile completion bar -->
            <div class="hm-drawer-progress">
                <div class="hm-drawer-progress-row">
                    <span>Profile completion</span>
                    <strong><?= $profileStrength ?>%</strong>
                </div>
                <div class="hm-drawer-progress-bar">
                    <div class="candidate-progress-fill" style="--candidate-progress:<?= $profileStrength ?>%"></div>
                </div>
                <p class="hm-drawer-progress-note"><?= esc($profilePrompt) ?></p>
                <div class="hm-drawer-metrics" aria-label="Candidate activity snapshot">
                    <a href="<?= base_url('candidate/applications') ?>" class="hm-drawer-metric-chip">
                        <strong><?= esc($formatCompactCount($applicationCount)) ?></strong>
                        <span>Applied</span>
                    </a>
                    <a href="<?= base_url('candidate/saved-jobs') ?>" class="hm-drawer-metric-chip">
                        <strong><?= esc($formatCompactCount($savedJobsCount)) ?></strong>
                        <span>Saved</span>
                    </a>
                    <a href="<?= base_url('candidate/job-alerts') ?>" class="hm-drawer-metric-chip">
                        <strong><?= esc($formatCompactCount($jobAlertsCount)) ?></strong>
                        <span>Alerts</span>
                    </a>
                </div>
            </div>

            <div class="hm-drawer-body">

                <!-- Section: My Activity -->
                <div class="hm-drawer-section">
                    <div class="hm-drawer-section-title">My Activity</div>
                    <a href="<?= base_url('candidate/dashboard') ?>" class="hm-drawer-link <?= $isHomeActive ? 'is-active' : '' ?>">
                        <span class="hm-drawer-link-icon"><i class="fas fa-home"></i></span>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= base_url('candidate/applications') ?>" class="hm-drawer-link <?= $isApplicationStatusActive ? 'is-active' : '' ?>">
                        <span class="hm-drawer-link-icon"><i class="fas fa-briefcase"></i></span>
                        <span>My Applications</span>
                        <span class="hm-drawer-pill hm-drawer-pill-muted"><?= esc($formatCompactCount($applicationCount)) ?></span>
                    </a>
                    <a href="<?= base_url('candidate/saved-jobs') ?>" class="hm-drawer-link <?= $isSavedJobsActive ? 'is-active' : '' ?>">
                        <span class="hm-drawer-link-icon"><i class="fas fa-bookmark"></i></span>
                        <span>Saved Jobs</span>
                        <span class="hm-drawer-pill hm-drawer-pill-muted"><?= esc($formatCompactCount($savedJobsCount)) ?></span>
                    </a>
                    <a href="<?= base_url('candidate/my-bookings') ?>" class="hm-drawer-link">
                        <span class="hm-drawer-link-icon"><i class="fas fa-calendar-check"></i></span>
                        <span>Interview Bookings</span>
                    </a>
                    <a href="<?= base_url('notifications') ?>" class="hm-drawer-link">
                        <span class="hm-drawer-link-icon"><i class="fas fa-bell"></i></span>
                        <span>Notifications</span>
                        <?php if ($unreadNotificationCount > 0): ?>
                            <span class="hm-drawer-badge"><?= $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Section: Jobs -->
                <div class="hm-drawer-section">
                    <div class="hm-drawer-section-title">Jobs</div>
                    <a href="<?= base_url('jobs?tab=suggested') ?>" class="hm-drawer-link">
                        <span class="hm-drawer-link-icon"><i class="fas fa-fire"></i></span>
                        <span>Recommended Jobs</span>
                        <span class="hm-drawer-pill hm-drawer-pill-accent">For You</span>
                    </a>
                </div>

                <!-- Section: Companies -->
                <div class="hm-drawer-section">
                    <div class="hm-drawer-section-title">Companies</div>
                    <?php foreach ($companyNavSegments as $segmentKey => $segmentItem): ?>
                        <?php $isSegmentActive = str_contains($currentPath, '/candidate/company-job-discovery') && $activeCompanySegment === (string) $segmentKey; ?>
                        <a href="<?= esc($companySegmentUrl((string) $segmentKey)) ?>" class="hm-drawer-link <?= $isSegmentActive ? 'is-active' : '' ?>">
                            <span class="hm-drawer-link-icon"><i class="<?= esc($segmentItem['icon']) ?>"></i></span>
                            <span><?= esc($segmentItem['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Section: Career Tools & Interview Prep -->
                <div class="hm-drawer-section">
                    <div class="hm-drawer-section-title">Career Tools</div>
                    <a href="<?= base_url('candidate/job-search-strategy') ?>" class="hm-drawer-link <?= $isJobStrategyActive ? 'is-active' : '' ?>">
                        <span class="hm-drawer-link-icon"><i class="fas fa-chart-line"></i></span>
                        <span>Job Search Strategy Coach</span>
                    </a>
                    <a href="<?= esc($careerTransitionUrl) ?>" class="hm-drawer-link <?= $isCareerTransitionActive ? 'is-active' : '' ?>">
                        <span class="cand-leftnav__icon"><i class="fas fa-rocket" style="font-weight: bold; background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;"></i></span>
                        <span style="font-weight: bold; background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;">Career Transition AI</span>
                        <?php if ($premiumLocked): ?><span class="hm-drawer-pro">Pro</span><?php endif; ?>
                    </a>
                    <a href="<?= esc($resumeStudioUrl) ?>" class="hm-drawer-link <?= $isResumeStudioActive ? 'is-active' : '' ?>">
                        <span class="cand-leftnav__icon"><i class="fas fa-file-alt" style="font-weight: bold; background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;"></i></span>
                        <span style="font-weight: bold; background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;">Resume Studio</span>
                        <?php if ($premiumLocked): ?><span class="hm-drawer-pro">Pro</span><?php endif; ?>
                    </a>
                    <a href="<?= esc($mentorUrl) ?>" class="hm-drawer-link <?= $isPremiumMentorActive ? 'is-active' : '' ?>">
                        <span class="hm-drawer-link-icon"><i class="fas fa-comments"></i></span>
                        <span>AI Career Mentor</span>
                        <?php if ($premiumLocked): ?><span class="hm-drawer-pro">Pro</span><?php endif; ?>
                    </a>
                </div>

                <!-- Section: Account -->
                <div class="hm-drawer-section">
                    <div class="hm-drawer-section-title">Account</div>
                    <a href="<?= base_url('candidate/profile') ?>" class="hm-drawer-link">
                        <span class="hm-drawer-link-icon"><i class="fas fa-user"></i></span>
                        <span>My Profile</span>
                    </a>
                    <a href="<?= base_url('candidate/settings') ?>" class="hm-drawer-link">
                        <span class="hm-drawer-link-icon"><i class="fas fa-cog"></i></span>
                        <span>Settings</span>
                    </a>
                    <a href="<?= base_url('premium/plans') ?>" class="hm-drawer-link">
                        <span class="hm-drawer-link-icon"><i class="fas fa-crown"></i></span>
                        <span>Premium Plans</span>
                    </a>
                    <a href="<?= base_url('payment/history') ?>" class="hm-drawer-link">
                        <span class="hm-drawer-link-icon"><i class="fas fa-receipt"></i></span>
                        <span>Payment History</span>
                    </a>
                </div>

            </div>

            <!-- Footer: logout -->
            <div class="hm-drawer-footer">
                <a href="<?= base_url('logout') ?>" class="hm-drawer-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

        </div>
    </div>
    <div class="hm-drawer-overlay" id="hmDrawerOverlay"></div>

    <!-- ═══════════════════════════════════════════════
         LEFT VERTICAL NAV  (desktop ≥ 1100px)
    ═══════════════════════════════════════════════ -->
    <aside class="cand-leftnav" id="candLeftnav">

        <!-- Brand -->
        <div class="cand-leftnav__brand">
            <a href="<?= base_url('candidate/dashboard') ?>" class="cand-leftnav__logo">
                <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix">
            </a>
            <div class="cand-leftnav__brand-text">
                <span class="cand-leftnav__brand-name">Hire<span>Matrix</span></span>
                <span class="cand-leftnav__brand-tag">Candidate Portal</span>
            </div>
        </div>
                <!-- Nav groups -->
        <nav class="cand-leftnav__nav">

            <div class="cand-leftnav__section" id="navSection-overview">
                <button class="cand-leftnav__group-label" data-section="overview" aria-expanded="true">
                    <span class="cand-leftnav__group-icon"><i class="fas fa-compass"></i></span>
                    <span>Overview</span>
                    <i class="fas fa-chevron-down cand-leftnav__chevron"></i>
                </button>
                <div class="cand-leftnav__group-body">
                    <a href="<?= base_url('candidate/dashboard') ?>" class="cand-leftnav__link <?= $isHomeActive ? 'is-active' : '' ?>" title="Dashboard">
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= base_url('notifications') ?>" class="cand-leftnav__link" title="Notifications">
                        <span>Notifications</span>
                        <?php if ($unreadNotificationCount > 0): ?>
                            <span class="cand-leftnav__badge"><?= $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <div class="cand-leftnav__section" id="navSection-jobs">
                <button class="cand-leftnav__group-label" data-section="jobs" aria-expanded="true">
                    <span class="cand-leftnav__group-icon"><i class="fas fa-briefcase"></i></span>
                    <span>Jobs</span>
                    <i class="fas fa-chevron-down cand-leftnav__chevron"></i>
                </button>
                <div class="cand-leftnav__group-body">
                    <a href="<?= base_url('jobs?tab=suggested') ?>" class="cand-leftnav__link <?= $isRecommendedActive ? 'is-active' : '' ?>" title="Recommended">
                        <span>Recommended</span>
                        <span class="cand-leftnav__pill cand-leftnav__pill--accent">For You</span>
                    </a>
                    <a href="<?= base_url('candidate/applications') ?>" class="cand-leftnav__link <?= $isApplicationStatusActive ? 'is-active' : '' ?>" title="Applications">
                        <span>Applications</span>
                        <?php if ($applicationCount > 0): ?>
                            <span class="cand-leftnav__pill"><?= $formatCompactCount($applicationCount) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= base_url('candidate/saved-jobs') ?>" class="cand-leftnav__link <?= $isSavedJobsActive ? 'is-active' : '' ?>" title="Saved Jobs">
                        <span>Saved Jobs</span>
                        <?php if ($savedJobsCount > 0): ?>
                            <span class="cand-leftnav__pill"><?= $formatCompactCount($savedJobsCount) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= base_url('candidate/my-bookings') ?>" class="cand-leftnav__link" title="Interviews">
                        <span>Interviews</span>
                    </a>
                </div>
            </div>

            <div class="cand-leftnav__section" id="navSection-companies">
                <button class="cand-leftnav__group-label" data-section="companies" aria-expanded="true">
                    <span class="cand-leftnav__group-icon"><i class="fas fa-building"></i></span>
                    <span>Companies</span>
                    <i class="fas fa-chevron-down cand-leftnav__chevron"></i>
                </button>
                <div class="cand-leftnav__group-body">
                    <?php foreach ($companyNavSegments as $segmentKey => $segmentItem): ?>
                        <?php $isSegmentActive = str_contains($currentPath, '/candidate/company-job-discovery') && $activeCompanySegment === (string) $segmentKey; ?>
                        <a href="<?= esc($companySegmentUrl((string) $segmentKey)) ?>" class="cand-leftnav__link <?= $isSegmentActive ? 'is-active' : '' ?>" title="<?= esc($segmentItem['label']) ?>">
                            <span><?= esc($segmentItem['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cand-leftnav__section" id="navSection-career">
                <button class="cand-leftnav__group-label" data-section="career" aria-expanded="true">
                    <span class="cand-leftnav__group-icon"><i class="fas fa-chart-line"></i></span>
                    <span>Career Tools</span>
                    <i class="fas fa-chevron-down cand-leftnav__chevron"></i>
                </button>
                <div class="cand-leftnav__group-body">
                    <a href="<?= base_url('candidate/job-search-strategy') ?>" class="cand-leftnav__link <?= $isJobStrategyActive ? 'is-active' : '' ?>" title="Strategy Coach">
                        <span>Strategy Coach</span>
                    </a>
                    <a href="<?= esc($careerTransitionUrl) ?>" class="cand-leftnav__link <?= $isCareerTransitionActive ? 'is-active' : '' ?>" title="Career Transition">
                        <span>Career Transition</span>
                        <?php if ($premiumLocked): ?><span class="cand-leftnav__pro">Pro</span><?php endif; ?>
                    </a>
                    <a href="<?= esc($resumeStudioUrl) ?>" class="cand-leftnav__link <?= $isResumeStudioActive ? 'is-active' : '' ?>" title="Resume Studio">
                        <span>Resume Studio</span>
                        <?php if ($premiumLocked): ?><span class="cand-leftnav__pro">Pro</span><?php endif; ?>
                    </a>
                    <a href="<?= esc($mentorUrl) ?>" class="cand-leftnav__link <?= $isPremiumMentorActive ? 'is-active' : '' ?>" title="AI Mentor">
                        <span>AI Mentor</span>
                        <?php if ($premiumLocked): ?><span class="cand-leftnav__pro">Pro</span><?php endif; ?>
                    </a>
                </div>
            </div>

        </nav>

        <!-- Bottom: utility actions -->
        <div class="cand-leftnav__bottom">

            <div class="cand-leftnav__user" id="candidateLeftnavUser">
                <div class="cand-leftnav__user-dropdown" id="candidateLeftnavUserDropdown">
                    <div class="cand-leftnav__dropdown-head">
                        <span class="cand-leftnav__dropdown-avatar">
                            <?php if ($candidatePhotoUrl !== ''): ?>
                                <img src="<?= esc($candidatePhotoUrl) ?>" alt="<?= esc($candidateName) ?>">
                            <?php else: ?>
                                <?= esc($candidateInitial) ?>
                            <?php endif; ?>
                        </span>
                        <span class="cand-leftnav__dropdown-user">
                            <strong><?= esc($candidateName) ?></strong>
                            <span><?= esc($profileHeadline ?? 'Candidate') ?></span>
                        </span>
                    </div>
                    <a href="<?= base_url('candidate/profile') ?>"><i class="fas fa-user"></i><span>My Profile</span></a>
                    <a href="<?= base_url('candidate/settings') ?>"><i class="fas fa-cog"></i><span>Settings</span></a>
                    <a href="<?= base_url('premium/plans') ?>" class="cand-leftnav__premium-link"><i class="fas fa-gem"></i><span>Premium Plans</span></a>
                    <a href="<?= base_url('payment/history') ?>"><i class="fas fa-credit-card"></i><span>Payment History</span></a>
                    <a href="<?= base_url('logout') ?>" class="cand-leftnav__logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                </div>
                <button type="button" class="cand-leftnav__user-btn" id="candidateLeftnavUserBtn" aria-haspopup="true" aria-expanded="false">
                    <span class="cand-leftnav__avatar">
                        <?php if ($candidatePhotoUrl !== ''): ?>
                            <img src="<?= esc($candidatePhotoUrl) ?>" alt="<?= esc($candidateName) ?>">
                        <?php else: ?>
                            <?= esc($candidateInitial) ?>
                        <?php endif; ?>
                    </span>
                    <span class="cand-leftnav__user-info">
                        <strong><?= esc($candidateName) ?></strong>
                        <span>Candidate</span>
                    </span>
                    <i class="fas fa-chevron-up cand-leftnav__user-more"></i>
                </button>
            </div>

        </div>

    </aside>

    <!-- ═══════════════════════════════════════════════
         MOBILE TOP BAR  (< 1100px)
    ═══════════════════════════════════════════════ -->
    <header class="cand-topbar" id="candTopbar">
        <a href="<?= base_url('candidate/dashboard') ?>" class="cand-topbar__logo">
            <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix" class="candidate-logo-sm">
            <span class="cand-topbar__brand-name">Hire<span>Matrix</span></span>
        </a>
        <div class="cand-topbar__actions">
            <button type="button" class="mobile-nav-icon" id="mobileSearchToggle" title="Search" aria-label="Search">
                <span class="icon-search"></span>
            </button>
            <!-- <a href="<?= base_url('notifications') ?>" class="mobile-nav-icon <?= $unreadNotificationCount > 0 ? 'has-unread' : '' ?>" title="Notifications">
                <span class="icon-bell"></span>
                <?php if ($unreadNotificationCount > 0): ?>
                    <span class="mobile-nav-badge js-notification-badge" data-unread-count="<?= $unreadNotificationCount ?>"><?= $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount ?></span>
                <?php endif; ?>
            </a> -->
            <!-- Avatar dropdown -->
            <div class="cand-topbar__avatar-menu" id="candidateAvatarMenu">
                <button type="button" class="cand-topbar__avatar-btn" id="candidateAvatarBtn" aria-haspopup="true" aria-expanded="false">
                    <div class="cand-topbar__avatar">
                        <?php if ($candidatePhotoUrl !== ''): ?>
                            <img src="<?= esc($candidatePhotoUrl) ?>" alt="<?= esc($candidateName) ?>">
                        <?php else: ?>
                            <?= esc($candidateInitial) ?>
                        <?php endif; ?>
                    </div>
                </button>
                <div class="candidate-avatar-dropdown cand-topbar__user-dropdown" id="candidateAvatarDropdown">
                    <div class="cand-leftnav__dropdown-head">
                        <span class="cand-leftnav__dropdown-avatar">
                            <?php if ($candidatePhotoUrl !== ''): ?>
                                <img src="<?= esc($candidatePhotoUrl) ?>" alt="<?= esc($candidateName) ?>">
                            <?php else: ?>
                                <?= esc($candidateInitial) ?>
                            <?php endif; ?>
                        </span>
                        <span class="cand-leftnav__dropdown-user">
                            <strong><?= esc($candidateName) ?></strong>
                            <span><?= esc($profileHeadline ?? 'Candidate') ?></span>
                        </span>
                    </div>
                    <a href="<?= base_url('candidate/profile') ?>"><i class="fas fa-user"></i><span>My Profile</span></a>
                    <a href="<?= base_url('candidate/settings') ?>"><i class="fas fa-cog"></i><span>Settings</span></a>
                    <a href="<?= base_url('premium/plans') ?>" class="cand-leftnav__premium-link"><i class="fas fa-gem"></i><span>Premium Plans</span></a>
                    <a href="<?= base_url('payment/history') ?>"><i class="fas fa-credit-card"></i><span>Payment History</span></a>
                    <a href="<?= base_url('logout') ?>" class="cand-leftnav__logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                </div>
            </div>
            <a href="#" class="mobile-nav-hamburger" id="hmDrawerToggle" aria-label="Menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </a>
        </div>
    </header>
    <!-- Mobile search drawer -->
    <div id="mobileSearchDrawer" class="mobile-search-drawer">
        <form action="<?= base_url('jobs') ?>" method="get" class="mobile-search-form">
            <div class="mobile-search-field">
                <span class="icon-search mobile-search-icon"></span>
                <input type="text" name="search" placeholder="Job title, skills or company" value="<?= esc($headerSearch !== '' ? $headerSearch : ($headerDesignation !== '' ? $headerDesignation : $headerCompany)) ?>" autocomplete="off">
            </div>
            <div class="mobile-search-row2">
                <div class="mobile-search-field mobile-search-field-half">
                    <i class="fas fa-map-marker-alt mobile-search-icon"></i>
                    <input type="text" name="location" placeholder="Location" value="<?= esc($headerLocation) ?>" autocomplete="off">
                </div>
                <div class="mobile-search-field mobile-search-field-half">
                    <i class="fas fa-briefcase mobile-search-icon"></i>
                    <select name="experience_level">
                        <option value="">Experience</option>
                        <?php foreach (['fresher' => 'Fresher', 'junior' => 'Junior', 'mid' => 'Mid-Level', 'senior' => 'Senior'] as $expValue => $expLabel): ?>
                            <option value="<?= esc($expValue) ?>" <?= strtolower($headerExperience) === strtolower($expValue) ? 'selected' : '' ?>><?= esc($expLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="mobile-search-submit"><span class="icon-search"></span> Search Jobs</button>
        </form>
    </div>
    <div id="mobileSearchOverlay" class="mobile-search-overlay"></div>

    <div class="candidate-workbar" role="search" aria-label="Candidate job search">
        <div class="candidate-workbar__title">
            <strong>Search Jobs</strong>
            <span>Find roles that match your preferences</span>
        </div>
        <form action="<?= base_url('jobs') ?>" method="get" class="candidate-workbar__form">
            <label class="candidate-workbar__field">
                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                <input type="text" name="location" placeholder="Location" value="<?= esc($headerLocation) ?>" autocomplete="off" aria-label="Location">
            </label>
            <label class="candidate-workbar__field">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="text" name="search" placeholder="Job title, skills or company" value="<?= esc($headerSearch !== '' ? $headerSearch : ($headerDesignation !== '' ? $headerDesignation : $headerCompany)) ?>" autocomplete="off" aria-label="Job title, skills or company">
            </label>
            <button type="submit" class="candidate-workbar__submit">Find Jobs</button>
        </form>
    </div>

    <main>
    <!-- Global mobile bottom tab bar (visible on all candidate pages ≤1100px) -->
    <nav class="dash-mobile-tabs" aria-label="Quick navigation">
        <?php
        $currentPath2 = '/' . trim((string) parse_url(current_url(), PHP_URL_PATH), '/');
        $tabActive = [
            'home'     => $currentPath2 === '/candidate' || $currentPath2 === '/candidate/dashboard',
            'jobs'     => str_contains($currentPath2, '/jobs') || str_contains($currentPath2, '/job/') || str_contains($currentPath2, '/candidate/company-job-discovery'),
            'applied'  => str_contains($currentPath2, '/candidate/applications'),
            'saved'    => str_contains($currentPath2, '/candidate/saved-jobs'),
            'profile'  => str_contains($currentPath2, '/candidate/profile') || str_contains($currentPath2, '/candidate/settings'),
        ];
        ?>
        <a href="<?= base_url('candidate/dashboard') ?>" class="dash-tab <?= $tabActive['home'] ? 'is-active' : '' ?>">
            <i class="fas fa-home"></i><span>Home</span>
        </a>
        <a href="<?= base_url('jobs?tab=suggested') ?>" class="dash-tab <?= $tabActive['jobs'] ? 'is-active' : '' ?>">
            <i class="fas fa-fire"></i><span>Jobs</span>
        </a>
        <a href="<?= base_url('candidate/applications') ?>" class="dash-tab <?= $tabActive['applied'] ? 'is-active' : '' ?>">
            <i class="fas fa-briefcase"></i><span>Applied</span>
        </a>
        <a href="<?= base_url('candidate/saved-jobs') ?>" class="dash-tab <?= $tabActive['saved'] ? 'is-active' : '' ?>">
            <i class="fas fa-bookmark"></i><span>Saved</span>
        </a>
        <a href="<?= base_url('candidate/profile') ?>" class="dash-tab <?= $tabActive['profile'] ? 'is-active' : '' ?>">
            <i class="fas fa-user"></i><span>Profile</span>
        </a>
    </nav>
        



