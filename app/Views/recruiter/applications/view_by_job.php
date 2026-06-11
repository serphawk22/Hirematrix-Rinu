        <?= view('Layouts/recruiter_header', ['title' => 'Applications - ' . $job['title']]) ?>
<style>
/* Modal size */
.ai-modal .modal-dialog {
  max-width: 1000px;
}

.ai-modal-content {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e8edf8;
  box-shadow: 0 10px 22px rgba(35, 54, 106, .08);
  overflow: hidden;
}

body.dark .ai-modal-content {
  background: #111111 !important;
  border: 1px solid #23343A !important;
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.3);
}

/* Header */
.ai-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  padding: 16px 20px;
  border-bottom: 1px solid #edf1fa;
}

body.dark .ai-header {
  background: #111111 !important;
  border-bottom: 1px solid #23343A !important;
}

/* Title */
.ai-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.ai-title h5 {
  margin: 0;
  font-weight: 600;
  color: #1f2f57;
}

body.dark .ai-title h5 {
  color: #F8FAFC !important;
}

.ai-title small {
  font-size: 0.75rem;
  color: #6b7c9f;
}

body.dark .ai-title small {
  color: #7A8B96 !important;
}

.ai-icon {
  font-size: 20px;
}

/* Close button */
.ai-close {
  background: transparent;
  border: none;
  color: #6b7c9f;
  font-size: 18px;
  padding: 5px 10px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}

.ai-close:hover {
  background: #f8f9fa;
  color: #1f2f57;
}

body.dark .ai-close {
  color: #7A8B96;
}

body.dark .ai-close:hover {
  background: #1B2A2F;
  color: #F8FAFC;
}

/* Body */
.ai-body {
  padding: 20px;
  color: #212529;
  max-height: 75vh;
  overflow-y: auto;
  background: #ffffff;
}

body.dark .ai-body {
  background: #111111 !important;
  color: #94A3B8 !important;
}

/* Cards inside modal */
body.dark #aiReportContent .card {
  background: #111111 !important;
  border: 1px solid #23343A !important;
}

body.dark #aiReportContent .card-header {
  background: #111111 !important;
  border-bottom: 1px solid #23343A !important;
}

body.dark #aiReportContent .card-header h5 {
  color: #F8FAFC !important;
}

body.dark #aiReportContent h4 {
  color: #F8FAFC !important;
}

body.dark #aiReportContent small.text-muted {
  color: #7A8B96 !important;
}

body.dark #aiReportContent table th,
body.dark #aiReportContent table td {
  background: #111111 !important;
  color: #94A3B8 !important;
  border-color: #23343A !important;
}

/* Loader */
.ai-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 50px 0;
  color: #6b7c9f;
}

body.dark .ai-loader {
  color: #7A8B96;
}

/* Spinner */
.spinner {
  width: 36px;
  height: 36px;
  border: 4px solid #ffe3cf;
  border-top: 4px solid #ff7b2a;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error state */
.ai-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 50px 0;
  color: #dc3545;
  text-align: center;
}

.ai-error i {
  font-size: 2rem;
}

body.dark .ai-error {
  color: #ff6b6b;
}

/* Scrollbar */
.ai-body::-webkit-scrollbar {
  width: 6px;
}

.ai-body::-webkit-scrollbar-thumb {
  background: #1FB7B5;
  border-radius: 10px;
}
  .btn-primary,.btn-outline-primary {  
        background: transparent !important;
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important;
    padding: 8px 20px;
    border-radius: 6px !important;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-primary:hover, .btn-primary:focus, .btn-outline-primary:focus, .btn-outline-primary:hover {
 background:  #1FB7B5 !important;
    color: #ffffff !important;
    transform: translateY(-1px);

}

    .page-board-title{
        font-size: 26px !important; 
    font-weight: 700 !important;
    color: var(--foreground) !important;
    margin: 0;
    }
    body.dark .page-board-title{
        font-size: 26px !important;
    font-weight: 700 !important;
    color: #F8FAFC !important;
    margin: 0;
    }
      .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    background: #16212b14;
    color: #0D8A90;
    border: none;
    text-decoration: none !important;
    white-space: nowrap;
    cursor: pointer;
}
body.dark .status-pill {
    background: #111111 !important;
    color: #0D8A90;
    border: 1px solid rgba(31, 183, 181, 0.15) !important;
}
.page-board-header.page-board-header-tight.recruiter-page-board-header, body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border : none !important;
}
tr, td, th {
    font-size: 1rem;
    font-weight: 500 !important;
    color: #64748B !important;
    background: white !important;
}

