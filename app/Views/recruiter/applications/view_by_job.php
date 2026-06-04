        <?= view('Layouts/recruiter_header', ['title' => 'Applications - ' . $job['title']]) ?>
<style>
/* Modal size */
.ai-modal .modal-dialog {
  max-width: 1000px;
}

/* Main modal */
.ai-modal-content {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e8edf8;
  box-shadow: 0 10px 22px rgba(35, 54, 106, .08);
  overflow: hidden;
}

/* Header */
.ai-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  padding: 16px 20px;
  border-bottom: 1px solid #edf1fa;
}

/* Title */
.ai-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.ai-title h5 {
  margin: 0;
  font-weight: 600;
  color: #1f2f57;
}

.ai-title small {
  font-size: 0.75rem;
  color: #6b7c9f;
}

/* Icon */
.ai-icon {
  color: #ff7b2a;
  font-size: 20px;
}

/* Close button */
.ai-close {
  background: transparent;
  border: none;
  color: #6b7c9f;
  font-size: 18px;
  padding: 5px 10px;
  border-radius: 6px;
  cursor: pointer;
}

.ai-close:hover {
  background: #f8f9fa;
  color: #1f2f57;
}

/* Body */
.ai-body {
  padding: 20px;
  color: #212529;
  max-height: 75vh;
  overflow-y: auto;
}

/* Loader */
.ai-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 50px 0;
  color: #6b7c9f;
}

