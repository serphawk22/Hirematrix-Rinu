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

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>HireMatrix | Home</title>
    <meta name="description" content="AI Job Portal home page">
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
/* ===============================
   STATIC STRIPES (NO ANIMATION)
================================= */

:root {
    --stripe-bg: repeating-linear-gradient(
        120deg,
        rgba(79,70,229,0.04) 0px,
        rgba(79,70,229,0.04) 2px,
        transparent 2px,
        transparent 60px,

        rgba(90,169,255,0.04) 60px,
        rgba(90,169,255,0.04) 62px,
        transparent 62px,
        transparent 120px,

        rgba(255,140,90,0.04) 120px,
        rgba(255,140,90,0.04) 122px,
        transparent 122px,
        transparent 180px
    );
}

body {
    position: relative;
    overflow-x: hidden;

    background: #fafbff; /* base fallback */
}


/* 🔥 MOVING STRIPES LAYER */
body::before {
    content: "";
    position: fixed;
    inset: 0;
    z-index: -2;

    background: repeating-linear-gradient(
        120deg,
        rgba(79,70,229,0.05) 0px,
        rgba(79,70,229,0.05) 2px,
        transparent 2px,
        transparent 60px,

        rgba(90,169,255,0.05) 60px,
        rgba(90,169,255,0.05) 62px,
        transparent 62px,
        transparent 120px,

        rgba(255,140,90,0.05) 120px,
        rgba(255,140,90,0.05) 122px,
        transparent 122px,
        transparent 180px
    );

    animation: moveStripes 40s linear infinite;
}
body {
    background:
        repeating-linear-gradient(
            120deg,
            rgba(79,70,229,0.025) 0px,
            rgba(79,70,229,0.025) 1px,
            transparent 1px,
            transparent 80px
        ),
        linear-gradient(
            120deg,
            #fafbff,
            #f5f7ff,
            #f8f9fc,
            #fdf6f2
        );
}

/* ===============================
   ANIMATIONS
================================= */

@keyframes moveStripes {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-200px); /* slow slide */
    }
}

@keyframes bodyGradientMove {
    0%   { background-position: 0% 50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
    .hero { position: relative; padding: 100px 0; overflow: hidden; /* 🔥 stronger animated gradient */ background: linear-gradient( 120deg, #dbe6ff, #eef2ff, #fcefe6, #fbc5afa1, #dbe6ff ); background-size: 300% 300%; animation: gradientMove 6s ease-in-out infinite; } /* 🎯 gradient animation */ @keyframes gradientMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } } /* =============================== 🔥 BIG FLOATING BLOBS (VISIBLE) ================================= */ .hero::before, .hero::after { content: ""; position: absolute; border-radius: 50%; filter: blur(60px); /* less blur = stronger */ opacity: 0.7; z-index: 0; } .hero::before { width: 420px; height: 420px; background: #ac75ffac; top: -100px; left: -100px; animation: blobMove1 5s ease-in-out infinite alternate; } .hero::after { width: 420px; height: 420px; background: #6b95ffe5; bottom: -100px; right: -100px; animation: blobMove2 6s ease-in-out infinite alternate; } @keyframes blobMove1 { 0% { transform: translate(0, 0); } 100% { transform: translate(120px, 80px); } } @keyframes blobMove2 { 0% { transform: translate(0, 0); } 100% { transform: translate(-120px, -80px); } } /* =============================== ✨ LIGHT SWEEP (VERY NOTICEABLE) ================================= */ .hero .light-sweep { position: absolute; top: 0; left: -120%; width: 60%; height: 100%; z-index: 1; background: linear-gradient( 120deg, transparent, rgba(255,255,255,0.5), transparent ); transform: skewX(-20deg); animation: sweepMove 4s linear infinite; } @keyframes sweepMove { 0% { left: -120%; } 100% { left: 130%; } }
/* ===============================
   STATUS PILL
================================= */

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(79, 70, 229, 0.08);
    color: rgba(78, 70, 229, 0.82);
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 15px;

    backdrop-filter: blur(10px);
}


/* ===============================
   HERO TEXT
================================= */

.hero-title {
    font-size: 48px;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.5px;
    margin-bottom: 15px;
     color: #111827;
}

.hero-subtitle {
    font-size: 16px;
    color: #6b7280;
    max-width: 600px;
}


/* gradient highlight */
.gradient-text {
    background: linear-gradient(90deg, #4f46e5, #9b87f5, #f4a261); 
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}


/* ===============================
   SEARCH PANEL
================================= */

.landing-search-panel {
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 15px 40px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    background: #ffffff;
}

.landing-search-panel:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.12);
}


/* search inputs */
.search-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f9fafb;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid transparent;
    transition: all 0.25s ease;
}

.search-input-group:hover {
    border-color: rgba(79,70,229,0.3);
    background: #fff;
}

.search-input-group input {
    border: none !important;
    background: transparent;
    font-size: 14px;
}

.search-input-group input:focus {
    outline: none;
    box-shadow: none;
}


/* ===============================
   SEARCH BUTTON
================================= */

.landing-search-submit {
    border-radius: 10px;
    font-weight: 600;

    background: linear-gradient(135deg, #4f46e5, #3b82f6);
    border: none;

    box-shadow: 0 8px 18px rgba(79,70,229,0.25);
    transition: all 0.3s ease;
}

.landing-search-submit:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 12px 28px rgba(79,70,229,0.35);
}


/* ===============================
   POPULAR TAGS
================================= */

.btn-group .btn {
    border-radius: 50px !important;
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.25s ease;
}

.btn-group .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0,0,0,0.1);
}


