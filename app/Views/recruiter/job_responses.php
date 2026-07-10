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
    grid-template-columns: minmax(240px, 1fr) minmax(170px, 0.6fr) minmax(210px, 0.7fr) auto;
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
.recruiter-jobs-jobboard .recruiter-jobs-table tbody td:nth-child(5) {
    color: #64748B !important;
    font-size: 1rem;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table tbody td:nth-child(5) {
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
    margin-top:7px;
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
.recruiter-jobs-jobboard .hm-attention-pill.is-soft {
    background:#F8FAFC;
    border-color:#DDE7EF;
    color:#475569;
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
.recruiter-jobs-jobboard .recruiter-jobs-list-card {
    background:#FFFFFF;
    border:1px solid #DDE7EF;
    border-radius:12px;
    box-shadow:0 10px 28px rgba(15,23,42,.05);
    overflow:visible;
}
.recruiter-jobs-jobboard .jobs-bulkbar {
    align-items:center;
    background:#FFFFFF;
    border-bottom:1px solid #E6EDF4;
    display:flex;
    gap:18px;
    justify-content:space-between;
    min-height:52px;
    padding:10px 16px;
}
.recruiter-jobs-jobboard .jobs-bulkbar-left,
.recruiter-jobs-jobboard .jobs-bulkbar-right {
    align-items:center;
    display:flex;
    flex-wrap:wrap;
    gap:14px;
}
.recruiter-jobs-jobboard .jobs-select-control {
    align-items:center;
    color:#475569;
    cursor:pointer;
    display:inline-flex;
    font-size:.84rem;
    font-weight:700;
    gap:8px;
    margin:0;
}
.recruiter-jobs-jobboard .jobs-select-control input,
.recruiter-jobs-jobboard .job-row-check {
    accent-color:#0F766E;
    cursor:pointer;
    height:16px;
    width:16px;
}
.recruiter-jobs-jobboard .jobs-selected-count {
    color:#64748B;
    font-size:.82rem;
    font-weight:700;
}
.recruiter-jobs-jobboard .jobs-bulk-close {
    align-items:center;
    background:#FFFFFF;
    border:1px solid #CBD5E1;
    border-radius:8px;
    color:#334155;
    display:inline-flex;
    font-size:.82rem;
    font-weight:800;
    gap:7px;
    min-height:34px;
    padding:7px 12px;
}
.recruiter-jobs-jobboard .jobs-bulk-close:not(:disabled):hover {
    border-color:#EF4444;
    color:#DC2626;
}
.recruiter-jobs-jobboard .jobs-bulk-close:disabled {
    cursor:not-allowed;
    opacity:.45;
}
.recruiter-jobs-jobboard .recruiter-jobs-table {
    border:0 !important;
    margin:0;
}
.recruiter-jobs-jobboard .recruiter-jobs-table thead th {
    background:#FBFCFE;
    border-bottom:1px solid #DDE7EF;
    color:#64748B;
    font-size:.74rem;
    font-weight:800;
    letter-spacing:.04em;
    padding:13px 16px;
    text-transform:uppercase;
}
.recruiter-jobs-jobboard .recruiter-jobs-table tbody td {
    border-top:1px solid #E8EEF5;
    padding:18px 16px;
    vertical-align:middle;
}
.recruiter-jobs-jobboard .hm-job-row {
    background:#FFFFFF;
    cursor:pointer;
}
.recruiter-jobs-jobboard .hm-job-row:hover {
    background:#FAFBFC;
}
.recruiter-jobs-jobboard .job-select-cell {
    text-align:center;
    width:44px;
}
.recruiter-jobs-jobboard .job-title-cell {
    min-width:360px;
}
.recruiter-jobs-jobboard .job-title-row {
    align-items:center;
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}
.recruiter-jobs-jobboard .job-title {
    color:#0F172A;
    font-size:1rem;
    font-weight:800;
    line-height:1.25;
}
.recruiter-jobs-jobboard .job-subtitle {
    align-items:center;
    color:#64748B;
    display:flex;
    flex-wrap:wrap;
    font-size:.82rem;
    gap:8px;
    margin-top:4px;
}
.recruiter-jobs-jobboard .job-dot {
    color:#CBD5E1;
}
.recruiter-jobs-jobboard .job-status-pill {
    align-items:center;
    border:1px solid #CBD5E1;
    border-radius:999px;
    color:#475569;
    display:inline-flex;
    font-size:.78rem;
    font-weight:800;
    min-width:76px;
    justify-content:center;
    padding:6px 11px;
}
.recruiter-jobs-jobboard .job-status-pill.is-open {
    background:#ECFDF5;
    border-color:#A7F3D0;
    color:#047857;
}
.recruiter-jobs-jobboard .job-status-pill.is-closed {
    background:#F8FAFC;
    border-color:#CBD5E1;
    color:#64748B;
}
.recruiter-jobs-jobboard .job-response-count {
    color:#0F172A;
    display:block;
    font-size:1.05rem;
    font-weight:850;
    line-height:1.1;
}
.recruiter-jobs-jobboard .job-response-meta {
    color:#64748B;
    display:block;
    font-size:.8rem;
    line-height:1.45;
}
.recruiter-jobs-jobboard .hm-job-more-btn {
    align-items:center;
    background:#FFFFFF !important;
    border:1px solid #CBD5E1 !important;
    border-radius:8px !important;
    color:#334155 !important;
    display:inline-flex;
    height:32px;
    justify-content:center;
    padding:0 !important;
    width:42px;
}
.recruiter-jobs-jobboard .hm-job-more-btn:hover,
.recruiter-jobs-jobboard .hm-job-more-btn:focus {
    background:#F8FAFC !important;
    border-color:#94A3B8 !important;
}
.recruiter-jobs-jobboard .hm-job-dropdown {
    display:inline-block;
    position:relative;
}
.recruiter-jobs-jobboard .hm-job-actions-cell {
    white-space:nowrap;
}
.recruiter-jobs-jobboard .hm-job-action-group {
    align-items:center;
    display:inline-flex;
    gap:8px;
    justify-content:flex-end;
}
.recruiter-jobs-jobboard .hm-job-dropdown-menu {
    background:#FFFFFF;
    border:1px solid #DDE7EF;
    border-radius:10px;
    box-shadow:0 18px 40px rgba(15,23,42,.16);
    min-width:174px;
    padding:6px 0;
    position:absolute;
    right:0;
    top:calc(100% + 6px);
    z-index:99999;
}
.recruiter-jobs-jobboard .hm-job-dropdown-separator {
    background:#E8EEF5;
    height:1px;
    margin:6px 0;
}
.recruiter-jobs-jobboard .hm-job-dropdown-item.is-danger {
    color:#DC2626;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-list-card,
body.dark .recruiter-jobs-jobboard .jobs-bulkbar,
body.dark .recruiter-jobs-jobboard .hm-job-row {
    background:#050505;
    border-color:#262626;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table thead th {
    background:#0A0A0A;
    border-color:#262626;
    color:#A3A3A3;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-table tbody td {
    border-color:#262626;
}
body.dark .recruiter-jobs-jobboard .hm-job-row:hover {
    background:#0F0F0F;
}
body.dark .recruiter-jobs-jobboard .job-title,
body.dark .recruiter-jobs-jobboard .job-response-count {
    color:#F5F5F5;
}
body.dark .recruiter-jobs-jobboard .job-subtitle,
body.dark .recruiter-jobs-jobboard .job-response-meta,
body.dark .recruiter-jobs-jobboard .jobs-selected-count,
body.dark .recruiter-jobs-jobboard .jobs-select-control {
    color:#A3A3A3;
}
body.dark .recruiter-jobs-jobboard .jobs-bulk-close,
body.dark .recruiter-jobs-jobboard .hm-job-more-btn {
    background:#0A0A0A !important;
    border-color:#3F3F46 !important;
    color:#D4D4D8 !important;
}
body.dark .recruiter-jobs-jobboard .job-status-pill.is-open {
    background:#101010;
    border-color:#525252;
    color:#E5E5E5;
}
body.dark .recruiter-jobs-jobboard .job-status-pill.is-closed {
    background:#0A0A0A;
    border-color:#3F3F46;
    color:#A3A3A3;
}
body.dark .recruiter-jobs-jobboard .hm-job-dropdown-menu { background:#111 !important; border-color:#333 !important; }
body.dark .recruiter-jobs-jobboard .hm-job-dropdown-item { color:#A3A3A3 !important; }
body.dark .recruiter-jobs-jobboard .hm-job-dropdown-item:hover { background:#1A1A1A !important; color:#F8FAFC !important; }
body.dark .recruiter-jobs-jobboard .hm-attention-pill { background:rgba(251,146,60,.12); border-color:rgba(251,146,60,.38); color:#FDBA74; }
body.dark .recruiter-jobs-jobboard .hm-attention-pill.is-critical { background:rgba(248,113,113,.12); border-color:rgba(248,113,113,.38); color:#FCA5A5; }
body.dark .recruiter-jobs-jobboard .hm-attention-pill.is-watch { background:rgba(245,158,11,.12); border-color:rgba(245,158,11,.42); color:#FCD34D; }
body.dark .recruiter-jobs-jobboard .hm-attention-pill.is-soft { background:#111827; border-color:#334155; color:#CBD5E1; }
body.dark .recruiter-jobs-jobboard .hm-job-match-meta { color:#94A3B8; }
body.dark .recruiter-jobs-jobboard .hm-attention-summary { color:#94A3B8; }
body.dark .recruiter-jobs-jobboard .hm-alert-center,
body.dark .recruiter-jobs-jobboard .hm-alert-center-head,
body.dark .recruiter-jobs-jobboard .hm-alert-item { background:#000; border-color:#23343A; }
body.dark .recruiter-jobs-jobboard .hm-alert-center-title,
body.dark .recruiter-jobs-jobboard .hm-alert-title { color:#F8FAFC; }
body.dark .recruiter-jobs-jobboard .hm-alert-meta,
body.dark .recruiter-jobs-jobboard .hm-alert-detail,
body.dark .recruiter-jobs-jobboard .hm-alert-empty { color:#94A3B8; }

/* Jobs list final shell polish: avoid nested/double borders */
.recruiter-jobs-jobboard .recruiter-jobs-list-card {
    border:1px solid #DDE7EF !important;
    border-radius:14px !important;
    overflow:visible !important;
}
.recruiter-jobs-jobboard .recruiter-jobs-list-card .recruiter-table-card,
.recruiter-jobs-jobboard .recruiter-jobs-list-card .table-responsive.recruiter-table-card {
    background:transparent !important;
    border:0 !important;
    border-radius:0 0 14px 14px !important;
    box-shadow:none !important;
    overflow:visible !important;
}
.recruiter-jobs-jobboard .recruiter-jobs-list-card .jobs-bulkbar {
    border-radius:14px 14px 0 0 !important;
}
.recruiter-jobs-jobboard .recruiter-jobs-list-card .recruiter-jobs-table {
    border:0 !important;
}
.recruiter-jobs-jobboard .recruiter-jobs-list-card .recruiter-jobs-table thead tr {
    border-bottom:1px solid #DDE7EF !important;
}
.recruiter-jobs-jobboard .recruiter-jobs-list-card .recruiter-jobs-table tbody tr:last-child td:first-child {
    border-bottom-left-radius:14px;
}
.recruiter-jobs-jobboard .recruiter-jobs-list-card .recruiter-jobs-table tbody tr:last-child td:last-child {
    border-bottom-right-radius:14px;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-list-card {
    border-color:#262626 !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-list-card .recruiter-table-card,
body.dark .recruiter-jobs-jobboard .recruiter-jobs-list-card .table-responsive.recruiter-table-card {
    border:0 !important;
}
body.dark .recruiter-jobs-jobboard .recruiter-jobs-list-card .recruiter-jobs-table thead tr {
    border-bottom-color:#262626 !important;
}

@media (max-width: 640px) {
    body.recruiter-jobboard main > .recruiter-jobs-jobboard {
        padding: 10px !important;
    }
    .recruiter-jobs-jobboard .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .recruiter-jobs-jobboard .page-board-header {
        border-radius: 18px !important;
        padding: 20px 18px !important;
    }
    .recruiter-jobs-jobboard .page-board-title {
        font-size: 1.45rem !important;
    }
    .recruiter-jobs-jobboard .recruiter-jobs-list-card {
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
    .recruiter-jobs-jobboard .jobs-bulkbar {
        border: 1px solid #DDE7EF !important;
        border-radius: 14px !important;
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        margin-bottom: 10px;
        padding: 12px !important;
    }
    .recruiter-jobs-jobboard .jobs-bulkbar-left,
    .recruiter-jobs-jobboard .jobs-bulkbar-right {
        justify-content: space-between;
        width: 100%;
    }
    .recruiter-jobs-jobboard .jobs-bulk-close {
        justify-content: center;
        width: 100%;
    }
    .recruiter-jobs-jobboard .recruiter-table-card {
        overflow: visible !important;
    }
    .recruiter-jobs-jobboard .recruiter-jobs-table,
    .recruiter-jobs-jobboard .recruiter-jobs-table tbody,
    .recruiter-jobs-jobboard .recruiter-jobs-table tr,
    .recruiter-jobs-jobboard .recruiter-jobs-table td {
        display: block !important;
        width: 100% !important;
    }
    .recruiter-jobs-jobboard .recruiter-jobs-table thead {
        display: none !important;
    }
    .recruiter-jobs-jobboard .recruiter-jobs-table tbody {
        display: grid !important;
        gap: 10px !important;
    }
    .recruiter-jobs-jobboard .hm-job-row {
        background: #FFFFFF !important;
        border: 1px solid #DDE7EF !important;
        border-radius: 16px !important;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .045);
        display: grid !important;
        gap: 10px !important;
        padding: 14px !important;
        position: relative;
    }
    .recruiter-jobs-jobboard .recruiter-jobs-table tbody td {
        border: 0 !important;
        padding: 0 !important;
    }
    .recruiter-jobs-jobboard .job-select-cell {
        left: 14px;
        position: absolute;
        top: 18px;
        width: auto !important;
        z-index: 2;
    }
    .recruiter-jobs-jobboard .job-title-cell {
        min-width: 0 !important;
        padding-left: 28px !important;
    }
    .recruiter-jobs-jobboard .job-title-row {
        align-items: flex-start;
        gap: 8px;
    }
    .recruiter-jobs-jobboard .job-title {
        font-size: 1rem;
        overflow-wrap: anywhere;
    }
    .recruiter-jobs-jobboard .job-subtitle {
        gap: 5px 7px;
        line-height: 1.45;
    }
    .recruiter-jobs-jobboard .hm-attention-stack {
        gap: 6px;
    }
    .recruiter-jobs-jobboard .hm-job-row > td:nth-child(3),
    .recruiter-jobs-jobboard .hm-job-row > td:nth-child(4),
    .recruiter-jobs-jobboard .hm-job-row > td:nth-child(5),
    .recruiter-jobs-jobboard .hm-job-actions-cell {
        align-items: center;
        border-top: 1px solid #EEF2F7 !important;
        display: grid !important;
        gap: 10px;
        grid-template-columns: 88px minmax(0, 1fr);
        padding-top: 10px !important;
        text-align: left !important;
    }
    .recruiter-jobs-jobboard .hm-job-row > td:nth-child(3)::before,
    .recruiter-jobs-jobboard .hm-job-row > td:nth-child(4)::before,
    .recruiter-jobs-jobboard .hm-job-row > td:nth-child(5)::before,
    .recruiter-jobs-jobboard .hm-job-actions-cell::before {
        color: #64748B;
        font-size: .68rem;
        font-weight: 850;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .recruiter-jobs-jobboard .hm-job-row > td:nth-child(3)::before { content: "Posted by"; }
    .recruiter-jobs-jobboard .hm-job-row > td:nth-child(4)::before { content: "Status"; }
    .recruiter-jobs-jobboard .hm-job-row > td:nth-child(5)::before { content: "Responses"; }
    .recruiter-jobs-jobboard .hm-job-actions-cell::before { content: "Actions"; }
    .recruiter-jobs-jobboard .job-status-pill {
        justify-self: start;
    }
    .recruiter-jobs-jobboard .job-response-count {
        display: inline-block;
        margin-right: 5px;
    }
    .recruiter-jobs-jobboard .hm-job-dropdown {
        justify-self: start;
    }
    .recruiter-jobs-jobboard .hm-job-dropdown-menu {
        left: 0;
        right: auto;
    }
    body.dark .recruiter-jobs-jobboard .jobs-bulkbar,
    body.dark .recruiter-jobs-jobboard .hm-job-row {
        background: #050505 !important;
        border-color: #262626 !important;
    }
    body.dark .recruiter-jobs-jobboard .hm-job-row > td:nth-child(3),
    body.dark .recruiter-jobs-jobboard .hm-job-row > td:nth-child(4),
    body.dark .recruiter-jobs-jobboard .hm-job-row > td:nth-child(5),
    body.dark .recruiter-jobs-jobboard .hm-job-actions-cell {
        border-top-color: #262626 !important;
    }
    body.dark .recruiter-jobs-jobboard .hm-job-row > td:nth-child(3)::before,
    body.dark .recruiter-jobs-jobboard .hm-job-row > td:nth-child(4)::before,
    body.dark .recruiter-jobs-jobboard .hm-job-row > td:nth-child(5)::before,
    body.dark .recruiter-jobs-jobboard .hm-job-actions-cell::before {
        color: #A3A3A3;
    }
}
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
                        <div>
                            <label class="sr-only">Job posted by</label>
                            <select name="posted_by" class="form-control">
                                <option value="me" <?= ($filters['posted_by'] ?? 'me') === 'me' ? 'selected' : '' ?>>Posted by me</option>
                                <?php if (!empty($postedByOptions) && count($postedByOptions) > 1): ?>
                                    <option value="all" <?= ($filters['posted_by'] ?? 'me') === 'all' ? 'selected' : '' ?>>All company recruiters</option>
                                    <?php foreach ($postedByOptions as $postedByRecruiter): ?>
                                        <?php
                                            $postedById = (int) ($postedByRecruiter['id'] ?? 0);
                                            if ($postedById === (int) session()->get('user_id')) {
                                                continue;
                                            }
                                            $postedByLabel = trim((string) ($postedByRecruiter['name'] ?? ''));
                                            if ($postedByLabel === '') {
                                                $postedByLabel = (string) ($postedByRecruiter['email'] ?? 'Recruiter');
                                            }
                                        ?>
                                        <option value="<?= $postedById ?>" <?= (string) ($filters['posted_by'] ?? 'me') === (string) $postedById ? 'selected' : '' ?>>
                                            <?= esc($postedByLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                <form method="post" action="<?= base_url('recruiter/jobs/bulk-close') ?>" id="jobsBulkCloseForm" class="recruiter-jobs-list-card">
                    <?= csrf_field() ?>
                    <div class="jobs-bulkbar">
                        <div class="jobs-bulkbar-left">
                            <label class="jobs-select-control" for="jobsSelectAll">
                                <input type="checkbox" id="jobsSelectAll">
                                <span>Select all open jobs</span>
                            </label>
                            <span class="jobs-selected-count" id="jobsSelectedCount">0 selected</span>
                        </div>
                        <div class="jobs-bulkbar-right">
                            <button type="submit" class="jobs-bulk-close" id="jobsBulkCloseButton" disabled>
                                <i class="fas fa-ban" aria-hidden="true"></i>
                                <span>Close selected</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive recruiter-table-card">
                    <table class="table table-hover bg-white recruiter-jobs-table">
                        <thead>
                            <tr>
                                <th class="job-select-cell"></th>
                                <th>Job title</th>
                                <th>Posted by</th>
                                <th>Status</th>
                                <th>Responses</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                                <?php
                                    $isOpen = ($job['status'] ?? '') === 'open';
                                    $attentionLevel = (string) ($job['attention_level'] ?? 'quiet');
                                    $showAttention = in_array($attentionLevel, ['critical', 'watch'], true);
                                    $attentionLabel = $attentionLevel === 'critical' ? 'Needs review' : 'Watch';
                                    $attentionFacts = array_values((array) ($job['attention_facts'] ?? []));
                                    $attentionChips = [];
                                    $shortlistedCount = (int) ($job['shortlisted_count'] ?? 0);
                                    $applicantCount = (int) ($job['applicant_count'] ?? 0);
                                    $averageAtsScore = (int) ($job['average_ats_score'] ?? 0);
                                    $attentionChips[] = $shortlistedCount . ' shortlisted';
                                    if ((int) ($job['applicant_count'] ?? 0) > 0) {
                                        $attentionChips[] = $averageAtsScore . '% avg match';
                                    }
                                    $attentionChips = array_slice(array_values(array_unique(array_merge($attentionChips, $attentionFacts))), 0, 3);
                                    $postedAt = !empty($job['created_at']) ? date('d M Y', strtotime((string) $job['created_at'])) : null;
                                    $postedByName = trim((string) ($job['posted_by_name'] ?? ''));
                                    $postedByEmail = trim((string) ($job['posted_by_email'] ?? ''));
                                    $isOwnJob = (int) ($job['recruiter_id'] ?? 0) === (int) session()->get('user_id');
                                ?>
                                <tr class="hm-job-row" data-href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>">
                                    <td class="job-select-cell">
                                        <input
                                            type="checkbox"
                                            class="job-row-check"
                                            name="job_ids[]"
                                            value="<?= (int) $job['id'] ?>"
                                            aria-label="Select <?= esc($job['title']) ?>"
                                            <?= ($isOpen && $isOwnJob) ? '' : 'disabled' ?>
                                        >
                                    </td>
                                    <td class="job-title-cell">
                                        <div class="job-title-row">
                                            <span class="job-title"><?= esc($job['title']) ?></span>
                                            <?php if ($showAttention): ?>
                                                <span class="hm-attention-pill is-<?= esc($attentionLevel) ?>"><?= esc($attentionLabel) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="job-subtitle">
                                            <span><?= esc($job['location']) ?></span>
                                            <?php if ($postedAt): ?>
                                                <span class="job-dot">&bull;</span>
                                                <span>Posted <?= esc($postedAt) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($showAttention): ?>
                                            <div class="hm-attention-stack">
                                                <?php foreach ($attentionChips as $chip): ?>
                                                    <span class="hm-attention-pill is-soft"><?= esc($chip) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="job-response-meta"><?= $isOwnJob ? 'Me' : esc($postedByName !== '' ? $postedByName : 'Recruiter') ?></span>
                                        <?php if (!$isOwnJob && $postedByEmail !== ''): ?>
                                            <span class="job-response-meta"><?= esc($postedByEmail) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="job-status-pill <?= $isOpen ? 'is-open' : 'is-closed' ?>"><?= esc(ucfirst((string) $job['status'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="job-response-count"><?= (int) ($job['applicant_count'] ?? 0) ?></span>
                                        <span class="job-response-meta">Total responses</span>
                                        <span class="job-response-meta"><?= (int) ($job['shortlisted_count'] ?? 0) ?> shortlisted</span>
                                        <?php if ((int) ($job['applicant_count'] ?? 0) > 0): ?>
                                            <span class="job-response-meta"><?= (int) ($job['average_ats_score'] ?? 0) ?>% avg match</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right hm-job-actions-cell">
                                        <div class="hm-job-action-group">
                                            <div class="hm-job-dropdown">
                                                <button class="btn btn-sm hm-job-more-btn" type="button" title="More actions" aria-label="More actions for <?= esc($job['title']) ?>">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <circle cx="3" cy="8" r="1.5" fill="currentColor"/>
      <circle cx="8" cy="8" r="1.5" fill="currentColor"/>
      <circle cx="13" cy="8" r="1.5" fill="currentColor"/>
    </svg>
                                                </button>
                                                <div class="hm-job-dropdown-menu">
                                                    <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>">View Pipeline</a>
                                                    <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>#leaderboard">Leaderboard</a>
                                                    <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/preview/' . $job['id']) ?>" target="_blank">Preview</a>
                                                    <?php if ($isOwnJob): ?>
                                                        <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/edit/' . $job['id']) ?>">Edit Job</a>
                                                        <div class="hm-job-dropdown-separator"></div>
                                                        <?php if ($isOpen): ?>
                                                        <a class="hm-job-dropdown-item is-danger" href="<?= base_url('recruiter/jobs/close/' . $job['id']) ?>" onclick="return confirm('Close this job?')">Close Job</a>
                                                        <?php else: ?>
                                                        <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/reopen/' . $job['id']) ?>">Reopen Job</a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </form>

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
            if (
                e.target.closest('.hm-job-actions-cell') ||
                e.target.closest('.job-row-check') ||
                e.target.closest('.jobs-select-control') ||
                e.target.closest('a') ||
                e.target.closest('button') ||
                e.target.closest('input')
            ) {
                return;
            }
            window.location = row.dataset.href;
        });
    });

    // … button toggle
    var bulkForm = document.getElementById('jobsBulkCloseForm');
    var selectAll = document.getElementById('jobsSelectAll');
    var selectedCount = document.getElementById('jobsSelectedCount');
    var bulkButton = document.getElementById('jobsBulkCloseButton');
    var rowChecks = Array.prototype.slice.call(document.querySelectorAll('.job-row-check:not(:disabled)'));

    function updateBulkState() {
        var checked = rowChecks.filter(function (input) { return input.checked; }).length;
        if (selectedCount) {
            selectedCount.textContent = checked + ' selected';
        }
        if (bulkButton) {
            bulkButton.disabled = checked === 0;
        }
        if (selectAll) {
            selectAll.checked = rowChecks.length > 0 && checked === rowChecks.length;
            selectAll.indeterminate = checked > 0 && checked < rowChecks.length;
            selectAll.disabled = rowChecks.length === 0;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowChecks.forEach(function (input) {
                input.checked = selectAll.checked;
            });
            updateBulkState();
        });
    }

    rowChecks.forEach(function (input) {
        input.addEventListener('change', updateBulkState);
        input.addEventListener('click', function (e) { e.stopPropagation(); });
    });

    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            var checked = rowChecks.filter(function (input) { return input.checked; }).length;
            if (checked === 0 || !confirm('Close ' + checked + ' selected job' + (checked === 1 ? '?' : 's?'))) {
                e.preventDefault();
            }
        });
    }

    updateBulkState();

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
