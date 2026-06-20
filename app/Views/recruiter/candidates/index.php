<?= view('Layouts/recruiter_header', ['title' => 'Candidate Database']) ?>
<style>
    .application-actions-wrap {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    gap: 8px;
}
.page-board-header.page-board-header-tight.recruiter-page-board-header,body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border:none !important;
}
.application-actions-wrap a {
    display: inline-flex !important;
    width: auto !important;
    white-space: nowrap;
}
.application-actions-wrap .btn {
    display: inline-flex !important;
    align-items: center;          /* vertical center */
    justify-content: center;
    gap: 6px;                     /* space between icon & text */
    padding: 6px 14px;
    line-height: 1;               /* remove extra height */
}

.application-actions-wrap .btn i {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    line-height: 1;
    margin: 0;
}
.page-board-title{
        font-size: 26px;
    font-weight: 700;
    color: var(--foreground) !important;
    margin: 0;
    }
    body.dark .page-board-title{
        font-size: 26px;
    font-weight: 700;
    color: #ffffff !important;
    margin: 0;
    }

    .btn-primary, .btn-outline-primary {
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
/* ── Page backgrounds ── */
.recruiter-candidates-jobboard {
    background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%) !important;
}
body.dark .recruiter-candidates-jobboard,
body.dark .page-board-header,
body.dark .card.shadow-sm.recruiter-table-card,
body.dark .hm-page-content,
body.dark .card.shadow-sm.recruiter-filter-card,
body.dark .card-header {
    background: #000000 !important;
}

/* ── Page title ── */
.page-board-title {
    font-size: 26px;
    font-weight: 700;
    color: #16212B !important;
    margin: 0;
}
body.dark .page-board-title {
    color: #ffffff !important;
}

/* ── Card ── */
.recruiter-table-card,
.recruiter-filter-card {
    border: 1px solid #D9ECE5 !important;
    border-radius: 12px !important;
    overflow: hidden;
}
body.dark .recruiter-table-card,
body.dark .recruiter-filter-card {
    border-color: #23343A !important;
}

/* ── Table base ── */
.recruiter-candidates-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 1rem;
}

/* ── Table head ── */
.recruiter-candidates-table thead tr {
    background: #F0FAF7 !important;
    border-bottom: 2px solid #D9ECE5 !important;
}
.recruiter-candidates-table thead th {
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
body.dark .recruiter-candidates-table thead tr {
    background: #000000 !important;
    border-bottom-color: #23343A !important;
}
body.dark .recruiter-candidates-table thead th {
    color: #ffffff !important;
}

/* ── Table body rows ── */
.recruiter-candidates-table tbody tr {
    border-bottom: 1px solid #EEF2F7 !important;
    transition: background .15s;
}
.recruiter-candidates-table tbody tr:last-child {
    border-bottom: none !important;
}
.recruiter-candidates-table tbody tr:hover {
    background: #F4FBFA !important;
}
body.dark .recruiter-candidates-table tbody tr {
    border-bottom-color: #23343A !important;
    background: transparent;
}
body.dark .recruiter-candidates-table tbody tr:hover {
    background: rgba(31, 183, 181, 0.05) !important;
}

/* ── Table cells ── */
.recruiter-candidates-table tbody td {
    padding: 14px 16px !important;
    vertical-align: middle !important;
    font-size: 1rem !important;
    color: #16212B !important;
    border: none !important;
}
body.dark .recruiter-candidates-table tbody td {
    color: #ffffff !important;
}

/* ── Candidate name ── */
.recruiter-candidates-table tbody td strong {
    font-weight: 600;
    font-size: 1rem;
    color: #16212B;
}
body.dark .recruiter-candidates-table tbody td strong {
    color: #ffffff;
}

/* ── Secondary text (location, date, email) ── */
.recruiter-candidates-table tbody td small,
.recruiter-candidates-table tbody td:nth-child(2),
.recruiter-candidates-table tbody td:nth-child(6) {
    color: #64748B !important;
    font-size: 0.875rem;
}
body.dark .recruiter-candidates-table tbody td small,
body.dark .recruiter-candidates-table tbody td:nth-child(2),
body.dark .recruiter-candidates-table tbody td:nth-child(6) {
    color: #ffffff !important;
}
.recruiter-candidates-table tbody td small {
    display: block;
    margin-top: 2px;
}

/* ── Badges ── */
.badge-resume-yes {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 600;
    background: #D1FAE5;
    color: #065F46;
}
body.dark .badge-resume-yes {
    background: #064E3B;
    color: #6EE7B7;
}
.badge-resume-no {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 600;
    background: #F1F5F9;
    color: #64748B;
}
body.dark .badge-resume-no {
    background: #1E293B;
    color: #94A3B8;
}

/* ── Action buttons ── */
.application-actions-wrap {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 6px !important;
    align-items: center !important;
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
    background: #000000 !important;
    color: #0D8A90;
    border: 1px solid rgba(31, 183, 181, 0.15) !important;
} 

/* ── Primary button (Search, View in AI table) ── */
.btn-primary {
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%) !important;
    border: none !important;
    color: #ffffff !important;
    padding: 8px 20px;
    border-radius: 4px !important;
    font-size: 1rem;
    font-weight: 600;
    transition: all .25s ease;
}
.btn-primary:hover,
.btn-primary:focus {
    transform: translateY(-1px);
    color: #ffffff !important;
}

