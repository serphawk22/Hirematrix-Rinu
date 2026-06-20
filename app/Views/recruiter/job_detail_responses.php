<?= view('Layouts/recruiter_header', [
    'title' => 'Job Detail Pipeline',
    'pageStyles' => [base_url('jobboard/css/recruiter-pipeline.css?v=' . @filemtime(FCPATH . 'jobboard/css/recruiter-pipeline.css'))],
]) ?>
<style>
 .container-fluid {
    max-width: 100% !important;
    padding-left: 34px !important;
    padding-right: 34px !important;
}
/* ============================================================
   RECRUITER PIPELINE PAGE — FULL THEME CSS
   Light + Dark (body.dark) — no CSS variables, hard color codes
   ============================================================ */

/* ══════════════════════════════════════════
   BUTTONS
══════════════════════════════════════════ */
.btn-outline-primary {
  background: transparent !important;
  border: 1.5px solid #1FB7B5 !important;
  color: #1FB7B5 !important;
  padding: 8px 20px;
  border-radius: 6px !important;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s ease;
  text-decoration: none !important;
}
.btn-outline-primary:hover,
.btn-outline-primary:focus {
  background: #1FB7B5 !important;
  color: #ffffff !important;
  transform: translateY(-1px);
  outline: none !important;
  box-shadow: none !important;
}

.btn-primary {
  background: transparent !important;
  border: 1.5px solid #1FB7B5 !important;
  color: #1FB7B5 !important;
  padding: 8px 20px;
  border-radius: 6px !important;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s ease;
}
.btn-primary:hover,
.btn-primary:focus {
  background: #1FB7B5 !important;
  color: #ffffff !important;
  transform: translateY(-1px);
  outline: none !important;
  box-shadow: none !important;
}

/* Dark mode buttons — same brand, unchanged */
body.dark .btn-outline-primary,
body.dark .btn-primary {
  background: transparent !important;
  border: 1.5px solid #1FB7B5 !important;
  color: #1FB7B5 !important;
}
body.dark .btn-outline-primary:hover,
body.dark .btn-outline-primary:focus,
body.dark .btn-primary:hover,
body.dark .btn-primary:focus {
  background: #1FB7B5 !important;
  color: #ffffff !important;
}

/* ── Outline secondary ── */
.btn-outline-secondary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 6px !important;
  font-size: 0.85rem;
  font-weight: 600;
  background: transparent !important;
  color: #64748B !important;
  border: 1px solid #D9ECE5 !important;
  text-decoration: none !important;
  transition: all 0.2s;
}
.btn-outline-secondary:hover {
  background: #EDF8F5 !important;
  color: #16212B !important;
  border-color: #1FB7B5 !important;
}
body.dark .btn-outline-secondary {
  color: #94A3B8 !important;
  border: 1px solid #23343A !important;
}
body.dark .btn-outline-secondary:hover {
  background: #1B2A2F !important;
  color: #F8FAFC !important;
  border-color: #1FB7B5 !important;
}

/* ── Outline success / danger / warning (bulk action bar) ── */
.btn-outline-success {
  background: transparent !important;
  border: 1.5px solid #6EE7B7 !important;
  color: #065F46 !important;
  border-radius: 6px !important;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 5px 12px;
  transition: all 0.2s;
}
.btn-outline-success:hover {
  background: #D1FAE5 !important;
}
body.dark .btn-outline-success {
  border-color: #064E3B !important;
  color: #6EE7B7 !important;
}
body.dark .btn-outline-success:hover {
  background: rgba(6, 78, 59, 0.31) !important;
}

.btn-outline-danger {
  background: transparent !important;
  border: 1.5px solid #FCA5A5 !important;
  color: #DC2626 !important;
  border-radius: 6px !important;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 5px 12px;
  transition: all 0.2s;
}
.btn-outline-danger:hover {
  background: #FEE2E2 !important;
}
body.dark .btn-outline-danger {
  border-color: rgba(127, 29, 29, 0.38) !important;
  color: #FCA5A5 !important;
}
body.dark .btn-outline-danger:hover {
  background: rgba(127, 29, 29, 0.19) !important;
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
.btn-warning {
  background: #FEF3C7 !important;
  border: none !important;
  color: #92400E !important;
  border-radius: 6px !important;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 5px 12px;
}
body.dark .btn-warning {
  background: rgba(120, 53, 15, 0.31) !important;
  color: #FCD34D !important;
}

.btn-danger {
  background: #FEE2E2 !important;
  border: none !important;
  color: #DC2626 !important;
  border-radius: 6px !important;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 5px 12px;
}
body.dark .btn-danger {
  background: rgba(127, 29, 29, 0.31) !important;
  color: #FCA5A5 !important;
}

/* ══════════════════════════════════════════
   PAGE WRAPPER & BASE
══════════════════════════════════════════ */
.recruiter-pipeline-page {
  background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%) !important;
  min-height: 100vh;
}
body.dark .recruiter-pipeline-page {
  background: #000000 !important;
}
body.dark .pipeline-empty{
    background: #000000 !important;
    color: #FFFFFF !important;  
}
/* ══════════════════════════════════════════
   PIPELINE SHELL
══════════════════════════════════════════ */
.pipeline-shell {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  width: 100%;
  box-sizing: border-box;
}
@media (max-width: 1200px) {
  .pipeline-shell { padding: 0 1.5rem; }
}
@media (max-width: 768px) {
  .pipeline-shell { padding: 0 1rem; }
}

/* ══════════════════════════════════════════
   PAGE HEADER
══════════════════════════════════════════ */
.recruiter-pipeline-page .page-board-header {
  background: transparent !important;
  border: none !important;
  padding: 2rem 0 1.5rem;
}
.recruiter-pipeline-page .page-board-header.page-board-header-tight.recruiter-page-board-header,
body.dark .recruiter-pipeline-page .page-board-header.page-board-header-tight.recruiter-page-board-header {
  border: none !important;
}

.recruiter-pipeline-page .page-board-title {
  font-size: 26px !important;
  font-weight: 700 !important;
  color: #16212B !important;
  margin: 0;
}
body.dark .recruiter-pipeline-page .page-board-title {
  color: #FFFFFF !important;
}

