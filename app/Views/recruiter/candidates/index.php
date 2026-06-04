                        <?= view('Layouts/recruiter_header', ['title' => 'Candidate Database']) ?>

<div class="recruiter-candidates-jobboard">
<div class="container-fluid py-5">
    <?php
    $selectedJobTitle = (string) ($selectedJob['title'] ?? '');
    $candidateCount = count($candidates ?? []);
    $aiSuggestionCount = count($aiSuggestions ?? []);
    ?>

    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-database"></i> Recruiter candidate search</span>
            <h1 class="page-board-title">Candidate Database</h1>
            <p class="page-board-subtitle">Search and discover candidates beyond direct applicants. Compare profiles and jump into the candidate workspace.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success recruiter-alert"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger recruiter-alert"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card shadow-sm recruiter-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h6 class="m-0 font-weight-bold text-primary">Search Filters</h6>
                    <p class="text-muted mb-0">Narrow down the candidate database by skill, experience, job fit, and resume availability.</p>
                </div>
            </div>

            <form method="get" action="<?= base_url('recruiter/candidates') ?>" class="recruiter-candidate-filter-form">
                <div class="row">
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">Keyword</label>
                        <input type="text" name="keyword" class="form-control" value="<?= esc($filters['keyword'] ?? '') ?>" placeholder="Name / Email / Skill">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Skills</label>
                        <input type="text" name="skills" class="form-control" value="<?= esc($filters['skills'] ?? '') ?>" placeholder="e.g. PHP">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= esc($filters['location'] ?? '') ?>" placeholder="City / State">
                    </div>
                    <div class="col-md-1">
                        <label class="small text-muted mb-1">Exp Min (Years)</label>
                        <input type="number" step="0.5" min="0" name="exp_min" class="form-control" value="<?= esc($filters['exp_min'] ?? '') ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="small text-muted mb-1">Exp Max (Years)</label>
                        <input type="number" step="0.5" min="0" name="exp_max" class="form-control" value="<?= esc($filters['exp_max'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Job Role</label>
                        <select name="job_id" class="form-control">
                            <option value="">Select Job</option>
                            <?php foreach (($recruiterJobs ?? []) as $job): ?>
                                <option value="<?= (int) $job['id'] ?>" <?= (int) ($filters['job_id'] ?? 0) === (int) $job['id'] ? 'selected' : '' ?>>
                                    <?= esc($job['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Resume</label>
                        <select name="resume" class="form-control">
                            <option value="" <?= ($filters['resume'] ?? '') === '' ? 'selected' : '' ?>>All</option>
                            <option value="yes" <?= ($filters['resume'] ?? '') === 'yes' ? 'selected' : '' ?>>With Resume</option>
                            <option value="no" <?= ($filters['resume'] ?? '') === 'no' ? 'selected' : '' ?>>Without Resume</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="mt-3 recruiter-filter-actions">
                    <a href="<?= base_url('recruiter/candidates') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($selectedJob)): ?>
        <div class="card shadow-sm recruiter-ai-suggestions-card mb-4">
            <div class="card-header py-3 bg-gradient-primary text-white">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-robot"></i> AI Candidate Suggestions for <?= esc($selectedJob['title'] ?? 'Selected Job') ?></h6>
            </div>
            <div class="card-body">
                <?php if (empty($aiSuggestions)): ?>
                    <p class="text-muted mb-0">No suitable candidates found for this role.</p>
                <?php else: ?>
                <form method="post" action="<?= base_url('recruiter/candidates/invite-job/bulk') ?>" class="mb-3 recruiter-bulk-invite-form" id="recruiterBulkInviteForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="job_id" value="<?= (int) ($selectedJob['id'] ?? 0) ?>">
                        <input type="hidden" name="return_to" value="<?= current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '') ?>">
                        <div class="recruiter-bulk-invite-bar">
                            <div>
                                <div class="font-weight-bold text-dark">Invite multiple candidates</div>
                                <div class="small text-muted">Select candidates below, then send one role invitation to everyone at once.</div>
                            </div>
                            <div class="recruiter-bulk-invite-actions">
                                <textarea name="message" class="form-control recruiter-bulk-invite-note" rows="2" maxlength="500" placeholder="Optional shared note for all selected candidates"></textarea>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane mr-1"></i> Invite Selected
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover recruiter-candidates-table">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 44px;">
                                        <input type="checkbox" class="js-select-all-candidates" aria-label="Select all candidates">
                                    </th>
                                    <th>Candidate</th>
                                    <th>Score</th>
                                    <th>Experience</th>
                                    <th>Skills</th>
                                    <th>ATS Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aiSuggestions as $candidate): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="candidate_ids[]" value="<?= (int) $candidate['id'] ?>" class="js-candidate-checkbox" aria-label="Select <?= esc($candidate['name'] ?? 'candidate') ?>" form="recruiterBulkInviteForm">
                                        </td>
                                        <td>
                                            <strong><?= esc($candidate['name'] ?? '-') ?></strong><br>
                                            <small class="text-muted"><?= esc($candidate['email'] ?? '-') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-success"><?= esc((string) ($candidate['match_score'] ?? 0)) ?>%</span>
                                        </td>
                                        <td><?= esc($candidate['experience_display'] ?? '-') ?></td>
                                        <td><small><?= esc($candidate['skill_name'] ?? '-') ?></small></td>
                                        <td><small><?= esc($candidate['match_reason'] ?? '-') ?></small></td>
                                        <td>
                                            <a href="<?= base_url('recruiter/candidate/' . $candidate['id'] . '?job_id=' . (int) ($selectedJob['id'] ?? 0)) ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-user"></i> View
                                            </a>
                                            <form method="post" action="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/invite-job') ?>" class="d-inline-block">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="job_id" value="<?= (int) ($selectedJob['id'] ?? 0) ?>">
                                                <input type="hidden" name="return_to" value="<?= current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '') ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-paper-plane"></i> Invite
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php $aiModeForJob = !empty($selectedJob); ?>
    <?php if (!$aiModeForJob): ?>
        <div class="card shadow-sm recruiter-table-card">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-users"></i> Candidates (<?= count($candidates ?? []) ?> on this page)</h6>
            </div>
            <div class="card-body">
                <?php if (empty($candidates)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <h5>No candidates found</h5>
                        <p class="text-muted mb-0">Try adjusting your filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover recruiter-candidates-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Candidate</th>
                                    <th>Location</th>
                                    <th>Experience</th>
                                    <th>Skills</th>
                                    <th>Resume</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                   <tr onclick="window.location='<?= base_url('recruiter/candidate/' . $candidate['id'] . '/view-contact') ?>'" style="cursor:pointer;">
                                        <td>
                                            <strong><?= esc($candidate['name'] ?? '-') ?></strong><br>
                                            <small class="text-muted"><?= esc($candidate['email'] ?? '-') ?></small>
                                        </td>
                                        <td><?= esc($candidate['location'] ?? '-') ?></td>
                                        <td><?= esc($candidate['experience_display'] ?? '-') ?></td>
                                        <td>
                                            <?php if (!empty($candidate['skill_name'])): ?>
                                                <small><?= esc($candidate['skill_name']) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($candidate['resume_path'])): ?>
                                                <span class="badge badge-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Not Uploaded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= !empty($candidate['created_at']) ? date('M d, Y', strtotime($candidate['created_at'])) : '-' ?></td>
                                        <td>
                                             <div class="application-actions-wrap">
    <a href="<?= base_url('recruiter/candidate/' . $candidate['id']) ?>" class="btn btn-sm btn-primary">
        <i class="fas fa-user"></i> View Profile
    </a>

    <?php if (!empty($candidate['resume_path'])): ?>
        <a href="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/download-resume') ?>" class="btn btn-sm btn-success">
            <i class="fas fa-download"></i> Resume
        </a>
    <?php endif; ?>
    <?php if (!empty($selectedJob['id'])): ?>
                                                    <form method="post" action="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/invite-job') ?>" class="d-inline-block">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="job_id" value="<?= (int) $selectedJob['id'] ?>">
                                                        <input type="hidden" name="return_to" value="<?= current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '') ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-paper-plane"></i> Invite
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
</div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (isset($pager) && is_object($pager) && method_exists($pager, 'links')): ?>
                        <div class="mt-3">
                            <?= $pager->links() ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.querySelector('.js-select-all-candidates');
    if (!selectAll) {
        return;
    }

    const checkboxes = Array.from(document.querySelectorAll('.js-candidate-checkbox'));
    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
    });

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            selectAll.checked = checkboxes.length > 0 && checkboxes.every(function (item) {
                return item.checked;
            });
        });
    });
});
</script>


<?= view('Layouts/recruiter_footer') ?>
            
