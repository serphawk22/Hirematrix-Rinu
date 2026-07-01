<?= view('Layouts/recruiter_header', ['title' => 'Recruiter Dashboard', 'showHero' => false]) ?>

<div class="recruiter-dashboard-jobboard">
<?php
$applicationsUrl = base_url('recruiter/jobs');
$jobsUrl         = base_url('recruiter/jobs');
$conversionUrl   = base_url('recruiter/dashboard') . '#conversion-metrics';
$bookingsUrl     = base_url('recruiter/slots/bookings');
$postJobUrl      = base_url('recruiter/post_job');
$slotsUrl        = base_url('recruiter/slots');
$leaderboardUrl  = base_url('recruiter/dashboard/leaderboard');

$formatRate = static function ($value): string {
    if ($value === null || $value === '') return 'N/A';
    return number_format((float) $value, 1) . '%';
};
?>

<style>
/* ═══════════════════════════════════════════
   RECRUITER DASHBOARD v2 — Modern + Actionable
   Cartoon brutalist meets clean SaaS
═══════════════════════════════════════════ */

/* ── Page background ── */
.recruiter-dashboard-jobboard,
.hm-page-content {
    background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%) !important;
    min-height: 100vh;
}
body.dark .recruiter-dashboard-jobboard,
body.dark .hm-page-content {
    background: #000 !important;
}

.recruiter-dashboard-main {
    max-width: 100% !important;
    padding: 24px 32px !important;
}

/* ── Cards ── */
.card, .card.shadow,
.recruiter-dashboard-panel-card,
.recruiter-pipeline-card {
    box-shadow: none !important;
    background: #fff !important;
    border: 1.5px solid #D9ECE5 !important;
    border-radius: 16px !important;
    transition: transform .15s, box-shadow .15s;
}
body.dark .card,
body.dark .card.shadow,
body.dark .recruiter-dashboard-panel-card,
body.dark .recruiter-pipeline-card {
    background: #000 !important;
    border-color: #23343A !important;
}

.card-header {
    background: #fff !important;
    border-bottom: 1.5px solid #D9ECE5 !important;
    border-radius: 16px 16px 0 0 !important;
}
body.dark .card-header {
    background: #000 !important;
    border-color: #23343A !important;
}

/* ── Stat cards ── */
.stat-card-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: #E8F9F8;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #0D8A90;
    font-size: 20px;
}
body.dark .stat-card-icon {
    background: #1B3035;
    color: #1FB7B5;
}

