<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="google-adsense-account" content="ca-pub-5380525657635231">
    <title><?= esc($title ?? 'Recruiter Portal') ?></title>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5380525657635231"
     crossorigin="anonymous"></script>
    <script>
        (function () {
            try {
                var darkTheme = localStorage.getItem('hm_theme') === 'dark'
                    || localStorage.getItem('recruiter-theme') === 'dark';
                if (darkTheme) {
                    document.documentElement.classList.add('hm-dark-preload');
                }
            } catch (error) {}
        })();
    </script>
    <link rel="icon" type="image/png" href="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/custom-bs.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/icomoon/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/line-icons/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.min.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.min.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/fontawesome-all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/responsive.min.css?v=' . @filemtime(FCPATH . 'jobboard/css/responsive.min.css')) ?>">
    <?php foreach ((array) ($pageStyles ?? []) as $pageStyle): ?>
        <link rel="stylesheet" href="<?= esc($pageStyle, 'attr') ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= base_url('jobboard/css/portal-ui-system.css?v=' . @filemtime(FCPATH . 'jobboard/css/portal-ui-system.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/recruiter-pages.min.css?v=' . @filemtime(FCPATH . 'jobboard/css/recruiter-pages.min.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/recruiter-notifications.css?v=' . @filemtime(FCPATH . 'jobboard/css/recruiter-notifications.css')) ?>">

</head>

<body id="top" class="hirematrix-app recruiter-jobboard">
<div id="overlayer"></div>
<div class="loader">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>

<?php
$recruiterId = (int)(session()->get('user_id') ?? 0);
$recruiterUnreadNotificationCount = $recruiterId > 0
    ? (int) model('NotificationModel')->getUnreadCount($recruiterId)
    : 0;
$currentUri = uri_string();
$isActive = fn(string $path) => str_starts_with($currentUri, $path) ? 'active' : '';
?>

<div class="hm-sb-overlay" id="hmOverlay"></div>

