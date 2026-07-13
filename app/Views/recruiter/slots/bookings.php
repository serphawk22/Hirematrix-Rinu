<?= view('Layouts/recruiter_header', ['title' => 'Interview Bookings']) ?>
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

        <div class="card shadow-sm recruiter-table-card recruiter-rounded-visible">
            <div class="slots-toolbar">
                <div class="slots-stats">
                    <div class="slots-stat">
                        <span class="slots-stat-value recruiter-brand-icon"><?= number_format($stats['total_bookings']) ?></span>
                        <span class="slots-stat-label">Total Bookings</span>
                    </div>
                    <div class="slots-stat-divider"></div>
                    <div class="slots-stat">
                        <span class="slots-stat-value recruiter-indigo-text"><?= number_format($stats['upcoming']) ?></span>
                        <span class="slots-stat-label">Upcoming</span>
                    </div>
                    <div class="slots-stat-divider"></div>
                    <div class="slots-stat">
                        <span class="slots-stat-value recruiter-brand-icon"><?= number_format($stats['completed']) ?></span>
                        <span class="slots-stat-label">Completed</span>
                    </div>
                    <div class="slots-stat-divider"></div>
                    <div class="slots-stat">
                        <span class="slots-stat-value recruiter-amber-text"><?= number_format($stats['rescheduled']) ?></span>
                        <span class="slots-stat-label">Rescheduled</span>
                    </div>
                </div>

                <form method="get" action="<?= base_url('recruiter/slots/bookings') ?>" class="slots-filters">
                    <select name="job_id" class="slots-filter-input">
                        <option value="">All Jobs</option>
                        <?php foreach ($jobs as $job): ?>
                            <option value="<?= $job['id'] ?>" <?= ($filters['job_id'] ?? '') == $job['id'] ? 'selected' : '' ?>>
                                <?= esc($job['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="slots-filter-input">
                        <option value="">All Status</option>
                        <option value="confirmed" <?= ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="rescheduled" <?= ($filters['status'] ?? '') === 'rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                        <option value="no_show" <?= ($filters['status'] ?? '') === 'no_show' ? 'selected' : '' ?>>No Show</option>
                        <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="slots-filter-btn"><i class="fas fa-search"></i></button>
                    <?php if (!empty($filters['job_id']) || !empty($filters['status'])): ?>
                        <a href="<?= base_url('recruiter/slots/bookings') ?>" class="slots-filter-clear">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                    <table class="table table-hover recruiter-bookings-table mb-0">
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
                    <div class="px-3 py-2">
                        <?= $pager->links('default', 'portal_full') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= view('Layouts/recruiter_footer') ?>