.recruiter-pipeline-page .pipeline-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.4rem;
}
.recruiter-pipeline-page .pipeline-meta span {
  font-size: 0.85rem;
  color: #64748B !important;
}
.recruiter-pipeline-page .pipeline-meta span::after {
  content: '·';
  margin-left: 0.5rem;
  color: #D9ECE5;
}
.recruiter-pipeline-page .pipeline-meta span:last-child::after { content: ''; }
body.dark .recruiter-pipeline-page .pipeline-meta span { color: #FFFFFF !important; }
body.dark .recruiter-pipeline-page .pipeline-meta span::after { color: #FFFFFF !important; }

/* ══════════════════════════════════════════
   HEADER ACTIONS
══════════════════════════════════════════ */
.recruiter-pipeline-page .pipeline-head-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
@media (max-width: 768px) {
  .recruiter-pipeline-page .pipeline-head-actions { width: 100%; }
}

/* ══════════════════════════════════════════
   NAV TABS
══════════════════════════════════════════ */
.recruiter-pipeline-page .pipeline-work-nav {
  border-bottom: 2px solid #D9ECE5 !important;
  margin-bottom: 1.25rem;
  gap: 0;
}
body.dark .recruiter-pipeline-page .pipeline-work-nav,
body.dark .nav.pipeline-work-nav {
  border-bottom: 2px solid #23343A !important;
  border: none !important;
}

.recruiter-pipeline-page .pipeline-work-nav .nav-link {
  font-size: 0.95rem;
  font-weight: 600;
  color: #64748B !important;
  padding: 10px 20px;
  border: none !important;
  border-bottom: 3px solid transparent !important;
  background: transparent !important;
  border-radius: 0 !important;
  transition: color 0.2s, border-color 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: -2px;
  text-decoration: none !important;
}
.recruiter-pipeline-page .pipeline-work-nav .nav-link:hover {
  color: #1FB7B5 !important;
  border-bottom-color: #1FB7B5 !important;
}
.recruiter-pipeline-page .pipeline-work-nav .nav-link.active {
  color: #1FB7B5 !important;
  border-bottom-color: #1FB7B5 !important;
  background: transparent !important;
}
body.dark .recruiter-pipeline-page .pipeline-work-nav .nav-link {
  color: #FFFFFF !important;
}
body.dark .recruiter-pipeline-page .pipeline-work-nav .nav-link:hover,
body.dark .recruiter-pipeline-page .pipeline-work-nav .nav-link.active {
  color: #1FB7B5 !important;
  border-bottom-color: #1FB7B5 !important;
}

/* ══════════════════════════════════════════
   CARDS
══════════════════════════════════════════ */
.recruiter-pipeline-page .card {
  background: #FFFFFF !important;
  border: 1px solid #D9ECE5 !important;
  border-radius: 12px !important;
  box-shadow: none !important;
}
body.dark .recruiter-pipeline-page .card {
  background: #000000 !important;
  border: 1px solid #23343A !important;
}

.recruiter-pipeline-page .card-header {
  background: #EDF8F5 !important;
  border-bottom: 1px solid #D9ECE5 !important;
  border-radius: 12px 12px 0 0 !important;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
}
body.dark .recruiter-pipeline-page .card-header {
  background: #000000 !important;
  border-bottom: 1px solid #23343A !important;
}

.recruiter-pipeline-page .card-header h6,
.recruiter-pipeline-page .card-header .font-weight-bold {
  color: #16212B !important;
  font-size: 1rem;
  font-weight: 600;
}
body.dark .recruiter-pipeline-page .card-header h6,
body.dark .recruiter-pipeline-page .card-header .font-weight-bold {
  color: #FFFFFF !important;
}

.recruiter-pipeline-page .card-header .text-primary { color: #1FB7B5 !important; }

/* Leaderboard gradient card header */
.recruiter-pipeline-page .card-header.bg-gradient-primary {
  background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%) !important;
  border-bottom: none !important;
}
.recruiter-pipeline-page .card-header.bg-gradient-primary h6 { color: #ffffff !important; }

.recruiter-pipeline-page .card-body {
  background: #FFFFFF !important;
}
body.dark .recruiter-pipeline-page .card-body {
  background: #000000 !important;
}

/* ══════════════════════════════════════════
   TABLE & TABLE CELLS
══════════════════════════════════════════ */
.recruiter-pipeline-page tr,
.recruiter-pipeline-page td,
.recruiter-pipeline-page th,
.recruiter-pipeline-page .col-md-3 {
  font-size: 1rem;
  font-weight: 500 !important;
  color: #16212B;
  background: #FFFFFF !important;
}
body.dark .recruiter-pipeline-page tr,
body.dark .recruiter-pipeline-page td,
body.dark .recruiter-pipeline-page th,
body.dark .recruiter-pipeline-page .col-md-3 {
  background: #000000 !important;
  color: #FFFFFF !important;
  border-color: #23343A !important;
}

.recruiter-pipeline-page .table-secondary td,
.recruiter-pipeline-page .table-secondary th,
.recruiter-pipeline-page .table-secondary {
  background: #EDF8F5 !important;
}
body.dark .recruiter-pipeline-page .table-secondary td,
body.dark .recruiter-pipeline-page .table-secondary th,
body.dark .recruiter-pipeline-page .table-secondary {
  background: #000000 !important;
}

.recruiter-pipeline-page thead th {
  background: #EDF8F5 !important;
  color: #64748B !important;
  border-color: #D9ECE5 !important;
}
body.dark .recruiter-pipeline-page thead th {
  background: #000000 !important;
  color: #FFFFFF !important;
  border-color: #23343A !important;
}

.recruiter-pipeline-page .table {
  border-color: #D9ECE5 !important;
}
body.dark .recruiter-pipeline-page .table {
  border-color: #23343A !important;
}

.recruiter-pipeline-page .table-bordered td,
.recruiter-pipeline-page .table-bordered th {
  border-color: #D9ECE5 !important;
}
body.dark .recruiter-pipeline-page .table-bordered td,
body.dark .recruiter-pipeline-page .table-bordered th {
  border-color: #23343A !important;
}

/* ══════════════════════════════════════════
   BULK ACTION BAR
══════════════════════════════════════════ */
.recruiter-pipeline-page #bulkActionBar {
  border-radius: 8px !important;
}
.recruiter-pipeline-page #bulkActionBar .small {
  font-size: 0.9rem;
  color: #16212B !important;
}
body.dark .recruiter-pipeline-page #bulkActionBar .small { color: #FFFFFF !important; }
.recruiter-pipeline-page #bulkActionBar #selectedCount {
  color: #FFFFFF !important;
  font-weight: 700;
}

/* ══════════════════════════════════════════
   PIPELINE SUMMARY BAR
══════════════════════════════════════════ */
.recruiter-pipeline-page .pipeline-summary-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 0.75rem 0 1rem;
}
.recruiter-pipeline-page .pipeline-summary-main {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  font-size: 1rem;
  font-weight: 600;
  color: #16212B !important;
}
body.dark .recruiter-pipeline-page .pipeline-summary-main {
  color: #FFFFFF !important;
}

.recruiter-pipeline-page .pipeline-hiring-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 600;
  background: rgba(22, 33, 43, 0.08);
  color: #0D8A90;
  border: none;
  text-decoration: none !important;
  white-space: nowrap;
  cursor: pointer;
}
body.dark .recruiter-pipeline-page .pipeline-hiring-chip {
 background: #000000 !important;
    color: #0D8A90;
    border: 1px solid rgba(31, 183, 181, 0.15) !important;
}
/* ══════════════════════════════════════════
   STAGE RAIL
══════════════════════════════════════════ */
.recruiter-pipeline-page .pipeline-stage-rail {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 1rem;
}
.recruiter-pipeline-page .stage-ajax-link {
  background: transparent !important;
  border: 1.5px solid #1FB7B5 !important;
  color: #1FB7B5 !important;
  padding: 8px 20px;
  border-radius: 6px !important;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s ease;
  text-decoration: none !important;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.recruiter-pipeline-page .stage-ajax-link:hover,
.recruiter-pipeline-page .stage-ajax-link.active {
  background: #1FB7B5 !important;
  color: #ffffff !important;
  transform: translateY(-1px);
}
.recruiter-pipeline-page .stage-count {
  font-size: 0.8rem;
  opacity: 0.8;
}

/* ══════════════════════════════════════════
   PIPELINE TOOLBAR
══════════════════════════════════════════ */
.recruiter-pipeline-page .pipeline-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  margin-bottom: 1rem;
}
@media (max-width: 768px) {
  .recruiter-pipeline-page .pipeline-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
}

/* Search box */
.recruiter-pipeline-page .pipeline-search {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #FFFFFF !important;
  border: 1px solid #D9ECE5 !important;
  border-radius: 8px;
  padding: 7px 14px;
  flex: 1;
  min-width: 200px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.recruiter-pipeline-page .pipeline-search:focus-within {
  border-color: #0D8A90 !important;
  box-shadow: none !important;
}
.recruiter-pipeline-page .pipeline-search i {
  color: #7A8B96;
  font-size: 0.85rem;
}
.recruiter-pipeline-page .pipeline-search input {
  background: transparent !important;
  border: none !important;
  outline: none !important;
  font-size: 0.95rem;
  color: #16212B !important;
  width: 100%;
}
.recruiter-pipeline-page .pipeline-search input::placeholder { color: #94A3B8 !important; }

body.dark .recruiter-pipeline-page .pipeline-search {
  background: #000000 !important;
  border: 1px solid #23343A !important;
}
body.dark .recruiter-pipeline-page .pipeline-search:focus-within {
  border-color: #0D8A90 !important;
}
body.dark .recruiter-pipeline-page .pipeline-search i { color: #FFFFFF !important; }
body.dark .recruiter-pipeline-page .pipeline-search input {
  color: #FFFFFF !important;
}
body.dark .recruiter-pipeline-page .pipeline-search input::placeholder { color: #FFFFFF !important; }

@media (max-width: 768px) {
  .recruiter-pipeline-page .pipeline-search { width: 100%; }
}

/* Tool button */
.recruiter-pipeline-page .pipeline-tool-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 16px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  background: #EDF8F5 !important;
  color: #64748B !important;
  border: 1px solid #D9ECE5 !important;
  cursor: pointer;
  transition: all 0.2s;
}
.recruiter-pipeline-page .pipeline-tool-btn:hover,
.recruiter-pipeline-page .pipeline-tool-btn.active {
  border-color: #1FB7B5 !important;
  color: #1FB7B5 !important;
}
body.dark .recruiter-pipeline-page .pipeline-tool-btn {
  background: #000000 !important;
  border: 1px solid #23343A !important;
  color: #FFFFFF !important;
}
body.dark .recruiter-pipeline-page .pipeline-tool-btn:hover,
body.dark .recruiter-pipeline-page .pipeline-tool-btn.active {
  border-color: #0D8A90 !important;
  color: #FFFFFF !important;
}

/* Bulk action select */
.recruiter-pipeline-page .pipeline-bulk-controls { display: flex; gap: 6px; align-items: center; }
.recruiter-pipeline-page .pipeline-bulk-controls select {
  font-size: 0.875rem;
  background: #FFFFFF !important;
  color: #16212B !important;
  border: 1px solid #D9ECE5 !important;
  border-radius: 6px;
  padding: 6px 12px;
  transition: border-color 0.2s;
}
.recruiter-pipeline-page .pipeline-bulk-controls select:focus {
  outline: none;
  box-shadow: none !important;
  border-color: #0D8A90 !important;
}
body.dark .recruiter-pipeline-page .pipeline-bulk-controls select {
  background: #000000 !important;
  border: 1px solid #23343A !important;
  color: #FFFFFF !important;
}
body.dark .recruiter-pipeline-page .pipeline-bulk-controls select:focus {
  border-color: #0D8A90 !important;
  box-shadow: none !important;
}
body.dark .recruiter-pipeline-page .pipeline-bulk-controls select option {
  background: #1B2A2F;
  color: #F8FAFC;
}

/* ══════════════════════════════════════════
   ADVANCED FILTER COLLAPSE
══════════════════════════════════════════ */
.recruiter-pipeline-page #advancedFilterCollapse .bg-light {
  background: #EDF8F5 !important;
  border-bottom: 1px solid #D9ECE5 !important;
  border-radius: 0 0 8px 8px;
}
body.dark .recruiter-pipeline-page #advancedFilterCollapse .bg-light {
  background: #1B2A2F !important;
  border-bottom: 1px solid #23343A !important;
}

.recruiter-pipeline-page #advancedFilterCollapse label {
  font-size: 0.82rem;
  font-weight: 600;
  color: #64748B !important;
}
body.dark .recruiter-pipeline-page #advancedFilterCollapse label { color: #94A3B8 !important; }

