<?php
$platformStats = $platformStats ?? [];
$featuredJobs = $featuredJobs ?? [];

$jobsPostedCount = (int) ($platformStats['jobs_posted'] ?? count($featuredJobs));
$candidateCount = (int) ($platformStats['candidates'] ?? 0);
$interviewCount = (int) ($platformStats['interviews_booked'] ?? 0);
$recruiterCount = (int) ($platformStats['recruiters'] ?? 0);

$jobIconSet = [
    'developer' => 'fas fa-code',
    'engineer' => 'fas fa-cogs',
    'designer' => 'fas fa-palette',
    'manager' => 'fas fa-chart-line',
    'data' => 'fas fa-database',
    'marketing' => 'fas fa-bullhorn',
    'product' => 'fas fa-briefcase',
];

$pickJobIcon = static function (string $title) use ($jobIconSet): string {
    $needle = strtolower($title);
    foreach ($jobIconSet as $key => $icon) {
        if (str_contains($needle, $key)) {
            return $icon;
        }
    }

    return 'fas fa-briefcase';
};

$formatAge = static function ($value): string {
    if ($value === null || $value === '') {
        return 'Recently';
    }

    $date = strtotime((string) $value);
    if ($date === false) {
        return 'Recently';
    }

    return date('M d, Y', $date);
};