/* Add these dark mode overrides */
body.dark tr,
body.dark td,
body.dark th {
  background:  #111111 !important;
    color:#94A3B8 !important;
    border-color: #23343A !important;
}

body.dark .table-secondary td,
body.dark .table-secondary th,
body.dark .table-secondary {
   background:  #111111 !important;
}

body.dark thead th {
   background: #111111 !important;
    color: #94A3B8 !important;
}
.hm-page-content,.recruiter-applications-jobboard{
         background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
}
body.dark .hm-page-content,body.dark .recruiter-applications-jobboard,body.dark .recruiter-job-summary-card,body.dark .recruiter-filter-card,body.dark .recruiter-alert, body.dark .recruiter-table-card{
    background: #111111 !important;
    border: 1px solid #23343A !important;
} 
body.dark .recruiter-job-summary-card h5.mb-1, body.dark .recruiter-filter-card,body.dark .recruiter-alert{
    color:#94A3B8 !important;
}
.page-board-header.page-board-header-tight.recruiter-page-board-header, body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header,{
      border: none !important;
}
/* ── Input focus border ── */
.recruiter-job-form .form-control:focus {
    border-color: var(--primary-dark, #0D8A90) !important; 
    outline: none !important;
}

.recruiter-job-form .form-control {
    border: 1px solid var(--border, #D9ECE5);
    border-radius: 6px;
    transition: border-color .2s, box-shadow .2s;
    background: #fff;
    color: var(--foreground, #16212B);
} 
body.dark .recruiter-job-form .form-control {
    border: 1px solid #23343A !important;
    border-radius: 6px;
    transition: border-color .2s, box-shadow .2s;
    background: #111111 !important;
    color: #F8FAFC !important;
}
/* ── Labels — match h6 style ── */
.recruiter-job-form label {
    font-size: 1rem;        /* same as Bootstrap h6 */
    font-weight: 500 !important;       /* same as h6 */
    color: var(--foreground, #16212B);
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
}
body.dark .recruiter-tip-item{
     background:#111111 !important;
    color: #7A8B96 !important;
     border: 1px solid #23343A !important;
      font-weight: 400 !important;   
}
 .recruiter-tip-item{ 
      font-weight: 400 !important;   
}
body.dark .recruiter-job-form label, body.dark h6 {
    font-size: 1rem;        /* same as Bootstrap h6 */
    font-weight: 500 !important;   
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
    color:#94A3B8;;
}
/* ── Kill Bootstrap's orange/default focus first ── */
/* ── Kill Bootstrap's orange/default focus first ── */
.recruiter-job-form .form-control:focus,
.recruiter-job-form select.form-control:focus,
.recruiter-job-form textarea.form-control:focus {
    outline: 0 !important;
    box-shadow: none !important;   /* ← add this */
    border-color: #0D8A90 !important; 
}
/* ── Also reset Bootstrap's base .form-control focus ── */
.form-control:focus {
    box-shadow: none !important;   /* ← already there, add !important */
    border-color: #0D8A90;
}
 .container-fluid {
    max-width: 100% !important;
    padding-left: 34px !important;
    padding-right: 34px !important;
}
    </style>
<div class="recruiter-applications-jobboard">
<div class="container-fluid py-5">
    <?php
    $applicationsCount = count($applications ?? []);
    $statusCount = [];
    foreach (($applications ?? []) as $app) {
        $status = strtolower((string) ($app['status'] ?? 'pending'));
        $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
    }
    $statusOptions = $statusOptions ?? [];
    ?>

    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title"><?= esc($job['title']) ?></h1>
            <p class="page-board-subtitle">
                Review candidates, run actions, and compare application status for this role.
            </p>
        </div>
        <div class="page-board-actions recruiter-applications-actions">
            <a href="<?= base_url('recruiter/jobs') ?>" class="btn btn-outline-primary">
                 Back to Jobs
            </a>
            <a href="<?= base_url('recruiter/jobs/' . $job['id'] . '/leaderboard') ?>" class="btn btn-outline-primary">
                 Open Leaderboard
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success recruiter-alert"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger recruiter-alert"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div id="recruiterAjaxAlert"></div>

    <div class="card shadow-sm recruiter-job-summary-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="mb-1"><?= esc($job['title']) ?></h5>
                    <small class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> <?= esc($job['location']) ?> |
                        <i class="fas fa-calendar"></i> Posted on <?= date('M d, Y', strtotime($job['created_at'])) ?>
                    </small>
                </div>
                <div class="text-muted small">
                    <?= $applicationsCount ?> candidate<?= $applicationsCount === 1 ? '' : 's' ?> in this pipeline
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm recruiter-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h6 class="m-0 font-weight-bold">Review Filters</h6>
                    <p class="text-muted mb-0">Filter by skills, experience, location, and scoring signals.</p>
                </div>
                <div class="text-muted small">
                    <?= !empty($applicationsCount) ? 'Bulk actions available' : 'No candidates yet' ?>
                </div>
            </div>

            <form method="get" action="<?= base_url('recruiter/jobs/' . $job['id'] . '/applications') ?>" class="recruiter-job-form">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">Skills</label>
                        <input type="text" name="skills" class="form-control" value="<?= esc($filters['skills'] ?? '') ?>" placeholder="e.g. PHP, Laravel">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Experience</label>
                        <input type="text" name="experience" class="form-control" value="<?= esc($filters['experience'] ?? '') ?>" placeholder="e.g. 3 years">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= esc($filters['location'] ?? '') ?>" placeholder="City / State">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Score Min</label>
                        <input type="number" step="0.1" min="0" max="10" name="score_min" class="form-control" value="<?= esc($filters['score_min'] ?? '') ?>" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Score Max</label>
                        <input type="number" step="0.1" min="0" max="10" name="score_max" class="form-control" value="<?= esc($filters['score_max'] ?? '') ?>" placeholder="10">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">ATS Min</label>
                        <input type="number" min="0" max="100" name="ats_min" class="form-control" value="<?= esc($filters['ats_min'] ?? '') ?>" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">ATS Max</label>
                        <input type="number" min="0" max="100" name="ats_max" class="form-control" value="<?= esc($filters['ats_max'] ?? '') ?>" placeholder="100">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Sort By</label>
                        <select name="sort" class="form-control">
                            <option value="applied_desc" <?= ($filters['sort'] ?? '') === 'applied_desc' ? 'selected' : '' ?>>Newest Applied</option>
                            <option value="ats_desc" <?= ($filters['sort'] ?? '') === 'ats_desc' ? 'selected' : '' ?>>ATS High to Low</option>
                            <option value="ats_asc" <?= ($filters['sort'] ?? '') === 'ats_asc' ? 'selected' : '' ?>>ATS Low to High</option>
                        </select>
                    </div>
                    <div class="col-md-2">
    <label class="small text-muted mb-1">Last Active</label>
    <select name="last_active" class="form-control">
        <option value="">All</option>
        <option value="7" <?= ($filters['last_active'] ?? '') === '7' ? 'selected' : '' ?>>Last 7 Days</option>
        <option value="30" <?= ($filters['last_active'] ?? '') === '30' ? 'selected' : '' ?>>Last 30 Days</option>
        <option value="90" <?= ($filters['last_active'] ?? '') === '90' ? 'selected' : '' ?>>Last 90 Days</option>
    </select>
</div>
                    <div class="col-md-1">
                        <label class="small text-muted mb-1">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?= esc($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                                    <?= esc(ucwords(str_replace('_', ' ', $status))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3 recruiter-filter-actions">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                       Apply Filters
                    </button>
                    <a href="<?= base_url('recruiter/jobs/' . $job['id'] . '/applications') ?>" class="btn btn-outline-primary btn-sm ml-2">
                        Clear
                    </a>
                    <a href="<?= base_url('recruiter/jobs/' . $job['id'] . '/leaderboard') ?>" class="btn btn-outline-primary btn-sm ml-2">
                       Open Leaderboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($filters['skills']) || !empty($filters['experience']) || !empty($filters['location']) || !empty($filters['score_min']) || !empty($filters['score_max']) || !empty($filters['ats_min']) || !empty($filters['ats_max']) || !empty($filters['sort']) || !empty($filters['status'])): ?>
        <div class="alert alert-info recruiter-alert">
          Active filters are applied.Use Clear to reset the search.
        </div>
    <?php endif; ?>

    <?php if (!empty($applications)): ?>
        <div class="alert alert-light border recruiter-alert">
            <strong>Decision workspace:</strong> bulk actions and per-candidate decisions are handled here. The leaderboard is kept read-focused for comparison only.
        </div>

        <div class="card shadow-sm recruiter-table-card">
            <div class="card-body">
                <form method="post" action="<?= base_url('recruiter/jobs/' . $job['id'] . '/applications/bulk') ?>" id="bulkActionForm" class="mb-3 recruiter-job-form">
                    <?= csrf_field() ?>
                    <div class="recruiter-bulk-toolbar">
                        <select name="bulk_action" id="bulkActionSelect" class="form-control form-control-sm recruiter-bulk-select">
                            <option value="">Bulk Action</option>
                            <option value="shortlist">Shortlist Selected</option>
                            <option value="reject">Reject Selected</option>
                            <option value="message">Message Selected</option>
                        </select>
                        <input type="text" name="bulk_message" id="bulkMessageInput" class="form-control form-control-sm recruiter-bulk-message" placeholder="Message for selected candidates (required only for Message action)">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                              Apply
                        </button>
                        <small class="text-muted">Select candidates using the first column.</small>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover recruiter-applications-table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAllApplications" title="Select all">
                                </th>
                                <th>ID</th>
                                <th>Candidate</th>
                                <th>Email</th>
                                <th>Experience</th>
                                <th>Skills</th>
                                <th>Tags</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>ATS Score</th>
                                <th>Last Active</th>
                                <th>Applied Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr data-application-row="<?= (int) $app['id'] ?>">
                                    <td>
                                        <input type="checkbox" class="application-checkbox" value="<?= (int) $app['id'] ?>">
                                    </td>
                                    <td>#<?= $app['id'] ?></td>
                                    <td> <?= esc($app['name']) ?> </td>
                                    <td><?= esc($app['email']) ?></td>
                                    <td><?= esc($app['experience_display'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($app['skill_name'])): ?>
                                            <?= esc($app['skill_name']) ?> 
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['recruiter_tags'])): ?>
                                            <?php foreach (explode(',', (string) $app['recruiter_tags']) as $tag): ?>
                                                <?php $trimmedTag = trim($tag); ?>
                                                <?php if ($trimmedTag !== ''): ?>
                                                    <span class="badge badge-light border mb-1"><?= esc($trimmedTag) ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['recruiter_notes'])): ?>
                                            <?php
                                            $fullNote = trim((string) $app['recruiter_notes']);
                                            $shortNote = mb_strlen($fullNote) > 80 ? mb_substr($fullNote, 0, 80) . '...' : $fullNote;
                                            ?>
                                            <small title="<?= esc($fullNote) ?>"><?= esc($shortNote) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'applied' => 'warning',
                                            'shortlisted' => 'success',
                                            'hold' => 'secondary',
                                            'filtered_out' => 'dark',
                                            'interview_slot_booked' => 'success',
                                            'selected' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $statusColors[$app['status']] ?? 'secondary';
                                        $statusLabels = [
                                            'pending' => 'Applied',
                                            'applied' => 'Applied',
                                            'shortlisted' => 'Shortlisted',
                                            'hold' => 'On Hold',
                                            'filtered_out' => 'Filtered Out',
                                            'interview_slot_booked' => 'Interview Booked',
                                            'selected' => 'Selected',
                                            'rejected' => 'Rejected',
                                        ];
                                        $label = $statusLabels[$app['status']] ?? ucwords(str_replace('_', ' ', $app['status']));
                                        ?>
                                        <span class="status-pill" data-status="<?= esc($app['status']) ?>">
                                            <?= esc($label) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $ats = (int) ($app['ats_score'] ?? 0);
                                        $atsBadge = 'danger';
                                        if ($ats >= 80) {
                                            $atsBadge = 'success';
                                        } elseif ($ats >= 60) {
                                            $atsBadge = 'warning';
                                        } elseif ($ats >= 40) {
                                            $atsBadge = 'info';
                                        }
                                        ?>
                                        <span class="status-pill"><?= $ats ?>%</span>
                                    </td>
                                    <td>
    <?php if (!empty($app['last_login'])): ?>
        <?= date('M d, Y', strtotime($app['last_login'])) ?>
    <?php else: ?>
        <span class="text-muted">Never</span>
    <?php endif; ?>
</td>
                                    <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                    <td>
                                        <div class="application-actions-wrap">
                                            <a href="<?= base_url('recruiter/candidate/' . $app['candidate_id'] . '?application_id=' . $app['id'] . '&job_id=' . $job['id']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                 View Profile
                                            </a>
                                            <?php if (!empty($app['can_manual_decision'])): ?>
                                                <form method="post" action="<?= base_url('recruiter/applications/shortlist/' . $app['id']) ?>" class="recruiter-job-form" data-application-id="<?= (int) $app['id'] ?>">
                                                    <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-check"></i> Shortlist
                                                </button>
                                                </form>
                                                <form method="post" action="<?= base_url('recruiter/applications/reject/' . $app['id']) ?>" class="recruiter-job-form" data-application-id="<?= (int) $app['id'] ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php elseif (($app['status'] ?? '') !== 'interview_slot_booked' && ($app['status'] ?? '') !== 'selected'): ?>
                                                 <button
    type="button"
    class="btn btn-sm btn-outline-primary view-ai-report-btn"
    data-candidate-id="<?= $app['candidate_id'] ?>"
    data-jobrole="<?= esc($job['title']) ?>"
    data-candidate-name="<?= esc($app['name']) ?>"
>
   AI Report
</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm recruiter-empty-state">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5>No applications yet</h5>
                <p class="text-muted mb-0">Applications will appear here once candidates apply</p>
            </div>
        </div>
    <?php endif; ?>
</div>
<div class="modal fade ai-modal" id="aiReportModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content ai-modal-content">

      <!-- HEADER -->
      <div class="modal-header ai-header">
        <div class="ai-title">
          <span class="ai-icon">📊</span>
          <div>
            <h5>AI Interview Report</h5>
            <small>Detailed candidate performance & insights</small>
          </div>
        </div>

        <button type="button" class="ai-close" data-dismiss="modal">
          ✕
        </button>
      </div>

      <!-- BODY -->
      <div class="modal-body ai-body">
        <div id="aiReportContent">

          <!-- Loader -->
          <div class="ai-loader">
            <div class="spinner"></div>
            <p>Analyzing interview performance...</p>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>
<script>
/* ── Bulk select / CSRF refresh / inline status update ── */
(function () {
    const alertHost = document.getElementById('recruiterAjaxAlert');

    function showAlert(type, message) {
        if (!alertHost) return;
        alertHost.innerHTML =
            '<div class="alert alert-' + type + ' recruiter-alert">' + message + '</div>';
    }

    function refreshCsrfTokens(name, hash) {
        if (!name || !hash) return;
        document.querySelectorAll('input[name="' + name + '"]').forEach(function (i) {
            i.value = hash;
        });
    }

    function setButtonBusy(btn, busy) {
        if (!btn) return;
        if (busy) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML =
                '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>Saving';
        } else {
            if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
            btn.disabled = false;
        }
    }

    /* Inline action forms */
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.classList.contains('application-action-form')) return;
        e.preventDefault();

        const row    = form.closest('[data-application-row]');
        const button = form.querySelector('button[type="submit"]');
        setButtonBusy(button, true);

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            body: new FormData(form)
        })
        .then(async function (res) {
            const ct = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                const txt = await res.text();
                throw new Error(txt || 'Unexpected server response.');
            }
            const payload = await res.json();
            refreshCsrfTokens(payload.csrf_token_name, payload.csrf_hash);
            if (!res.ok || !payload.success) throw new Error(payload.message || 'Could not update status.');

            showAlert('success', payload.message);

            if (row) {
                const badge = row.querySelector('.application-status-badge');
                if (badge) {
                    badge.className = 'badge badge-' + (payload.status_badge || 'secondary') + ' application-status-badge';
                    badge.textContent = payload.status_label || badge.textContent;
                    badge.dataset.status = payload.status || '';
                }
            }
        })
        .catch(function (err) {
            showAlert('danger', err.message || 'Could not update application status.');
        })
        .finally(function () {
            setButtonBusy(button, false);
        });
    });
})();

