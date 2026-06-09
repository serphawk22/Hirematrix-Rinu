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
    $rateClass = static function ($value, float $goodThreshold): string {
        if ($value === null || $value === '') return 'secondary';
        return ((float) $value) >= $goodThreshold ? 'success' : 'warning';
    };
    ?>

    <style>
        /* ══════════════════════════════════════════════
           RESET & BASE
        ══════════════════════════════════════════════ */
        .recruiter-dashboard-jobboard,
        .hm-page-content {
            background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%) !important;
            min-height: 100vh;
        }

        body.dark .recruiter-dashboard-jobboard,
        body.dark .hm-page-content {
            background: linear-gradient(135deg, #0F1C20 0%, #162327 100%) !important;
        }

        /* ── Full-width container ── */
        .recruiter-dashboard-main {
            max-width: 100% !important;
            padding: 24px 32px !important;
        }

        /* ══════════════════════════════════════════════
           CARDS — no shadow, clean borders
        ══════════════════════════════════════════════ */
        .card,
        .card.shadow,
        .recruiter-dashboard-panel-card,
        .recruiter-pipeline-card,
        .recruiter-stat-card,
        .recruiter-stat-card.shadow {
            box-shadow: none !important;
            background: #FFFFFF !important;
            border: 1px solid #D9ECE5 !important;
            border-radius: 10px !important;
        }

        body.dark .card,
        body.dark .card.shadow,
        body.dark .recruiter-dashboard-panel-card,
        body.dark .recruiter-pipeline-card,
        body.dark .recruiter-stat-card,
        body.dark .recruiter-action-center-empty {
            background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important;
            border: 1px solid #23343A !important;
        }

        /* ── Card headers ── */
        .card-header,
        .recruiter-section-header {
            background: #FFFFFF !important;
            border-bottom: 1px solid #D9ECE5 !important;
            border-radius: 10px 10px 0 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        body.dark .card-header,
        body.dark .recruiter-section-header {
            background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important;
            border-bottom: 1px solid #23343A !important;
        }

        body.dark .card-header h6,
        body.dark .card-header .font-weight-bold,
        body.dark h6.text-primary,
        body.dark .font-weight-bold.text-primary {
            color: #1FB7B5 !important;
        }

        /* ══════════════════════════════════════════════
           STAT CARDS
        ══════════════════════════════════════════════ */
        .recruiter-stat-card {
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .recruiter-stat-card:hover {
            transform: translateY(-2px);
            border-color: #1FB7B5 !important;
        }

        /* Hide decorative ::before */
        .recruiter-stat-card.recruiter-stat-applications::before,
        .recruiter-stat-card.recruiter-stat-openjobs::before,
        .recruiter-stat-card.recruiter-stat-conversion::before,
        .recruiter-stat-card.recruiter-stat-bookings::before {
            display: none !important;
        }

        /* Metric title */
        .recruiter-dashboard-main .recruiter-stat-card .dashboard-metric-title,
        .recruiter-dashboard-main .recruiter-stat-card .text-primary.dashboard-metric-title,
        .recruiter-dashboard-main .recruiter-stat-card .text-info.dashboard-metric-title,
        .recruiter-dashboard-main .recruiter-stat-card .text-warning.dashboard-metric-title,
        .recruiter-dashboard-main .recruiter-stat-card .text-success.dashboard-metric-title {
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            color: #94A3B8 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.04em !important;
            margin-bottom: 6px !important;
            display: block !important;
        }

        body.dark .recruiter-dashboard-main .recruiter-stat-card .dashboard-metric-title,
        body.dark .recruiter-dashboard-main .recruiter-stat-card .text-primary.dashboard-metric-title,
        body.dark .recruiter-dashboard-main .recruiter-stat-card .text-info.dashboard-metric-title,
        body.dark .recruiter-dashboard-main .recruiter-stat-card .text-warning.dashboard-metric-title {
            color: #7A8B96 !important;
        }

        /* Large number */
        .recruiter-dashboard-main .recruiter-stat-card .h4,
        .recruiter-dashboard-main .recruiter-stat-card .h4.text-gray-800 {
            font-size: 2rem !important;
            font-weight: 700 !important;
            color: #16212B !important;
            line-height: 1.15 !important;
        }

        body.dark .recruiter-dashboard-main .recruiter-stat-card .h4 {
            color: #F8FAFC !important;
        }

        /* Sub-label */
        .recruiter-dashboard-main .recruiter-stat-card small.text-muted {
            font-size: 0.78rem !important;
            color: #94A3B8 !important;
            font-weight: 400 !important;
        }

        body.dark .recruiter-dashboard-main .recruiter-stat-card small.text-muted {
            color: #7A8B96 !important;
        }

        /* All icons inside stat cards → teal */
        .recruiter-dashboard-main .recruiter-stat-card .col-auto i,
        .recruiter-dashboard-main .recruiter-stat-card i.fa-2x,
        .recruiter-dashboard-main .recruiter-stat-card .text-gray-300,
        .recruiter-stat-card i.fas,
        .recruiter-stat-card i.far,
        .recruiter-stat-card .fa-2x,
        .recruiter-stat-card .text-primary,
        .recruiter-stat-card .text-info,
        .recruiter-stat-card .text-warning,
        .recruiter-stat-card .text-success,
        .recruiter-stat-card .text-secondary {
            color: #0D8A90 !important;
        }

        body.dark .recruiter-dashboard-main .recruiter-stat-card .col-auto i,
        body.dark .recruiter-dashboard-main .recruiter-stat-card i.fa-2x {
            color: #1FB7B5 !important;
        }

        /* Stat icon bubble */
        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #E8F9F8;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        body.dark .stat-card-icon {
            background: #1B3035;
        }

        /* Stat link wrapper */
        .dashboard-stat-link {
            text-decoration: none !important;
            color: inherit !important;
            display: block;
        }

        /* ══════════════════════════════════════════════
           PIPELINE
        ══════════════════════════════════════════════ */
        .recruiter-pipeline-stats .pipeline-stat h3 {
            color: #16212B;
            font-weight: 700;
            font-size: 1.6rem;
        }

        body.dark .recruiter-pipeline-stats .pipeline-stat h3 {
            color: #F8FAFC !important;
        }

        body.dark .recruiter-pipeline-stats .text-muted {
            color: #7A8B96 !important;
        }

        .recruiter-dashboard-main .recruiter-pipeline-stats .stat-icon {
            background: #E8F9F8 !important;
            width: 52px !important;
            height: 52px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 auto 10px !important;
        }

        .recruiter-dashboard-main .recruiter-pipeline-stats .stat-icon[class*="bg-"] {
            background: #E8F9F8 !important;
        }

        .recruiter-dashboard-main .recruiter-pipeline-stats .stat-icon i,
        .recruiter-dashboard-main .recruiter-pipeline-stats .stat-icon .fas {
            color: #0D8A90 !important;
        }

        body.dark .recruiter-dashboard-main .recruiter-pipeline-stats .stat-icon,
        body.dark .recruiter-dashboard-main .recruiter-pipeline-stats .stat-icon[class*="bg-"] {
            background: #1B3035 !important;
        }

        body.dark .recruiter-dashboard-main .recruiter-pipeline-stats .stat-icon i,
        body.dark .recruiter-dashboard-main .recruiter-pipeline-stats .stat-icon .fas {
            color: #1FB7B5 !important;
        }

        /* Pipeline conversion text */
        .recruiter-dashboard-main .recruiter-pipeline-stats .text-success,
        .recruiter-dashboard-main .recruiter-pipeline-stats small.text-success {
            color: #0D8A90 !important;
        }

        body.dark .recruiter-dashboard-main .recruiter-pipeline-stats .text-success,
        body.dark .recruiter-dashboard-main .recruiter-pipeline-stats small.text-success {
            color: #1FB7B5 !important;
        }

        /* Pipeline divider arrows between stages */
        .pipeline-connector {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #D9ECE5;
            font-size: 1.2rem;
            padding-top: 10px;
        }

        body.dark .pipeline-connector {
            color: #23343A;
        }

        /* Pipeline note */
        .recruiter-pipeline-note,
        .alert-light {
            background: #EDF8F5 !important;
            border: 1px solid #D9ECE5 !important;
            color: #64748B !important;
            border-radius: 8px !important;
        }

        body.dark .recruiter-pipeline-note,
        body.dark .alert-light,
        body.dark .recruiter-alert {
            background: #1B2A2F !important;
            border: 1px solid #23343A !important;
            color: #7A8B96 !important;
        }

        /* ══════════════════════════════════════════════
           TABLES
        ══════════════════════════════════════════════ */
        .table th,
        .table td {
            color: #16212B;
            border-color: #D9ECE5 !important;
            vertical-align: middle;
        }

        .thead-light th {
            background: #EDF8F5 !important;
            color: #64748B !important;
            border-color: #D9ECE5 !important;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        body.dark .table th,
        body.dark .table td {
            color: #94A3B8 !important;
            background: transparent !important;
            border-color: #23343A !important;
        }

        body.dark .thead-light th,
        body.dark thead th {
            background: #162327 !important;
            color: #7A8B96 !important;
            border-color: #23343A !important;
        }

        body.dark .table-hover tbody tr:hover td {
            background: rgba(31, 183, 181, 0.04) !important;
        }

        /* ══════════════════════════════════════════════
           STATUS PILL
        ══════════════════════════════════════════════ */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 600;
            background: rgba(13, 138, 144, 0.08);
            color: #0D8A90;
            border: 1px solid rgba(13, 138, 144, 0.15);
            white-space: nowrap;
        }

        body.dark .status-pill {
            background: rgba(31, 183, 181, 0.08);
            color: #1FB7B5;
            border-color: rgba(31, 183, 181, 0.15);
        }

        /* ══════════════════════════════════════════════
           ACTION CENTER
        ══════════════════════════════════════════════ */
        .action-item-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid #D9ECE5;
            color: #16212B;
            text-decoration: none;
            transition: background 0.15s ease;
        }

        .action-item-link:last-child {
            border-bottom: none;
        }

        .action-item-link:hover {
            background: #EDF8F5;
            color: #16212B;
        }

        body.dark .action-item-link {
            border-bottom-color: #23343A;
            color: #94A3B8;
        }

        body.dark .action-item-link:hover {
            background: rgba(31, 183, 181, 0.04);
            color: #F8FAFC;
        }

        body.dark .action-item-label small.text-muted {
            color: #7A8B96 !important;
        }

        /* Empty state */
        .recruiter-action-center-empty {
            padding: 36px 20px;
            text-align: center;
            border: none !important;
            background: transparent !important;
        }

        .recruiter-action-center-empty h6 {
            color: #16212B;
            font-weight: 600;
        }

        body.dark .recruiter-action-center-empty h6 {
            color: #F8FAFC !important;
        }

        .recruiter-action-center-empty-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #E8F9F8;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 20px;
        }

        .recruiter-action-center-empty-icon i,
        .recruiter-action-center-empty-icon .fas,
        .recruiter-action-center-empty-icon .fa-check {
            color: #0D8A90 !important;
        }

        body.dark .recruiter-action-center-empty-icon {
            background: #1B3035;
        }

        body.dark .recruiter-action-center-empty-icon i,
        body.dark .recruiter-action-center-empty-icon .fas {
            color: #1FB7B5 !important;
        }

        /* ══════════════════════════════════════════════
           CONVERSION METRICS
        ══════════════════════════════════════════════ */
        .conversion-summary-row {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .conversion-summary-card {
            background: #EDF8F5;
            border: 1px solid #D9ECE5;
            border-radius: 10px;
            padding: 16px 24px;
            min-width: 160px; font-weight:500 !important;
        }

        body.dark .conversion-summary-card {
            background: #1B2A2F !important;
            border-color: #23343A !important;
        }

        .conversion-summary-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
            display: block;
            margin-bottom: 4px;
        }

        body.dark .conversion-summary-label { color: #7A8B96 !important; }

        .conversion-summary-value {
            font-size: 1.2rem !important;
            font-weight: 500 !important;
            color: #16212B;
            line-height: 1.1;
        }

        body.dark .conversion-summary-value { color: #F8FAFC !important; }

        .conversion-summary-note  {
            color: #16212B;
            font-size: 14px;
        }

        body.dark .conversion-summary-note   { color: #94A3B8 !important; }
        body.dark .conversion-summary-note p.text-muted { color: #7A8B96 !important; }

        .conversion-table thead th {
            background: #EDF8F5 !important;
            color: #64748B !important;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-color: #D9ECE5 !important;
        }

        body.dark .conversion-table thead th {
            background: #162327 !important;
            color: #7A8B96 !important;
            border-color: #23343A !important;
        }

        body.dark .conversion-table td {
            color: #94A3B8 !important;
            border-color: #23343A !important;
        }

        body.dark .conversion-table tr.font-weight-bold td {
            color: #F8FAFC !important;
        }

        /* ══════════════════════════════════════════════
           BUTTONS
        ══════════════════════════════════════════════ */
        .btn-primary,
        .btn-outline-primary {
            background: transparent !important;
            border: 1.5px solid #1FB7B5 !important;
            color: #1FB7B5 !important;
            padding: 7px 18px;
            border-radius: 6px !important;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary:hover,
        .btn-primary:focus,
        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background: #1FB7B5 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
        }

        /* ══════════════════════════════════════════════
           GENERAL DARK MODE HELPERS
        ══════════════════════════════════════════════ */
        body.dark .text-muted { color: #7A8B96 !important; }

        body.dark .card-header,
        body.dark .recruiter-leaderboard-card,
        body.dark .recruiter-filter-card,
        body.dark .recruiter-alert,
        body.dark .alert-light {
            background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important;
            border: 1px solid #23343A !important;
        }

        /* ══════════════════════════════════════════════
           SECTION LABEL
        ══════════════════════════════════════════════ */
        .section-eyebrow {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94A3B8;
            margin-bottom: 12px;
            display: block;
        }

        body.dark .section-eyebrow { color: #4A5C62; }
    </style>

    <div class="container-fluid recruiter-dashboard-main">

        <?php if (!empty($noJobs)): ?>
        <div class="card mb-4 recruiter-dashboard-panel-card">
            <div class="card-body p-4 text-center">
                <h4 class="mb-2">No jobs posted yet</h4>
                <p class="text-muted mb-3">Post your first job to start receiving applications and build your hiring pipeline.</p>
                <a href="<?= $postJobUrl ?>" class="btn btn-outline-primary">
                    <i class="fas fa-plus"></i> Post Your First Job
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($noJobs) && array_sum($pendingActions) > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
             <i class="fas fa-exclamation-triangle"></i> Pending Actions: 
            <?php if ($pendingActions['pending_screening'] > 0): ?>
                <span class="badge badge-warning"><?= $pendingActions['pending_screening'] ?></span> applications to screen,
            <?php endif; ?>
            <?php if ($pendingActions['hr_interviews_today'] > 0): ?>
                <span class="badge badge-primary"><?= $pendingActions['hr_interviews_today'] ?></span> HR interviews today
            <?php endif; ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        <?php endif; ?>

        <!-- ── ROW 1: 4 Stat Cards ── -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <a href="<?= $applicationsUrl ?>" class="dashboard-stat-link">
                    <div class="card recruiter-stat-card recruiter-stat-applications h-100">
                        <div class="card-body d-flex align-items-center gap-3" style="gap:16px;">
                            <div class="stat-card-icon">
                                <i class="fas fa-file-alt fa-lg"></i>
                            </div>
                            <div>
                                <div class="dashboard-metric-title text-primary">Total Applications</div>
                                <div class="h4 mb-0 text-gray-800" style="font-weight:500 !important;font-size:1.2rem !important;"><?= number_format($funnel['total_applications']) ?></div>
                                <small class="text-muted">Across all active jobs</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <a href="<?= $jobsUrl ?>" class="dashboard-stat-link">
                    <div class="card recruiter-stat-card recruiter-stat-openjobs h-100">
                        <div class="card-body d-flex align-items-center" style="gap:16px;">
                            <div class="stat-card-icon">
                                <i class="fas fa-briefcase fa-lg"></i>
                            </div>
                            <div>
                                <div class="dashboard-metric-title text-info">Open Jobs</div>
                                <div class="h4 mb-0 text-gray-800"  style="font-weight:500 !important;font-size:1.2rem !important;"><?= $jobStats['active_jobs'] ?></div>
                                <small class="text-muted">Currently hiring</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <a href="<?= $conversionUrl ?>" class="dashboard-stat-link">
                    <div class="card recruiter-stat-card recruiter-stat-conversion h-100">
                        <div class="card-body d-flex align-items-center" style="gap:16px;">
                            <div class="stat-card-icon">
                                <i class="fas fa-chart-pie fa-lg"></i>
                            </div>
                            <div>
                                <div class="dashboard-metric-title text-warning">Conversion Rate</div>
                                <div class="h4 mb-0 text-gray-800" style="font-weight:500 !important;font-size:1.2rem !important;"><?= $conversionMetrics['overall_conversion'] ?? 0 ?>%</div>
                                <small class="text-muted">Pipeline efficiency</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <a href="<?= $bookingsUrl ?>" class="dashboard-stat-link">
                    <div class="card recruiter-stat-card recruiter-stat-bookings h-100">
                        <div class="card-body d-flex align-items-center" style="gap:16px;">
                            <div class="stat-card-icon">
                                <i class="fas fa-calendar-check fa-lg"></i>
                            </div>
                            <div>
                                <div class="dashboard-metric-title text-info">Interview Bookings</div>
                                <div class="h4 mb-0 text-gray-800"  style="font-weight:500 !important;font-size:1.2rem !important;"><?= number_format($jobStats['interview_bookings'] ?? 0) ?></div>
                                <small class="text-muted">HR rounds scheduled</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- ── ROW 2: Pipeline (full width) ── -->
        <div class="row mb-4" id="conversion-metrics">
            <div class="col-12">
                <div class="card recruiter-dashboard-panel-card recruiter-pipeline-card">
                    <div class="card-header py-3 recruiter-section-header">
                        <div>
                            <h6 class="m-0 font-weight-bold text-primary">  Recruitment Pipeline</h6>
                            <small class="text-muted">Volume, screening progress, and where the process slows down.</small>
                        </div>
                        <a href="<?= $jobsUrl ?>" class="btn btn-outline-primary btn-sm">Review Jobs</a>
                    </div>
                    <div class="card-body">
                        <?php $screeningCompleted = (int) ($funnel['shortlisted'] ?? 0) + (int) ($funnel['rejected'] ?? 0); ?>
                        <div class="row text-center recruiter-pipeline-stats align-items-center">
                            <div class="col-md col-6 mb-3 mb-md-0">
                                <div class="pipeline-stat">
                                    <div class="stat-icon bg-primary"><i class="fas fa-inbox"></i></div>
                                    <h4 style="font-weight:500 !important"><?= number_format($funnel['total_applications']) ?></h4>
                                    <p class="text-muted mb-0 small">Applications</p>
                                </div>
                            </div>
                            <div class="col-md-auto d-none d-md-flex pipeline-connector"><i class="fas fa-chevron-right"></i></div>
                            <div class="col-md col-6 mb-3 mb-md-0">
                                <div class="pipeline-stat">
                                    <div class="stat-icon bg-info"><i class="fas fa-cogs"></i></div>
                                    <h4 style="font-weight:500 !important"><?= number_format($screeningCompleted) ?></h4>
                                    <p class="text-muted mb-0 small">Screening Completed</p>
                                    <small class="text-success"><i class="fas fa-arrow-right"></i> <?= $funnel['total_applications'] > 0 ? round(($screeningCompleted / $funnel['total_applications']) * 100, 1) : 0 ?>% from applications</small>
                                </div>
                            </div>
                            <div class="col-md-auto d-none d-md-flex pipeline-connector"><i class="fas fa-chevron-right"></i></div>
                            <div class="col-md col-6">
                                <div class="pipeline-stat">
                                    <div class="stat-icon bg-success"><i class="fas fa-star"></i></div>
                                    <h4 style="font-weight:500 !important"><?= number_format($funnel['shortlisted']) ?></h4>
                                    <p class="text-muted mb-0 small">Shortlisted</p>
                                    <small class="text-success"><i class="fas fa-arrow-right"></i> <?= $screeningCompleted > 0 ? round(($funnel['shortlisted'] / $screeningCompleted) * 100, 1) : 0 ?>% from screened</small>
                                </div>
                            </div>
                            <div class="col-md-auto d-none d-md-flex pipeline-connector"><i class="fas fa-chevron-right"></i></div>
                            <div class="col-md col-6">
                                <div class="pipeline-stat">
                                    <div class="stat-icon bg-warning"><i class="fas fa-calendar-check"></i></div>
                                    <h4 style="font-weight:500 !important"><?= number_format($funnel['interview_slot_booked']) ?></h4>
                                    <p class="text-muted mb-0 small">HR Interviews</p>
                                    <small class="text-success"><i class="fas fa-arrow-right"></i> <?= $funnel['shortlisted'] > 0 ? round(($funnel['interview_slot_booked'] / $funnel['shortlisted']) * 100, 1) : 0 ?>% from shortlisted</small>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-light mt-3 mb-0 recruiter-pipeline-note">
                            <small class="text-muted"><i class="fas fa-info-circle"></i> Each stage shows conversion rate from the previous stage.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── ROW 3: Recent Applications (left) + Conversion Metrics (right) ── -->
        <div class="row mb-4">
            <!-- Recent Applications -->
            <div class="col-xl-7 col-lg-6 mb-4 mb-lg-0">
                <div class="card recruiter-dashboard-panel-card h-100">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">  Recent Applications</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="pl-3">ID</th>
                                        <th>Candidate</th>
                                        <th>Job</th>
                                        <th>Status</th>
                                        <th>Applied</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentApplications)): ?>
                                        <?php foreach ($recentApplications as $app): ?>
                                            <tr onclick="window.location='<?= base_url('recruiter/jobs/' . $app['job_id'] . '/leaderboard') ?>'" style="cursor:pointer;">
                                                <td class="pl-3 text-muted">#<?= $app['id'] ?></td>
                                                <td> <?= esc($app['candidate_name']) ?> </td>
                                                <td><?= esc($app['job_title']) ?></td>
                                                <td>
                                                    <span class="status-pill"><?= ucwords(str_replace('_', ' ', $app['status'])) ?></span>
                                                </td>
                                                <td class="text-muted"><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No recent applications</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conversion Metrics -->
            <div class="col-xl-5 col-lg-6">
                <div class="card recruiter-dashboard-panel-card h-100">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-funnel-dollar mr-1"></i> Conversion Metrics</h6>
                    </div>
                    <div class="card-body">
                        <div class="conversion-summary-card mb-3">
                            <span class="conversion-summary-label">Overall Conversion</span>
                            <div class="conversion-summary-value"><?= number_format((float) ($conversionMetrics['overall_conversion'] ?? 0), 1) ?>%</div>
                            <small class="text-muted">Pipeline efficiency</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm conversion-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Stage Transition</th>
                                        <th class="text-right">Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Application → Screening</td>
                                        <td class="text-right"><span class="status-pill"><?= $formatRate($conversionMetrics['application_to_screening'] ?? null) ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Screening → Shortlist</td>
                                        <td class="text-right"><span class="status-pill"><?= $formatRate($conversionMetrics['screening_to_shortlist'] ?? null) ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Shortlist → HR Interview</td>
                                        <td class="text-right"><span class="status-pill"><?= $formatRate($conversionMetrics['shortlist_to_hr_interview'] ?? null) ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>HR Interview → Selection</td>
                                        <td class="text-right"><span class="status-pill"><?= $formatRate($conversionMetrics['hr_interview_to_selection'] ?? null) ?></span></td>
                                    </tr>
                                    <tr class="font-weight-bold">
                                        <td style="font-weight:500 !important">Overall Conversion</td>
                                        <td class="text-right"><span class="status-pill"><?= number_format((float) ($conversionMetrics['overall_conversion'] ?? 0), 1) ?>%</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── ROW 4: Action Center (full width) ── -->
        <?php if (empty($noJobs)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card recruiter-dashboard-panel-card recruiter-pipeline-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"> Action Center</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php $hasActionCenterItems = ((int) ($pendingActions['pending_screening'] ?? 0) > 0) || ((int) ($pendingActions['hr_interviews_today'] ?? 0) > 0); ?>
                        <?php if ($hasActionCenterItems): ?>
                            <?php if ((int) ($pendingActions['pending_screening'] ?? 0) > 0): ?>
                                <a href="<?= $jobsUrl ?>" class="action-item-link">
                                    <div class="action-item-label">
                                       Screen New Applications 
                                        <small class="text-muted d-block">Review and shortlist incoming candidates.</small>
                                    </div>
                                    <span class="badge badge-warning"><?= (int) ($pendingActions['pending_screening'] ?? 0) ?></span>
                                </a>
                            <?php endif; ?>
                            <?php if ((int) ($pendingActions['hr_interviews_today'] ?? 0) > 0): ?>
                                <a href="<?= $bookingsUrl ?>" class="action-item-link">
                                    <div class="action-item-label">
                                        Interviews Today 
                                        <small class="text-muted d-block">Track today's booked interviews and status.</small>
                                    </div>
                                    <span class="status-pill"><?= (int) ($pendingActions['hr_interviews_today'] ?? 0) ?></span>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="recruiter-action-center-empty">
                                 
                                <h6 class="mb-2" style="font-weight:500 !important">All caught up!</h6>
                                <p class="text-muted mb-3 small">No pending screenings or interviews right now.</p>
                                <a href="<?= $jobsUrl ?>" class="btn btn-outline-primary btn-sm">
                                 Review Jobs
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.recruiter-dashboard-main -->
</div><!-- /.recruiter-dashboard-jobboard -->

<?= view('Layouts/recruiter_footer') ?>