/* ===============================
   FOOT NOTE TEXT
================================= */

.hero p.text-muted {
    opacity: 0.8;
}


/* ===============================
   RESPONSIVE
================================= */

@media (max-width: 768px) {
    .hero-title {
        font-size: 32px;
    }

    .hero {
        padding: 70px 0;
    }
}
/* ===============================
   CAREER TRANSITION BACKGROUND
================================= */
/* ===============================
   CAREER TRANSITION (MATCH HERO)
================================= */

.landing-career-transition {
    position: relative;
    padding: 100px 0;
    overflow: hidden;

    /* 🔥 EXACT HERO GRADIENT */
    background: linear-gradient(
        120deg,
        #dbe6ff,
        #eef2ff,
        #fcefe6,
        #fbc5afa1,
        #dbe6ff
    );

    background-size: 300% 300%;
    animation: gradientMove 6s ease-in-out infinite;
}

/* SAME gradient animation */
@keyframes gradientMove {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}


/* ===============================
   🔥 HERO STYLE BLOBS (MATCHED)
================================= */

.landing-career-transition::before,
.landing-career-transition::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    opacity: 0.7;
    z-index: 0;
}

/* SAME COLORS AS HERO */
.landing-career-transition::before {
    width: 420px;
    height: 420px;
    background: #ac75ffac;
    top: -100px;
    left: -100px;
    animation: blobMove1 5s ease-in-out infinite alternate;
}

.landing-career-transition::after {
    width: 420px;
    height: 420px;
    background: #81a4fece;
    bottom: -100px;
    right: -100px;
    animation: blobMove2 6s ease-in-out infinite alternate;
}

@keyframes blobMove1 {
    0% { transform: translate(0, 0); }
    100% { transform: translate(120px, 80px); }
}

@keyframes blobMove2 {
    0% { transform: translate(0, 0); }
    100% { transform: translate(-120px, -80px); }
}


/* ===============================
   ✨ LIGHT SWEEP (MATCH HERO)
================================= */

.landing-career-transition .light-sweep {
    position: absolute;
    top: 0;
    left: -120%;
    width: 60%;
    height: 100%;
    z-index: 1;

    background: linear-gradient(
        120deg,
        transparent,
        rgba(255,255,255,0.5),
        transparent
    );

    transform: skewX(-20deg);
    animation: sweepMove 4s linear infinite;
}

