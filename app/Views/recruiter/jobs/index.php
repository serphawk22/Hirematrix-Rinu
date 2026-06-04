<?= view('Layouts/recruiter_header', ['title' => 'My Jobs']) ?>

<div class="recruiter-jobs-jobboard">
<div class="container-fluid py-5">
    <?php
    $totalJobs = count($jobs ?? []);
    $openJobs = 0;
    $closedJobs = 0;
    foreach (($jobs ?? []) as $job) {
        if (($job['status'] ?? '') === 'open') {
            $openJobs++;
        } else {
            $closedJobs++;
        }
    }
    ?>
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-briefcase"></i> Recruiter jobs</span>
            <h1 class="page-board-title">My Posted Jobs</h1>
            <p class="page-board-subtitle">Manage job postings, applications, screening policy, and leaderboard access.</p>
        </div>
        <div class="page-board-actions">
            <a href="<?= base_url('recruiter/post_job') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Post New Job
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success recruiter-alert"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger recruiter-alert"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm recruiter-table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover recruiter-jobs-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Job Title</th>
                            <th>Location</th>
                            <th>Applications</th>
                            <th>AI Policy</th>
                            <th>Status</th>
                            <th>Posted Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($jobs)): ?>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td><strong><?= esc($job['title']) ?></strong></td>
                                    <td><?= esc($job['location']) ?></td>
                                    <td>
                                        <a href="<?= base_url('recruiter/jobs/' . $job['id'] . '/applications') ?>">
                                            <span class="badge badge-primary"><?= $job['application_count'] ?></span>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $policy = strtoupper($job['ai_interview_policy'] ?? 'REQUIRED_HARD');
                                        $policyMap = [
                                            'OFF' => ['label' => 'Not Required', 'hint' => 'Direct apply', 'class' => 'ai-policy-chip-off'],
                                            'OPTIONAL' => ['label' => 'Optional', 'hint' => 'Can improve ranking', 'class' => 'ai-policy-chip-optional'],
                                            'REQUIRED_SOFT' => ['label' => 'Required + Review', 'hint' => 'Recruiter can override', 'class' => 'ai-policy-chip-soft'],
                                            'REQUIRED_HARD' => ['label' => 'Mandatory Screening', 'hint' => 'Strict AI gate', 'class' => 'ai-policy-chip-hard'],
                                        ];
                                        $policyMeta = $policyMap[$policy] ?? $policyMap['REQUIRED_HARD'];
                                        ?>
                                        <div class="ai-policy-chip <?= esc($policyMeta['class']) ?>">
                                            <strong>AI Interview: <?= esc($policyMeta['label']) ?></strong>
                                            <small><?= esc($policyMeta['hint']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $job['status'] == 'open' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($job['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                                    <td>
                                        <div class="job-actions-wrap">
                                            <a href="<?= base_url('recruiter/jobs/edit/' . $job['id']) ?>" class="btn btn-sm btn-action btn-action-edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="<?= base_url('recruiter/jobs/' . $job['id'] . '/applications') ?>" class="btn btn-sm btn-action btn-action-applications">
                                                <i class="fas fa-users"></i> View Applications
                                            </a>
                                            <a href="<?= base_url('recruiter/jobs/' . $job['id'] . '/leaderboard') ?>" class="btn btn-sm btn-action btn-action-leaderboard">
                                                <i class="fas fa-trophy"></i> Leaderboard
                                            </a>
                                            <?php if ($job['status'] == 'open'): ?>
                                                <a href="<?= base_url('recruiter/jobs/close/' . $job['id']) ?>"
                                                   class="btn btn-sm btn-action btn-action-close"
                                                   onclick="return confirm('Are you sure you want to close this job?')">
                                                    <i class="fas fa-times-circle"></i> Close
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= base_url('recruiter/jobs/reopen/' . $job['id']) ?>" class="btn btn-sm btn-action btn-action-reopen">
                                                    <i class="fas fa-check-circle"></i> Reopen
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                    <h5>No jobs posted yet</h5>
                                    <p class="text-muted">Start by posting your first job</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>
