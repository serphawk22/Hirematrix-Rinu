<?= view('Layouts/recruiter_header', ['title' => 'Interview Bookings']) ?>
<style>
       .btn-primary,.btn-outline-first {
  background: transparent !important;
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important;
    padding: 8px 20px;
    border-radius: 6px !important;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-primary:hover, .btn-primary:focus, .btn-outline-first:focus, .btn-outline-first:hover {
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
    background: #000000 !important;
    color: #0D8A90;
    border: 1px solid rgba(31, 183, 181, 0.15) !important;
}
.page-board-header.page-board-header-tight.recruiter-page-board-header,body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border: none !important;
}
     
body.dark .m-0.font-weight-bold{
    color:#FFFFFF !important;
}
.hm-page-content,.recruiter-slot-bookings-jobboard{
         background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
}
body.dark .hm-page-content,body.dark .recruiter-slot-bookings-jobboard, body.dark .recruiter-summary-card, body.dark .recruiter-filter-card,body.dark .recruiter-table-card{
    background: #000000 !important;
    border: 1px solid #23343A !important;
} 
/* Replace your existing tr,td,th block with this: */
tr, td, th {
    font-size: 1rem;
    font-weight: 500 !important;
    color: var(--foreground, #16212B);
    background: white !important;
}

/* Add these dark mode overrides */
body.dark tr,
body.dark td,
body.dark th {
   background: #000000 !important; 
    color:#FFFFFF !important;
    border-color: #23343A !important;
}

body.dark .table-secondary td,
body.dark .table-secondary th,
body.dark .table-secondary {
   background: #000000 !important;
}

body.dark thead th {
   background: #000000 !important;
    color: #FFFFFF !important;
}
 body.dark .recruiter-summary-card{
   color:#FFFFFF !important;
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
    background:#000000 !important;
    color: #FFFFFF !important;
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
 
body.dark .recruiter-job-form label, body.dark h6 {
    font-size: 1rem;        /* same as Bootstrap h6 */
    font-weight: 500 !important;   
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
    color:#FFFFFF !important;
}
body.dark .m-0.font-weight-bold,body.dark .recruiter-summary-item{
     color:#FFFFFFF !important;
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
body.dark span{
    color:#FFFFFF !important;
}
.recruiter-slot-bookings-jobboard .recruiter-filter-card {
    margin-bottom: 14px !important;
}
.recruiter-slot-bookings-jobboard .recruiter-filter-card .card-body {
    padding: 14px 16px !important;
}
.recruiter-booking-filter-head {
    align-items: center;
    display: flex;
    gap: 10px;
    justify-content: space-between;
    margin-bottom: 10px;
}
.recruiter-booking-filter-title {
    color: #16212B;
    font-size: 0.92rem;
    font-weight: 700;
    line-height: 1.2;
    margin: 0;
}
.recruiter-booking-filter-hint {
    color: #64748B;
    font-size: 0.78rem;
    margin: 0;
}
.recruiter-booking-filter-grid {
    align-items: end;
    display: grid;
    gap: 10px;
    grid-template-columns: minmax(260px, 1fr) minmax(180px, 0.7fr) auto;
}
.recruiter-booking-filter-grid .form-control {
    min-height: 40px !important;
    padding: 8px 12px !important;
}
.recruiter-booking-filter-actions .btn {
    min-height: 40px;
    min-width: 150px;
    padding: 8px 16px !important;
}
body.dark .recruiter-booking-filter-title,
body.dark .recruiter-booking-filter-hint {
    color: #FFFFFF !important;
}
@media (max-width: 767.98px) {
    .recruiter-booking-filter-grid {
        grid-template-columns: 1fr;
    }
    .recruiter-booking-filter-actions .btn {
        width: 100%;
    }
}
.recruiter-slot-bookings-jobboard .recruiter-table-card {
    border-radius: 20px !important;
    overflow: hidden;
}
.recruiter-slot-bookings-jobboard .recruiter-table-card .card-header {
    border: 0 !important;
    border-bottom: 1px solid #D9ECE5 !important;
}
.recruiter-slot-bookings-jobboard .recruiter-table-card .card-body {
    padding: 0 !important;
}
.recruiter-slot-bookings-jobboard .recruiter-table-card .table-responsive {
    border: 0 !important;
    border-radius: 0 !important;
}
.recruiter-slot-bookings-jobboard .recruiter-bookings-table {
    border: 0 !important;
    border-collapse: collapse !important;
    margin: 0 !important;
}
.recruiter-slot-bookings-jobboard .recruiter-bookings-table th,
.recruiter-slot-bookings-jobboard .recruiter-bookings-table td {
    border-left: 0 !important;
    border-right: 0 !important;
    border-top: 0 !important;
    border-bottom: 1px solid #D9ECE5 !important;
}
.recruiter-slot-bookings-jobboard .recruiter-bookings-table thead th {
    background: #FFFFFF !important;
}
.recruiter-slot-bookings-jobboard .recruiter-bookings-table tbody tr:last-child td {
    border-bottom: 0 !important;
}
body.dark .recruiter-slot-bookings-jobboard .recruiter-bookings-table th,
body.dark .recruiter-slot-bookings-jobboard .recruiter-bookings-table td {
    border-bottom-color: #23343A !important;
}
body.dark .recruiter-slot-bookings-jobboard .recruiter-table-card .card-header {
    background: #000000 !important;
    border: 0 !important;
    border-bottom: 1px solid #23343A !important;
}
body.dark .recruiter-slot-bookings-jobboard .recruiter-bookings-table {
    border: 0 !important;
}
body.dark .recruiter-slot-bookings-jobboard .recruiter-bookings-table thead th,
body.dark .recruiter-slot-bookings-jobboard .recruiter-bookings-table td {
    background: #000000 !important;
}
</style>
<div class="recruiter-slot-bookings-jobboard">
    <div class="container-fluid py-5">
        <div class="page-board-header page-board-header-tight recruiter-page-board-header">
            <div class="page-board-copy"> 
                <h1 class="page-board-title">Interview Bookings</h1>
                <p class="page-board-subtitle">Track confirmed interviews, manage reschedules, and complete finished booking flows.</p>
            </div>
            <div class="page-board-actions">
                <a href="<?= base_url('recruiter/slots') ?>" class="btn btn-outline-first">
                     Back to Slots
                </a>
            </div>
        </div>

        <div class="card shadow-sm recruiter-summary-card mb-4" style="border-radius: 20px !important;overflow: hidden;">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                        <div class="recruiter-summary-item">
                            <h6 class="m-0 font-weight-bold">Total Bookings</h6>
                           <?= number_format($stats['total_bookings']) ?> 
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                        <div class="recruiter-summary-item">
                            <h6 class="m-0 font-weight-bold">Upcoming</h6>
                            <?= number_format($stats['upcoming']) ?> 
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                        <div class="recruiter-summary-item">
                            <h6 class="m-0 font-weight-bold">Completed</h6>
                             <?= number_format($stats['completed']) ?> 
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="recruiter-summary-item">
                            <h6 class="m-0 font-weight-bold">Rescheduled</h6>
                          <?= number_format($stats['rescheduled']) ?> 
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm recruiter-filter-card" style="border-radius: 20px !important;overflow: hidden;">
            <div class="card-body">
                <div class="recruiter-booking-filter-head">
                    <h6 class="recruiter-booking-filter-title">Filters</h6>
                    
                </div>
                <form method="get" action="<?= base_url('recruiter/slots/bookings') ?>" class="recruiter-job-form">
                    <div class="recruiter-booking-filter-grid">
                        <div>
                            <label class="sr-only">Job</label>
                            <select name="job_id" class="form-control">
                                <option value="">All Jobs</option>
                                <?php foreach ($jobs as $job): ?>
                                    <option value="<?= $job['id'] ?>" <?= ($filters['job_id'] ?? '') == $job['id'] ? 'selected' : '' ?>>
                                        <?= esc($job['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="sr-only">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="confirmed" <?= ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="rescheduled" <?= ($filters['status'] ?? '') === 'rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                                <option value="no_show" <?= ($filters['status'] ?? '') === 'no_show' ? 'selected' : '' ?>>No Show</option>
                                <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="recruiter-booking-filter-actions">
                            <button type="submit" class="btn btn-outline-first">
                                Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm recruiter-table-card" style="border-radius: 20px !important;overflow: hidden;">
            <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="m-0 font-weight-bold">All Bookings</h6>
                
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover recruiter-bookings-table">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Candidate</th>
                                <th>Job</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Booked On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">No bookings found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <?php
                                    $isPast = strtotime($booking['slot_datetime']) < time();
                                    $isUpcoming = strtotime($booking['slot_datetime']) > time();
                                    $statusColors = [
                                        'booked' => 'primary',
                                        'confirmed' => 'success',
                                        'completed' => 'info',
                                        'rescheduled' => 'warning',
                                        'no_show' => 'danger',
                                        'cancelled' => 'danger'
                                    ];
                                    $color = $statusColors[$booking['booking_status']] ?? 'secondary';
                                    $hasReview = !empty($booking['review_id']);
                                    $statusLabels = [
                                        'booked' => 'Booked',
                                        'confirmed' => 'Confirmed',
                                        'completed' => 'Completed',
                                        'rescheduled' => 'Rescheduled',
                                        'no_show' => 'No Show',
                                        'cancelled' => 'Cancelled',
                                    ];
                                    ?>
                                    <tr class="<?= $isPast ? 'table-secondary' : '' ?>">
                                        <td><?= $booking['id'] ?></td>
                                        <td>
                                            <div class="recruiter-booking-person">
                                               <?= esc($booking['candidate_name']) ?> 
                                                <span><?= esc($booking['email']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= esc($booking['job_title']) ?></td>
                                        <td>
                                             <?= date('M d, Y', strtotime($booking['slot_date'])) ?> <br>
                                            <span><?= date('h:i A', strtotime($booking['slot_time'])) ?></span>
                                        </td>
                                        <td>
                                            <span class="status-pill">
                                                <?= esc($statusLabels[$booking['booking_status']] ?? ucwords(str_replace('_', ' ', $booking['booking_status']))) ?>
                                            </span>
                                            <?php if ($hasReview): ?>
                                                <div><small > Reviewed</small></div>
                                                <?php if (!empty($booking['review_decision'])): ?>
                                                    <div><small >Decision: <?= esc(ucwords(str_replace('_', ' ', (string) $booking['review_decision']))) ?></small></div>
                                                <?php endif; ?>
                                                <?php if (!empty($booking['review_notes'])): ?>
                                                    <?php $reviewPreview = mb_strlen((string) $booking['review_notes']) > 70 ? mb_substr((string) $booking['review_notes'], 0, 70) . '...' : (string) $booking['review_notes']; ?>
                                                    <div><small class="text-muted" title="<?= esc((string) $booking['review_notes']) ?>"><?= esc($reviewPreview) ?></small></div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($booking['reschedule_count'] > 0): ?>
                                                <div><small class="text-muted">Rescheduled: <?= $booking['reschedule_count'] ?>x</small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= date('M d, Y', strtotime($booking['booked_at'])) ?></small>
                                        </td>
                                        <td>
                                        <div class="job-actions-wrap recruiter-booking-actions">
                                                <?php if ($isUpcoming && in_array($booking['booking_status'], ['booked', 'confirmed', 'rescheduled'], true)): ?>
                                                    <a href="<?= base_url('recruiter/slots/reschedule/' . $booking['id']) ?>" class="btn btn-sm btn-warning btn-action" title="Reschedule">
                                                        <i class="fas fa-sync"></i> Reschedule
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($isPast || in_array($booking['booking_status'], ['completed', 'no_show', 'rescheduled'], true)): ?>
                                                    <a href="<?= base_url('recruiter/slots/review/' . $booking['id']) ?>" class="btn btn-sm btn-outline-first" title="<?= $hasReview ? 'Edit Review' : 'Review Interview' ?>">
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

                <?php if (isset($pager) && is_object($pager) && method_exists($pager, 'links') && $pager->getPageCount() > 1): ?>
                    <div class="mt-3">
                        <?= $pager->links('default', 'portal_full') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= view('Layouts/recruiter_footer') ?>
