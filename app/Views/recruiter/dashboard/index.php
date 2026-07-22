<?= view('Layouts/recruiter_header', ['title' => 'Recruiter Dashboard', 'showHero' => false]) ?>
<style>
/* ================================================
   AI INTERVIEW FEATURE POPUP (recruiter dashboard)
================================================ */
.hm-modal-overlay {
    position: fixed; inset: 0; background: rgba(17,24,39,.55);
    display: none; align-items: center; justify-content: center;
    z-index: 9999; padding: 20px; backdrop-filter: blur(2px);
}
.hm-modal-overlay.active { display: flex; }

.hm-modal {
    background: #fff; border-radius: 20px; max-width: 500px; width: 100%;
    max-height: 90vh; overflow-y: auto; padding: 32px 32px 28px;
    box-shadow: 0 30px 80px rgba(0,0,0,.25);
    position: relative; animation: hmPop .25s ease;
}
@keyframes hmPop { from{opacity:0; transform:translateY(12px) scale(.98);} to{opacity:1; transform:none;} }

.hm-modal-close {
    position: absolute; top: 18px; right: 18px;
    width: 34px; height: 34px; border-radius: 9px; border: 1px solid #E5E7EB;
    background: #fff; display: flex; align-items: center; justify-content: center;
    color: #6b7280; cursor: pointer; transition: .15s; font-size: 14px;
}
.hm-modal-close:hover { background: #F9FAFB; color: #111827; }

.hm-modal-eyebrow { font-size: 13px; font-weight: 800; letter-spacing: .08em; color: #0D8A90; text-transform: uppercase; margin-bottom: 4px; }
.hm-modal-sub { font-size: 13px; color: #9CA3AF; margin-bottom: 16px; }
.hm-modal-divider { height: 2px; background: linear-gradient(90deg,#1FB7B5,#53B86C,#B5D84E); border-radius: 2px; margin-bottom: 22px; }

.hm-modal-title { font-size: 25px; font-weight: 600; color: #111827; margin-bottom: 14px; line-height: 1.28; }
.hm-modal-title span { background: linear-gradient(135deg,#1FB7B5,#53B86C,#B5D84E); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

/* Single feature spotlight card */
.hm-feature-spotlight {
    display: flex; align-items: flex-start; gap: 16px;
    padding: 20px 22px; border-radius: 14px;
    border: 1.5px solid #D9ECE5; background: linear-gradient(135deg,#F4FBFA 0%,#EEF9F2 100%);
    margin-bottom: 20px;
}
.hm-feature-spotlight-icon {
    width: 52px; height: 52px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff;
    background: linear-gradient(135deg,#1FB7B5,#53B86C);
}
.hm-feature-spotlight-text p { margin: 0; font-size: 14.5px; color: #4b5563; line-height: 1.5; }

.hm-feature-list {
    list-style: none; padding: 0; margin: 0 0 22px;
    display: flex; flex-direction: column; gap: 11px;
}
.hm-feature-list li {
    display: flex; align-items: flex-start; gap: 10px;
    font-size: 14px; color: #374151; line-height: 1.4;
}
.hm-feature-list li i {
    color: #53B86C; font-size: 13px; margin-top: 3px; flex-shrink: 0;
}
.hm-feature-list li strong { color: #111827; }

.hm-modal-cta-row { display: flex; gap: 12px; align-items: center; }
.hm-modal-cta-btn {
    flex: 1; text-align: center;
    background: linear-gradient(135deg,#1FB7B5,#53B86C);
    color: #fff !important; padding: 13px 20px;
    border-radius: 10px; font-weight: 600; font-size: 14.5px;
    text-decoration: none !important; transition: .2s; border: none; cursor: pointer;
}
.hm-modal-cta-btn:hover { opacity: .9; transform: translateY(-1px); color: #fff !important; }

.hm-intent-skip {
    display: block; text-align: center; margin-top: 16px;
    font-size: 13.5px; color: #9CA3AF; background: none; border: none;
    cursor: pointer; text-decoration: underline; outline: none;
    -webkit-tap-highlight-color: transparent;
}
.hm-intent-skip:focus { outline: none; box-shadow: none; }
.hm-intent-skip:hover { color: #6b7280; }

@media (max-width: 600px) {
    .hm-modal { padding: 26px 20px 22px; }
    .hm-modal-title { font-size: 21px; }
    .hm-modal-cta-row { flex-direction: column; }
}

@media (prefers-color-scheme: dark) {
    .hm-modal { background: #0B0B0B !important; border: 1px solid #23343A; }
    .hm-modal-close { background: #0B0B0B !important; border-color: #23343A !important; color: #94A3B8 !important; }
    .hm-modal-close:hover { background: #1B2A2F !important; color: #fff !important; }
    .hm-modal-title { color: #F8FAFC !important; }
    .hm-modal-sub { color: #7A8B96 !important; }
    .hm-feature-spotlight { background: linear-gradient(135deg,#162327 0%,#1B2A2F 100%) !important; border-color: #23343A !important; }
    .hm-feature-spotlight-text p { color: #94A3B8 !important; }
    .hm-feature-list li { color: #CBD5E1 !important; }
    .hm-feature-list li strong { color: #F8FAFC !important; }
    .hm-intent-skip { color: #7A8B96 !important; }
    .hm-intent-skip:hover { color: #CBD5E1 !important; }
}
</style>
<div class="recruiter-dashboard-jobboard">
<?php
$applicationsUrl = base_url('recruiter/jobs');
$jobsUrl         = base_url('recruiter/jobs');
$conversionUrl   = base_url('recruiter/dashboard') . '#conversion-metrics';
$bookingsUrl     = base_url('recruiter/slots/bookings');
$postJobUrl      = base_url('recruiter/post_job');
$slotsUrl        = base_url('recruiter/slots');

$formatRate = static function ($value): string {
    if ($value === null || $value === '') return 'N/A';
    return number_format((float) $value, 1) . '%';
};

$stageLabels = [
    'application_to_screening' => 'Application -> Screening',
    'screening_to_shortlist' => 'Screening -> Shortlist',
    'shortlist_to_hr_interview' => 'Shortlist -> HR Interview',
    'hr_interview_to_selection' => 'HR Interview -> Selection',
];

$conversionHeadline = [
    'label' => 'Pipeline Bottleneck',
    'value' => 'N/A',
    'caption' => 'Not enough data yet',
];

$availableRates = [];
foreach ($stageLabels as $key => $label) {
    if (isset($conversionMetrics[$key]) && $conversionMetrics[$key] !== null && $conversionMetrics[$key] !== '') {
        $availableRates[$key] = (float) $conversionMetrics[$key];
    }
}

if (!empty($availableRates)) {
    asort($availableRates);
    $bottleneckKey = array_key_first($availableRates);
    $bottleneckRate = $availableRates[$bottleneckKey];
    $conversionHeadline = [
        'label' => $bottleneckKey === 'hr_interview_to_selection' ? 'Selection Stage' : 'Pipeline Bottleneck',
        'value' => number_format($bottleneckRate, 1) . '%',
        'caption' => $bottleneckKey === 'hr_interview_to_selection' && $bottleneckRate <= 0
            ? 'No hires recorded yet'
            : $stageLabels[$bottleneckKey],
    ];
}
?>

<div class="container-fluid recruiter-dashboard-main">

    <!-- ── HEADER ── -->
    <div class="page-board-header page-board-header-tight recruiter-page-board-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <span class="section-eyebrow recruiter-dashboard-kicker d-block mb-1 recruiter-dashboard-kicker-style">Hiring analytics</span>
            <h1 class="recruiter-dashboard-title m-0 recruiter-dashboard-title-style">Recruitment Overview</h1>
            <p class="recruiter-dashboard-subtitle text-muted mb-0 recruiter-text-09">Review applications, job activity, and pipeline performance across your jobs.</p>
        </div>
        <?php if (empty($noJobs)): ?>
            <a href="<?= base_url('recruiter/dashboard/export-excel?type=overview') ?>" class="btn btn-outline-primary">
                <i class="fas fa-file-excel"></i> Export
            </a>
        <?php endif; ?>
    </div>


    <?php if (!empty($noJobs)): ?>
    <div class="card mb-4 p-5 text-center">
        <h4 class="mb-2 recruiter-weight-600">No jobs posted yet</h4>
        <p class="text-muted mb-3">Post your first job to start receiving applications and build your hiring pipeline.</p>
        <a href="<?= $postJobUrl ?>" class="btn btn-outline-primary"><i class="fas fa-plus"></i> Post Your First Job</a>
    </div>
    <?php endif; ?>

    <!-- ════════════════════ ROW 1: 4 STAT CARDS ════════════════════ -->
    <div class="row recruiter-dashboard-stat-row mb-4 recruiter-row-gap-16">
        <div class="col-xl-3 col-md-6">
            <a href="<?= $applicationsUrl ?>" class="text-decoration-none d-block">
                <div class="card recruiter-dashboard-stat-card h-100 recruiter-radius-16">
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
                <div class="card recruiter-dashboard-stat-card h-100 recruiter-radius-16">
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
                <div class="card recruiter-dashboard-stat-card h-100 recruiter-radius-16">
                    <div class="recruiter-dashboard-stat-body d-flex align-items-center">
                        <div class="stat-card-icon"><i class="fas fa-chart-pie"></i></div>
                        <div class="recruiter-dashboard-stat-copy">
                            <div class="stat-label"><?= esc($conversionHeadline['label']) ?></div>
                            <div class="stat-value"><?= esc($conversionHeadline['value']) ?></div>
                            <small class="text-muted"><?= esc($conversionHeadline['caption']) ?></small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="<?= $bookingsUrl ?>" class="text-decoration-none d-block">
                <div class="card recruiter-dashboard-stat-card h-100 recruiter-radius-16">
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
            <div class="card recruiter-pipeline-overview-card recruiter-radius-16">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="m-0 font-weight-bold recruiter-weight-600">Recruitment Pipeline</h6>
                        <small class="text-muted">Volume, screening, and conversion tracking</small>
                    </div>
                    <a href="<?= $jobsUrl ?>" class="btn btn-outline-primary btn-sm">Review Jobs</a>
                </div>
                <div class="card-body">
                    <?php $screeningCompleted = (int) ($funnel['screening_completed'] ?? 0); ?>
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
                            <small class="text-muted" title="Booked interview count can exceed current shortlisted count when candidates move into later statuses or book multiple rounds.">
                                <?= $funnel['shortlisted'] > 0 ? round(($funnel['interview_slot_booked'] / $funnel['shortlisted']) * 100, 1) : 0 ?>% from current shortlisted
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card recent-applications-card recruiter-radius-16">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="m-0 font-weight-bold recruiter-weight-600">Recent Applications</h6>
                    <a href="<?= $applicationsUrl ?>" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive recent-applications-table-wrap">
                        <table class="table table-hover mb-0 recent-applications-table">
                            <thead class="thead-light">
                                <tr><th class="pl-3">ID</th><th>Candidate</th><th>Job</th><th>Status</th><th class="pr-3">Applied</th></tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentApplications)): ?>
                                    <?php foreach ($recentApplications as $app): ?>
                                        <?php
                                            $statusValue = trim((string)($app['status'] ?? ''));
                                            $statusLabel = $statusValue !== ''
                                                ? ucwords(str_replace('_', ' ', $statusValue))
                                                : 'Needs Screening';
                                        ?>
                                        <tr class="recruiter-cursor-pointer" onclick="window.location='<?= base_url('recruiter/jobs/view/' . $app['job_id']) ?>#leaderboard'">
                                            <td class="pl-3 text-muted">#<?= $app['id'] ?></td>
                                            <td><strong><?= esc($app['candidate_name']) ?></strong></td>
                                            <td><?= esc($app['job_title']) ?></td>
                                            <td><span class="status-pill"><?= esc($statusLabel) ?></span></td>
                                            <td class="text-muted pr-3"><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
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
            <div class="card recruiter-radius-16">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold recruiter-weight-600">Action Center</h6>
                </div>
                <div class="card-body p-0">
                    <?php $hasActions = ((int)($pendingActions['pending_screening'] ?? 0) > 0) || ((int)($pendingActions['hr_interviews_today'] ?? 0) > 0) || ((int)($pendingActions['stale_jobs'] ?? 0) > 0) || ((int)($pendingActions['awaiting_replies'] ?? 0) > 0); ?>
                    <?php if ($hasActions): ?>
                        <?php if ((int)($pendingActions['pending_screening'] ?? 0) > 0): ?>
                        <a href="<?= $jobsUrl ?>" class="recruiter-action-center-link d-flex align-items-center justify-content-between p-3">
                            <div>
                                <strong><i class="fas fa-file-signature recruiter-brand-link"></i> Screen New Applications</strong>
                                <small class="d-block text-muted">Review and shortlist incoming candidates.</small>
                            </div>
                            <span class="badge recruiter-badge-screening"><?= (int)$pendingActions['pending_screening'] ?></span>
                        </a>
                        <?php endif; ?>
                        <?php if ((int)($pendingActions['stale_jobs'] ?? 0) > 0): ?>
                        <a href="<?= $jobsUrl ?>" class="recruiter-action-center-link d-flex align-items-center justify-content-between p-3">
                            <div>
                                <strong><i class="fas fa-exclamation-circle recruiter-danger-icon"></i> Jobs Stale With 0 Shortlisted</strong>
                                <small class="d-block text-muted">Open roles have applications older than 14 days but no shortlist yet.</small>
                            </div>
                            <span class="badge recruiter-badge-danger-soft"><?= (int)$pendingActions['stale_jobs'] ?></span>
                        </a>
                        <?php endif; ?>
                        <?php if ((int)($pendingActions['awaiting_replies'] ?? 0) > 0): ?>
                        <a href="<?= base_url('notifications') ?>" class="recruiter-action-center-link d-flex align-items-center justify-content-between p-3">
                            <div>
                                <strong><i class="fas fa-comments recruiter-warning-icon"></i> Candidates Awaiting Reply</strong>
                                <small class="d-block text-muted">Unread candidate replies need your attention.</small>
                            </div>
                            <span class="badge recruiter-badge-warning-soft"><?= (int)$pendingActions['awaiting_replies'] ?></span>
                        </a>
                        <?php endif; ?>
                        <?php if ((int)($pendingActions['hr_interviews_today'] ?? 0) > 0): ?>
                        <a href="<?= $bookingsUrl ?>" class="recruiter-action-center-link d-flex align-items-center justify-content-between p-3">
                            <div>
                                <strong><i class="fas fa-calendar-check recruiter-brand-link"></i> Interviews Today</strong>
                                <small class="d-block text-muted">Track today's booked interviews and update status.</small>
                            </div>
                            <span class="status-pill"><?= (int)$pendingActions['hr_interviews_today'] ?></span>
                        </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="recruiter-caught-up-icon"><i class="fas fa-check"></i></div>
                            <h6 class="recruiter-weight-600">All caught up!</h6>
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
            <div class="dash-calendar-legend"><span class="dash-calendar-dot"></span> Interview scheduled</div>

            <div class="interview-today-card">
                <div class="card-header py-2 recruiter-card-header-rounded">
                    <h6 class="m-0 recruiter-heading-small">
                        Today's Interviews
                        <?php if (count($todayInterviews) > 0): ?>
                            <span class="badge ml-1 recruiter-count-badge"><?= count($todayInterviews) ?></span>
                        <?php endif; ?>
                    </h6>
                </div>
                <?php if (count($todayInterviews) > 0): ?>
                    <?php foreach ($todayInterviews as $iv): ?>
                        <a href="<?= base_url('recruiter/slots/bookings') ?>" class="interview-today-item">
                            <span class="interview-time-badge"><?= date('h:i A', strtotime($iv['slot_time'])) ?></span>
                            <div>
                                <strong class="recruiter-text-085"><?= esc($iv['candidate_name']) ?></strong>
                                <small class="d-block text-muted recruiter-text-075"><?= esc($iv['job_title'] ?? 'Interview') ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-3 text-center text-muted recruiter-text-085">
                        <div class="mb-2"><i class="fas fa-check-circle recruiter-brand-icon"></i> No interviews scheduled today</div>
                        <a href="<?= $slotsUrl ?>" class="btn btn-outline-primary btn-sm">Suggest interview slots</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Actionable mini-card: pending screening ── -->
            <div class="card recruiter-radius-16" id="conversion-metrics">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold recruiter-weight-600">Conversion Metrics</h6>
                </div>
                <div class="card-body">
                    <div class="conversion-overall-card">
                        <div>
                            <span class="conversion-overall-label">Hire Conversion</span>
                            <span class="conversion-overall-value"><?= number_format((float)($conversionMetrics['overall_conversion'] ?? 0), 1) ?>%</span>
                        </div>
                        <div class="conversion-overall-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <table class="table table-sm mb-0 recruiter-text-085">
                        <thead><tr><th>Stage Transition</th><th class="text-right">Rate</th></tr></thead>
                        <tbody>
                            <?php
                            $stages = $stageLabels;
                            foreach ($stages as $key => $label): ?>
                                <tr>
                                    <td><?= $label ?></td>
                                    <td class="text-right"><span class="status-pill conversion-rate-pill"><?= $formatRate($conversionMetrics[$key] ?? null) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="font-weight-bold">
                                <td>Hire Conversion</td>
                                <td class="text-right"><span class="status-pill conversion-rate-pill"><?= number_format((float)($conversionMetrics['overall_conversion'] ?? 0), 1) ?>%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ AI INTERVIEW FEATURE POPUP (fires 7s after load) ═══════════════ -->
<div class="hm-modal-overlay" id="recruiterAiInterviewPopup">
  <div class="hm-modal" role="dialog" aria-modal="true" aria-labelledby="recruiterAiInterviewTitle">
    <button type="button" class="hm-modal-close" onclick="hmCloseRecruiterAiPopup()" aria-label="Close"><i class="fas fa-times"></i></button>

    <div class="hm-modal-eyebrow">Hiring Feature</div>
    <div class="hm-modal-sub">Screen candidates without the manual grind</div>
    <div class="hm-modal-divider"></div>

    <h3 class="hm-modal-title" id="recruiterAiInterviewTitle">Let <span>AI Interviews</span> do the first pass</h3>

    <div class="hm-feature-spotlight"> 
      <div class="hm-feature-spotlight-text">
        <p>Run structured, AI-led interview rounds that evaluate every applicant consistently — before they ever reach your calendar.</p>
      </div>
    </div>

    <ul class="hm-feature-list">
      <li><i class="fas fa-check-circle"></i> <span><strong>Aptitude testing</strong> — logic and reasoning checks scored automatically</span></li>
      <li><i class="fas fa-check-circle"></i> <span><strong>Technical &amp; coding rounds</strong> — role-specific problems evaluated in real time</span></li>
      <li><i class="fas fa-check-circle"></i> <span><strong>Communication assessment</strong> — clarity, confidence, and articulation scoring</span></li>
      <li><i class="fas fa-check-circle"></i> <span><strong>Faster shortlisting</strong> — ranked results so you only speak with top candidates</span></li>
    </ul>
 
  </div>
</div>
</div><!-- /.recruiter-dashboard-main -->
<script>
(function () {
  var STORAGE_KEY = 'hm_recruiter_ai_interview_popup_shown';
  var DELAY_MS = 7000;

  function showPopup() {
    if (sessionStorage.getItem(STORAGE_KEY)) return;
    var modal = document.getElementById('recruiterAiInterviewPopup');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  window.hmCloseRecruiterAiPopup = function () {
    var modal = document.getElementById('recruiterAiInterviewPopup');
    if (modal) {
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }
    sessionStorage.setItem(STORAGE_KEY, '1');
  };

  document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('recruiterAiInterviewPopup');
    if (modal) {
      modal.addEventListener('click', function (e) {
        if (e.target === modal) window.hmCloseRecruiterAiPopup();
      });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') window.hmCloseRecruiterAiPopup();
    });
    setTimeout(showPopup, DELAY_MS);
  });
})();
</script>
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