/* Spinner */
.spinner {
  width: 36px;
  height: 36px;
  border: 4px solid #ffe3cf;
  border-top: 4px solid #ff7b2a;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Scrollbar */
.ai-body::-webkit-scrollbar {
  width: 6px;
}

.ai-body::-webkit-scrollbar-thumb {
  background: #ff7b2a;
  border-radius: 10px;
}
    </style>
<div class="recruiter-applications-jobboard">
<div class="container-fluid py-5">
    <?php
    $applicationsCount = count($applications ?? []);
    $statusCount = [];
    foreach (($applications ?? []) as $app) {
        $status = strtolower((string) ($app['status'] ?? 'pending'));
        $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
    }
    $statusOptions = $statusOptions ?? [];
    ?>

    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-users-cog"></i> Recruiter applications</span>
            <h1 class="page-board-title"><?= esc($job['title']) ?></h1>
            <p class="page-board-subtitle">
                Review candidates, run actions, and compare application status for this role.
            </p>
        </div>
        <div class="page-board-actions recruiter-applications-actions">
            <a href="<?= base_url('recruiter/jobs') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Jobs
            </a>
            <a href="<?= base_url('recruiter/jobs/' . $job['id'] . '/leaderboard') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-chart-line"></i> Open Leaderboard
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success recruiter-alert"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger recruiter-alert"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div id="recruiterAjaxAlert"></div>

    <div class="card shadow-sm recruiter-job-summary-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="mb-1"><?= esc($job['title']) ?></h5>
                    <small class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> <?= esc($job['location']) ?> |
                        <i class="fas fa-calendar"></i> Posted on <?= date('M d, Y', strtotime($job['created_at'])) ?>
                    </small>
                </div>
                <div class="text-muted small">
                    <?= $applicationsCount ?> candidate<?= $applicationsCount === 1 ? '' : 's' ?> in this pipeline
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm recruiter-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h6 class="m-0 font-weight-bold">Review Filters</h6>
                    <p class="text-muted mb-0">Filter by skills, experience, location, and scoring signals.</p>
                </div>
                <div class="text-muted small">
                    <?= !empty($applicationsCount) ? 'Bulk actions available' : 'No candidates yet' ?>
                </div>
            </div>

            <form method="get" action="<?= base_url('recruiter/jobs/' . $job['id'] . '/applications') ?>" class="recruiter-app-filters">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">Skills</label>
                        <input type="text" name="skills" class="form-control" value="<?= esc($filters['skills'] ?? '') ?>" placeholder="e.g. PHP, Laravel">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Experience</label>
                        <input type="text" name="experience" class="form-control" value="<?= esc($filters['experience'] ?? '') ?>" placeholder="e.g. 3 years">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= esc($filters['location'] ?? '') ?>" placeholder="City / State">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Score Min</label>
                        <input type="number" step="0.1" min="0" max="10" name="score_min" class="form-control" value="<?= esc($filters['score_min'] ?? '') ?>" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Score Max</label>
                        <input type="number" step="0.1" min="0" max="10" name="score_max" class="form-control" value="<?= esc($filters['score_max'] ?? '') ?>" placeholder="10">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">ATS Min</label>
                        <input type="number" min="0" max="100" name="ats_min" class="form-control" value="<?= esc($filters['ats_min'] ?? '') ?>" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">ATS Max</label>
                        <input type="number" min="0" max="100" name="ats_max" class="form-control" value="<?= esc($filters['ats_max'] ?? '') ?>" placeholder="100">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Sort By</label>
                        <select name="sort" class="form-control">
                            <option value="applied_desc" <?= ($filters['sort'] ?? '') === 'applied_desc' ? 'selected' : '' ?>>Newest Applied</option>
                            <option value="ats_desc" <?= ($filters['sort'] ?? '') === 'ats_desc' ? 'selected' : '' ?>>ATS High to Low</option>
                            <option value="ats_asc" <?= ($filters['sort'] ?? '') === 'ats_asc' ? 'selected' : '' ?>>ATS Low to High</option>
                        </select>
                    </div>
                    <div class="col-md-2">
    <label class="small text-muted mb-1">Last Active</label>
    <select name="last_active" class="form-control">
        <option value="">All</option>
        <option value="7" <?= ($filters['last_active'] ?? '') === '7' ? 'selected' : '' ?>>Last 7 Days</option>
        <option value="30" <?= ($filters['last_active'] ?? '') === '30' ? 'selected' : '' ?>>Last 30 Days</option>
        <option value="90" <?= ($filters['last_active'] ?? '') === '90' ? 'selected' : '' ?>>Last 90 Days</option>
    </select>
</div>
                    <div class="col-md-1">
                        <label class="small text-muted mb-1">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?= esc($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                                    <?= esc(ucwords(str_replace('_', ' ', $status))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3 recruiter-filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="<?= base_url('recruiter/jobs/' . $job['id'] . '/applications') ?>" class="btn btn-outline-secondary btn-sm ml-2">
                        Clear
                    </a>
                    <a href="<?= base_url('recruiter/jobs/' . $job['id'] . '/leaderboard') ?>" class="btn btn-outline-secondary btn-sm ml-2">
                        <i class="fas fa-chart-line"></i> Open Leaderboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($filters['skills']) || !empty($filters['experience']) || !empty($filters['location']) || !empty($filters['score_min']) || !empty($filters['score_max']) || !empty($filters['ats_min']) || !empty($filters['ats_max']) || !empty($filters['sort']) || !empty($filters['status'])): ?>
        <div class="alert alert-info recruiter-alert">
            <strong>Active filters are applied.</strong> Use Clear to reset the search.
        </div>
    <?php endif; ?>

    <?php if (!empty($applications)): ?>
        <div class="alert alert-light border recruiter-alert">
            <strong>Decision workspace:</strong> bulk actions and per-candidate decisions are handled here. The leaderboard is kept read-focused for comparison only.
        </div>

        <div class="card shadow-sm recruiter-table-card">
            <div class="card-body">
                <form method="post" action="<?= base_url('recruiter/jobs/' . $job['id'] . '/applications/bulk') ?>" id="bulkActionForm" class="mb-3 recruiter-bulk-form">
                    <?= csrf_field() ?>
                    <div class="recruiter-bulk-toolbar">
                        <select name="bulk_action" id="bulkActionSelect" class="form-control form-control-sm recruiter-bulk-select">
                            <option value="">Bulk Action</option>
                            <option value="shortlist">Shortlist Selected</option>
                            <option value="reject">Reject Selected</option>
                            <option value="message">Message Selected</option>
                        </select>
                        <input type="text" name="bulk_message" id="bulkMessageInput" class="form-control form-control-sm recruiter-bulk-message" placeholder="Message for selected candidates (required only for Message action)">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-bolt"></i> Apply
                        </button>
                        <small class="text-muted">Select candidates using the first column.</small>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover recruiter-applications-table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAllApplications" title="Select all">
                                </th>
                                <th>ID</th>
                                <th>Candidate</th>
                                <th>Email</th>
                                <th>Experience</th>
                                <th>Skills</th>
                                <th>Tags</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>ATS Score</th>
                                <th>Last Active</th>
                                <th>Applied Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr data-application-row="<?= (int) $app['id'] ?>">
                                    <td>
                                        <input type="checkbox" class="application-checkbox" value="<?= (int) $app['id'] ?>">
                                    </td>
                                    <td>#<?= $app['id'] ?></td>
                                    <td><strong><?= esc($app['name']) ?></strong></td>
                                    <td><?= esc($app['email']) ?></td>
                                    <td><?= esc($app['experience_display'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($app['skill_name'])): ?>
                                            <small><?= esc($app['skill_name']) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['recruiter_tags'])): ?>
                                            <?php foreach (explode(',', (string) $app['recruiter_tags']) as $tag): ?>
                                                <?php $trimmedTag = trim($tag); ?>
                                                <?php if ($trimmedTag !== ''): ?>
                                                    <span class="badge badge-light border mb-1"><?= esc($trimmedTag) ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['recruiter_notes'])): ?>
                                            <?php
                                            $fullNote = trim((string) $app['recruiter_notes']);
                                            $shortNote = mb_strlen($fullNote) > 80 ? mb_substr($fullNote, 0, 80) . '...' : $fullNote;
                                            ?>
                                            <small title="<?= esc($fullNote) ?>"><?= esc($shortNote) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'applied' => 'warning',
                                            'shortlisted' => 'success',
                                            'hold' => 'secondary',
                                            'filtered_out' => 'dark',
                                            'interview_slot_booked' => 'success',
                                            'selected' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $statusColors[$app['status']] ?? 'secondary';
                                        $statusLabels = [
                                            'pending' => 'Applied',
                                            'applied' => 'Applied',
                                            'shortlisted' => 'Shortlisted',
                                            'hold' => 'On Hold',
                                            'filtered_out' => 'Filtered Out',
                                            'interview_slot_booked' => 'Interview Booked',
                                            'selected' => 'Selected',
                                            'rejected' => 'Rejected',
                                        ];
                                        $label = $statusLabels[$app['status']] ?? ucwords(str_replace('_', ' ', $app['status']));
                                        ?>
                                        <span class="badge badge-<?= $color ?> application-status-badge" data-status="<?= esc($app['status']) ?>">
                                            <?= esc($label) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $ats = (int) ($app['ats_score'] ?? 0);
                                        $atsBadge = 'danger';
                                        if ($ats >= 80) {
                                            $atsBadge = 'success';
                                        } elseif ($ats >= 60) {
                                            $atsBadge = 'warning';
                                        } elseif ($ats >= 40) {
                                            $atsBadge = 'info';
                                        }
                                        ?>
                                        <span class="badge badge-<?= $atsBadge ?>"><?= $ats ?>%</span>
                                    </td>
                                    <td>
    <?php if (!empty($app['last_login'])): ?>
        <?= date('M d, Y', strtotime($app['last_login'])) ?>
    <?php else: ?>
        <span class="text-muted">Never</span>
    <?php endif; ?>
