<?= view('Layouts/recruiter_header', [
    'title' => 'Jobs & Responses',
    'pageStyles' => [base_url('jobboard/css/recruiter-jobs.css?v=' . @filemtime(FCPATH . 'jobboard/css/recruiter-jobs.css'))],
]) ?>

  <style>  
/* ══════════════════════════════════════════
   PAGE & BACKGROUNDS
══════════════════════════════════════════ */
.recruiter-jobs-jobboard,
.recruiter-jobs-jobboard,.hm-page-content {
    background:none !important;
}
body.dark .recruiter-jobs-jobboard,
body.dark .recruiter-jobs-jobboard,body.dark .hm-page-content {
    background: #000000 !important;
}

.recruiter-jobs-jobboard .page-board-header.page-board-header-tight.recruiter-page-board-header,
body.dark .recruiter-jobs-jobboard .page-board-header.page-board-header-tight.recruiter-page-board-header {
    border: none !important;
}

/* ══════════════════════════════════════════
   PAGE TITLE & SUBTITLE
══════════════════════════════════════════ */
.recruiter-jobs-jobboard h1,
.recruiter-jobs-jobboard .page-board-title {
    font-size: 26px;
    font-weight: 700;
    color: #16212B !important;
    margin: 0;
}
body.dark .recruiter-jobs-jobboard h1,
body.dark .recruiter-jobs-jobboard .page-board-title {
    color: #FFFFFF !important;
}
.recruiter-jobs-jobboard .page-board-subtitle,
.recruiter-jobs-jobboard p.text-muted {
    color: #64748B !important;
    font-size: 1rem;
}
body.dark .recruiter-jobs-jobboard .page-board-subtitle,
body.dark .recruiter-jobs-jobboard p.text-muted {
    color: #FFFFFF !important;
}

