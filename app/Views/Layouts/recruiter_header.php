<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="base-url" content="<?= base_url() ?>">
    <title><?= esc($title ?? 'Recruiter Portal') ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>">

    <link rel="stylesheet" href="<?= base_url('jobboard/css/theme-colors.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/custom-bs.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/jquery.fancybox.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/bootstrap-select.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/icomoon/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/line-icons/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/owl.carousel.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/animate.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/recruiter-pages.css') ?>">
    <?php foreach (($pageStyles ?? []) as $styleHref): ?>
        <link rel="stylesheet" href="<?= esc($styleHref) ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/fontawesome-all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/responsive.css?v=' . @filemtime(FCPATH . 'jobboard/css/responsive.css')) ?>">
</head>
<body id="top" class="hirematrix-app recruiter-jobboard">
<script>
    (function () {
        try {
            if (localStorage.getItem('recruiter-theme') === 'dark') {
                document.body.classList.add('dark-mode');
                document.documentElement.setAttribute('data-recruiter-theme', 'dark');
            }
        } catch (error) {
            document.documentElement.removeAttribute('data-recruiter-theme');
        }
    })();
</script>
<div id="overlayer"></div>
<div class="loader">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>

<div class="site-wrap">
    <?php
    $recruiterId = (int) (session()->get('user_id') ?? 0);
    $recruiterName = (string) (session()->get('user_name') ?? 'Recruiter');
    $recruiterInitial = strtoupper(substr(trim($recruiterName), 0, 1) ?: 'R');
    $recruiterUnreadNotificationCount = $recruiterId > 0
        ? (int) model('NotificationModel')->getUnreadCount($recruiterId)
        : 0;
    $currentPath = '/' . trim((string) parse_url(current_url(), PHP_URL_PATH), '/');
    $isRecruiterDashboard = str_contains($currentPath, '/recruiter/dashboard');
    $isRecruiterJobs = str_contains($currentPath, '/recruiter/jobs') || str_contains($currentPath, '/recruiter/post_job');
    $isRecruiterCandidates = str_contains($currentPath, '/recruiter/candidates') || str_contains($currentPath, '/recruiter/candidate/');
    $isRecruiterSlots = str_contains($currentPath, '/recruiter/slots');
    $isRecruiterCompanyProfile = str_contains($currentPath, '/recruiter/company-profile');
    ?>
    <div class="site-mobile-menu site-navbar-target">
        <div class="site-mobile-menu-header">
            <div class="site-mobile-menu-close mt-3">
                <span class="icon-close2 js-menu-toggle"></span>
            </div>
        </div>
        <div class="site-mobile-menu-body"></div>
    </div>

    <div class="hm-drawer" id="hmDrawer" aria-hidden="true">
        <div class="hm-drawer-inner">
            <div class="hm-drawer-head">
                <div class="hm-drawer-avatar">
                    <span><?= esc($recruiterInitial) ?></span>
                </div>
                <div class="hm-drawer-user">
                    <strong><?= esc($recruiterName) ?></strong>
                    <span>Recruiter</span>
                </div>
                <button type="button" class="hm-drawer-close" id="hmDrawerClose" aria-label="Close menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="hm-drawer-body">
                <div class="hm-drawer-cta-card">
                    <div class="hm-drawer-cta-kicker">Candidate database</div>
                    <h3>Discover Top Talent</h3>
                    <p>Search beyond direct applicants. Compare comprehensive profiles and manage your pipeline in one workspace.</p>
                    <div class="hm-drawer-cta-actions">
                        <a href="<?= base_url('recruiter/post_job') ?>" class="hm-drawer-cta-primary">Post Job</a>
                        <a href="<?= base_url('recruiter/candidates') ?>" class="hm-drawer-cta-secondary">Find Talent</a>
                    </div>
                </div>

                <div class="hm-drawer-section">
                    <div class="hm-drawer-section-title">Recruiter</div>
                    <a href="<?= base_url('recruiter/dashboard') ?>" class="hm-drawer-link <?= $isRecruiterDashboard ? 'is-active' : '' ?>">
                        <span class="hm-drawer-link-icon"><i class="fas fa-home"></i></span>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= base_url('recruiter/jobs') ?>" class="hm-drawer-link <?= $isRecruiterJobs ? 'is-active' : '' ?>">
                        <span class="hm-drawer-link-icon"><i class="fas fa-briefcase"></i></span>
                        <span>My Jobs</span>
                    </a>
                    <a href="<?= base_url('recruiter/post_job') ?>" class="hm-drawer-link">
                        <span class="hm-drawer-link-icon"><i class="fas fa-plus"></i></span>
                        <span>Post a Job</span>
                    </a>
                    <a href="<?= base_url('recruiter/candidates') ?>" class="hm-drawer-link <?= $isRecruiterCandidates ? 'is-active' : '' ?>">
                        <span class="hm-drawer-link-icon"><i class="fas fa-users"></i></span>
                        <span>Candidates</span>
                    </a>
                    <a href="<?= base_url('recruiter/slots') ?>" class="hm-drawer-link <?= $isRecruiterSlots ? 'is-active' : '' ?>">
                        <span class="hm-drawer-link-icon"><i class="fas fa-calendar-alt"></i></span>
                        <span>Interview Slots</span>
                    </a>
                    <a href="<?= base_url('notifications') ?>" class="hm-drawer-link">
                        <span class="hm-drawer-link-icon"><i class="fas fa-bell"></i></span>
                        <span>Notifications</span>
                        <?php if ($recruiterUnreadNotificationCount > 0): ?>
                            <span class="hm-drawer-badge"><?= $recruiterUnreadNotificationCount > 99 ? '99+' : $recruiterUnreadNotificationCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="hm-drawer-section">
                    <div class="hm-drawer-section-title">Account</div>
                    <a href="<?= base_url('recruiter/company-profile') ?>" class="hm-drawer-link <?= $isRecruiterCompanyProfile ? 'is-active' : '' ?>">
                        <span class="hm-drawer-link-icon"><i class="fas fa-building"></i></span>
                        <span>Company Profile</span>
                    </a>
                    <a href="<?= base_url('account/change-password') ?>" class="hm-drawer-link">
                        <span class="hm-drawer-link-icon"><i class="fas fa-lock"></i></span>
                        <span>Change Password</span>
                    </a>
                </div>
            </div>

            <div class="hm-drawer-footer">
                <a href="<?= base_url('logout') ?>" class="hm-drawer-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
    <div class="hm-drawer-overlay" id="hmDrawerOverlay"></div>

    <header class="site-navbar site-navbar-target">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="site-logo col-6 col-xl-2">
                    <a href="<?= base_url('recruiter/dashboard') ?>" class="d-inline-flex align-items-center">
                        <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix Logo" style="height: 34px; width: auto; margin-right: 8px;">
                        <span style="text-transform: none;">HireMatrix</span>
                    </a>
                </div>
                <nav class="mx-auto site-navigation col-xl-7">
                    <ul class="site-menu js-clone-nav d-none d-xl-block ml-0 pl-0">
                        <li class="has-children">
                            <a href="<?= base_url('recruiter/jobs') ?>" class="nav-link">Jobs</a>
                            <ul class="dropdown">
                                <li><a href="<?= base_url('recruiter/jobs') ?>">My Jobs</a></li>
                                <li><a href="<?= base_url('recruiter/post_job') ?>">Post a Job</a></li>
                            </ul>
                        </li>
                        <li><a href="<?= base_url('recruiter/candidates') ?>" class="nav-link">Candidates</a></li>
                        <li><a href="<?= base_url('recruiter/slots') ?>" class="nav-link">Interview Slots</a></li>
                    </ul>
                </nav>
                <div class="right-cta-menu text-right d-flex justify-content-end align-items-center col-6 col-xl-3">
                    <div class="ml-auto d-flex align-items-center">
                        <a href="<?= base_url('notifications') ?>" class="recruiter-notification-link d-none d-lg-inline-flex <?= $recruiterUnreadNotificationCount > 0 ? 'has-unread' : '' ?>" title="Notifications" aria-label="Notifications">
                            <span class="icon-bell" style="font-size: 18px; line-height: 1;"></span>
                            <?php if ($recruiterUnreadNotificationCount > 0): ?>
                                <span class="recruiter-notification-badge js-notification-badge" data-unread-count="<?= $recruiterUnreadNotificationCount ?>"><?= $recruiterUnreadNotificationCount > 99 ? '99+' : $recruiterUnreadNotificationCount ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="d-none d-lg-inline-block recruiter-avatar-menu" id="recruiterAvatarMenu">
                            <button type="button" class="recruiter-avatar-btn" id="recruiterAvatarBtn" aria-haspopup="true" aria-expanded="false" title="<?= esc($recruiterName) ?>">
                                <?= esc($recruiterInitial) ?>
                            </button>
                            <div class="recruiter-avatar-dropdown" id="recruiterAvatarDropdown">
                                <a href="<?= base_url('recruiter/company-profile') ?>"><i class="fas fa-briefcase"></i><span>Company Profile</span></a>
                                <a href="<?= base_url('account/change-password') ?>"><i class="fas fa-lock"></i><span>Change Password</span></a>
                                <a href="<?= base_url('logout') ?>"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                            </div>
                        </div>
                    </div>
                    <a href="<?= base_url('notifications') ?>" class="recruiter-mobile-notification-link d-inline-flex d-lg-none <?= $recruiterUnreadNotificationCount > 0 ? 'has-unread' : '' ?>" title="Notifications" aria-label="Notifications">
                        <span class="icon-bell"></span>
                        <?php if ($recruiterUnreadNotificationCount > 0): ?>
                            <span class="recruiter-notification-badge js-notification-badge" data-unread-count="<?= $recruiterUnreadNotificationCount ?>"><?= $recruiterUnreadNotificationCount > 99 ? '99+' : $recruiterUnreadNotificationCount ?></span>
                        <?php endif; ?>
                    </a>
                    <button type="button" class="recruiter-mobile-hamburger d-inline-flex d-xl-none" id="hmDrawerToggle" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
                </div>
            </div>
        </div>
    </header>

    <?php
    $showHero = $showHero ?? false;
    $heroTitle = esc($title ?? 'Recruiter');
    ?>
    <?php if ($showHero): ?>
    <section class="section-hero overlay inner-page bg-image recruiter-global-hero" style="background-image: url('<?= base_url('jobboard/images/hero_1.jpg') ?>');" id="home-section">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h1 class="text-white font-weight-bold"><?= $heroTitle ?></h1>
                    <div class="custom-breadcrumbs">
                        <a href="<?= base_url('recruiter/dashboard') ?>">Home</a>
                        <span class="mx-2 slash">/</span>
                        <span class="text-white"><strong><?= $heroTitle ?></strong></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <main>
