<?= view('Layouts/recruiter_header') ?>

<div class="recruiter-slot-bookings-jobboard recruiter-slot-review-jobboard">
    <div class="container-fluid py-5">
        <div class="page-board-header page-board-header-tight recruiter-page-board-header">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-clipboard-check"></i> Recruiter scheduling</span>
                <h1 class="page-board-title">Review Interview</h1>
                <p class="page-board-subtitle">Mark attendance, capture notes, and update the hiring decision in one place.</p>
            </div>
            <div class="page-board-actions">
                <a href="<?= base_url('recruiter/slots/bookings') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Bookings
                </a>
            </div>
        </div>

        <div class="card shadow-sm recruiter-review-summary-card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="recruiter-summary-item">
                            <span class="recruiter-summary-label">Candidate</span>
                            <strong><?= esc($booking['candidate_name'] ?? '-') ?></strong>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="recruiter-summary-item">
                            <span class="recruiter-summary-label">Job</span>
                            <strong><?= esc($booking['job_title'] ?? '-') ?></strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="recruiter-summary-item">
                            <span class="recruiter-summary-label">Slot</span>
                            <strong><?= !empty($booking['slot_datetime']) ? date('M d, Y h:i A', strtotime($booking['slot_datetime'])) : '-' ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm recruiter-review-card h-100">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h6 class="m-0 font-weight-bold text-primary">Interview Review</h6>
                        <?php
                            $status = (string) ($booking['booking_status'] ?? 'booked');
                            $badgeMap = [
                                'booked' => 'primary',
                                'confirmed' => 'success',
                                'rescheduled' => 'warning',
                                'completed' => 'info',
                                'no_show' => 'danger',
                                'cancelled' => 'secondary',
                            ];
                            $statusBadge = $badgeMap[$status] ?? 'secondary';
                        ?>
                        <span class="badge badge-<?= $statusBadge ?>"><?= esc(ucwords(str_replace('_', ' ', $status))) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($review)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                A review already exists for this booking. You can update it below.
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= base_url('recruiter/slots/review/' . (int) $booking['id']) ?>">
                            <?= csrf_field() ?>

                            <div class="form-group">
                                <label for="attendance_status">Attendance</label>
                                <select id="attendance_status" name="attendance_status" class="form-control">
                                    <option value="attended" <?= ($review['attendance_status'] ?? 'attended') === 'attended' ? 'selected' : '' ?>>Attended</option>
                                    <option value="late" <?= ($review['attendance_status'] ?? '') === 'late' ? 'selected' : '' ?>>Late but attended</option>
                                    <option value="no_show" <?= ($review['attendance_status'] ?? '') === 'no_show' ? 'selected' : '' ?>>No Show</option>
                                </select>
                                <small class="form-text text-muted">Attendance is stored separately from the final hiring decision.</small>
                            </div>

                            <div class="form-group">
                                <label for="decision">Recruiter Decision</label>
                                <select id="decision" name="decision" class="form-control">
                                    <option value="shortlisted" <?= ($review['decision'] ?? '') === 'shortlisted' ? 'selected' : '' ?>>Shortlist for next step</option>
                                    <option value="hold" <?= ($review['decision'] ?? '') === 'hold' ? 'selected' : '' ?>>Hold / Revisit Later</option>
                                    <option value="selected" <?= ($review['decision'] ?? '') === 'selected' ? 'selected' : '' ?>>Select / Offer</option>
                                    <option value="rejected" <?= ($review['decision'] ?? '') === 'rejected' ? 'selected' : '' ?>>Reject</option>
                                </select>
                                <small class="form-text text-muted">If the candidate did not attend, the application will be marked rejected automatically.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="strengths">Strengths</label>
                                        <textarea id="strengths" name="strengths" class="form-control" rows="4" placeholder="What did the candidate do well?"><?= esc($review['strengths'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="concerns">Concerns</label>
                                        <textarea id="concerns" name="concerns" class="form-control" rows="4" placeholder="Any concerns or gaps to note?"><?= esc($review['concerns'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="notes">Recruiter Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="5" placeholder="Write the interview summary and next steps..."><?= esc($review['notes'] ?? '') ?></textarea>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Review
                                </button>
                                <a href="<?= base_url('recruiter/slots/bookings') ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm recruiter-review-card h-100">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">What happens next</h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0 pl-3 text-muted">
                            <li>Attendance and recruiter notes are stored together for this booking.</li>
                            <li>The application stage updates automatically after you save.</li>
                            <li>Candidate-facing pages only see the final result, not your internal notes.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= view('Layouts/recruiter_footer') ?>
