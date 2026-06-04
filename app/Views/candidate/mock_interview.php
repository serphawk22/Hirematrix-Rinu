<?= view('Layouts/candidate_header', ['title' => 'Mock Interview']) ?>

<?php
$application = $application ?? [];
$mockInterview = $mockInterview ?? [];
$jobTitle = (string) ($application['job_title'] ?? 'Mock Interview');
$companyName = trim((string) ($application['company'] ?? ''));
$prepSource = (string) ($mockInterview['source'] ?? 'fallback');
$status = (string) ($application['status'] ?? '');
$aiPolicy = strtoupper((string) ($application['ai_interview_policy'] ?? 'REQUIRED_HARD'));
?>

<div class="mock-interview-jobboard">
<div class="container">
    <div class="page-board-header page-board-header-tight">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-video"></i> Interview practice</span>
            <h1 class="page-board-title">Detailed Mock Interview</h1>
            <p class="page-board-subtitle">Practice the most likely interview questions and answer patterns for this role.</p>
        </div>
        <div class="page-board-actions">
            <a href="<?= base_url('candidate/applications') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Applications
            </a>
            <?php if ($status === 'applied' && $aiPolicy !== 'OFF'): ?>
                <a href="<?= base_url('interview/start/' . (int) $application['id']) ?>" class="btn btn-success">
                    <i class="fas fa-video mr-1"></i> Start AI Interview
                </a>
            <?php elseif ($status === 'shortlisted'): ?>
                <a href="<?= base_url('candidate/book-slot/' . (int) $application['id']) ?>" class="btn btn-warning">
                    <i class="fas fa-calendar-plus mr-1"></i> Book Interview Slot
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container py-5">
        <div class="mock-interview-shell">
        <aside class="mock-sidebar">
            <h3>Application Context</h3>
            <div class="mock-sidebar-meta">
                <div>
                    <strong>Role</strong>
                    <?= esc($jobTitle) ?>
                </div>
                <?php if ($companyName !== ''): ?>
                    <div>
                        <strong>Company</strong>
                        <?= esc($companyName) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <strong>Status</strong>
                    <?= esc(ucwords(str_replace('_', ' ', $status))) ?>
                </div>
                <?php if (!empty($application['resume_version_title'])): ?>
                    <div>
                        <strong>Resume Used</strong>
                        <?= esc($application['resume_version_title']) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <strong>Interview Flow</strong>
                    <?= $aiPolicy === 'OFF' ? 'Recruiter / HR flow' : 'AI-assisted screening flow' ?>
                </div>
            </div>

            <div class="mock-sidebar-actions">
                <a href="<?= base_url('candidate/applications') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Applications
                </a>
                <?php if ($status === 'applied' && $aiPolicy !== 'OFF'): ?>
                    <a href="<?= base_url('interview/start/' . (int) $application['id']) ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-video"></i> Start AI Interview
                    </a>
                <?php elseif ($status === 'shortlisted'): ?>
                    <a href="<?= base_url('candidate/book-slot/' . (int) $application['id']) ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-calendar-plus"></i> Book Interview Slot
                    </a>
                <?php endif; ?>
            </div>
        </aside>

        <div class="mock-main">
            <section class="mock-card">
                <span class="mock-eyebrow <?= $prepSource === 'ai' ? 'is-ai' : '' ?>">
                    <?= $prepSource === 'ai' ? 'AI-generated mock interview' : 'Structured fallback mock interview' ?>
                </span>
                <h2 class="mock-page-title"><?= esc($mockInterview['title'] ?? ($jobTitle . ' Mock Interview')) ?></h2>
                <div class="mock-submeta">
                    <?= esc($jobTitle) ?>
                    <?php if ($companyName !== ''): ?>
                        <span class="mx-2">&bull;</span><?= esc($companyName) ?>
                    <?php endif; ?>
                </div>
                <p class="mock-intro"><?= esc($mockInterview['intro'] ?? 'Use this page to rehearse the most likely questions and answer patterns for the next interview step.') ?></p>
            </section>

            <?php if (!empty($mockInterview['focus_skills'])): ?>
                <section class="mock-card">
                    <div class="mock-section-title">Focus Skills</div>
                    <div class="mock-chip-list">
                        <?php foreach ((array) $mockInterview['focus_skills'] as $skill): ?>
                            <span class="mock-chip"><?= esc($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="mock-card">
                <div class="mock-section-title">Mock Interview Rounds</div>
                <?php foreach ((array) ($mockInterview['rounds'] ?? []) as $round): ?>
                    <div class="mock-round">
                        <div class="mock-round-title"><?= esc($round['name'] ?? 'Interview Round') ?></div>
                        <?php if (!empty($round['objective'])): ?>
                            <div class="mock-round-objective"><?= esc($round['objective']) ?></div>
                        <?php endif; ?>

                        <?php foreach ((array) ($round['questions'] ?? []) as $question): ?>
                            <div class="mock-question">
                                <div class="mock-question-title"><?= esc($question['question'] ?? '') ?></div>
                                <div class="mock-question-meta">
                                    <?php if (!empty($question['why_it_matters'])): ?>
                                        <div><strong>Why it matters:</strong> <?= esc($question['why_it_matters']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($question['answer_tip'])): ?>
                                        <div><strong>Answer tip:</strong> <?= esc($question['answer_tip']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </section>

            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <section class="mock-card h-100">
                        <div class="mock-section-title">Answer Framework</div>
                        <ul class="mock-note-list">
                            <?php foreach ((array) ($mockInterview['answer_framework'] ?? []) as $item): ?>
                                <li><?= esc($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                </div>
                <div class="col-lg-6">
                    <section class="mock-card h-100">
                        <div class="mock-section-title">What Interviewers Will Evaluate</div>
                        <ul class="mock-note-list">
                            <?php foreach ((array) ($mockInterview['evaluation_points'] ?? []) as $item): ?>
                                <li><?= esc($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                </div>
            </div>

            <section class="mock-card">
                <div class="mock-section-title">Final Rehearsal Checklist</div>
                <ul class="mock-note-list">
                    <?php foreach ((array) ($mockInterview['final_checklist'] ?? []) as $item): ?>
                        <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>
    </div>
</div>
</div>

<?= view('Layouts/candidate_footer') ?>
