<?= view('Layouts/recruiter_header', [
    'title' => 'Job Detail Pipeline',
    'pageStyles' => [base_url('jobboard/css/recruiter-pipeline.css?v=' . @filemtime(FCPATH . 'jobboard/css/recruiter-pipeline.css'))],
]) ?>

<?php
$statusTones = [
    'applied' => 'neutral',
    'shortlisted' => 'success',
    'interview_scheduled' => 'info',
    'interviewed' => 'violet',
    'offered' => 'warning',
    'hired' => 'success',
    'rejected' => 'danger',
    'withdrawn' => 'muted',
    'on_hold' => 'warning',
    'filtered_out' => 'muted',
];
$allApplicationsLabel = (int) ($totalApplicationsCount ?? 0);
$avgMatch = (int) ($scoreboard['average_ats_score'] ?? 0);
$openings = (int) ($job['openings'] ?? 0);
$safeActiveStage = $activeStage ?? 'all';
$hasActiveFilters = !empty(array_filter($advancedFilters ?? [], function($v) { return $v !== '' && $v !== null; }));
$companyName = trim((string) ($job['company'] ?? $job['client_company_name'] ?? ''));
$metaParts = array_filter([
    $job['location'] ?? '',
    $job['category'] ?? '',
    $companyName,
    'JOB-' . str_pad((string) ($job['id'] ?? 0), 4, '0', STR_PAD_LEFT),
]);
$statusLabel = ucfirst((string) ($job['status'] ?? 'open'));
$statusClass = strtolower((string) ($job['status'] ?? 'open')) === 'open' ? 'is-open' : 'is-closed';
?>



<div
    id="recruiterPipelinePage"
    class="recruiter-pipeline-page recruiter-applications-jobboard recruiter-leaderboard-jobboard"
    data-job-id="<?= (int) $job['id'] ?>"
    data-job-title="<?= esc($job['title']) ?>"
    data-bulk-url="<?= base_url('recruiter/jobs/' . $job['id'] . '/applications/bulk') ?>"
    data-email-url="<?= base_url('recruiter/jobs/' . $job['id'] . '/send-bulk-email') ?>"
    data-status-url-base="<?= base_url('recruiter/applications/update-status/') ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-hash="<?= csrf_hash() ?>"