<div class="site-wrap">

    <!-- ═══════════ SIDEBAR ═══════════ -->
    <aside class="hm-sidebar" id="hmSidebar" aria-label="Recruiter navigation">

        <!-- LOGO + BRAND = collapse toggle -->
        <div class="hm-sb-head" id="hmSbToggle" role="button" tabindex="0"
             aria-label="Toggle sidebar" title="Toggle sidebar">
            <div class="hm-sb-logo">
                <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix logo">
            </div>
            <span class="hm-sb-brand">
                Hire<span class="recruiter-logo-accent">Matrix</span>
            </span> 
        </div>

        <!-- NAV -->
        <nav class="hm-sb-body"> 
            <button class="hm-sb-item hm-sb-group-toggle sb-open" id="hmOverviewBtn" aria-expanded="true">
                <i class="fas fa-th-large sb-icon"></i>
                <span class="sb-label">OVERVIEW</span>
                <i class="fas fa-chevron-down sb-arrow"></i>
                <span class="sb-tooltip">Overview</span>
            </button>
            <div class="hm-sb-sub sb-open" id="hmOverviewSub">
                <a href="<?= base_url('recruiter/dashboard') ?>"
                   class="hm-sb-subitem <?= $isActive('recruiter/dashboard') ?>">
                    <i class="fas fa-th-large sb-icon"></i>
                    <span class="sb-label">Dashboard</span>
                </a>
                <a href="<?= base_url('notifications') ?>"
                   class="hm-sb-subitem <?= $isActive('notifications') ?>">
                    <i class="fas fa-bell sb-icon"></i>
                    <span class="sb-label">Notifications</span>
                    <?php if ($recruiterUnreadNotificationCount > 0): ?>
                        <span class="sb-badge js-recruiter-notification-badge">
                            <?= $recruiterUnreadNotificationCount > 99 ? '99+' : $recruiterUnreadNotificationCount ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>

            <button class="hm-sb-item hm-sb-group-toggle sb-open <?= ($isActive('recruiter/jobs') || $isActive('recruiter/post_job')) ? 'active' : '' ?>"
                    id="hmJobsBtn" aria-expanded="true">
                <i class="fas fa-briefcase sb-icon"></i>
                <span class="sb-label">JOBS</span>
                <i class="fas fa-chevron-down sb-arrow"></i>
                <span class="sb-tooltip">Jobs</span>
            </button>
            <div class="hm-sb-sub sb-open"
                 id="hmJobsSub">
                <a href="<?= base_url('recruiter/jobs') ?>"
                   class="hm-sb-subitem <?= ($isActive('recruiter/jobs') && !$isActive('recruiter/post_job')) ? 'active' : '' ?>">
                    <i class="fas fa-briefcase sb-icon"></i>
                    <span class="sb-label">My Jobs</span>
                </a>
                <a href="<?= base_url('recruiter/post_job') ?>"
                   class="hm-sb-subitem <?= $isActive('recruiter/post_job') ? 'active' : '' ?>">
                    <i class="fas fa-plus-square sb-icon"></i>
                    <span class="sb-label">Post a Job</span>
                </a>
            </div>

            <button class="hm-sb-item hm-sb-group-toggle sb-open <?= ($isActive('recruiter/slots')) ? 'active' : '' ?>"
                    id="hmSlotsBtn" aria-expanded="true">
                <i class="fas fa-calendar-alt sb-icon"></i>
                <span class="sb-label">INTERVIEW SLOTS</span>
                <i class="fas fa-chevron-down sb-arrow"></i>
                <span class="sb-tooltip">Slots</span>
            </button>
            <div class="hm-sb-sub sb-open" id="hmSlotsSub">
                <a href="<?= base_url('recruiter/slots') ?>"
                   class="hm-sb-subitem <?= ($currentUri === 'recruiter/slots') ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check sb-icon"></i>
                    <span class="sb-label">Manage Slots</span>
                </a>
                <a href="<?= base_url('recruiter/slots/create') ?>"
                   class="hm-sb-subitem <?= $isActive('recruiter/slots/create') ? 'active' : '' ?>">
                    <i class="fas fa-calendar-plus sb-icon"></i>
                    <span class="sb-label">Create Slot</span>
                </a>
                <a href="<?= base_url('recruiter/slots/bookings') ?>"
                   class="hm-sb-subitem <?= str_contains($currentUri, 'bookings') ? 'active' : '' ?>">
                    <i class="fas fa-eye sb-icon"></i>
                    <span class="sb-label">View Bookings</span>
                </a>
            </div>

            <button class="hm-sb-item hm-sb-group-toggle sb-open" id="hmToolsBtn" aria-expanded="true">
                <i class="fas fa-cog sb-icon"></i>
                <span class="sb-label">TOOLS</span>
                <i class="fas fa-chevron-down sb-arrow"></i>
                <span class="sb-tooltip">Tools</span>
            </button>
            <div class="hm-sb-sub sb-open" id="hmToolsSub">
                <a href="<?= base_url('recruiter/dashboard/export-excel') ?>"
                   class="hm-sb-subitem <?= $isActive('recruiter/dashboard/export-excel') ?>">
                    <i class="fas fa-file-excel sb-icon"></i>
                    <span class="sb-label">Export Data</span>
                </a>
                 <a href="<?= base_url('recruiter/reports') ?>"
                   class="hm-sb-subitem <?= $isActive('recruiter/reports') ?>">
                    <i class="fas fa-file-excel sb-icon"></i>
                    <span class="sb-label">Export Jobs</span>
                </a>
                <a href="<?= base_url('recruiter/settings') ?>"
                   class="hm-sb-subitem <?= $isActive('recruiter/settings') ?>">
                    <i class="fas fa-cog sb-icon"></i>
                    <span class="sb-label">Settings</span>
                </a>
            </div>

            <!-- ▼▼▼ NEW: RESDEX group ▼▼▼ -->
            <button class="hm-sb-item hm-sb-group-toggle sb-open <?= $isActive('recruiter/resdex') ? 'active' : '' ?>"
                    id="hmResdexBtn" aria-expanded="true">
                <i class="fas fa-search sb-icon"></i>
                <span class="sb-label">RESUME DATABASE</span>
                <i class="fas fa-chevron-down sb-arrow"></i>
                <span class="sb-tooltip">Resume Database</span>
            </button>
            <div class="hm-sb-sub sb-open" id="hmResdexSub">
                <a href="<?= base_url('recruiter/resdex') ?>"
                   class="hm-sb-subitem <?= ($isActive('recruiter/resdex') && !$isActive('recruiter/resdex/saved-searches') && !$isActive('recruiter/resdex/folders')) ? 'active' : '' ?>">
                    <i class="fas fa-search sb-icon"></i>
                    <span class="sb-label">Search Resumes</span>
                </a>
                <a href="<?= base_url('recruiter/resdex/saved-searches') ?>"
                   class="hm-sb-subitem <?= $isActive('recruiter/resdex/saved-searches') ?>">
                    <i class="fas fa-bookmark sb-icon"></i>
                    <span class="sb-label">Manage Searches</span>
                </a>
                <a href="<?= base_url('recruiter/resdex/folders') ?>"
                   class="hm-sb-subitem <?= $isActive('recruiter/resdex/folders') ?>">
                    <i class="fas fa-folder sb-icon"></i>
                    <span class="sb-label">Manage Folders</span>
                </a>
            </div>
            <!-- ▲▲▲ END RESDEX group ▲▲▲ -->

        </nav>

        <!-- FOOTER: profile submenu + profile card -->
        <div class="hm-sb-foot">

            <!-- opens upward above the card -->
            <div class="hm-sb-profile-sub" id="hmProfileSub">
                <a href="<?= base_url('recruiter/company-profile') ?>"
                   class="<?= $isActive('recruiter/company-profile') ?>">
                    <i class="fas fa-building"></i> Company Profile
                </a>
                <a href="<?= base_url('logout') ?>" class="prof-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <!-- profile card toggle -->
            <div class="hm-sb-profile" id="hmProfileCard" role="button" tabindex="0" aria-expanded="false">
                <div class="hm-sb-profile-avatar">
                    <?= strtoupper(substr(session()->get('user_name') ?? 'R', 0, 1)) ?>
                </div>
                <div class="hm-sb-profile-info">
                    <div class="hm-sb-profile-name"><?= esc(session()->get('user_name') ?? 'Recruiter') ?></div>
                    <div class="hm-sb-profile-role">Recruiter</div>
                </div>
                <i class="fas fa-chevron-up hm-sb-profile-chevron"></i>
                <span class="sb-tooltip"><?= esc(session()->get('user_name') ?? 'Recruiter') ?></span>
            </div>

        </div>

    </aside>
    <!-- ═══════════ END SIDEBAR ═══════════ -->

    <!-- ═══════════ MAIN ═══════════ -->
    <div class="hm-main" id="hmMain"> 

        <div class="hm-topbar" aria-label="Recruiter mobile navigation">
            <button class="hm-mobile-toggle" id="hmMobileToggle" type="button" aria-label="Open navigation">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
            <span class="hm-topbar-title">Recruiter Portal</span>
        </div>

        <div class="hm-page-content">
        <main>

