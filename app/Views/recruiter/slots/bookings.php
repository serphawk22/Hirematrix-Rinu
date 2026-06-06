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
    background: #7a8b9650;
    color: #0D8A90;
}
.page-board-header.page-board-header-tight.recruiter-page-board-header,body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border: none !important;
}
     
body.dark .m-0.font-weight-bold{
    color:#F8FCFB !important;
}
.hm-page-content,.recruiter-slot-bookings-jobboard{
         background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
}
body.dark .hm-page-content,body.dark .recruiter-slot-bookings-jobboard, body.dark .recruiter-summary-card, body.dark .recruiter-filter-card,body.dark .recruiter-table-card,body.dark .card-header,body.dark .table.table-bordered.table-hover.recruiter-bookings-table{
    background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important; 
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
   background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important; 
    color:#94A3B8 !important;
    border-color: #23343A !important;
}

body.dark .table-secondary td,
body.dark .table-secondary th,
body.dark .table-secondary {
   background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important; 
}

body.dark thead th {
   background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important; 
    color: #94A3B8 !important;
}
 body.dark .recruiter-summary-card{
   color:#94A3B8 !important;
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
    background: #1B2A2F !important;
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
 
body.dark .recruiter-job-form label, body.dark h6 {
    font-size: 1rem;        /* same as Bootstrap h6 */
    font-weight: 500 !important;   
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
    color:#94A3B8 !important;
}
body.dark .m-0.font-weight-bold,body.dark .recruiter-summary-item{
     color:#94A3B8 !important;
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

        <div class="card shadow-sm recruiter-summary-card mb-4">
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

        <div class="card shadow-sm recruiter-filter-card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h6 class="m-0 font-weight-bold">Filters</h6>
                        <p class="text-muted mb-0">Narrow bookings by job and status.</p>
                    </div>
                </div>
                <form method="get" action="<?= base_url('recruiter/slots/bookings') ?>" class="recruiter-job-form">
                    <div class="row">
                        <div class="col-lg-5 col-md-6">
                            <div class="form-group">
                                <label>Job</label>
                                <select name="job_id" class="form-control">
                                    <option value="">All Jobs</option>
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?= $job['id'] ?>" <?= ($filters['job_id'] ?? '') == $job['id'] ? 'selected' : '' ?>>
                                            <?= esc($job['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="confirmed" <?= ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="rescheduled" <?= ($filters['status'] ?? '') === 'rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                                    <option value="no_show" <?= ($filters['status'] ?? '') === 'no_show' ? 'selected' : '' ?>>No Show</option>
                                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-12 d-flex align-items-end">
                            <div class="form-group w-100">
                                <button type="submit" class="btn btn-outline-first btn-block">
                                 Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm recruiter-table-card">
            <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="m-0 font-weight-bold">All Bookings</h6>
                <span class="text-muted">Manage interview actions from one place</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover recruiter-bookings-table">
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
                                                <?php if ($isUpcoming && in_array($booking['booking_status'], ['confirmed', 'rescheduled'], true)): ?>
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

                <?php if (isset($pager) && is_object($pager) && method_exists($pager, 'links')): ?>
                    <div class="mt-3">
                        <?= $pager->links() ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= view('Layouts/recruiter_footer') ?>