@keyframes sweepMove {
    0% { left: -120%; }
    100% { left: 130%; }
}


/* ===============================
   GLASS CARD (READABLE)
================================= */

.landing-career-transition-inner {
    position: relative;
    z-index: 2;

    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(20px);

    border-radius: 20px;
    padding: 40px;

    border: 1px solid rgba(255,255,255,0.6);
    box-shadow: 0 15px 50px rgba(0,0,0,0.15);
}


/* ===============================
   TEXT
================================= */

.landing-career-transition-kicker {
    display: inline-block;
    font-size: 13px;
    font-weight: 600;

    color: #4f46e5;
    background: rgba(79,70,229,0.12);

    padding: 6px 12px;
    border-radius: 20px;
    margin-bottom: 15px;
}

.landing-career-transition-title {
    font-size: 32px;
    font-weight: 700;

     background: linear-gradient(90deg, #4f46e5, #9b87f5, #f4a261); 
    background-size: 200% auto;

    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;

    animation: textShine 4s linear infinite;
}

@keyframes textShine {
    to { background-position: 200% center; }
}

.landing-career-transition-text {
    font-size: 15px;
    color: #374151;
    opacity: 0.95;
    max-width: 500px;
}


/* ===============================
   BUTTON
================================= */

.landing-career-transition-btn {
    display: inline-block;
    margin-top: 20px;

    background: #ffffff;
    color: #4f46e5;
    font-weight: 600;

    padding: 10px 18px;
    border-radius: 10px;

    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.landing-career-transition-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}


/* ===============================
   LAYER FIX
================================= */

.landing-career-transition .container {
    position: relative;
    z-index: 2;
}


/* ===============================
   RESPONSIVE
================================= */

@media (max-width: 768px) {
    .landing-career-transition-inner {
        padding: 25px;
    }

    .landing-career-transition-title {
        font-size: 24px;
    }
}
.job-card {
    background: linear-gradient(
        135deg,
        #ffffff 0%,
        #f7f9ff 100%
    );

    border-radius: 14px;
    padding: 20px;

    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);

    transition: all 0.3s ease;
}
 
