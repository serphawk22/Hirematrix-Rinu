        <?= view('Layouts/recruiter_header', ['title' => 'Candidate Insights']) ?>

<div class="recruiter-leaderboard-jobboard">
<div class="container-fluid py-5">
    <?php
    $leaderboardAction = !empty($selectedJob['id'])
        ? base_url('recruiter/jobs/' . $selectedJob['id'] . '/leaderboard')
        : base_url('recruiter/dashboard/leaderboard');
    $candidateCount = count($candidates ?? []);
    $avgTech = !empty($candidates) ? array_sum(array_column($candidates, 'technical_score')) / count($candidates) : 0;
    $avgComm = !empty($candidates) ? array_sum(array_column($candidates, 'communication_score')) / count($candidates) : 0;
    $avgOverall = !empty($candidates) ? array_sum(array_column($candidates, 'overall_rating')) / count($candidates) : 0;
    $atsScores = array_filter(array_column($candidates ?? [], 'ats_score'), static fn ($score) => $score !== null);
    $avgAts = !empty($atsScores) ? array_sum($atsScores) / count($atsScores) : null;
    ?>

    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-trophy"></i> Recruiter insights</span>
            <h1 class="page-board-title">Candidate Insights Leaderboard</h1>
            <p class="page-board-subtitle">Compare applicants by fit, scores, and profile signals. Use this page for review, not bulk actions.</p>
        </div>
        <div class="page-board-actions recruiter-leaderboard-actions">
            <a href="<?= base_url('recruiter/jobs') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to My Jobs
            </a>
            <?php if (!empty($selectedJob['id'])): ?>
                <a href="<?= base_url('recruiter/jobs/' . $selectedJob['id'] . '/applications') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-users-cog"></i> Open Candidate List
                </a>
            <?php endif; ?>
            <a href="<?= base_url('recruiter/dashboard/export-excel?type=leaderboard') ?>" class="btn btn-primary">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
        </div>
    </div>

    <div class="card shadow-sm recruiter-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h6 class="m-0 font-weight-bold">Review Filters</h6>
                    <p class="text-muted mb-0">Fine tune the leaderboard before reviewing applications.</p>
                </div>
                <div class="text-muted small">
                    <?= !empty($selectedJob['id']) ? 'Locked to one job' : 'Across all jobs' ?>
                </div>
            </div>
            <form method="get" action="<?= $leaderboardAction ?>" id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="sort_by">Sort By</label>
                            <select name="sort_by" id="sort_by" class="form-control">
                                <option value="technical_score" <?= ($filters['sort_by'] ?? '') === 'technical_score' ? 'selected' : '' ?>>Technical Score</option>
                                <option value="overall_rating" <?= ($filters['sort_by'] ?? '') === 'overall_rating' ? 'selected' : '' ?>>Overall AI Rating</option>
                                <option value="communication_score" <?= ($filters['sort_by'] ?? '') === 'communication_score' ? 'selected' : '' ?>>Communication Score</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="skill">Filter by Skill</label>
                            <select name="skill" id="skill" class="form-control">
                                <option value="">All Skills</option>
                                <?php foreach ($skills as $skill): ?>
                                    <option value="<?= esc($skill) ?>" <?= ($filters['skill'] ?? '') === $skill ? 'selected' : '' ?>>
                                        <?= esc($skill) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="job_id">Filter by Job</label>
                            <?php if (!empty($selectedJob['id'])): ?>
                                <input type="text" class="form-control" value="<?= esc($selectedJob['title']) ?>" readonly>
                            <?php else: ?>
                                <select name="job_id" id="job_id" class="form-control">
                                    <option value="">All Jobs</option>
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?= $job['id'] ?>" <?= ($filters['job_id'] ?? '') == $job['id'] ? 'selected' : '' ?>>
                                            <?= esc($job['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($filters['skill']) || !empty($filters['job_id']) || !empty($filters['sort_by'])): ?>
        <div class="alert alert-info alert-dismissible fade show recruiter-alert" role="alert">
            <strong>Active Filters:</strong>
            <?php if (!empty($filters['sort_by'])): ?>
                <span class="badge badge-primary">Sort: <?= ucwords(str_replace('_', ' ', $filters['sort_by'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($filters['skill'])): ?>
                <span class="badge badge-primary">Skill: <?= esc($filters['skill']) ?></span>
            <?php endif; ?>
            <?php if (!empty($filters['job_id'])): ?>
                <span class="badge badge-info">Job Selected</span>
            <?php endif; ?>
            <a href="<?= $leaderboardAction ?>" class="btn btn-sm btn-outline-secondary ml-2">
                Clear All
            </a>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm recruiter-leaderboard-card">
        <div class="card-header py-3 bg-gradient-primary text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-crown"></i> Comparison View - <?= ucwords(str_replace('_', ' ', $filters['sort_by'] ?? 'technical_score')) ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="alert alert-light border mb-4">
                <strong>How to use this page:</strong> compare candidate quality here, then open a candidate application to shortlist, reject, or message from the candidate list.
            </div>

            <?php if (empty($candidates)): ?>
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
                            <?php foreach ($candidates as $candidate): ?>
                                <tr class="<?= $candidate['rank'] <= 3 ? 'top-performer' : '' ?>">
                                    <td class="rank-cell">
                                        <?php if ($candidate['rank'] === 1): ?>
                                            <span class="rank-badge gold"><i class="fas fa-crown"></i> 1</span>
                                        <?php elseif ($candidate['rank'] === 2): ?>
                                            <span class="rank-badge silver"><i class="fas fa-medal"></i> 2</span>
                                        <?php elseif ($candidate['rank'] === 3): ?>
                                            <span class="rank-badge bronze"><i class="fas fa-medal"></i> 3</span>
                                        <?php else: ?>
                                            <span class="rank-number"><?= $candidate['rank'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="candidate-info">
                                            <strong><?= esc($candidate['name']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= esc($candidate['email']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= esc($candidate['job_title']) ?></td>
                                    <td>
                                        <div class="skills-display">
                                            <?php if (!empty($candidate['required_skills'])): ?>
                                                <div class="skill-match-badge mb-2">
                                                    <span class="badge badge-<?= $candidate['skill_match'] >= 80 ? 'success' : ($candidate['skill_match'] >= 60 ? 'warning' : 'danger') ?>">
                                                        <?= $candidate['skill_match'] ?>% Match
                                                    </span>
                                                    <small class="text-muted">
                                                        (<?php
                                                            $candidateSkillsLower = array_map('strtolower', $candidate['candidate_skills'] ?? []);
                                                            $requiredSkillsLower = array_map('strtolower', $candidate['required_skills']);
                                                            $matchedCount = count(array_intersect($candidateSkillsLower, $requiredSkillsLower));
                                                            echo $matchedCount . '/' . count($candidate['required_skills']);
                                                        ?>)
                                                    </small>
                                                </div>

                                                <div class="required-skills">
                                                    <?php
                                                    $candidateSkillsLower = array_map('strtolower', $candidate['candidate_skills'] ?? []);
                                                    foreach ($candidate['required_skills'] as $requiredSkill):
                                                        $hasSkill = in_array(strtolower($requiredSkill), $candidateSkillsLower);
                                                    ?>
                                                        <span class="skill-badge <?= $hasSkill ? 'skill-has' : 'skill-missing' ?>"
                                                              title="<?= $hasSkill ? 'Candidate has this skill' : 'Candidate does not have this skill' ?>">
                                                            <?= esc($requiredSkill) ?>
                                                            <?php if ($hasSkill): ?>
                                                                <i class="fas fa-check-circle text-success"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-times-circle text-danger"></i>
                                                            <?php endif; ?>
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
                                                <div class="mb-2">
                                                    <small class="text-muted text-uppercase font-weight-bold">GitHub Stack</small>
                                                </div>
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
                                            <span class="score-value <?= $candidate['technical_score'] >= 80 ? 'text-success' : ($candidate['technical_score'] >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                <?= number_format($candidate['technical_score'] ?? 0, 1) ?>
                                            </span>
                                            <div class="score-bar">
                                                <div class="score-fill" style="width: <?= ($candidate['technical_score'] ?? 0) ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="score-display">
                                            <span class="score-value <?= $candidate['communication_score'] >= 80 ? 'text-success' : ($candidate['communication_score'] >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                <?= number_format($candidate['communication_score'] ?? 0, 1) ?>
                                            </span>
                                            <div class="score-bar">
                                                <div class="score-fill" style="width: <?= ($candidate['communication_score'] ?? 0) ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="overall-rating">
                                            <span class="rating-badge <?= $candidate['overall_rating'] >= 80 ? 'badge-success' : ($candidate['overall_rating'] >= 60 ? 'badge-warning' : 'badge-danger') ?>">
                                                <?= number_format($candidate['overall_rating'] ?? 0, 1) ?>
                                            </span>
                                            <div class="rating-stars">
                                                <?php
                                                $stars = round(($candidate['overall_rating'] ?? 0) / 20);
                                                for ($i = 1; $i <= 5; $i++):
                                                ?>
                                                    <i class="fas fa-star <?= $i <= $stars ? 'text-warning' : 'text-muted' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($candidate['ats_score'] !== null): ?>
                                            <div class="score-display">
                                                <span class="score-value <?= $candidate['ats_score'] >= 80 ? 'text-success' : ($candidate['ats_score'] >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                    <?= (int) $candidate['ats_score'] ?>
                                                </span>
                                                <div class="score-bar">
                                                    <div class="score-fill" style="width: <?= (int) $candidate['ats_score'] ?>%"></div>
                                                </div>
                                                <small class="text-muted d-block mt-1">Fit signal</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $statusColors = [
                                            'applied' => 'secondary',
                                            'pending' => 'secondary',
                                            'shortlisted' => 'primary',
                                            'filtered_out' => 'dark',
                                            'interview_slot_booked' => 'warning',
                                            'selected' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $statusColors[$candidate['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge badge-<?= $color ?>">
                                            <?= ucwords(str_replace('_', ' ', $candidate['status'])) ?>
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

                <div class="mt-4">
                    <?php if ($pager->getPageCount() > 1): ?>
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="pagination-info mb-2 mb-md-0">
                                <span class="text-muted">
                                    Showing
                                    <strong><?= (($pager->getCurrentPage() - 1) * $pager->getPerPage()) + 1 ?></strong>
                                    to
                                    <strong><?= min($pager->getCurrentPage() * $pager->getPerPage(), $pager->getTotal()) ?></strong>
                                    of
                                    <strong><?= number_format($pager->getTotal()) ?></strong>
                                    candidates
                                </span>
                            </div>
                            <div><?= $pager->links() ?></div>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-2">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                Showing all <?= count($candidates) ?> candidate<?= count($candidates) != 1 ? 's' : '' ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-muted">Average Technical Score</h5>
                                <h3 class="text-primary"><?= number_format($avgTech, 1) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-muted">Average Communication Score</h5>
                                <h3 class="text-info"><?= number_format($avgComm, 1) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-muted">Average Overall Rating</h5>
                                <h3 class="text-warning"><?= number_format($avgOverall, 1) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-muted">Average ATS Score</h5>
                                <h3 class="text-info"><?= $avgAts !== null ? number_format($avgAts, 1) : 'N/A' ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>
    