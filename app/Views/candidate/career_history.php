<?= view('Layouts/candidate_header', ['title' => 'Career Transition History']) ?>

<div class="career-history-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-history"></i> Career paths</span>
                <h1 class="page-board-title">Career Transition History</h1>
                <p class="page-board-subtitle">Review, reactivate, or compare the learning paths you’ve already saved.</p>
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
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap candidate-flex-gap-12">
                <h3 class="mb-0">Your Saved Paths</h3>
                <a href="<?= base_url('career-transition') ?>" class="btn btn-primary">
                    <i class="fas fa-rocket"></i> Go to Career Transition
                </a>
            </div>

            <?php if (empty($transitions)): ?>
                <div class="card history-panel text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                        <h5>No Career Transitions Yet</h5>
                        <p class="text-muted">Start your first career transition to see it here.</p>
                        <a href="<?= base_url('career-transition') ?>" class="btn btn-primary">
                            Start Career Transition
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($transitions as $transition): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card history-panel h-100 <?= $transition['status'] === 'active' ? 'history-active history-active-card' : '' ?>">
                                <div class="card-header <?= $transition['status'] === 'active' ? 'history-active-header' : 'bg-white' ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0 <?= $transition['status'] === 'active' ? 'history-active-header-text' : '' ?>">
                                            <?php if ($transition['status'] === 'active'): ?>
                                                <i class="fas fa-check-circle"></i> Active Path
                                            <?php else: ?>
                                                <i class="fas fa-archive"></i> Saved Path
                                            <?php endif; ?>
                                        </h5>
                                        <span class="badge badge-<?= $transition['status'] === 'active' ? 'light' : 'secondary' ?> history-created-badge">
                                            Created: <?= date('M d, Y', strtotime($transition['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <h6 class="history-role-current"><?= esc($transition['current_role']) ?></h6>
                                        <i class="fas fa-arrow-down fa-2x my-2 text-muted"></i>
                                        <h6 class="history-role-target"><?= esc($transition['target_role']) ?></h6>
                                    </div>

                                    <div class="mt-3">
                                        <h6><i class="fas fa-tasks"></i> Skill Gaps</h6>
                                        <ul class="small mb-0 pl-3">
                                            <?php
                                            $skillGaps = json_decode($transition['skill_gaps'], true);
                                            if ($skillGaps && is_array($skillGaps)):
                                                foreach (array_slice($skillGaps, 0, 3) as $skill): ?>
                                                    <li><?= esc($skill) ?></li>
                                                <?php endforeach;
                                                if (count($skillGaps) > 3): ?>
                                                    <li class="text-muted">+ <?= count($skillGaps) - 3 ?> more</li>
                                                <?php endif;
                                            else: ?>
                                                <li class="text-muted">No skill gaps recorded</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>

                                    <div class="mt-3">
                                        <div class="row text-center small">
                                            <div class="col-6">
                                                <i class="fas fa-redo text-info"></i>
                                                <div><strong><?= $transition['reactivation_count'] ?? 0 ?></strong></div>
                                                <div class="text-muted">Times Reused</div>
                                            </div>
                                            <div class="col-6">
                                                <?php if ($transition['status'] === 'inactive' && !empty($transition['deactivated_at'])): ?>
                                                    <i class="fas fa-calendar-times text-warning"></i>
                                                    <div><strong><?= date('M d, Y', strtotime($transition['deactivated_at'])) ?></strong></div>
                                                    <div class="text-muted">Deactivated</div>
                                                <?php elseif (!empty($transition['reactivated_at'])): ?>
                                                    <i class="fas fa-calendar-check text-success"></i>
                                                    <div><strong><?= date('M d, Y', strtotime($transition['reactivated_at'])) ?></strong></div>
                                                    <div class="text-muted">Last Active</div>
                                                <?php else: ?>
                                                    <i class="fas fa-calendar-plus text-primary"></i>
                                                    <div><strong><?= date('M d, Y', strtotime($transition['created_at'])) ?></strong></div>
                                                    <div class="text-muted">Created</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer bg-white">
                                    <?php if ($transition['status'] === 'active'): ?>
                                        <button class="btn btn-block history-active-button" disabled>
                                            <i class="fas fa-check"></i> Currently Active
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= base_url('career-transition/reactivate/' . $transition['id']) ?>"
                                           class="btn btn-primary btn-block"
                                           onclick="return confirm('Reactivate this career path? Your current active path will be saved to history.')">
                                            <i class="fas fa-play"></i> Reactivate This Path
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="alert alert-info history-info-box mt-3">
                    <h5><i class="fas fa-info-circle"></i> How It Works</h5>
                    <ul class="mb-0 pl-3">
                        <li><strong>Reactivate:</strong> Resume any previous learning journey instantly.</li>
                        <li><strong>No API Calls:</strong> Uses your saved course content immediately.</li>
                        <li><strong>Fresh Start:</strong> Task progress resets when reactivating a path.</li>
                        <li><strong>Flexible:</strong> Switch between different career paths anytime.</li>
                        <li><strong>Persistent:</strong> Your transition history stays saved.</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>
