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
    <link rel="stylesheet" href="<?= base_url('jobboard/css/admin-pages.css?v=' . @filemtime(FCPATH . 'jobboard/css/admin-pages.css')) ?>">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5380525657635231"
     crossorigin="anonymous"></script>
</head>
<style>
    .admin-portal .admin-table-scroll {
        overflow-x: auto;
        overflow-y: auto;
    }

    .admin-portal .admin-table {
        border-collapse: collapse;
        table-layout: fixed;
        width: 100%;
    }

    .admin-portal .admin-table th,
    .admin-portal .admin-table td {
        display: table-cell;
        padding: 14px 16px;
        vertical-align: middle;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .admin-portal .admin-table thead {
        display: table-header-group;
    }

    .admin-portal .admin-table tbody {
        display: table-row-group;
    }

    .admin-portal .admin-table tr {
        display: table-row;
    }

    .admin-portal .admin-table thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #f8fafc;
        color: #111827;
        font-weight: 700;
    }

    .admin-portal .admin-jobs-table {
        min-width: 980px;
    }
</style>
<body class="bg-light hirematrix-app admin-portal">
<?php
$adminNav = [
    ['url' => 'admin/dashboard', 'label' => 'Dashboard', 'icon' => 'fas fa-chart-pie', 'active' => url_is('admin/dashboard*')],
    ['url' => 'admin/jobs', 'label' => 'Jobs', 'icon' => 'fas fa-briefcase', 'active' => url_is('admin/jobs*')],
    ['url' => 'admin/users', 'label' => 'Users', 'icon' => 'fas fa-users', 'active' => url_is('admin/users*')],
    ['url' => 'admin/companies', 'label' => 'Companies', 'icon' => 'fas fa-building', 'active' => url_is('admin/companies*') || url_is('admin/company/*')],
    ['url' => 'admin/company-ats-mappings', 'label' => 'Company ATS', 'icon' => 'fas fa-sitemap', 'active' => url_is('admin/company-ats-mappings*')],
    ['url' => 'admin/subscriptions', 'label' => 'Subscriptions', 'icon' => 'fas fa-credit-card', 'active' => url_is('admin/subscriptions*') || url_is('admin/subscription/*')],
    ['url' => 'admin/blogs', 'label' => 'Blogs', 'icon' => 'fas fa-newspaper', 'active' => url_is('admin/blogs*')],
    ['url' => 'admin/feedback', 'label' => 'Feedback', 'icon' => 'fas fa-comments', 'active' => url_is('admin/feedback*')],
];
?>
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
        <div class="admin-sidebar-brand">
            <a href="<?= base_url('admin/dashboard') ?>">
                <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix">
                <span>HireMatrix</span>
            </a>
            <button type="button" class="admin-sidebar-close" id="adminSidebarClose" aria-label="Close navigation"><i class="fas fa-times"></i></button>
        </div>
        <div class="admin-sidebar-label">ADMIN CONTROL</div>
        <nav class="admin-sidebar-nav">
            <?php foreach ($adminNav as $item): ?>
                <a href="<?= base_url($item['url']) ?>" class="admin-sidebar-link <?= $item['active'] ? 'active' : '' ?>" <?= $item['active'] ? 'aria-current="page"' : '' ?>>
                    <span class="admin-sidebar-icon"><i class="<?= esc($item['icon']) ?>"></i></span>
                    <span><?= esc($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar-footer">
            <div class="admin-sidebar-user">
                <span class="admin-sidebar-avatar">A</span>
                <span><strong>System Admin</strong><small>Administrator</small></span>
            </div>
            <a href="<?= base_url('admin/logout') ?>" class="admin-sidebar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>
    <button type="button" class="admin-sidebar-overlay" id="adminSidebarOverlay" aria-label="Close navigation"></button>
    <div class="admin-main">
        <!-- <header class="admin-topbar">
            <button type="button" class="admin-menu-toggle" id="adminMenuToggle" aria-controls="adminSidebar" aria-expanded="false"><i class="fas fa-bars"></i></button>
            <div>
                <strong>Admin Portal</strong>
                <span>HireMatrix control center</span>
            </div>
        </header> -->
        <main class="admin-content">
