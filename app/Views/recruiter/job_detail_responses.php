<?= view('Layouts/recruiter_header', [
    'title' => 'Job Detail Pipeline',
    'pageStyles' => [base_url('jobboard/css/recruiter-pipeline.css?v=' . @filemtime(FCPATH . 'jobboard/css/recruiter-pipeline.css'))],
]) ?>
<?php
$statusTones = [
    'applied' => 'neutral',
    'ai_interview_completed' => 'info',
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
$hasActiveFilters = !empty(array_filter($advancedFilters ?? [], function($v, $key) {
    if ($key === 'sort' && ($v === '' || $v === null || $v === 'ats_desc')) {
        return false;
    }
    return $v !== '' && $v !== null;
}, ARRAY_FILTER_USE_BOTH));
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
    data-schedule-url-base="<?= base_url('recruiter/applications/schedule-interview/') ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-hash="<?= csrf_hash() ?>"
>
<div class="pipeline-shell container-fluid">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header pipeline-job-head">
        <div class="page-board-copy"> 
            <span class="pipeline-job-status <?= esc($statusClass) ?>"><?= esc($statusLabel) ?></span>
            <h1 class="page-board-title"><?= esc($job['title']) ?></h1>
            <div class="pipeline-meta">
                <?php foreach ($metaParts as $part): ?>
                    <span><?= esc($part) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="page-board-actions pipeline-head-actions">
            <a href="<?= base_url('recruiter/jobs') ?>" class="btn btn-outline-primary">
                Back to My Jobs
            </a>
            <a href="<?= base_url('recruiter/jobs/edit/' . $job['id']) ?>" class="btn btn-outline-primary">  Edit</a>
            <a href="<?= base_url('recruiter/jobs/preview/' . $job['id']) ?>" class="btn btn-outline-primary" target="_blank" rel="noopener">  Preview Job</a>
        </div>
    </div>

    <ul class="nav pipeline-work-nav" id="jobDetailTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#applications-list" role="tab"><i class="fas fa-users"></i> Candidates</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#interviews" role="tab"><i class="fas fa-calendar-check"></i> Interviews</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#leaderboard" role="tab"><i class="fas fa-trophy"></i> Leaderboard</a></li>
    </ul>

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
                    <button type="button" class="btn btn-outline-primary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-primary" onclick="executeBulkAction('message')">
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
                        <div id="emailRecipients" class="p-2 border rounded bg-light recruiter-recipient-box">
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
                    <button type="button" class="btn btn-outline-primary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-primary" id="pipelineBulkEmailSendButton" onclick="sendBulkEmail()">
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
            <div class="pipeline-board response-console">
                <aside class="response-filter-panel" aria-label="Response filters">
                    <div class="response-filter-head">
                        <strong>Filters</strong>
                        <?php if ($hasActiveFilters): ?>
                            <a href="<?= base_url('recruiter/jobs/view/' . $job['id'] . '?stage=' . $safeActiveStage) ?>">Clear</a>
                        <?php endif; ?>
                    </div>
                    <form method="get" action="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>" class="response-filter-form is-collapsible">
                        <input type="hidden" name="stage" value="<?= esc($safeActiveStage) ?>">

                        <div class="response-filter-group">
                            <label for="responseSkills">Keywords</label>
                            <input type="text" id="responseSkills" name="skills" placeholder="Search keywords in profile" value="<?= esc($advancedFilters['skills'] ?? '') ?>">
                        </div>

                        <div class="response-filter-group">
                            <label for="responseLocation">Location</label>
                            <input type="text" id="responseLocation" name="location" placeholder="City or region" value="<?= esc($advancedFilters['location'] ?? '') ?>">
                        </div>

                        <div class="response-filter-group">
                            <label for="responseExperience">Experience</label>
                            <input type="text" id="responseExperience" name="experience" placeholder="e.g. 2" value="<?= esc($advancedFilters['experience'] ?? '') ?>">
                        </div>

                        <div class="response-filter-group">
                            <label for="responseNoticePeriod">Notice period</label>
                            <select id="responseNoticePeriod" name="notice_period">
                                <option value="">Any availability</option>
                                <option value="immediate" <?= ($advancedFilters['notice_period'] ?? '') === 'immediate' ? 'selected' : '' ?>>Immediate</option>
                                <option value="15" <?= ($advancedFilters['notice_period'] ?? '') === '15' ? 'selected' : '' ?>>0-15 days</option>
                                <option value="30" <?= ($advancedFilters['notice_period'] ?? '') === '30' ? 'selected' : '' ?>>30 days</option>
                                <option value="60" <?= ($advancedFilters['notice_period'] ?? '') === '60' ? 'selected' : '' ?>>60 days</option>
                                <option value="90" <?= ($advancedFilters['notice_period'] ?? '') === '90' ? 'selected' : '' ?>>90 days</option>
                            </select>
                        </div>

                        <div class="response-filter-group">
                            <label>Salary / CTC</label>
                            <div class="response-score-range">
                                <input type="number" name="salary_min" min="0" step="0.1" placeholder="Min LPA" value="<?= esc($advancedFilters['salary_min'] ?? '') ?>">
                                <input type="number" name="salary_max" min="0" step="0.1" placeholder="Max LPA" value="<?= esc($advancedFilters['salary_max'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="response-filter-group">
                            <label for="responseEducation">Education</label>
                            <input type="text" id="responseEducation" name="education" placeholder="Degree or college" value="<?= esc($advancedFilters['education'] ?? '') ?>">
                        </div>

                        <div class="response-filter-group">
                            <label for="responseDiversity">Diversity</label>
                            <select id="responseDiversity" name="diversity">
                                <option value="">Any candidate</option>
                                <option value="female" <?= ($advancedFilters['diversity'] ?? '') === 'female' ? 'selected' : '' ?>>Female candidates</option>
                                <option value="male" <?= ($advancedFilters['diversity'] ?? '') === 'male' ? 'selected' : '' ?>>Male candidates</option>
                                <option value="other" <?= ($advancedFilters['diversity'] ?? '') === 'other' ? 'selected' : '' ?>>Other / non-binary</option>
                            </select>
                        </div>

                        <div class="response-filter-group">
                            <label for="responseCompany">Company</label>
                            <input type="text" id="responseCompany" name="company" placeholder="Current or previous company" value="<?= esc($advancedFilters['company'] ?? '') ?>">
                        </div>

                        <div class="response-filter-group">
                            <label for="responseDesignation">Designation</label>
                            <input type="text" id="responseDesignation" name="designation" placeholder="Role title or headline" value="<?= esc($advancedFilters['designation'] ?? '') ?>">
                        </div>

                        <div class="response-filter-group">
                            <label for="responseLastActive">Last active</label>
                            <select id="responseLastActive" name="last_active">
                                <option value="">Any time</option>
                                <option value="7" <?= ($advancedFilters['last_active'] ?? '') === '7' ? 'selected' : '' ?>>Last 7 days</option>
                                <option value="30" <?= ($advancedFilters['last_active'] ?? '') === '30' ? 'selected' : '' ?>>Last 30 days</option>
                                <option value="90" <?= ($advancedFilters['last_active'] ?? '') === '90' ? 'selected' : '' ?>>Last 90 days</option>
                            </select>
                        </div>

                        <div class="response-filter-group">
                            <label>Match score</label>
                            <div class="response-score-range">
                                <input type="number" name="ats_min" min="0" max="100" placeholder="Min" value="<?= esc($advancedFilters['ats_min'] ?? '') ?>">
                                <input type="number" name="ats_max" min="0" max="100" placeholder="Max" value="<?= esc($advancedFilters['ats_max'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="response-filter-group">
                            <label for="responseSort">Sort by</label>
                            <select id="responseSort" name="sort">
                                <option value="applied_desc" <?= ($advancedFilters['sort'] ?? '') === 'applied_desc' ? 'selected' : '' ?>>Most recent application</option>
                                <option value="ats_desc" <?= ($advancedFilters['sort'] ?? 'ats_desc') === 'ats_desc' ? 'selected' : '' ?>>Highest ATS match</option>
                                <option value="ats_asc" <?= ($advancedFilters['sort'] ?? '') === 'ats_asc' ? 'selected' : '' ?>>Lowest ATS match</option>
                            </select>
                        </div>

                        <button type="submit" class="response-filter-submit">Apply filters</button>
                    </form>
                </aside>

                <div class="response-workspace">
                    <div class="response-tabs">
                        <?php
                            $responseStageTabs = [
                                'all' => ['label' => 'All', 'count' => $allApplicationsLabel],
                                'applied' => ['label' => 'Applied', 'count' => count($applicationsByStatus['applied'] ?? [])],
                                'ai_interview_completed' => ['label' => 'AI Interview Completed', 'count' => count($applicationsByStatus['ai_interview_completed'] ?? [])],
                                'shortlisted' => ['label' => 'Shortlisted', 'count' => count($applicationsByStatus['shortlisted'] ?? [])],
                                'interview_scheduled' => ['label' => 'Interview Scheduled', 'count' => count($applicationsByStatus['interview_scheduled'] ?? [])],
                                'interviewed' => ['label' => 'Interviewed', 'count' => count($applicationsByStatus['interviewed'] ?? [])],
                                'offered' => ['label' => 'Offered', 'count' => count($applicationsByStatus['offered'] ?? [])],
                                'hired' => ['label' => 'Hired', 'count' => count($applicationsByStatus['hired'] ?? [])],
                                'rejected' => ['label' => 'Rejected', 'count' => count($applicationsByStatus['rejected'] ?? [])],
                                'withdrawn' => ['label' => 'Withdrawn', 'count' => count($applicationsByStatus['withdrawn'] ?? [])],
                                'on_hold' => ['label' => 'On Hold', 'count' => count($applicationsByStatus['on_hold'] ?? [])],
                                'filtered_out' => ['label' => 'Filtered Out', 'count' => count($applicationsByStatus['filtered_out'] ?? [])],
                            ];
                        ?>
                        <?php foreach ($responseStageTabs as $stageKey => $stageTab): ?>
                            <a class="stage-ajax-link <?= $safeActiveStage === $stageKey ? 'active' : '' ?>" href="<?= base_url('recruiter/jobs/view/' . $job['id'] . '?stage=' . $stageKey) ?>" data-stage="<?= esc($stageKey) ?>" data-label="<?= esc($stageTab['label']) ?>" data-count="<?= (int) $stageTab['count'] ?>">
                                <?= esc($stageTab['label']) ?> (<?= (int) $stageTab['count'] ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php
                        $activeStageMeta = $responseStageTabs[$safeActiveStage] ?? $responseStageTabs['all'];
                        $activeStageCount = $safeActiveStage === 'all' ? $allApplicationsLabel : (int) ($activeStageMeta['count'] ?? 0);
                    ?>
                    <?php
                        $nextActionLabels = [
                            'unreviewed' => 'Unreviewed',
                            'follow_up_due' => 'Follow-up due',
                            'candidate_replied' => 'Candidate replied',
                            'interview_pending' => 'Interview pending',
                            'inactive_3d' => 'No activity for 3+ days',
                        ];
                        $activeNextAction = (string) ($advancedFilters['next_action'] ?? '');
                        $nextActionBase = array_filter(array_merge($advancedFilters ?? [], ['stage' => $safeActiveStage]), static fn ($value) => $value !== '' && $value !== null);
                    ?>
                    <nav class="next-action-bar" aria-label="Next action filters">
                        <span class="next-action-label">Next action</span>
                        <?php foreach ($nextActionLabels as $nextActionKey => $nextActionLabel): ?>
                            <?php
                                $nextActionParams = $nextActionBase;
                                if ($activeNextAction === $nextActionKey) {
                                    unset($nextActionParams['next_action']);
                                } else {
                                    $nextActionParams['next_action'] = $nextActionKey;
                                }
                                $nextActionUrl = base_url('recruiter/jobs/view/' . $job['id'] . '?' . http_build_query($nextActionParams));
                            ?>
                            <a href="<?= esc($nextActionUrl) ?>" class="next-action-filter <?= $activeNextAction === $nextActionKey ? 'is-active' : '' ?>" data-next-action="<?= esc($nextActionKey) ?>">
                                <?= esc($nextActionLabel) ?>
                                <span data-next-action-count="<?= esc($nextActionKey) ?>"><?= (int) ($nextActionCounts[$nextActionKey] ?? 0) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <div class="response-toolbar">
                        <div class="response-toolbar-summary">
                            <strong id="responseShowingCount">Showing <?= $activeStageCount ?> <?= $activeStageCount === 1 ? 'response' : 'responses' ?></strong>
                            <span class="response-chip"><?= max(0, $openings) ?> openings</span>
                            <span class="response-chip"><?= $avgMatch ?>% avg match</span>
                        </div>
                        <div class="response-toolbar-actions">
                            <div class="pipeline-search">
                                <i class="fas fa-search"></i>
                                <input type="search" id="candidatePipelineSearch" placeholder="Search candidates..." autocomplete="off">
                            </div>
                            <a href="<?= base_url('recruiter/dashboard/export-excel?type=detailed&job_id=' . (int) $job['id']) ?>" class="response-export-link">
                                <i class="fas fa-file-excel"></i> Export
                            </a>
                        </div>
                    </div>

                    <div class="tab-content" id="applications-table-wrapper">
                        <div class="tab-pane fade show active" id="applications-ajax-container">
                            <?= view('recruiter/partials/job_pipeline_applications', get_defined_vars()) ?>
                        </div>
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
            <div class="card shadow-sm recruiter-summary-card mb-4 recruiter-rounded-hidden">
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

            <div class="card shadow-sm recruiter-table-card mb-4 recruiter-rounded-hidden">
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
                                                        <a href="<?= base_url('recruiter/slots/review/' . $booking['id']) ?>" class="btn btn-sm btn-outline-primary btn-action">
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
                </div>
            </div>

            <div class="card shadow-sm recruiter-table-card recruiter-rounded-hidden">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="m-0 font-weight-bold text-primary">Slot Capacity</h6>
                    <a href="<?= base_url('recruiter/slots/create') ?>" class="btn btn-sm btn-outline-primary">
                  Create New Slots
                    </a>
                </div>
                <div class="card-body" >
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
                                        <td colspan="8" class="text-center py-5">No booked slots found for this job</td>
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
                                            <a href="<?= base_url('recruiter/slots/edit/' . $slot['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="post" action="<?= base_url('recruiter/slots/delete/' . $slot['id']) ?>" class="d-inline" onsubmit="return confirm('Delete this slot?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
            <div class="card shadow-sm recruiter-leaderboard-card recruiter-rounded-hidden">
                <div class="card-header ">
                    <h6 class="m-0 font-weight-bold">
                      Comparison View - <?= esc($job['title']) ?>
                    </h6>
                </div>
                <div class="card-body">
<?php if (empty($leaderboard)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No candidates found for this leaderboard</p>
                        </div>
                    <?php else: ?>
                        <?php
                            $showAiScoreColumns = false;
                            foreach ($leaderboard as $scoreCandidate) {
                                if (
                                    $scoreCandidate['technical_score'] !== null
                                    || $scoreCandidate['communication_score'] !== null
                                    || $scoreCandidate['overall_rating'] !== null
                                ) {
                                    $showAiScoreColumns = true;
                                    break;
                                }
                            }
                        ?>
                        <div class="table-responsive">
                            <table class="table table-hover leaderboard-table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="60">Rank</th>
                                        <th>Candidate</th>
                                        <th>Job Position</th>
                                        <th>Skills</th>
                                        <th>GitHub Stack</th>
                                        <?php if ($showAiScoreColumns): ?>
                                            <th class="text-center">Technical</th>
                                            <th class="text-center">Communication</th>
                                            <th class="text-center">Overall Rating</th>
                                        <?php endif; ?>
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
                                            $technicalScore = $candidate['technical_score'] !== null ? (float) $candidate['technical_score'] : null;
                                            $communicationScore = $candidate['communication_score'] !== null ? (float) $candidate['communication_score'] : null;
                                            $overallRating = $candidate['overall_rating'] !== null ? (float) $candidate['overall_rating'] : null;
                                            $atsScore = (int) ($candidate['ats_score'] ?? 0);
                                            $status = (string) ($candidate['status'] ?? 'applied');
                                            $statusColors = [
                                                'applied' => 'secondary',
                                                'pending' => 'secondary',
                                                'ai_interview_completed' => 'info',
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
                                                    <span  >  1</span>
                                                <?php elseif ($rank === 2): ?>
                                                    <span  >  2</span>
                                                <?php elseif ($rank === 3): ?>
                                                    <span  > 3</span>
                                                <?php else: ?>
                                                    <span  ><?= $rank ?></span>
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
                                                            <span class="status-pill">
                                                                <?= (int) ($candidate['skill_match'] ?? 0) ?>% Match
                                                            </span>
                                                            <small class="text-muted">
                                                                (<?= count(array_intersect($candidateSkillsLower, array_map('strtolower', $requiredSkills))) ?>/<?= count($requiredSkills) ?>)
                                                            </small>
                                                        </div>
                                                        <div class="required-skills">
                                                            <?php foreach ($requiredSkills as $requiredSkill): ?>
                                                                <?php $hasSkill = in_array(strtolower($requiredSkill), $candidateSkillsLower, true); ?>
                                                                <span class="status-pill" title="<?= $hasSkill ? 'Candidate has this skill' : 'Candidate does not have this skill' ?>">
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
                                                                <span class="status-pill"><?= esc($language) ?></span>
                                                            <?php endforeach; ?>
                                                            <?php if (count($candidate['github_stack']) > 6): ?>
                                                                <span class="status-pill">+<?= count($candidate['github_stack']) - 6 ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No GitHub stack</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($showAiScoreColumns): ?>
                                                <td class="text-center">
                                                    <?php if ($technicalScore !== null): ?>
                                                        <div class="score-display">
                                                            <span class="score-value <?= $technicalScore >= 80 ? 'text-success' : ($technicalScore >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                                <?= number_format($technicalScore, 1) ?>
                                                            </span>
                                                            <div class="score-bar"><div class="score-fill" style="width: <?= min(100, max(0, $technicalScore)) ?>%"></div></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">No score</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($communicationScore !== null): ?>
                                                        <div class="score-display">
                                                            <span class="score-value <?= $communicationScore >= 80 ? 'text-success' : ($communicationScore >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                                <?= number_format($communicationScore, 1) ?>
                                                            </span>
                                                            <div class="score-bar"><div class="score-fill" style="width: <?= min(100, max(0, $communicationScore)) ?>%"></div></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">No score</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($overallRating !== null): ?>
                                                        <div class="overall-rating">
                                                            <span class="status-pill">
                                                                <?= number_format($overallRating, 1) ?>
                                                            </span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">No score</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
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
                                                <span class="status-pill">
                                                    <?= esc($statuses[$status] ?? ucwords(str_replace('_', ' ', $status))) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('recruiter/candidate/' . $candidate['candidate_id'] . '?application_id=' . $candidate['id'] . '&job_id=' . $candidate['job_id']) ?>" class="btn btn-sm btn-outline-primary">
                                                   View 
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

<div class="modal fade ai-modal recruiter-rounded-hidden" id="aiReportModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content ai-modal-content">
      <div class="modal-header ai-header">
        <div class="ai-title">
          <span class="ai-icon"><i class="fas fa-chart-line"></i></span>
          <div>
            <h5>AI Interview Report</h5>
            <small>Detailed candidate performance and insights</small>
          </div>
        </div>
        <button type="button" class="ai-close" data-dismiss="modal" aria-label="Close">
          x
        </button>
      </div>
      <div class="modal-body ai-body">
        <div id="aiReportContent">
          <div class="ai-loader">
            <div class="spinner"></div>
            <p>Analyzing interview performance...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var filterGroups = document.querySelectorAll('.response-filter-form.is-collapsible .response-filter-group');

    function getGroupLabel(group) {
        for (var i = 0; i < group.children.length; i++) {
            if (group.children[i].tagName && group.children[i].tagName.toLowerCase() === 'label') {
                return group.children[i];
            }
        }
        return null;
    }

    function groupHasActiveValue(group) {
        var fields = group.querySelectorAll('input, select, textarea');

        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            var value = (field.value || '').trim();

            if (field.type === 'hidden') {
                continue;
            }

            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
                continue;
            }

            if (field.name === 'sort' && (value === '' || value === 'ats_desc')) {
                continue;
            }

            if (value !== '') {
                return true;
            }
        }

        return false;
    }

    filterGroups.forEach(function (group) {
        var label = getGroupLabel(group);

        if (!label) {
            return;
        }

        if (groupHasActiveValue(group)) {
            group.classList.add('is-open');
        }

        label.setAttribute('role', 'button');
        label.setAttribute('tabindex', '0');
        label.setAttribute('aria-expanded', group.classList.contains('is-open') ? 'true' : 'false');

        function toggleGroup(event) {
            event.preventDefault();
            var isOpen = group.classList.toggle('is-open');
            label.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        label.addEventListener('click', toggleGroup);
        label.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                toggleGroup(event);
            }
        });
    });
});

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.view-ai-report-btn');
    if (!btn) return;

    const candidateId = btn.dataset.candidateId;
    const jobId = btn.dataset.jobId;
    const jobrole = btn.dataset.jobrole;
    const candidateName = btn.dataset.candidateName;
    const reportHost = document.getElementById('aiReportContent');

    if (!reportHost) return;

    $('#aiReportModal').modal('show');
    reportHost.innerHTML = `
        <div class="ai-loader">
            <div class="spinner"></div>
            <p>Analyzing interview performance...</p>
        </div>`;

    fetch('<?= base_url('recruiter/get-ai-report') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ candidate_id: candidateId, job_id: jobId, jobrole: jobrole })
    })
    .then(function (res) {
        if (!res.ok) {
            return res.text().then(function (txt) {
                throw new Error('Server error ' + res.status + (txt ? ': ' + txt.substring(0, 200) : ''));
            });
        }
        return res.json();
    })
    .then(function (data) {
        if (!data || typeof data !== 'object') {
            throw new Error('Invalid response from server.');
        }

        let violationsHtml = '';
        if (Array.isArray(data.violations) && data.violations.length) {
            data.violations.forEach(function (v) {
                violationsHtml += `
                    <tr>
                        <td>${v.message ?? '-'}</td>
                        <td><span class="status-pill">${v.total ?? 0}</span></td>
                    </tr>`;
            });
        } else {
            violationsHtml = `
                <tr>
                    <td colspan="2" class="text-center text-muted">No violations found</td>
                </tr>`;
        }

        let resultHtml = '';
        if (Array.isArray(data.results) && data.results.length) {
            data.results.forEach(function (r) {
                resultHtml += `
                    <tr>
                        <td>${r.round_name ?? '-'}</td>
                        <td>${r.score ?? 0}</td>
                        <td>${r.total_questions ?? 0}</td>
                        <td><span class="status-pill">${r.percentage ?? 0}%</span></td>
                    </tr>`;
            });
        } else {
            resultHtml = `
                <tr>
                    <td colspan="4" class="text-center text-muted">No interview results</td>
                </tr>`;
        }

        reportHost.innerHTML = `
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h4 class="font-weight-bold">${candidateName}</h4>
                            <small class="text-muted">${jobrole}</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Interview Scores</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Round</th>
                                        <th>Score</th>
                                        <th>Total</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>${resultHtml}</tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Violations</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Violation</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>
                                <tbody>${violationsHtml}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>`;
    })
    .catch(function (err) {
        reportHost.innerHTML = `
            <div class="ai-error">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Failed to load report</strong>
                <p class="mb-0 text-muted recruiter-text-085">${err.message || 'An unexpected error occurred. Please try again.'}</p>
            </div>`;
    });
});
</script>

<?= view('Layouts/recruiter_footer', [
    'pageScripts' => [base_url('jobboard/js/recruiter-pipeline.js?v=' . @filemtime(FCPATH . 'jobboard/js/recruiter-pipeline.js'))],
]) ?>
