<?= view('Layouts/recruiter_header', ['title' => 'Edit Interview Slot']) ?>
<style>
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
 .hm-page-content,.recruiter-slot-edit-jobboard {
         background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
}

body.dark .hm-page-content,body.dark .recruiter-slot-edit-jobboard, body.dark .recruiter-form-card,body.dark .recruiter-tip-item,body.dark .recruiter-info-card{
    background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important; 
    border: 1px solid #23343A; !important;
} 
.page-board-header.page-board-header-tight.recruiter-page-board-header,body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border:none !important;
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
.recruiter-info-card{ 
    box-shadow: 0 0.1px 1px rgba(0, 0, 0, 0.08) !important;
}
body.dark .card,body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
 border: 1px solid #23343A !important;
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
    color:#94A3B8;;
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
body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border:none !important;
}
body.dark .recruiter-summary-card{
    background: linear-gradient(
      135deg,
      #162327 0%,
      #1B2A2F 100%
    );
    color:#94A3B8;
    border:1px solid #23343A;
}
body.dark .recruiter-info-card,body.dark .recruiter-tip-item{
    color:#94A3B8;
}
</style>
<div class="recruiter-slot-edit-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">Edit Interview Slot</h1>
            <p class="page-board-subtitle">Update the timing and capacity of an interview slot while keeping bookings safe.</p>
        </div>
        <div class="page-board-actions">
            <a href="<?= base_url('recruiter/slots') ?>" class="btn btn-outline-primary">
                Back to Slots
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show recruiter-alert" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($slot['booked_count'] > 0): ?>
        <div class="alert alert-warning recruiter-alert">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Warning:</strong> This slot has <?= $slot['booked_count'] ?> booking(s).
            Slots with bookings cannot be edited.
        </div>
    <?php endif; ?>

    <div class="recruiter-form-layout recruiter-slot-edit-layout">
        <div class="recruiter-form-main">
            <div class="card shadow-sm recruiter-form-card">
                <div class="card-body">
                    <form method="post" action="<?= base_url('recruiter/slots/update/' . $slot['id']) ?>" id="editSlotForm" class="recruiter-job-form">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="job_id">Job Position</label>
                            <select name="job_id" id="job_id" class="form-control" disabled>
                                <?php foreach ($jobs as $job): ?>
                                    <option value="<?= $job['id'] ?>" <?= $slot['job_id'] == $job['id'] ? 'selected' : '' ?>>
                                        <?= esc($job['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Job position cannot be changed</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slot_date">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="slot_date" id="slot_date" class="form-control" value="<?= esc($slot['slot_date']) ?>" min="<?= date('Y-m-d') ?>" <?= $slot['booked_count'] > 0 ? 'readonly' : '' ?> required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slot_time">Time <span class="text-danger">*</span></label>
                                    <input type="time" name="slot_time" id="slot_time" class="form-control" value="<?= esc($slot['slot_time']) ?>" <?= $slot['booked_count'] > 0 ? 'readonly' : '' ?> required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="capacity">Capacity <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" id="capacity" class="form-control" value="<?= esc($slot['capacity']) ?>" min="<?= $slot['booked_count'] ?>" max="50" <?= $slot['booked_count'] > 0 ? 'readonly' : '' ?> required>
                            <?php if ($slot['booked_count'] > 0): ?>
                                <small class="form-text text-muted">Cannot reduce capacity below current bookings (<?= $slot['booked_count'] ?>)</small>
                            <?php else: ?>
                                <small class="form-text text-muted">Number of candidates that can book this slot</small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Current Status</label>
                            <div class="card   recruiter-info-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                          Booked: 
                                            <span  >
                                                <?= $slot['booked_count'] ?> / <?= $slot['capacity'] ?>
                                            </span>
                                        </div>
                                        <div class="col-md-6">
                                             Available: 
                                            <?php if ($slot['is_available'] && strtotime($slot['slot_datetime']) > time()): ?>
                                                <span  >Yes</span>
                                            <?php else: ?>
                                                <span  >No</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Additional Information</label>
                            <div class="card bg-light recruiter-info-card">
                                <div class="card-body">
                                    
                                         Slot ID:  #<?= $slot['id'] ?><br>
                                        Created: <?= date('M d, Y h:i A', strtotime($slot['created_at'])) ?><br>
                                        <?php if (isset($slot['updated_at'])): ?>
                                           Last Updated:  <?= date('M d, Y h:i A', strtotime($slot['updated_at'])) ?>
                                        <?php endif; ?> 
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <?php if ($slot['booked_count'] == 0): ?>
                                <button type="submit" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-save"></i> Update Slot
                                </button>
                            <?php endif; ?>
                            <a href="<?= base_url('recruiter/slots') ?>" class="btn btn-outline-primary btn-lg">
                             Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="recruiter-form-side">
            <div class="card shadow-sm recruiter-form-card">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-shield-alt"></i> Booking protection</h6>
                    <div class="recruiter-tip-list">
                        <div class="recruiter-tip-item">Booked slots stay locked to protect candidate appointments.</div>
                        <div class="recruiter-tip-item">Adjust date and time only when there are no bookings.</div>
                        <div class="recruiter-tip-item">Capacity can’t be lowered below the current booking count.</div>
                    </div>
                </div>
            </div>

            <?php if ($slot['booked_count'] > 0): ?>
                <div class="card shadow-sm recruiter-form-card">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-users"></i> Existing bookings</h6>
                        <p class="text-muted mb-3">This slot has <?= $slot['booked_count'] ?> active booking(s). To modify it, candidates must be rescheduled or cancelled first.</p>
                        <a href="<?= base_url('recruiter/slots/bookings?slot_id=' . $slot['id']) ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i> View Bookings
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>
