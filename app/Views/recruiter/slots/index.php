<?= view('Layouts/recruiter_header', ['title' => 'Interview Slots']) ?>
<style>
 .container-fluid {
    max-width: 100% !important;
    padding-left: 34px !important;
    padding-right: 34px !important;
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
    color: #FFFFFF !important;
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
    background: #000000 !important;
    color: #0D8A90;
    border: 1px solid rgba(31, 183, 181, 0.15) !important;
}
 
.card.recruiter-stat-card.recruiter-stat-applications::before,body.dark .card.recruiter-stat-card.recruiter-stat-applications::before{
    display:none !important;
}
/* ═══════════════════════════════════════════════════
   INTERVIEW SLOTS PAGE — THEMED CSS
   Mirrors Candidate Database patterns
   Uses CSS variables + body.dark toggle
═══════════════════════════════════════════════════ */

 

/* ── Page title ── */
.page-board-title {
    font-size: 1.625rem !important;
    font-weight: 700 !important;
    color: #16212B !important;
    margin: 0;
}
body.dark .page-board-title {
    color: #FFFFFF !important;
}

/* ── Page subtitle ── */
.page-board-subtitle {
    color: #64748B !important;
    font-size: 1rem;
}
body.dark .page-board-subtitle {
    color: #FFFFFF !important;
}

/* ── Card borders ── */
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

/* ── Dark card base ── */
body.dark .card,
body.dark .card-header,
body.dark .card-body {
    border-color: #000000 !important;
}
body.dark .card.shadow-sm {
    box-shadow: none !important;
}

/* ── Card header h6 ── */
.recruiter-filter-card .card-body h6,
.recruiter-filter-card .card-body h6.font-weight-bold,
.recruiter-table-card .card-header h6,
.recruiter-table-card .card-header h6.font-weight-bold {
    font-size: 1rem;
    font-weight: 700 !important;
    color: #16212B !important;
}
body.dark .recruiter-filter-card .card-body h6,
body.dark .recruiter-filter-card .card-body h6.font-weight-bold,
body.dark .recruiter-table-card .card-header h6,
body.dark .recruiter-table-card .card-header h6.font-weight-bold {
    color: #FFFFFF !important;
}

/* ── Muted text inside cards ── */
.recruiter-filter-card .text-muted,
.recruiter-table-card .text-muted {
    color: #64748B !important;
    font-size: 1rem;
}
body.dark .recruiter-filter-card .text-muted,
body.dark .recruiter-table-card .text-muted {
    color: #FFFFFF !important;
}

/* ── Stat cards — kill ::before accent bar ── */
.card.recruiter-stat-card.recruiter-stat-applications::before,
body.dark .card.recruiter-stat-card.recruiter-stat-applications::before {
    display: none !important;
}

/* ── Stat card labels & values ── */
.recruiter-stat-card .text-xs {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #64748B;
}
body.dark .recruiter-stat-card .text-xs {
    color: #FFFFFF !important;
}
.recruiter-stat-card .h5 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #16212B !important;
}
body.dark .recruiter-stat-card .h5 {
    color: #FFFFFF !important;
}

/* ── Filter form labels ── */
.recruiter-slot-filter-form label {
    font-size: 1rem;
    font-weight: 500 !important;
    color: #16212B;
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
}
body.dark .recruiter-slot-filter-form label {
    color: #FFFFFF !important;
}

/* ── Filter form inputs ── */
.recruiter-slot-filter-form .form-control {
    font-size: 1rem;
    border: 1px solid #D9ECE5;
    border-radius: 6px;
    background: #FFFFFF;
    color: #16212B;
    transition: border-color .2s, box-shadow .2s;
}
body.dark .recruiter-slot-filter-form .form-control {
    border: 1px solid #23343A !important;
    background: #000000 !important;
    color: #FFFFFF !important;
}

/* ── Kill Bootstrap focus glow, apply brand border ── */
.recruiter-slot-filter-form .form-control:focus,
body.dark .recruiter-slot-filter-form .form-control:focus,
.form-control:focus {
    outline: 0 !important;
    box-shadow: none !important;
    border-color: #0D8A90 !important;
}

/* ── Primary button ── */
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

/* ── Status pill ── */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    background: #16212b14;
    color: #0D8A90;
    border: none;
    text-decoration: none !important;
    white-space: nowrap;
    cursor: pointer;
    transition: opacity .15s;
}
body.dark .status-pill {
    background: #000000;
    color: #0D8A90;
}
.status-pill:hover {
    opacity: .85;
    color: #0D8A90;
    text-decoration: none !important;
}
/* ═══════════════════════════════════════════════════
   INTERVIEW SLOTS PAGE — FULL THEMED CSS
   Fixed: table rows, stat cards, badges, shadows
═══════════════════════════════════════════════════ */

