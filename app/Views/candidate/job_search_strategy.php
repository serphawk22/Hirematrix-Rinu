<?= view('Layouts/candidate_header', ['title' => 'Job Search Strategy Coach']) ?>

<?php
$jobSearchStrategy = $jobSearchStrategy ?? [];
$topSuggestedJobs = $topSuggestedJobs ?? [];
$recommendedIds = array_map('intval', (array) ($jobSearchStrategy['recommended_job_ids'] ?? []));
$recommendedJobs = array_values(array_filter($topSuggestedJobs, static function (array $job) use ($recommendedIds): bool {
    return in_array((int) ($job['id'] ?? 0), $recommendedIds, true);
}));
if (empty($recommendedJobs)) {
    $recommendedJobs = array_slice($topSuggestedJobs, 0, 3);
}

$source = (string) ($jobSearchStrategy['source'] ?? 'fallback');

$priorityActions = array_values(array_filter(array_map('trim', (array) ($jobSearchStrategy['priority_actions'] ?? []))));
if (empty($priorityActions)) {
    $priorityActions = [
        'Refine your resume around the skills that matter most.',
        'Focus on applications with the highest match potential.',
        'Set weekly priorities instead of applying broadly.',
    ];
}

$profileFixes = array_values(array_filter(array_map('trim', (array) ($jobSearchStrategy['profile_fixes'] ?? []))));
if (empty($profileFixes)) {
    $profileFixes = [
        'Add missing keywords to your summary and skills section.',
        'Show recent outcomes and measurable results in each role.',
        'Make your target role obvious in the first screen of your profile.',
    ];
}

$applicationStrategy = array_values(array_filter(array_map('trim', (array) ($jobSearchStrategy['application_strategy'] ?? []))));
if (empty($applicationStrategy)) {
    $applicationStrategy = [
        'Apply first to roles with the highest match score.',
        'Use a tailored resume version for each target role.',
        'Track follow-ups so every application gets a next step.',
    ];
}

$weeklyPlan = array_values(array_filter(array_map('trim', (array) ($jobSearchStrategy['weekly_plan'] ?? []))));
if (empty($weeklyPlan)) {
    $weeklyPlan = [
        'Day 1: Update your resume and profile headline.',
        'Day 2: Apply to your best-fit roles.',
        'Day 3: Review responses and adjust your search.',
    ];
}

$watchouts = array_values(array_filter(array_map('trim', (array) ($jobSearchStrategy['watchouts'] ?? []))));

$targetRoles = array_values(array_filter(array_map('trim', (array) ($jobSearchStrategy['target_roles'] ?? []))));
if (empty($targetRoles)) {
    $targetRoles = array_values(array_filter(array_map(static function (array $job): string {
        return trim((string) ($job['title'] ?? ''));
    }, $recommendedJobs)));
}
if (empty($targetRoles)) {
    $targetRoles = ['Web Developer', 'Software Developer', 'Frontend Developer'];
}

$roadmapPhases = [
    [
        'step' => '01',
        'title' => 'Set the search boundary',
        'copy' => 'Pick the roles you want to be known for and use them to narrow your search.',
        'items' => $targetRoles,
    ],
    [
        'step' => '02',
        'title' => 'Strengthen the profile',
        'copy' => 'Close the gaps that recruiters notice first so your profile feels ready to move.',
        'items' => $profileFixes,
    ],
    [
        'step' => '03',
        'title' => 'Apply in a focused rhythm',
        'copy' => 'Move through your highest-fit roles first, then follow up with intent.',
        'items' => $applicationStrategy,
    ],
];
?>

