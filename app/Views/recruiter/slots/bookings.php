<?= view('Layouts/recruiter_header', ['title' => 'Interview Bookings']) ?>

<div class="recruiter-slot-bookings-jobboard">
    <div class="container-fluid py-5">
        <div class="page-board-header page-board-header-tight recruiter-page-board-header">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-calendar-check"></i> Recruiter scheduling</span>
                <h1 class="page-board-title">Interview Bookings</h1>
                <p class="page-board-subtitle">Track confirmed interviews, manage reschedules, and complete finished booking flows.</p>
            </div>
            <div class="page-board-actions">
                <a href="<?= base_url('recruiter/slots') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-calendar-alt"></i> Back to Slots
                </a>
            </div>
        </div>

        <div class="card shadow-sm recruiter-summary-card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                        <div class="recruiter-summary-item">
                            <span class="recruiter-summary-label">Total Bookings</span>
                            <strong><?= number_format($stats['total_bookings']) ?></strong>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                        <div class="recruiter-summary-item">
                            <span class="recruiter-summary-label">Upcoming</span>
                            <strong><?= number_format($stats['upcoming']) ?></strong>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                        <div class="recruiter-summary-item">
                            <span class="recruiter-summary-label">Completed</span>
                            <strong><?= number_format($stats['completed']) ?></strong>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="recruiter-summary-item">
                            <span class="recruiter-summary-label">Rescheduled</span>
                            <strong><?= number_format($stats['rescheduled']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm recruiter-filter-card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h6 class="m-0 font-weight-bold text-dark">Filters</h6>
                        <p class="text-muted mb-0">Narrow bookings by job and status.</p>
                    </div>
                </div>
                <form method="get" action="<?= base_url('recruiter/slots/bookings') ?>" class="recruiter-booking-filter-form">
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
                            <div class="form-group w-100 mb-0">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm recruiter-table-card">
            <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="m-0 font-weight-bold text-primary">All Bookings</h6>
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
                                                <strong><?= esc($booking['candidate_name']) ?></strong>
                                                <span><?= esc($booking['email']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= esc($booking['job_title']) ?></td>
                                        <td>
                                            <strong><?= date('M d, Y', strtotime($booking['slot_date'])) ?></strong><br>
                                            <span class="text-primary"><?= date('h:i A', strtotime($booking['slot_time'])) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $color ?>">
                                                <?= esc($statusLabels[$booking['booking_status']] ?? ucwords(str_replace('_', ' ', $booking['booking_status']))) ?>
                                            </span>
                                            <?php if ($hasReview): ?>
                                                <div><small class="text-success"><i class="fas fa-check-circle"></i> Reviewed</small></div>
                                                <?php if (!empty($booking['review_decision'])): ?>
                                                    <div><small class="text-muted">Decision: <?= esc(ucwords(str_replace('_', ' ', (string) $booking['review_decision']))) ?></small></div>
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
                                                    <a href="<?= base_url('recruiter/slots/review/' . $booking['id']) ?>" class="btn btn-sm btn-primary btn-action" title="<?= $hasReview ? 'Edit Review' : 'Review Interview' ?>">
                                                        <i class="fas fa-clipboard-check"></i> <?= $hasReview ? 'Edit Review' : 'Review Interview' ?>
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
