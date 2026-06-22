<?= view('Layouts/recruiter_header', ['title' => 'My Jobs']) ?>
   <style>  
/* ── Page backgrounds ── */
.recruiter-jobs-jobboard,
.page-board-header,
.card.shadow-sm.recruiter-table-card,.hm-page-content {
    background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%) !important;
}
body.dark .recruiter-jobs-jobboard,
body.dark .page-board-header,
body.dark .card.shadow-sm.recruiter-table-card,body.dark .hm-page-content {
    background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important;
}

/* ── Page title ── */
.page-board-title {
    font-size: 26px; font-weight: 700; color: #16212B !important; margin: 0;
}
body.dark .page-board-title { color: #F8FAFC !important; }

/* ── Card ── */
.recruiter-table-card {
    border: 1px solid #D9ECE5 !important;
    border-radius: 12px !important;
    overflow: hidden;
}
body.dark .recruiter-table-card {
    border-color: #23343A !important;
}

/* ── Table base ── */
.recruiter-jobs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 1rem;
}

/* ── Table head ── */
.recruiter-jobs-table thead tr {
    background: #F0FAF7 !important;
    border-bottom: 2px solid #D9ECE5 !important;
}
.recruiter-jobs-table thead th {
    padding: 13px 16px !important;
    font-size: 0.9rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: .5px !important;
    color: #64748B !important;
    white-space: nowrap;
    border: none !important;
    background: transparent !important;
}
body.dark .recruiter-jobs-table thead tr {
    background: #1B2A2F !important;
    border-bottom-color: #23343A !important;
}
body.dark .recruiter-jobs-table thead th {
    color: #7A8B96 !important;
}

/* ── Table body rows ── */
.recruiter-jobs-table tbody tr {
    border-bottom: 1px solid #EEF2F7 !important;
    transition: background .15s;
}
.recruiter-jobs-table tbody tr:last-child {
    border-bottom: none !important;
}
.recruiter-jobs-table tbody tr:hover {
    background: #F4FBFA !important;
}
body.dark .recruiter-jobs-table tbody tr {
    border-bottom-color: #23343A !important;
    background: transparent;
}
body.dark .recruiter-jobs-table tbody tr:hover {
    background: rgba(31, 183, 181, 0.05) !important;
}

/* ── Table cells ── */
.recruiter-jobs-table tbody td {
    padding: 14px 16px !important;
    vertical-align: middle !important;
    color: #16212B !important;
    border: none !important;
}
body.dark .recruiter-jobs-table tbody td {
    color: #E2E8F0 !important;
}

/* ── Job title ── */
.recruiter-jobs-table tbody td strong {
    font-weight: 600;
    color: #16212B;
}
body.dark .recruiter-jobs-table tbody td strong {
    color: #F8FAFC;
}

/* ── Location & date ── */
.recruiter-jobs-table tbody td:nth-child(2),
.recruiter-jobs-table tbody td:nth-child(6) {
    color: #64748B !important;
    font-size: 1rem;
}
body.dark .recruiter-jobs-table tbody td:nth-child(2),
body.dark .recruiter-jobs-table tbody td:nth-child(6) {
    color: #94A3B8 !important;
}

/* ── Application count link ── */
.recruiter-jobs-table tbody td a span[style] {
    font-weight: 700 !important;
    font-size: 1rem;
}

/* ── AI Policy cell ── */
.recruiter-jobs-table tbody td strong {
    font-size: 1rem;
}
.recruiter-jobs-table tbody td small {
    color: #94A3B8;
    font-size: 11.5px;
    display: block;
    margin-top: 2px;
}
body.dark .recruiter-jobs-table tbody td small {
    color: #7A8B96;
}
 
/* ── Action buttons wrap ── */
.job-actions-wrap {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 6px !important;
    align-items: center !important;
}
 

/* ── Empty state ── */
.recruiter-jobs-table td.text-center i.fa-briefcase {
    color: #D9ECE5 !important;
    display: block;
    margin-bottom: 12px;
}
.recruiter-jobs-table td.text-center h5 {
    color: #16212B;
    font-weight: 600;
    margin-bottom: 6px;
}
body.dark .recruiter-jobs-table td.text-center h5 {
    color: #F8FAFC;
}

/* ── Responsive scroll ── */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.status-pill,
.ai-badge, .hero-search-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border-radius: none !important;
  font-weight: 600;
}
.status-pill,.ai-badge, .hero-search-tag{
     display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #16212b14 !important;
    color: #0D8A90 !important;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600 !important;
    text-decoration:none !important;
    backdrop-filter: blur(10px);
}
body.dark .status-pill{
     display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #7a8b9650 !important;
    color: #0D8A90 !important;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600 !important;
    text-decoration:none !important;
    backdrop-filter: blur(10px);
}
.recruiter-candidate-filter-form .form-control:focus,
.recruiter-candidate-filter-form select.form-control:focus,
.recruiter-candidate-filter-form textarea.form-control:focus {
    outline: 0 !important;
    box-shadow: none !important;   /* ← add this */
    border-color: #0D8A90 !important; 
}
/* ── Also reset Bootstrap's base .form-control focus ── */
.form-control:focus {
    box-shadow: none !important;   /* ← already there, add !important */
    border-color: #0D8A90;
}
</style> 
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
                                        <a href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>">
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
                                       <?php $statusColor = $job['status'] == 'open' ? '#1FB7B5' : '#ef4444'; ?>
<span style="color: <?= $statusColor ?>; font-weight: 600;">
    <?= ucfirst($job['status']) ?> 
                                    </td>
                                    <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                                    <td>
                                        <div class="job-actions-wrap">
                                            <a href="<?= base_url('recruiter/jobs/edit/' . $job['id']) ?>" class="status-pill">
                                                 Edit
                                            </a>
                                            <a href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>" class="status-pill">
                                               View Applications
                                            </a>
                                            <a href="<?= base_url('recruiter/jobs/' . $job['id'] . '/leaderboard') ?>" class="status-pill">
                                                Leaderboard
                                            </a>
                                            <?php if ($job['status'] == 'open'): ?>
                                                <a href="<?= base_url('recruiter/jobs/close/' . $job['id']) ?>"
                                                   class="status-pill"
                                                   onclick="return confirm('Are you sure you want to close this job?')">
                                                  Close
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= base_url('recruiter/jobs/reopen/' . $job['id']) ?>" class="status-pill">
                                                      Reopen
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5"> 
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