.stat-value {
    font-size: 1.75rem; font-weight: 700; color: #16212B; line-height: 1.1;
}
body.dark .stat-value { color: #fff; }

.stat-label {
    font-size: .78rem; font-weight: 600; color: #94A3B8;
    text-transform: uppercase; letter-spacing: .04em;
}

.recruiter-dashboard-stat-row {
    margin-left: -12px;
    margin-right: -12px;
}
.recruiter-dashboard-stat-row > [class*="col-"] {
    padding-left: 12px;
    padding-right: 12px;
}
.recruiter-dashboard-stat-card {
    padding: 20px 18px !important;
}
.recruiter-dashboard-stat-body {
    gap: 16px;
}
.recruiter-dashboard-stat-copy {
    min-width: 0;
}

.recruiter-action-center-link {
    border-bottom: 1px solid #D9ECE5;
    color: #16212B;
    text-decoration: none !important;
    transition: background-color .12s ease, color .12s ease;
}
.recruiter-action-center-link:hover,
.recruiter-action-center-link:focus {
    background: #E8F9F8 !important;
    color: #16212B;
    text-decoration: none !important;
}
body.dark .recruiter-action-center-link {
    border-color: #23343A !important;
    color: #F8FAFC !important;
}
body.dark .recruiter-action-center-link:hover,
body.dark .recruiter-action-center-link:focus {
    background: rgba(31,183,181,.08) !important;
    color: #F8FAFC !important;
}

/* ── Quick action pills ── */
.hm-quick-actions {
    display: flex; gap: 10px; flex-wrap: wrap;
}
.hm-qa-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 22px; border-radius: 50px;
    border: 1.5px solid #D9ECE5;
    background: #fff; color: #16212B;
    font-size: 13.5px; font-weight: 600;
    text-decoration: none !important;
    position: relative;
    transition: border-color .18s, background .18s, color .18s, transform .15s;
    white-space: nowrap;
}
.hm-qa-btn:hover {
    border-color: #1FB7B5; background: #E8F9F8;
    color: #0D8A90; transform: translateY(-1px);
}
.hm-qa-icon {
    width: 28px; height: 28px;
    background: #E8F9F8; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; color: #0D8A90; flex-shrink: 0;
}
.hm-qa-badge {
    position: absolute; top: -8px; right: -8px;
    min-width: 20px; height: 20px; border-radius: 10px;
    background: #1FB7B5; color: #fff;
    font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    padding: 0 5px; border: 2px solid #fff;
}
body.dark .hm-qa-btn {
    background: #000; border-color: #23343A; color: #94A3B8;
}
body.dark .hm-qa-btn:hover {
    border-color: #1FB7B5; background: rgba(31,183,181,.08); color: #1FB7B5;
}
body.dark .hm-qa-icon { background: #1B3035; color: #1FB7B5; }

/* ── Calendar ── */
.dash-calendar {
    background: #fff; border-radius: 16px;
    border: 1.5px solid #D9ECE5; overflow: hidden;
}
body.dark .dash-calendar {
    background: #000; border-color: #23343A;
}
.dash-calendar-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px 8px;
}
.dash-calendar-header h6 {
    font-weight: 700; font-size: 1rem; margin: 0; color: #16212B;
}
body.dark .dash-calendar-header h6 { color: #fff; }
.dash-calendar-nav {
    display: flex; gap: 6px;
}
.dash-calendar-nav button {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid #D9ECE5; background: #fff;
    color: #16212B; cursor: pointer; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    transition: .12s;
}
.dash-calendar-nav button:hover {
    background: #E8F9F8; border-color: #1FB7B5; color: #0D8A90;
}
body.dark .dash-calendar-nav button {
    background: #000; border-color: #23343A; color: #94A3B8;
}
body.dark .dash-calendar-nav button:hover {
    background: rgba(31,183,181,.1); border-color: #1FB7B5; color: #1FB7B5;
}

.dash-calendar-weekdays {
    display: grid; grid-template-columns: repeat(7, 1fr);
    padding: 4px 10px 0;
    font-size: .7rem; font-weight: 700; color: #94A3B8;
    text-transform: uppercase; text-align: center;
}

.dash-calendar-grid {
    display: grid; grid-template-columns: repeat(7, 1fr);
    gap: 2px; padding: 6px 10px 14px;
}

.dash-calendar-day {
    aspect-ratio: 1; border-radius: 10px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    font-size: .82rem; font-weight: 500; color: #16212B;
    position: relative; cursor: default;
    transition: .1s;
    min-height: 38px;
}
body.dark .dash-calendar-day { color: #F8FAFC; }
.dash-calendar-day.other-month { opacity: .25; }
.dash-calendar-day.today {
    background: #1FB7B5; color: #fff; font-weight: 700;
    box-shadow: 0 2px 8px rgba(31,183,181,.35);
}
.dash-calendar-day.has-interview::after {
    content: ''; position: absolute; bottom: 4px;
    width: 5px; height: 5px; border-radius: 50%;
    background: #F59E0B;
}
.dash-calendar-day.today.has-interview::after {
    background: #fff;
}

/* ── Interview list in sidebar ── */
.interview-today-card {
    border: 1.5px solid #D9ECE5; border-radius: 14px;
    background: #fff; overflow: hidden;
}
body.dark .interview-today-card {
    background: #000; border-color: #23343A;
}
.interview-today-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; border-bottom: 1px solid #D9ECE5;
    text-decoration: none; color: #16212B;
    transition: background .12s;
}
.interview-today-item:last-child { border-bottom: none; }
.interview-today-item:hover { background: #E8F9F8; }
body.dark .interview-today-item {
    color: #F8FAFC; border-color: #23343A;
}
body.dark .interview-today-item:hover { background: rgba(31,183,181,.08); }
.interview-time-badge {
    background: #E8F9F8; border-radius: 8px;
    padding: 4px 10px; font-size: .72rem; font-weight: 700;
    color: #0D8A90; white-space: nowrap; flex-shrink: 0;
}
body.dark .interview-time-badge {
    background: #1B3035; color: #1FB7B5;
}

.recruiter-pending-screen-card {
    border: 1.5px solid #F4E29A;
    border-radius: 14px;
    background: #FEF7D8;
    color: #8A6A08;
    text-decoration: none !important;
    transition: background-color .12s ease, border-color .12s ease;
}
.recruiter-pending-screen-card strong {
    color: #8A6A08;
    font-size: .85rem;
}
.recruiter-pending-screen-card small {
    color: #B89A3A;
    font-size: .75rem;
}
.recruiter-pending-screen-badge {
    background: #FFE45C;
    color: #6B5300;
    font-size: 1rem;
    font-weight: 700;
    border-radius: 10px;
    padding: 4px 10px;
}
body.dark .recruiter-pending-screen-card {
    background: #071214;
    border-color: #23343A;
    color: #F8FAFC;
}
body.dark .recruiter-pending-screen-card:hover,
body.dark .recruiter-pending-screen-card:focus {
    background: rgba(31,183,181,.08);
    border-color: #1FB7B5;
}
body.dark .recruiter-pending-screen-card strong {
    color: #F8FAFC;
}
body.dark .recruiter-pending-screen-card small {
    color: #94A3B8;
}
body.dark .recruiter-pending-screen-badge {
    background: #1B3035;
    color: #1FB7B5;
}

/* ── Pipeline ── */
.pipeline-stat h3 {
    color: #16212B; font-weight: 700; font-size: 1.6rem;
}
body.dark .pipeline-stat h3 { color: #fff; }
.pipeline-stat-icon {
    width: 56px; height: 56px; border-radius: 50%;
    background: #E8F9F8;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 10px; font-size: 20px; color: #0D8A90;
}
body.dark .pipeline-stat-icon { background: #1B3035; color: #1FB7B5; }
.pipeline-connector {
    display: flex; align-items: center; justify-content: center;
    color: #D9ECE5; font-size: 1.2rem; padding-top: 10px;
}
body.dark .pipeline-connector { color: #23343A; }
.recruiter-pipeline-overview-card {
    height: auto !important;
}
.recruiter-pipeline-overview-card .card-body {
    padding: 24px 28px 26px !important;
}
.recruiter-dashboard-main-stack,
.recruiter-dashboard-side-stack {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* ── Table ── */
/* Conversion metrics */
.conversion-overall-card {
    align-items: center;
    background: #E8F9F8;
    border: 1px solid #D9ECE5;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding: 1rem;
}
.conversion-overall-label {
    color: #94A3B8;
    display: block;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.conversion-overall-value {
    color: #16212B;
    font-size: 1.3rem;
    font-weight: 700;
}
.conversion-overall-icon {
    align-items: center;
    background: #FFFFFF;
    border-radius: 50%;
    color: #0D8A90;
    display: flex;
    font-size: 22px;
    height: 56px;
    justify-content: center;
    width: 56px;
}
body.dark .conversion-overall-card {
    background: #071214 !important;
    border-color: #23343A !important;
}
body.dark .conversion-overall-value {
    color: #F8FAFC !important;
}
body.dark .conversion-overall-icon {
    background: #0E1D21 !important;
    color: #1FB7B5 !important;
}

.table th, .table td {
    color: #16212B; border-color: #D9ECE5 !important;
    vertical-align: middle; font-size: .85rem;
}
body.dark .table th, body.dark .table td {
    color: #F8FAFC; border-color: #23343A !important;
    background: transparent !important;
}
.thead-light th {
    background: #EDF8F5 !important;
    color: #64748B !important;
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
}
body.dark .thead-light th {
    background: #000 !important; color: #94A3B8 !important;
}
.table-hover tbody tr:hover td {
    background: #E8F9F8 !important;
}
body.dark .table-hover tbody tr:hover td {
    background: #162327 !important;
}

/* ── Status pill ── */
.status-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 14px; border-radius: 50px;
    font-size: .78rem; font-weight: 600;
    background: rgba(13,138,144,.08); color: #0D8A90;
    border: 1px solid rgba(13,138,144,.15);
    white-space: nowrap;
}
body.dark .status-pill {
    background: #000; color: #1FB7B5;
    border-color: rgba(31,183,181,.15);
}

/* ── Buttons ── */
.btn-outline-primary {
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important; background: transparent !important;
    padding: 7px 18px; border-radius: 8px !important;
    font-size: 13px; font-weight: 600; transition: all .2s;
}
.btn-outline-primary:hover {
    background: #1FB7B5 !important; color: #fff !important;
    transform: translateY(-1px);
}

/* ── Alert ── */
.alert-warning {
    background: #FEF7D8 !important;
    border: 1px solid #F4E29A !important;
    color: #8A6A08 !important; border-radius: 12px !important;
}
body.dark .alert-warning {
    background: #171a14 !important;
    border-color: #3A3420 !important; color: #D8C27A !important;
}

/* ── Dark mode general ── */
body.dark .text-muted { color: #94A3B8 !important; }
body.dark h4, body.dark h6, body.dark h5, body.dark .h4 { color: #fff !important; }
body.dark .recruiter-dashboard-kicker {
    color: #7DD3FC !important;
}
body.dark .recruiter-dashboard-title {
    color: #F8FAFC !important;
}
body.dark .recruiter-dashboard-subtitle {
    color: #C8D7E3 !important;
}

@media (max-width: 480px) {
    .recruiter-dashboard-main { padding: 16px !important; }
    .hm-quick-actions { gap: 6px; }
    .hm-qa-btn { padding: 7px 14px; font-size: 12px; }
}
</style>

<div class="container-fluid recruiter-dashboard-main">

    <!-- ── HEADER ── -->
    <div class="page-board-header page-board-header-tight recruiter-page-board-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <span class="section-eyebrow recruiter-dashboard-kicker d-block mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94A3B8;">Hiring analytics</span>
            <h1 class="recruiter-dashboard-title m-0" style="font-size:1.6rem;font-weight:700;color:#16212B;">Recruitment Overview</h1>
            <p class="recruiter-dashboard-subtitle text-muted mb-0" style="font-size:.9rem;">Review applications, job activity, and pipeline performance across your jobs.</p>
        </div>
        <?php if (empty($noJobs)): ?>
            <a href="<?= base_url('recruiter/dashboard/export-excel?type=overview') ?>" class="btn btn-outline-primary">
                <i class="fas fa-file-excel"></i> Export
            </a>
        <?php endif; ?>
    </div>


    <?php if (!empty($noJobs)): ?>
    <div class="card mb-4 p-5 text-center">
        <h4 class="mb-2" style="font-weight:600;">No jobs posted yet</h4>
        <p class="text-muted mb-3">Post your first job to start receiving applications and build your hiring pipeline.</p>
        <a href="<?= $postJobUrl ?>" class="btn btn-outline-primary"><i class="fas fa-plus"></i> Post Your First Job</a>
    </div>
    <?php endif; ?>

    <?php if (empty($noJobs) && array_sum($pendingActions) > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-triangle"></i> Pending Actions:
        <?php if ($pendingActions['pending_screening'] > 0): ?>
            <span class="badge badge-warning ml-1"><?= $pendingActions['pending_screening'] ?></span> applications to screen,
        <?php endif; ?>
        <?php if ($pendingActions['hr_interviews_today'] > 0): ?>
            <span class="badge badge-primary ml-1"><?= $pendingActions['hr_interviews_today'] ?></span> interviews today
        <?php endif; ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <!-- ════════════════════ ROW 1: 4 STAT CARDS ════════════════════ -->
    <div class="row recruiter-dashboard-stat-row mb-4" style="row-gap:16px;">
        <div class="col-xl-3 col-md-6">
            <a href="<?= $applicationsUrl ?>" class="text-decoration-none d-block">
                <div class="card recruiter-dashboard-stat-card h-100" style="border-radius:16px;">
                    <div class="recruiter-dashboard-stat-body d-flex align-items-center">
                        <div class="stat-card-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="recruiter-dashboard-stat-copy">
                            <div class="stat-label">Total Applications</div>
                            <div class="stat-value"><?= number_format($funnel['total_applications']) ?></div>
                            <small class="text-muted">Across all active jobs</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="<?= $jobsUrl ?>" class="text-decoration-none d-block">
                <div class="card recruiter-dashboard-stat-card h-100" style="border-radius:16px;">
                    <div class="recruiter-dashboard-stat-body d-flex align-items-center">
                        <div class="stat-card-icon"><i class="fas fa-briefcase"></i></div>
                        <div class="recruiter-dashboard-stat-copy">
                            <div class="stat-label">Open Jobs</div>
                            <div class="stat-value"><?= $jobStats['active_jobs'] ?></div>
                            <small class="text-muted">Currently hiring</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="<?= $conversionUrl ?>" class="text-decoration-none d-block">
                <div class="card recruiter-dashboard-stat-card h-100" style="border-radius:16px;">
                    <div class="recruiter-dashboard-stat-body d-flex align-items-center">
                        <div class="stat-card-icon"><i class="fas fa-chart-pie"></i></div>
                        <div class="recruiter-dashboard-stat-copy">
                            <div class="stat-label">Conversion Rate</div>
                            <div class="stat-value"><?= $conversionMetrics['overall_conversion'] ?? 0 ?>%</div>
                            <small class="text-muted">Pipeline efficiency</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="<?= $bookingsUrl ?>" class="text-decoration-none d-block">
                <div class="card recruiter-dashboard-stat-card h-100" style="border-radius:16px;">
                    <div class="recruiter-dashboard-stat-body d-flex align-items-center">
                        <div class="stat-card-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="recruiter-dashboard-stat-copy">
                            <div class="stat-label">Interview Bookings</div>
                            <div class="stat-value"><?= number_format($jobStats['interview_bookings'] ?? 0) ?></div>
                            <small class="text-muted">HR rounds scheduled</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- ════════════════════ ROW 2: PIPELINE + CALENDAR ════════════════════ -->
    <div class="row g-4 mb-4 align-items-start">
        <!-- ── Pipeline ── -->
        <div class="col-lg-8 recruiter-dashboard-main-stack">
            <div class="card recruiter-pipeline-overview-card" style="border-radius:16px;">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="m-0 font-weight-bold" style="font-weight:600;">Recruitment Pipeline</h6>
                        <small class="text-muted">Volume, screening, and conversion tracking</small>
                    </div>
                    <a href="<?= $jobsUrl ?>" class="btn btn-outline-primary btn-sm">Review Jobs</a>
                </div>
                <div class="card-body">
                    <?php $screeningCompleted = (int)($funnel['ai_interview_completed'] ?? 0) + (int)($funnel['shortlisted'] ?? 0) + (int)($funnel['rejected'] ?? 0); ?>
                    <div class="row text-center align-items-center g-3">
                        <div class="col-md col-6">
                            <div class="pipeline-stat-icon"><i class="fas fa-inbox"></i></div>
                            <h3><?= number_format($funnel['total_applications']) ?></h3>
                            <p class="text-muted mb-0 small">Applications</p>
                        </div>
                        <div class="col-md-auto d-none d-md-flex pipeline-connector"><i class="fas fa-chevron-right"></i></div>
                        <div class="col-md col-6">
                            <div class="pipeline-stat-icon"><i class="fas fa-cogs"></i></div>
                            <h3><?= number_format($screeningCompleted) ?></h3>
                            <p class="text-muted mb-0 small">Screening Done</p>
                            <small class="text-muted"><?= $funnel['total_applications'] > 0 ? round(($screeningCompleted / $funnel['total_applications']) * 100, 1) : 0 ?>% from applications</small>
                        </div>
                        <div class="col-md-auto d-none d-md-flex pipeline-connector"><i class="fas fa-chevron-right"></i></div>
                        <div class="col-md col-6">
                            <div class="pipeline-stat-icon"><i class="fas fa-star"></i></div>
                            <h3><?= number_format($funnel['shortlisted']) ?></h3>
                            <p class="text-muted mb-0 small">Shortlisted</p>
                            <small class="text-muted"><?= $screeningCompleted > 0 ? round(($funnel['shortlisted'] / $screeningCompleted) * 100, 1) : 0 ?>% from screened</small>
                        </div>
                        <div class="col-md-auto d-none d-md-flex pipeline-connector"><i class="fas fa-chevron-right"></i></div>
                        <div class="col-md col-6">
                            <div class="pipeline-stat-icon"><i class="fas fa-calendar-check"></i></div>
                            <h3><?= number_format($funnel['interview_slot_booked']) ?></h3>
                            <p class="text-muted mb-0 small">HR Interviews</p>
                            <small class="text-muted"><?= $funnel['shortlisted'] > 0 ? round(($funnel['interview_slot_booked'] / $funnel['shortlisted']) * 100, 1) : 0 ?>% from shortlisted</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="border-radius:16px;">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="m-0 font-weight-bold" style="font-weight:600;">Recent Applications</h6>
                    <a href="<?= $applicationsUrl ?>" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr><th class="pl-3">ID</th><th>Candidate</th><th>Job</th><th>Status</th><th>Applied</th></tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentApplications)): ?>
                                    <?php foreach ($recentApplications as $app): ?>
                                        <tr onclick="window.location='<?= base_url('recruiter/jobs/' . $app['job_id'] . '/leaderboard') ?>'" style="cursor:pointer;">
                                            <td class="pl-3 text-muted">#<?= $app['id'] ?></td>
                                            <td><strong><?= esc($app['candidate_name']) ?></strong></td>
                                            <td><?= esc($app['job_title']) ?></td>
                                            <td><span class="status-pill"><?= ucwords(str_replace('_', ' ', $app['status'])) ?></span></td>
                                            <td class="text-muted"><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No recent applications</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (empty($noJobs)): ?>
            <div class="card" style="border-radius:16px;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold" style="font-weight:600;"><i class="fas fa-bolt"></i> Action Center</h6>
                </div>
                <div class="card-body p-0">
                    <?php $hasActions = ((int)($pendingActions['pending_screening'] ?? 0) > 0) || ((int)($pendingActions['hr_interviews_today'] ?? 0) > 0); ?>
                    <?php if ($hasActions): ?>
                        <?php if ((int)($pendingActions['pending_screening'] ?? 0) > 0): ?>
                        <a href="<?= $jobsUrl ?>" class="recruiter-action-center-link d-flex align-items-center justify-content-between p-3">
                            <div>
                                <strong><i class="fas fa-file-signature" style="color:#0D8A90;"></i> Screen New Applications</strong>
                                <small class="d-block text-muted">Review and shortlist incoming candidates.</small>
                            </div>
                            <span class="badge" style="background:#FFE45C;color:#6B5300;border-radius:20px;font-weight:700;padding:5px 12px;"><?= (int)$pendingActions['pending_screening'] ?></span>
                        </a>
                        <?php endif; ?>
                        <?php if ((int)($pendingActions['hr_interviews_today'] ?? 0) > 0): ?>
                        <a href="<?= $bookingsUrl ?>" class="recruiter-action-center-link d-flex align-items-center justify-content-between p-3">
                            <div>
                                <strong><i class="fas fa-calendar-check" style="color:#0D8A90;"></i> Interviews Today</strong>
                                <small class="d-block text-muted">Track today's booked interviews and update status.</small>
                            </div>
                            <span class="status-pill"><?= (int)$pendingActions['hr_interviews_today'] ?></span>
                        </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div style="width:48px;height:48px;border-radius:50%;background:#E8F9F8;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;color:#0D8A90;font-size:20px;"><i class="fas fa-check"></i></div>
                            <h6 style="font-weight:600;">All caught up!</h6>
                            <p class="text-muted small mb-3">No pending screenings or interviews right now.</p>
                            <a href="<?= $jobsUrl ?>" class="btn btn-outline-primary btn-sm">Review Jobs</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <div class="col-lg-4 recruiter-dashboard-side-stack">
            <div class="dash-calendar" id="dashCalendar"></div>

            <div class="interview-today-card">
                <div class="card-header py-2" style="border-radius:14px 14px 0 0;">
                    <h6 class="m-0" style="font-weight:600;font-size:.9rem;">
                        <i class="fas fa-clock"></i> Today's Interviews
                        <?php if (count($todayInterviews) > 0): ?>
                            <span class="badge ml-1" style="background:#1FB7B5;color:#fff;border-radius:20px;font-size:10px;"><?= count($todayInterviews) ?></span>
                        <?php endif; ?>
                    </h6>
                </div>
                <?php if (count($todayInterviews) > 0): ?>
                    <?php foreach ($todayInterviews as $iv): ?>
                        <a href="<?= base_url('recruiter/slots/bookings') ?>" class="interview-today-item">
                            <span class="interview-time-badge"><?= date('h:i A', strtotime($iv['slot_time'])) ?></span>
                            <div>
                                <strong style="font-size:.85rem;"><?= esc($iv['candidate_name']) ?></strong>
                                <small class="d-block text-muted" style="font-size:.75rem;"><?= esc($iv['job_title'] ?? 'Interview') ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-3 text-center text-muted" style="font-size:.85rem;">
                        <i class="fas fa-check-circle" style="color:#1FB7B5;"></i> No interviews scheduled today
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Actionable mini-card: pending screening ── -->
            <div class="card" style="border-radius:16px;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold" style="font-weight:600;"><i class="fas fa-funnel-dollar mr-1"></i> Conversion Metrics</h6>
                </div>
                <div class="card-body">
                    <div class="conversion-overall-card">
                        <div>
                            <span class="conversion-overall-label">Overall Conversion</span>
                            <span class="conversion-overall-value"><?= number_format((float)($conversionMetrics['overall_conversion'] ?? 0), 1) ?>%</span>
                        </div>
                        <div class="conversion-overall-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <table class="table table-sm mb-0" style="font-size:.85rem;">
                        <thead><tr><th>Stage Transition</th><th class="text-right">Rate</th></tr></thead>
                        <tbody>
                            <?php
                            $stages = [
                                'application_to_screening' => 'Application → Screening',
                                'screening_to_shortlist' => 'Screening → Shortlist',
                                'shortlist_to_hr_interview' => 'Shortlist → HR Interview',
                                'hr_interview_to_selection' => 'HR Interview → Selection'
                            ];
                            foreach ($stages as $key => $label): ?>
                                <tr>
                                    <td><?= $label ?></td>
                                    <td class="text-right"><span class="status-pill"><?= $formatRate($conversionMetrics[$key] ?? null) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="font-weight-bold">
                                <td>Overall</td>
                                <td class="text-right"><span class="status-pill"><?= number_format((float)($conversionMetrics['overall_conversion'] ?? 0), 1) ?>%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════ ACTION CENTER ════════════════════ -->
    <?php if (empty($noJobs)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="border-radius:16px;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold" style="font-weight:600;"><i class="fas fa-bolt"></i> Action Center</h6>
                </div>
                <div class="card-body p-0">
                    <?php $hasActions = ((int)($pendingActions['pending_screening'] ?? 0) > 0) || ((int)($pendingActions['hr_interviews_today'] ?? 0) > 0); ?>
                    <?php if ($hasActions): ?>
                        <?php if ((int)($pendingActions['pending_screening'] ?? 0) > 0): ?>
                        <a href="<?= $jobsUrl ?>" class="d-flex align-items-center justify-content-between p-3" style="border-bottom:1px solid #D9ECE5;text-decoration:none;color:#16212B;transition:background.12s;" onmouseover="this.style.background='#E8F9F8'" onmouseout="this.style.background='transparent'">
                            <div>
                                <strong><i class="fas fa-file-signature" style="color:#0D8A90;"></i> Screen New Applications</strong>
                                <small class="d-block text-muted">Review and shortlist incoming candidates.</small>
                            </div>
                            <span class="badge" style="background:#FFE45C;color:#6B5300;border-radius:20px;font-weight:700;padding:5px 12px;"><?= (int)$pendingActions['pending_screening'] ?></span>
                        </a>
                        <?php endif; ?>
                        <?php if ((int)($pendingActions['hr_interviews_today'] ?? 0) > 0): ?>
                        <a href="<?= $bookingsUrl ?>" class="d-flex align-items-center justify-content-between p-3" style="border-bottom:1px solid #D9ECE5;text-decoration:none;color:#16212B;transition:background.12s;" onmouseover="this.style.background='#e8f9f817'" onmouseout="this.style.background='transparent'">
                            <div>
                                <strong><i class="fas fa-calendar-check" style="color:#0D8A90;"></i> Interviews Today</strong>
                                <small class="d-block text-muted">Track today's booked interviews and update status.</small>
                            </div>
                            <span class="status-pill"><?= (int)$pendingActions['hr_interviews_today'] ?></span>
                        </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div style="width:56px;height:56px;border-radius:50%;background:#E8F9F8;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#0D8A90;font-size:22px;"><i class="fas fa-check"></i></div>
                            <h6 style="font-weight:600;">All caught up!</h6>
                            <p class="text-muted small">No pending screenings or interviews right now.</p>
                            <a href="<?= $jobsUrl ?>" class="btn btn-outline-primary btn-sm">Review Jobs</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.recruiter-dashboard-main -->

<script>
(function() {
    'use strict';

    var interviewDates = <?= json_encode($interviewDates ?? []) ?>;
    var todayStr = '<?= date('Y-m-d') ?>';

    function buildCalendar(baseDate) {
        var year = baseDate.getFullYear();
        var month = baseDate.getMonth();

        var firstDay = new Date(year, month, 1);
        var lastDay = new Date(year, month + 1, 0);
        var startPad = firstDay.getDay(); // 0=Sun
        var totalDays = lastDay.getDate();

        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

        var html = '';

        // Header
        html += '<div class="dash-calendar-header">';
        html += '<h6>' + monthNames[month] + ' ' + year + '</h6>';
        html += '<div class="dash-calendar-nav">';
        html += '<button type="button" class="dash-cal-prev" aria-label="Previous month"><i class="fas fa-chevron-left"></i></button>';
        html += '<button type="button" class="dash-cal-next" aria-label="Next month"><i class="fas fa-chevron-right"></i></button>';
        html += '</div></div>';

        // Weekday headers
        html += '<div class="dash-calendar-weekdays">';
        ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(function(d) { html += '<span>' + d + '</span>'; });
        html += '</div>';

        // Grid
        html += '<div class="dash-calendar-grid">';

        // Padding cells from previous month
        var prevMonthDays = new Date(year, month, 0).getDate();
        for (var p = startPad - 1; p >= 0; p--) {
            html += '<div class="dash-calendar-day other-month">' + (prevMonthDays - p) + '</div>';
        }

        // Actual days
        for (var d = 1; d <= totalDays; d++) {
            var dateStr = year + '-' + String(month + 1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
            var classes = 'dash-calendar-day';
            if (dateStr === todayStr) classes += ' today';
            if (interviewDates[dateStr]) classes += ' has-interview';
            html += '<div class="' + classes + '" title="' + (interviewDates[dateStr] ? interviewDates[dateStr] + ' interview(s)' : '') + '">' + d + '</div>';
        }

        // Trailing cells for next month
        var used = startPad + totalDays;
        var remaining = (7 - (used % 7)) % 7;
        for (var t = 1; t <= remaining; t++) {
            html += '<div class="dash-calendar-day other-month">' + t + '</div>';
        }

        html += '</div>';

        var container = document.getElementById('dashCalendar');
        container.innerHTML = html;

        // Nav buttons
        container.querySelector('.dash-cal-prev').addEventListener('click', function(e) {
            e.stopPropagation();
            buildCalendar(new Date(year, month - 1, 1));
        });
        container.querySelector('.dash-cal-next').addEventListener('click', function(e) {
            e.stopPropagation();
            buildCalendar(new Date(year, month + 1, 1));
        });
    }

    buildCalendar(new Date());
})();
</script>

</div><!-- /.recruiter-dashboard-jobboard -->
<?= view('Layouts/recruiter_footer') ?>