$formatCount = static function (int $count, string $fallback): string {
    if ($count <= 0) {
        return $fallback;
    }

    if ($count >= 1000) {
        return number_format($count / 1000, 1) . 'k+';
    }

    return number_format($count) . '+';
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>HireMatrix | AI Job Portal</title>
    <meta name="description" content="HireMatrix connects candidates and recruiters with AI job matching, resume tools, interviews, and career transition guidance.">
    <link rel="icon" type="image/png" href="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>">

    <link rel="stylesheet" href="<?= base_url('jobboard/css/theme-colors.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/custom-bs.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/jquery.fancybox.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/bootstrap-select.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/icomoon/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/line-icons/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/owl.carousel.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/animate.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/fontawesome-all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/responsive.css?v=' . @filemtime(FCPATH . 'jobboard/css/responsive.css')) ?>">
    <style>
        :root {
            --hm-primary: #1FB7B5;
            --hm-primary-dark: #0D8A90;
            --hm-secondary: #53B86C;
            --hm-accent: #B5D84E;
            --hm-ink: #0F172A;
            --hm-muted: #5D7083;
            --hm-bg: #FFFFFF;
            --hm-surface: #FFFFFF;
            --hm-soft: #F1FAF9;
            --hm-line: #DDECEF;
            --hm-radius: 8px;
        }

        body.landing-redesign {
            background: var(--hm-bg);
            color: var(--hm-ink);
            overflow-x: hidden;
        }

        body.landing-redesign::before {
            display: none !important;
        }

        .landing-redesign .site-wrap {
            min-height: 100vh;
            background: var(--hm-bg);
        }

        .hm-landing-rail {
            width: min(1180px, calc(100vw - 40px));
            margin: 0 auto;
        }

        .hm-hero {
            padding: 28px 0 38px;
        }

        .hm-hero-nav {
            position: relative;
            z-index: 4;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
            animation: hmFadeUp 0.65s ease both;
        }

        .hm-brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--hm-ink);
            font-weight: 850;
            text-decoration: none;
        }

        .hm-brand-mark img {
            width: 40px;
            height: 40px;
            border-radius: 0;
            object-fit: contain;
            background: transparent;
            border: 0;
        }

        .hm-hero-nav-actions {
            display: flex;
            justify-content: flex-end;
        }

        .hm-hero-nav-actions .hm-cta-primary,
        .hm-hero-nav-actions .hm-cta-secondary {
            min-height: 42px;
            padding: 0 16px;
        }

        .hm-hero-panel {
            position: relative;
            overflow: hidden;
            min-height: 640px;
            border: 1px solid rgba(216, 231, 239, 0.95);
            border-radius: var(--hm-radius);
            background:
                linear-gradient(90deg, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 0.93) 48%, rgba(241, 250, 249, 0.88) 100%),
                url("<?= base_url('jobboard/images/hero_1.jpg') ?>");
            background-size: cover;
            background-position: center right;
            box-shadow: 0 20px 48px rgba(15, 23, 42, 0.08);
            animation: hmFadeUp 0.72s ease 0.08s both;
        }

        .hm-hero-panel::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(13, 138, 144, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(13, 138, 144, 0.08) 1px, transparent 1px);
            background-size: 42px 42px;
            opacity: 0.38;
            pointer-events: none;
        }

        .hm-hero-content {
            position: relative;
            z-index: 2;
            display: block;
            min-height: 640px;
            padding: 44px;
        }

        .hm-hero-content > div:first-child {
            position: relative;
            z-index: 3;
            max-width: 710px;
            animation: hmFadeUp 0.78s ease 0.18s both;
        }

        .hm-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 30px;
            padding: 5px 10px;
            border: 1px solid rgba(31, 183, 181, 0.25);
            border-radius: 999px;
            background: rgba(234, 248, 247, 0.92);
            color: var(--hm-primary-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .hm-hero h1 {
            max-width: 690px;
            margin: 18px 0 16px;
            color: var(--hm-ink);
            font-size: 56px;
            font-weight: 850;
            letter-spacing: 0;
            line-height: 1.03;
        }

        .hm-hero h1 span {
            color: var(--hm-primary-dark);
        }

        .hm-hero-copy {
            max-width: 640px;
            margin: 0;
            color: var(--hm-muted);
            font-size: 17px;
            line-height: 1.7;
        }

        .hm-search-card {
            margin-top: 28px;
            padding: 12px;
            max-width: 760px;
            border: 1px solid rgba(216, 231, 239, 0.98);
            border-radius: var(--hm-radius);
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
            animation: hmFadeUp 0.78s ease 0.28s both;
        }

        .hm-search-form {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.8fr) auto;
            gap: 10px;
            align-items: stretch;
        }

        .hm-search-field {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            padding: 0 14px;
            border-radius: 6px;
            background: #F2FBFA;
            border: 1px solid rgba(31, 183, 181, 0.14);
        }

        .hm-search-field i {
            color: var(--hm-primary-dark);
        }

        .hm-search-field input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--hm-ink);
            font-size: 14px;
        }

        .hm-search-submit,
        .hm-cta-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 52px;
            padding: 0 20px;
            border: 1px solid var(--hm-primary);
            border-radius: 6px;
            background: var(--hm-primary);
            color: #fff !important;
            font-weight: 800;
            text-decoration: none !important;
            white-space: nowrap;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .hm-cta-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 52px;
            padding: 0 20px;
            border: 1px solid rgba(31, 183, 181, 0.48);
            border-radius: 6px;
            background: #fff;
            color: var(--hm-primary-dark) !important;
            font-weight: 800;
            text-decoration: none !important;
            white-space: nowrap;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .hm-search-submit:hover,
        .hm-cta-primary:hover,
        .hm-cta-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
        }

        .hm-quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .hm-quick-links a {
            display: inline-flex;
            align-items: center;
            min-height: 32px;
            padding: 0 12px;
            border: 1px solid rgba(31, 183, 181, 0.2);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.86);
            color: var(--hm-primary-dark);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .hm-hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .hm-product-scene {
            position: absolute;
            right: 44px;
            bottom: 44px;
            z-index: 2;
            display: grid;
            width: min(520px, 42vw);
            animation: hmFloatScene 5.8s ease-in-out infinite;
        }

        .hm-dashboard-card {
            position: relative;
            border: 1px solid rgba(216, 231, 239, 0.95);
            border-radius: var(--hm-radius);
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.14);
            overflow: hidden;
            transform-origin: center;
        }

        .hm-dashboard-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            border-bottom: 1px solid var(--hm-line);
            background: #F8FCFC;
        }

        .hm-window-dots {
            display: inline-flex;
            gap: 6px;
        }

        .hm-window-dots span {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #CBD5E1;
        }

        .hm-window-dots span:nth-child(2) {
            background: var(--hm-primary);
        }

        .hm-window-dots span:nth-child(3) {
            background: var(--hm-accent);
        }

        .hm-dashboard-body {
            display: grid;
            gap: 14px;
            padding: 16px;
        }

        .hm-match-card,
        .hm-job-row,
        .hm-pipeline-card {
            border: 1px solid var(--hm-line);
            border-radius: 8px;
            background: #fff;
        }

        .hm-match-card {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 14px;
        }

        .hm-avatar-stack {
            position: relative;
            width: 58px;
            height: 44px;
        }

        .hm-avatar-stack img {
            position: absolute;
            width: 38px;
            height: 38px;
            border: 2px solid #fff;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
        }

        .hm-avatar-stack img:nth-child(2) {
            left: 20px;
        }

        .hm-match-card strong,
        .hm-pipeline-card strong,
        .hm-job-row strong {
            display: block;
            color: var(--hm-ink);
            font-size: 15px;
            line-height: 1.2;
        }

        .hm-match-card span,
        .hm-pipeline-card span,
        .hm-job-row span {
            color: var(--hm-muted);
            font-size: 12px;
        }

        .hm-score-ring {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: conic-gradient(var(--hm-primary) 88%, #E6EEF2 0);
            color: var(--hm-primary-dark);
            font-size: 13px;
            font-weight: 850;
        }

        .hm-score-ring::before {
            content: "";
            position: absolute;
        }

        .hm-dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .hm-pipeline-card {
            padding: 14px;
        }

        .hm-bars {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }

        .hm-bars span {
            display: block;
            height: 8px;
            border-radius: 999px;
            background: #E8F1F4;
            overflow: hidden;
        }

        .hm-bars span::before {
            content: "";
            display: block;
            height: 100%;
            width: var(--w, 72%);
            border-radius: inherit;
            background: var(--hm-primary);
            transform-origin: left;
            animation: hmBarGrow 1.1s ease both;
        }

        .hm-job-row {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 12px;
        }

        .hm-job-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: var(--hm-soft);
            color: var(--hm-primary-dark);
        }

        .hm-job-pill {
            display: inline-flex;
            min-height: 28px;
            align-items: center;
            padding: 0 10px;
            border-radius: 999px;
            background: #F2FBFA;
            color: var(--hm-primary-dark);
            font-size: 12px;
            font-weight: 800;
        }

        .hm-stats {
            padding: 22px 0 28px;
        }

        .hm-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0;
            border-top: 1px solid var(--hm-line);
            border-bottom: 1px solid var(--hm-line);
        }

        .hm-stat-card {
            min-height: 96px;
            padding: 22px 24px;
        }

        .hm-stat-card + .hm-stat-card {
            border-left: 1px solid var(--hm-line);
        }

        .hm-stat-card strong {
            display: block;
            color: var(--hm-ink);
            font-size: 30px;
            font-weight: 850;
            line-height: 1;
        }

        .hm-stat-card span {
            display: block;
            margin-top: 8px;
            color: var(--hm-muted);
            font-size: 14px;
        }

        .hm-section {
            padding: 54px 0;
        }

        .hm-section-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 24px;
            margin-bottom: 24px;
        }

        .hm-section-title {
            margin: 12px 0 0;
            color: var(--hm-ink);
            font-size: 34px;
            line-height: 1.12;
            font-weight: 850;
            letter-spacing: 0;
        }

        .hm-section-text {
            max-width: 610px;
            margin: 10px 0 0;
            color: var(--hm-muted);
            line-height: 1.7;
        }

        .hm-cta-panel {
            border: 1px solid var(--hm-line);
            border-radius: var(--hm-radius);
            background: var(--hm-surface);
            box-shadow: none;
            transition: transform 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
        }

        .hm-job-card:hover {
            background: rgba(234, 248, 247, 0.48);
        }

        .hm-story-board {
            display: grid;
            gap: 0;
            border-top: 1px solid var(--hm-line);
            border-bottom: 1px solid var(--hm-line);
        }

        .hm-story-row {
            display: grid;
            grid-template-columns: 180px minmax(0, 1fr) minmax(240px, 0.72fr);
            gap: 28px;
            align-items: center;
            padding: 28px 0;
        }

        .hm-story-row + .hm-story-row {
            border-top: 1px solid var(--hm-line);
        }

        .hm-story-label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--hm-primary-dark);
            font-size: 13px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .hm-story-label i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--hm-soft);
        }

        .hm-story-row h3,
        .hm-job-card h3 {
            margin: 0 0 8px;
            color: var(--hm-ink);
            font-size: 18px;
            font-weight: 850;
            line-height: 1.25;
        }

        .hm-story-row p,
        .hm-job-card p {
            margin: 0;
            color: var(--hm-muted);
            font-size: 14px;
            line-height: 1.65;
        }

        .hm-story-steps {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .hm-story-steps li {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 11px;
            border-radius: 999px;
            background: #F2FBFA;
            color: var(--hm-primary-dark);
            font-size: 12px;
            font-weight: 800;
        }

        .hm-jobs-grid {
            display: grid;
            grid-template-columns: 1fr;
            border-top: 1px solid var(--hm-line);
            border-bottom: 1px solid var(--hm-line);
        }

        .hm-job-card {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(240px, 0.9fr) auto;
            gap: 18px;
            align-items: center;
            padding: 18px 0;
            transition: background 0.18s ease;
        }

        .hm-job-card + .hm-job-card {
            border-top: 1px solid var(--hm-line);
        }

        .hm-job-card-head {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .hm-job-card h3 {
            margin: 0 0 4px;
        }

        .hm-job-meta,
        .hm-job-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .hm-job-meta span,
        .hm-job-tags span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            background: #F2FBFA;
            color: var(--hm-primary-dark);
            font-size: 12px;
            font-weight: 700;
        }

        .hm-job-link {
            justify-self: end;
            color: var(--hm-primary-dark);
            font-weight: 850;
            text-decoration: none;
            white-space: nowrap;
        }

        .hm-cta-panel {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 20px;
            padding: 34px;
            background: #0F2F34;
            color: #fff;
        }

        .hm-cta-panel h2 {
            margin: 0 0 8px;
            color: #fff;
            font-size: 32px;
            font-weight: 850;
            letter-spacing: 0;
        }

        .hm-cta-panel p {
            margin: 0;
            color: rgba(255, 255, 255, 0.76);
        }

        .hm-cta-panel .hm-cta-secondary {
            background: transparent;
            border-color: rgba(255, 255, 255, 0.36);
            color: #fff !important;
        }

        .hm-cta-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: flex-end;
        }

        .hm-stat-card,
        .hm-story-row,
        .hm-job-card,
        .hm-cta-panel {
            animation: hmFadeUp 0.72s ease both;
        }

        .hm-stat-card:nth-child(2),
        .hm-story-row:nth-child(2),
        .hm-job-card:nth-child(2) {
            animation-delay: 0.06s;
        }

        .hm-stat-card:nth-child(3),
        .hm-story-row:nth-child(3),
        .hm-job-card:nth-child(3) {
            animation-delay: 0.12s;
        }

        .hm-stat-card:nth-child(4),
        .hm-job-card:nth-child(4) {
            animation-delay: 0.18s;
        }

        .hm-job-card:nth-child(5) {
            animation-delay: 0.24s;
        }

        .hm-job-card:nth-child(6) {
            animation-delay: 0.3s;
        }

        @keyframes hmFadeUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes hmFloatScene {
            0%,
            100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes hmBarGrow {
            from {
                transform: scaleX(0);
            }
            to {
                transform: scaleX(1);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 0.001ms !important;
            }
        }

        @media (max-width: 1199.98px) {
            .hm-hero-content {
                min-height: 0;
            }

            .hm-product-scene {
                position: relative;
                right: auto;
                bottom: auto;
                width: min(100%, 720px);
                margin-top: 30px;
            }
        }

        @media (max-width: 991.98px) {
            .hm-hero {
                padding-top: 18px;
            }

            .hm-hero-content {
                padding: 28px;
            }

            .hm-hero h1 {
                font-size: 42px;
            }

            .hm-search-form,
            .hm-stats-grid,
            .hm-story-row,
            .hm-jobs-grid,
            .hm-cta-panel {
                grid-template-columns: 1fr;
            }

            .hm-section-head {
                display: block;
            }

            .hm-stat-card + .hm-stat-card {
                border-left: 0;
                border-top: 1px solid var(--hm-line);
            }

            .hm-job-card {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .hm-story-row {
                gap: 14px;
            }

            .hm-job-link {
                justify-self: start;
            }

            .hm-hero-nav {
                align-items: flex-start;
            }

            .hm-cta-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 575.98px) {
            .hm-landing-rail {
                width: min(100% - 24px, 1180px);
            }

            .hm-hero-content {
                padding: 18px;
            }

            .hm-hero h1 {
                font-size: 34px;
            }

            .hm-hero-nav {
                display: grid;
            }

            .hm-hero-nav-actions {
                justify-content: flex-start;
            }

            .hm-dashboard-grid {
                grid-template-columns: 1fr;
            }

            .hm-match-card,
            .hm-job-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body id="top" class="hirematrix-app landing-page landing-redesign">
<div class="site-wrap">

    <main>
        <section class="hm-hero">
            <div class="hm-landing-rail">
                <div class="hm-hero-nav">
                    <a href="<?= base_url('/') ?>" class="hm-brand-mark" aria-label="HireMatrix home">
                        <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix logo">
                        <span>HireMatrix</span>
                    </a>
                    <div class="hm-hero-nav-actions">
                        <a href="<?= base_url('login') ?>" class="hm-cta-secondary">Sign in</a>
                    </div>
                </div>
                <div class="hm-hero-panel">
                    <div class="hm-hero-content">
                        <div>
                            <span class="hm-kicker"><i class="fas fa-bolt"></i> AI hiring platform</span>
                            <h1>From search to shortlist, <span>move faster</span>.</h1>
                            <p class="hm-hero-copy">
                                HireMatrix helps candidates get ready and helps recruiters find the right fit.
                            </p>

                            <div class="hm-search-card">
                                <form action="<?= base_url('jobs') ?>" method="get" class="hm-search-form">
                                    <label class="hm-search-field">
                                        <i class="fas fa-search" aria-hidden="true"></i>
                                        <input type="text" name="search" placeholder="Job title, skills, or company" autocomplete="off">
                                    </label>
                                    <label class="hm-search-field">
                                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                        <input type="text" name="location" placeholder="Location or remote" autocomplete="off">
                                    </label>
                                    <button type="submit" class="hm-search-submit">Search Jobs</button>
                                </form>
                            </div>

                            <div class="hm-quick-links" aria-label="Popular searches">
                                <a href="<?= base_url('jobs?search=developer') ?>">Developer</a>
                                <a href="<?= base_url('jobs?search=data') ?>">Data roles</a>
                                <a href="<?= base_url('jobs?location=remote') ?>">Remote</a>
                                <a href="<?= base_url('candidate/company-job-discovery') ?>">Company discovery</a>
                            </div>

                            <div class="hm-hero-actions">
                                <a href="<?= base_url('register') ?>" class="hm-cta-primary">Start as candidate</a>
                                <a href="<?= base_url('recruiter/register') ?>" class="hm-cta-secondary">Hire talent</a>
                            </div>
                        </div>

                        <div class="hm-product-scene" aria-label="HireMatrix product preview">
                            <div class="hm-dashboard-card">
                                <div class="hm-dashboard-topbar">
                                    <span class="hm-window-dots"><span></span><span></span><span></span></span>
                                    <span class="hm-job-pill">Live match</span>
                                </div>
                                <div class="hm-dashboard-body">
                                    <div class="hm-match-card">
                                        <span class="hm-avatar-stack">
                                            <img src="<?= base_url('jobboard/images/person_1.jpg') ?>" alt="Candidate avatar">
                                            <img src="<?= base_url('jobboard/images/person_2.jpg') ?>" alt="Recruiter avatar">
                                        </span>
                                        <span>
                                            <strong>Frontend Developer</strong>
                                            <span>Matched by skills and intent</span>
                                        </span>
                                        <span class="hm-score-ring">88%</span>
                                    </div>

                                    <div class="hm-dashboard-grid">
                                        <div class="hm-pipeline-card">
                                            <strong>Pipeline</strong>
                                            <span>Applied to booked</span>
                                            <span class="hm-bars">
                                                <span style="--w: 74%"></span>
                                                <span style="--w: 54%"></span>
                                                <span style="--w: 38%"></span>
                                            </span>
                                        </div>
                                        <div class="hm-pipeline-card">
                                            <strong>Career path</strong>
                                            <span>PHP to Data Analyst</span>
                                            <span class="hm-bars">
                                                <span style="--w: 82%"></span>
                                                <span style="--w: 61%"></span>
                                                <span style="--w: 42%"></span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="hm-job-row">
                                        <span class="hm-job-icon"><i class="fas fa-file-alt"></i></span>
                                        <span>
                                            <strong>Resume Studio</strong>
                                            <span>Tailored and ready</span>
                                        </span>
                                        <span class="hm-job-pill">ATS</span>
                                    </div>
                                    <div class="hm-job-row">
                                        <span class="hm-job-icon"><i class="fas fa-calendar-check"></i></span>
                                        <span>
                                            <strong>Interview booked</strong>
                                            <span>Slot confirmed</span>
                                        </span>
                                        <span class="hm-job-pill">Today</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="hm-stats">
            <div class="hm-landing-rail">
                <div class="hm-stats-grid">
                    <div class="hm-stat-card"><strong><?= esc($formatCount($jobsPostedCount, '500+')) ?></strong><span>matched jobs</span></div>
                    <div class="hm-stat-card"><strong><?= esc($formatCount($candidateCount, '2k+')) ?></strong><span>candidate profiles</span></div>
                    <div class="hm-stat-card"><strong><?= esc($formatCount($interviewCount, '250+')) ?></strong><span>interviews booked</span></div>
                    <div class="hm-stat-card"><strong><?= esc($formatCount($recruiterCount, '120+')) ?></strong><span>recruiters</span></div>
                </div>
            </div>
        </section>

        <section class="hm-section">
            <div class="hm-landing-rail">
                <div class="hm-section-head">
                    <div>
                        <span class="hm-kicker"><i class="fas fa-layer-group"></i> Three connected journeys</span>
                        <h2 class="hm-section-title">One platform, different moves.</h2>
                    </div>
                    <p class="hm-section-text">Candidate, recruiter, and interview flow each get a clear next step.</p>
                </div>

                <div class="hm-story-board">
                    <article class="hm-story-row">
                        <span class="hm-story-label"><i class="fas fa-user"></i> Candidate</span>
                        <div>
                            <h3>Get ready before the application.</h3>
                            <p>Build the profile, match the role, send the better resume.</p>
                        </div>
                        <ul class="hm-story-steps">
                            <li>Profile</li>
                            <li>Resume Studio</li>
                            <li>Matched jobs</li>
                        </ul>
                    </article>

                    <article class="hm-story-row">
                        <span class="hm-story-label"><i class="fas fa-briefcase"></i> Recruiter</span>
                        <div>
                            <h3>See the right candidates sooner.</h3>
                            <p>Post the role, compare applicants, keep hiring motion visible.</p>
                        </div>
                        <ul class="hm-story-steps">
                            <li>Post role</li>
                            <li>Applicant fit</li>
                            <li>Notes</li>
                        </ul>
                    </article>

                    <article class="hm-story-row">
                        <span class="hm-story-label"><i class="fas fa-calendar-check"></i> Interview</span>
                        <div>
                            <h3>Turn interest into a booked slot.</h3>
                            <p>Move from discovery to interview without losing the thread.</p>
                        </div>
                        <ul class="hm-story-steps">
                            <li>Company discovery</li>
                            <li>Slots</li>
                            <li>Status</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="hm-section" id="jobs">
            <div class="hm-landing-rail">
                <div class="hm-section-head">
                    <div>
                        <span class="hm-kicker"><i class="fas fa-briefcase"></i> Open roles</span>
                        <h2 class="hm-section-title">Start with a real opportunity.</h2>
                    </div>
                    <a href="<?= base_url('jobs') ?>" class="hm-cta-secondary">View all jobs <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="hm-jobs-grid">
                    <?php
                    $publicJobActions = ['Check fit', 'View opening', 'Explore role', 'Open details', 'Review job', 'See role'];
                    $publicJobLabels = ['Match after sign-in', 'Public opening', 'Hiring now', 'Role preview', 'Fresh listing', 'Open position'];
                    ?>
                    <?php if (!empty($featuredJobs)): ?>
                        <?php foreach (array_slice($featuredJobs, 0, 6) as $jobIndex => $job): ?>
                            <?php
                            $title = (string) ($job['title'] ?? 'Untitled Role');
                            $company = trim((string) ($job['company'] ?? 'Company'));
                            $location = trim((string) ($job['location'] ?? 'N/A'));
                            $postedAt = $formatAge($job['created_at'] ?? $job['posted_at'] ?? null);
                            $jobType = trim((string) ($job['job_type'] ?? $job['type'] ?? 'Full-time'));
                            $jobAction = $publicJobActions[$jobIndex % count($publicJobActions)];
                            $jobLabel = $publicJobLabels[$jobIndex % count($publicJobLabels)];
                            ?>
                            <article class="hm-job-card">
                                <div class="hm-job-card-head">
                                    <span class="hm-job-icon"><i class="<?= esc($pickJobIcon($title)) ?>"></i></span>
                                    <div>
                                        <h3><?= esc($title) ?></h3>
                                        <p><?= esc($company) ?></p>
                                    </div>
                                </div>
                                <div class="hm-job-meta">
                                    <span><i class="fas fa-map-marker-alt"></i> <?= esc($location) ?></span>
                                    <span><i class="fas fa-clock"></i> <?= esc($postedAt) ?></span>
                                </div>
                                <div class="hm-job-tags">
                                    <span><?= esc($jobLabel) ?></span>
                                    <span><?= esc($jobType ?: 'Full-time') ?></span>
                                </div>
                                <a href="<?= base_url('login') ?>" class="hm-job-link"><?= esc($jobAction) ?></a>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php
                        $fallbackJobs = [
                            ['Data Scientist', 'AI Dynamics', 'Remote', 'Full-time', 'fas fa-database'],
                            ['UI/UX Designer', 'Design Studio Pro', 'Bangalore', 'Contract', 'fas fa-palette'],
                            ['Backend Engineer', 'Cloud Systems Inc', 'Hyderabad', 'Full-time', 'fas fa-code'],
                        ];
                        ?>
                        <?php foreach ($fallbackJobs as $jobIndex => $job): ?>
                            <?php
                            $jobAction = $publicJobActions[$jobIndex % count($publicJobActions)];
                            $jobLabel = $publicJobLabels[$jobIndex % count($publicJobLabels)];
                            ?>
                            <article class="hm-job-card">
                                <div class="hm-job-card-head">
                                    <span class="hm-job-icon"><i class="<?= esc($job[4]) ?>"></i></span>
                                    <div>
                                        <h3><?= esc($job[0]) ?></h3>
                                        <p><?= esc($job[1]) ?></p>
                                    </div>
                                </div>
                                <div class="hm-job-meta">
                                    <span><i class="fas fa-map-marker-alt"></i> <?= esc($job[2]) ?></span>
                                    <span><i class="fas fa-clock"></i> Recently</span>
                                </div>
                                <div class="hm-job-tags">
                                    <span><?= esc($jobLabel) ?></span>
                                    <span><?= esc($job[3]) ?></span>
                                </div>
                                <a href="<?= base_url('login') ?>" class="hm-job-link"><?= esc($jobAction) ?></a>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="hm-section">
            <div class="hm-landing-rail">
                <div class="hm-cta-panel">
                    <div>
                        <h2>Your next move starts here.</h2>
                        <p>Search smarter. Hire faster.</p>
                    </div>
                    <div class="hm-cta-actions">
                        <a href="<?= base_url('register') ?>" class="hm-cta-primary">Join as candidate</a>
                        <a href="<?= base_url('recruiter/register') ?>" class="hm-cta-secondary">Join as recruiter</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

</div>
</body>
</html>
