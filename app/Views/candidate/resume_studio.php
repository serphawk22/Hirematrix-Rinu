<?= view('Layouts/candidate_header', ['title' => 'AI Resume Studio']) ?>
<?php
$prefillGenerationMode = (string) (service('request')->getGet('generation_mode') ?? 'role');
if (!in_array($prefillGenerationMode, ['role', 'job'], true)) {
    $prefillGenerationMode = 'role';
}
$prefillJobId = (int) (service('request')->getGet('job_id') ?? 0);
$activeTransition = $activeTransition ?? null;
$resumeVersions = $resumeVersions ?? [];
$resumeTemplates = $resumeTemplates ?? [];
$blockedResumeTemplates = $blockedResumeTemplates ?? [];
$resumeTargets = $resumeTargets ?? [];
$profileReadiness = $profileReadiness ?? ['is_ready' => true, 'missing_details' => []];
?>

<div class="resume-studio-jobboard">
    <section class="content-wrap resume-studio-content-canvas">
        <div class="container-fluid">

            <div class="page-board-header page-board-header-tight">
                <div class="page-board-copy">
                    <span class="page-board-kicker"><i class="fas fa-file-alt"></i> AI-powered resume tools</span>
                    <h1 class="page-board-title">AI Resume Studio</h1>
                    <p class="page-board-subtitle">Generate polished role-based resumes, create job-specific versions, and keep a transition-ready primary resume.</p>
                </div>
                <div class="page-board-actions">
                    <a href="<?= base_url('candidate/profile') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-user mr-1"></i> My Profile
                    </a>
                    <?php if (!empty($activeTransition)): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('syncTransitionForm').submit()">
                            <i class="fas fa-sync-alt mr-1"></i> Sync Transition
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="resume-studio-body">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= esc(session()->getFlashdata('success')) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= esc(session()->getFlashdata('error')) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (!$profileReadiness['is_ready']): ?>
                <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                    <strong><i class="fas fa-exclamation-triangle mr-2"></i>Complete your profile before generating a resume.</strong>
                    <ul class="small mb-2 mt-2">
                        <?php foreach ($profileReadiness['missing_details'] as $detail): ?>
                            <li><?= esc($detail) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= base_url('candidate/profile') ?>" class="btn btn-warning btn-sm">Complete Profile</a>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <div class="resume-studio-layout">

                <div class="resume-studio-main">

                    <?php if (!empty($activeTransition)): ?>
                        <form method="post" action="<?= base_url('candidate/resume/sync-transition') ?>" id="syncTransitionForm" class="candidate-hidden-form">
                            <?= csrf_field() ?>
                        </form>
                        <div class="dashboard-panel resume-transition-panel mb-4">
                            <div class="panel-header">
                                <span class="page-board-kicker mb-1"><i class="fas fa-exchange-alt"></i> Active Transition</span>
                            </div>
                            <div class="panel-body">
                                <div class="resume-transition-flow">
                                    <div class="resume-transition-pill"><?= esc($activeTransition['current_role'] ?? 'Current role') ?></div>
                                    <i class="fas fa-arrow-right text-muted"></i>
                                    <div class="resume-transition-pill is-target"><?= esc($activeTransition['target_role'] ?? 'Target role') ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="dashboard-panel mb-4">
                        <div class="panel-header">
                            <h2 class="section-title mb-1">Generate Resume</h2>
                            <p class="section-subtitle mb-0">Choose a mode, pick a template, and generate an AI-tailored resume version.</p>
                        </div>
                        <div class="panel-body">
                            <form method="post" action="<?= base_url('candidate/resume/generate') ?>" data-loading-form>
                                <?= csrf_field() ?>

                                <div class="generation-mode-grid mb-3">
                                    <label class="generation-mode-option">
                                        <input type="radio" name="generation_mode" value="role" <?= $prefillGenerationMode === 'role' ? 'checked' : '' ?>>
                                        <span class="generation-mode-card candidate-text-left">
                                            <strong>By Role</strong>
                                            <small>Create a resume for a target role you enter manually.</small>
                                        </span>
                                    </label>
                                    <label class="generation-mode-option">
                                        <input type="radio" name="generation_mode" value="job" <?= $prefillGenerationMode === 'job' ? 'checked' : '' ?>>
                                        <span class="generation-mode-card candidate-text-left">
                                            <strong>For a Specific Job</strong>
                                            <small>Create a version tailored to one selected job posting.</small>
                                        </span>
                                    </label>
                                </div>

                                <div class="generation-role-field">
                                    <label class="form-label fw-semibold">Target Role</label>
                                    <input type="text" name="target_role" class="form-control" placeholder="e.g. Product Designer, PHP Developer">
                                    <small class="text-muted d-block mt-1">Used only for role-based generation.</small>
                                </div>

                                <div class="generation-panel <?= $prefillGenerationMode === 'job' ? 'is-active' : '' ?>" data-generation-panel="job">
                                    <label class="form-label fw-semibold">Select Job</label>
                                    <select name="job_id" class="form-control resume-studio-select">
                                        <option value="">Select a job</option>
                                        <?php foreach (($resumeTargets ?? []) as $target): ?>
                                            <option value="<?= (int) $target['job_id'] ?>" <?= $prefillJobId === (int) $target['job_id'] ? 'selected' : '' ?>><?= esc($target['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted d-block mt-1">Used only for job-specific generation.</small>
                                </div>

                                <div class="generation-panel is-active">
                                    <label class="form-label fw-semibold">Choose Template</label>
                                    <div class="template-grid">
                                        <?php foreach (($resumeTemplates ?? []) as $templateKey => $template): ?>
                                            <?php $disabledMessage = (string) ($blockedResumeTemplates[$templateKey] ?? ''); ?>
                                            <?php $isTemplateDisabled = $disabledMessage !== ''; ?>
                                            <?php $previewClass = (string) ($template['preview_class'] ?? 'modern'); ?>
                                            <label class="template-option <?= $isTemplateDisabled ? 'is-disabled' : '' ?>">
                                                <input type="radio" name="template_key" value="<?= esc($templateKey) ?>" <?= $templateKey === 'modern_professional' ? 'checked' : '' ?> <?= $isTemplateDisabled ? 'disabled' : '' ?>>
                                                <span class="template-card candidate-text-left">
                                                    <span class="template-preview <?= esc($previewClass) ?>"></span>
                                                    <strong class="d-block mb-1"><?= esc($template['label']) ?></strong>
                                                    <small class="text-muted d-block"><?= esc($template['description']) ?></small>
                                                    <?php if ($isTemplateDisabled): ?>
                                                        <small class="template-disabled-note"><i class="fas fa-info-circle"></i> <?= esc($disabledMessage) ?></small>
                                                    <?php endif; ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" value="1" id="makePrimaryResumeVersion" name="make_primary">
                                        <label class="form-check-label" for="makePrimaryResumeVersion">Set as primary resume</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary" data-loading-button>
                                        <span class="btn-submit-text"><i class="fas fa-magic mr-1"></i> Generate Resume</span>
                                        <span class="btn-loading-state" aria-hidden="true"><i class="fas fa-spinner fa-spin"></i> Generating...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>

                <aside class="resume-studio-side">
                    <div class="dashboard-panel resume-how-panel">
                        <div class="panel-header">
                            <h3 class="section-title mb-0 candidate-heading-compact">How It Works</h3>
                        </div>
                        <div class="panel-body">
                            <ul class="resume-studio-tip-list">
                                <li>Choose a generation mode to tailor the resume for a role or a specific job.</li>
                                <li>Select a template that matches the tone of the target role.</li>
                                <li>Mark a version as primary when you want it to be your default resume.</li>
                            </ul>
                        </div>
                    </div>

                    <a href="#resume-versions" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-clipboard-list mr-1"></i> View Saved Versions
                    </a>
                </aside>

            </div>

            <!-- Saved Versions -->
            <div class="dashboard-panel" id="resume-versions">
                <div class="panel-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h2 class="section-title mb-1">Saved Resume Versions</h2>
                            <p class="section-subtitle mb-0">One saved version per role, one per job target — no duplicates.</p>
                        </div>
                        <a href="<?= base_url('candidate/profile') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-user mr-1"></i> Back to Profile
                        </a>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if (!empty($resumeVersions)): ?>
                        <div class="resume-versions-grid">
                            <?php foreach ($resumeVersions as $version): ?>
                                <article class="dashboard-panel resume-version-card">
                                    <div class="panel-header">
                                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?= esc($version['title'] ?? 'Resume Version') ?>
                                                    <?php if ((int) ($version['is_primary'] ?? 0) === 1): ?>
                                                        <span class="badge badge-primary ml-2">Primary</span>
                                                    <?php endif; ?>
                                                    <span class="badge badge-<?= $version['strength_class'] ?? 'secondary' ?> ml-2">
                                                        <?= (int)($version['strength_score'] ?? 0) ?>% match
                                                    </span>
                                                </h6>
                                                <p class="text-muted small mb-0">
                                                    <?= esc($version['target_role'] ?? '-') ?>
                                                    <?php if (!empty($version['job_title'])): ?> &middot; <?= esc($version['job_title']) ?><?php endif; ?>
                                                    <?php if (!empty($version['template_label'])): ?> &middot; <?= esc($version['template_label']) ?><?php endif; ?>
                                                    &middot; <?= esc(ucwords(str_replace('_', ' ', (string) ($version['generation_source'] ?? 'role_based')))) ?>
                                                </p>
                                            </div>
                                            <div class="resume-version-actions">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="previewResumeVersion(<?= (int)$version['id'] ?>)" title="Preview">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="<?= base_url('candidate/resume-version/' . (int) $version['id'] . '/download') ?>" class="btn btn-outline-secondary btn-sm" title="Download PDF">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <form method="post" action="<?= base_url('candidate/resume-version/' . (int) $version['id'] . '/delete') ?>" onsubmit="return confirm('Delete this resume version?');" class="candidate-inline-block-form">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
                                                </form>
                                                <?php if ((int) ($version['is_primary'] ?? 0) !== 1): ?>
                                                    <form method="post" action="<?= base_url('candidate/resume-version/' . (int) $version['id'] . '/primary') ?>" class="candidate-inline-block-form">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-outline-primary btn-sm resume-version-set-primary">Set primary</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-body">
                                        <?php if (!empty($version['summary'])): ?>
                                            <p class="text-muted small mb-3 resume-summary-clamp"><?= esc($version['summary']) ?></p>
                                        <?php endif; ?>
                                        <div class="resume-version-content resume-version-preview">
                                            <?= $version['rendered_preview'] ?? '' ?>
                                            <div class="resume-version-fade"></div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-2x text-muted mb-3 d-block"></i>
                            <h6 class="mb-1">No resume versions yet</h6>
                            <p class="text-muted mb-0">Generate your first role-based or job-specific resume version above.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>

        </div>
    </section>
</div>

<!-- Resume Preview Modal -->
<div class="modal fade" id="resumePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye mr-2"></i> Resume Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div id="previewLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Rendering preview...</p>
                </div>
                <div id="previewIframeContainer" class="d-none">
                    <iframe id="resumePreviewIframe" class="resume-preview-frame"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function previewResumeVersion(versionId) {
    const modal = $('#resumePreviewModal');
    const iframe = document.getElementById('resumePreviewIframe');
    const loading = document.getElementById('previewLoading');
    const container = document.getElementById('previewIframeContainer');
    loading.classList.remove('d-none');
    container.classList.add('d-none');
    modal.modal('show');
    iframe.src = `<?= base_url('candidate/resume-version') ?>/${versionId}/preview`;
    iframe.onload = function() {
        loading.classList.add('d-none');
        container.classList.remove('d-none');
    };
}
</script>

<?= view('Layouts/candidate_footer') ?>
