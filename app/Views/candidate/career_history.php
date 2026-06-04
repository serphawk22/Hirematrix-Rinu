<?= view('Layouts/candidate_header', ['title' => 'Career Transition History']) ?>

<?php
$transitions = is_array($transitions ?? null) ? $transitions : [];
$totalPaths = count($transitions);
$activePaths = count(array_filter($transitions, static fn ($transition): bool => ($transition['status'] ?? '') === 'active'));
$savedPaths = max(0, $totalPaths - $activePaths);
?>

<div class="career-history-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-history"></i> Career paths</span>
                <h1 class="page-board-title">Career Transition History</h1>
                <p class="page-board-subtitle">Review, reactivate, or compare the learning paths you've already saved.</p>
            </div>
            <div class="page-board-actions">
                <a href="<?= base_url('career-transition') ?>" class="btn btn-primary">
                    <i class="fas fa-rocket mr-1"></i> Go to Career Transition
                </a>
            </div>
        </div>
    </div>

    <section class="site-section pt-0 content-wrap">
        <div class="container">
            <div class="history-summary-strip">
                <div class="history-summary-copy">
                    <span class="history-summary-kicker">Saved paths</span>
                    <h2>Your Saved Paths</h2>
                    <p>Switch back to a previous path without regenerating the plan.</p>
                </div>
                <div class="history-summary-metrics" aria-label="Career transition history summary">
                    <span><strong><?= (int) $totalPaths ?></strong> total</span>
                    <span><strong><?= (int) $activePaths ?></strong> active</span>
                    <span><strong><?= (int) $savedPaths ?></strong> saved</span>
                </div>
            </div>

            <?php if (empty($transitions)): ?>
                <div class="history-empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Career Transitions Yet</h3>
                    <p>Start your first career transition to see it here.</p>
                    <a href="<?= base_url('career-transition') ?>" class="btn btn-primary">
                        Start Career Transition
                    </a>
                </div>
            <?php else: ?>
                <div class="history-path-grid">
                    <?php foreach ($transitions as $transition): ?>
                        <?php $isActive = ($transition['status'] ?? '') === 'active'; ?>
                        <article class="history-panel <?= $isActive ? 'history-active history-active-card' : '' ?>">
                            <div class="history-card-head <?= $isActive ? 'history-active-header' : '' ?>">
                                <div class="history-status-label <?= $isActive ? 'history-active-header-text' : '' ?>">
                                    <?php if ($isActive): ?>
                                        <i class="fas fa-check-circle"></i> Active Path
                                    <?php else: ?>
                                        <i class="fas fa-archive"></i> Saved Path
                                    <?php endif; ?>
                                </div>
                                <span class="history-created-badge">
                                    <?= date('M d, Y', strtotime($transition['created_at'])) ?>
                                </span>
                            </div>

                            <div class="history-card-body">
                                <div class="history-role-flow">
                                    <div class="history-role-current"><?= esc($transition['current_role']) ?></div>
                                    <i class="fas fa-arrow-down"></i>
                                    <div class="history-role-target"><?= esc($transition['target_role']) ?></div>
                                </div>

                                <div class="history-skill-block">
                                    <h3><i class="fas fa-tasks"></i> Skill Gaps</h3>
                                    <div class="history-skill-list">
                                        <?php
                                        $skillGaps = json_decode($transition['skill_gaps'] ?? '[]', true);
                                        if ($skillGaps && is_array($skillGaps)):
                                            foreach (array_slice($skillGaps, 0, 3) as $skill): ?>
                                                <span><?= esc($skill) ?></span>
                                            <?php endforeach;
                                            if (count($skillGaps) > 3): ?>
                                                <span>+ <?= count($skillGaps) - 3 ?> more</span>
                                            <?php endif;
                                        else: ?>
                                            <span>No skill gaps recorded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="history-meta-grid">
                                    <div class="history-meta-item">
                                        <i class="fas fa-redo"></i>
                                        <strong><?= (int) ($transition['reactivation_count'] ?? 0) ?></strong>
                                        <span>Times Reused</span>
                                    </div>
                                    <div class="history-meta-item">
                                        <?php if (($transition['status'] ?? '') === 'inactive' && !empty($transition['deactivated_at'])): ?>
                                            <i class="fas fa-calendar-times"></i>
                                            <strong><?= date('M d, Y', strtotime($transition['deactivated_at'])) ?></strong>
                                            <span>Deactivated</span>
                                        <?php elseif (!empty($transition['reactivated_at'])): ?>
                                            <i class="fas fa-calendar-check"></i>
                                            <strong><?= date('M d, Y', strtotime($transition['reactivated_at'])) ?></strong>
                                            <span>Last Active</span>
                                        <?php else: ?>
                                            <i class="fas fa-calendar-plus"></i>
                                            <strong><?= date('M d, Y', strtotime($transition['created_at'])) ?></strong>
                                            <span>Created</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="history-card-actions">
                                <?php if ($isActive): ?>
                                    <button class="btn history-active-button" disabled>
                                        <i class="fas fa-check"></i> Currently Active
                                    </button>
                                <?php else: ?>
                                    <a href="<?= base_url('career-transition/reactivate/' . $transition['id']) ?>"
                                       class="btn btn-primary"
                                       onclick="return confirm('Reactivate this career path? Your current active path will be saved to history.')">
                                        <i class="fas fa-play"></i> Reactivate This Path
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="history-info-box mt-3">
                    <div class="history-info-head">
                        <i class="fas fa-info-circle"></i>
                        <h3>How It Works</h3>
                    </div>
                    <div class="history-info-grid">
                        <span><strong>Reactivate:</strong> Resume any previous learning journey instantly.</span>
                        <span><strong>No API Calls:</strong> Uses your saved course content immediately.</span>
                        <span><strong>Fresh Start:</strong> Task progress resets when reactivating a path.</span>
                        <span><strong>Flexible:</strong> Switch between different career paths anytime.</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>