/* ── AI Report modal ── */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.view-ai-report-btn');
    if (!btn) return;

    const candidate_id   = btn.dataset.candidateId;
    const jobrole        = btn.dataset.jobrole;
    const candidate_name = btn.dataset.candidateName;

    /* Show modal with loader */
    $('#aiReportModal').modal('show');
    document.getElementById('aiReportContent').innerHTML = `
        <div class="ai-loader">
            <div class="spinner"></div>
            <p>Analyzing interview performance…</p>
        </div>`;

    fetch('<?= base_url('recruiter/get-ai-report') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ candidate_id, jobrole })
    })
    .then(function (res) {
        /* Surface HTTP errors (500, 404, etc.) */
        if (!res.ok) {
            return res.text().then(function (txt) {
                throw new Error('Server error ' + res.status + (txt ? ': ' + txt.substring(0, 200) : ''));
            });
        }
        return res.json();
    })
    .then(function (data) {
        /* Guard: unexpected shape */
        if (!data || typeof data !== 'object') throw new Error('Invalid response from server.');

        /* Build violations rows */
        let violationsHtml = '';
        if (Array.isArray(data.violations) && data.violations.length) {
            data.violations.forEach(function (v) {
                violationsHtml += `
                    <tr>
                        <td>${v.message ?? '-'}</td>
                        <td><span class="badge badge-danger">${v.total ?? 0}</span></td>
                    </tr>`;
            });
        } else {
            violationsHtml = `
                <tr>
                    <td colspan="2" class="text-center text-muted">No violations found</td>
                </tr>`;
        }

        /* Build results rows */
        let resultHtml = '';
        if (Array.isArray(data.results) && data.results.length) {
            data.results.forEach(function (r) {
                resultHtml += `
                    <tr>
                        <td>${r.round_name ?? '-'}</td>
                        <td>${r.score ?? 0}</td>
                        <td>${r.total_questions ?? 0}</td>
                        <td><span class="badge badge-success">${r.percentage ?? 0}%</span></td>
                    </tr>`;
            });
        } else {
            resultHtml = `
                <tr>
                    <td colspan="4" class="text-center text-muted">No interview results</td>
                </tr>`;
        }

        document.getElementById('aiReportContent').innerHTML = `
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h4 class="font-weight-bold">${candidate_name}</h4>
                            <small class="text-muted">${jobrole}</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Interview Scores</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Round</th>
                                        <th>Score</th>
                                        <th>Total</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>${resultHtml}</tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0 text-danger">Violations</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Violation</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>
                                <tbody>${violationsHtml}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>`;
    })
    .catch(function (err) {
        /* Show a clean error state inside the modal */
        document.getElementById('aiReportContent').innerHTML = `
            <div class="ai-error">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Failed to load report</strong>
                <p class="mb-0 text-muted" style="font-size:0.85rem">${err.message || 'An unexpected error occurred. Please try again.'}</p>
            </div>`;
    });
});
</script>
<?= view('Layouts/recruiter_footer') ?>
    