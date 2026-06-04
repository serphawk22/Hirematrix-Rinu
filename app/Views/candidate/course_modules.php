<?= view('Layouts/candidate_header', ['title' => 'Course Modules']) ?>

<div class="course-modules-jobboard">
    <div class="offline-badge online" id="offlineStatus">Online</div>

    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-graduation-cap"></i> Career learning path</span>
                <h1 class="page-board-title">Course Modules</h1>
                <p class="page-board-subtitle">Open a module to continue your role transition roadmap and keep learning at your own pace.</p>
            </div>
            <div class="page-board-actions">
                <a href="<?= base_url('career-transition') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <section class="site-section pt-0 content-wrap">
        <div class="container">
            <div class="course-header-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap transition-header-row">
                        <div class="flex-grow-1">
                            <h3 class="mb-1">Your Learning Journey</h3>
                            <h5 class="mb-2"><?= esc($transition['current_role']) ?> to <?= esc($transition['target_role']) ?></h5>
                            <p class="text-muted mb-0">Open any module to start learning. Course content is available for offline-ready usage.</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <a href="<?= base_url('career-transition') ?>" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="course-download-box mt-4">
                        <h6><i class="fas fa-file-pdf text-danger"></i> Download Offline PDF</h6>
                        <p class="text-muted mb-3">Get a complete PDF with modules, lessons, resources, and exercises.</p>
                        <button class="btn btn-primary" id="coursePdfBtn" data-download-url="<?= base_url('career-transition/download-pdf') ?>">
                            <span id="coursePdfBtnText"><i class="fas fa-download"></i> Download Complete Course PDF</span>
                            <span id="coursePdfBtnLoading" class="transition-btn-loading"><i class="fas fa-spinner fa-spin"></i> Generating PDF...</span>
                        </button>
                    </div>
                </div>
            </div>

            <?php if (empty($modules)): ?>
                <div class="alert alert-info">
                    <h5>No course content available yet.</h5>
                    <p class="mb-0">Generate your career transition roadmap first.</p>
                </div>
            <?php else: ?>
                <div class="course-module-grid">
                <?php foreach ($modules as $module): ?>
                    <a href="<?= base_url('career-transition/module/' . (int) $module['id']) ?>" class="course-module-card">
                        <div class="course-module-number"><?= (int) $module['module_number'] ?></div>
                        <div class="course-module-content">
                            <h5 class="mb-1"><?= esc($module['title']) ?></h5>
                            <p class="text-muted mb-2"><?= esc($module['description']) ?></p>
                            <span class="course-module-duration"><i class="far fa-clock"></i> <?= (int) $module['duration_weeks'] ?> week(s)</span>
                        </div>
                        <span class="course-module-arrow"><i class="fas fa-arrow-right"></i></span>
                    </a>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>