/* ── Page wrapper & structural backgrounds ── */
.recruiter-slots-jobboard  {
    background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%) !important;
}
body.dark .recruiter-slots-jobboard,
body.dark .page-board-header,
body.dark .card.shadow-sm.recruiter-table-card,
body.dark .hm-page-content,
body.dark .card.shadow-sm.recruiter-filter-card,
body.dark .card-header {
    background: #000000 !important;
}
 
/* ── Page title ── */
.page-board-title {
    font-size: 1.625rem !important;
    font-weight: 700 !important;
    color: #16212B !important;
    margin: 0;
}
body.dark .page-board-title {
    color: #FFFFFF !important;
}

/* ── Page subtitle ── */
.page-board-subtitle {
    color: #64748B !important;
    font-size: 1rem;
}
body.dark .page-board-subtitle {
    color: #FFFFFF !important;
}

/* ══════════════════════════════════════
   STAT CARDS — strip ALL legacy effects
══════════════════════════════════════ */
.recruiter-stat-card,
.card.recruiter-stat-card {
    background: white !important;
    border: 1px solid #D9ECE5 !important;
    border-radius: 12px !important;
    box-shadow: none !important;
}
body.dark .recruiter-stat-card,
body.dark .card.recruiter-stat-card {
    background: #000000 !important;
    border: 1px solid #23343A !important;
    box-shadow: none !important;
}

/* Kill ALL ::before / ::after pseudo elements on stat cards */
.recruiter-stat-card::before,
.recruiter-stat-card::after,
.card.recruiter-stat-card::before,
.card.recruiter-stat-card::after,
.recruiter-stat-applications::before,
.recruiter-stat-applications::after,
.recruiter-stat-openjobs::before,
.recruiter-stat-openjobs::after,
.recruiter-stat-conversion::before,
.recruiter-stat-conversion::after,
.recruiter-stat-bookings::before,
.recruiter-stat-bookings::after {
    display: none !important;
    content: none !important;
}
body.dark .recruiter-stat-card::before,
body.dark .recruiter-stat-card::after,
body.dark .card.recruiter-stat-card::before,
body.dark .card.recruiter-stat-card::after {
    display: none !important;
    content: none !important;
}

/* Stat card inner body */
.recruiter-stat-card .card-body {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

/* Stat labels */
.recruiter-stat-card .text-xs {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #64748B !important;
}
body.dark .recruiter-stat-card .text-xs {
    color: #FFFFFF !important;
}

/* Stat values */
.recruiter-stat-card .h5,
.recruiter-stat-card h5 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #16212B !important;
    margin-bottom: 0;
}
body.dark .recruiter-stat-card .h5,
body.dark .recruiter-stat-card h5 {
    color: #FFFFFF !important;
}

/* Stat icons */
.recruiter-stat-card .fa-2x {
    color: #D9ECE5 !important;
}
body.dark .recruiter-stat-card .fa-2x {
    color: #FFFFFF !important;
}

/* ══════════════════════════════════════
   FILTER & TABLE CARDS
══════════════════════════════════════ */
.recruiter-table-card,
.recruiter-filter-card {
    border: 1px solid #D9ECE5 !important;
    border-radius: 12px !important;
    box-shadow: none !important;
    overflow: hidden;
}
body.dark .recruiter-table-card,
body.dark .recruiter-filter-card {
    border-color: #23343A !important;
    box-shadow: none !important;
}

/* ── Dark card base reset ── */
body.dark .card,
body.dark .card-header,
body.dark .card-body {
    border-color: #23343A !important;
}
body.dark .card.shadow-sm,
body.dark .card.shadow {
    box-shadow: none !important;
}

/* ── Card header h6 ── */
.recruiter-filter-card .card-body h6,
.recruiter-filter-card .card-body h6.font-weight-bold,
.recruiter-table-card .card-header h6,
.recruiter-table-card .card-header h6.font-weight-bold {
    font-size: 1rem;
    font-weight: 700 !important;
    color: #16212B !important;
}
body.dark .recruiter-filter-card .card-body h6,
body.dark .recruiter-filter-card .card-body h6.font-weight-bold,
body.dark .recruiter-table-card .card-header h6,
body.dark .recruiter-table-card .card-header h6.font-weight-bold {
    color: #FFFFFF !important;
}

