                        <?= view('Layouts/recruiter_header', ['title' => 'Recruiter Dashboard', 'showHero' => false]) ?>

 <div class="recruiter-dashboard-jobboard">
    <?php
    $applicationsUrl = base_url('recruiter/jobs');
    $jobsUrl = base_url('recruiter/jobs');
    $conversionUrl = base_url('recruiter/dashboard') . '#conversion-metrics';
    $bookingsUrl = base_url('recruiter/slots/bookings');
    $postJobUrl = base_url('recruiter/post_job');
    $slotsUrl = base_url('recruiter/slots');
    $leaderboardUrl = base_url('recruiter/dashboard/leaderboard');
    $formatRate = static function ($value): string {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return number_format((float) $value, 1) . '%';
    };
    $rateClass = static function ($value, float $goodThreshold): string {
        if ($value === null || $value === '') {
            return 'secondary';
        }

        return ((float) $value) >= $goodThreshold ? 'success' : 'warning';
    };
    ?>
    <section class="recruiter-dashboard-hero">
        <div class="container">
            <div class="recruiter-dashboard-hero-grid">
                <div class="recruiter-dashboard-hero-copy">
                    <span class="page-board-kicker"><i class="fas fa-tachometer-alt"></i> Recruiter dashboard</span>
                    <h1 class="page-board-title" style="margin-bottom:0.5rem;">Find Your Next Great Hire</h1>
                    <p class="page-board-subtitle">
                        A quick view of open roles, active applications, and what needs attention today.
                    </p>

                </div>

              <div class="recruiter-dashboard-hero-aside">
    <div class="row g-2">
        <div class="col-md-4 col-12">
            <a href="<?= $postJobUrl ?>" class="btn btn-primary w-100">
                <i class="fas fa-plus"></i> Post Job
            </a>
        </div>

        <div class="col-md-4 col-12">
            <a href="<?= base_url('recruiter/jobs') ?>" class="btn btn-primary w-100">
                <i class="fas fa-briefcase"></i> Manage Job
            </a>
        </div>

        <div class="col-md-4 col-12">
            <a href="<?= base_url('recruiter/candidates') ?>" class="btn btn-primary w-100">
                <i class="fas fa-arrow-trend-up"></i> <?= number_format((int) $funnel['total_applications']) ?> Candidates
            </a>
        </div>
    </div>
