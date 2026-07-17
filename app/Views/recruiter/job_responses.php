<?= view('Layouts/recruiter_header', [
    'title' => 'Jobs & Responses',
    'pageStyles' => [base_url('jobboard/css/recruiter-jobs.min.css?v=' . @filemtime(FCPATH . 'jobboard/css/recruiter-jobs.min.css'))],
]) ?>

  <div
    id="recruiterJobsPage"
    class="recruiter-jobs-jobboard"
    data-status-url-base="<?= base_url('recruiter/applications/update-status/') ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-hash="<?= csrf_hash() ?>"
>
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <h1 class="page-board-title">Jobs Management</h1>
            <p class="page-board-subtitle">Review job posts, applicant volume, and hiring status from one workspace.</p>
        </div>
        <div class="page-board-actions">
            <a href="<?= base_url('recruiter/post_job') ?>" class="btn btn-primary">Post New Job</a>
        </div>
    </div>

    <div id="jobs-list">
            <section class="hm-alert-center" aria-label="Recruiter alerts">
                <div class="hm-alert-center-head">
                    <h2 class="hm-alert-center-title"><i class="fas fa-bell"></i> Attention Inbox</h2>
                    <span class="hm-alert-count"><?= count((array) ($recruiterAlerts ?? [])) ?> active</span>
                </div>
                <?php if (empty($recruiterAlerts)): ?>
                    <div class="hm-alert-empty">No urgent recruiter alerts right now.</div>
                <?php else: ?>
                    <div class="hm-alert-list">
                        <?php foreach ((array) $recruiterAlerts as $alert): ?>
                            <a class="hm-alert-item" href="<?= esc($alert['url'] ?? '#') ?>">
                                <span class="hm-alert-topline">
                                    <span class="hm-alert-title"><?= esc($alert['title'] ?? '') ?></span>
                                    <span class="hm-alert-tone is-<?= esc($alert['tone'] ?? 'info') ?>"></span>
                                </span>
                                <span class="hm-alert-meta"><?= esc($alert['meta'] ?? '') ?></span>
                                <span class="hm-alert-detail"><?= esc($alert['detail'] ?? '') ?></span>
                                <span class="hm-alert-action"><?= esc($alert['action'] ?? 'Open') ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="card bg-light recruiter-filter-card">
                <div class="card-body">
                    <form action="<?= base_url('recruiter/jobs') ?>" method="get" class="recruiter-jobs-filter-grid">
                        <div>
                            <label class="sr-only">Search Jobs</label>
                            <input type="text" name="q" id="q" class="form-control" placeholder="Search by title..." value="<?= esc($filters['q']) ?>">
                        </div>
                        <div>
                            <label class="sr-only">Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active Jobs</option>
                                <option value="closed" <?= $filters['status'] === 'closed' ? 'selected' : '' ?>>Closed Jobs</option>
                                <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>All Jobs</option>
                            </select>
                        </div>
                        <div>
                            <label class="sr-only">Job posted by</label>
                            <select name="posted_by" class="form-control">
                                <option value="me" <?= ($filters['posted_by'] ?? 'me') === 'me' ? 'selected' : '' ?>>Posted by me</option>
                                <?php if (!empty($postedByOptions) && count($postedByOptions) > 1): ?>
                                    <option value="all" <?= ($filters['posted_by'] ?? 'me') === 'all' ? 'selected' : '' ?>>All company recruiters</option>
                                    <?php foreach ($postedByOptions as $postedByRecruiter): ?>
                                        <?php
                                            $postedById = (int) ($postedByRecruiter['id'] ?? 0);
                                            if ($postedById === (int) session()->get('user_id')) {
                                                continue;
                                            }
                                            $postedByLabel = trim((string) ($postedByRecruiter['name'] ?? ''));
                                            if ($postedByLabel === '') {
                                                $postedByLabel = (string) ($postedByRecruiter['email'] ?? 'Recruiter');
                                            }
                                        ?>
                                        <option value="<?= $postedById ?>" <?= (string) ($filters['posted_by'] ?? 'me') === (string) $postedById ? 'selected' : '' ?>>
                                            <?= esc($postedByLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="recruiter-jobs-filter-actions">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="alert alert-info">No jobs found matching your criteria.</div>
            <?php else: ?>
                <form method="post" action="<?= base_url('recruiter/jobs/bulk-close') ?>" id="jobsBulkCloseForm" class="recruiter-jobs-list-card">
                    <?= csrf_field() ?>
                    <div class="jobs-bulkbar">
                        <div class="jobs-bulkbar-left">
                            <label class="jobs-select-control" for="jobsSelectAll">
                                <input type="checkbox" id="jobsSelectAll">
                                <span>Select all open jobs</span>
                            </label>
                            <span class="jobs-selected-count" id="jobsSelectedCount">0 selected</span>
                        </div>
                        <div class="jobs-bulkbar-right">
                            <button type="submit" class="jobs-bulk-close" id="jobsBulkCloseButton" disabled>
                                <i class="fas fa-ban" aria-hidden="true"></i>
                                <span>Close selected</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive recruiter-table-card">
                    <table class="table table-hover bg-white recruiter-jobs-table">
                        <thead>
                            <tr>
                                <th class="job-select-cell"></th>
                                <th>Job title</th>
                                <th>Posted by</th>
                                <th>Status</th>
                                <th>Responses</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                                <?php
                                    $isOpen = ($job['status'] ?? '') === 'open';
                                    $attentionLevel = (string) ($job['attention_level'] ?? 'quiet');
                                    $showAttention = in_array($attentionLevel, ['critical', 'watch'], true);
                                    $attentionLabel = $attentionLevel === 'critical' ? 'Needs review' : 'Watch';
                                    $attentionFacts = array_values((array) ($job['attention_facts'] ?? []));
                                    $attentionChips = [];
                                    $shortlistedCount = (int) ($job['shortlisted_count'] ?? 0);
                                    $applicantCount = (int) ($job['applicant_count'] ?? 0);
                                    $averageAtsScore = (int) ($job['average_ats_score'] ?? 0);
                                    $attentionChips[] = $shortlistedCount . ' shortlisted';
                                    if ((int) ($job['applicant_count'] ?? 0) > 0) {
                                        $attentionChips[] = $averageAtsScore . '% avg match';
                                    }
                                    $attentionChips = array_slice(array_values(array_unique(array_merge($attentionChips, $attentionFacts))), 0, 3);
                                    $postedAt = !empty($job['created_at']) ? date('d M Y', strtotime((string) $job['created_at'])) : null;
                                    $postedByName = trim((string) ($job['posted_by_name'] ?? ''));
                                    $postedByEmail = trim((string) ($job['posted_by_email'] ?? ''));
                                    $isOwnJob = (int) ($job['recruiter_id'] ?? 0) === (int) session()->get('user_id');
                                ?>
                                <tr class="hm-job-row" data-href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>">
                                    <td class="job-select-cell">
                                        <input
                                            type="checkbox"
                                            class="job-row-check"
                                            name="job_ids[]"
                                            value="<?= (int) $job['id'] ?>"
                                            aria-label="Select <?= esc($job['title']) ?>"
                                            <?= ($isOpen && $isOwnJob) ? '' : 'disabled' ?>
                                        >
                                    </td>
                                    <td class="job-title-cell">
                                        <div class="job-title-row">
                                            <span class="job-title"><?= esc($job['title']) ?></span>
                                            <?php if ($showAttention): ?>
                                                <span class="hm-attention-pill is-<?= esc($attentionLevel) ?>"><?= esc($attentionLabel) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="job-subtitle">
                                            <span><?= esc($job['location']) ?></span>
                                            <?php if ($postedAt): ?>
                                                <span class="job-dot">&bull;</span>
                                                <span>Posted <?= esc($postedAt) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($showAttention): ?>
                                            <div class="hm-attention-stack">
                                                <?php foreach ($attentionChips as $chip): ?>
                                                    <span class="hm-attention-pill is-soft"><?= esc($chip) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="job-response-meta"><?= $isOwnJob ? 'Me' : esc($postedByName !== '' ? $postedByName : 'Recruiter') ?></span>
                                        <?php if (!$isOwnJob && $postedByEmail !== ''): ?>
                                            <span class="job-response-meta"><?= esc($postedByEmail) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="job-status-pill <?= $isOpen ? 'is-open' : 'is-closed' ?>"><?= esc(ucfirst((string) $job['status'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="job-response-count"><?= (int) ($job['applicant_count'] ?? 0) ?></span>
                                        <span class="job-response-meta">Total responses</span>
                                        <span class="job-response-meta"><?= (int) ($job['shortlisted_count'] ?? 0) ?> shortlisted</span>
                                        <?php if ((int) ($job['applicant_count'] ?? 0) > 0): ?>
                                            <span class="job-response-meta"><?= (int) ($job['average_ats_score'] ?? 0) ?>% avg match</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right hm-job-actions-cell">
                                        <div class="hm-job-action-group">
                                            <div class="hm-job-dropdown">
                                                <button class="btn btn-sm hm-job-more-btn" type="button" title="More actions" aria-label="More actions for <?= esc($job['title']) ?>">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <circle cx="3" cy="8" r="1.5" fill="currentColor"/>
      <circle cx="8" cy="8" r="1.5" fill="currentColor"/>
      <circle cx="13" cy="8" r="1.5" fill="currentColor"/>
    </svg>
                                                </button>
                                                <div class="hm-job-dropdown-menu">
                                                    <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>">View Pipeline</a>
                                                    <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>#leaderboard">Leaderboard</a>
                                                    <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/preview/' . $job['id']) ?>" target="_blank">Preview</a>
                                                    <?php if ($isOwnJob): ?>
                                                        <a class="hm-job-dropdown-item" href="<?= base_url('recruiter/jobs/edit/' . $job['id']) ?>">Edit Job</a>
                                                        <div class="hm-job-dropdown-separator"></div>
                                                        <?php if ($isOpen): ?>
                                                            <button
                                                                type="submit"
                                                                class="hm-job-dropdown-item is-danger"
                                                                formaction="<?= base_url('recruiter/jobs/close/' . (int) $job['id']) ?>"
                                                                formmethod="post"
                                                                data-job-action="close"
                                                                onclick="return confirm('Close this job?')"
                                                            >Close Job</button>
                                                    <?php else: ?>
                                                            <button
                                                                type="submit"
                                                                class="hm-job-dropdown-item"
                                                                formaction="<?= base_url('recruiter/jobs/reopen/' . (int) $job['id']) ?>"
                                                                formmethod="post"
                                                                data-job-action="reopen"
                                                            >Reopen Job</button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </form>

                <?php if ($pager->getPageCount() > 1): ?>
                    <div class="mt-4">
                        <?= $pager->links('default', 'portal_full') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Row click → pipeline (skip actions cell)
    document.querySelectorAll('.hm-job-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (
                e.target.closest('.hm-job-actions-cell') ||
                e.target.closest('.job-row-check') ||
                e.target.closest('.jobs-select-control') ||
                e.target.closest('a') ||
                e.target.closest('button') ||
                e.target.closest('input')
            ) {
                return;
            }
            window.location = row.dataset.href;
        });
    });

    // … button toggle
    var bulkForm = document.getElementById('jobsBulkCloseForm');
    var selectAll = document.getElementById('jobsSelectAll');
    var selectedCount = document.getElementById('jobsSelectedCount');
    var bulkButton = document.getElementById('jobsBulkCloseButton');
    var rowChecks = Array.prototype.slice.call(document.querySelectorAll('.job-row-check:not(:disabled)'));

    function updateBulkState() {
        var checked = rowChecks.filter(function (input) { return input.checked; }).length;
        if (selectedCount) {
            selectedCount.textContent = checked + ' selected';
        }
        if (bulkButton) {
            bulkButton.disabled = checked === 0;
        }
        if (selectAll) {
            selectAll.checked = rowChecks.length > 0 && checked === rowChecks.length;
            selectAll.indeterminate = checked > 0 && checked < rowChecks.length;
            selectAll.disabled = rowChecks.length === 0;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowChecks.forEach(function (input) {
                input.checked = selectAll.checked;
            });
            updateBulkState();
        });
    }

    rowChecks.forEach(function (input) {
        input.addEventListener('change', updateBulkState);
        input.addEventListener('click', function (e) { e.stopPropagation(); });
    });

    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            if (e.submitter && e.submitter.matches('[data-job-action]')) {
                return;
            }
            var checked = rowChecks.filter(function (input) { return input.checked; }).length;
            if (checked === 0 || !confirm('Close ' + checked + ' selected job' + (checked === 1 ? '?' : 's?'))) {
                e.preventDefault();
            }
        });
    }

    updateBulkState();

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.hm-job-more-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            var menu = btn.parentElement.querySelector('.hm-job-dropdown-menu');
            var isOpen = menu.style.display === 'block';
            document.querySelectorAll('.hm-job-dropdown-menu').forEach(function (m) { m.style.display = 'none'; });
            menu.style.display = isOpen ? 'none' : 'block';
            return;
        }
        // close on outside click
        if (!e.target.closest('.hm-job-dropdown')) {
            document.querySelectorAll('.hm-job-dropdown-menu').forEach(function (m) { m.style.display = 'none'; });
        }
    });
});
</script>
<?= view('Layouts/recruiter_footer', [
    'pageScripts' => [base_url('jobboard/js/recruiter-jobs.js?v=' . @filemtime(FCPATH . 'jobboard/js/recruiter-jobs.js'))],
]) ?>