>
<div class="pipeline-shell">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header pipeline-job-head">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-users-cog"></i> Recruiter pipeline</span>
            <h1 class="page-board-title"><?= esc($job['title']) ?></h1>
            <div class="pipeline-meta">
                <?php foreach ($metaParts as $part): ?>
                    <span><?= esc($part) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="page-board-actions pipeline-head-actions">
            <a href="<?= base_url('recruiter/jobs') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to My Jobs
            </a>
            <a href="<?= base_url('recruiter/jobs/edit/' . $job['id']) ?>" class="btn btn-outline-secondary"><i class="fas fa-edit"></i> Edit</a>
            <a href="<?= base_url('recruiter/jobs/preview/' . $job['id']) ?>" class="btn btn-primary" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Preview Job</a>
        </div>
    </div>

    <ul class="nav pipeline-work-nav" id="jobDetailTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#applications-list" role="tab"><i class="fas fa-users"></i> Candidates</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#interviews" role="tab"><i class="fas fa-calendar-check"></i> Interviews</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#leaderboard" role="tab"><i class="fas fa-trophy"></i> Leaderboard</a></li>
    </ul>

    <!-- Bulk Action Bar (Shared across Applications and Leaderboard) -->
    <div id="bulkActionBar" class="card shadow-sm mt-3 mb-2 d-none">
        <div class="card-body py-2 d-flex align-items-center justify-content-between">
            <div class="small">
                <span id="selectedCount" class="font-weight-bold text-primary">0</span> candidates selected
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openBulkEmailModal()"><i class="fas fa-at mr-1"></i> Mail</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openBulkMessageModal()"><i class="fas fa-comments mr-1"></i> Message</button>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="executeBulkAction('shortlist')"><i class="fas fa-check-circle mr-1"></i> Shortlist</button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="executeBulkAction('reject')"><i class="fas fa-times-circle mr-1"></i> Reject</button>
            </div>
        </div>
    </div>

    <!-- Bulk Message Modal -->
    <div class="modal fade" id="bulkMessageModal" tabindex="-1" role="dialog" aria-labelledby="bulkMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkMessageModalLabel"><i class="fas fa-comments mr-2"></i>Message Selected Candidates</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label for="bulkMessageText" class="font-weight-bold">Message</label>
                        <textarea class="form-control" id="bulkMessageText" rows="5" maxlength="1000" placeholder="Write a message for the selected candidates..."></textarea>
                        <small class="form-text text-muted">This message will be sent to every selected candidate.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="executeBulkAction('message')">
                        <i class="fas fa-paper-plane mr-1"></i> Send Message
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Email Modal -->
    <div class="modal fade" id="bulkEmailModal" tabindex="-1" role="dialog" aria-labelledby="bulkEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkEmailModalLabel"><i class="fas fa-at mr-2"></i>Send Email to Selected Candidates</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">To:</label>
                        <div id="emailRecipients" class="p-2 border rounded bg-light" style="max-height: 120px; overflow-y: auto;">
                            <span class="text-muted">No recipients selected</span>
                        </div>
                        <small class="text-muted"><span id="emailRecipientCount">0</span> recipients</small>
                    </div>
                    <div class="form-group">
                        <label for="emailSubject" class="font-weight-bold">Subject:</label>
                        <input type="text" class="form-control" id="emailSubject" placeholder="Enter email subject..." required>
                    </div>
                    <div class="form-group">
                        <label for="emailBody" class="font-weight-bold">Message:</label>
                        <textarea class="form-control" id="emailBody" rows="10" placeholder="Write your email message here..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Quick Templates:</label>
                        <div class="btn-group btn-group-sm flex-wrap">
                            <button type="button" class="btn btn-outline-primary" onclick="applyEmailTemplate('interview')">Interview Invitation</button>
                            <button type="button" class="btn btn-outline-primary" onclick="applyEmailTemplate('followup')">Follow-up</button>
                            <button type="button" class="btn btn-outline-primary" onclick="applyEmailTemplate('rejection')">Rejection Notice</button>
                            <button type="button" class="btn btn-outline-primary" onclick="applyEmailTemplate('offer')">Offer Letter</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="sendBulkEmail()">
                        <i class="fas fa-paper-plane mr-1"></i> Send Email
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success recruiter-alert"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger recruiter-alert"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="tab-content" id="jobDetailTabContent">
        <!-- Applications List Tab -->
        <div class="tab-pane fade show active" id="applications-list" role="tabpanel">
            <div class="pipeline-board">
                <div class="pipeline-summary-bar">
                    <div class="pipeline-summary-main">
                        <strong><?= $allApplicationsLabel ?> Candidates</strong>
                        <span class="pipeline-hiring-chip"><i class="fas fa-users"></i> <?= max(0, $openings) ?> openings</span>
                        <span class="pipeline-hiring-chip"><i class="fas fa-bullseye"></i> <?= $avgMatch ?>% avg match</span>
                    </div>
                    
                </div>

                <div class="pipeline-stage-rail">
                    <a class="stage-ajax-link <?= $safeActiveStage === 'all' ? 'active' : '' ?>" href="<?= base_url('recruiter/jobs/view/' . $job['id'] . '?stage=all') ?>">
                        <span class="stage-count"><i class="fas fa-layer-group"></i> <?= $allApplicationsLabel ?></span>
                        <span class="stage-label">All</span>
                    </a>
                    <?php foreach ($statuses as $key => $label): ?>
                        <a class="stage-ajax-link <?= $safeActiveStage === $key ? 'active' : '' ?>" href="<?= base_url('recruiter/jobs/view/' . $job['id'] . '?stage=' . $key) ?>">
                            <span class="stage-label"><?= esc($label) ?></span>
                        </a>
                    <?php endforeach; ?>
                    
                </div>

                <div class="pipeline-toolbar">
                    <div class="pipeline-search">
                        <i class="fas fa-search"></i>
                        <input type="search" id="candidatePipelineSearch" placeholder="Search candidates..." autocomplete="off">
                    </div>
                    <button type="button" class="pipeline-tool-btn <?= $hasActiveFilters ? 'active' : '' ?>" data-toggle="collapse" data-target="#advancedFilterCollapse" aria-expanded="<?= $hasActiveFilters ? 'true' : 'false' ?>" aria-controls="advancedFilterCollapse">
                        <i class="fas fa-filter"></i> Advanced Filters
                        <?php if ($hasActiveFilters): ?>
                            <span class="badge badge-primary ml-1"><?= count(array_filter($advancedFilters, function($v) { return $v !== '' && $v !== null; })) ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="pipeline-bulk-controls">
                        <select id="pipelineBulkAction" aria-label="Bulk action">
                            <option value="">Bulk Action</option>
                            <option value="email">Email Selected</option>
                            <option value="message">Message Selected</option>
                            <option value="shortlist">Shortlist Selected</option>
                            <option value="reject">Reject Selected</option>
                        </select>
                        <button type="button" class="btn btn-primary btn-sm" onclick="executeSelectedBulkAction()">
                            <i class="fas fa-bolt"></i> Apply
                        </button>
                    </div>
                </div>

                <!-- Advanced Filter Collapsible -->
                <div class="collapse <?= $hasActiveFilters ? 'show' : '' ?>" id="advancedFilterCollapse">
                    <div class="px-4 py-3 border-bottom bg-light">
                        <form method="get" action="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>">
                            <input type="hidden" name="stage" value="<?= esc($safeActiveStage) ?>">
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">Skills</label>
                                    <input type="text" name="skills" class="form-control form-control-sm" placeholder="e.g. PHP, React" value="<?= esc($advancedFilters['skills'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">Location</label>
                                    <input type="text" name="location" class="form-control form-control-sm" placeholder="e.g. Bangalore" value="<?= esc($advancedFilters['location'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">Experience (Years)</label>
                                    <input type="text" name="experience" class="form-control form-control-sm" placeholder="e.g. 2" value="<?= esc($advancedFilters['experience'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">Last Active</label>
                                    <select name="last_active" class="form-control form-control-sm">
                                        <option value="">Any time</option>
                                        <option value="7" <?= ($advancedFilters['last_active'] ?? '') === '7' ? 'selected' : '' ?>>Last 7 days</option>
                                        <option value="30" <?= ($advancedFilters['last_active'] ?? '') === '30' ? 'selected' : '' ?>>Last 30 days</option>
                                        <option value="90" <?= ($advancedFilters['last_active'] ?? '') === '90' ? 'selected' : '' ?>>Last 90 days</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row align-items-end">
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">ATS Score (Min %)</label>
                                    <input type="number" name="ats_min" class="form-control form-control-sm" min="0" max="100" value="<?= esc($advancedFilters['ats_min'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">ATS Score (Max %)</label>
                                    <input type="number" name="ats_max" class="form-control form-control-sm" min="0" max="100" value="<?= esc($advancedFilters['ats_max'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold text-muted">Sort By</label>
                                    <select name="sort" class="form-control form-control-sm">
                                        <option value="applied_desc" <?= ($advancedFilters['sort'] ?? '') === 'applied_desc' ? 'selected' : '' ?>>Most recent application</option>
                                        <option value="ats_desc" <?= ($advancedFilters['sort'] ?? '') === 'ats_desc' ? 'selected' : '' ?>>Highest ATS Match</option>
                                        <option value="ats_asc" <?= ($advancedFilters['sort'] ?? '') === 'ats_asc' ? 'selected' : '' ?>>Lowest ATS Match</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="d-flex" style="gap: 8px;">
                                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1 font-weight-bold">Apply Filters</button>
                                        <?php if ($hasActiveFilters): ?>
                                            <a href="<?= base_url('recruiter/jobs/view/' . $job['id'] . '?stage=' . $safeActiveStage) ?>" class="btn btn-outline-secondary btn-sm" title="Clear all filters">Clear</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            <div class="tab-content" id="applications-table-wrapper">
                <div class="tab-pane fade show active" id="applications-ajax-container">
                    <?= view('recruiter/partials/job_pipeline_applications', get_defined_vars()) ?>
                </div>
            </div>
            </div>
        </div>

        
        <!-- Interviews Tab -->
        <div class="tab-pane fade" id="interviews" role="tabpanel">
            <?php
            $interviewStats = $interviewStats ?? [
                'total_bookings' => 0,
                'upcoming' => 0,
                'completed' => 0,
                'rescheduled' => 0,
                'booked_slots' => 0,
            ];
            $interviewBookings = $interviewBookings ?? [];
            $interviewSlots = $interviewSlots ?? [];
            ?>
            <div class="card shadow-sm recruiter-summary-card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <div class="recruiter-summary-item">
                                <span class="recruiter-summary-label">Total Bookings</span>
                                <strong><?= number_format((int) $interviewStats['total_bookings']) ?></strong>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <div class="recruiter-summary-item">
                                <span class="recruiter-summary-label">Upcoming</span>
                                <strong><?= number_format((int) $interviewStats['upcoming']) ?></strong>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <div class="recruiter-summary-item">
                                <span class="recruiter-summary-label">Completed</span>
                                <strong><?= number_format((int) $interviewStats['completed']) ?></strong>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="recruiter-summary-item">
                                <span class="recruiter-summary-label">Booked Slots</span>
                                <strong><?= number_format((int) $interviewStats['booked_slots']) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm recruiter-table-card mb-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="m-0 font-weight-bold text-primary">Booked Interviews for This Job</h6>
                    
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover recruiter-bookings-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Candidate</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Booked On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($interviewBookings)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-calendar-check fa-2x text-muted mb-3"></i>
                                            <div class="font-weight-bold">No interview bookings yet</div>
                                            <div class="text-muted">Booked candidate slots for this job will appear here.</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($interviewBookings as $booking): ?>
                                        <?php
                                        $isPast = strtotime((string) $booking['slot_datetime']) < time();
                                        $isUpcoming = strtotime((string) $booking['slot_datetime']) > time();
                                        $bookingStatus = (string) ($booking['booking_status'] ?? 'booked');
                                        $statusColors = [
                                            'booked' => 'primary',
                                            'confirmed' => 'success',
                                            'completed' => 'info',
                                            'rescheduled' => 'warning',
                                            'no_show' => 'danger',
                                            'cancelled' => 'danger',
                                        ];
                                        $statusLabels = [
                                            'booked' => 'Booked',
                                            'confirmed' => 'Confirmed',
                                            'completed' => 'Completed',
                                            'rescheduled' => 'Rescheduled',
                                            'no_show' => 'No Show',
                                            'cancelled' => 'Cancelled',
                                        ];
                                        $color = $statusColors[$bookingStatus] ?? 'secondary';
                                        $hasReview = !empty($booking['review_id']);
                                        ?>
                                        <tr class="<?= $isPast ? 'table-secondary' : '' ?>">
                                            <td><?= (int) $booking['id'] ?></td>
                                            <td>
                                                <div class="recruiter-booking-person">
                                                    <strong><?= esc($booking['candidate_name'] ?? 'Candidate') ?></strong>
                                                    <span><?= esc($booking['email'] ?? '') ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?= !empty($booking['slot_date']) ? date('M d, Y', strtotime($booking['slot_date'])) : date('M d, Y', strtotime($booking['slot_datetime'])) ?></strong><br>
                                                <span class="text-primary"><?= !empty($booking['slot_time']) ? date('h:i A', strtotime($booking['slot_time'])) : date('h:i A', strtotime($booking['slot_datetime'])) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $color ?>">
                                                    <?= esc($statusLabels[$bookingStatus] ?? ucwords(str_replace('_', ' ', $bookingStatus))) ?>
                                                </span>
                                                <?php if ($hasReview): ?>
                                                    <div><small class="text-success"><i class="fas fa-check-circle"></i> Reviewed</small></div>
                                                    <?php if (!empty($booking['review_decision'])): ?>
                                                        <div><small class="text-muted">Decision: <?= esc(ucwords(str_replace('_', ' ', (string) $booking['review_decision']))) ?></small></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if ((int) ($booking['reschedule_count'] ?? 0) > 0): ?>
                                                    <div><small class="text-muted">Rescheduled: <?= (int) $booking['reschedule_count'] ?>x</small></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= !empty($booking['booked_at']) ? date('M d, Y', strtotime($booking['booked_at'])) : '-' ?></small>
                                            </td>
                                            <td>
                                                <div class="job-actions-wrap recruiter-booking-actions">
                                                    <?php if ($isUpcoming && in_array($bookingStatus, ['booked', 'confirmed', 'rescheduled'], true)): ?>
                                                        <a href="<?= base_url('recruiter/slots/reschedule/' . $booking['id']) ?>" class="btn btn-sm btn-warning btn-action">
                                                            <i class="fas fa-sync"></i> Reschedule
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($isPast || in_array($bookingStatus, ['completed', 'no_show', 'rescheduled'], true)): ?>
                                                        <a href="<?= base_url('recruiter/slots/review/' . $booking['id']) ?>" class="btn btn-sm btn-primary btn-action">
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
                </div>
            </div>

            <div class="card shadow-sm recruiter-table-card">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="m-0 font-weight-bold text-primary">Slot Capacity</h6>
                    <a href="<?= base_url('recruiter/slots/create') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-calendar-alt"></i> Create New Slots
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover recruiter-slots-table">
                            <thead class="thead-light">
                                <tr>
                                <th>ID</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Capacity</th>
                                    <th>Booked</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                                <?php if (empty($interviewSlots)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">No booked slots found for this job</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($interviewSlots as $slot): ?>
                                        <?php
                                        $isPastSlot = strtotime((string) $slot['slot_datetime']) < time();
                                        $isFull = (int) $slot['booked_count'] >= (int) $slot['capacity'];
                                        ?>
                                        <tr class="<?= $isPastSlot ? 'table-secondary' : ($isFull ? 'table-warning' : '') ?>">
                                            <td><?= (int) $slot['id'] ?></td>
                                            <td><?= date('M d, Y', strtotime($slot['slot_date'])) ?></td>
                                            <td><strong><?= date('h:i A', strtotime($slot['slot_time'])) ?></strong></td>
                                            <td><?= (int) $slot['capacity'] ?></td>
                                            <td>
                                                <span class="badge badge-primary"><?= (int) $slot['booked_count'] ?></span>
                                            </td>
                                            <td>
                                                <?php if ($isPastSlot): ?>
                                                    <span class="badge badge-secondary">Past</span>
                                                <?php elseif ($isFull): ?>
                                                    <span class="badge badge-danger">Full</span>
                                                <?php else: ?>
                                                    <span class="badge badge-dark">Available</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= esc($slot['created_by_name']) ?></td>
                                    <td>
                                        <?php if ($slot['booked_count'] == 0): ?>
                                            <a href="<?= base_url('recruiter/slots/edit/' . $slot['id']) ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= base_url('recruiter/slots/delete/' . $slot['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this slot?')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Has bookings</span>
                                        <?php endif; ?>
                                    </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard Tab -->
        <div class="tab-pane fade" id="leaderboard" role="tabpanel">
            <div class="card shadow-sm recruiter-leaderboard-card">
                <div class="card-header py-3 bg-gradient-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-crown"></i> Comparison View - <?= esc($job['title']) ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border mb-4">
                        <strong>How to use this page:</strong> compare candidate quality here, then return to Candidates to shortlist, reject, or message applicants.
                    </div>

                    <?php if (empty($leaderboard)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No candidates found for this leaderboard</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover leaderboard-table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="60">Rank</th>
                                        <th>Candidate</th>
                                        <th>Job Position</th>
                                        <th>Skills</th>
                                        <th>GitHub Stack</th>
                                        <th class="text-center">Technical</th>
                                        <th class="text-center">Communication</th>
                                        <th class="text-center">Overall Rating</th>
                                        <th class="text-center">ATS</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Review</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaderboard as $candidate): ?>
                                        <?php
                                            $rank = (int) ($candidate['rank'] ?? 0);
                                            $candidateSkills = $candidate['candidate_skills'] ?? [];
                                            $requiredSkills = $candidate['required_skills'] ?? [];
                                            $candidateSkillsLower = array_map('strtolower', $candidateSkills);
                                            $technicalScore = (float) ($candidate['technical_score'] ?? 0);
                                            $communicationScore = (float) ($candidate['communication_score'] ?? 0);
                                            $overallRating = (float) ($candidate['overall_rating'] ?? 0);
                                            $atsScore = (int) ($candidate['ats_score'] ?? 0);
                                            $status = (string) ($candidate['status'] ?? 'applied');
                                            $statusColors = [
                                                'applied' => 'secondary',
                                                'pending' => 'secondary',
                                                'shortlisted' => 'primary',
                                                'interview_scheduled' => 'warning',
                                                'interview_slot_booked' => 'warning',
                                                'interviewed' => 'info',
                                                'offered' => 'success',
                                                'selected' => 'success',
                                                'hired' => 'success',
                                                'filtered_out' => 'dark',
                                                'rejected' => 'danger',
                                                'withdrawn' => 'secondary',
                                                'on_hold' => 'warning',
                                            ];
                                            $color = $statusColors[$status] ?? 'secondary';
                                        ?>
                                        <tr class="<?= $rank <= 3 ? 'top-performer' : '' ?>">
                                            <td class="rank-cell">
                                                <?php if ($rank === 1): ?>
                                                    <span class="rank-badge gold"><i class="fas fa-crown"></i> 1</span>
                                                <?php elseif ($rank === 2): ?>
                                                    <span class="rank-badge silver"><i class="fas fa-medal"></i> 2</span>
                                                <?php elseif ($rank === 3): ?>
                                                    <span class="rank-badge bronze"><i class="fas fa-medal"></i> 3</span>
                                                <?php else: ?>
                                                    <span class="rank-number"><?= $rank ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="candidate-info">
                                                    <strong><?= esc($candidate['candidate_name'] ?? $candidate['name'] ?? 'Candidate') ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= esc($candidate['candidate_email'] ?? $candidate['email'] ?? '') ?></small>
                                                </div>
                                            </td>
                                            <td><?= esc($candidate['job_title'] ?? $job['title']) ?></td>
                                            <td>
                                                <div class="skills-display">
                                                    <?php if (!empty($requiredSkills)): ?>
                                                        <div class="skill-match-badge mb-2">
                                                            <span class="badge badge-<?= ($candidate['skill_match'] ?? 0) >= 80 ? 'success' : (($candidate['skill_match'] ?? 0) >= 60 ? 'warning' : 'danger') ?>">
                                                                <?= (int) ($candidate['skill_match'] ?? 0) ?>% Match
                                                            </span>
                                                            <small class="text-muted">
                                                                (<?= count(array_intersect($candidateSkillsLower, array_map('strtolower', $requiredSkills))) ?>/<?= count($requiredSkills) ?>)
                                                            </small>
                                                        </div>
                                                        <div class="required-skills">
                                                            <?php foreach ($requiredSkills as $requiredSkill): ?>
                                                                <?php $hasSkill = in_array(strtolower($requiredSkill), $candidateSkillsLower, true); ?>
                                                                <span class="skill-badge <?= $hasSkill ? 'skill-has' : 'skill-missing' ?>" title="<?= $hasSkill ? 'Candidate has this skill' : 'Candidate does not have this skill' ?>">
                                                                    <?= esc($requiredSkill) ?>
                                                                    <i class="fas fa-<?= $hasSkill ? 'check' : 'times' ?>-circle <?= $hasSkill ? 'text-success' : 'text-danger' ?>"></i>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">No required skills specified</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($candidate['github_stack'])): ?>
                                                    <div class="skills-display">
                                                        <div class="required-skills">
                                                            <?php foreach (array_slice($candidate['github_stack'], 0, 6) as $language): ?>
                                                                <span class="skill-badge skill-has"><?= esc($language) ?></span>
                                                            <?php endforeach; ?>
                                                            <?php if (count($candidate['github_stack']) > 6): ?>
                                                                <span class="badge badge-light">+<?= count($candidate['github_stack']) - 6 ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No GitHub stack</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="score-display">
                                                    <span class="score-value <?= $technicalScore >= 80 ? 'text-success' : ($technicalScore >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                        <?= number_format($technicalScore, 1) ?>
                                                    </span>
                                                    <div class="score-bar"><div class="score-fill" style="width: <?= min(100, max(0, $technicalScore)) ?>%"></div></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="score-display">
                                                    <span class="score-value <?= $communicationScore >= 80 ? 'text-success' : ($communicationScore >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                        <?= number_format($communicationScore, 1) ?>
                                                    </span>
                                                    <div class="score-bar"><div class="score-fill" style="width: <?= min(100, max(0, $communicationScore)) ?>%"></div></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="overall-rating">
                                                    <span class="rating-badge badge-<?= $overallRating >= 80 ? 'success' : ($overallRating >= 60 ? 'warning' : 'danger') ?>">
                                                        <?= number_format($overallRating, 1) ?>
                                                    </span>
                                                    <div class="rating-stars">
                                                        <?php $stars = round($overallRating / 20); ?>
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?= $i <= $stars ? 'text-warning' : 'text-muted' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="score-display">
                                                    <span class="score-value <?= $atsScore >= 80 ? 'text-success' : ($atsScore >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                        <?= $atsScore ?>
                                                    </span>
                                                    <div class="score-bar"><div class="score-fill" style="width: <?= min(100, max(0, $atsScore)) ?>%"></div></div>
                                                    <small class="text-muted d-block mt-1">Fit signal</small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-<?= $color ?>">
                                                    <?= esc($statuses[$status] ?? ucwords(str_replace('_', ' ', $status))) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('recruiter/candidate/' . $candidate['candidate_id'] . '?application_id=' . $candidate['id'] . '&job_id=' . $candidate['job_id']) ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-eye"></i> View Application
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer', [
    'pageScripts' => [base_url('jobboard/js/recruiter-pipeline.js?v=' . @filemtime(FCPATH . 'jobboard/js/recruiter-pipeline.js'))],
]) ?>


