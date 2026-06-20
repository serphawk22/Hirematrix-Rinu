        <?= view('Layouts/candidate_header', ['title' => 'Applications']) ?>

<?php
$totalApplications = count($applications ?? []);
$sessionCandidateName = (string) (session()->get('name') ?? session()->get('user_name') ?? '');
$activeApplications = count(array_filter($applications ?? [], function ($application) {
    return !in_array($application['status'] ?? '', ['filtered_out', 'rejected', 'selected', 'withdrawn', 'hired'], true);
}));
$completedApplications = count(array_filter($applications ?? [], function ($application) {
    return in_array($application['status'] ?? '', ['selected', 'hired'], true);
}));
?>

<div class="applications-jobboard">
    <div class="container">
        <div class="page-board-header applications-page-header">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-briefcase"></i> Application tracking</span>
                <h1 class="page-board-title">My Applications</h1>
                <p class="page-board-subtitle">Track application status, recruiter activity, and next steps in a simple dashboard.</p>
            </div>
            <div class="page-board-metrics">
                <span class="hero-stat-chip"><strong
                        id="applicationsTotalCount"><?= $totalApplications ?></strong>Total</span>
                <span class="hero-stat-chip"><strong
                        id="applicationsActiveCount"><?= $activeApplications ?></strong>Active</span>
                <span class="hero-stat-chip"><strong
                        id="applicationsCompletedCount"><?= $completedApplications ?></strong>Completed</span>
            </div>
        </div>
    </div>

    <div class="container content-wrap pb-5">
        <div id="candidateApplicationsAjaxAlert"></div>
        <?php if (empty($applications)): ?>
            <div class="applications-empty-state">
                <i class="fas fa-inbox"></i>
                <h4>No Applications Yet</h4>
                <p>You have not applied to any jobs yet.</p>
                <a href="<?= base_url('jobs') ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-search"></i> Browse Jobs
                </a>
            </div>
        <?php else: ?>
            <div class="applications-shell">
                <aside class="applications-sidebar">
                    <div class="applications-sidebar-header">
                        <h5 class="mb-1">Applied Jobs</h5>
                        <p class="text-muted mb-0 small"><span
                                id="applicationsSidebarTotal"><?= $totalApplications ?></span> total, <span
                                id="applicationsSidebarActive"><?= $activeApplications ?></span> active</p>
                    </div>
              
 
                    <div class="applications-sidebar-list">
                        <?php foreach ($applications as $index => $application): ?>
                            <?php
                            $listActivity = $application['recruiter_activity'] ?? [];
                            $activityCount = (int) ($listActivity['profile_viewed_count'] ?? 0)
                                + (int) ($listActivity['contact_viewed_count'] ?? 0)
                                + (int) ($listActivity['resume_downloaded_count'] ?? 0);
                            $statusLabel = getStatusLabel((string) ($application['status'] ?? ''));
                            ?>
                            <button type="button"
                                class="application-list-item application-list-item-plain<?= $index === 0 ? ' is-active' : '' ?>"
                                data-application-target="application-<?= (int) $application['id'] ?>" data-application-list-item
                                data-application-id="<?= (int) $application['id'] ?>">
                                <div class="application-list-topline">
                                    <div class="application-list-title"><?= esc($application['job_title']) ?></div>
                                    <span
                                        class="badge status-badge badge-<?= getStatusBadgeColor($application['status']) ?> js-application-status-badge">
                                        <?= esc($statusLabel) ?>
                                    </span>
                                </div>
                                <div class="application-list-company">
                                    <?= esc($application['company'] ?? 'Company not available') ?></div>
                                <div class="application-list-meta">
                                    Applied <?= date('M d, Y', strtotime($application['applied_at'])) ?>
                                </div>
                                <div class="application-list-footnote">
                                    <span><?= $activityCount ?> recruiter activity</span>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <section class="application-detail-panel">
                    <?php foreach ($applications as $index => $application): ?>
                        <?php
                        $status = (string) ($application['status'] ?? '');
                        $statusLabel = getStatusLabel($status);
                        $statusMessage = getStatusMessage($status);
                        $recruiterActivity = $application['recruiter_activity'] ?? [];
                        $profileViewed = (int) (($recruiterActivity['profile_unique_recruiters'] ?? 0) ?: ($recruiterActivity['profile_viewed_count'] ?? 0));
                        $contactViewed = (int) (($recruiterActivity['contact_unique_recruiters'] ?? 0) ?: ($recruiterActivity['contact_viewed_count'] ?? 0));
                        $resumeDownloaded = (int) (($recruiterActivity['resume_unique_recruiters'] ?? 0) ?: ($recruiterActivity['resume_downloaded_count'] ?? 0));
                        $lastActivityAt = $recruiterActivity['last_recruiter_activity_at'] ?? null;
                        $timeline = buildApplicationTimeline($status);
                        $prep = $application['interview_prep'] ?? [];
                        $canShowCoaching = !empty($prep) && !in_array($status, ['filtered_out', 'rejected', 'withdrawn', 'selected', 'hired'], true);
                        ?>
                        <article id="application-<?= (int) $application['id'] ?>"
                            class="application-detail-card application-detail-card-plain<?= $index === 0 ? ' is-active' : '' ?>"
                            data-application-id="<?= (int) $application['id'] ?>">
                            <div class="application-detail-head application-detail-head-plain">
                                <div>
                                    <div class="application-detail-kicker">Application status</div>
                                    <h2 class="application-detail-title">
                                        <a href="<?= base_url('job/' . $application['job_id']) ?>" target="_blank"
                                            class="application-detail-title-link">
                                            <?= esc($application['job_title']) ?>
                                        </a>
                                    </h2>
                                    <div class="application-detail-submeta">
                                        <?= esc($application['company'] ?? 'Company not available') ?>
                                        <span class="mx-2">&bull;</span>
                                        Applied <?= date('M d, Y', strtotime($application['applied_at'])) ?>
                                    </div>
                                </div>
                                <div class="application-detail-status-wrap">
                                    <span class="badge status-badge badge-<?= getStatusBadgeColor($status) ?>">
                                        <?= esc($statusLabel) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="application-status-summary">
                                <div class="application-status-summary-title">Current status</div>
                                <div class="application-status-summary-text"><?= esc($statusMessage) ?></div>
                            </div>

                            <div class="application-detail-grid">
                                <div class="application-detail-main">
                                    <div class="application-section-card">
                                        <div class="application-section-title">Application Timeline</div>
                                        <div class="application-timeline-list">
                                            <?php foreach ($timeline as $step): ?>
                                                <div
                                                    class="application-timeline-item <?= !empty($step['is_done']) ? 'is-done' : '' ?> <?= !empty($step['is_current']) ? 'is-current' : '' ?>">
                                                    <div class="application-timeline-dot"></div>
                                                    <div class="application-timeline-content">
                                                        <div class="application-timeline-label"><?= esc($step['label']) ?></div>
                                                        <div class="application-timeline-note"><?= esc($step['note']) ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="application-section-card">
                                        <div class="application-section-title">Recruiter Activity</div>
                                        <div class="application-activity-grid">
                                            <div class="application-activity-item">
                                                <span class="application-activity-value"><?= $profileViewed ?></span>
                                                <span class="application-activity-label">Profile viewed</span>
                                            </div>
                                            <div class="application-activity-item">
                                                <span class="application-activity-value"><?= $contactViewed ?></span>
                                                <span class="application-activity-label">Contact viewed</span>
                                            </div>
                                            <div class="application-activity-item">
                                                <span class="application-activity-value"><?= $resumeDownloaded ?></span>
                                                <span class="application-activity-label">Resume downloaded</span>
                                            </div>
                                        </div>
                                        <?php if (!empty($lastActivityAt)): ?>
                                            <div class="application-activity-note">Last recruiter activity:
                                                <?= date('M d, Y h:i A', strtotime($lastActivityAt)) ?></div>
                                        <?php else: ?>
                                            <div class="application-activity-note">No recruiter activity recorded yet.</div>
                                        <?php endif; ?>
                                    </div>

                                </div>

                                <aside class="application-detail-side">
                                    <div class="application-section-card">
                                        <div class="application-section-title">Application Details</div>
                                        <div class="application-meta-list">
                                            <div class="application-meta-row">
                                                <span>Job title</span>
                                                <strong><?= esc($application['job_title']) ?></strong>
                                            </div>
                                            <div class="application-meta-row">
                                                <span>Company</span>
                                                <strong><?= esc($application['company'] ?? 'Not available') ?></strong>
                                            </div>
                                            <div class="application-meta-row">
                                                <span>Applied on</span>
                                                <strong><?= date('M d, Y', strtotime($application['applied_at'])) ?></strong>
                                            </div>
                                            <?php if (!empty($application['resume_version_title'])): ?>
                                                <div class="application-meta-row">
                                                    <span>Resume used</span>
                                                    <strong><?= esc($application['resume_version_title']) ?></strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="application-section-card">
                                        <div class="application-section-title">Next Step</div>
                                        <div class="application-next-step-copy"><?= esc($statusMessage) ?></div>
                                        <div class="detail-actions detail-actions-plain">
                                            <?php if ($application['status'] === 'applied'): ?>
                                                <?php $policy = strtoupper($application['ai_interview_policy'] ?? 'REQUIRED_HARD'); ?>
                                                <?php if ($policy === 'OFF'): ?>
                                                    <span class="badge badge-info p-2">AI interview disabled for this job</span>
                                                <?php else: ?>
                                                    <form action="<?= base_url('ai_interview/index.php') ?>" method="post" class="candidate-inline-form" ref="_blank">
                                                        <input type="hidden" name="candidate_id"
                                                               value="<?= htmlspecialchars($application['candidate_id'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="candidate_name"
                                                               value="<?= htmlspecialchars($sessionCandidateName, ENT_QUOTES, 'UTF-8') ?>">
                                                    
                                                        <input type="hidden" name="job_title"
                                                               value="<?= htmlspecialchars($application['job_title'], ENT_QUOTES, 'UTF-8') ?>">
                                                                <input type="hidden" name="jobid"
           value="<?= htmlspecialchars($application['job_id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    
                                                        <input type="input" name="highlight_skills"
                                                               value="<?= htmlspecialchars($application['resume_version_highlight_skills'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="experience"
                                                               value="<?= esc($calculatedExperience['level'] ?? '') ?>">
                                                    
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <?= $policy === 'OPTIONAL' ? 'Start AI Interview (Optional)' : 'Start AI Interview' ?>
                                                        </button>
                                                    </form> 
                                                <?php endif; ?>
                                            <?php elseif ($status === 'ai_interview_completed'): ?>
                                                <span class="badge badge-info p-2">AI interview submitted. Recruiter review is in progress.</span>
                                            <?php elseif ($status === 'shortlisted'): ?>
                                                <a href="<?= base_url('candidate/book-slot/' . $application['id']) ?>"
                                                    class="btn btn-warning btn-sm btn-block"><i class="fas fa-calendar-plus"></i>
                                                    Book Interview Slot</a>
                                            <?php elseif ($status === 'interview_slot_booked'): ?>
                                                <a href="<?= base_url('candidate/my-bookings') ?>"
                                                    class="btn btn-info btn-sm text-white btn-block"><i
                                                        class="fas fa-calendar-check"></i> View Interview Schedule</a>
                                            <?php elseif ($canShowCoaching): ?>
                                                <a href="<?= base_url('candidate/applications/' . (int) $application['id'] . '/mock-interview') ?>"
                                                    class="btn btn-outline-primary btn-sm btn-block"><i class="fas fa-comments"></i>
                                                    Continue Preparation</a>
                                            <?php endif; ?>

                                            <?php if (!in_array($status, ['withdrawn', 'filtered_out', 'rejected', 'selected', 'hired', 'interview_slot_booked'], true)): ?>
                                                <form
                                                    action="<?= base_url('candidate/applications/withdraw/' . $application['id']) ?>"
                                                    method="post" onsubmit="return confirm('Withdraw this application?');"
                                                    class="js-withdraw-application-form"
                                                    data-application-id="<?= (int) $application['id'] ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm btn-block"><i
                                                            class="fas fa-times-circle"></i> Withdraw Application</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
