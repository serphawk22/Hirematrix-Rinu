<?= view('Layouts/recruiter_header', ['title' => 'Create Interview Slots']) ?>

<div class="recruiter-slot-create-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-calendar-plus"></i> Recruiter scheduling</span>
            <h1 class="page-board-title">Create Interview Slots</h1>
            <p class="page-board-subtitle">Generate one or more booking windows for a job while keeping scheduling clear and organized.</p>
        </div>
        <div class="page-board-actions">
            <a href="<?= base_url('recruiter/slots') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Slots
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
            <div class="card shadow-sm recruiter-form-card">
                <div class="card-body">
                    <form method="post" action="<?= base_url('recruiter/slots/store') ?>" id="slotForm" class="recruiter-slot-form">
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
                                        <button type="button" class="btn btn-success" id="addTime">
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
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Create Slots
                            </button>
                            <a href="<?= base_url('recruiter/slots') ?>" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="recruiter-form-side">
            <div class="card shadow-sm recruiter-form-card">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-question-circle"></i> How it works</h6>
                    <ul class="recruiter-help-list mb-0">
                        <li>Select a job position for the interview slots.</li>
                        <li>Choose start date and optionally an end date for multiple days.</li>
                        <li>Add one or more time slots for each day.</li>
                        <li>Set capacity for how many candidates can book each slot.</li>
                        <li>Optionally exclude weekends from the date range.</li>
                        <li>The system will automatically create all combinations.</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm recruiter-form-card">
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

