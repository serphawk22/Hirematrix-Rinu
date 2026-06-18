<?= view('Layouts/recruiter_header', ['title' => 'Create Interview Slots']) ?>
<style>
 .container-fluid {
    max-width: 100% !important;
    padding-left: 34px !important;
    padding-right: 34px !important;
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
 .hm-page-content,.recruiter-slot-create-jobboard{
         background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
}
body.dark .hm-page-content,body.dark .recruiter-slot-create-jobboard, body.dark .recruiter-form-card,body.dark .checklist-item{
    background: #000000 !important;
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
body.dark .card,body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
 border: 1px solid #23343A !important;
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
body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border:none !important;
}
body.dark .recruiter-summary-card{
    background: #000000 !important;
    color:#FFFFFF !important;
    border:1px solid #23343A;
}
</style>
<div class="recruiter-slot-create-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">Create Interview Slots</h1>
            <p class="page-board-subtitle">Generate one or more booking windows for a job while keeping scheduling clear and organized.</p>
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

    <div class="recruiter-form-layout recruiter-slot-create-layout">
        <div class="recruiter-form-main">
            <div class="card shadow-sm recruiter-form-card" style="border-radius: 20px !important;overflow: hidden;">
                <div class="card-body">
                    <form method="post" action="<?= base_url('recruiter/slots/store') ?>" id="slotForm" class="recruiter-job-form">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="job_id">Select Job <span class="text-danger">*</span></label>
                            <select name="job_id" id="job_id" class="form-control recruiter-slot-job-select" required>
                                <option value="">-- Select Job --</option>
                                <?php foreach ($jobs as $job): ?>
                                    <option value="<?= $job['id'] ?>">
                                        <?= esc($job['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Select the job position for these interview slots</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                                    <small class="form-text text-muted">First date for slots</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">End Date (Optional)</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" min="<?= date('Y-m-d') ?>">
                                    <small class="form-text text-muted">Leave empty for single day</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Time Slots <span class="text-danger">*</span></label>
                            <div id="timeSlots">
                                <div class="input-group mb-2">
                                    <input type="time" name="times[]" class="form-control" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-primary" id="addTime">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">Add multiple time slots for each day</small>
                        </div>

                        <div class="form-group">
                            <label for="capacity">Capacity per Slot <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" id="capacity" class="form-control" min="1" max="50" value="1" required>
                            <small class="form-text text-muted">Number of candidates that can book each slot</small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="exclude_weekends" name="exclude_weekends" value="1" checked>
                                <label class="custom-control-label" for="exclude_weekends">Exclude Weekends (Saturday & Sunday)</label>
                            </div>
                        </div>

                        <div class="alert alert-info recruiter-summary-card">
                            <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Summary</h6>
                            <p class="mb-0" id="slotSummary">Please fill in the form to see the summary of slots to be created.</p>
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-save"></i> Create Slots
                            </button>
                            <a href="<?= base_url('recruiter/slots') ?>" class="btn btn-outline-primary btn-lg">
                              Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="recruiter-form-side">
            <div class="card shadow-sm recruiter-form-card" style="border-radius: 20px !important;overflow: hidden;">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-question-circle"></i> How it works</h6>
                    <ul class="recruiter-help-list mb-0">
                        <li style="color:#94A3B8;">Select a job position for the interview slots.</li>
                        <li style="color:#94A3B8;">Choose start date and optionally an end date for multiple days.</li>
                        <li style="color:#94A3B8;">Add one or more time slots for each day.</li>
                        <li style="color:#94A3B8;">Set capacity for how many candidates can book each slot.</li>
                        <li style="color:#94A3B8;">Optionally exclude weekends from the date range.</li>
                        <li style="color:#94A3B8;">The system will automatically create all combinations.</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm recruiter-form-card" style="border-radius: 20px !important;overflow: hidden;">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-lightbulb"></i> Scheduling tip</h6>
                    <p class="text-muted mb-0">Create fewer, higher-quality slots if you want easier coordination and lower no-show risk.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>