</div>
            </div>
        </div>
    </section>

    <div class="container recruiter-dashboard-main">
        <?php if (!empty($noJobs)): ?>
        <div class="card shadow mb-4 recruiter-dashboard-panel-card">
            <div class="card-body p-4 text-center">
                <h4 class="mb-2">No jobs posted yet</h4>
                <p class="text-muted mb-3">Post your first job to start receiving applications and build your hiring pipeline.</p>
                <a href="<?= $postJobUrl ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Post Your First Job
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pending Actions Alert -->
        <?php if (empty($noJobs) && array_sum($pendingActions) > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle"></i> Pending Actions:</strong>
            <?php if ($pendingActions['pending_screening'] > 0): ?>
                <span class="badge badge-warning"><?= $pendingActions['pending_screening'] ?></span> applications to screen, 
            <?php endif; ?>
            <?php if ($pendingActions['hr_interviews_today'] > 0): ?>
                <span class="badge badge-primary"><?= $pendingActions['hr_interviews_today'] ?></span> HR interviews today
            <?php endif; ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Quick Stats Row -->
        <div class="row mb-4">
        <div class="col-xl col-md-6 mb-3">
            <a href="<?= $applicationsUrl ?>" class="dashboard-stat-link">
            <div class="card recruiter-stat-card recruiter-stat-applications shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1 dashboard-metric-title">
                                Total Applications
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($funnel['total_applications']) ?>
                            </div>
                            <small class="text-muted d-block">Across all active jobs</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>

        <div class="col-xl col-md-6 mb-3">
            <a href="<?= $jobsUrl ?>" class="dashboard-stat-link">
            <div class="card recruiter-stat-card recruiter-stat-openjobs shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1 dashboard-metric-title">
                                Open Jobs
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                <?= $jobStats['active_jobs'] ?>
                            </div>
                            <small class="text-muted d-block">Currently hiring</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>

        <div class="col-xl col-md-6 mb-3">
            <a href="<?= $conversionUrl ?>" class="dashboard-stat-link">
            <div class="card recruiter-stat-card recruiter-stat-conversion shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1 dashboard-metric-title">
                                Conversion Rate
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                <?= $conversionMetrics['overall_conversion'] ?? 0 ?>%
                            </div>
                            <small class="text-muted d-block">Pipeline efficiency</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>

        <div class="col-xl col-md-6 mb-3">
            <a href="<?= $bookingsUrl ?>" class="dashboard-stat-link">
            <div class="card recruiter-stat-card recruiter-stat-bookings shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1 dashboard-metric-title">
                                Interview Bookings
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($jobStats['interview_bookings'] ?? 0) ?>
                            </div>
                            <small class="text-muted d-block">HR rounds scheduled</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>
        </div>

         <div class="row recruiter-pipeline-row" id="conversion-metrics">
        <div class="col-12 mb-4">
            <div class="card shadow h-100 recruiter-dashboard-panel-card recruiter-pipeline-card">
                <div class="card-header py-3 recruiter-section-header">
                    <div>
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-bar"></i> Recruitment Pipeline</h6>
                        <small class="text-muted">A quick read on volume, screening progress, and where the process slows down.</small>
                    </div>
                    <a href="<?= $jobsUrl ?>" class="btn btn-outline-primary btn-sm">Review jobs</a>
                </div>
                <div class="card-body">
                    <div class="row text-center recruiter-pipeline-stats">
                        <div class="col-md-3 col-6 mb-3 mb-md-0">
                            <div class="pipeline-stat">
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h3><?= number_format($funnel['total_applications']) ?></h3>
                                <p class="text-muted mb-0">Applications</p>
                            </div>
                        </div>
                        <?php $screeningCompleted = (int) ($funnel['shortlisted'] ?? 0) + (int) ($funnel['rejected'] ?? 0); ?>
                        <div class="col-md-3 col-6 mb-3 mb-md-0">
                            <div class="pipeline-stat">
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <h3><?= number_format($screeningCompleted) ?></h3>
                                <p class="text-muted mb-0">Screening Completed</p>
                                <small class="text-success"><i class="fas fa-arrow-right"></i> <?= $funnel['total_applications'] > 0 ? round(($screeningCompleted / $funnel['total_applications']) * 100, 1) : 0 ?>% from applications</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="pipeline-stat">
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-star"></i>
                                </div>
                                <h3><?= number_format($funnel['shortlisted']) ?></h3>
                                <p class="text-muted mb-0">Shortlisted</p>
                                <small class="text-success"><i class="fas fa-arrow-right"></i> <?= $screeningCompleted > 0 ? round(($funnel['shortlisted'] / $screeningCompleted) * 100, 1) : 0 ?>% from screened</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="pipeline-stat">
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h3><?= number_format($funnel['interview_slot_booked']) ?></h3>
                                <p class="text-muted mb-0">HR Interviews</p>
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

         <!-- Recent Applications -->
        <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow recruiter-dashboard-panel-card blue-animated-card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Applications</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Candidate</th>
                                    <th>Job</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                    <!-- <th>Actions</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentApplications)): ?>
                                    <?php foreach ($recentApplications as $app): ?>
                                        <tr onclick="window.location='<?= base_url('recruiter/jobs/' . $app['job_id'] . '/leaderboard') ?>'" style="cursor:pointer;">
                                            <td>#<?= $app['id'] ?></td>
                                            <td>
                                                <strong><?= esc($app['candidate_name']) ?></strong>
                                            </td>
                                            <td><?= esc($app['job_title']) ?></td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'shortlisted' => 'info',
                                                    'selected' => 'success',
                                                    'rejected' => 'danger'
                                                ];
                                                $color = $statusColors[$app['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?= $color ?>">
                                                    <?= ucwords(str_replace('_', ' ', $app['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                            <!-- <td>
                                                <a href="<?= base_url('recruiter/applications/view/' . $app['id']) ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td> -->
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No recent applications</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        </div>

        
        <?php if (empty($noJobs)): ?>
        <div class="row mb-4 recruiter-action-center">
        <div class="col-12 mb-3">
            <div class="card shadow h-100 recruiter-dashboard-panel-card recruiter-pipeline-card animated-glow-card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bolt"></i> Action Center</h6>
                </div>
                <div class="card-body p-0">
                    <?php $hasActionCenterItems = ((int) ($pendingActions['pending_screening'] ?? 0) > 0) || ((int) ($pendingActions['hr_interviews_today'] ?? 0) > 0); ?>
                    <?php if ($hasActionCenterItems): ?>
                        <?php if ((int) ($pendingActions['pending_screening'] ?? 0) > 0): ?>
                            <a href="<?= $jobsUrl ?>" class="action-item-link">
                                <div class="action-item-label">
                                    <strong>Screen New Applications</strong>
                                    <small class="text-muted d-block">Review and shortlist incoming candidates.</small>
                                </div>
                                <span class="badge badge-warning"><?= (int) ($pendingActions['pending_screening'] ?? 0) ?></span>
                            </a>
                        <?php endif; ?>
                        <?php if ((int) ($pendingActions['hr_interviews_today'] ?? 0) > 0): ?>
                            <a href="<?= $bookingsUrl ?>" class="action-item-link">
                                <div class="action-item-label">
                                    <strong>Interviews Today</strong>
                                    <small class="text-muted d-block">Track today&#39;s booked interviews and status.</small>
                                </div>
                                <span class="badge badge-primary"><?= (int) ($pendingActions['hr_interviews_today'] ?? 0) ?></span>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="recruiter-action-center-empty recruiter-dashboard-panel-card animated-glow-card"> 
                            <div class="recruiter-action-center-empty-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h6 class="mb-2">Everything is up to date</h6>
                            <p class="text-muted mb-3">No pending screenings or interviews right now. You&#39;re all caught up.</p>
                            <a href="<?= $jobsUrl ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-briefcase mr-1"></i> Review Jobs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
        <?php endif; ?>

        <!-- Main Content Row -->

        <!-- Conversion Metrics -->
        <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow h-100 recruiter-dashboard-panel-card blue-animated-card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Conversion Metrics</h6>
                </div>
                <div class="card-body">
                    <div class="conversion-summary-row">
                        <div class="conversion-summary-card">
                            <span class="conversion-summary-label">Overall conversion</span>
                            <div class="conversion-summary-value"><?= number_format((float) ($conversionMetrics['overall_conversion'] ?? 0), 1) ?>%</div>
                            <small class="text-muted">Pipeline efficiency</small>
                        </div>
                        <div class="conversion-summary-note">
                            <strong>Stage transitions</strong>
                            <p class="mb-0 text-muted">Quick view of where candidates move forward or slow down.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm conversion-table">
                            <thead>
                                <tr>
                                    <th>Stage Transition</th>
                                    <th class="text-right">Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Application → Screening</td>
                                    <td class="text-right">
                                        <span class="badge badge-<?= $rateClass($conversionMetrics['application_to_screening'] ?? null, 50) ?>">
                                            <?= $formatRate($conversionMetrics['application_to_screening'] ?? null) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Screening → Shortlist</td>
                                    <td class="text-right">
                                        <span class="badge badge-<?= $rateClass($conversionMetrics['screening_to_shortlist'] ?? null, 40) ?>">
                                            <?= $formatRate($conversionMetrics['screening_to_shortlist'] ?? null) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Shortlist → HR Interview</td>
                                    <td class="text-right">
                                        <span class="badge badge-<?= $rateClass($conversionMetrics['shortlist_to_hr_interview'] ?? null, 60) ?>">
                                            <?= $formatRate($conversionMetrics['shortlist_to_hr_interview'] ?? null) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>HR Interview → Selection</td>
                                    <td class="text-right">
                                        <span class="badge badge-<?= $rateClass($conversionMetrics['hr_interview_to_selection'] ?? null, 30) ?>">
                                            <?= $formatRate($conversionMetrics['hr_interview_to_selection'] ?? null) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="font-weight-bold">
                                    <td>Overall Conversion</td>
                                    <td class="text-right">
                                        <span class="badge badge-primary badge-lg">
                                            <?= number_format((float) ($conversionMetrics['overall_conversion'] ?? 0), 1) ?>%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div> 

<?= view('Layouts/recruiter_footer') ?>

            