<div class="strategy-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-compass"></i> Search guidance</span>
                <h1 class="page-board-title">Job Search Strategy Coach</h1>
                <p class="page-board-subtitle">A living roadmap for your next applications, built to help you decide what to do today, this week, and next.</p>
            </div>
            <div class="page-board-actions">
                <a href="<?= base_url('jobs?tab=suggested') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-briefcase mr-1"></i> Suggested Jobs
                </a>
                <a href="<?= base_url('candidate/profile') ?>" class="btn btn-primary">
                    <i class="fas fa-user-edit mr-1"></i> Update Profile
                </a>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="strategy-shell">
            <aside class="strategy-sidebar">
                <div class="strategy-sidebar-block">
                    <span class="strategy-sidebar-kicker">Plan snapshot</span>
                    <h3>Current summary</h3>
                    <p class="text-muted mb-0">
                        This plan centers on <?= count($priorityActions) ?> priority actions, <?= count($profileFixes) ?> profile fixes, and <?= count($targetRoles) ?> target roles.
                        The details live in the roadmap below.
                    </p>
                </div>

                <div class="strategy-sidebar-block">
                    <h3>Target Roles</h3>
                    <div class="strategy-chip-list">
                        <?php foreach ($targetRoles as $role): ?>
                            <span class="strategy-chip"><?= esc($role) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="strategy-sidebar-block">
                    <h3>Quick Actions</h3>
                    <a href="<?= base_url('jobs?tab=suggested') ?>" class="btn btn-outline-primary btn-sm btn-block">Open Suggested Jobs</a>
                    <a href="<?= base_url('candidate/profile') ?>" class="btn btn-outline-secondary btn-sm btn-block mt-2">Review Profile</a>
                </div>
            </aside>

            <div class="strategy-main">
                <section class="strategy-card strategy-hero-card">
                    <div class="strategy-card-topline">
                        <span class="strategy-badge <?= $source === 'ai' ? 'is-ai' : '' ?>">
                            <?= $source === 'ai' ? 'AI-generated strategy' : 'Structured fallback strategy' ?>
                        </span>
                        <span class="strategy-plan-state">Plan in progress</span>
                    </div>
                    <h2 class="strategy-title"><?= esc($jobSearchStrategy['title'] ?? 'Job Search Strategy Coach') ?></h2>
                    <p class="strategy-summary"><?= esc($jobSearchStrategy['summary'] ?? 'Use this plan to make your next applications more selective and better aligned to your strongest opportunities.') ?></p>
                </section>

                <section class="strategy-section">
                    <div class="strategy-section-heading">
                        <div>
                            <h3>Roadmap</h3>
                            <p>Work through these steps in order so the plan feels actionable instead of abstract.</p>
                        </div>
                    </div>

                    <div class="strategy-roadmap">
                        <?php foreach ($roadmapPhases as $phase): ?>
                            <article class="strategy-step-card">
                                <div class="strategy-step-head">
                                    <span class="strategy-step-number"><?= esc($phase['step']) ?></span>
                                    <div>
                                        <h4><?= esc($phase['title']) ?></h4>
                                        <p><?= esc($phase['copy']) ?></p>
                                    </div>
                                </div>
                                <ul class="strategy-step-list">
                                    <?php foreach (array_slice($phase['items'], 0, 4) as $item): ?>
                                        <li><?= esc($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <div class="row">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <section class="strategy-card h-100">
                            <div class="strategy-section-title">Weekly Cadence</div>
                            <ul class="strategy-list strategy-plan-list">
                                <?php foreach (array_slice($weeklyPlan, 0, 4) as $item): ?>
                                    <li><?= esc($item) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    </div>
                    <div class="col-lg-6">
                        <section class="strategy-card h-100">
                            <div class="strategy-section-title">Watchouts</div>
                            <?php if (!empty($watchouts)): ?>
                                <ul class="strategy-list strategy-plan-list">
                                    <?php foreach ($watchouts as $item): ?>
                                        <li><?= esc($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">Keep an eye on role fit, keyword coverage, and follow-up timing as you apply.</p>
                            <?php endif; ?>
                        </section>
                    </div>
                </div>

                <?php if (!empty($recommendedJobs)): ?>
                    <section class="strategy-card">
                        <div class="strategy-section-title">Recommended Roles To Target</div>
                        <div class="strategy-job-list">
                            <?php foreach ($recommendedJobs as $job): ?>
                                <article class="strategy-job">
                                    <div class="strategy-job-top">
                                        <div>
                                            <div class="strategy-job-title">
                                                <a href="<?= base_url('job/' . (int) $job['id']) ?>"><?= esc($job['title'] ?? 'Untitled Role') ?></a>
                                            </div>
                                            <div class="strategy-job-meta">
                                                <?= esc($job['company'] ?? 'Company') ?>
                                                <span class="mx-2">&bull;</span>
                                                <?= esc($job['location'] ?? 'N/A') ?>
                                                <span class="mx-2">&bull;</span>
                                                <?= (int) round($job['match_score'] ?? 0) ?>% match
                                            </div>
                                        </div>
                                        <a href="<?= base_url('job/' . (int) $job['id']) ?>" class="btn btn-outline-primary btn-sm">Open Job</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= view('Layouts/candidate_footer') ?>