function getStatusBadgeColor($status)
{
    $colors = [
        'applied' => 'warning',
        'ai_interview_completed' => 'info',
        'shortlisted' => 'success',
        'hold' => 'secondary',
        'filtered_out' => 'dark',
        'rejected' => 'danger',
        'interview_slot_booked' => 'warning',
        'selected' => 'success',
        'withdrawn' => 'secondary',
        'hired' => 'success',
    ];

    return $colors[$status] ?? 'secondary';
}

function getStatusLabel(string $status): string
{
    $labels = [
        'applied' => 'Applied',
        'ai_interview_completed' => 'AI Interview Completed',
        'shortlisted' => 'Shortlisted',
        'hold' => 'On Hold',
        'filtered_out' => 'Filtered Out',
        'rejected' => 'Rejected',
        'interview_slot_booked' => 'Interview Booked',
        'selected' => 'Selected',
        'withdrawn' => 'Withdrawn',
        'hired' => 'Hired',
    ];

    return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
}

function getStatusMessage(string $status): string
{
    return match ($status) {
        'applied' => 'Your application has been submitted and is under recruiter review.',
        'ai_interview_completed' => 'Your AI interview is complete. The recruiter is reviewing your results.',
        'shortlisted' => 'You have been shortlisted. Book your next interview slot to continue.',
        'hold' => 'Your application is on hold for now. Recruiters may review it again later.',
        'filtered_out' => 'This application did not meet one or more mandatory screening criteria.',
        'interview_slot_booked' => 'Your interview slot is booked. Check your schedule for timing details.',
        'selected' => 'You have been selected for this role.',
        'hired' => 'You have been marked as hired for this role.',
        'rejected' => 'This application has been closed by the recruiter.',
        'withdrawn' => 'You withdrew this application.',
        default => 'Your application is being processed.',
    };
}

