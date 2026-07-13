<?= view('Layouts/recruiter_header', ['title' => 'Candidate Database']) ?>
<?php
$selectedJobTitle = (string) ($selectedJob['title'] ?? '');
$candidateCount = count($candidates ?? []);
$aiSuggestionCount = count($aiSuggestions ?? []);
$returnTo = current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
$hasSelectableCandidates = $candidateCount > 0 || $aiSuggestionCount > 0;
?>
<div class="recruiter-candidates-jobboard"
     id="recruiterCandidatePoolPage"
     data-email-url="<?= base_url('recruiter/candidates/send-bulk-email') ?>"
     data-csrf-name="<?= csrf_token() ?>"
     data-csrf-hash="<?= csrf_hash() ?>"
     data-job-title="<?= esc($selectedJobTitle !== '' ? $selectedJobTitle : 'this opportunity') ?>">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">Candidate Database</h1>
            <p class="page-board-subtitle">Search and discover candidates beyond direct applicants. Compare profiles and jump into the workspace.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success recruiter-alert"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger recruiter-alert"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card shadow-sm recruiter-filter-card recruiter-rounded-hidden">
        <div class="card-body">
            <div class="recruiter-filter-compact-head">
                <h6 class="recruiter-filter-compact-title">Search Filters</h6>
                <p class="recruiter-filter-compact-hint">Skill, location, experience, and job fit</p>
            </div>

            <form method="get" action="<?= base_url('recruiter/candidates') ?>" class="recruiter-candidate-filter-form" >
                <div class="recruiter-filter-grid">
                    <div>
                        <label class="sr-only">Keyword</label>
                        <input type="text" name="keyword" class="form-control" value="<?= esc($filters['keyword'] ?? '') ?>" placeholder="Name / Email / Skill">
                    </div>
                    <div>
                        <label class="sr-only">Skills</label>
                        <input type="text" name="skills" class="form-control" value="<?= esc($filters['skills'] ?? '') ?>" placeholder="e.g. PHP">
                    </div>
                    <div>
                        <label class="sr-only">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= esc($filters['location'] ?? '') ?>" placeholder="City / State">
                    </div>
                    <div>
                        <label class="sr-only">Experience minimum in years</label>
                        <input type="number" step="0.5" min="0" name="exp_min" class="form-control" value="<?= esc($filters['exp_min'] ?? '') ?>" placeholder="Min yrs">
                    </div>
                    <div>
                        <label class="sr-only">Experience maximum in years</label>
                        <input type="number" step="0.5" min="0" name="exp_max" class="form-control" value="<?= esc($filters['exp_max'] ?? '') ?>" placeholder="Max yrs">
                    </div>
                    <div class="filter-job">
                        <label class="sr-only">Job Role</label>
                        <select name="job_id" class="form-control">
                            <option value="">Select Job</option>
                            <?php foreach (($recruiterJobs ?? []) as $job): ?>
                                <option value="<?= (int) $job['id'] ?>" <?= (int) ($filters['job_id'] ?? 0) === (int) $job['id'] ? 'selected' : '' ?>>
                                    <?= esc($job['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="recruiter-filter-actions-compact">
                        <button type="submit" class="btn btn-outline-primary btn-icon" aria-label="Search candidates" title="Search candidates">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="<?= base_url('recruiter/candidates') ?>" class="btn btn-outline-primary">Reset</a>
                    </div>
                </div> 
            </form>
        </div>
    </div>

    <?php if ($hasSelectableCandidates): ?>
        <form method="post" action="<?= base_url('recruiter/candidates/invite-job/bulk') ?>"
              class="recruiter-bulk-invite-form mb-4" id="recruiterBulkInviteForm">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="<?= esc($returnTo) ?>">
            <div class="recruiter-bulk-invite-bar">
                <div>
                    <div class="recruiter-bulk-invite-title">Bulk candidate actions</div>
                    <div class="small text-muted">
                        Select candidates from the table. <span class="recruiter-bulk-selection-count" id="bulkCandidateCount">0 selected</span>
                    </div>
                </div>
                <div class="recruiter-bulk-invite-actions">
                    <select name="job_id" class="form-control" id="bulkInviteJobSelect" aria-label="Select job for invitations">
                        <option value="">Select Job to Invite</option>
                        <?php foreach (($recruiterJobs ?? []) as $job): ?>
                            <option value="<?= (int) $job['id'] ?>" <?= (int) ($filters['job_id'] ?? 0) === (int) $job['id'] ? 'selected' : '' ?>>
                                <?= esc($job['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="message" class="form-control recruiter-bulk-invite-note" rows="1" maxlength="500"
                              placeholder="Optional invite note"></textarea>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary recruiter-bulk-invite-submit" id="bulkInviteSubmit" disabled>
                            <i class="fas fa-paper-plane mr-1"></i> Invite
                        </button>
                        <button type="button" class="btn btn-outline-primary recruiter-bulk-invite-submit" id="bulkEmailButton" disabled>
                            <i class="fas fa-at mr-1"></i> Email
                        </button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <?php if (!empty($selectedJob)): ?>
        <div class="card shadow-sm recruiter-ai-suggestions-card mb-4 recruiter-rounded-hidden">
            <div class="card-header py-3 bg-gradient-primary text-white">
                <h6 class="title mb-3"> AI Candidate Suggestions for <?= esc($selectedJob['title'] ?? 'Selected Job') ?></h6>
            </div>
            <div class="card-body" >
                <?php if (empty($aiSuggestions)): ?>
                    <p class="text-muted mb-0">No suitable candidates found for this role.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover recruiter-candidates-table">
                            <thead class="thead-light">
                                <tr>
                                    <th class="recruiter-select-col">
                                        <input type="checkbox" class="recruiter-candidate-checkbox js-select-all-candidates"
                                               aria-label="Select all suggested candidates">
                                    </th>
                                    <th>Candidate</th>
                                    <th>Score</th>
                                    <th>Experience</th>
                                    <th>Skills</th>
                                    <th>AI Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aiSuggestions as $candidate): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="candidate_ids[]" value="<?= (int) $candidate['id'] ?>"
                                                   class="recruiter-candidate-checkbox js-candidate-checkbox"
                                                   data-email="<?= esc($candidate['email'] ?? '') ?>"
                                                   data-name="<?= esc($candidate['name'] ?? '') ?>"
                                                   aria-label="Select <?= esc($candidate['name'] ?? 'candidate') ?>"
                                                   form="recruiterBulkInviteForm">
                                        </td>
                                        <td>
                                            <strong><?= esc($candidate['name'] ?? '-') ?></strong><br>
                                            <small class="text-muted"><?= esc($candidate['email'] ?? '-') ?></small>
                                        </td>
                                        <td>
                                            <span class="recruiter-match-score"><?= esc((string) ($candidate['match_score'] ?? 0)) ?>%</span>
                                        </td>
                                        <td><?= esc($candidate['experience_display'] ?? '-') ?></td>
                                        <td><small><?= esc($candidate['skill_name'] ?? '-') ?></small></td>
                                        <td><small><?= esc($candidate['match_reason'] ?? '-') ?></small></td>
                                        <td>
                                            <a href="<?= base_url('recruiter/candidate/' . $candidate['id'] . '?job_id=' . (int) ($selectedJob['id'] ?? 0)) ?>" class="status-pill">
                                                View
                                            </a>
                                            <form method="post" action="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/invite-job') ?>" class="d-inline-block">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="job_id" value="<?= (int) ($selectedJob['id'] ?? 0) ?>">
                                                <input type="hidden" name="return_to" value="<?= esc(current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
                                                <button type="submit" class="status-pill">
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
        <div class="card shadow-sm recruiter-table-card recruiter-rounded-hidden">
            <div class="card-header py-3">
                <h6 class="title mb-3">  Candidates (<?= count($candidates ?? []) ?> on this page)</h6>
            </div>
            <div class="card-body">
                <?php if (empty($candidates)): ?>
                    <div class="text-center py-5">
                         
                        <h5>No candidates found</h5>
                        <p class="text-muted mb-0">Try adjusting your filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover recruiter-candidates-table">
                            <thead class="thead-light">
                                <tr>
                                    <th class="recruiter-select-col">
                                        <input type="checkbox" class="recruiter-candidate-checkbox js-select-all-candidates"
                                               aria-label="Select all candidates">
                                    </th>
                                    <th>Candidate</th>
                                    <th>Location</th>
                                    <th>Experience</th>
                                    <th>Skills</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr class="recruiter-cursor-pointer" onclick="window.location='<?= base_url('recruiter/candidate/' . $candidate['id'] . '/view-contact') ?>'">
                                        <td onclick="event.stopPropagation();">
                                            <input type="checkbox" name="candidate_ids[]" value="<?= (int) $candidate['id'] ?>"
                                                   class="recruiter-candidate-checkbox js-candidate-checkbox"
                                                   data-email="<?= esc($candidate['email'] ?? '') ?>"
                                                   data-name="<?= esc($candidate['name'] ?? '') ?>"
                                                   aria-label="Select <?= esc($candidate['name'] ?? 'candidate') ?>"
                                                   form="recruiterBulkInviteForm">
                                        </td>
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
                                        <td><?= !empty($candidate['created_at']) ? date('M d, Y', strtotime($candidate['created_at'])) : '-' ?></td>
                                        <td>
                                          <div class="application-actions-wrap" onclick="event.stopPropagation();">
    <a href="<?= base_url('recruiter/candidate/' . $candidate['id']) ?>" class="status-pill">
        View Profile
    </a>
    <?php if (!empty($candidate['resume_path'])): ?>
        <a href="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/download-resume') ?>"
           class="recruiter-resume-icon-action"
           title="Download <?= esc($candidate['name'] ?? 'candidate') ?>'s resume"
           aria-label="Download <?= esc($candidate['name'] ?? 'candidate') ?>'s resume">
            <i class="fas fa-download" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
</div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (isset($pager) && is_object($pager) && method_exists($pager, 'links') && $pager->getPageCount() > 1): ?>
                        <div>
                            <?= $pager->links('default', 'portal_full') ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="candidatePoolEmailModal" tabindex="-1" role="dialog" aria-labelledby="candidatePoolEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="candidatePoolEmailModalLabel"><i class="fas fa-at mr-2"></i>Send Email to Selected Candidates</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="candidatePoolEmailFeedback" class="alert alert-danger d-none" role="alert"></div>
                <div class="form-group">
                    <label class="font-weight-bold">To:</label>
                    <div id="candidatePoolEmailRecipients" class="p-2 border rounded bg-light recruiter-recipient-box">
                        <span class="text-muted">No recipients selected</span>
                    </div>
                    <small class="text-muted"><span id="candidatePoolEmailRecipientCount">0</span> recipients</small>
                </div>
                <div class="form-group">
                    <label for="candidatePoolEmailSubject" class="font-weight-bold">Subject:</label>
                    <input type="text" class="form-control" id="candidatePoolEmailSubject" placeholder="Enter email subject..." required>
                </div>
                <div class="form-group">
                    <label for="candidatePoolEmailBody" class="font-weight-bold">Message:</label>
                    <textarea class="form-control" id="candidatePoolEmailBody" rows="10" placeholder="Write your email message here..."></textarea>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Quick Templates:</label>
                    <div class="btn-group btn-group-sm flex-wrap">
                        <button type="button" class="btn btn-outline-primary" data-template="invite">Invite Intro</button>
                        <button type="button" class="btn btn-outline-primary" data-template="followup">Follow-up</button>
                        <button type="button" class="btn btn-outline-primary" data-template="availability">Availability</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-primary" id="candidatePoolSendEmailButton">
                    <i class="fas fa-paper-plane mr-1"></i> Send Email
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('recruiterCandidatePoolPage');
    const selectAllBoxes = Array.from(document.querySelectorAll('.js-select-all-candidates'));
    const checkboxes = Array.from(document.querySelectorAll('.js-candidate-checkbox'));
    const submitButton = document.getElementById('bulkInviteSubmit');
    const emailButton = document.getElementById('bulkEmailButton');
    const countLabel = document.getElementById('bulkCandidateCount');
    const bulkForm = document.getElementById('recruiterBulkInviteForm');
    const sendEmailButton = document.getElementById('candidatePoolSendEmailButton');

    if (!checkboxes.length || !submitButton || !countLabel || !bulkForm || !root) return;

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function selectedCheckboxes() {
        return checkboxes.filter(function (checkbox) { return checkbox.checked; });
    }

    function setCandidatePoolEmailFeedback(message, type) {
        const feedback = document.getElementById('candidatePoolEmailFeedback');
        if (!feedback) return;

        feedback.classList.remove('alert-danger', 'alert-success', 'alert-warning');
        if (!message) {
            feedback.classList.add('d-none');
            feedback.textContent = '';
            return;
        }

        feedback.classList.remove('d-none');
        feedback.classList.add(type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger'));
        feedback.textContent = message;
    }

    function syncSelection() {
        const selectedCount = selectedCheckboxes().length;
        selectAllBoxes.forEach(function (selectAll) {
            selectAll.checked = selectedCount === checkboxes.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        });
        submitButton.disabled = selectedCount === 0;
        if (emailButton) {
            emailButton.disabled = selectedCount === 0;
        }
        countLabel.textContent = selectedCount + (selectedCount === 1 ? ' selected' : ' selected');
    }

    selectAllBoxes.forEach(function (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
            syncSelection();
        });
    });

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', syncSelection);
    });

    bulkForm.addEventListener('submit', function (event) {
        if (!selectedCheckboxes().length) {
            event.preventDefault();
            return;
        }

        const jobSelect = document.getElementById('bulkInviteJobSelect');
        if (!jobSelect || !jobSelect.value) {
            event.preventDefault();
            alert('Please select a job before sending invitations.');
        }
    });

    if (emailButton) {
        emailButton.addEventListener('click', function () {
            const selected = selectedCheckboxes().filter(function (checkbox) {
                return checkbox.dataset.email;
            });

            if (!selected.length) {
                alert('Please select at least one candidate with an email address.');
                return;
            }

            const recipientHtml = selected.map(function (checkbox) {
                const name = checkbox.dataset.name || checkbox.dataset.email;
                return '<div class="mb-1"><i class="fas fa-user text-primary mr-1"></i>' +
                    escapeHtml(name) + ' <small class="text-muted">&lt;' + escapeHtml(checkbox.dataset.email) + '&gt;</small></div>';
            }).join('');

            document.getElementById('candidatePoolEmailRecipients').innerHTML = recipientHtml;
            document.getElementById('candidatePoolEmailRecipientCount').textContent = selected.length;
            document.getElementById('candidatePoolEmailSubject').value = '';
            document.getElementById('candidatePoolEmailBody').value = '';
            setCandidatePoolEmailFeedback('');
            if (window.jQuery) {
                window.jQuery('#candidatePoolEmailModal').modal('show');
            }
        });
    }

    document.querySelectorAll('#candidatePoolEmailModal [data-template]').forEach(function (button) {
        button.addEventListener('click', function () {
            const jobTitle = root.dataset.jobTitle || 'this opportunity';
            const templates = {
                invite: {
                    subject: 'Invitation to connect about ' + jobTitle,
                    body: 'Dear Candidate,\n\nYour profile looks relevant for ' + jobTitle + '. We would like to connect and share more details about the opportunity.\n\nBest regards,\nRecruiting Team'
                },
                followup: {
                    subject: 'Following up from HireMatrix',
                    body: 'Dear Candidate,\n\nWe wanted to follow up after reviewing your profile. Please let us know if you are open to discussing relevant opportunities.\n\nBest regards,\nRecruiting Team'
                },
                availability: {
                    subject: 'Availability for a quick discussion',
                    body: 'Dear Candidate,\n\nWe would like to schedule a quick discussion about your experience and current job preferences. Please share a few suitable time slots.\n\nBest regards,\nRecruiting Team'
                }
            };
            const template = templates[button.dataset.template];
            if (!template) return;
            document.getElementById('candidatePoolEmailSubject').value = template.subject;
            document.getElementById('candidatePoolEmailBody').value = template.body;
        });
    });

    if (sendEmailButton) {
        sendEmailButton.addEventListener('click', function () {
            const subject = document.getElementById('candidatePoolEmailSubject').value.trim();
            const body = document.getElementById('candidatePoolEmailBody').value.trim();
            const selectedIds = selectedCheckboxes()
                .filter(function (checkbox) { return checkbox.dataset.email; })
                .map(function (checkbox) { return checkbox.value; });

            if (!subject) {
                setCandidatePoolEmailFeedback('Add an email subject before sending.');
                document.getElementById('candidatePoolEmailSubject').focus();
                return;
            }

            if (!body) {
                setCandidatePoolEmailFeedback('Add the email message before sending.');
                document.getElementById('candidatePoolEmailBody').focus();
                return;
            }

            if (!selectedIds.length) {
                setCandidatePoolEmailFeedback('Select at least one candidate with an email address.');
                return;
            }

            const formData = new FormData();
            selectedIds.forEach(function (id) {
                formData.append('candidate_ids[]', id);
            });
            formData.append('subject', subject);
            formData.append('body', body);
            formData.append(root.dataset.csrfName, root.dataset.csrfHash);

            const originalText = sendEmailButton.innerHTML;
            sendEmailButton.disabled = true;
            sendEmailButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending...';

            fetch(root.dataset.emailUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) throw payload;
                        return payload;
                    });
                })
                .then(function (payload) {
                    if (payload.csrf_hash) {
                        root.dataset.csrfHash = payload.csrf_hash;
                    }
                    if (window.jQuery) {
                        window.jQuery('#candidatePoolEmailModal').modal('hide');
                    }
                    setCandidatePoolEmailFeedback('');
                    alert(payload.message || 'Email sent successfully.');
                    window.location.reload();
                })
                .catch(function (error) {
                    if (error.csrf_hash) {
                        root.dataset.csrfHash = error.csrf_hash;
                    }
                    setCandidatePoolEmailFeedback(error.message || 'Failed to send email. Please try again.');
                })
                .finally(function () {
                    sendEmailButton.disabled = false;
                    sendEmailButton.innerHTML = originalText;
                });
        });
    }

    syncSelection();
});
</script>

<?= view('Layouts/recruiter_footer') ?>

