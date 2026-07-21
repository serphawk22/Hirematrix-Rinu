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
                    <button type="button"
                            class="btn btn-primary"
                            data-course-pdf-button
                            data-prepare-url="<?= base_url('career-transition/prepare-pdf') ?>"
                            data-download-url="<?= base_url('career-transition/download-pdf') ?>"
                            data-csrf-name="<?= csrf_token() ?>"
                            data-csrf-value="<?= csrf_hash() ?>">
                        <i class="fas fa-file-pdf"></i>
                        <span data-pdf-label>Download PDF</span>
                    </button>
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
                                        <div class="course-lesson-loading-title">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                            <span>Preparing full lesson...</span>
                                        </div>
                                        <div class="course-lesson-loading-track" aria-hidden="true"><span></span></div>
                                        <div class="course-lesson-loading-lines" aria-hidden="true">
                                            <span></span><span></span><span></span>
                                        </div>
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

<style>
.course-content-jobboard .lesson-preparing-animation{display:grid;gap:12px;max-width:720px;padding:14px 0 6px}
.course-content-jobboard .lesson-preparing-head{display:flex;align-items:center;gap:9px;color:var(--muted-foreground);font-weight:600;font-size:14px}
.course-content-jobboard .lesson-preparing-orbit{position:relative;width:20px;height:20px;border:2px solid rgba(31,183,181,.2);border-top-color:var(--primary);border-radius:50%;animation:lessonPreparingSpin .8s linear infinite}
.course-content-jobboard .lesson-preparing-progress{height:4px;overflow:hidden;border-radius:999px;background:rgba(31,183,181,.12)}
.course-content-jobboard .lesson-preparing-progress span{display:block;width:34%;height:100%;border-radius:inherit;background:var(--primary);animation:lessonPreparingMove 1.25s ease-in-out infinite}
.course-content-jobboard .lesson-preparing-skeleton{display:grid;gap:8px}
.course-content-jobboard .lesson-preparing-skeleton span{height:9px;border-radius:999px;background:rgba(31,183,181,.13);animation:lessonPreparingGlow 1.25s ease-in-out infinite}
.course-content-jobboard .lesson-preparing-skeleton span:nth-child(1){width:92%}
.course-content-jobboard .lesson-preparing-skeleton span:nth-child(2){width:74%;animation-delay:.14s}
.course-content-jobboard .lesson-preparing-skeleton span:nth-child(3){width:55%;animation-delay:.28s}
@keyframes lessonPreparingSpin{to{transform:rotate(360deg)}}
@keyframes lessonPreparingMove{from{transform:translateX(-110%)}to{transform:translateX(310%)}}
@keyframes lessonPreparingGlow{0%,100%{opacity:.28}50%{opacity:.9}}
@media (prefers-reduced-motion:reduce){.course-content-jobboard .lesson-preparing-animation *{animation:none!important}}
</style>
<script>
(function () {
    var loadingMarkup = '<div class="lesson-preparing-animation" role="status" aria-live="polite">' +
        '<div class="lesson-preparing-head"><span class="lesson-preparing-orbit" aria-hidden="true"></span><span>Building your full lesson...</span></div>' +
        '<div class="lesson-preparing-progress" aria-hidden="true"><span></span></div>' +
        '<div class="lesson-preparing-skeleton" aria-hidden="true"><span></span><span></span><span></span></div>' +
    '</div>';

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.js-load-course-lesson');
        if (!button) return;
        window.setTimeout(function () {
            var card = button.closest('[data-course-lesson-card]');
            var detail = card ? card.querySelector('[data-course-lesson-detail]') : null;
            if (detail && detail.getAttribute('data-loaded') !== '1' && !detail.querySelector('.lesson-preparing-animation')) {
                detail.innerHTML = loadingMarkup;
            }
        }, 0);
    });
})();
</script>

<?= view('Layouts/candidate_footer') ?>