/* ── Filter form labels ── */
.recruiter-candidate-filter-form label.small {
    font-size: 0.85rem;
    color: #64748B;
}
body.dark .recruiter-candidate-filter-form label.small {
    color: #ffffff;
}

/* ── Filter form inputs ── */
.recruiter-candidate-filter-form .form-control {
    font-size: 1rem;
}

/* ── Responsive ── */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* ── Empty state ── */
.recruiter-candidates-table td.text-center h5 {
    color: #16212B;
    font-weight: 600;
    margin-bottom: 6px;
}
body.dark .recruiter-candidates-table td.text-center h5 {
    color: #ffffff;
} 
body.dark .title{
    font-size: 1rem;        /* same as Bootstrap h6 */
    font-weight: 500 !important;       /* same as h6 */
    color:  #F8FAFC !important;
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
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
/* ── Labels — match h6 style ── */
.recruiter-candidate-filter-form label {
    font-size: 1rem;        /* same as Bootstrap h6 */
    font-weight: 500 !important;       /* same as h6 */
    color: var(--foreground, #16212B);
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
} 
/* ── Input focus border ── */
.recruiter-candidate-filter-form .form-control:focus {
    border-color: var(--primary-dark, #0D8A90) !important; 
    outline: none !important;
}

.recruiter-candidate-filter-form .form-control {
    border: 1px solid var(--border, #D9ECE5);
    border-radius: 6px;
    transition: border-color .2s, box-shadow .2s;
    background: #fff;
    color: var(--foreground, #16212B);
}
body.dark .card,body.dark .card-header, body.dark .card-body{
 border: 1px solid #23343A !important;
}
body.dark .recruiter-candidate-filter-form .form-control {
    border: 1px solid #23343A !important;
    border-radius: 6px;
    transition: border-color .2s, box-shadow .2s;
    background: #000000 !important;
    color: #ffffff !important;
}
/* ── Kill Bootstrap's orange/default focus first ── */
/* ── Kill Bootstrap's orange/default focus first ── */
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
body.dark .recruiter-filter-card .card-body h6,
body.dark .recruiter-filter-card .card-body h6.font-weight-bold {
    color: #ffffff !important;
}

body.dark .recruiter-table-card .card-header h6,
body.dark .recruiter-table-card .card-header h6.font-weight-bold {
    color: #ffffff !important;
}
body.dark .recruiter-filter-card .text-muted,
body.dark .recruiter-table-card .text-muted {
    color: #ffffff !important;
}
body.dark .card.shadow-sm.recruiter-table-card,
body.dark .card.shadow-sm.recruiter-filter-card {
    border: 1px solid #23343A !important;
    box-shadow: none !important;       /* removes Bootstrap shadow-sm white glow */
}
body.dark .d-flex.align-items-start.justify-content-between.flex-wrap h6{
    color: #ffffff !important;
}
/* Nuclear override for pagination links */
ul.pagination li.page-item a.page-link,
ul.pagination li.page-item a.page-link:visited,
ul.pagination li.page-item a.page-link:hover,
ul.pagination li.page-item a.page-link:focus {
    color: #1FB7B5 !important;
    background-color: transparent !important;
    border-color: #D9ECE5 !important;
    text-decoration: none !important;
}

ul.pagination li.page-item.active a.page-link {
    color: #ffffff !important;
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 100%) !important;
    border-color: #1FB7B5 !important;
}

body.dark ul.pagination li.page-item a.page-link {
    border-color: #ffffff !important;
}
body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border:none !important;
}
 .container-fluid {
    max-width: 100% !important;
    padding-left: 34px !important;
    padding-right: 34px !important;
}
</style>
<div class="recruiter-candidates-jobboard">
<div class="container-fluid py-5">
    <?php
    $selectedJobTitle = (string) ($selectedJob['title'] ?? '');
    $candidateCount = count($candidates ?? []);
    $aiSuggestionCount = count($aiSuggestions ?? []);
    ?>

    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">Candidate Database</h1>
            <p class="page-board-subtitle">Search and discover candidates beyond direct applicants. Compare profiles and jump into the workspace.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success recruiter-alert"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger recruiter-alert"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card shadow-sm recruiter-filter-card mb-4" style="border-radius: 20px !important;overflow: hidden;">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h6 class="mb-3">Search Filters</h6>
                    <p class="text-muted mb-0">Narrow down the candidate database by skill, experience, job fit, and resume availability.</p>
                </div>
            </div>

            <form method="get" action="<?= base_url('recruiter/candidates') ?>" class="recruiter-candidate-filter-form" >
                <div class="row">
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">Keyword</label>
                        <input type="text" name="keyword" class="form-control" value="<?= esc($filters['keyword'] ?? '') ?>" placeholder="Name / Email / Skill">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Skills</label>
                        <input type="text" name="skills" class="form-control" value="<?= esc($filters['skills'] ?? '') ?>" placeholder="e.g. PHP">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= esc($filters['location'] ?? '') ?>" placeholder="City / State">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Exp Min (Years)</label>
                        <input type="number" step="0.5" min="0" name="exp_min" class="form-control" value="<?= esc($filters['exp_min'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Exp Max (Years)</label>
                        <input type="number" step="0.5" min="0" name="exp_max" class="form-control" value="<?= esc($filters['exp_max'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Job Role</label>
                        <select name="job_id" class="form-control">
                            <option value="">Select Job</option>
                            <?php foreach (($recruiterJobs ?? []) as $job): ?>
                                <option value="<?= (int) $job['id'] ?>" <?= (int) ($filters['job_id'] ?? 0) === (int) $job['id'] ? 'selected' : '' ?>>
                                    <?= esc($job['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Resume</label>
                        <select name="resume" class="form-control">
                            <option value="" <?= ($filters['resume'] ?? '') === '' ? 'selected' : '' ?>>All</option>
                            <option value="yes" <?= ($filters['resume'] ?? '') === 'yes' ? 'selected' : '' ?>>With Resume</option>
                            <option value="no" <?= ($filters['resume'] ?? '') === 'no' ? 'selected' : '' ?>>Without Resume</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>&#160;&#160;
                         <a href="<?= base_url('recruiter/candidates') ?>" class="btn btn-outline-primary">Reset</a>
                    </div>
                </div> 
            </form>
        </div>
    </div>

    <?php if (!empty($selectedJob)): ?>
        <div class="card shadow-sm recruiter-ai-suggestions-card mb-4" style="border-radius: 20px !important;overflow: hidden;">
            <div class="card-header py-3 bg-gradient-primary text-white">
                <h6 class="title mb-3"> AI Candidate Suggestions for <?= esc($selectedJob['title'] ?? 'Selected Job') ?></h6>
            </div>
            <div class="card-body" >
                <?php if (empty($aiSuggestions)): ?>
                    <p class="text-muted mb-0">No suitable candidates found for this role.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover recruiter-candidates-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Candidate</th>
                                    <th>Score</th>
                                    <th>Experience</th>
                                    <th>Skills</th>
                                    <th>AI Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aiSuggestions as $candidate): ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($candidate['name'] ?? '-') ?></strong><br>
                                            <small class="text-muted"><?= esc($candidate['email'] ?? '-') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-success"><?= esc((string) ($candidate['match_score'] ?? 0)) ?>%</span>
                                        </td>
                                        <td><?= esc($candidate['experience_display'] ?? '-') ?></td>
                                        <td><small><?= esc($candidate['skill_name'] ?? '-') ?></small></td>
                                        <td><small><?= esc($candidate['match_reason'] ?? '-') ?></small></td>
                                        <td>
                                            <a href="<?= base_url('recruiter/candidate/' . $candidate['id']) ?>" class="status-pill">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php $aiModeForJob = !empty($selectedJob); ?>
    <?php if (!$aiModeForJob): ?>
        <div class="card shadow-sm recruiter-table-card" style="border-radius: 20px !important;overflow: hidden;">
            <div class="card-header py-3">
                <h6 class="title mb-3">  Candidates (<?= count($candidates ?? []) ?> on this page)</h6>
            </div>
            <div class="card-body">
                <?php if (empty($candidates)): ?>
                    <div class="text-center py-5">
                         
                        <h5>No candidates found</h5>
                        <p class="text-muted mb-0">Try adjusting your filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover recruiter-candidates-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Candidate</th>
                                    <th>Location</th>
                                    <th>Experience</th>
                                    <th>Skills</th>
                                    <th>Resume</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr onclick="window.location='<?= base_url('recruiter/candidate/' . $candidate['id'] . '/view-contact') ?>'" style="cursor:pointer;">
                                        <td>
                                            <strong><?= esc($candidate['name'] ?? '-') ?></strong><br>
                                            <small class="text-muted"><?= esc($candidate['email'] ?? '-') ?></small>
                                        </td>
                                        <td><?= esc($candidate['location'] ?? '-') ?></td>
                                        <td><?= esc($candidate['experience_display'] ?? '-') ?></td>
                                        <td>
                                            <?php if (!empty($candidate['skill_name'])): ?>
                                                <small><?= esc($candidate['skill_name']) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Resume badge — replace both badge spans -->
<?php if (!empty($candidate['resume_path'])): ?>
    <span class="status-pill">Available</span>
<?php else: ?>
    <span class="status-pill">Not Uploaded</span>
<?php endif; ?>
                                        </td>
                                        <td><?= !empty($candidate['created_at']) ? date('M d, Y', strtotime($candidate['created_at'])) : '-' ?></td>
                                        <td>
                                          <div class="application-actions-wrap">
    <a href="<?= base_url('recruiter/candidate/' . $candidate['id']) ?>" class="status-pill">
        View Profile
    </a>
    <?php if (!empty($candidate['resume_path'])): ?>
        <a href="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/download-resume') ?>" class="status-pill">
            Resume
        </a>
    <?php endif; ?>
</div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (isset($pager) && is_object($pager) && method_exists($pager, 'links') && $pager->getPageCount() > 1): ?>
                        <div>
                            <?= $pager->links('default', 'portal_full') ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>
