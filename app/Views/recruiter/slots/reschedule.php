<?= view('Layouts/recruiter_header', ['title' => 'Reschedule Booking']) ?>
<div class="recruiter-slot-reschedule-jobboard">
    <div class="container-fluid py-5">
        <div class="page-board-header page-board-header-tight recruiter-page-board-header">
            <div class="page-board-copy"> 
                <h1 class="page-board-title">Reschedule Interview</h1>
                <p class="page-board-subtitle">Choose a new slot, add a clear reason, and notify the candidate automatically.</p>
                <div class="company-profile-meta">
                    <span class="status-pill"> #<?= $booking['id'] ?> Booking ID</span>
                    <span class="status-pill"> <?= esc($booking['candidate_name'] ?? 'N/A') ?> Candidate</span>
                    <span class="status-pill"> <?= esc($booking['job_title'] ?? 'N/A') ?>  Role</span>
                </div>
            </div>
            <div class="page-board-actions">
                <a href="<?= base_url('recruiter/slots/bookings') ?>" class="btn btn-outline-primary">
                    Back to Bookings
                </a>
            </div>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger recruiter-alert" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="recruiter-form-layout recruiter-slot-reschedule-layout">
            <div class="recruiter-form-stack">
                <div class="card shadow-sm mb-4 recruiter-info-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Current Booking Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="recruiter-info-grid">
                            <div class="recruiter-info-item">
                                <span class="recruiter-info-label">Booking ID</span>
                                #<?= $booking['id'] ?> 
                            </div>
                            <div class="recruiter-info-item">
                                <span class="recruiter-info-label">Candidate</span>
                                <?= esc($booking['candidate_name'] ?? 'N/A') ?> 
                                <span><?= esc($booking['email'] ?? 'N/A') ?></span>
                            </div>
                            <div class="recruiter-info-item">
                                <span class="recruiter-info-label">Job Position</span>
                                <?= esc($booking['job_title'] ?? 'N/A') ?> 
                            </div>
                            <div class="recruiter-info-item">
                                <span class="recruiter-info-label">Current Schedule</span>
                                <div class="recruiter-current-schedule">
                                    <?= date('l, M d, Y', strtotime($booking['slot_datetime'])) ?> 
                                    <span><?= date('h:i A', strtotime($booking['slot_datetime'])) ?></span>
                                </div>
                            </div>
                            <div class="recruiter-info-item">
                                <span class="recruiter-info-label">Status</span>
                                <div>
                                    <span class="status-pill">
                                        <?= ucfirst($booking['booking_status']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php if (isset($booking['reschedule_count']) && $booking['reschedule_count'] > 0): ?>
                                <div class="recruiter-info-item">
                                    <span class="recruiter-info-label">Reschedule History</span>
                                    <span>Rescheduled <?= $booking['reschedule_count'] ?> time(s)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm recruiter-form-card recruiter-rounded-hidden">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Select New Slot</h6>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?= base_url('recruiter/slots/process-reschedule') ?>" id="rescheduleForm" class="recruiter-job-form">
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
                                    <div class="alert alert-info recruiter-alert recruiter-inline-empty" data-hm-alert-inline="1" role="status">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <div>
                                            <strong>No available slots found for this job position.</strong>
                                            <p>Create a new slot, then return here to finish rescheduling this interview.</p>
                                            <a href="<?= base_url('recruiter/slots/create') ?>" class="alert-link" class="recruiter-brand-link">Create new slots</a>
                                        </div>
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
                                    <button type="submit" class="btn btn-outline-primary btn-lg">
                                         Reschedule Interview
                                    </button>
                                <?php endif; ?>
                                <a href="<?= base_url('recruiter/slots/bookings') ?>" class="btn btn-outline-primary btn-lg">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="recruiter-side-rail">
                <div class="card shadow-sm recruiter-help-card mb-4 recruiter-rounded-hidden">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Important</h6>
                    </div>
                    <div class="card-body"> 
                    <div class="recruiter-tip-stack">
                            <div class="recruiter-tip-item">The candidate will be notified automatically.</div>
                            <div class="recruiter-tip-item">This action cannot be undone.</div>
                            <div class="recruiter-tip-item">Provide a clear reason for rescheduling.</div>
                            <div class="recruiter-tip-item">The old slot will be released for others.</div> 
                            </div>
                    </div>
                </div>

                <div class="card shadow-sm recruiter-help-card recruiter-rounded-hidden">
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
