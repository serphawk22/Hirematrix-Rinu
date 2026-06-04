<?= view('Layouts/candidate_header', ['title' => esc($module['title'] ?? 'Course Module')]) ?>

<div class="course-content-jobboard">
    <div class="offline-badge online" id="offlineStatus">Online</div>

    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-book-open"></i> Learning content</span>
                <h1 class="page-board-title"><?= esc($module['title']) ?></h1>
                <p class="page-board-subtitle">Read through the lessons, resources, and practice exercises for this module.</p>
            </div>
            <div class="page-board-actions">
                <a href="<?= base_url('career-transition/course') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left mr-1"></i> All Modules
                </a>
            </div>
        </div>
    </div>

    <section class="site-section pt-0 content-wrap">
        <div class="container">
            <div class="course-header-card mb-4">
                <div class="card-body d-flex justify-content-between align-items-start flex-wrap transition-header-row">
                    <div>
                        <span class="badge badge-light mb-2">Module <?= (int) $module['module_number'] ?></span>
                        <h4 class="mb-1"><?= esc($module['title']) ?></h4>
                        <p class="text-muted mb-0"><?= esc($module['description']) ?></p>
                        <small class="text-muted d-block mt-2"><i class="far fa-clock"></i> <?= (int) $module['duration_weeks'] ?> week(s)</small>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="<?= base_url('career-transition/course') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> All Modules
                        </a>
                    </div>
                </div>
            </div>

            <?php if (empty($lessons)): ?>
                <div class="alert alert-warning">No lessons available for this module.</div>
            <?php else: ?>
                <?php foreach ($lessons as $lesson): ?>
                    <div class="course-lesson-card mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <span class="course-lesson-number"><?= (int) $lesson['lesson_number'] ?></span>
                                <h5 class="mb-0"><?= esc($lesson['title']) ?></h5>
                            </div>

                            <div class="course-lesson-content">
                                <?= nl2br(esc($lesson['content'])) ?>
                            </div>

                            <div class="mt-4">
                                <h6 class="course-section-title"><i class="fas fa-book"></i> Learning Resources</h6>
                                <div>
                                    <?php
                                    $resources = is_string($lesson['resources']) ? json_decode($lesson['resources'], true) : $lesson['resources'];
                                    if (!empty($resources) && is_array($resources)):
                                        foreach ($resources as $resource):
                                            if (filter_var($resource, FILTER_VALIDATE_URL)): ?>
                                                <a href="<?= esc($resource) ?>" target="_blank" class="course-resource-link">
                                                    <i class="fas fa-link"></i> <?= esc(parse_url($resource, PHP_URL_HOST)) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="course-resource-link muted"><?= esc($resource) ?></span>
                                            <?php endif;
                                        endforeach;
                                    else: ?>
                                        <p class="text-muted mb-0">No additional resources for this lesson.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h6 class="course-section-title"><i class="fas fa-pen"></i> Practice Exercises</h6>
                                <?php
                                $exercises = is_string($lesson['exercises']) ? json_decode($lesson['exercises'], true) : $lesson['exercises'];
                                if (!empty($exercises) && is_array($exercises)):
                                    foreach ($exercises as $index => $exercise): ?>
                                        <div class="course-exercise-item">
                                            <strong>Exercise <?= (int) $index + 1 ?>:</strong> <?= esc($exercise) ?>
                                        </div>
                                    <?php endforeach;
                                else: ?>
                                    <p class="text-muted mb-0">No exercises for this lesson.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="text-center mt-4">
                    <a href="<?= base_url('career-transition/course') ?>" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-arrow-left"></i> Back to All Modules
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>
