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
    box-shadow: none !important;
    outline: 0 !important;
    overflow: hidden;
}
body.dark .recruiter-table-card,
body.dark .recruiter-filter-card {
    border-color: #23343A !important;
    box-shadow: none !important;
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

.recruiter-candidates-jobboard .recruiter-filter-card {
    margin-bottom: 14px !important;
}

.recruiter-candidates-jobboard .recruiter-filter-card .card-body {
    padding: 14px 16px !important;
}

.recruiter-filter-compact-head {
    align-items: center;
    display: flex;
    gap: 10px;
    justify-content: space-between;
    margin-bottom: 10px;
}

.recruiter-filter-compact-title {
    color: #16212B;
    font-size: 0.92rem;
    font-weight: 700;
    line-height: 1.2;
    margin: 0;
}

.recruiter-filter-compact-hint {
    color: #64748B;
    font-size: 0.78rem;
    margin: 0;
}

body.dark .recruiter-filter-compact-title,
body.dark .recruiter-filter-compact-hint {
    color: #ffffff !important;
}

.recruiter-filter-grid {
    align-items: end;
    display: grid;
    gap: 10px;
    grid-template-columns: minmax(210px, 1.4fr) minmax(150px, 0.9fr) minmax(150px, 0.9fr) minmax(92px, 0.5fr) minmax(92px, 0.5fr) minmax(190px, 1fr) auto;
}

.recruiter-filter-grid .form-control {
    min-height: 40px !important;
    padding: 8px 12px !important;
}

.recruiter-filter-actions-compact {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.recruiter-filter-actions-compact .btn {
    min-height: 40px;
    padding: 8px 14px !important;
}

.recruiter-filter-actions-compact .btn-icon {
    width: 44px;
    padding-left: 0 !important;
    padding-right: 0 !important;
}

@media (max-width: 1399.98px) {
    .recruiter-filter-grid {
        grid-template-columns: minmax(210px, 1.35fr) minmax(150px, 0.9fr) minmax(150px, 0.9fr) repeat(2, minmax(92px, 0.5fr));
    }

    .recruiter-filter-grid .filter-job,
    .recruiter-filter-grid .recruiter-filter-actions-compact {
        grid-column: span 2;
    }
}

@media (max-width: 767.98px) {
    .recruiter-filter-grid {
        grid-template-columns: 1fr;
    }

    .recruiter-filter-grid .filter-job,
    .recruiter-filter-grid .recruiter-filter-actions-compact {
        grid-column: auto;
    }

    .recruiter-filter-actions-compact {
        justify-content: stretch;
    }

    .recruiter-filter-actions-compact .btn {
        flex: 1 1 auto;
    }
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
body.dark .recruiter-candidates-jobboard .card {
    border: 1px solid #23343A !important;
}
body.dark .recruiter-candidates-jobboard .card-header {
    border: 0 !important;
    border-bottom: 1px solid #23343A !important;
}
body.dark .recruiter-candidates-jobboard .card-body {
    border: 0 !important;
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

.recruiter-bulk-invite-form {
    margin-bottom: 18px;
}
.recruiter-bulk-invite-bar {
    display: grid;
    grid-template-columns: minmax(220px, 0.7fr) minmax(360px, 1.3fr);
    align-items: center;
    gap: 18px;
    padding: 14px 16px;
    border: 1px solid #D9ECE5;
    border-radius: 12px;
    background: #F4FBFA;
}
.recruiter-bulk-invite-title {
    color: #16212B;
    font-weight: 700;
    margin-bottom: 3px;
}
.recruiter-bulk-invite-actions {
    display: grid;
    grid-template-columns: minmax(200px, 0.9fr) minmax(220px, 1fr) auto;
    align-items: center;
    gap: 12px;
}
.recruiter-bulk-invite-note {
    min-height: 44px;
    height: 44px;
    resize: none;
}
.recruiter-bulk-invite-submit {
    min-height: 44px;
    white-space: nowrap;
}
.recruiter-bulk-selection-count {
    color: #0D8A90;
    font-weight: 700;
}
.recruiter-candidate-checkbox {
    width: 17px;
    height: 17px;
    accent-color: #1FB7B5;
    cursor: pointer;
}
.recruiter-match-score {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 54px;
    width: auto !important;
    padding: 6px 11px !important;
    border: 1px solid rgba(31, 183, 181, 0.28) !important;
    border-radius: 999px !important;
    background: rgba(31, 183, 181, 0.12) !important;
    color: #0D8A90 !important;
    font-size: 0.78rem !important;
    font-weight: 700 !important;
    line-height: 1 !important;
    white-space: nowrap !important;
    overflow-wrap: normal !important;
    word-break: keep-all !important;
}
.application-actions-wrap .recruiter-resume-icon-action {
    width: 34px !important;
    min-width: 34px !important;
    height: 34px !important;
    flex: 0 0 34px !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(31, 183, 181, 0.28) !important;
    border-radius: 9px !important;
    background: rgba(31, 183, 181, 0.08) !important;
    color: #0D8A90 !important;
    line-height: 1 !important;
    box-sizing: border-box;
    text-decoration: none !important;
}
.application-actions-wrap .recruiter-resume-icon-action i {
    display: block;
    margin: 0;
    font-size: 13px;
    line-height: 1;
}
.application-actions-wrap .recruiter-resume-icon-action:hover,
.application-actions-wrap .recruiter-resume-icon-action:focus {
    background: #1FB7B5 !important;
    border-color: #1FB7B5 !important;
    color: #ffffff !important;
}
body.dark .application-actions-wrap .recruiter-resume-icon-action {
    background: rgba(31, 183, 181, 0.12) !important;
    border-color: #23343A !important;
    color: #5EEAD4 !important;
}
body.dark .recruiter-match-score {
    border-color: rgba(31, 183, 181, 0.38) !important;
    background: rgba(31, 183, 181, 0.14) !important;
    color: #5EEAD4 !important;
}
body.dark .recruiter-bulk-invite-bar {
    background: #111111;
    border-color: #23343A;
}
body.dark .recruiter-bulk-invite-title {
    color: #F8FAFC;
}
@media (max-width: 991.98px) {
    .recruiter-bulk-invite-bar,
    .recruiter-bulk-invite-actions {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 575.98px) {
    .recruiter-bulk-invite-submit {
        width: 100%;
    }
}
</style>
<?php
$selectedJobTitle = (string) ($selectedJob['title'] ?? '');
$candidateCount = count($candidates ?? []);
$aiSuggestionCount = count($aiSuggestions ?? []);
$returnTo = current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
$hasSelectableCandidates = $candidateCount > 0 || $aiSuggestionCount > 0;
?>
<div class="recruiter-candidates-jobboard"
     id="recruiterCandidatePoolPage"
     data-email-url="<?= base_url('recruiter/candidates/send-bulk-email') ?>"
     data-csrf-name="<?= csrf_token() ?>"
     data-csrf-hash="<?= csrf_hash() ?>"
     data-job-title="<?= esc($selectedJobTitle !== '' ? $selectedJobTitle : 'this opportunity') ?>">
<div class="container-fluid py-5">
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

    <div class="card shadow-sm recruiter-filter-card" style="border-radius: 20px !important;overflow: hidden;">
        <div class="card-body">
            <div class="recruiter-filter-compact-head">
                <h6 class="recruiter-filter-compact-title">Search Filters</h6>
                <p class="recruiter-filter-compact-hint">Skill, location, experience, and job fit</p>
            </div>

            <form method="get" action="<?= base_url('recruiter/candidates') ?>" class="recruiter-candidate-filter-form" >
                <div class="recruiter-filter-grid">
                    <div>
                        <label class="sr-only">Keyword</label>
                        <input type="text" name="keyword" class="form-control" value="<?= esc($filters['keyword'] ?? '') ?>" placeholder="Name / Email / Skill">
                    </div>
                    <div>
                        <label class="sr-only">Skills</label>
                        <input type="text" name="skills" class="form-control" value="<?= esc($filters['skills'] ?? '') ?>" placeholder="e.g. PHP">
                    </div>
                    <div>
                        <label class="sr-only">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= esc($filters['location'] ?? '') ?>" placeholder="City / State">
                    </div>
                    <div>
                        <label class="sr-only">Experience minimum in years</label>
                        <input type="number" step="0.5" min="0" name="exp_min" class="form-control" value="<?= esc($filters['exp_min'] ?? '') ?>" placeholder="Min yrs">
                    </div>
                    <div>
                        <label class="sr-only">Experience maximum in years</label>
                        <input type="number" step="0.5" min="0" name="exp_max" class="form-control" value="<?= esc($filters['exp_max'] ?? '') ?>" placeholder="Max yrs">
                    </div>
                    <div class="filter-job">
                        <label class="sr-only">Job Role</label>
                        <select name="job_id" class="form-control">
                            <option value="">Select Job</option>
                            <?php foreach (($recruiterJobs ?? []) as $job): ?>
                                <option value="<?= (int) $job['id'] ?>" <?= (int) ($filters['job_id'] ?? 0) === (int) $job['id'] ? 'selected' : '' ?>>
                                    <?= esc($job['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="recruiter-filter-actions-compact">
                        <button type="submit" class="btn btn-outline-primary btn-icon" aria-label="Search candidates" title="Search candidates">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="<?= base_url('recruiter/candidates') ?>" class="btn btn-outline-primary">Reset</a>
                    </div>
                </div> 
            </form>
        </div>
    </div>

    <?php if ($hasSelectableCandidates): ?>
        <form method="post" action="<?= base_url('recruiter/candidates/invite-job/bulk') ?>"
              class="recruiter-bulk-invite-form mb-4" id="recruiterBulkInviteForm">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="<?= esc($returnTo) ?>">
            <div class="recruiter-bulk-invite-bar">
                <div>
                    <div class="recruiter-bulk-invite-title">Bulk candidate actions</div>
                    <div class="small text-muted">
                        Select candidates from the table. <span class="recruiter-bulk-selection-count" id="bulkCandidateCount">0 selected</span>
                    </div>
                </div>
                <div class="recruiter-bulk-invite-actions">
                    <select name="job_id" class="form-control" id="bulkInviteJobSelect" aria-label="Select job for invitations">
                        <option value="">Select Job to Invite</option>
                        <?php foreach (($recruiterJobs ?? []) as $job): ?>
                            <option value="<?= (int) $job['id'] ?>" <?= (int) ($filters['job_id'] ?? 0) === (int) $job['id'] ? 'selected' : '' ?>>
                                <?= esc($job['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="message" class="form-control recruiter-bulk-invite-note" rows="1" maxlength="500"
                              placeholder="Optional invite note"></textarea>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary recruiter-bulk-invite-submit" id="bulkInviteSubmit" disabled>
                            <i class="fas fa-paper-plane mr-1"></i> Invite
                        </button>
                        <button type="button" class="btn btn-outline-primary recruiter-bulk-invite-submit" id="bulkEmailButton" disabled>
                            <i class="fas fa-at mr-1"></i> Email
                        </button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>

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
                                    <th style="width: 44px;">
                                        <input type="checkbox" class="recruiter-candidate-checkbox js-select-all-candidates"
                                               aria-label="Select all suggested candidates">
                                    </th>
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
                                            <input type="checkbox" name="candidate_ids[]" value="<?= (int) $candidate['id'] ?>"
                                                   class="recruiter-candidate-checkbox js-candidate-checkbox"
                                                   data-email="<?= esc($candidate['email'] ?? '') ?>"
                                                   data-name="<?= esc($candidate['name'] ?? '') ?>"
                                                   aria-label="Select <?= esc($candidate['name'] ?? 'candidate') ?>"
                                                   form="recruiterBulkInviteForm">
                                        </td>
                                        <td>
                                            <strong><?= esc($candidate['name'] ?? '-') ?></strong><br>
                                            <small class="text-muted"><?= esc($candidate['email'] ?? '-') ?></small>
                                        </td>
                                        <td>
                                            <span class="recruiter-match-score"><?= esc((string) ($candidate['match_score'] ?? 0)) ?>%</span>
                                        </td>
                                        <td><?= esc($candidate['experience_display'] ?? '-') ?></td>
                                        <td><small><?= esc($candidate['skill_name'] ?? '-') ?></small></td>
                                        <td><small><?= esc($candidate['match_reason'] ?? '-') ?></small></td>
                                        <td>
                                            <a href="<?= base_url('recruiter/candidate/' . $candidate['id'] . '?job_id=' . (int) ($selectedJob['id'] ?? 0)) ?>" class="status-pill">
                                                View
                                            </a>
                                            <form method="post" action="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/invite-job') ?>" class="d-inline-block">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="job_id" value="<?= (int) ($selectedJob['id'] ?? 0) ?>">
                                                <input type="hidden" name="return_to" value="<?= esc(current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
                                                <button type="submit" class="status-pill">
                                                    <i class="fas fa-paper-plane"></i> Invite
                                                </button>
                                            </form>
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
                                    <th style="width: 44px;">
                                        <input type="checkbox" class="recruiter-candidate-checkbox js-select-all-candidates"
                                               aria-label="Select all candidates">
                                    </th>
                                    <th>Candidate</th>
                                    <th>Location</th>
                                    <th>Experience</th>
                                    <th>Skills</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr onclick="window.location='<?= base_url('recruiter/candidate/' . $candidate['id'] . '/view-contact') ?>'" style="cursor:pointer;">
                                        <td onclick="event.stopPropagation();">
                                            <input type="checkbox" name="candidate_ids[]" value="<?= (int) $candidate['id'] ?>"
                                                   class="recruiter-candidate-checkbox js-candidate-checkbox"
                                                   data-email="<?= esc($candidate['email'] ?? '') ?>"
                                                   data-name="<?= esc($candidate['name'] ?? '') ?>"
                                                   aria-label="Select <?= esc($candidate['name'] ?? 'candidate') ?>"
                                                   form="recruiterBulkInviteForm">
                                        </td>
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
                                        <td><?= !empty($candidate['created_at']) ? date('M d, Y', strtotime($candidate['created_at'])) : '-' ?></td>
                                        <td>
                                          <div class="application-actions-wrap" onclick="event.stopPropagation();">
    <a href="<?= base_url('recruiter/candidate/' . $candidate['id']) ?>" class="status-pill">
        View Profile
    </a>
    <?php if (!empty($candidate['resume_path'])): ?>
        <a href="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/download-resume') ?>"
           class="recruiter-resume-icon-action"
           title="Download <?= esc($candidate['name'] ?? 'candidate') ?>'s resume"
           aria-label="Download <?= esc($candidate['name'] ?? 'candidate') ?>'s resume">
            <i class="fas fa-download" aria-hidden="true"></i>
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

<div class="modal fade" id="candidatePoolEmailModal" tabindex="-1" role="dialog" aria-labelledby="candidatePoolEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="candidatePoolEmailModalLabel"><i class="fas fa-at mr-2"></i>Send Email to Selected Candidates</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="candidatePoolEmailFeedback" class="alert alert-danger d-none" role="alert"></div>
                <div class="form-group">
                    <label class="font-weight-bold">To:</label>
                    <div id="candidatePoolEmailRecipients" class="p-2 border rounded bg-light" style="max-height: 120px; overflow-y: auto;">
                        <span class="text-muted">No recipients selected</span>
                    </div>
                    <small class="text-muted"><span id="candidatePoolEmailRecipientCount">0</span> recipients</small>
                </div>
                <div class="form-group">
                    <label for="candidatePoolEmailSubject" class="font-weight-bold">Subject:</label>
                    <input type="text" class="form-control" id="candidatePoolEmailSubject" placeholder="Enter email subject..." required>
                </div>
                <div class="form-group">
                    <label for="candidatePoolEmailBody" class="font-weight-bold">Message:</label>
                    <textarea class="form-control" id="candidatePoolEmailBody" rows="10" placeholder="Write your email message here..."></textarea>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Quick Templates:</label>
                    <div class="btn-group btn-group-sm flex-wrap">
                        <button type="button" class="btn btn-outline-primary" data-template="invite">Invite Intro</button>
                        <button type="button" class="btn btn-outline-primary" data-template="followup">Follow-up</button>
                        <button type="button" class="btn btn-outline-primary" data-template="availability">Availability</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-primary" id="candidatePoolSendEmailButton">
                    <i class="fas fa-paper-plane mr-1"></i> Send Email
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('recruiterCandidatePoolPage');
    const selectAllBoxes = Array.from(document.querySelectorAll('.js-select-all-candidates'));
    const checkboxes = Array.from(document.querySelectorAll('.js-candidate-checkbox'));
    const submitButton = document.getElementById('bulkInviteSubmit');
    const emailButton = document.getElementById('bulkEmailButton');
    const countLabel = document.getElementById('bulkCandidateCount');
    const bulkForm = document.getElementById('recruiterBulkInviteForm');
    const sendEmailButton = document.getElementById('candidatePoolSendEmailButton');

    if (!checkboxes.length || !submitButton || !countLabel || !bulkForm || !root) return;

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function selectedCheckboxes() {
        return checkboxes.filter(function (checkbox) { return checkbox.checked; });
    }

    function setCandidatePoolEmailFeedback(message, type) {
        const feedback = document.getElementById('candidatePoolEmailFeedback');
        if (!feedback) return;

        feedback.classList.remove('alert-danger', 'alert-success', 'alert-warning');
        if (!message) {
            feedback.classList.add('d-none');
            feedback.textContent = '';
            return;
        }

        feedback.classList.remove('d-none');
        feedback.classList.add(type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger'));
        feedback.textContent = message;
    }

    function syncSelection() {
        const selectedCount = selectedCheckboxes().length;
        selectAllBoxes.forEach(function (selectAll) {
            selectAll.checked = selectedCount === checkboxes.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        });
        submitButton.disabled = selectedCount === 0;
        if (emailButton) {
            emailButton.disabled = selectedCount === 0;
        }
        countLabel.textContent = selectedCount + (selectedCount === 1 ? ' selected' : ' selected');
    }

    selectAllBoxes.forEach(function (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
            syncSelection();
        });
    });

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', syncSelection);
    });

    bulkForm.addEventListener('submit', function (event) {
        if (!selectedCheckboxes().length) {
            event.preventDefault();
            return;
        }

        const jobSelect = document.getElementById('bulkInviteJobSelect');
        if (!jobSelect || !jobSelect.value) {
            event.preventDefault();
            alert('Please select a job before sending invitations.');
        }
    });

    if (emailButton) {
        emailButton.addEventListener('click', function () {
            const selected = selectedCheckboxes().filter(function (checkbox) {
                return checkbox.dataset.email;
            });

            if (!selected.length) {
                alert('Please select at least one candidate with an email address.');
                return;
            }

            const recipientHtml = selected.map(function (checkbox) {
                const name = checkbox.dataset.name || checkbox.dataset.email;
                return '<div class="mb-1"><i class="fas fa-user text-primary mr-1"></i>' +
                    escapeHtml(name) + ' <small class="text-muted">&lt;' + escapeHtml(checkbox.dataset.email) + '&gt;</small></div>';
            }).join('');

            document.getElementById('candidatePoolEmailRecipients').innerHTML = recipientHtml;
            document.getElementById('candidatePoolEmailRecipientCount').textContent = selected.length;
            document.getElementById('candidatePoolEmailSubject').value = '';
            document.getElementById('candidatePoolEmailBody').value = '';
            setCandidatePoolEmailFeedback('');
            if (window.jQuery) {
                window.jQuery('#candidatePoolEmailModal').modal('show');
            }
        });
    }

    document.querySelectorAll('#candidatePoolEmailModal [data-template]').forEach(function (button) {
        button.addEventListener('click', function () {
            const jobTitle = root.dataset.jobTitle || 'this opportunity';
            const templates = {
                invite: {
                    subject: 'Invitation to connect about ' + jobTitle,
                    body: 'Dear Candidate,\n\nYour profile looks relevant for ' + jobTitle + '. We would like to connect and share more details about the opportunity.\n\nBest regards,\nRecruiting Team'
                },
                followup: {
                    subject: 'Following up from HireMatrix',
                    body: 'Dear Candidate,\n\nWe wanted to follow up after reviewing your profile. Please let us know if you are open to discussing relevant opportunities.\n\nBest regards,\nRecruiting Team'
                },
                availability: {
                    subject: 'Availability for a quick discussion',
                    body: 'Dear Candidate,\n\nWe would like to schedule a quick discussion about your experience and current job preferences. Please share a few suitable time slots.\n\nBest regards,\nRecruiting Team'
                }
            };
            const template = templates[button.dataset.template];
            if (!template) return;
            document.getElementById('candidatePoolEmailSubject').value = template.subject;
            document.getElementById('candidatePoolEmailBody').value = template.body;
        });
    });

    if (sendEmailButton) {
        sendEmailButton.addEventListener('click', function () {
            const subject = document.getElementById('candidatePoolEmailSubject').value.trim();
            const body = document.getElementById('candidatePoolEmailBody').value.trim();
            const selectedIds = selectedCheckboxes()
                .filter(function (checkbox) { return checkbox.dataset.email; })
                .map(function (checkbox) { return checkbox.value; });

            if (!subject) {
                setCandidatePoolEmailFeedback('Add an email subject before sending.');
                document.getElementById('candidatePoolEmailSubject').focus();
                return;
            }

            if (!body) {
                setCandidatePoolEmailFeedback('Add the email message before sending.');
                document.getElementById('candidatePoolEmailBody').focus();
                return;
            }

            if (!selectedIds.length) {
                setCandidatePoolEmailFeedback('Select at least one candidate with an email address.');
                return;
            }

            const formData = new FormData();
            selectedIds.forEach(function (id) {
                formData.append('candidate_ids[]', id);
            });
            formData.append('subject', subject);
            formData.append('body', body);
            formData.append(root.dataset.csrfName, root.dataset.csrfHash);

            const originalText = sendEmailButton.innerHTML;
            sendEmailButton.disabled = true;
            sendEmailButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending...';

            fetch(root.dataset.emailUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) throw payload;
                        return payload;
                    });
                })
                .then(function (payload) {
                    if (payload.csrf_hash) {
                        root.dataset.csrfHash = payload.csrf_hash;
                    }
                    if (window.jQuery) {
                        window.jQuery('#candidatePoolEmailModal').modal('hide');
                    }
                    setCandidatePoolEmailFeedback('');
                    alert(payload.message || 'Email sent successfully.');
                    window.location.reload();
                })
                .catch(function (error) {
                    if (error.csrf_hash) {
                        root.dataset.csrfHash = error.csrf_hash;
                    }
                    setCandidatePoolEmailFeedback(error.message || 'Failed to send email. Please try again.');
                })
                .finally(function () {
                    sendEmailButton.disabled = false;
                    sendEmailButton.innerHTML = originalText;
                });
        });
    }

    syncSelection();
});
</script>

<?= view('Layouts/recruiter_footer') ?>

