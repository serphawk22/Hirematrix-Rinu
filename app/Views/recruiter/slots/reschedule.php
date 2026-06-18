<?= view('Layouts/recruiter_header', ['title' => 'Reschedule Booking']) ?>
<style>
.btn-primary,.btn-outline-primary {  
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
.page-board-header.page-board-header-tight.recruiter-page-board-header, body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border : none !important;
}
.hm-page-content,.recruiter-slot-reschedule-jobboard{
         background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
}
body.dark .hm-page-content,body.dark .recruiter-slot-reschedule-jobboard,body.dark .recruiter-form-card,body.dark .card-header,body.dark .recruiter-info-card,body.dark .recruiter-help-card,body.dark .recruiter-tip-item,body.dark .recruiter-alert{
    background: #000000 !important;
    border: 1px solid #23343A !important;
} 
.recruiter-info-card,.recruiter-info-label,.recruiter-form-card,h6.m-0.font-weight-bold.text-primary,.recruiter-help-list,.recruiter-tip-item{
     font-size: 1rem;
    font-weight: 500 !important;
    color: var(--foreground, #16212B);
}
body.dark .recruiter-info-card,body.dark .recruiter-info-label,body.dark .recruiter-form-card,body.dark h6.m-0.font-weight-bold.text-primary,body.dark .recruiter-help-list,body.dark .recruiter-tip-item,body.dark .recruiter-alert{
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
    background: #000000 !important;
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
body.dark .recruiter-tip-item{
     background: #000000 !important;
    color: #FFFFFF !important;
     border: 1px solid #23343A !important;
      font-weight: 400 !important;   
}
 .recruiter-tip-item{ 
      font-weight: 400 !important;   
}
body.dark .recruiter-job-form label, body.dark h6 {
    font-size: 1rem;        /* same as Bootstrap h6 */
    font-weight: 500 !important;   
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
    color:#FFFFFF !important;
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

.recruiter-slot-picker {
    display: grid;
    gap: 1rem;
}

.recruiter-slot-date-group {
    border: 1px solid #D9ECE5;
    border-radius: 18px;
    background: #FFFFFF;
    padding: 0.95rem 1rem 0.3rem;
}

.recruiter-slot-date-head {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 0.9rem;
    color: #1F3B73;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.3;
}

.recruiter-slot-date-head i {
    color: #1F3B73;
}

.recruiter-slot-radio {
    min-height: 100%;
    padding-left: 1.8rem;
}

.recruiter-slot-radio .custom-control-label {
    position: relative;
    width: 100%;
    margin-bottom: 0;
}

.recruiter-slot-radio .custom-control-label::before,
.recruiter-slot-radio .custom-control-label::after {
    top: 50%;
    transform: translateY(-50%);
}

.recruiter-slot-option {
    display: block;
    width: 100%;
    padding: 0.9rem 1rem;
    border: 1px solid #D9ECE5;
    border-radius: 14px;
    background: #FFFFFF;
    transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.recruiter-slot-option:hover {
    border-color: #1FB7B5;
    background: #F7FBFA;
}

.recruiter-slot-radio .custom-control-input:checked ~ .custom-control-label.recruiter-slot-option {
    border-color: #1FB7B5;
    background: #EFFAF8;
    box-shadow: 0 0 0 1px rgba(31, 183, 181, 0.08);
}

.recruiter-slot-radio .custom-control-input:focus ~ .custom-control-label.recruiter-slot-option {
    box-shadow: 0 0 0 2px rgba(31, 183, 181, 0.12);
}

.recruiter-slot-radio .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #1FB7B5;
    border-color: #1FB7B5;
}

.recruiter-slot-radio .badge-success {
    background: rgba(31, 183, 181, 0.12) !important;
    color: #0D8A90 !important;
    border: 1px solid rgba(31, 183, 181, 0.2);
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

body.dark .recruiter-slot-date-group { 
    background: #000000 !important; 
    border-color: #23343A !important;
}

body.dark .recruiter-slot-date-head,
body.dark .recruiter-slot-date-head i { 
    color: #FFFFFF !important;
}

body.dark .recruiter-slot-option {
    background: #000000 !important; 
    color: #F8FAFC !important;
}

body.dark .recruiter-slot-option {
    background: #161D21 !important; 
    border-color: #23343A !important;
}

body.dark .recruiter-slot-option:hover {
    background: #1A2328 !important;
    border-color: #1FB7B5 !important;
}

body.dark .recruiter-slot-radio .custom-control-input:checked ~ .custom-control-label.recruiter-slot-option { 
    background: #000000 !important; 
    border-color: #1FB7B5 !important;
    box-shadow: 0 0 0 1px rgba(31, 183, 181, 0.14);
}

body.dark .recruiter-slot-radio .custom-control-input:focus ~ .custom-control-label.recruiter-slot-option {
    box-shadow: 0 0 0 2px rgba(31, 183, 181, 0.18);
}

body.dark .recruiter-slot-radio .custom-control-label::before {
 
    background: #000000 !important; 
    border-color: #4A5C63 !important;
}

body.dark .recruiter-slot-radio .custom-control-input:checked ~ .custom-control-label::before { 
 
    background-color: #1FB7B5 !important; 
    border-color: #1FB7B5 !important;
}

body.dark .recruiter-slot-radio .badge-success {
    background: rgba(31, 183, 181, 0.14) !important;
    color: #57D3D1 !important;
    border-color: rgba(31, 183, 181, 0.24) !important;
}

body.dark .custom-control-label,
body.dark .custom-control-label strong, 
body.dark .custom-control-label .text-primary,body.dark span {
    color: #FFFFFF !important;
}
body.dark .custom-control-label .text-primary {
    color: #FFFFFF !important; 
}
</style>
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
            <div class="recruiter-form-card" style="border-radius: 20px !important;overflow: hidden;">
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

                <div class="card shadow-sm recruiter-form-card" style="border-radius: 20px !important;overflow: hidden;">
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
                                    <div class="alert alert-info recruiter-alert">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        No available slots found for this job position.
                                        <a href="<?= base_url('recruiter/slots/create') ?>" class="alert-link" style="color:#0D8A90;">Create new slots</a>
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
                <div class="card shadow-sm recruiter-help-card mb-4" style="border-radius: 20px !important;overflow: hidden;">
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

                <div class="card shadow-sm recruiter-help-card" style="border-radius: 20px !important;overflow: hidden;">
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