/* ══════════════════════════════════════════
   FILTER CARD
══════════════════════════════════════════ */
.recruiter-jobs-jobboard .recruiter-filter-card,
.recruiter-jobs-jobboard .card.bg-light {
    background: white !important;
    border: 1px solid #D9ECE5 !important;
    border-radius: 12px !important;
    box-shadow: none !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-filter-card,
body.dark .recruiter-jobs-jobboard .card.bg-light {
    background:  #000000 !important;
    border-color: #23343A !important;
}

/* Filter card labels */
.recruiter-jobs-jobboard .recruiter-filter-card label,
.recruiter-jobs-jobboard .card.bg-light label {
    color: #64748B !important;
    font-size: 0.85rem;
    font-weight: 600;
}
body.dark .recruiter-jobs-jobboard .recruiter-filter-card label,
body.dark .recruiter-jobs-jobboard .card.bg-light label {
    color: #FFFFFF !important;
}

/* Filter card inputs & selects */
.recruiter-jobs-jobboard .recruiter-filter-card .form-control,
.recruiter-jobs-jobboard .card.bg-light .form-control {
    font-size: 1rem;
    background-color: #ffffff !important;
    color: #16212B !important;
    border: 1px solid #D9ECE5 !important;
    border-radius: 6px !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-filter-card .form-control,
body.dark .recruiter-jobs-jobboard .card.bg-light .form-control {
    background-color: #000000 !important;
    color: #FFFFFF !important;
    border-color: #2E4A52 !important;
}
body.dark .recruiter-jobs-jobboard .form-control::placeholder {
    color: #FFFFFF !important;
}

.recruiter-jobs-jobboard .recruiter-filter-card {
    margin-bottom: 14px !important;
}
.recruiter-jobs-jobboard .recruiter-filter-card .card-body {
    padding: 14px 16px !important;
}
.recruiter-jobs-filter-grid {
    align-items: end;
    display: grid;
    gap: 10px;
    grid-template-columns: minmax(260px, 1fr) minmax(180px, 0.7fr) auto;
}
.recruiter-jobs-filter-grid .form-control {
    min-height: 40px !important;
    padding: 8px 12px !important;
}
.recruiter-jobs-filter-actions .btn {
    min-height: 40px;
    min-width: 150px;
    padding: 8px 16px !important;
}
@media (max-width: 767.98px) {
    .recruiter-jobs-filter-grid {
        grid-template-columns: 1fr;
    }
    .recruiter-jobs-filter-actions .btn {
        width: 100%;
    }
}

/* ══════════════════════════════════════════
   TABLE CARD WRAPPER
══════════════════════════════════════════ */
.recruiter-jobs-jobboard .recruiter-table-card,
.recruiter-jobs-jobboard .table-responsive.recruiter-table-card {
    background: white !important;
    border: 1px solid #D9ECE5 !important;
    border-radius: 12px !important;
    overflow: visible !important;
    box-shadow: none !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-table-card,
body.dark .recruiter-jobs-jobboard .table-responsive.recruiter-table-card {
    background:  #000000 !important;
    border-color: #23343A !important;
}

/* ══════════════════════════════════════════
   TABLE BASE
══════════════════════════════════════════ */
.recruiter-jobs-jobboard .recruiter-jobs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 1rem;
    /* remove Bootstrap's bg-white on the table itself */
    background: transparent !important;
    border: none !important;
    border-radius: 0 !important;
}

/* ── Head ── */
.recruiter-jobs-jobboard .recruiter-jobs-table thead tr {
    background: #F0FAF7 !important;
    border-bottom: 2px solid #D9ECE5 !important;
}
.recruiter-jobs-jobboard .recruiter-jobs-table thead th {
    padding: 13px 16px !important;
    font-size: 0.9rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: #64748B !important;
    white-space: nowrap;
    border: none !important;
    background: transparent !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table thead tr {
    background: #000000 !important;
    border-bottom-color: #23343A !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table thead th {
    color: #FFFFFF !important;
    background:  #000000 !important;
}

/* ── Body rows ── */
.recruiter-jobs-jobboard .recruiter-jobs-table tbody tr {
    border-bottom: 1px solid #EEF2F7 !important;
    transition: background 0.15s;
    background: transparent !important;
}
.recruiter-jobs-jobboard .recruiter-jobs-table tbody tr:last-child {
    border-bottom: none !important;
}
.recruiter-jobs-jobboard .recruiter-jobs-table tbody tr:hover {
    background: #F4FBFA !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table tbody tr {
    border-bottom-color: #23343A !important;
    background: transparent !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table tbody tr:hover {
    background: rgba(31, 183, 181, 0.05) !important;
}

/* ── Cells ── */
.recruiter-jobs-jobboard .recruiter-jobs-table tbody td {
    padding: 14px 16px !important;
    vertical-align: middle !important;
    font-size: 1rem !important;
    color: #16212B !important;
    border: none !important;
    background: transparent !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table tbody td {
    color: #000000 !important;
}

/* ── Job title strong ── */
.recruiter-jobs-jobboard .recruiter-jobs-table .job-title,
.recruiter-jobs-jobboard .recruiter-jobs-table tbody td .font-weight-bold {
    font-weight: 600;
    font-size: 1rem;
    color: #16212B !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table .job-title,
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table tbody td .font-weight-bold {
    color: #FFFFFF !important;
}

/* ── Small / muted text in cells ── */
.recruiter-jobs-jobboard .recruiter-jobs-table tbody td small,
.recruiter-jobs-jobboard .recruiter-jobs-table tbody td .text-muted {
    font-size: 0.85rem !important;
    color: #64748B !important;
    display: block;
    margin-top: 2px;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table tbody td small,
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table tbody td .text-muted {
    color: #FFFFFF !important;
}

/* ── Applicant count ── */
.recruiter-jobs-jobboard .recruiter-jobs-table tbody td:nth-child(3) {
    color: #64748B !important;
    font-size: 1rem;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table tbody td:nth-child(3) {
    color: #FFFFFF !important;
}

/* ══════════════════════════════════════════
   STATUS BADGE (open / closed)
══════════════════════════════════════════ */
.recruiter-jobs-jobboard .badge-success {
    background: #D1FAE5 !important;
    color: #065F46 !important;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 50px;
    border: none;
}
.recruiter-jobs-jobboard .badge-secondary {
    background: #F1F5F9 !important;
    color: #475569 !important;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 50px;
    border: none;
}
body.dark .recruiter-jobs-jobboard .badge-success {
    background: #064E3B !important;
    color: #6EE7B7 !important;
}
body.dark .recruiter-jobs-jobboard .badge-secondary {
    background: #1E293B !important;
    color: #94A3B8 !important;
}

/* ══════════════════════════════════════════
   ACTION BUTTONS
══════════════════════════════════════════ */
.recruiter-jobs-jobboard .job-actions-wrap {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 6px !important;
    align-items: center !important;
    justify-content: flex-end;
}

/* Pill-style action links */
.recruiter-jobs-jobboard .status-pill{
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
body.dark .recruiter-jobs-jobboard .status-pill {
    background:  #000000 !important;
    color: #0D8A90;
}
.recruiter-jobs-jobboard .btn-outline-primary {
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
    padding: 5px 14px !important;
    border-radius: 50px !important;
    font-size: 0.9rem !important;
    font-weight: 600 !important;
    background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
    color: #0D8A90 !important;
    border: none !important;
    text-decoration: none !important;
    white-space: nowrap;
    transition: opacity 0.2s;
}
 
.recruiter-jobs-jobboard .btn-outline-primary:hover {
    opacity: 0.8;
    color: #0D8A90 !important;
    text-decoration: none !important;
}
body.dark .recruiter-jobs-jobboard .btn-outline-primary {
    background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important; 
     border: 2px solid #1FB7B5;
    color: #1FB7B5 !important;
    font-size: 0.9rem !important;
}

body.dark .recruiter-jobs-jobboard .btn-outline-primary:hover {
    background: #000000 !important;
    border: 1px solid rgba(31, 183, 181, 0.15) !important;
    color: #ffffff !important;
}
body.dark .recruiter-jobs-jobboard .status-pill,
body.dark .recruiter-jobs-jobboard .btn-outline-primary {
    background: #000000 !important;
    color: #1FB7B5 !important;
    border: 1px solid rgba(31, 183, 181, 0.15) !important;
}

/* Primary button */
.recruiter-jobs-jobboard .btn-primary {
    background: transparent !important;
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important;
    padding: 8px 20px;
    border-radius: 6px !important;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}
.recruiter-jobs-jobboard .btn-primary:hover,
.recruiter-jobs-jobboard .btn-primary:focus {
     background:  #1FB7B5 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
}

/* ══════════════════════════════════════════
   EMPTY STATE
══════════════════════════════════════════ */
.recruiter-jobs-jobboard .alert-info {
    background: #EFF9F9 !important;
    border: 1px solid #B2E4E4 !important;
    color: #0D8A90 !important;
    border-radius: 8px;
    font-size: 1rem;
}
body.dark .recruiter-jobs-jobboard .alert-info {
    background: #162327 !important;
    border-color: #1FB7B540 !important;
    color: #1FB7B5 !important;
}

/* ══════════════════════════════════════════
   PAGINATION
══════════════════════════════════════════ */
.recruiter-jobs-jobboard ul.pagination li.page-item a.page-link,
.recruiter-jobs-jobboard ul.pagination li.page-item span.page-link {
    color: #1FB7B5 !important;
    background-color: transparent !important;
    border-color: #D9ECE5 !important;
    font-size: 1rem;
    text-decoration: none !important;
}
.recruiter-jobs-jobboard ul.pagination li.page-item a.page-link:hover {
    color: #0D8A90 !important;
    background-color: #F0FAF7 !important;
    border-color: #1FB7B5 !important;
}
.recruiter-jobs-jobboard ul.pagination li.page-item.active .page-link {
    color: #ffffff !important;
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 100%) !important;
    border-color: #1FB7B5 !important;
}
.recruiter-jobs-jobboard ul.pagination li.page-item.disabled .page-link {
    color: #94A3B8 !important;
    background-color: transparent !important;
    border-color: #D9ECE5 !important;
}
body.dark .recruiter-jobs-jobboard ul.pagination li.page-item a.page-link,
body.dark .recruiter-jobs-jobboard ul.pagination li.page-item span.page-link {
    color: #FFFFFF !important;
    background-color: transparent !important;
    border-color: #23343A !important;
}
body.dark .recruiter-jobs-jobboard ul.pagination li.page-item a.page-link:hover {
    background-color: rgba(31, 183, 181, 0.08) !important;
    border-color: #1FB7B5 !important;
}
body.dark .recruiter-jobs-jobboard ul.pagination li.page-item.active .page-link {
    color: #ffffff !important;
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 100%) !important;
    border-color: #1FB7B5 !important;
}
body.dark .recruiter-jobs-jobboard ul.pagination li.page-item.disabled .page-link {
    color: #3D5560 !important;
    border-color: #23343A !important;
}
#q:focus, .row.align-items-end.form-control:focus,
  .row.align-items-end select.form-control:focus,
  .row.align-items-end textarea.form-control:focus {
    outline: 0 !important;
    box-shadow: none !important;   /* ← add this */
    border-color: #0D8A90 !important; 
}
/* ── Also reset Bootstrap's base .form-control focus ── */
.form-control:focus {
    box-shadow: none !important;   /* ← already there, add !important */
    border-color: #0D8A90;
}
/* ══════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════ */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
 .container-fluid {
    max-width: 100% !important;
    padding-left: 34px !important;
    padding-right: 34px !important;
}
.recruiter-jobs-jobboard #jobs-list .card,
.recruiter-jobs-jobboard .recruiter-filter-card{
    border-radius: 20px !important;
    overflow: hidden;
}
.recruiter-jobs-jobboard .hm-job-dropdown-menu { display:none; }
.recruiter-jobs-jobboard .hm-job-dropdown-item {
    display:block;
    padding:8px 16px;
    font-size:0.88rem;
    font-weight:500;
    color:#16212B;
    text-decoration:none !important;
    transition:background .15s;
    white-space:nowrap;
}
.recruiter-jobs-jobboard .hm-job-dropdown-item:hover { background:#EDF8F5; color:#0D8A90; }
.recruiter-jobs-jobboard .hm-attention-stack {
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    margin-top:8px;
}
.recruiter-jobs-jobboard .hm-attention-pill {
    align-items:center;
    background:#FFF7ED;
    border:1px solid #FDBA74;
    border-radius:999px;
    color:#9A3412;
    display:inline-flex;
    font-size:.74rem;
    font-weight:700;
    line-height:1;
    padding:6px 9px;
}
.recruiter-jobs-jobboard .hm-attention-pill.is-critical {
    background:#FEF2F2;
    border-color:#FCA5A5;
    color:#B91C1C;
}
.recruiter-jobs-jobboard .hm-attention-pill.is-watch {
    background:#FFFBEB;
    border-color:#FCD34D;
    color:#92400E;
}
.recruiter-jobs-jobboard .hm-job-match-meta {
    color:#64748B;
    display:block;
    font-size:.78rem;
    margin-top:2px;
}
.recruiter-jobs-jobboard .hm-attention-summary {
    color:#64748B;
    display:block;
    font-size:.8rem;
    font-weight:500;
    margin-top:6px;
}
.recruiter-jobs-jobboard .hm-job-quick-actions {
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:10px;
}
.recruiter-jobs-jobboard .hm-job-quick-action {
    align-items:center;
    background:#FFFFFF;
    border:1px solid #B9E8E4;
    border-radius:999px;
    color:#008C95;
    display:inline-flex;
    font-size:.78rem;
    font-weight:700;
    padding:7px 11px;
    text-decoration:none !important;
}
.recruiter-jobs-jobboard .hm-job-quick-action.is-primary {
    background:#1FB7B5;
    border-color:#1FB7B5;
    color:#FFFFFF;
}
.recruiter-jobs-jobboard .hm-job-quick-action:hover,
.recruiter-jobs-jobboard .hm-job-quick-action:focus {
    border-color:#0D8A90;
    color:#0D8A90;
}
.recruiter-jobs-jobboard .hm-job-quick-action.is-primary:hover,
.recruiter-jobs-jobboard .hm-job-quick-action.is-primary:focus {
    background:#0D8A90;
    color:#FFFFFF;
}
.recruiter-jobs-jobboard .hm-alert-center {
    background:#FFFFFF;
    border:1px solid #D9ECE5;
    border-radius:20px;
    margin-bottom:16px;
    overflow:hidden;
}
.recruiter-jobs-jobboard .hm-alert-center-head {
    align-items:center;
    border-bottom:1px solid #D9ECE5;
    display:flex;
    justify-content:space-between;
    padding:16px 18px;
}
.recruiter-jobs-jobboard .hm-alert-center-title {
    align-items:center;
    color:#16212B;
    display:flex;
    font-size:1rem;
    font-weight:800;
    gap:9px;
    margin:0;
}
.recruiter-jobs-jobboard .hm-alert-count {
    background:#E0F5F0;
    border:1px solid #B9E8E4;
    border-radius:999px;
    color:#008C95;
    font-size:.76rem;
    font-weight:800;
    padding:5px 9px;
}
.recruiter-jobs-jobboard .hm-alert-list {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:0;
}
.recruiter-jobs-jobboard .hm-alert-item {
    border-right:1px solid #E6F1ED;
    color:#16212B;
    display:block;
    padding:14px 16px;
    text-decoration:none !important;
}
.recruiter-jobs-jobboard .hm-alert-item:hover,
.recruiter-jobs-jobboard .hm-alert-item:focus {
    background:#F8FCFB;
}
.recruiter-jobs-jobboard .hm-alert-topline {
    align-items:center;
    display:flex;
    gap:8px;
    justify-content:space-between;
}
.recruiter-jobs-jobboard .hm-alert-title {
    font-size:.9rem;
    font-weight:800;
}
.recruiter-jobs-jobboard .hm-alert-tone {
    border-radius:999px;
    flex:0 0 auto;
    height:9px;
    width:9px;
}
.recruiter-jobs-jobboard .hm-alert-tone.is-danger { background:#EF4444; }
.recruiter-jobs-jobboard .hm-alert-tone.is-warning { background:#F59E0B; }
.recruiter-jobs-jobboard .hm-alert-tone.is-info { background:#1FB7B5; }
.recruiter-jobs-jobboard .hm-alert-meta,
.recruiter-jobs-jobboard .hm-alert-detail {
    color:#64748B;
    display:block;
    font-size:.78rem;
    margin-top:4px;
}
.recruiter-jobs-jobboard .hm-alert-action {
    color:#008C95;
    display:inline-flex;
    font-size:.78rem;
    font-weight:800;
    margin-top:9px;
}
.recruiter-jobs-jobboard .hm-alert-empty {
    color:#64748B;
    font-size:.86rem;
    padding:16px 18px;
}
body.dark .recruiter-jobs-jobboard .hm-job-dropdown-menu { background:#111 !important; border-color:#23343A !important; }
body.dark .recruiter-jobs-jobboard .hm-job-dropdown-item { color:#94A3B8 !important; }
body.dark .recruiter-jobs-jobboard .hm-job-dropdown-item:hover { background:rgba(31,183,181,.1) !important; color:#F8FAFC !important; }
body.dark .recruiter-jobs-jobboard .hm-attention-pill { background:rgba(251,146,60,.12); border-color:rgba(251,146,60,.38); color:#FDBA74; }
body.dark .recruiter-jobs-jobboard .hm-attention-pill.is-critical { background:rgba(248,113,113,.12); border-color:rgba(248,113,113,.38); color:#FCA5A5; }
body.dark .recruiter-jobs-jobboard .hm-attention-pill.is-watch { background:rgba(245,158,11,.12); border-color:rgba(245,158,11,.42); color:#FCD34D; }
body.dark .recruiter-jobs-jobboard .hm-job-match-meta { color:#94A3B8; }
body.dark .recruiter-jobs-jobboard .hm-attention-summary { color:#94A3B8; }
body.dark .recruiter-jobs-jobboard .hm-job-quick-action { background:#111; border-color:#23343A; color:#5EEAD4; }
body.dark .recruiter-jobs-jobboard .hm-job-quick-action.is-primary { background:#1FB7B5; border-color:#1FB7B5; color:#FFFFFF; }
body.dark .recruiter-jobs-jobboard .hm-alert-center,
body.dark .recruiter-jobs-jobboard .hm-alert-center-head,
body.dark .recruiter-jobs-jobboard .hm-alert-item { background:#000; border-color:#23343A; }
body.dark .recruiter-jobs-jobboard .hm-alert-center-title,
body.dark .recruiter-jobs-jobboard .hm-alert-title { color:#F8FAFC; }
body.dark .recruiter-jobs-jobboard .hm-alert-meta,
body.dark .recruiter-jobs-jobboard .hm-alert-detail,
body.dark .recruiter-jobs-jobboard .hm-alert-empty { color:#94A3B8; }
</style> 

<div
    id="recruiterJobsPage"
    class="recruiter-jobs-jobboard"
    data-status-url-base="<?= base_url('recruiter/applications/update-status/') ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-hash="<?= csrf_hash() ?>"
>
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <h1 class="page-board-title">Jobs Management</h1>
            <p class="page-board-subtitle">Review job posts, applicant volume, and hiring status from one workspace.</p>
        </div>
        <div class="page-board-actions">
            <a href="<?= base_url('recruiter/post_job') ?>" class="btn btn-primary">Post New Job</a>
        </div>
    </div>

    <div id="jobs-list">
            <section class="hm-alert-center" aria-label="Recruiter alerts">
                <div class="hm-alert-center-head">
                    <h2 class="hm-alert-center-title"><i class="fas fa-bell"></i> Attention Inbox</h2>
                    <span class="hm-alert-count"><?= count((array) ($recruiterAlerts ?? [])) ?> active</span>
                </div>
                <?php if (empty($recruiterAlerts)): ?>
                    <div class="hm-alert-empty">No urgent recruiter alerts right now.</div>
                <?php else: ?>
                    <div class="hm-alert-list">
                        <?php foreach ((array) $recruiterAlerts as $alert): ?>
                            <a class="hm-alert-item" href="<?= esc($alert['url'] ?? '#') ?>">
                                <span class="hm-alert-topline">
                                    <span class="hm-alert-title"><?= esc($alert['title'] ?? '') ?></span>
                                    <span class="hm-alert-tone is-<?= esc($alert['tone'] ?? 'info') ?>"></span>
                                </span>
                                <span class="hm-alert-meta"><?= esc($alert['meta'] ?? '') ?></span>
                                <span class="hm-alert-detail"><?= esc($alert['detail'] ?? '') ?></span>
                                <span class="hm-alert-action"><?= esc($alert['action'] ?? 'Open') ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="card bg-light recruiter-filter-card">
                <div class="card-body">
                    <form action="<?= base_url('recruiter/jobs') ?>" method="get" class="recruiter-jobs-filter-grid">
                        <div>
                            <label class="sr-only">Search Jobs</label>
                            <input type="text" name="q" id="q" class="form-control" placeholder="Search by title..." value="<?= esc($filters['q']) ?>">
                        </div>
                        <div>
                            <label class="sr-only">Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active Jobs</option>
                                <option value="closed" <?= $filters['status'] === 'closed' ? 'selected' : '' ?>>Closed Jobs</option>
                                <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>All Jobs</option>
                            </select>
                        </div>
                        <div class="recruiter-jobs-filter-actions">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="alert alert-info">No jobs found matching your criteria.</div>
            <?php else: ?>
                <div class="table-responsive recruiter-table-card" style="border-radius:20px !important;overflow:visible !important;">
                    <table class="table table-hover bg-white border rounded recruiter-jobs-table" style="overflow:visible;">
                        <thead class="bg-light">
                            <tr>
                                <th>Job Title</th>
                                <th>Status</th>
                                <th>Applicants</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                                <?php
                                    $attentionLevel = (string) ($job['attention_level'] ?? 'quiet');
                                    $showAttention = in_array($attentionLevel, ['critical', 'watch'], true);
                                    $attentionLabel = $attentionLevel === 'critical' ? 'Critical priority' : 'Watch';
                                    $attentionFacts = array_values((array) ($job['attention_facts'] ?? []));
                                    $attentionSummaryParts = [
                                        (int) ($job['shortlisted_count'] ?? 0) . ' shortlisted',
                                    ];
                                    if ((int) ($job['applicant_count'] ?? 0) > 0) {
                                        $attentionSummaryParts[] = (int) ($job['average_ats_score'] ?? 0) . '% avg match';
                                    }
                                    $attentionSummaryParts = array_merge($attentionSummaryParts, $attentionFacts);
                                ?>
                                <tr class="hm-job-row" data-href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>" style="cursor:pointer;">
                                    <td>
                                        <div class="job-title"><?= esc($job['title']) ?></div>
                                        <small class="text-muted"><?= esc($job['location']) ?></small>
                                        <?php if ($showAttention): ?>
                                            <div class="hm-attention-stack" aria-label="Needs attention">
                                                <span class="hm-attention-pill is-<?= esc($attentionLevel) ?>"><?= esc($attentionLabel) ?></span>
                                            </div>
                                            <span class="hm-attention-summary"><?= esc(implode(' · ', array_unique($attentionSummaryParts))) ?></span>
                                            <?php if (!empty($job['suggested_actions'])): ?>
                                                <div class="hm-job-quick-actions">
                                                    <?php foreach (array_slice((array) $job['suggested_actions'], 0, 2) as $action): ?>
                                                        <a class="hm-job-quick-action <?= !empty($action['primary']) ? 'is-primary' : '' ?>"
                                                           href="<?= esc($action['url'] ?? '#') ?>">
                                                            <?= esc($action['label'] ?? 'Open') ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $statusColor = $job['status'] == 'open' ? '#1FB7B5' : '#ef4444'; ?>
                                        <span style="color:<?= $statusColor ?>;font-weight:600;"><?= ucfirst($job['status']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= $job['applicant_count'] ?></strong>
                                        <small class="text-muted d-block"><?= $job['shortlisted_count'] ?> shortlisted</small>
                                        <?php if ((int) ($job['applicant_count'] ?? 0) > 0): ?>
                                            <span class="hm-job-match-meta"><?= (int) ($job['average_ats_score'] ?? 0) ?>% avg match</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right hm-job-actions-cell" style="overflow:visible;">
                                        <div class="hm-job-dropdown" style="position:relative;display:inline-block;">
                                            <button class="btn btn-sm btn-outline-primary hm-job-more-btn" type="button" title="More actions" style="padding:5px 11px;border:1.5px solid #1FB7B5 !important;border-radius:6px !important;">
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block;">
  <circle cx="3" cy="8" r="1.5" fill="currentColor"/>
  <circle cx="8" cy="8" r="1.5" fill="currentColor"/>
  <circle cx="13" cy="8" r="1.5" fill="currentColor"/>
</svg>
                                            </button>
                                            <div class="hm-job-dropdown-menu" style="display:none;position:absolute;right:0;top:calc(100% + 4px);min-width:160px;background:#fff;border:1px solid #D9ECE5;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:99999;padding:4px 0;">
                                                <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>">View Pipeline</a>
                                                <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/' . $job['id'] . '/leaderboard') ?>">Leaderboard</a>
                                                <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/edit/' . $job['id']) ?>">Edit Job</a>
                                                <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/preview/' . $job['id']) ?>" target="_blank">Preview</a>
                                                <div style="height:1px;background:#D9ECE5;margin:4px 0;"></div>
                                                <?php if ($job['status'] === 'open'): ?>
                                                <a class="hm-job-dropdown-item" style="color:#ef4444;" href="<?= base_url('recruiter/jobs/close/' . $job['id']) ?>" onclick="return confirm('Close this job?')">Close Job</a>
                                                <?php else: ?>
                                                <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/reopen/' . $job['id']) ?>">Reopen Job</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pager->getPageCount() > 1): ?>
                    <div class="mt-4">
                        <?= $pager->links('default', 'portal_full') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Row click → pipeline (skip actions cell)
    document.querySelectorAll('.hm-job-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.hm-job-actions-cell') || e.target.closest('.hm-job-quick-action')) return;
            window.location = row.dataset.href;
        });
    });

    // … button toggle
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.hm-job-more-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            var menu = btn.parentElement.querySelector('.hm-job-dropdown-menu');
            var isOpen = menu.style.display === 'block';
            document.querySelectorAll('.hm-job-dropdown-menu').forEach(function (m) { m.style.display = 'none'; });
            menu.style.display = isOpen ? 'none' : 'block';
            return;
        }
        // close on outside click
        if (!e.target.closest('.hm-job-dropdown')) {
            document.querySelectorAll('.hm-job-dropdown-menu').forEach(function (m) { m.style.display = 'none'; });
        }
    });
});
</script>
<?= view('Layouts/recruiter_footer', [
    'pageScripts' => [base_url('jobboard/js/recruiter-jobs.js?v=' . @filemtime(FCPATH . 'jobboard/js/recruiter-jobs.js'))],
]) ?>