<script>
(function () {
    'use strict';
 
    var sidebar     = document.getElementById('hmSidebar');
    var toggleArea  = document.getElementById('hmSbToggle');
    var mobileBtn   = document.getElementById('hmMobileToggle');
    var overlay     = document.getElementById('hmOverlay');
    var overviewBtn = document.getElementById('hmOverviewBtn');
    var overviewSub = document.getElementById('hmOverviewSub');
    var jobsBtn     = document.getElementById('hmJobsBtn');
    var jobsSub     = document.getElementById('hmJobsSub');
    var slotsBtn    = document.getElementById('hmSlotsBtn');
    var slotsSub    = document.getElementById('hmSlotsSub');
    var toolsBtn    = document.getElementById('hmToolsBtn');
    var toolsSub    = document.getElementById('hmToolsSub');
    var resdexBtn   = document.getElementById('hmResdexBtn');
    var resdexSub   = document.getElementById('hmResdexSub');
    var profileCard = document.getElementById('hmProfileCard');
    var profileSub  = document.getElementById('hmProfileSub');
 
    var KEY       = 'hm_sb_icon_rail';
    var collapsed = localStorage.getItem(KEY) !== '0';
 
    function applyCollapsed() {
        sidebar.classList.toggle('sb-collapsed', collapsed);
        document.body.classList.toggle('sb-collapsed-body', collapsed);
    }

    applyCollapsed();

 
    toggleArea.addEventListener('click', function () {
        if (window.innerWidth <= 991) return;
        collapsed = !collapsed;
        localStorage.setItem(KEY, collapsed ? '1' : '0');
        applyCollapsed();
    });
    toggleArea.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleArea.click(); }
    });
 
    function setProfileOpen(open) {
        if (!profileCard || !profileSub) return;
        profileSub.classList.toggle('prof-open', open);
        profileCard.classList.toggle('prof-open', open);
        profileCard.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    profileCard && profileCard.addEventListener('click', function () {
        setProfileOpen(!profileSub.classList.contains('prof-open'));
    });
    profileCard && profileCard.addEventListener('mouseenter', function () {
        if (window.innerWidth <= 991) return;
        setProfileOpen(true);
    });
    profileCard && profileCard.addEventListener('focus', function () {
        if (window.innerWidth <= 991) return;
        setProfileOpen(true);
    });
    profileSub && profileSub.addEventListener('mouseenter', function () {
        if (window.innerWidth <= 991) return;
        setProfileOpen(true);
    });
    profileSub && profileSub.addEventListener('mouseleave', function () {
        if (window.innerWidth <= 991) return;
        setProfileOpen(false);
    });
    profileCard && profileCard.addEventListener('mouseleave', function () {
        if (window.innerWidth <= 991) return;
        window.setTimeout(function () {
            if (!profileSub.matches(':hover') && !profileCard.matches(':hover')) {
                setProfileOpen(false);
            }
        }, 80);
    });
    profileCard && profileCard.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); profileCard.click(); }
    });
 
    function openMobile() {
        sidebar.classList.add('sb-mobile-open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeMobile() {
        sidebar.classList.remove('sb-mobile-open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    mobileBtn && mobileBtn.addEventListener('click', openMobile);
    overlay.addEventListener('click', closeMobile);
 
    var navGroups = [
        { button: overviewBtn, submenu: overviewSub },
        { button: jobsBtn, submenu: jobsSub },
        { button: slotsBtn, submenu: slotsSub },
        { button: toolsBtn, submenu: toolsSub },
        { button: resdexBtn, submenu: resdexSub }
    ].filter(function (group) {
        return group.button && group.submenu;
    });

    function setRecruiterSubnavOpen(activeGroup) {
        navGroups.forEach(function (group) {
            var isOpen = group === activeGroup;
            group.button.classList.toggle('sb-open', isOpen);
            group.submenu.classList.toggle('sb-open', isOpen);
            group.button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    function findInitialSubnavGroup() {
        for (var i = 0; i < navGroups.length; i++) {
            if (
                navGroups[i].button.classList.contains('active') ||
                navGroups[i].submenu.querySelector('.active')
            ) {
                return navGroups[i];
            }
        }
        return navGroups[0] || null;
    }

    navGroups.forEach(function (group) {
        group.button.addEventListener('click', function (e) {
            e.preventDefault();
            setRecruiterSubnavOpen(group);
        });
        group.button.addEventListener('mouseenter', function () {
            if (window.innerWidth <= 991) return;
            setRecruiterSubnavOpen(group);
        });
        group.button.addEventListener('focus', function () {
            if (window.innerWidth <= 991) return;
            setRecruiterSubnavOpen(group);
        });
    });

    var initialSubnavGroup = findInitialSubnavGroup();
    if (initialSubnavGroup) {
        setRecruiterSubnavOpen(initialSubnavGroup);
    }
 
})();
</script><script>
 (function () {
    var THEME_KEY = 'hm_theme';
    var pill = document.getElementById('hmThemePill');

    function applyTheme(dark) {
        document.body.classList.toggle('dark', dark);
        if (pill) pill.setAttribute('aria-checked', dark);
    }

    applyTheme(localStorage.getItem(THEME_KEY) === 'dark');

    window.hmToggleTheme = function () {
        var isDark = document.body.classList.contains('dark');
        localStorage.setItem(THEME_KEY, isDark ? 'light' : 'dark');
        applyTheme(!isDark);
    };
})();
</script>
