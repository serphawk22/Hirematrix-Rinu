<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="google-adsense-account" content="ca-pub-5380525657635231">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= esc($title ?? 'Admin Portal') ?> - HireMatrix</title>
    <link rel="icon" type="image/png" href="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/theme-colors.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/custom-bs.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/fontawesome-all.min.css') ?>">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5380525657635231"
     crossorigin="anonymous"></script>
</head>
<style>
    /* Highlight active navigation links */
    .admin-portal .site-navigation .nav-link.active {
        color: #2446c0 !important; /* Your primary brand blue */
        font-weight: 700 !important;
        border-bottom: 2px solid #2446c0;
    }
</style>
<body class="bg-light hirematrix-app admin-portal d-flex flex-column min-vh-100">

<header class="site-navbar shadow-sm mb-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="site-logo col-6 col-xl-2">
                <a href="<?= base_url('admin/dashboard') ?>" class="d-inline-flex align-items-center">
                    <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="Logo" style="height: 30px; width: auto; margin-right: 8px;">
                    <span style="text-transform: none; color: var(--foreground); font-weight: 700;">Hirematrix</span>
                </a>
            </div>
            <nav class="mx-auto site-navigation col-xl-7 d-none d-xl-block text-center">
                <ul class="site-menu list-unstyled d-flex justify-content-center mb-0">
                    <li><a href="<?= base_url('admin/dashboard') ?>" class="nav-link px-3 <?= url_is('admin/dashboard*') ? 'active' : '' ?>">Dashboard</a></li>
                    <li><a href="<?= base_url('admin/jobs') ?>" class="nav-link px-3 <?= url_is('admin/jobs*') ? 'active' : '' ?>">Jobs</a></li>
                    <li><a href="<?= base_url('admin/users') ?>" class="nav-link px-3 <?= url_is('admin/users*') ? 'active' : '' ?>">Users</a></li>
                    <li><a href="<?= base_url('admin/companies') ?>" class="nav-link px-3 <?= (url_is('admin/companies*') || url_is('admin/company/*')) ? 'active' : '' ?>">Companies</a></li>
                    <li><a href="<?= base_url('admin/company-ats-mappings') ?>" class="nav-link px-3 <?= url_is('admin/company-ats-mappings*') ? 'active' : '' ?>">Company ATS</a></li>
                    <li><a href="<?= base_url('admin/subscriptions') ?>" class="nav-link px-3 <?= (url_is('admin/subscriptions*') || url_is('admin/subscription/*')) ? 'active' : '' ?>">Subscriptions</a></li>
                    <li><a href="<?= base_url('admin/blogs') ?>" class="nav-link px-3 <?= url_is('admin/blogs*') ? 'active' : '' ?>">Blogs</a></li>
                    <li><a href="<?= base_url('admin/feedback') ?>" class="nav-link px-3 <?= url_is('admin/feedback*') ? 'active' : '' ?>">Feedback</a></li>
                </ul>
            </nav>
            <div class="right-cta-menu col-6 col-xl-3 text-end">
                <div class="d-flex justify-content-end align-items-center gap-3">
                    <span class="text-muted small d-none d-md-inline">System Admin</span>
                    <a href="<?= base_url('logout') ?>" class="btn btn-outline-danger btn-sm rounded-pill">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container py-2 flex-grow-1">