.recruiter-pipeline-page #advancedFilterCollapse .form-control {
  font-size: 0.9rem;
  background: #FFFFFF !important;
  color: #16212B !important;
  border: 1px solid #D9ECE5 !important;
  border-radius: 6px !important;
  transition: border-color 0.2s;
}
.recruiter-pipeline-page #advancedFilterCollapse .form-control:focus {
  outline: none !important;
  box-shadow: none !important;
  border-color: #0D8A90 !important;
}
.recruiter-pipeline-page #advancedFilterCollapse .form-control::placeholder { color: #94A3B8 !important; }
body.dark .recruiter-pipeline-page #advancedFilterCollapse .form-control {
  background: #1B2A2F !important;
  color: #F8FAFC !important;
  border: 1px solid #23343A !important;
}
body.dark .recruiter-pipeline-page #advancedFilterCollapse .form-control::placeholder { color: #4A5C63 !important; }

/* ══════════════════════════════════════════
   BADGES
══════════════════════════════════════════ */
.recruiter-pipeline-page .badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 600;
  background: rgba(22, 33, 43, 0.08);
  color: #0D8A90;
  border: none;
  text-decoration: none !important;
  white-space: nowrap;
  cursor: pointer;
}
body.dark .recruiter-pipeline-page .badge {
  background: rgba(122, 139, 150, 0.31);
  color: #0D8A90;
}