/* ── Muted text ── */
.recruiter-filter-card .text-muted,
.recruiter-table-card .text-muted {
    color: #64748B !important;
    font-size: 1rem;
}
body.dark .recruiter-filter-card .text-muted,
body.dark .recruiter-table-card .text-muted {
    color: #FFFFFF !important;
}

/* ── d-flex header text dark ── */
body.dark .d-flex.align-items-start.justify-content-between.flex-wrap h6 {
    color: #FFFFFF !important;
}

/* ══════════════════════════════════════
   FILTER FORM
══════════════════════════════════════ */
.recruiter-slot-filter-form label {
    font-size: 1rem;
    font-weight: 500 !important;
    color: #16212B;
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
}
body.dark .recruiter-slot-filter-form label {
    color: #FFFFFF !important;
}

.recruiter-slot-filter-form .form-control {
    font-size: 1rem;
    border: 1px solid #D9ECE5;
    border-radius: 6px;
    background: #FFFFFF;
    color: #16212B;
    transition: border-color .2s, box-shadow .2s;
}
body.dark .recruiter-slot-filter-form .form-control {
    border: 1px solid #23343A !important;
    background: #000000 !important;
    color: #FFFFFF !important;
}

.recruiter-slot-filter-form .form-control:focus,
body.dark .recruiter-slot-filter-form .form-control:focus,
.form-control:focus {
    outline: 0 !important;
    box-shadow: none !important;
    border-color: #0D8A90 !important;
}

 

/* Small action buttons in table */
.btn-sm {
    font-size: 0.875rem;
    padding: 5px 10px;
}

/* ══════════════════════════════════════
   STATUS PILL
══════════════════════════════════════ */
 
/* ══════════════════════════════════════
   TABLE — full reset, clean design
══════════════════════════════════════ */
.recruiter-slots-table {
    width: 100%;
    border-collapse: collapse !important;
    font-size: 13.5px;
    border: none !important;
}

/* Remove Bootstrap's default table-bordered borders */
.recruiter-slots-table,
.recruiter-slots-table th,
.recruiter-slots-table td,
.table-bordered,
.table-bordered th,
.table-bordered td {
    border: none !important;
}

/* thead */
.recruiter-slots-table thead tr {
    background: #EDF8F5 !important;
    border-bottom: 2px solid #D9ECE5 !important;
}
.recruiter-slots-table thead th {
    padding: 13px 16px !important;
    font-size: 12.5px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: .5px !important;
    color: #64748B !important;
    white-space: nowrap;
    border: none !important;
    background: white !important;
    box-shadow: none !important;
}
body.dark .recruiter-slots-table thead tr ,body.dark .recruiter-slots-table thead th{
    background: #000000 !important;
    border-bottom: 2px solid #23343A !important;
}
body.dark .recruiter-slots-table thead th {
    color: #FFFFFF !important;
}

/* tbody rows — clean white, only bottom separator */
.recruiter-slots-table tbody tr {
    background: #FFFFFF !important;
    border-bottom: 1px solid #EEF2F7 !important;
    transition: background .15s;
}
.recruiter-slots-table tbody tr:last-child {
    border-bottom: none !important;
}
.recruiter-slots-table tbody tr:hover {
    background: #F4FBFA !important;
}
body.dark .recruiter-slots-table tbody tr {
    background: #000000 !important;
    border-bottom: 1px solid #23343A !important;
}
body.dark .recruiter-slots-table tbody tr:hover {
    background: rgba(31, 183, 181, 0.05) !important;
}

/* tbody cells */
.recruiter-slots-table tbody td {
    padding: 14px 16px !important;
    vertical-align: middle !important;
    font-size: 13.5px !important;
    line-height: 1.45 !important;
    color: #16212B !important;
    border: none !important;
    background: white !important;
}
body.dark .recruiter-slots-table tbody td { 
    color: #FFFFFF !important; 
    background: transparent !important;
}

/* secondary / small text */
.recruiter-slots-table tbody td small {
    display: block;
    margin-top: 2px;
    font-size: 12.5px;
    color: #64748B !important;
}
body.dark .recruiter-slots-table tbody td small {
    color: #FFFFFF !important;
}

/* ── Past row — subtle grey tint, NOT full grey band ── */
.recruiter-slots-table tr.table-secondary,
.recruiter-slots-table tr.table-secondary td {
    background: white !important;
    color: #94A3B8 !important;
}
body.dark .recruiter-slots-table tr.table-secondary,
body.dark .recruiter-slots-table tr.table-secondary td { 
    background: #000000 !important;
    color: #FFFFFF !important; 
}

