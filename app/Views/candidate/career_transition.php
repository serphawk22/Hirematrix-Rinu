        <?= view('Layouts/candidate_header', ['title' => 'Career Transition AI']) ?>
<?php
$skillGaps = [];
if (!empty($transition['skill_gaps'])) {
    $decodedGaps = json_decode((string) $transition['skill_gaps'], true);
    if (is_array($decodedGaps)) {
        $skillGaps = $decodedGaps;
    }
}
$hasTransition = !empty($transition);
$taskCount = count($tasks ?? []);
$reactivationCount = (int) ($transition['reactivation_count'] ?? 0);
?>

<div class="career-transition-jobboard career-transition-service-jobboard">
    <section class="career-transition-content service-content-canvas">
        <div class="container-fluid">
            <div class="page-board-header page-board-header-tight">
                <div class="page-board-copy">
                    <span class="page-board-kicker"><i class="fas fa-map-signs"></i> Career learning path</span>
                    <h1 class="page-board-title">Career Transition AI</h1>
                    <p class="page-board-subtitle">Get a personalised roadmap toward your target role with clear daily steps.</p>
                </div>
                <div class="page-board-actions">
                    <a href="<?= base_url('career-transition/history') ?>" class="btn btn-primary">
                        <i class="fas fa-history mr-1"></i> View History
                    </a>
                </div>
            </div>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <?= esc(session()->getFlashdata('error')) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= esc(session()->getFlashdata('success')) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (!$hasTransition): ?>
                <div class="career-transition-simple-layout">
                    <div class="career-transition-card dashboard-panel">
                        <div class="panel-body">
                            <form action="<?= base_url('career-transition/create') ?>" method="post" id="transitionForm">
                                <?= csrf_field() ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Current Role</label>
                                    <input type="text" name="current_role" class="form-control" value="<?= esc($currentRole ?? '') ?>" placeholder="e.g., PHP Developer" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Target Role</label>
                                    <input list="role-suggestions" type="text" name="target_role" class="form-control" value="<?= esc($targetRole ?? '') ?>" placeholder="e.g., Next.js Developer" required>
                                    <datalist id="role-suggestions">
                                        <option value="Next.js Developer">
                                        <option value="React Developer">
                                        <option value="DevOps Engineer">
                                        <option value="DevOps Developer">
                                        <option value="Data Scientist">
                                        <option value="Data Analyst">
                                        <option value="Full Stack Developer">
                                        <option value="Frontend Developer">
                                        <option value="Backend Developer">
                                        <option value="Python Developer">
                                        <option value="Java Developer">
                                        <option value="Node.js Developer">
                                        <option value="Cloud Engineer">
                                        <option value="Machine Learning Engineer">
                                    </datalist>
                                </div>
                                <button type="submit" class="btn btn-primary career-transition-submit-btn" id="submitBtn">
                                    <span id="btnText"><i class="fas fa-rocket"></i> Generate Roadmap</span>
                                    <span id="btnLoading" class="transition-btn-loading">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                        Generating AI course...
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="career-transition-note">
                        <h3>How it works</h3>
                        <p>We keep the flow lightweight: define a target role, generate a plan, and follow daily tasks.</p>
                        <ul>
                            <li>Start from your current role</li>
                            <li>Pick one target role</li>
                            <li>Follow the generated tasks step by step</li>
                        </ul>
                    </div>
                </div>

            <?php else: ?>
                <div class="career-transition-simple-layout">

                    <div class="career-transition-card dashboard-panel career-transition-overview-card">
                        <div class="panel-body">
                            <div class="career-transition-overview">
                                <div class="career-transition-path">
                                    <span class="career-transition-path-label">Active path</span>
                                    <div class="career-transition-role-flow">
                                        <div class="career-transition-role"><?= esc($transition['current_role']) ?></div>
                                        <div class="career-transition-path-arrow"><i class="fas fa-arrow-right"></i></div>
                                        <div class="career-transition-role career-transition-role-target"><?= esc($transition['target_role']) ?></div>
                                    </div>
                                </div>

                                <div class="career-transition-overview-side">
                                    <div class="career-transition-stats">
                                        <span>
                                            <strong><?= (int) $taskCount ?></strong>
                                            tasks
                                        </span>
                                        <span>
                                            <strong><?= count($skillGaps) ?></strong>
                                            skill gaps
                                        </span>
                                        <span>
                                            <strong><?= $reactivationCount ?></strong>
                                            reused
                                        </span>
                                    </div>

                                    <div class="career-transition-actions">
                                        <a href="<?= base_url('career-transition/course') ?>" class="btn btn-primary">
                                            <i class="fas fa-book-open"></i> View Course
                                        </a>
                                        <a href="<?= base_url('career-transition/download-pdf') ?>" class="btn btn-primary">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </a>
                                        <a href="#change-transition-form" class="btn btn-outline-secondary">
                                            <i class="fas fa-sync"></i> Change Path
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="career-transition-card dashboard-panel career-transition-change-card" id="change-transition-form">
                        <div class="panel-body">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                <div>
                                    <h2 class="section-title mb-1">Change Career Path</h2>
                                    <p class="section-subtitle mb-0">Generate a new roadmap or restore a previous matching path.</p>
                                </div>
                                <span class="badge badge-light">Current path is saved to history</span>
                            </div>

                            <form action="<?= base_url('career-transition/create') ?>" method="post" id="changeTransitionForm">
                                <?= csrf_field() ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Current Role</label>
                                        <input type="text" name="current_role" class="form-control" value="<?= esc($transition['current_role'] ?? $currentRole ?? '') ?>" placeholder="e.g., PHP Developer" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Target Role</label>
                                        <input list="role-suggestions-active" type="text" name="target_role" class="form-control" value="<?= esc($transition['target_role'] ?? '') ?>" placeholder="e.g., Frontend Developer" required>
                                        <datalist id="role-suggestions-active">
                                            <option value="Next.js Developer">
                                            <option value="React Developer">
                                            <option value="DevOps Engineer">
                                            <option value="DevOps Developer">
                                            <option value="Data Scientist">
                                            <option value="Data Analyst">
                                            <option value="Full Stack Developer">
                                            <option value="Frontend Developer">
                                            <option value="Backend Developer">
                                            <option value="Python Developer">
                                            <option value="Java Developer">
                                            <option value="Node.js Developer">
                                            <option value="Cloud Engineer">
                                            <option value="Machine Learning Engineer">
                                        </datalist>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary career-transition-submit-btn">
                                    <span><i class="fas fa-rocket"></i> Update Roadmap</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($skillGaps)): ?>
                    <div class="career-transition-note">
                        <h3>Skill Gaps</h3>
                        <div class="career-transition-chip-row">
                            <?php foreach ($skillGaps as $skill): ?>
                                <span class="career-transition-chip"><?= esc($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="career-transition-task-list">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h2 class="section-title mb-1">Daily Tasks</h2>
                                <p class="section-subtitle mb-0">Complete one task at a time to progress toward your target role.</p>
                            </div>
                            <span class="badge badge-primary"><?= $taskCount ?> tasks</span>
                        </div>

                        <?php if (!empty($tasks)): ?>
                            <div class="d-grid gap-3">
                                <?php foreach ($tasks as $task): ?>
                                    <div class="dashboard-panel transition-task-card <?= !empty($task['is_completed']) ? 'task-completed' : '' ?>">
                                        <div class="panel-body">
                                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                <div class="flex-grow-1">
                                                    <h4 class="mb-1">Day <?= (int) $task['day_number'] ?>: <?= esc($task['task_title']) ?></h4>
                                                    <p class="mb-0 text-muted transition-task-description"><?= esc($task['task_description']) ?></p>
                                                </div>
                                                <div>
                                                    <?php if (empty($task['is_completed'])): ?>
                                                        <button class="btn btn-sm btn-primary" onclick="completeTask(<?= (int) $task['id'] ?>)">
                                                            <i class="fas fa-check"></i> Complete
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge badge-primary"><i class="fas fa-check"></i> Done</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="dashboard-panel">
                                <div class="panel-body text-center py-4">
                                    <p class="text-muted mb-0" data-auto-refresh="1" data-refresh-delay="5000">
                                        Tasks are being generated. This page will refresh shortly.
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>