</td>
                                    <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                    <td>
                                        <div class="application-actions-wrap">
                                            <a href="<?= base_url('recruiter/candidate/' . $app['candidate_id'] . '?application_id=' . $app['id'] . '&job_id=' . $job['id']) ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="fas fa-user"></i> View Profile
                                            </a>
                                            <?php if (!empty($app['can_manual_decision'])): ?>
                                                <form method="post" action="<?= base_url('recruiter/applications/shortlist/' . $app['id']) ?>" class="application-action-form" data-application-id="<?= (int) $app['id'] ?>">
                                                    <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-check"></i> Shortlist
                                                </button>
                                                </form>
                                                <form method="post" action="<?= base_url('recruiter/applications/reject/' . $app['id']) ?>" class="application-action-form" data-application-id="<?= (int) $app['id'] ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php elseif (($app['status'] ?? '') !== 'interview_slot_booked' && ($app['status'] ?? '') !== 'selected'): ?>
                                                 <button
    type="button"
    class="btn btn-sm btn-info view-ai-report-btn"
    data-candidate-id="<?= $app['candidate_id'] ?>"
    data-jobrole="<?= esc($job['title']) ?>"
    data-candidate-name="<?= esc($app['name']) ?>"
>
    <i class="fas fa-robot"></i> AI Report
</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm recruiter-empty-state">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5>No applications yet</h5>
                <p class="text-muted mb-0">Applications will appear here once candidates apply</p>
            </div>
        </div>
    <?php endif; ?>