.recruiter-pipeline-page .badge-primary   { background: #e1f5ee !important; color: #0f6e56 !important; }
.recruiter-pipeline-page .badge-success   { background: #D1FAE5 !important; color: #065F46 !important; }
.recruiter-pipeline-page .badge-info      { background: #DBEAFE !important; color: #1E40AF !important; }
.recruiter-pipeline-page .badge-warning   { background: #FEF3C7 !important; color: #92400E !important; }
.recruiter-pipeline-page .badge-danger    { background: #FEE2E2 !important; color: #991B1B !important; }
.recruiter-pipeline-page .badge-secondary { background: #F1F5F9 !important; color: #475569 !important; }
.recruiter-pipeline-page .badge-dark      { background: #1E293B  !important; color: #E2E8F0 !important; }
.recruiter-pipeline-page .badge-light     { background: #EDF8F5  !important; color: #64748B  !important; }

body.dark .recruiter-pipeline-page .badge-primary   { background: rgba(15, 110, 86, 0.25)  !important; color: #6EE7B7 !important; }
body.dark .recruiter-pipeline-page .badge-success   { background: rgba(6, 78, 59, 0.31)    !important; color: #6EE7B7 !important; }
body.dark .recruiter-pipeline-page .badge-info      { background: rgba(30, 58, 95, 0.31)   !important; color: #93C5FD !important; }
body.dark .recruiter-pipeline-page .badge-warning   { background: rgba(120, 53, 15, 0.31)  !important; color: #FCD34D !important; }
body.dark .recruiter-pipeline-page .badge-danger    { background: rgba(127, 29, 29, 0.31)  !important; color: #FCA5A5 !important; }
body.dark .recruiter-pipeline-page .badge-secondary { background: #1E293B                  !important; color: #94A3B8 !important; }
body.dark .recruiter-pipeline-page .badge-dark      { background: #0E1619                  !important; color: #94A3B8 !important; }
body.dark .recruiter-pipeline-page .badge-light     { background: #1B2A2F                  !important; color: #94A3B8 !important; }

/* ══════════════════════════════════════════
   INTERVIEW SUMMARY CARD
══════════════════════════════════════════ */
.recruiter-pipeline-page .recruiter-summary-card .recruiter-summary-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.recruiter-pipeline-page .recruiter-summary-label {
  font-size: 0.78rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748B !important;
}
body.dark .recruiter-pipeline-page .recruiter-summary-label { color: #94A3B8 !important; }

.recruiter-pipeline-page .recruiter-summary-item strong {
  font-size: 1.6rem;
  font-weight: 700;
  color: #16212B !important;
}
body.dark .recruiter-pipeline-page .recruiter-summary-item strong { color: #F8FAFC !important; }

/* ══════════════════════════════════════════
   BOOKING PERSON CELL
══════════════════════════════════════════ */
.recruiter-pipeline-page .recruiter-booking-person { display: flex; flex-direction: column; gap: 2px; }
.recruiter-pipeline-page .recruiter-booking-person strong {
  font-weight: 600;
  font-size: 1rem;
  color: #16212B !important;
}
body.dark .recruiter-pipeline-page .recruiter-booking-person strong { color: #F8FAFC !important; }
.recruiter-pipeline-page .recruiter-booking-person span {
  font-size: 0.82rem;
  color: #64748B !important;
}
body.dark .recruiter-pipeline-page .recruiter-booking-person span { color: #94A3B8 !important; }

/* ══════════════════════════════════════════
   ACTION WRAP (table row actions)
══════════════════════════════════════════ */
.recruiter-pipeline-page .job-actions-wrap,
.recruiter-pipeline-page .recruiter-booking-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

/* ══════════════════════════════════════════
   LEADERBOARD — RANK BADGES
══════════════════════════════════════════ */
.recruiter-pipeline-page .rank-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 50px;
  font-size: 0.82rem;
  font-weight: 700;
}
.recruiter-pipeline-page .rank-badge.gold   { background: #FEF3C7; color: #92400E; }
.recruiter-pipeline-page .rank-badge.silver { background: #F1F5F9; color: #334155; }
.recruiter-pipeline-page .rank-badge.bronze { background: #FEF3C7; color: #7C2D12; }
.recruiter-pipeline-page .rank-number       { font-size: 0.95rem; font-weight: 700; color: #64748B; }

body.dark .recruiter-pipeline-page .rank-badge.gold   { background: rgba(120, 53, 15, 0.31); color: #FCD34D; }
body.dark .recruiter-pipeline-page .rank-badge.silver { background: #1E293B; color: #94A3B8; }
body.dark .recruiter-pipeline-page .rank-badge.bronze { background: rgba(124, 45, 18, 0.31); color: #FCA5A5; }
body.dark .recruiter-pipeline-page .rank-number       { color: #94A3B8; }

/* Top performer row */
.recruiter-pipeline-page .leaderboard-table tbody tr.top-performer {
  background: rgba(31, 183, 181, 0.04) !important;
}

/* Score display */
.recruiter-pipeline-page .score-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.recruiter-pipeline-page .score-value { font-size: 1rem; font-weight: 700; }
.recruiter-pipeline-page .score-bar {
  width: 60px;
  height: 4px;
  background: #D9ECE5;
  border-radius: 2px;
  overflow: hidden;
}
body.dark .recruiter-pipeline-page .score-bar { background: #23343A; }
.recruiter-pipeline-page .score-fill {
  height: 100%;
  background: #111111 !important;
  border-radius: 2px;
}

/* Score colors */
.recruiter-pipeline-page .text-success { color: #065F46 !important; }
.recruiter-pipeline-page .text-warning { color: #92400E !important; }
.recruiter-pipeline-page .text-danger  { color: #991B1B !important; }
body.dark .recruiter-pipeline-page .text-success { color: #6EE7B7 !important; }
body.dark .recruiter-pipeline-page .text-warning { color: #FCD34D !important; }
body.dark .recruiter-pipeline-page .text-danger  { color: #FCA5A5 !important; }

/* Overall rating */
.recruiter-pipeline-page .overall-rating {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.recruiter-pipeline-page .rating-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 700;
}
.recruiter-pipeline-page .rating-badge.badge-success { background: #D1FAE5; color: #065F46; }
.recruiter-pipeline-page .rating-badge.badge-warning { background: #FEF3C7; color: #92400E; }
.recruiter-pipeline-page .rating-badge.badge-danger  { background: #FEE2E2; color: #991B1B; }
body.dark .recruiter-pipeline-page .rating-badge.badge-success { background: rgba(6, 78, 59, 0.31); color: #6EE7B7; }
body.dark .recruiter-pipeline-page .rating-badge.badge-warning { background: rgba(120, 53, 15, 0.31); color: #FCD34D; }
body.dark .recruiter-pipeline-page .rating-badge.badge-danger  { background: rgba(127, 29, 29, 0.31); color: #FCA5A5; }

.recruiter-pipeline-page .rating-stars i { font-size: 0.72rem; }
.recruiter-pipeline-page .rating-stars .text-warning { color: #F59E0B !important; }
.recruiter-pipeline-page .rating-stars .text-muted   { color: #D9ECE5 !important; }
body.dark .recruiter-pipeline-page .rating-stars .text-muted { color: #23343A !important; }

/* Skill badges */
.recruiter-pipeline-page .skill-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 500;
  margin: 2px;
}
.recruiter-pipeline-page .skill-has {
  background: #D1FAE5;
  color: #065F46;
  border: 1px solid #6EE7B7;
}
.recruiter-pipeline-page .skill-missing {
  background: #FEE2E2;
  color: #991B1B;
  border: 1px solid #FCA5A5;
}
body.dark .recruiter-pipeline-page .skill-has {
  background: rgba(6, 78, 59, 0.31);
  color: #6EE7B7;
  border-color: #064E3B;
}
body.dark .recruiter-pipeline-page .skill-missing {
  background: rgba(127, 29, 29, 0.19);
  color: #FCA5A5;
  border-color: #7F1D1D;
}

/* Leaderboard alert */
.recruiter-pipeline-page .alert-light {
  background: #EDF8F5 !important;
  border: 1px solid #D9ECE5 !important;
  color: #64748B !important;
  border-radius: 8px;
  font-size: 0.9rem;
}
.recruiter-pipeline-page .alert-light strong { color: #16212B !important; }
body.dark .recruiter-pipeline-page .alert-light {
  background: #000000 !important;
  border: 1px solid #23343A !important;
  color: #FFFFFF !important;
}
body.dark .recruiter-pipeline-page .alert-light strong { color: #FFFFFF !important; }

/* ══════════════════════════════════════════
   MODAL
══════════════════════════════════════════ */
.recruiter-pipeline-page .modal-content,
.modal-content {
  background: #FFFFFF !important;
  border: 1px solid #D9ECE5 !important;
  border-radius: 12px !important;
  box-shadow: none !important;
}
body.dark .recruiter-pipeline-page .modal-content,
body.dark .modal-content {
  background: #000000 !important;
  border: 1px solid #23343A !important;
}

.modal-header {
  background: #EDF8F5 !important;
  border-bottom: 1px solid #D9ECE5 !important;
  border-radius: 12px 12px 0 0 !important;
}
body.dark .modal-header {
  background: #000000 !important;
  border-bottom: 1px solid #23343A !important;
}

.modal-header .modal-title { color: #16212B !important; font-size: 1rem; font-weight: 600; }
body.dark .modal-header .modal-title { color: #FFFFFF !important; }
.modal-header .close { color: #64748B !important; opacity: 1; }
body.dark .modal-header .close { color: #FFFFFF !important; }

.modal-body { background: #FFFFFF !important; }
body.dark .modal-body { background: #000000 !important; }

.modal-body label { font-size: 0.9rem; font-weight: 600; color: #16212B !important; }
body.dark .modal-body label { color: #FFFFFF !important; }

.modal-body .form-control {
  background: #FFFFFF !important;
  color: #16212B !important;
  border: 1px solid #D9ECE5 !important;
  border-radius: 6px !important;
  font-size: 1rem;
}
.modal-body .form-control:focus {
  outline: none !important;
  box-shadow: none !important;
  border-color: #0D8A90 !important;
}
.modal-body .form-control::placeholder { color: #94A3B8 !important; }
body.dark .modal-body .form-control {
  background: #000000 !important;
  color: #FFFFFF !important;
  border: 1px solid #23343A !important;
}
body.dark .modal-body .form-control::placeholder { color: #FFFFFF !important; }

.modal-body .text-muted,
.modal-body small.text-muted { color: #64748B !important; }
body.dark .modal-body .text-muted,
body.dark .modal-body small.text-muted { color: #FFFFFF !important; }

.modal-body #emailRecipients {
  background: #EDF8F5 !important;
  border-color: #D9ECE5 !important;
  color: #16212B !important;
  border-radius: 6px;
}
body.dark .modal-body #emailRecipients {
  background: #000000 !important;
  border-color: #23343A !important;
  color: #FFFFF !important;
}

.modal-footer {
  background: #EDF8F5 !important;
  border-top: 1px solid #D9ECE5 !important;
  border-radius: 0 0 12px 12px !important;
}
body.dark .modal-footer {
  background: #000000 !important;
  border-top: 1px solid #23343A !important;
}

.modal-footer .btn-secondary {
  background: transparent !important;
  border: 1px solid #D9ECE5 !important;
  color: #64748B !important;
  border-radius: 6px !important;
  font-weight: 600;
  padding: 7px 18px;
}
.modal-footer .btn-secondary:hover { background: #EDF8F5 !important; }
body.dark .modal-footer .btn-secondary {
  border: 1px solid #23343A !important;
  color: #FFFFFF !important;
}
body.dark .modal-footer .btn-secondary:hover { background: #000000 !important; }

.ai-modal .modal-content {
  border-radius: 18px !important;
  overflow: hidden;
}

.ai-modal .ai-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.15rem 1.35rem;
}

.ai-modal .ai-title {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
}

.ai-modal .ai-icon {
  width: 2.35rem;
  height: 2.35rem;
  border-radius: 0.85rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #DDF5F1;
  color: #0D8A90;
  flex: 0 0 2.35rem;
}

.ai-modal .ai-title h5 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #16212B;
  line-height: 1.2;
}

.ai-modal .ai-title small {
  display: block;
  margin-top: 0.3rem;
  font-size: 0.95rem;
  color: #64748B;
  line-height: 1.5;
}

.ai-modal .ai-close {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 0.8rem;
  border: 1px solid #D9ECE5;
  background: #FFFFFF;
  color: #64748B;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  font-weight: 600;
  line-height: 1;
  transition: all 0.2s ease;
}

.ai-modal .ai-close:hover,
.ai-modal .ai-close:focus {
  background: #EDF8F5;
  color: #16212B;
  outline: none;
}

.ai-modal .ai-body {
  padding: 1rem 1rem 1.2rem;
  background: #F7FBFA !important;
}

.ai-modal #aiReportContent .card {
  border: 1px solid #D9ECE5 !important;
  border-radius: 16px !important;
  background: #FFFFFF !important;
  box-shadow: none !important;
}

.ai-modal #aiReportContent .card-header,
.ai-modal #aiReportContent .card-body {
  background: transparent !important;
}

.ai-modal #aiReportContent .card-header {
  border-bottom: 1px solid #D9ECE5 !important;
  padding: 1rem 1.15rem;
}

.ai-modal #aiReportContent .card-body {
  padding: 1.15rem;
}

.ai-modal #aiReportContent h4,
.ai-modal #aiReportContent h5,
.ai-modal #aiReportContent strong {
  color: #16212B !important;
}

.ai-modal #aiReportContent .table {
  margin-bottom: 0;
  color: #16212B;
  background: transparent;
}

.ai-modal #aiReportContent .table thead th {
  background: #F6FAFC;
  color: #41587B;
  font-size: 0.92rem;
  font-weight: 700;
  border-top: 0 !important;
  border-bottom: 1px solid #D9ECE5 !important;
  padding: 0.95rem 0.85rem;
}

.ai-modal #aiReportContent .table td {
  padding: 0.95rem 0.85rem;
  border-top: 1px solid #E8F2EF !important;
  color: #16212B;
  vertical-align: middle;
}

.ai-modal .ai-loader,
.ai-modal .ai-error {
  display: grid;
  place-items: center;
  text-align: center;
  min-height: 240px;
  padding: 1.5rem;
  color: #64748B;
}

.ai-modal .ai-error i {
  font-size: 1.5rem;
  color: #F59E0B;
  margin-bottom: 0.75rem;
}

.ai-modal .spinner {
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  border: 3px solid #D9ECE5;
  border-top-color: #0D8A90;
  animation: aiReportSpin 0.8s linear infinite;
  margin-bottom: 0.9rem;
}

@keyframes aiReportSpin {
  to { transform: rotate(360deg); }
}

body.dark .ai-modal .modal-content {
  background: #000000 !important;
  border-color: #23343A !important;
}

body.dark .ai-modal .ai-header {
  background: #000000 !important;
  border-bottom-color: #23343A !important;
}

body.dark .ai-modal .ai-icon {
  background: rgba(31, 183, 181, 0.14);
  color: #57D3D1;
}

body.dark .ai-modal .ai-title h5 {
  color: #FFFFFF !important;
}

body.dark .ai-modal .ai-title small {
  color: #FFFFFF !important;
}

body.dark .ai-modal .ai-close {
  background: #000000;
  border-color: #23343A;
  color: #FFFFFF;
}

body.dark .ai-modal .ai-close:hover,
body.dark .ai-modal .ai-close:focus {
  background: #000000;
  color: #FFFFFF;
}

body.dark .ai-modal .ai-body {
  background: #000000 !important;
}

body.dark .ai-modal #aiReportContent .card {
  background: #000000 !important;
  border-color: #23343A !important;
}

body.dark .ai-modal #aiReportContent .card-header {
  border-bottom-color: #23343A !important;
}

body.dark .ai-modal #aiReportContent h4,
body.dark .ai-modal #aiReportContent h5,
body.dark .ai-modal #aiReportContent strong,
body.dark .ai-modal #aiReportContent .table,
body.dark .ai-modal #aiReportContent .table td {
  color: #FFFFFF !important;
}

body.dark .ai-modal #aiReportContent .table thead th {
  background: #000000;
  color: #FFFFFF;
  border-bottom-color: #23343A !important;
}

body.dark .ai-modal #aiReportContent .table td {
  border-top-color: #23343A !important;
}

body.dark .ai-modal #aiReportContent .text-muted,
body.dark .ai-modal #aiReportContent small.text-muted,
body.dark .ai-modal .ai-loader,
body.dark .ai-modal .ai-error {
  color: #FFFFFF !important;
}

body.dark .ai-modal .spinner {
  border-color: #23343A;
  border-top-color: #57D3D1;
}

.ai-modal .modal-content {
  border-radius: 18px !important;
  overflow: hidden;
}

.ai-modal .ai-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.15rem 1.35rem;
}

.ai-modal .ai-title {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
}

.ai-modal .ai-icon {
  width: 2.35rem;
  height: 2.35rem;
  border-radius: 0.85rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #DDF5F1;
  color: #0D8A90;
  flex: 0 0 2.35rem;
}

.ai-modal .ai-title h5 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #16212B;
  line-height: 1.2;
}

.ai-modal .ai-title small {
  display: block;
  margin-top: 0.3rem;
  font-size: 0.95rem;
  color: #64748B;
  line-height: 1.5;
}

.ai-modal .ai-close {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 0.8rem;
  border: 1px solid #D9ECE5;
  background: #FFFFFF;
  color: #64748B;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  font-weight: 600;
  line-height: 1;
  transition: all 0.2s ease;
}

.ai-modal .ai-close:hover,
.ai-modal .ai-close:focus {
  background: #EDF8F5;
  color: #16212B;
  outline: none;
}

.ai-modal .ai-body {
  padding: 1rem 1rem 1.2rem;
  background: #F7FBFA !important;
}

.ai-modal #aiReportContent .card {
  border: 1px solid #D9ECE5 !important;
  border-radius: 16px !important;
  background: #FFFFFF !important;
  box-shadow: none !important;
}

.ai-modal #aiReportContent .card-header,
.ai-modal #aiReportContent .card-body {
  background: transparent !important;
}

.ai-modal #aiReportContent .card-header {
  border-bottom: 1px solid #D9ECE5 !important;
  padding: 1rem 1.15rem;
}

.ai-modal #aiReportContent .card-body {
  padding: 1.15rem;
}

.ai-modal #aiReportContent h4,
.ai-modal #aiReportContent h5,
.ai-modal #aiReportContent strong {
  color: #16212B !important;
}

.ai-modal #aiReportContent .table {
  margin-bottom: 0;
  color: #16212B;
  background: transparent;
}

.ai-modal #aiReportContent .table thead th {
  background: #F6FAFC;
  color: #41587B;
  font-size: 0.92rem;
  font-weight: 700;
  border-top: 0 !important;
  border-bottom: 1px solid #D9ECE5 !important;
  padding: 0.95rem 0.85rem;
}

.ai-modal #aiReportContent .table td {
  padding: 0.95rem 0.85rem;
  border-top: 1px solid #E8F2EF !important;
  color: #16212B;
  vertical-align: middle;
}

.ai-modal .ai-loader,
.ai-modal .ai-error {
  display: grid;
  place-items: center;
  text-align: center;
  min-height: 240px;
  padding: 1.5rem;
  color: #64748B;
}

.ai-modal .ai-error i {
  font-size: 1.5rem;
  color: #F59E0B;
  margin-bottom: 0.75rem;
}

.ai-modal .spinner {
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  border: 3px solid #D9ECE5;
  border-top-color: #0D8A90;
  animation: aiReportSpin 0.8s linear infinite;
  margin-bottom: 0.9rem;
}

@keyframes aiReportSpin {
  to { transform: rotate(360deg); }
}

body.dark .ai-modal .modal-content {
  background: #162327 !important;
  border-color: #23343A !important;
}

body.dark .ai-modal .ai-header {
  background: #18262B !important;
  border-bottom-color: #23343A !important;
}

body.dark .ai-modal .ai-icon {
  background: rgba(31, 183, 181, 0.14);
  color: #57D3D1;
}

body.dark .ai-modal .ai-title h5 {
  color: #F8FAFC !important;
}

body.dark .ai-modal .ai-title small {
  color: #94A3B8 !important;
}

body.dark .ai-modal .ai-close {
  background: #111B1F;
  border-color: #23343A;
  color: #94A3B8;
}

body.dark .ai-modal .ai-close:hover,
body.dark .ai-modal .ai-close:focus {
  background: #1B2A2F;
  color: #F8FAFC;
}

body.dark .ai-modal .ai-body {
  background: #162327 !important;
}

body.dark .ai-modal #aiReportContent .card {
  background: #111B1F !important;
  border-color: #23343A !important;
}

body.dark .ai-modal #aiReportContent .card-header {
  border-bottom-color: #23343A !important;
}

body.dark .ai-modal #aiReportContent h4,
body.dark .ai-modal #aiReportContent h5,
body.dark .ai-modal #aiReportContent strong,
body.dark .ai-modal #aiReportContent .table,
body.dark .ai-modal #aiReportContent .table td {
  color: #F8FAFC !important;
}

body.dark .ai-modal #aiReportContent .table thead th {
  background: #162327;
  color: #B7C6D2;
  border-bottom-color: #23343A !important;
}

body.dark .ai-modal #aiReportContent .table td {
  border-top-color: #23343A !important;
}

body.dark .ai-modal #aiReportContent .text-muted,
body.dark .ai-modal #aiReportContent small.text-muted,
body.dark .ai-modal .ai-loader,
body.dark .ai-modal .ai-error {
  color: #94A3B8 !important;
}

body.dark .ai-modal .spinner {
  border-color: #23343A;
  border-top-color: #57D3D1;
}

/* ══════════════════════════════════════════
   ALERTS
══════════════════════════════════════════ */
.recruiter-pipeline-page .alert-success {
  background: #D1FAE5 !important;
  border: 1px solid #6EE7B7 !important;
  color: #065F46 !important;
  border-radius: 8px;
  font-size: 1rem;
}
.recruiter-pipeline-page .alert-danger {
  background: #FEE2E2 !important;
  border: 1px solid #FCA5A5 !important;
  color: #991B1B !important;
  border-radius: 8px;
  font-size: 1rem;
}
body.dark .recruiter-pipeline-page .alert-success {
  background: rgba(6, 78, 59, 0.19) !important;
  border-color: #064E3B !important;
  color: #6EE7B7 !important;
}
body.dark .recruiter-pipeline-page .alert-danger {
  background: rgba(127, 29, 29, 0.19) !important;
  border-color: #7F1D1D !important;
  color: #FCA5A5 !important;
}

/* ══════════════════════════════════════════
   PAGINATION
══════════════════════════════════════════ */
.recruiter-pipeline-page ul.pagination li.page-item a.page-link,
.recruiter-pipeline-page ul.pagination li.page-item span.page-link {
  color: #1FB7B5 !important;
  background-color: transparent !important;
  border-color: #D9ECE5 !important;
  font-size: 1rem;
  text-decoration: none !important;
}
.recruiter-pipeline-page ul.pagination li.page-item a.page-link:hover {
  color: #0D8A90 !important;
  background-color: #EDF8F5 !important;
  border-color: #1FB7B5 !important;
}
.recruiter-pipeline-page ul.pagination li.page-item.active .page-link {
  color: #ffffff !important;
  background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%) !important;
  border-color: #1FB7B5 !important;
}
.recruiter-pipeline-page ul.pagination li.page-item.disabled .page-link {
  color: #94A3B8 !important;
  border-color: #D9ECE5 !important;
}

body.dark .recruiter-pipeline-page ul.pagination li.page-item a.page-link,
body.dark .recruiter-pipeline-page ul.pagination li.page-item span.page-link {
  color: #FFFFFF !important;
  background-color: transparent !important;
  border-color: #23343A !important;
}
body.dark .recruiter-pipeline-page ul.pagination li.page-item a.page-link:hover {
  background-color: #1B2A2F !important;
  border-color: #1FB7B5 !important;
}
body.dark .recruiter-pipeline-page ul.pagination li.page-item.disabled .page-link {
  color: #4A5C63 !important;
  border-color: #23343A !important;
}

/* ══════════════════════════════════════════
   MISC TEXT HELPERS
══════════════════════════════════════════ */
.recruiter-pipeline-page .text-muted { color: #64748B !important; }
body.dark .recruiter-pipeline-page .text-muted { color: #94A3B8 !important; }
.recruiter-pipeline-page .text-primary { color: #1FB7B5 !important; }
.recruiter-pipeline-page small { color: #64748B; }
body.dark .recruiter-pipeline-page small { color: #94A3B8; }

/* ══════════════════════════════════════════
   TABLE RESPONSIVE
══════════════════════════════════════════ */
.recruiter-pipeline-page .table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}
</style>
<?php
$statusTones = [
    'applied' => 'neutral',
    'ai_interview_completed' => 'info',
    'shortlisted' => 'success',
    'interview_scheduled' => 'info',
    'interviewed' => 'violet',
    'offered' => 'warning',
    'hired' => 'success',
    'rejected' => 'danger',
    'withdrawn' => 'muted',
    'on_hold' => 'warning',
    'filtered_out' => 'muted',
];
$allApplicationsLabel = (int) ($totalApplicationsCount ?? 0);
$avgMatch = (int) ($scoreboard['average_ats_score'] ?? 0);
$openings = (int) ($job['openings'] ?? 0);
$safeActiveStage = $activeStage ?? 'all';
$hasActiveFilters = !empty(array_filter($advancedFilters ?? [], function($v) { return $v !== '' && $v !== null; }));
$companyName = trim((string) ($job['company'] ?? $job['client_company_name'] ?? ''));
$metaParts = array_filter([
    $job['location'] ?? '',
    $job['category'] ?? '',
    $companyName,
    'JOB-' . str_pad((string) ($job['id'] ?? 0), 4, '0', STR_PAD_LEFT),
]);
$statusLabel = ucfirst((string) ($job['status'] ?? 'open'));
$statusClass = strtolower((string) ($job['status'] ?? 'open')) === 'open' ? 'is-open' : 'is-closed';
?>



<div
    id="recruiterPipelinePage"
    class="recruiter-pipeline-page recruiter-applications-jobboard recruiter-leaderboard-jobboard"
    data-job-id="<?= (int) $job['id'] ?>"
    data-job-title="<?= esc($job['title']) ?>"
    data-bulk-url="<?= base_url('recruiter/jobs/' . $job['id'] . '/applications/bulk') ?>"
    data-email-url="<?= base_url('recruiter/jobs/' . $job['id'] . '/send-bulk-email') ?>"
    data-status-url-base="<?= base_url('recruiter/applications/update-status/') ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-hash="<?= csrf_hash() ?>"
>
<div class="pipeline-shell container-fluid">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header pipeline-job-head">
        <div class="page-board-copy"> 
            <h1 class="page-board-title"><?= esc($job['title']) ?></h1>
            <div class="pipeline-meta">
                <?php foreach ($metaParts as $part): ?>
                    <span><?= esc($part) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="page-board-actions pipeline-head-actions">
            <a href="<?= base_url('recruiter/jobs') ?>" class="btn btn-outline-primary">
                Back to My Jobs
            </a>
            <a href="<?= base_url('recruiter/jobs/edit/' . $job['id']) ?>" class="btn btn-outline-primary">  Edit</a>
            <a href="<?= base_url('recruiter/jobs/preview/' . $job['id']) ?>" class="btn btn-outline-primary" target="_blank" rel="noopener">  Preview Job</a>
        </div>
    </div>

    <ul class="nav pipeline-work-nav" id="jobDetailTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#applications-list" role="tab"><i class="fas fa-users"></i> Candidates</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#interviews" role="tab"><i class="fas fa-calendar-check"></i> Interviews</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#leaderboard" role="tab"><i class="fas fa-trophy"></i> Leaderboard</a></li>
    </ul>

    <!-- Bulk Action Bar (Shared across Applications and Leaderboard) -->
    <div id="bulkActionBar" class="card shadow-sm mt-3 mb-2 d-none">
        <div class="card-body py-2 d-flex align-items-center justify-content-between">
            <div class="small">
                <span id="selectedCount" class="font-weight-bold text-primary">0</span> candidates selected
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openBulkEmailModal()"> Mail</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openBulkMessageModal()">  Message</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="executeBulkAction('shortlist')">  Shortlist</button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="executeBulkAction('reject')"><i class="fas fa-times-circle mr-1"></i> Reject</button>
            </div>
        </div>
    </div>

    <!-- Bulk Message Modal -->
    <div class="modal fade" id="bulkMessageModal" tabindex="-1" role="dialog" aria-labelledby="bulkMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkMessageModalLabel"><i class="fas fa-comments mr-2"></i>Message Selected Candidates</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label for="bulkMessageText" class="font-weight-bold">Message</label>
                        <textarea class="form-control" id="bulkMessageText" rows="5" maxlength="1000" placeholder="Write a message for the selected candidates..."></textarea>
                        <small class="form-text text-muted">This message will be sent to every selected candidate.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-primary" onclick="executeBulkAction('message')">
                        <i class="fas fa-paper-plane mr-1"></i> Send Message
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Email Modal -->
    <div class="modal fade" id="bulkEmailModal" tabindex="-1" role="dialog" aria-labelledby="bulkEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkEmailModalLabel"><i class="fas fa-at mr-2"></i>Send Email to Selected Candidates</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">To:</label>
                        <div id="emailRecipients" class="p-2 border rounded bg-light" style="max-height: 120px; overflow-y: auto;">
                            <span class="text-muted">No recipients selected</span>
                        </div>
                        <small class="text-muted"><span id="emailRecipientCount">0</span> recipients</small>
                    </div>
                    <div class="form-group">
                        <label for="emailSubject" class="font-weight-bold">Subject:</label>
                        <input type="text" class="form-control" id="emailSubject" placeholder="Enter email subject..." required>
                    </div>
                    <div class="form-group">
                        <label for="emailBody" class="font-weight-bold">Message:</label>
                        <textarea class="form-control" id="emailBody" rows="10" placeholder="Write your email message here..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Quick Templates:</label>
                        <div class="btn-group btn-group-sm flex-wrap">
                            <button type="button" class="btn btn-outline-primary" onclick="applyEmailTemplate('interview')">Interview Invitation</button>
                            <button type="button" class="btn btn-outline-primary" onclick="applyEmailTemplate('followup')">Follow-up</button>
                            <button type="button" class="btn btn-outline-primary" onclick="applyEmailTemplate('rejection')">Rejection Notice</button>
                            <button type="button" class="btn btn-outline-primary" onclick="applyEmailTemplate('offer')">Offer Letter</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-primary" onclick="sendBulkEmail()">
                        <i class="fas fa-paper-plane mr-1"></i> Send Email
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success recruiter-alert"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger recruiter-alert"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="tab-content" id="jobDetailTabContent">
        <!-- Applications List Tab -->
        <div class="tab-pane fade show active" id="applications-list" role="tabpanel">
            <div class="pipeline-board">
                <div class="pipeline-summary-bar">
                    <div class="pipeline-summary-main">
                        <?= $allApplicationsLabel ?> Candidates 
                        <span class="pipeline-hiring-chip"><i class="fas fa-users"></i> <?= max(0, $openings) ?> openings</span>
                        <span class="pipeline-hiring-chip"><i class="fas fa-bullseye"></i> <?= $avgMatch ?>% avg match</span>
                    </div>
                    
                </div>

                <div class="pipeline-stage-rail">
                    <a class="stage-ajax-link <?= $safeActiveStage === 'all' ? 'active' : '' ?>" href="<?= base_url('recruiter/jobs/view/' . $job['id'] . '?stage=all') ?>">
                        <span class="stage-label">All (<?= $allApplicationsLabel ?>)</span>
                    </a>
                    <?php foreach ($statuses as $key => $label): ?>
                        <a class="stage-ajax-link <?= $safeActiveStage === $key ? 'active' : '' ?>" href="<?= base_url('recruiter/jobs/view/' . $job['id'] . '?stage=' . $key) ?>">
                            <span class="stage-label"><?= esc($label) ?> (<?= count($applicationsByStatus[$key] ?? []) ?>)</span>
                        </a>
                    <?php endforeach; ?>
                    
                </div>

                <div class="pipeline-toolbar">
                    <div class="pipeline-search">
                        <i class="fas fa-search"></i>
                        <input type="search" id="candidatePipelineSearch" placeholder="Search candidates..." autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-outline-primary" data-toggle="collapse" data-target="#advancedFilterCollapse" aria-expanded="<?= $hasActiveFilters ? 'true' : 'false' ?>" aria-controls="advancedFilterCollapse">
                        Advanced Filters
                        <?php if ($hasActiveFilters): ?>
                            <span class="badge badge-primary ml-1"><?= count(array_filter($advancedFilters, function($v) { return $v !== '' && $v !== null; })) ?></span>
                        <?php endif; ?>
                    </button>
                    <a href="<?= base_url('recruiter/dashboard/export-excel?type=detailed&job_id=' . (int) $job['id']) ?>" class="btn btn-outline-primary">
                        <i class="fas fa-file-excel"></i> Export Applicants
                    </a>
                </div>

                <!-- Advanced Filter Collapsible -->
                <div class="collapse <?= $hasActiveFilters ? 'show' : '' ?>" id="advancedFilterCollapse">
                    <div class="px-4 py-3 border-bottom bg-light">
                        <form method="get" action="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>">
                            <input type="hidden" name="stage" value="<?= esc($safeActiveStage) ?>">
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">Skills</label>
                                    <input type="text" name="skills" class="form-control form-control-sm" placeholder="e.g. PHP, React" value="<?= esc($advancedFilters['skills'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">Location</label>
                                    <input type="text" name="location" class="form-control form-control-sm" placeholder="e.g. Bangalore" value="<?= esc($advancedFilters['location'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">Experience (Years)</label>
                                    <input type="text" name="experience" class="form-control form-control-sm" placeholder="e.g. 2" value="<?= esc($advancedFilters['experience'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">Last Active</label>
                                    <select name="last_active" class="form-control form-control-sm">
                                        <option value="">Any time</option>
                                        <option value="7" <?= ($advancedFilters['last_active'] ?? '') === '7' ? 'selected' : '' ?>>Last 7 days</option>
                                        <option value="30" <?= ($advancedFilters['last_active'] ?? '') === '30' ? 'selected' : '' ?>>Last 30 days</option>
                                        <option value="90" <?= ($advancedFilters['last_active'] ?? '') === '90' ? 'selected' : '' ?>>Last 90 days</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row align-items-end">
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">ATS Score (Min %)</label>
                                    <input type="number" name="ats_min" class="form-control form-control-sm" min="0" max="100" value="<?= esc($advancedFilters['ats_min'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">ATS Score (Max %)</label>
                                    <input type="number" name="ats_max" class="form-control form-control-sm" min="0" max="100" value="<?= esc($advancedFilters['ats_max'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">Sort By</label>
                                    <select name="sort" class="form-control form-control-sm">
                                        <option value="applied_desc" <?= ($advancedFilters['sort'] ?? '') === 'applied_desc' ? 'selected' : '' ?>>Most recent application</option>
                                        <option value="ats_desc" <?= ($advancedFilters['sort'] ?? '') === 'ats_desc' ? 'selected' : '' ?>>Highest ATS Match</option>
                                        <option value="ats_asc" <?= ($advancedFilters['sort'] ?? '') === 'ats_asc' ? 'selected' : '' ?>>Lowest ATS Match</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="d-flex" style="gap: 8px;">
                                        <button type="submit" class="btn btn-outline-primary btn-sm flex-grow-1 ">Apply Filters</button>
                                        <?php if ($hasActiveFilters): ?>
                                            <a href="<?= base_url('recruiter/jobs/view/' . $job['id'] . '?stage=' . $safeActiveStage) ?>" class="btn btn-outline-primary btn-sm" title="Clear all filters">Clear</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            <div class="tab-content" id="applications-table-wrapper">
                <div class="tab-pane fade show active" id="applications-ajax-container">
                    <?= view('recruiter/partials/job_pipeline_applications', get_defined_vars()) ?>
                </div>
            </div>
            </div>
        </div>

        
        <!-- Interviews Tab -->
        <div class="tab-pane fade" id="interviews" role="tabpanel">
            <?php
            $interviewStats = $interviewStats ?? [
                'total_bookings' => 0,
                'upcoming' => 0,
                'completed' => 0,
                'rescheduled' => 0,
                'booked_slots' => 0,
            ];
            $interviewBookings = $interviewBookings ?? [];
            $interviewSlots = $interviewSlots ?? [];
            ?>
            <div class="card shadow-sm recruiter-summary-card mb-4" style="border-radius: 20px !important;overflow: hidden;">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <div class="recruiter-summary-item">
                                <span class="recruiter-summary-label">Total Bookings</span>
                                <strong><?= number_format((int) $interviewStats['total_bookings']) ?></strong>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <div class="recruiter-summary-item">
                                <span class="recruiter-summary-label">Upcoming</span>
                                <strong><?= number_format((int) $interviewStats['upcoming']) ?></strong>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <div class="recruiter-summary-item">
                                <span class="recruiter-summary-label">Completed</span>
                                <strong><?= number_format((int) $interviewStats['completed']) ?></strong>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="recruiter-summary-item">
                                <span class="recruiter-summary-label">Booked Slots</span>
                                <strong><?= number_format((int) $interviewStats['booked_slots']) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm recruiter-table-card mb-4" style="border-radius: 20px !important;overflow: hidden;">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="m-0 font-weight-bold text-primary">Booked Interviews for This Job</h6>
                    
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover recruiter-bookings-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Candidate</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Booked On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($interviewBookings)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-calendar-check fa-2x text-muted mb-3"></i>
                                            <div class="font-weight-bold">No interview bookings yet</div>
                                            <div class="text-muted">Booked candidate slots for this job will appear here.</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($interviewBookings as $booking): ?>
                                        <?php
                                        $isPast = strtotime((string) $booking['slot_datetime']) < time();
                                        $isUpcoming = strtotime((string) $booking['slot_datetime']) > time();
                                        $bookingStatus = (string) ($booking['booking_status'] ?? 'booked');
                                        $statusColors = [
                                            'booked' => 'primary',
                                            'confirmed' => 'success',
                                            'completed' => 'info',
                                            'rescheduled' => 'warning',
                                            'no_show' => 'danger',
                                            'cancelled' => 'danger',
                                        ];
                                        $statusLabels = [
                                            'booked' => 'Booked',
                                            'confirmed' => 'Confirmed',
                                            'completed' => 'Completed',
                                            'rescheduled' => 'Rescheduled',
                                            'no_show' => 'No Show',
                                            'cancelled' => 'Cancelled',
                                        ];
                                        $color = $statusColors[$bookingStatus] ?? 'secondary';
                                        $hasReview = !empty($booking['review_id']);
                                        ?>
                                        <tr class="<?= $isPast ? 'table-secondary' : '' ?>">
                                            <td><?= (int) $booking['id'] ?></td>
                                            <td>
                                                <div class="recruiter-booking-person">
                                                    <strong><?= esc($booking['candidate_name'] ?? 'Candidate') ?></strong>
                                                    <span><?= esc($booking['email'] ?? '') ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?= !empty($booking['slot_date']) ? date('M d, Y', strtotime($booking['slot_date'])) : date('M d, Y', strtotime($booking['slot_datetime'])) ?></strong><br>
                                                <span class="text-primary"><?= !empty($booking['slot_time']) ? date('h:i A', strtotime($booking['slot_time'])) : date('h:i A', strtotime($booking['slot_datetime'])) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $color ?>">
                                                    <?= esc($statusLabels[$bookingStatus] ?? ucwords(str_replace('_', ' ', $bookingStatus))) ?>
                                                </span>
                                                <?php if ($hasReview): ?>
                                                    <div><small class="text-success"><i class="fas fa-check-circle"></i> Reviewed</small></div>
                                                    <?php if (!empty($booking['review_decision'])): ?>
                                                        <div><small class="text-muted">Decision: <?= esc(ucwords(str_replace('_', ' ', (string) $booking['review_decision']))) ?></small></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if ((int) ($booking['reschedule_count'] ?? 0) > 0): ?>
                                                    <div><small class="text-muted">Rescheduled: <?= (int) $booking['reschedule_count'] ?>x</small></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= !empty($booking['booked_at']) ? date('M d, Y', strtotime($booking['booked_at'])) : '-' ?></small>
                                            </td>
                                            <td>
                                                <div class="job-actions-wrap recruiter-booking-actions">
                                                    <?php if ($isUpcoming && in_array($bookingStatus, ['booked', 'confirmed', 'rescheduled'], true)): ?>
                                                        <a href="<?= base_url('recruiter/slots/reschedule/' . $booking['id']) ?>" class="btn btn-sm btn-warning btn-action">
                                                            <i class="fas fa-sync"></i> Reschedule
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($isPast || in_array($bookingStatus, ['completed', 'no_show', 'rescheduled'], true)): ?>
                                                        <a href="<?= base_url('recruiter/slots/review/' . $booking['id']) ?>" class="btn btn-sm btn-outline-primary btn-action">
                                                            <?= $hasReview ? 'Edit Review' : 'Review Interview' ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm recruiter-table-card" style="border-radius: 20px !important;overflow: hidden;">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="m-0 font-weight-bold text-primary">Slot Capacity</h6>
                    <a href="<?= base_url('recruiter/slots/create') ?>" class="btn btn-sm btn-outline-primary">
                  Create New Slots
                    </a>
                </div>
                <div class="card-body" >
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover recruiter-slots-table">
                            <thead class="thead-light">
                                <tr>
                                <th>ID</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Capacity</th>
                                    <th>Booked</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                                <?php if (empty($interviewSlots)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">No booked slots found for this job</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($interviewSlots as $slot): ?>
                                        <?php
                                        $isPastSlot = strtotime((string) $slot['slot_datetime']) < time();
                                        $isFull = (int) $slot['booked_count'] >= (int) $slot['capacity'];
                                        ?>
                                        <tr class="<?= $isPastSlot ? 'table-secondary' : ($isFull ? 'table-warning' : '') ?>">
                                            <td><?= (int) $slot['id'] ?></td>
                                            <td><?= date('M d, Y', strtotime($slot['slot_date'])) ?></td>
                                            <td><strong><?= date('h:i A', strtotime($slot['slot_time'])) ?></strong></td>
                                            <td><?= (int) $slot['capacity'] ?></td>
                                            <td>
                                                <span class="badge badge-primary"><?= (int) $slot['booked_count'] ?></span>
                                            </td>
                                            <td>
                                                <?php if ($isPastSlot): ?>
                                                    <span class="badge badge-secondary">Past</span>
                                                <?php elseif ($isFull): ?>
                                                    <span class="badge badge-danger">Full</span>
                                                <?php else: ?>
                                                    <span class="badge badge-dark">Available</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= esc($slot['created_by_name']) ?></td>
                                    <td>
                                        <?php if ($slot['booked_count'] == 0): ?>
                                            <a href="<?= base_url('recruiter/slots/edit/' . $slot['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= base_url('recruiter/slots/delete/' . $slot['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this slot?')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Has bookings</span>
                                        <?php endif; ?>
                                    </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard Tab -->
        <div class="tab-pane fade" id="leaderboard" role="tabpanel">
            <div class="card shadow-sm recruiter-leaderboard-card" style="border-radius: 20px !important;overflow: hidden;">
                <div class="card-header ">
                    <h6 class="m-0 font-weight-bold">
                      Comparison View - <?= esc($job['title']) ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border mb-4">
                        <strong>How to use this page:</strong> compare candidate quality here, then return to Candidates to shortlist, reject, or message applicants.
                    </div>

                    <?php if (empty($leaderboard)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No candidates found for this leaderboard</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover leaderboard-table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="60">Rank</th>
                                        <th>Candidate</th>
                                        <th>Job Position</th>
                                        <th>Skills</th>
                                        <th>GitHub Stack</th>
                                        <th class="text-center">Technical</th>
                                        <th class="text-center">Communication</th>
                                        <th class="text-center">Overall Rating</th>
                                        <th class="text-center">ATS</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Review</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaderboard as $candidate): ?>
                                        <?php
                                            $rank = (int) ($candidate['rank'] ?? 0);
                                            $candidateSkills = $candidate['candidate_skills'] ?? [];
                                            $requiredSkills = $candidate['required_skills'] ?? [];
                                            $candidateSkillsLower = array_map('strtolower', $candidateSkills);
                                            $technicalScore = (float) ($candidate['technical_score'] ?? 0);
                                            $communicationScore = (float) ($candidate['communication_score'] ?? 0);
                                            $overallRating = (float) ($candidate['overall_rating'] ?? 0);
                                            $atsScore = (int) ($candidate['ats_score'] ?? 0);
                                            $status = (string) ($candidate['status'] ?? 'applied');
                                            $statusColors = [
                                                'applied' => 'secondary',
                                                'pending' => 'secondary',
                                                'ai_interview_completed' => 'info',
                                                'shortlisted' => 'primary',
                                                'interview_scheduled' => 'warning',
                                                'interview_slot_booked' => 'warning',
                                                'interviewed' => 'info',
                                                'offered' => 'success',
                                                'selected' => 'success',
                                                'hired' => 'success',
                                                'filtered_out' => 'dark',
                                                'rejected' => 'danger',
                                                'withdrawn' => 'secondary',
                                                'on_hold' => 'warning',
                                            ];
                                            $color = $statusColors[$status] ?? 'secondary';
                                        ?>
                                        <tr class="<?= $rank <= 3 ? 'top-performer' : '' ?>">
                                            <td class="rank-cell">
                                                <?php if ($rank === 1): ?>
                                                    <span  >  1</span>
                                                <?php elseif ($rank === 2): ?>
                                                    <span  >  2</span>
                                                <?php elseif ($rank === 3): ?>
                                                    <span  > 3</span>
                                                <?php else: ?>
                                                    <span  ><?= $rank ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="candidate-info">
                                                    <strong><?= esc($candidate['candidate_name'] ?? $candidate['name'] ?? 'Candidate') ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= esc($candidate['candidate_email'] ?? $candidate['email'] ?? '') ?></small>
                                                </div>
                                            </td>
                                            <td><?= esc($candidate['job_title'] ?? $job['title']) ?></td>
                                            <td>
                                                <div class="skills-display">
                                                    <?php if (!empty($requiredSkills)): ?>
                                                        <div class="skill-match-badge mb-2">
                                                            <span class="status-pill">
                                                                <?= (int) ($candidate['skill_match'] ?? 0) ?>% Match
                                                            </span>
                                                            <small class="text-muted">
                                                                (<?= count(array_intersect($candidateSkillsLower, array_map('strtolower', $requiredSkills))) ?>/<?= count($requiredSkills) ?>)
                                                            </small>
                                                        </div>
                                                        <div class="required-skills">
                                                            <?php foreach ($requiredSkills as $requiredSkill): ?>
                                                                <?php $hasSkill = in_array(strtolower($requiredSkill), $candidateSkillsLower, true); ?>
                                                                <span class="status-pill" title="<?= $hasSkill ? 'Candidate has this skill' : 'Candidate does not have this skill' ?>">
                                                                    <?= esc($requiredSkill) ?>
                                                                    <i class="fas fa-<?= $hasSkill ? 'check' : 'times' ?>-circle <?= $hasSkill ? 'text-success' : 'text-danger' ?>"></i>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">No required skills specified</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($candidate['github_stack'])): ?>
                                                    <div class="skills-display">
                                                        <div class="required-skills">
                                                            <?php foreach (array_slice($candidate['github_stack'], 0, 6) as $language): ?>
                                                                <span class="status-pill"><?= esc($language) ?></span>
                                                            <?php endforeach; ?>
                                                            <?php if (count($candidate['github_stack']) > 6): ?>
                                                                <span class="status-pill">+<?= count($candidate['github_stack']) - 6 ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No GitHub stack</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="score-display">
                                                    <span class="score-value <?= $technicalScore >= 80 ? 'text-success' : ($technicalScore >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                        <?= number_format($technicalScore, 1) ?>
                                                    </span>
                                                    <div class="score-bar"><div class="score-fill" style="width: <?= min(100, max(0, $technicalScore)) ?>%"></div></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="score-display">
                                                    <span class="score-value <?= $communicationScore >= 80 ? 'text-success' : ($communicationScore >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                        <?= number_format($communicationScore, 1) ?>
                                                    </span>
                                                    <div class="score-bar"><div class="score-fill" style="width: <?= min(100, max(0, $communicationScore)) ?>%"></div></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="overall-rating">
                                                    <span class="status-pill">
                                                        <?= number_format($overallRating, 1) ?>
                                                    </span>
                                                    
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="score-display">
                                                    <span class="score-value <?= $atsScore >= 80 ? 'text-success' : ($atsScore >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                        <?= $atsScore ?>
                                                    </span>
                                                    <div class="score-bar"><div class="score-fill" style="width: <?= min(100, max(0, $atsScore)) ?>%"></div></div>
                                                    <small class="text-muted d-block mt-1">Fit signal</small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="status-pill">
                                                    <?= esc($statuses[$status] ?? ucwords(str_replace('_', ' ', $status))) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('recruiter/candidate/' . $candidate['candidate_id'] . '?application_id=' . $candidate['id'] . '&job_id=' . $candidate['job_id']) ?>" class="btn btn-sm btn-outline-primary">
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
        </div>
    </div>
</div>
</div>

<div class="modal fade ai-modal" id="aiReportModal" tabindex="-1" style="border-radius: 20px !important;overflow: hidden;">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content ai-modal-content">
      <div class="modal-header ai-header">
        <div class="ai-title">
          <span class="ai-icon"><i class="fas fa-chart-line"></i></span>
          <div>
            <h5>AI Interview Report</h5>
            <small>Detailed candidate performance and insights</small>
          </div>
        </div>
        <button type="button" class="ai-close" data-dismiss="modal" aria-label="Close">
          x
        </button>
      </div>
      <div class="modal-body ai-body">
        <div id="aiReportContent">
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
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.view-ai-report-btn');
    if (!btn) return;

    const candidateId = btn.dataset.candidateId;
    const jobrole = btn.dataset.jobrole;
    const candidateName = btn.dataset.candidateName;
    const reportHost = document.getElementById('aiReportContent');

    if (!reportHost) return;

    $('#aiReportModal').modal('show');
    reportHost.innerHTML = `
        <div class="ai-loader">
            <div class="spinner"></div>
            <p>Analyzing interview performance...</p>
        </div>`;

    fetch('<?= base_url('recruiter/get-ai-report') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ candidate_id: candidateId, jobrole: jobrole })
    })
    .then(function (res) {
        if (!res.ok) {
            return res.text().then(function (txt) {
                throw new Error('Server error ' + res.status + (txt ? ': ' + txt.substring(0, 200) : ''));
            });
        }
        return res.json();
    })
    .then(function (data) {
        if (!data || typeof data !== 'object') {
            throw new Error('Invalid response from server.');
        }

        let violationsHtml = '';
        if (Array.isArray(data.violations) && data.violations.length) {
            data.violations.forEach(function (v) {
                violationsHtml += `
                    <tr>
                        <td>${v.message ?? '-'}</td>
                        <td><span class="status-pill">${v.total ?? 0}</span></td>
                    </tr>`;
            });
        } else {
            violationsHtml = `
                <tr>
                    <td colspan="2" class="text-center text-muted">No violations found</td>
                </tr>`;
        }

        let resultHtml = '';
        if (Array.isArray(data.results) && data.results.length) {
            data.results.forEach(function (r) {
                resultHtml += `
                    <tr>
                        <td>${r.round_name ?? '-'}</td>
                        <td>${r.score ?? 0}</td>
                        <td>${r.total_questions ?? 0}</td>
                        <td><span class="status-pill">${r.percentage ?? 0}%</span></td>
                    </tr>`;
            });
        } else {
            resultHtml = `
                <tr>
                    <td colspan="4" class="text-center text-muted">No interview results</td>
                </tr>`;
        }

        reportHost.innerHTML = `
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h4 class="font-weight-bold">${candidateName}</h4>
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
                            <h5 class="mb-0">Violations</h5>
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
        reportHost.innerHTML = `
            <div class="ai-error">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Failed to load report</strong>
                <p class="mb-0 text-muted" style="font-size:0.85rem">${err.message || 'An unexpected error occurred. Please try again.'}</p>
            </div>`;
    });
});
</script>

<?= view('Layouts/recruiter_footer', [
    'pageScripts' => [base_url('jobboard/js/recruiter-pipeline.js?v=' . @filemtime(FCPATH . 'jobboard/js/recruiter-pipeline.js'))],
]) ?>
