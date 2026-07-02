<?= view('Layouts/candidate_header', ['title' => esc($module['title'] ?? 'Course Module')]) ?>
<?php
$modules = is_array($modules ?? null) ? $modules : [];
$moduleGaps = is_array($module['covered_skill_gaps'] ?? null) ? $module['covered_skill_gaps'] : [];
$completedLessons = count(array_filter($lessons ?? [], static fn ($lesson): bool => !empty($lesson['is_completed'])));
$lessonCount = count($lessons ?? []);
?>

<div class="course-content-jobboard course-service-jobboard">
    <div class="offline-badge online" id="offlineStatus">Online</div>

    <section class="course-service-canvas">
        <div class="container-fluid">
            <div class="page-board-header page-board-header-tight">
                <div class="page-board-copy">
                    <span class="page-board-kicker"><i class="fas fa-book-open"></i> Learning content</span>
                    <h1 class="page-board-title"><?= esc($module['title']) ?></h1>
                    <p class="page-board-subtitle">Switch modules and open lessons from one place without returning to a separate directory.</p>
                </div>
                <div class="page-board-actions">
                    <a href="<?= base_url('career-transition') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Career Transition
                    </a>
                    <a href="<?= base_url('career-transition') ?>#change-transition-form" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Change Path
                    </a>
                </div>
            </div>

            <?php if (!empty($modules)): ?>
                <div class="course-module-tabs" role="tablist" aria-label="Course modules">
                    <?php foreach ($modules as $navModule): ?>
                        <?php $isCurrentModule = (int) $navModule['id'] === (int) $module['id']; ?>
                        <a href="<?= base_url('career-transition/module/' . (int) $navModule['id']) ?>"
                           class="course-module-tab <?= $isCurrentModule ? 'is-active' : '' ?>"
                           role="tab"
                           data-course-module-tab
                           data-module-id="<?= (int) $navModule['id'] ?>"
                           aria-selected="<?= $isCurrentModule ? 'true' : 'false' ?>">
                            <span>Module <?= (int) $navModule['module_number'] ?></span>
                            <strong><?= esc($navModule['title'] ?? 'Module') ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div id="courseModuleContent" data-current-module-id="<?= (int) $module['id'] ?>">
            <div class="course-header-card mb-4" data-course-module-header>
                <div class="card-body d-flex justify-content-between align-items-start flex-wrap transition-header-row">
                    <div>
                        <span class="badge badge-light mb-2">Module <?= (int) $module['module_number'] ?></span>
                        <h4 class="mb-1"><?= esc($module['title']) ?></h4>
                        <p class="text-muted mb-0"><?= esc($module['description']) ?></p>
                        <small class="text-muted d-block mt-2"><i class="far fa-clock"></i> <?= (int) $module['duration_weeks'] ?> week(s)</small>
                        <?php if (!empty($moduleGaps)): ?>
                            <div class="course-module-gap-row mt-3">
                                <span class="course-module-gap-label">This module covers</span>
                                <?php foreach ($moduleGaps as $gap): ?>
                                    <span class="course-module-gap"><?= esc($gap) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="course-progress-summary">
                        <strong><?= (int) $completedLessons ?>/<?= (int) $lessonCount ?></strong>
                        <span>lessons complete</span>
                    </div>
                </div>
            </div>

            <div data-course-lessons-list>
                <?php if (empty($lessons)): ?>
                    <div class="alert alert-warning">No lessons available for this module.</div>
                <?php else: ?>
                    <?php foreach ($lessons as $lesson): ?>
                        <div class="course-lesson-card mb-4 <?= !empty($lesson['is_completed']) ? 'lesson-completed' : '' ?>" data-course-lesson-card data-lesson-id="<?= (int) $lesson['id'] ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                    <div class="d-flex align-items-center">
                                        <span class="course-lesson-number"><?= (int) $lesson['lesson_number'] ?></span>
                                        <div>
                                            <h5 class="mb-1"><?= esc($lesson['title']) ?></h5>
                                            <?php if (!empty($lesson['covered_skill_gaps'])): ?>
                                                <div class="course-module-gap-row">
                                                    <span class="course-module-gap-label">Gaps</span>
                                                    <?php foreach ($lesson['covered_skill_gaps'] as $gap): ?>
                                                        <span class="course-module-gap"><?= esc($gap) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="course-lesson-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary js-load-course-lesson" data-lesson-id="<?= (int) $lesson['id'] ?>" aria-expanded="false">
                                            <i class="fas fa-book-open"></i> Open Lesson
                                        </button>
                                        <?php if (empty($lesson['is_completed'])): ?>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="completeLesson(<?= (int) $lesson['id'] ?>)">
                                                <i class="fas fa-check"></i> Mark Complete
                                            </button>
                                        <?php else: ?>
                                            <span class="badge badge-primary"><i class="fas fa-check"></i> Complete</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="course-lesson-detail" data-course-lesson-detail hidden>
                                    <div class="course-lesson-loading">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                        Loading lesson content...
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>