</div>
<div class="modal fade ai-modal" id="aiReportModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content ai-modal-content">

      <!-- HEADER -->
      <div class="modal-header ai-header">
        <div class="ai-title">
          <span class="ai-icon">📊</span>
          <div>
            <h5>AI Interview Report</h5>
            <small>Detailed candidate performance & insights</small>
          </div>
        </div>

        <button type="button" class="ai-close" data-dismiss="modal">
          ✕
        </button>
      </div>

      <!-- BODY -->
      <div class="modal-body ai-body">
        <div id="aiReportContent">

          <!-- Loader -->
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
(function () {
    const alertHost = document.getElementById('recruiterAjaxAlert');

    function showAlert(type, message) {
        if (!alertHost) {
            return;
        }

        alertHost.innerHTML = '<div class="alert alert-' + type + ' recruiter-alert">' + message + '</div>';
    }

    function refreshCsrfTokens(tokenName, tokenHash) {
        if (!tokenName || !tokenHash) {
            return;
        }

        document.querySelectorAll('input[name="' + tokenName + '"]').forEach(function (input) {
            input.value = tokenHash;
        });
    }

    function setButtonBusy(button, busy) {
        if (!button) {
            return;
        }

        if (busy) {
            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
            }
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>Saving';
            return;
        }

        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
        button.disabled = false;
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!form.classList.contains('application-action-form')) {
            return;
        }

        event.preventDefault();

        const row = form.closest('[data-application-row]');
        const button = form.querySelector('button[type="submit"]');

        setButtonBusy(button, true);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(form)
        })
            .then(async function (response) {
                const contentType = response.headers.get('content-type') || '';
                let payload = null;

                if (contentType.indexOf('application/json') !== -1) {
                    payload = await response.json();
                } else {
                    const text = await response.text();
                    throw new Error(text || 'Unexpected response from the server.');
                }

                if (payload.csrf_token_name && payload.csrf_hash) {
                    refreshCsrfTokens(payload.csrf_token_name, payload.csrf_hash);
                }

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Could not update application status.');
                }

                showAlert('success', payload.message);

                if (row) {
                    const badge = row.querySelector('.application-status-badge');
                    if (badge) {
                        badge.className = 'badge badge-' + (payload.status_badge || 'secondary') + ' application-status-badge';
                        badge.textContent = payload.status_label || badge.textContent;
                        badge.dataset.status = payload.status || '';
                    }
                }
            })
            .catch(function (error) {
                showAlert('danger', error.message || 'Could not update application status.');
            })
            .finally(function () {
                setButtonBusy(button, false);
            });
    });
})();
</script>
<script>

document.addEventListener(
    "click",
    function (e) {

        const btn =
            e.target.closest(
                ".view-ai-report-btn"
            );

        if (!btn) return;

        const candidate_id =
            btn.dataset.candidateId;

        const jobrole =
            btn.dataset.jobrole;

        const candidate_name =
            btn.dataset.candidateName;

        $("#aiReportModal").modal("show");

        document.getElementById(
            "aiReportContent"
        ).innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
            </div>
        `;

        fetch(
            "<?= base_url('recruiter/get-ai-report') ?>",
            {

                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({

                    candidate_id:
                        candidate_id,

                    jobrole:
                        jobrole

                })

            }
        )

        .then(res => res.json())

        .then(data => {

            let violationsHtml = "";

            if (
                data.violations.length > 0
            ) {

                data.violations.forEach(v => {

                    violationsHtml += `
                        <tr>
                            <td>${v.message}</td>
                            <td>
                                <span class="badge badge-danger">
                                    ${v.total}
                                </span>
                            </td>
                        </tr>
                    `;

                });

            } else {

                violationsHtml = `
                    <tr>
                        <td colspan="2"
                            class="text-center text-muted">
                            No violations found
                        </td>
                    </tr>
                `;
            }

            let resultHtml = "";

            if (
                data.results.length > 0
            ) {

                data.results.forEach(r => {

                    resultHtml += `
                        <tr>
                            <td>${r.round_name}</td>
                            <td>${r.score}</td>
                            <td>${r.total_questions}</td>
                            <td>
                                <span class="badge badge-success">
                                    ${r.percentage}%
                                </span>
                            </td>
                        </tr>
                    `;

                });

            } else {

                resultHtml = `
                    <tr>
                        <td colspan="4"
                            class="text-center text-muted">
                            No interview results
                        </td>
                    </tr>
                `;
            }

            document.getElementById(
                "aiReportContent"
            ).innerHTML = `

            <div class="row">

                <div class="col-md-12 mb-4">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <h4 class="font-weight-bold">
                                ${candidate_name}
                            </h4>

                            <small class="text-muted">
                                ${jobrole}
                            </small>

                        </div>

                    </div>

                </div>

                <div class="col-md-7">

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                Interview Scores
                            </h5>
                        </div>

                        <div class="card-body">

                            <table class="table">

                                <thead>

                                    <tr>
                                        <th>Round</th>
                                        <th>Score</th>
                                        <th>Total</th>
                                        <th>Percentage</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    ${resultHtml}

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

                <div class="col-md-5">

                    <div class="card border-0 shadow-sm">

                        <div class="card-header bg-white">
                            <h5 class="mb-0 text-danger">
                                Violations
                            </h5>
                        </div>

                        <div class="card-body">

                            <table class="table">

                                <thead>

                                    <tr>
                                        <th>Violation</th>
                                        <th>Count</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    ${violationsHtml}

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>
            `;

        });

    }
);

</script>
<?= view('Layouts/recruiter_footer') ?>
    