function buildApplicationTimeline(string $status): array
{
    $steps = [
        ['key' => 'applied', 'label' => 'Applied', 'note' => 'Application submitted successfully.'],
        ['key' => 'ai_interview_completed', 'label' => 'AI Interview Completed', 'note' => 'Your AI interview was submitted successfully.'],
        ['key' => 'shortlisted', 'label' => 'Shortlisted', 'note' => 'Recruiter moved your profile forward.'],
        ['key' => 'interview_slot_booked', 'label' => 'Interview Booked', 'note' => 'Interview slot scheduled.'],
        ['key' => 'selected', 'label' => 'Selected', 'note' => 'You cleared the hiring process.'],
    ];

    $progressMap = [
        'applied' => 0,
        'hold' => 0,
        'filtered_out' => 0,
        'rejected' => 0,
        'withdrawn' => 0,
        'ai_interview_completed' => 1,
        'shortlisted' => 2,
        'interview_slot_booked' => 3,
        'selected' => 4,
        'hired' => 4,
    ];

    $currentIndex = $progressMap[$status] ?? 0;

    foreach ($steps as $index => &$step) {
        $step['is_done'] = $index < $currentIndex;
        $step['is_current'] = $index === $currentIndex && !in_array($status, ['filtered_out', 'rejected', 'withdrawn', 'hold'], true);
    }
    unset($step);

    if ($status === 'hold') {
        $steps[] = ['key' => 'hold', 'label' => 'On Hold', 'note' => 'Recruiter has paused the application.', 'is_done' => false, 'is_current' => true];
    } elseif ($status === 'filtered_out') {
        $steps[] = ['key' => 'filtered_out', 'label' => 'Filtered Out', 'note' => 'Mandatory screening criteria were not met.', 'is_done' => false, 'is_current' => true];
    } elseif ($status === 'rejected') {
        $steps[] = ['key' => 'rejected', 'label' => 'Rejected', 'note' => 'Application was not moved forward.', 'is_done' => false, 'is_current' => true];
    } elseif ($status === 'withdrawn') {
        $steps[] = ['key' => 'withdrawn', 'label' => 'Withdrawn', 'note' => 'You withdrew this application.', 'is_done' => false, 'is_current' => true];
    } elseif ($status === 'hired') {
        $steps[] = ['key' => 'hired', 'label' => 'Hired', 'note' => 'You joined the role successfully.', 'is_done' => false, 'is_current' => true];
    }

    return $steps;
}
?>

<?= view('Layouts/candidate_footer') ?>
    