/* ── Full/warning row — very subtle accent tint ── */
.recruiter-slots-table tr.table-warning,
.recruiter-slots-table tr.table-warning td {
    background: rgba(181, 216, 78, 0.06) !important;
}
body.dark .recruiter-slots-table tr.table-warning,
body.dark .recruiter-slots-table tr.table-warning td { 
    background: #000000 !important;
    color: #FFFFFF !important; 
}
body.dark .recruiter-slots-table tr.table-warning:hover,
body.dark .recruiter-slots-table tr.table-warning:hover td {
    background: #1b221e !important;
}
body.dark .recruiter-slots-table tr.table-secondary:hover,
body.dark .recruiter-slots-table tr.table-secondary:hover td {
    background: #191f24 !important;
}

/* ── Bootstrap badge overrides inside table ── */
.recruiter-slots-table .badge {
    font-size: 12.5px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 50px;
}
.recruiter-slots-table .badge-secondary {
    background: #EDF8F5 !important;
    color: #64748B !important;
}
.recruiter-slots-table .badge-primary {
    background: rgba(31, 183, 181, 0.15) !important;
    color: #0D8A90 !important;
}
.recruiter-slots-table .badge-danger {
    background: rgba(239, 68, 68, 0.1) !important;
    color: #DC2626 !important;
}
.recruiter-slots-table .badge-dark {
    background: rgba(22, 33, 43, 0.08) !important;
    color: #16212B !important;
}
.recruiter-slots-table .badge-warning {
    background: rgba(181, 216, 78, 0.15) !important;
    color: #6B7B0E !important;
}
body.dark .recruiter-slots-table .badge-secondary {
    background: #1B2A2F !important;
    color: #94A3B8 !important;
}
body.dark .recruiter-slots-table .badge-primary {
    background: rgba(31, 183, 181, 0.15) !important;
    color: #1FB7B5 !important;
}
body.dark .recruiter-slots-table .badge-danger {
    background: rgba(239, 68, 68, 0.12) !important;
    color: #F87171 !important;
}
body.dark .recruiter-slots-table .badge-dark {
    background: rgba(248, 250, 252, 0.08) !important;
    color: #E2E8F0 !important;
}
body.dark .recruiter-slots-table .badge-warning {
    background: rgba(181, 216, 78, 0.1) !important;
    color: #B5D84E !important;
}

/* ── "Has bookings" muted text ── */
.recruiter-slots-table .text-muted {
    color: #94A3B8 !important;
    font-size: 12.5px;
}
body.dark .recruiter-slots-table .text-muted {
    color: #FFFFFF !important;
}

/* ══════════════════════════════════════
   PAGINATION
══════════════════════════════════════ */
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
    border-color: #23343A !important;
}

/* ── page-board-header dark border reset ── */
body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header {
    border: none !important;
}

