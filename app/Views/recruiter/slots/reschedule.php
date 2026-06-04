<?= view('Layouts/recruiter_header', ['title' => 'Reschedule Booking']) ?>

<div class="recruiter-slot-reschedule-jobboard">
    <div class="container-fluid py-5">
        <div class="page-board-header page-board-header-tight recruiter-page-board-header">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-sync-alt"></i> Recruiter scheduling</span>
                <h1 class="page-board-title">Reschedule Interview</h1>
                <p class="page-board-subtitle">Choose a new slot, add a clear reason, and notify the candidate automatically.</p>
                <div class="company-profile-meta">
                    <span class="meta-chip"><strong>#<?= $booking['id'] ?></strong> Booking ID</span>
                    <span class="meta-chip"><strong><?= esc($booking['candidate_name'] ?? 'N/A') ?></strong> Candidate</span>
                    <span class="meta-chip"><strong><?= esc($booking['job_title'] ?? 'N/A') ?></strong> Role</span>
                </div>
            </div>
            <div class="page-board-actions">
                <a href="<?= base_url('recruiter/slots/bookings') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Bookings
                </a>
            </div>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger recruiter-alert" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="recruiter-form-layout recruiter-slot-reschedule-layout">
            <div class="recruiter-form-card">
                <div class="card shadow-sm mb-4 recruiter-info-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Current Booking Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="recruiter-info-grid">
                            <div class="recruiter-info-item">
                                <span class="recruiter-info-label">Booking ID</span>
                                <strong>#<?= $booking['id'] ?></strong>
                            </div>
                            <div class="recruiter-info-item">
                                <span class="recruiter-info-label">Candidate</span>
                                <strong><?= esc($booking['candidate_name'] ?? 'N/A') ?></strong>
                                <span><?= esc($booking['email'] ?? 'N/A') ?></span>
                            </div>
                            <div class="recruiter-info-item">
                                <span class="recruiter-info-label">Job Position</span>
                                <strong><?= esc($booking['job_title'] ?? 'N/A') ?></strong>
                            </div>
                            <div class="recruiter-info-item">
                                <span class="recruiter-info-label">Current Schedule</span>
                                <div class="recruiter-current-schedule">
                                    <strong><?= date('l, M d, Y', strtotime($booking['slot_datetime'])) ?></strong>
                                    <span><?= date('h:i A', strtotime($booking['slot_datetime'])) ?></span>
                                </div>
                            </div>
                            <div class="recruiter-info-item">
                                <span class="recruiter-info-label">Status</span>
                                <div>
                                    <span class="badge badge-<?= $booking['booking_status'] === 'confirmed' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($booking['booking_status']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php if (isset($booking['reschedule_count']) && $booking['reschedule_count'] > 0): ?>
                                <div class="recruiter-info-item">
                                    <span class="recruiter-info-label">Reschedule History</span>
                                    <strong class="text-warning">Rescheduled <?= $booking['reschedule_count'] ?> time(s)</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm recruiter-form-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Select New Slot</h6>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?= base_url('recruiter/slots/process-reschedule') ?>" id="rescheduleForm" class="recruiter-reschedule-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

                            <div class="form-group">
                                <label for="reason">Reason for Rescheduling <span class="text-danger">*</span></label>
                                <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Please provide a reason for rescheduling this interview..." required></textarea>
                                <small class="form-text text-muted">This reason will be shared with the candidate.</small>
                            </div>

                            <div class="form-group">
                                <label>Available Slots <span class="text-danger">*</span></label>
                                <?php if (empty($available_slots)): ?>
                                    <div class="alert alert-warning recruiter-alert">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        No available slots found for this job position.
                                        <a href="<?= base_url('recruiter/slots/create') ?>" class="alert-link">Create new slots</a>
                                    </div>
                                <?php else: ?>
                                    <div class="recruiter-slot-picker">
                                        <?php foreach ($available_slots as $date => $slots): ?>
                                            <div class="recruiter-slot-date-group">
                                                <div class="recruiter-slot-date-head">
                                                    <i class="fas fa-calendar"></i>
                                                    <span><?= date('l, F d, Y', strtotime($date)) ?></span>
                                                </div>
                                                <div class="row">
                                                    <?php foreach ($slots as $slot): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <div class="custom-control custom-radio recruiter-slot-radio">
                                                                <input type="radio"
                                                                       id="slot_<?= $slot['id'] ?>"
                                                                       name="slot_id"
                                                                       value="<?= $slot['id'] ?>"
                                                                       class="custom-control-input"
                                                                       required>
                                                                <label class="custom-control-label recruiter-slot-option" for="slot_<?= $slot['id'] ?>">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <div>
                                                                            <strong class="text-primary"><?= date('h:i A', strtotime($slot['slot_time'])) ?></strong>
                                                                        </div>
                                                                        <div>
                                                                            <span class="badge badge-success">
                                                                                <?= $slot['capacity'] - $slot['booked_count'] ?> available
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="confirm_reschedule" required>
                                    <label class="custom-control-label" for="confirm_reschedule">
                                        I confirm that I want to reschedule this interview and notify the candidate
                                    </label>
                                </div>
                            </div>

                            <div class="form-group mb-0 recruiter-form-actions">
                                <?php if (!empty($available_slots)): ?>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sync-alt"></i> Reschedule Interview
                                    </button>
                                <?php endif; ?>
                                <a href="<?= base_url('recruiter/slots/bookings') ?>" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="recruiter-side-rail">
                <div class="card shadow-sm recruiter-help-card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-exclamation-triangle"></i> Important</h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0 small recruiter-help-list">
                            <li>The candidate will be notified automatically.</li>
                            <li>This action cannot be undone.</li>
                            <li>Provide a clear reason for rescheduling.</li>
                            <li>The old slot will be released for others.</li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm recruiter-help-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Quick Notes</h6>
                    </div>
                    <div class="card-body">
                        <div class="recruiter-tip-stack">
                            <div class="recruiter-tip-item">Choose a slot with enough remaining capacity.</div>
                            <div class="recruiter-tip-item">Keep the reason concise and professional.</div>
                            <div class="recruiter-tip-item">Confirm before submitting to avoid duplicate notices.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= view('Layouts/recruiter_footer') ?>