.job-card:hover {
    transform: translateY(-6px);

    background: linear-gradient(
        135deg,
        #ffffff 0%,
        #eef2ff 100%
    );

    box-shadow: 0 18px 35px rgba(0,0,0,0.10);
}
.job-card-title { 

    background: linear-gradient(90deg, #4f46e5, #9b87f5, #f4a261);

    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.section-title { 
    background: linear-gradient(90deg, #4f46e5, #9b87f5, #f4a261);

    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.landing-get-started-title{
    background: linear-gradient(90deg, #4f46e5, #9b87f5, #f4a261);

    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
/* ===============================
   REMOVE SECTION BACKGROUND
================================= */
/* ===============================
   SECTION BACKGROUND (SMOOTH + NO LINE)
================================= */
/* ===============================
   SECTION BACKGROUND (CLEAN + SPACING)
================================= */
.landing-get-started {
    padding: 100px 0 120px;

    background: linear-gradient(
        120deg,
        #f8faff,
        #f4f6ff,
        #fdf7f3
    );
}
/* ===============================
   REMOVE GLOW EDGE BLEED
================================= */
.landing-get-started::before {
    content: "";
    position: absolute;
    inset: 0;

    background:
        radial-gradient(circle at 20% 30%, rgba(79,70,229,0.06), transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(255,140,90,0.06), transparent 40%);

    pointer-events: none;
}
/* ===============================
   CONTAINER LAYER FIX
================================= */

.landing-get-started .container {
    position: relative;
    z-index: 2;
}


/* ===============================
   CARD (REDUCE SHADOW SPILL)
================================= */

.landing-start-card {
    position: relative;
    border-radius: 18px;
    padding: 30px;

    background: linear-gradient(145deg, #ffffff, #f5f7ff);

    border: none;

    /* 👇 slightly tighter shadow to avoid line */
    box-shadow: 0 10px 25px rgba(0,0,0,0.07);

    transition: all 0.3s ease;
}


/* ===============================
   HOVER (CONTROLLED)
================================= */

.landing-start-card:hover {
    transform: translateY(-6px);

    /* 👇 controlled shadow (not too deep) */
    box-shadow: 0 18px 35px rgba(0,0,0,0.10);
}


/* ===============================
   EXTRA GAP BEFORE FOOTER
================================= */

footer,
.site-footer {
    margin-top: 0 !important;
    border-top: none !important;
    box-shadow: none !important;
}

/* Optional: add breathing space */
footer {
    padding-top: 40px;
}

/* ===============================
   RESPONSIVE
================================= */

@media (max-width: 768px) {
    .landing-get-started-title {
        font-size: 28px;
    }

    .landing-start-card {
        padding: 20px;
    }
}

/* ===============================
   MOBILE HEADER VISIBILITY FIX
================================= */

@media (max-width: 991px) {
    .site-navbar {
        padding-top: 12px;
        padding-bottom: 12px;
        background: rgba(255, 255, 255, 0.98) !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;
    }

    .landing-header-logo-image {
        height: 32px;
    }

    .landing-header-logo-text {
        font-size: 1.35rem;
    }

    .site-menu-toggle {
        text-decoration: none !important;
        color: #111827 !important;
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.04);
        transition: background 0.2s ease;
    }

    .site-menu-toggle:active {
        background: rgba(0, 0, 0, 0.1);
    }
}
        </style>
</head>
<?= view('Layouts/public_header', ['body_class' => 'landing-page']) ?>

    <section class="hero py-5">
        <div class="light-sweep"></div>
<div class="glow"></div>
        <div class="container">
            <div class="status-pill">
                <i class="fas fa-arrow-trend-up" style="color: var(--primary);"></i>
                <?= max(1, $jobsPostedCount) ?>+ Active Jobs Available
            </div>

            <h1 class="hero-title">
                Find Your
                <span class="gradient-text">Dream Job</span>
                Today
            </h1>

            <p class="hero-subtitle">
                Connect with top companies and discover opportunities that match your skills.
                AI-powered recommendations to fast-track your career.
            </p>

            <div class="card mb-4 landing-search-panel" style="max-width: 800px;">
                <div class="card-body p-3 p-md-4">
                    <form action="<?= base_url('jobs') ?>" method="get" class="landing-search-form">
                        <div class="row g-3 g-md-2 align-items-stretch">
                            <div class="col-12 col-md-6 col-lg-5">
                                <div class="search-input-group">
                                    <i class="fas fa-search" style="color: var(--muted-foreground);"></i>
                                    <input type="text" name="search" placeholder="Job title, skills, or company" class="form-control border-0">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="search-input-group">
                                    <i class="fas fa-map-pin" style="color: var(--muted-foreground);"></i>
                                    <input type="text" name="location" placeholder="City or location" class="form-control border-0">
                                </div>
                            </div>
                            <div class="col-12 col-lg-3 landing-search-submit-col">
                                <button class="btn btn-primary w-100 landing-search-submit" type="submit">Search Jobs</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mb-5">
                <span class="text-muted me-3" style="font-size: 0.875rem; font-weight: 500;">Popular:</span>
                <div class="btn-group" role="group">
                    <a class="btn btn-outline-primary btn-sm landing-popular-developer" href="<?= base_url('jobs?search=developer') ?>" style="border-width: 2px;">Developer</a>
                    <a class="btn btn-sm" href="<?= base_url('jobs?search=designer') ?>" style="background: rgba(255, 123, 42, 0.2); color: var(--secondary); border: none;">Designer</a>
                    <a class="btn btn-sm" href="<?= base_url('jobs?search=marketing') ?>" style="background: rgba(0, 191, 165, 0.2); color: var(--accent); border: none;">Marketing</a>
                    <a class="btn btn-sm" href="<?= base_url('jobs?location=remote') ?>" style="background: rgba(59, 130, 246, 0.2); color: var(--primary); border: none;">Remote</a>
                    <a class="btn btn-sm" href="<?= base_url('jobs?employment_type=full-time') ?>" style="background: rgba(255, 123, 42, 0.2); color: var(--secondary); border: none;">Full-time</a>
                </div>
            </div>

            <p class="text-center text-muted" style="font-size: 0.875rem;">
                Sign in to view complete listings, AI match score, and application status.
            </p>
        </div>
    </section>

    <section class="py-5" id="jobs">
        <div class="container">
            <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                <div>
                    <div class="ai-badge">
                        <i class="fas fa-sparkles"></i>
                        Live Open Roles
                    </div>
                    <h2 class="section-title">Featured Jobs</h2>
                    <p class="section-subtitle">Live openings pulled from the database. Sign in to get personalized matching.</p>
                </div>
                <a href="<?= base_url('jobs') ?>" class="btn btn-ghost landing-view-all-link">View all jobs <i class="fas fa-arrow-right ms-2"></i></a>
            </div>

            <div class="landing-featured-jobs-grid mb-4">
                <?php if (!empty($featuredJobs)): ?>
                    <?php foreach (array_slice($featuredJobs, 0, 6) as $job): ?>
                        <?php
                        $title = (string) ($job['title'] ?? 'Untitled Role');
                        $company = trim((string) ($job['company'] ?? 'Company'));
                        $location = trim((string) ($job['location'] ?? 'N/A'));
                        $postedAt = $formatAge($job['created_at'] ?? $job['posted_at'] ?? null);
                        $matchScore = (int) round((float) ($job['match_score'] ?? 85));
                        ?>
                        <div class="landing-featured-jobs-item">
                            <div class="job-card">
                                <div class="job-card-icon"><i class="<?= esc($pickJobIcon($title)) ?>"></i></div>
                                <h3 class="job-card-title"><?= esc($title) ?></h3>
                                <p class="job-card-company"><?= esc($company) ?></p>
                                <div class="job-card-meta">
                                    <span><i class="fas fa-map-pin"></i> <?= esc($location) ?></span>
                                    <span><i class="fas fa-clock"></i> <?= esc($postedAt) ?></span>
                                </div>
                                <div class="job-card-tags">
                                    <span class="badge badge-primary">Full-time</span>
                                    <span class="badge badge-secondary"><?= esc(substr($title, 0, 15) ?: 'Role') ?></span>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar-custom" style="width: <?= max(10, min(100, $matchScore)) ?>%;"></div>
                                    <span class="progress-label"><?= max(10, min(100, $matchScore)) ?>%</span>
                                </div>
                                <a href="<?= base_url('login') ?>" class="view-details">View Details &rarr;</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="landing-featured-jobs-item">
                        <div class="job-card">
                            <div class="job-card-icon"><i class="fas fa-robot"></i></div>
                            <h3 class="job-card-title">Data Scientist</h3>
                            <p class="job-card-company">AI Dynamics</p>
                            <div class="job-card-meta">
                                <span><i class="fas fa-map-pin"></i> Boston, MA</span>
                                <span><i class="fas fa-clock"></i> 2 days ago</span>
                            </div>
                            <div class="job-card-tags">
                                <span class="badge badge-primary">Full-time</span>
                                <span class="badge badge-secondary">Python</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar-custom" style="width: 88%;"></div>
                                <span class="progress-label">88%</span>
                            </div>
                            <a href="<?= base_url('login') ?>" class="view-details">View Details &rarr;</a>
                        </div>
                    </div>

                    <div class="landing-featured-jobs-item">
                        <div class="job-card">
                            <div class="job-card-icon"><i class="fas fa-pencil-ruler"></i></div>
                            <h3 class="job-card-title">UI/UX Designer</h3>
                            <p class="job-card-company">Design Studio Pro</p>
                            <div class="job-card-meta">
                                <span><i class="fas fa-map-pin"></i> Los Angeles, CA</span>
                                <span><i class="fas fa-clock"></i> 4 days ago</span>
                            </div>
                            <div class="job-card-tags">
                                <span class="badge badge-primary">Remote</span>
                                <span class="badge badge-secondary">Design</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar-custom" style="width: 91%;"></div>
                                <span class="progress-label">91%</span>
                            </div>
                            <a href="<?= base_url('login') ?>" class="view-details">View Details &rarr;</a>
                        </div>
                    </div>

                    <div class="landing-featured-jobs-item">
                        <div class="job-card">
                            <div class="job-card-icon"><i class="fas fa-code"></i></div>
                            <h3 class="job-card-title">Backend Engineer</h3>
                            <p class="job-card-company">Cloud Systems Inc</p>
                            <div class="job-card-meta">
                                <span><i class="fas fa-map-pin"></i> Seattle, WA</span>
                                <span><i class="fas fa-clock"></i> 1 day ago</span>
                            </div>
                            <div class="job-card-tags">
                                <span class="badge badge-primary">Full-time</span>
                                <span class="badge badge-secondary">Node.js</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar-custom" style="width: 86%;"></div>
                                <span class="progress-label">86%</span>
                            </div>
                            <a href="<?= base_url('login') ?>" class="view-details">View Details &rarr;</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <p class="text-center text-muted" style="font-size: 0.875rem;">
                Sign in to see personalized match scores, saved jobs, and application status.
            </p>
        </div>
    </section>

    <section class="landing-career-transition">
        <div class="light-sweep"></div>
        <div class="container">
            <div class="landing-career-transition-inner">
                <div class="landing-career-transition-copy">
                    <div class="landing-career-transition-kicker">
                        <i class="fas fa-sparkles"></i>
                        Career Transition AI
                    </div>
                    <h2 class="landing-career-transition-title">Career Transition AI</h2>
                    <p class="landing-career-transition-text">
                        We analyze your current skill set and generate a focused roadmap for your target role.
                        Start your career transition journey today!
                    </p>
                    <a href="<?= base_url('career-transition') ?>" class="btn btn-light landing-career-transition-btn">
                        Generate Roadmap <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div> 
            </div>
        </div>
    </section>

    <section class="landing-get-started" id="get-started">
        <div class="container">
            <div class="text-center landing-get-started-head">
                <h2 class="landing-get-started-title">Get Started Today</h2>
                <p class="landing-get-started-subtitle">
                    Whether you're looking for your next opportunity or searching for top talent, HireMatrix has you covered.
                </p>
            </div>

            <div class="row g-4 justify-content-center landing-get-started-grid">
                <div class="col-lg-5">
                    <div class="landing-start-card landing-start-card-candidate h-100">
                        <div class="landing-start-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 style="color:#0a80ff">For Job Seekers</h3>
                        <p>Discover opportunities tailored to your skills and career goals.</p>
                        <ul class="landing-start-list">
                            <li><i class="fas fa-check"></i> AI-powered job recommendations</li>
                            <li><i class="fas fa-check"></i> Skill gap analysis</li>
                            <li><i class="fas fa-check"></i> Career transition tools</li>
                        </ul>
                        <a href="<?= base_url('register') ?>" class="btn btn-primary landing-start-btn">
                            Create Candidate Account <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="landing-start-card landing-start-card-recruiter h-100">
                        <div class="landing-start-icon landing-start-icon-recruiter">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3 style="color:#ff8018">For Recruiters</h3>
                        <p>Find and connect with the best talent for your organization.</p>
                        <ul class="landing-start-list landing-start-list-recruiter">
                            <li><i class="fas fa-check"></i> Smart candidate matching</li>
                            <li><i class="fas fa-check"></i> ATS integration</li>
                            <li><i class="fas fa-check"></i> Team collaboration tools</li>
                        </ul>
                        <a href="<?= base_url('recruiter/register') ?>" class="btn btn-primary landing-start-btn landing-start-btn-recruiter">
                            Join as Recruiter <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?= view('Layouts/public_footer') ?>
</body>
</html>
