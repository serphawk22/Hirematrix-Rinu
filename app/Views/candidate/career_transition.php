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

<style>
    .career-transition-service-jobboard .career-transition-content {
        padding-top: 26px;
    }

    .career-transition-service-jobboard .page-board-header {
        border: 1px solid rgba(31, 183, 181, 0.14);
        border-radius: 22px;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 252, 251, 0.96));
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.045);
        min-height: 138px;
    }

    .career-transition-service-jobboard .career-transition-simple-layout {
        gap: 16px;
    }

    .career-transition-service-jobboard .dashboard-panel,
    .career-transition-service-jobboard .career-transition-note {
        border: 1px solid rgba(31, 183, 181, 0.16);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.045);
    }

    .career-transition-service-jobboard .dashboard-panel .panel-body {
        padding: 20px;
    }

    .career-transition-service-jobboard .career-transition-overview {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 18px;
    }

    .career-transition-service-jobboard .career-transition-path {
        min-height: 92px;
        padding: 18px;
        border: 1px solid rgba(31, 183, 181, 0.18);
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(31, 183, 181, 0.06), rgba(83, 184, 108, 0.045));
        text-align: left;
    }

    .career-transition-service-jobboard .career-transition-path-label,
    .career-transition-service-jobboard .page-board-kicker {
        color: #007f86;
        letter-spacing: .08em;
        font-size: 11px;
        font-weight: 800;
    }

    .career-transition-service-jobboard .career-transition-role-flow {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 10px;
    }

    .career-transition-service-jobboard .career-transition-role {
        font-size: clamp(1.05rem, 1.3vw, 1.35rem);
        font-weight: 800;
        color: #0f172a;
    }

    .career-transition-service-jobboard .career-transition-role-target {
        color: #007f86;
    }

    .career-transition-service-jobboard .career-transition-path-arrow {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(31, 183, 181, 0.1);
        color: #007f86;
        font-size: 14px;
        flex: 0 0 auto;
    }

    .career-transition-service-jobboard .career-transition-overview-side {
        display: grid;
        justify-items: end;
        gap: 12px;
    }

    .career-transition-service-jobboard .career-transition-stats,
    .career-transition-service-jobboard .career-transition-actions {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 10px;
    }

    .career-transition-service-jobboard .career-transition-stats span {
        min-height: 34px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 12px;
        border: 1px solid rgba(31, 183, 181, 0.22);
        border-radius: 999px;
        background: rgba(31, 183, 181, 0.07);
        color: #007f86;
        font-size: 12px;
        font-weight: 700;
    }

    .career-transition-service-jobboard .career-transition-stats strong {
        color: #0f172a;
        font-size: 13px;
    }

    .career-transition-service-jobboard .career-transition-actions {
        margin-top: 0;
    }

    .career-transition-service-jobboard .career-transition-actions .btn,
    .career-transition-service-jobboard .page-board-actions .btn,
    .career-transition-service-jobboard .career-transition-submit-btn {
        min-height: 40px;
        border-radius: 8px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
    }

    .career-transition-service-jobboard .career-transition-change-card .d-flex {
        padding-bottom: 12px;
        margin-bottom: 16px !important;
        border-bottom: 1px solid #edf4f3;
    }

    .career-transition-service-jobboard .career-transition-change-card .badge {
        border: 1px solid rgba(31, 183, 181, 0.18);
        background: rgba(31, 183, 181, 0.06);
        color: #0f172a;
        border-radius: 999px;
        padding: 7px 10px;
    }

    .career-transition-service-jobboard .career-transition-change-card label {
        margin-bottom: 8px;
        color: #0f172a;
        font-size: 13px;
        font-weight: 700;
    }

    .career-transition-service-jobboard .career-transition-change-card .form-control {
        min-height: 42px;
        border-color: rgba(31, 183, 181, 0.22);
        border-radius: 10px;
        background: #fff;
        box-shadow: none;
    }

    .career-transition-service-jobboard .career-transition-change-card .form-control:focus {
        border-color: rgba(31, 183, 181, 0.7);
        box-shadow: 0 0 0 3px rgba(31, 183, 181, 0.12);
    }

    .career-transition-service-jobboard .career-transition-note {
        padding: 16px 18px;
    }

    .career-transition-service-jobboard .career-transition-chip-row {
        gap: 8px;
    }

    .career-transition-service-jobboard .career-transition-chip {
        min-height: 30px;
        padding: 6px 12px;
        border-color: rgba(31, 183, 181, 0.2);
        background: rgba(31, 183, 181, 0.07);
        color: #007f86;
        font-weight: 700;
    }

    body.dark.candidate-app .career-transition-service-jobboard .page-board-header,
    body.dark.candidate-app .career-transition-service-jobboard .dashboard-panel,
    body.dark.candidate-app .career-transition-service-jobboard .career-transition-note {
        background: #111;
        border-color: #272727;
        box-shadow: none;
    }

    body.dark.candidate-app .career-transition-service-jobboard .career-transition-path {
        background: #181818;
        border-color: rgba(31, 183, 181, 0.25);
    }

    body.dark.candidate-app .career-transition-service-jobboard .career-transition-role,
    body.dark.candidate-app .career-transition-service-jobboard .career-transition-stats strong,
    body.dark.candidate-app .career-transition-service-jobboard .career-transition-change-card label {
        color: #f4f4f5;
    }

    @media (max-width: 991.98px) {
        .career-transition-service-jobboard .career-transition-overview {
            grid-template-columns: 1fr;
        }

        .career-transition-service-jobboard .career-transition-overview-side {
            justify-items: start;
        }

        .career-transition-service-jobboard .career-transition-stats,
        .career-transition-service-jobboard .career-transition-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 575.98px) {
        .career-transition-service-jobboard .dashboard-panel .panel-body {
            padding: 16px;
        }

        .career-transition-service-jobboard .career-transition-actions .btn,
        .career-transition-service-jobboard .career-transition-submit-btn {
            width: 100%;
        }

        .career-transition-service-jobboard .career-transition-role-flow {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    .career-transition-service-jobboard .career-transition-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 18px;
    }

    .career-transition-service-jobboard .career-transition-overview-card {
        overflow: hidden;
        border: 0;
        background: #073f46;
        box-shadow: 0 18px 42px rgba(7, 63, 70, 0.18);
    }

    .career-transition-service-jobboard .career-transition-overview-card .panel-body {
        padding: 0;
    }

    .career-transition-service-jobboard .career-transition-overview {
        grid-template-columns: minmax(0, 1fr) 350px;
        gap: 0;
        min-height: 164px;
    }

    .career-transition-service-jobboard .career-transition-path {
        min-height: 164px;
        padding: 28px 30px;
        border: 0;
        border-radius: 0;
        background:
            linear-gradient(135deg, rgba(31, 183, 181, 0.18), rgba(83, 184, 108, 0.08)),
            #073f46;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .career-transition-service-jobboard .career-transition-path-label {
        color: rgba(255, 255, 255, 0.78);
    }

    .career-transition-service-jobboard .career-transition-role {
        color: #fff;
        font-size: clamp(1.35rem, 2vw, 2rem);
        line-height: 1.15;
    }

    .career-transition-service-jobboard .career-transition-role-target {
        color: #9ff3df;
    }

    .career-transition-service-jobboard .career-transition-path-arrow {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.13);
        color: #fff;
    }

    .career-transition-service-jobboard .career-transition-path-copy {
        margin: 12px 0 0;
        color: rgba(255, 255, 255, 0.74);
        font-size: 14px;
        line-height: 1.55;
        max-width: 720px;
    }

    .career-transition-service-jobboard .career-transition-overview-side {
        height: 100%;
        padding: 22px;
        justify-items: stretch;
        align-content: center;
        background: #ffffff;
        border-left: 1px solid rgba(31, 183, 181, 0.18);
    }

    .career-transition-service-jobboard .career-transition-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .career-transition-service-jobboard .career-transition-stats span {
        min-height: 62px;
        border-radius: 12px;
        flex-direction: column;
        justify-content: center;
        gap: 3px;
        padding: 10px;
        text-align: center;
        background: #f3fbfa;
    }

    .career-transition-service-jobboard .career-transition-stats strong {
        font-size: 18px;
        line-height: 1;
    }

    .career-transition-service-jobboard .career-transition-actions {
        display: grid;
        grid-template-columns: 1fr 74px 1fr;
        gap: 8px;
    }

    .career-transition-service-jobboard .career-transition-actions .btn {
        width: 100%;
        min-height: 44px;
        padding-inline: 12px;
    }

    .career-transition-service-jobboard .career-transition-lower-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 390px;
        gap: 18px;
        align-items: stretch;
    }

    .career-transition-service-jobboard .career-transition-change-card,
    .career-transition-service-jobboard .career-transition-gap-card {
        min-height: 100%;
    }

    .career-transition-service-jobboard .career-transition-change-card {
        border-left: 4px solid #1fb7b5;
    }

    .career-transition-service-jobboard .career-transition-change-card .section-title {
        font-size: 1.35rem;
    }

    .career-transition-service-jobboard .career-transition-change-card .row {
        margin-left: -8px;
        margin-right: -8px;
    }

    .career-transition-service-jobboard .career-transition-change-card .row > [class*="col-"] {
        padding-left: 8px;
        padding-right: 8px;
    }

    .career-transition-service-jobboard .career-transition-gap-card {
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: linear-gradient(180deg, #ffffff 0%, #f4fbfa 100%);
    }

    .career-transition-service-jobboard .career-transition-gap-card h3 {
        font-size: 1.08rem;
        margin-bottom: 12px;
    }

    .career-transition-service-jobboard .career-transition-gap-card .career-transition-chip {
        width: 100%;
        justify-content: flex-start;
        border-radius: 10px;
        white-space: normal;
        line-height: 1.35;
    }

    body.dark.candidate-app .career-transition-service-jobboard .career-transition-overview-card {
        background: #111;
        box-shadow: none;
    }

    body.dark.candidate-app .career-transition-service-jobboard .career-transition-path {
        background: #111;
    }

    body.dark.candidate-app .career-transition-service-jobboard .career-transition-overview-side,
    body.dark.candidate-app .career-transition-service-jobboard .career-transition-gap-card {
        background: #181818;
        border-color: #272727;
    }

    @media (max-width: 1199.98px) {
        .career-transition-service-jobboard .career-transition-overview {
            grid-template-columns: 1fr;
        }

        .career-transition-service-jobboard .career-transition-overview-side {
            border-left: 0;
            border-top: 1px solid rgba(31, 183, 181, 0.18);
        }

        .career-transition-service-jobboard .career-transition-lower-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .career-transition-service-jobboard .career-transition-path {
            padding: 22px;
        }

        .career-transition-service-jobboard .career-transition-stats,
        .career-transition-service-jobboard .career-transition-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

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
                            <form action="<?= base_url('career-transition/create') ?>" method="post" id="transitionForm" data-career-transition-form>
                                <?= csrf_field() ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Current Role</label>
                                    <input type="text" name="current_role" class="form-control" value="<?= esc($currentRole ?? '') ?>" placeholder="e.g., PHP Developer" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Target Role</label>
                                    <input type="text" name="target_role" class="form-control" value="<?= esc($targetRole ?? '') ?>" placeholder="e.g., Next.js Developer" required>
                                </div>
                                <button type="submit" class="btn btn-primary career-transition-submit-btn" id="submitBtn">
                                    <span id="btnText" data-submit-label><i class="fas fa-rocket"></i> Generate Roadmap</span>
                                    <span id="btnLoading" class="transition-btn-loading" data-submit-loading>
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                        Generating AI course...
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="career-transition-note">
                        <h3>How it works</h3>
                        <p>We keep the flow lightweight: define a target role, generate a plan, and complete lessons inside each course module.</p>
                        <ul>
                            <li>Start from your current role</li>
                            <li>Pick one target role</li>
                            <li>Follow the generated tasks step by step</li>
                        </ul>
                    </div>
                </div>

            <?php else: ?>
                <div class="career-transition-simple-layout career-transition-workspace">

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
                                    <p class="career-transition-path-copy">Your learning path is ready. Continue with course modules or generate a new route when your target changes.</p>
                                </div>

                                <div class="career-transition-overview-side">
                                    <div class="career-transition-stats">
                                        <span>
                                            <strong><?= (int) $taskCount ?></strong>
                                            lessons
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

                    <div class="career-transition-lower-grid">
                        <div class="career-transition-card dashboard-panel career-transition-change-card" id="change-transition-form">
                            <div class="panel-body">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                    <div>
                                        <h2 class="section-title mb-1">Change Career Path</h2>
                                        <p class="section-subtitle mb-0">Generate a new roadmap or restore a previous matching path.</p>
                                    </div>
                                    <span class="badge badge-light">Current path is saved to history</span>
                                </div>

                                <form action="<?= base_url('career-transition/create') ?>" method="post" id="changeTransitionForm" data-career-transition-form>
                                    <?= csrf_field() ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Current Role</label>
                                            <input type="text" name="current_role" class="form-control" value="<?= esc($transition['current_role'] ?? $currentRole ?? '') ?>" placeholder="e.g., PHP Developer" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Target Role</label>
                                            <input type="text" name="target_role" class="form-control" value="<?= esc($transition['target_role'] ?? '') ?>" placeholder="e.g., Frontend Developer" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary career-transition-submit-btn">
                                        <span data-submit-label><i class="fas fa-rocket"></i> Update Roadmap</span>
                                        <span class="transition-btn-loading" data-submit-loading>
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                            Generating AI course...
                                        </span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <?php if (!empty($skillGaps)): ?>
                        <div class="career-transition-note career-transition-gap-card">
                            <h3>Skill Gaps</h3>
                            <div class="career-transition-chip-row">
                                <?php foreach ($skillGaps as $skill): ?>
                                    <span class="career-transition-chip"><?= esc($skill) ?></span>
                                <?php endforeach; ?>
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
