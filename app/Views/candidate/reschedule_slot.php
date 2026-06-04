<?= view('Layouts/candidate_header', ['title' => 'Reschedule Interview']) ?>

<div class="reschedule-slot-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-sync-alt"></i> Interview reschedule</span>
                <h1 class="page-board-title">Reschedule Interview</h1>
                <p class="page-board-subtitle">Choose a new slot if you need to move your upcoming interview within the allowed limit.</p>
                <div class="company-profile-meta">
                    <span class="meta-chip"><strong><?= (int) ($can_reschedule_info['remaining_reschedules'] ?? 0) ?></strong> Remaining</span>
                    <span class="meta-chip"><strong><?= date('M j', strtotime($booking['slot_datetime'])) ?></strong> Current date</span>
                </div>
            </div>
            <div class="page-board-actions">
                <a href="<?= base_url('candidate/my-bookings') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> My Bookings
                </a>
            </div>
        </div>
    </div>

    <section class="site-section pt-0 content-wrap">
        <div class="container">
            <div class="col-lg-10 offset-lg-1 px-0">
                <div class="card mb-4 current-booking-card">
                    <div class="card-body">
                        <h5><i class="fas fa-calendar-check mr-2"></i>Current Interview</h5>
                        <p class="mb-2">
                            <strong>Date:</strong> <?= date('l, F j, Y', strtotime($booking['slot_datetime'])) ?><br>
                            <strong>Time:</strong> <?= date('h:i A', strtotime($booking['slot_datetime'])) ?>
                        </p>
                        <div class="alert alert-warning mb-0 booking-alert">
                            <strong>Reschedules Remaining:</strong>
                            <?= $can_reschedule_info['remaining_reschedules'] ?> out of <?= $booking['max_reschedules'] ?>
                        </div>
                    </div>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger">
                        <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($available_slots)): ?>
                    <div class="alert alert-warning text-center">
                        <h5>No Alternative Slots Available</h5>
                        <p>Please contact HR for assistance.</p>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= base_url('candidate/process-reschedule') ?>" class="slot-form-card">
                        <?= csrf_field() ?>
                        <input type="hidden" name="application_id" value="<?= $application['id'] ?>">

                        <div class="slot-selection">
                            <?php foreach ($available_slots as $date => $slots): ?>
                                <div class="card mb-4 slot-day-card">
                                    <div class="card-header slot-day-head danger">
                                        <h5 class="mb-0">
                                            <i class="fas fa-calendar-day mr-2"></i>
                                            <?= date('l, F j, Y', strtotime($date)) ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php foreach ($slots as $slot): ?>
                                                <?php
                                                // Skip if this is the current slot
                                                if ($slot['id'] == $booking['slot_id']) continue;
                                                ?>
                                                <div class="col-md-4 mb-3">
                                                    <input type="radio" name="slot_id" id="slot_<?= $slot['id'] ?>"
                                                           value="<?= $slot['id'] ?>" class="slot-radio" required>
                                                    <label for="slot_<?= $slot['id'] ?>" class="slot-label">
                                                        <span class="slot-time"><i class="fas fa-clock mr-2"></i><?= date('h:i A', strtotime($slot['slot_time'])) ?></span>
                                                        <small class="slot-spots">
                                                            <?= (int) $slot['capacity'] - (int) $slot['booked_count'] ?> spot(s) left
                                                        </small>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-group">
                            <label>Reason for Rescheduling (Optional)</label>
                            <textarea class="form-control" name="reason" rows="3"
                                      placeholder="Please provide a brief reason..."></textarea>
                        </div>

                        <div class="alert alert-danger booking-alert">
                            <strong><i class="fas fa-exclamation-triangle mr-1"></i>Warning:</strong>
                            <ul class="mb-0">
                                <li>You have <strong><?= $can_reschedule_info['remaining_reschedules'] ?></strong> reschedule(s) remaining</li>
                                <li>After reaching the limit, you won't be able to reschedule again</li>
                                <li>Cancellation is <strong>NOT allowed</strong></li>
                            </ul>
                        </div>

                        <div class="text-center mt-4">
                            <a href="<?= base_url('candidate/my-bookings') ?>" class="btn btn-secondary mr-2">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync mr-1"></i> Confirm Reschedule
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if (!empty($history)): ?>
                    <div class="card mt-4 reschedule-history-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Reschedule History</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline-list">
                                <?php foreach ($history as $item): ?>
                                    <div class="timeline-item-row">
                                        <div class="timeline-badge"><i class="fas fa-sync"></i></div>
                                        <div class="timeline-content flex-grow-1">
                                            <p class="mb-1">
                                                <strong>From:</strong> <?= date('M j, Y h:i A', strtotime($item['old_slot_datetime'])) ?><br>
                                                <strong>To:</strong> <?= date('M j, Y h:i A', strtotime($item['new_slot_datetime'])) ?>
                                            </p>
                                            <?php if ($item['reason']): ?>
                                                <p class="text-muted mb-1"><em>"<?= esc($item['reason']) ?>"</em></p>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <?= date('M j, Y h:i A', strtotime($item['rescheduled_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>