/* ══════════════════════════════════════
   INLINE TOOLBAR (stats + filters)
══════════════════════════════════════ */
.slots-toolbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid #D9ECE5;
}
body.dark .slots-toolbar { border-bottom-color: #23343A; }

.slots-stats {
    display: flex;
    align-items: center;
    gap: 0;
    flex-shrink: 0;
}
.slots-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 18px;
    line-height: 1.2;
}
.slots-stat-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: #16212B;
}
body.dark .slots-stat-value { color: #F8FAFC; }
.slots-stat-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94A3B8;
}
.slots-stat-divider {
    width: 1px;
    height: 28px;
    background: #D9ECE5;
    flex-shrink: 0;
}
body.dark .slots-stat-divider { background: #23343A; }

.slots-filters {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-left: auto;
}
.slots-filter-input {
    height: 34px;
    padding: 0 10px;
    border: 1px solid #D9ECE5;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #16212B;
    background: #fff;
    outline: none;
    transition: border-color .2s;
}
.slots-filter-input:focus { border-color: #0D8A90; }
body.dark .slots-filter-input {
    background: #1B2A2F;
    border-color: #23343A;
    color: #F8FAFC;
}
.slots-filter-btn {
    height: 34px;
    padding: 0 14px;
    border: 1.5px solid #1FB7B5;
    border-radius: 6px;
    background: transparent;
    color: #1FB7B5;
    font-size: 0.85rem;
    cursor: pointer;
    transition: background .2s, color .2s;
}
.slots-filter-btn:hover { background: #1FB7B5; color: #fff; }
.slots-filter-clear {
    font-size: 0.82rem;
    color: #94A3B8;
    text-decoration: none !important;
    white-space: nowrap;
}
.slots-filter-clear:hover { color: #ef4444; }

</style>
<div class="recruiter-slots-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">Interview Slots</h1>
            <p class="page-board-subtitle">Create, review, and manage slots before candidates book interview windows.</p>
        </div>
        <div class="page-board-actions">
            <a href="<?= base_url('recruiter/slots/create') ?>" class="btn btn-outline-primary">Create New Slots</a>
            <a href="<?= base_url('recruiter/slots/bookings') ?>" class="btn btn-outline-primary">View All Bookings</a>
        </div>
    </div>

    <div class="card shadow-sm recruiter-table-card" style="border-radius:20px !important;overflow:visible;">

        <!-- Inline stat strip + filters in one header row -->
        <div class="slots-toolbar">
            <div class="slots-stats">
                <div class="slots-stat">
                    <span class="slots-stat-value"><?= $stats['total_slots'] ?></span>
                    <span class="slots-stat-label">Total</span>
                </div>
                <div class="slots-stat-divider"></div>
                <div class="slots-stat">
                    <span class="slots-stat-value" style="color:#1FB7B5;"><?= $stats['available_slots'] ?></span>
                    <span class="slots-stat-label">Available</span>
                </div>
                <div class="slots-stat-divider"></div>
                <div class="slots-stat">
                    <span class="slots-stat-value" style="color:#f59e0b;"><?= $stats['fully_booked'] ?></span>
                    <span class="slots-stat-label">Full</span>
                </div>
                <div class="slots-stat-divider"></div>
                <div class="slots-stat">
                    <span class="slots-stat-value" style="color:#6366f1;"><?= $stats['total_bookings'] ?></span>
                    <span class="slots-stat-label">Bookings</span>
                </div>
            </div>

            <form method="get" action="<?= base_url('recruiter/slots') ?>" class="slots-filters">
                <select name="job_id" class="slots-filter-input">
                    <option value="">All Jobs</option>
                    <?php foreach ($jobs as $job): ?>
                        <option value="<?= $job['id'] ?>" <?= ($filters['job_id'] ?? '') == $job['id'] ? 'selected' : '' ?>><?= esc($job['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date" class="slots-filter-input" value="<?= esc($filters['date'] ?? '') ?>">
                <select name="status" class="slots-filter-input">
                    <option value="">All Status</option>
                    <option value="available" <?= ($filters['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="full" <?= ($filters['status'] ?? '') === 'full' ? 'selected' : '' ?>>Fully Booked</option>
                    <option value="past" <?= ($filters['status'] ?? '') === 'past' ? 'selected' : '' ?>>Past</option>
                </select>
                <button type="submit" class="slots-filter-btn"><i class="fas fa-search"></i></button>
                <?php if (!empty($filters['job_id']) || !empty($filters['date']) || !empty($filters['status'])): ?>
                    <a href="<?= base_url('recruiter/slots') ?>" class="slots-filter-clear">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover recruiter-slots-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Job</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Capacity</th>
                        <th>Booked</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($slots)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">No slots found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($slots as $slot): ?>
                            <?php
                            $isPast = strtotime($slot['slot_datetime']) < time();
                            $isAvailable = $slot['is_available'] && !$isPast;
                            $isFull = $slot['booked_count'] >= $slot['capacity'];
                            ?>
                            <tr class="<?= $isPast ? 'table-secondary' : ($isFull ? 'table-warning' : '') ?>">
                                <td><?= esc($slot['job_title']) ?></td>
                                <td><?= date('M d, Y', strtotime($slot['slot_date'])) ?></td>
                                <td><strong><?= date('h:i A', strtotime($slot['slot_time'])) ?></strong></td>
                                <td><?= $slot['capacity'] ?></td>
                                <td><?= $slot['booked_count'] ?></td>
                                <td>
                                    <?php if ($isPast): ?>
                                        <span class="status-pill">Past</span>
                                    <?php elseif ($isFull): ?>
                                        <span class="status-pill" style="color:#f59e0b;">Full</span>
                                    <?php elseif ($isAvailable): ?>
                                        <span class="status-pill">Available</span>
                                    <?php else: ?>
                                        <span class="status-pill">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($slot['created_by_name']) ?></td>
                                <td>
                                    <?php if ($slot['booked_count'] == 0): ?>
                                        <a href="<?= base_url('recruiter/slots/edit/' . $slot['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <a href="<?= base_url('recruiter/slots/delete/' . $slot['id']) ?>" class="btn btn-sm btn-outline-primary" onclick="return confirm('Delete this slot?')" style="color:#ef4444;border-color:#ef4444;">Delete</a>
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

        <?php if (isset($pager) && is_object($pager) && method_exists($pager, 'links') && $pager->getPageCount() > 1): ?>
            <div class="px-3 py-2">
                <?= $pager->links('default', 'portal_full') ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>
