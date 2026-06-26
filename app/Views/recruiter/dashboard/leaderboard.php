        <?= view('Layouts/recruiter_header', ['title' => 'Candidate Insights']) ?>
<style>
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

    .page-board-title{
        font-size: 26px !important; 
    font-weight: 700 !important;
    color: var(--foreground) !important;
    margin: 0;
    }
    body.dark .page-board-title{
        font-size: 26px !important;
    font-weight: 700 !important;
    color: #ffffff !important;
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
.page-board-header.page-board-header-tight.recruiter-page-board-header, body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border : none !important;
}
tr, td, th, .col-md-3 {
    font-size: 1rem;
    font-weight: 500 !important;
    color: var(--foreground, #16212B);
    background: white !important;
}

/* Add these dark mode overrides */
body.dark tr,
body.dark td,
body.dark th, body.dark .col-md-3  {
background: #000000 !important;
    color:#FFFFFF !important;
 border: 1px solid #23343A !important;
}

body.dark .table-secondary td,
body.dark .table-secondary th,
body.dark .table-secondary {
   background:  #000000 !important; 
   border: 1px solid #23343A !important;
}

body.dark thead th {
border: 1px solid #23343A !important;
    color: #ffffff !important;
}
.hm-page-content,.recruiter-leaderboard-jobboard{
         background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
}
body.dark .hm-page-content,body.dark .recruiter-leaderboard-jobboard,body.dark .card-header,body.dark .recruiter-leaderboard-card,body.dark .recruiter-alert, body.dark .alert-light{
  background: #000000 !important;
    border: none !important;
} 
body.dark .card-header, body.dark .recruiter-filter-card,body.dark .recruiter-alert,body.dark h6.m-0.font-weight-bold{
    color:#ffffff !important;
}
.page-board-header.page-board-header-tight.recruiter-page-board-header, body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header,body.dark .card-body,body.dark .row mt-4,body.dark .col-md-3{
      border: none !important;
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
body.dark .recruiter-job-form .form-control {
    border: 1px solid #23343A !important;
    border-radius: 6px;
    transition: border-color .2s, box-shadow .2s;
   background: #000000 !important;
    color: #ffffff !important;
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
body.dark .recruiter-tip-item{
   background: #000000 !important;
    color: #ffffff !important;
     border: 1px solid #23343A !important;
      font-weight: 400 !important;   
}
 .recruiter-tip-item{ 
      font-weight: 400 !important;   
}
body.dark .recruiter-job-form label, body.dark h6 {
    font-size: 1rem;        /* same as Bootstrap h6 */
    font-weight: 500 !important;   
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
    color:#ffffff !important;
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

#filterForm .form-control:focus,
#filterForm select.form-control:focus,
#filterForm textarea.form-control:focus {
    outline: 0 !important;
    box-shadow: none !important;   /* ← add this */
    border-color: #0D8A90 !important; 
}
/* ── Also reset Bootstrap's base .form-control focus ── */
.form-control:focus {
    box-shadow: none !important;   /* ← already there, add !important */
    border-color: #0D8A90;
}
/* ── Summary cards below leaderboard table ── */
body.dark .card,
body.dark .card.bg-light {
  background: #000000 !important;
    border: 1px solid #23343A !important;
}

body.dark .card .card-body {
   background: #000000 !important;
}

body.dark .card .card-body h5.text-muted {
    color: #ffffff !important;
}

body.dark .card .card-body h3.text-primary {
    color: #1FB7B5 !important;
}

body.dark .card .card-body h3.text-info {
    color: #38BDF8 !important;
}

body.dark .card .card-body h3.text-warning {
    color: #FBBF24 !important;
}
.recruiter-leaderboard-jobboard .recruiter-leaderboard-card .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.recruiter-leaderboard-jobboard .leaderboard-table {
    min-width: 1240px;
}

.recruiter-leaderboard-jobboard .leaderboard-table th,
.recruiter-leaderboard-jobboard .leaderboard-table td {
    vertical-align: middle;
    word-break: normal;
    overflow-wrap: normal;
}

.recruiter-leaderboard-jobboard .leaderboard-table th {
    white-space: nowrap;
}

.recruiter-leaderboard-jobboard .leaderboard-table th:nth-child(1),
.recruiter-leaderboard-jobboard .leaderboard-table td:nth-child(1) {
    min-width: 64px;
}

.recruiter-leaderboard-jobboard .leaderboard-table th:nth-child(2),
.recruiter-leaderboard-jobboard .leaderboard-table td:nth-child(2) {
    min-width: 150px;
}

.recruiter-leaderboard-jobboard .leaderboard-table th:nth-child(3),
.recruiter-leaderboard-jobboard .leaderboard-table td:nth-child(3) {
    min-width: 140px;
}

.recruiter-leaderboard-jobboard .leaderboard-table th:nth-child(4),
.recruiter-leaderboard-jobboard .leaderboard-table td:nth-child(4) {
    min-width: 230px;
}

.recruiter-leaderboard-jobboard .leaderboard-table th:nth-child(5),
.recruiter-leaderboard-jobboard .leaderboard-table td:nth-child(5) {
    min-width: 180px;
}

.recruiter-leaderboard-jobboard .leaderboard-table th:nth-child(6),
.recruiter-leaderboard-jobboard .leaderboard-table td:nth-child(6),
.recruiter-leaderboard-jobboard .leaderboard-table th:nth-child(7),
.recruiter-leaderboard-jobboard .leaderboard-table td:nth-child(7),
.recruiter-leaderboard-jobboard .leaderboard-table th:nth-child(8),
.recruiter-leaderboard-jobboard .leaderboard-table td:nth-child(8) {
    min-width: 120px;
}

.recruiter-leaderboard-jobboard .candidate-info strong,
.recruiter-leaderboard-jobboard .leaderboard-table td:nth-child(3) {
    overflow-wrap: normal;
    word-break: normal;
}

.recruiter-leaderboard-jobboard .candidate-info small {
    display: block;
    max-width: 150px;
    overflow-wrap: anywhere;
}
 .container-fluid {
    max-width: 100% !important;
    padding-left: 34px !important;
    padding-right: 34px !important;
}
</style>
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
            <h1 class="page-board-title">Candidate Insights Leaderboard</h1>
            <p class="page-board-subtitle">Compare applicants by fit, scores, and profile signals. Use this page for review, not bulk actions.</p>
        </div>
        <div class="page-board-actions recruiter-leaderboard-actions">
            <a href="<?= base_url('recruiter/jobs') ?>" class="btn btn-outline-primary">
                  Back to My Jobs
            </a>
            <?php if (!empty($selectedJob['id'])): ?>
                <a href="<?= base_url('recruiter/jobs/view/' . $selectedJob['id']) ?>" class="btn btn-outline-primary">
                     Open Candidate List
                </a>
            <?php endif; ?>
            <a href="<?= base_url('recruiter/dashboard/export-excel?type=leaderboard') ?>" class="btn btn-outline-primary">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
        </div>
    </div>

    <div class="card shadow-sm recruiter-filter-card mb-4" style="border-radius: 20px !important;overflow: hidden;">
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
            <form method="get" action="<?= $leaderboardAction ?>" id="filterForm" class="recruiter-job-form">
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
                            <button type="submit" class="btn btn-outline-primary btn-block">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($filters['skill']) || !empty($filters['job_id']) || !empty($filters['sort_by'])): ?>

    <?php endif; ?>

    <div class="card shadow-sm recruiter-leaderboard-card" style="border-radius: 20px !important;overflow: hidden;">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-crown"></i> Comparison View - <?= ucwords(str_replace('_', ' ', $filters['sort_by'] ?? 'technical_score')) ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($candidates)): ?>
                <div class="text-center py-5"> 
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
                                            <span ><i class="fas fa-crown"></i> 1</span>
                                        <?php elseif ($candidate['rank'] === 2): ?>
                                            <span  ><i class="fas fa-medal"></i> 2</span>
                                        <?php elseif ($candidate['rank'] === 3): ?>
                                            <span ><i class="fas fa-medal"></i> 3</span>
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
                                                    <span class="status-pill">
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
                                                        <span class="status-pill"
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
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($candidate['ats_score'] !== null): ?>
                                            <div class="score-display">
                                                <span class="score-value <?= $candidate['ats_score'] >= 80 ? 'text-success' : ($candidate['ats_score'] >= 60 ? 'text-warning' : 'text-danger') ?>">
                                                    <?= (int) $candidate['ats_score'] ?>
                                                </span>
                                                <div class="score-bar">
                                                    <div class="score-fill" style="width: <?= (int) $candidate['ats_score'] ?>%" style="color:#0D8A90;"></div>
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
                                            'ai_interview_completed' => 'info',
                                            'shortlisted' => 'primary',
                                            'filtered_out' => 'dark',
                                            'interview_slot_booked' => 'warning',
                                            'selected' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $statusColors[$candidate['status']] ?? 'secondary';
                                        $statusLabel = ($candidate['status'] ?? '') === 'ai_interview_completed'
                                            ? 'AI Interview Completed'
                                            : ucwords(str_replace('_', ' ', $candidate['status']));
                                        ?>
                                        <span class="status-pill">
                                            <?= esc($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('recruiter/candidate/' . $candidate['candidate_id'] . '?application_id=' . $candidate['id'] . '&job_id=' . $candidate['job_id']) ?>" class="btn btn-sm btn-outline-primary">
                                            View Application
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
                            <div><?= $pager->links('default', 'portal_full') ?></div>
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
                        <div class="card" style="border-radius: 20px !important;overflow: hidden;">
                            <div class="card-body text-center crd" >
                                <h5 class="text-muted">Average Technical Score</h5>
                                <h3 class="text-primary"><?= number_format($avgTech, 1) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" style="border-radius: 20px !important;overflow: hidden;">
                            <div class="card-body text-center">
                                <h5 class="text-muted">Average Communication Score</h5>
                                <h3 class="text-info"><?= number_format($avgComm, 1) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" style="border-radius: 20px !important;overflow: hidden;">
                            <div class="card-body text-center">
                                <h5 class="text-muted">Average Overall Rating</h5>
                                <h3 class="text-warning"><?= number_format($avgOverall, 1) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card" style="border-radius: 20px !important;overflow: hidden;">
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
    
