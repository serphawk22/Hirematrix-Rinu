<?php
$statusTones = $statusTones ?? [
    'applied' => 'neutral',
    'ai_interview_completed' => 'info',
    'shortlisted' => 'success',
    'interview_scheduled' => 'info',
    'interviewed' => 'violet',
    'offered' => 'warning',
    'hired' => 'success',
    'rejected' => 'danger',
    'withdrawn' => 'muted',
    'on_hold' => 'warning',
    'filtered_out' => 'muted',
];
$stageIcons = $stageIcons ?? [
    'applied' => 'fa-inbox',
    'ai_interview_completed' => 'fa-chart-line',
    'shortlisted' => 'fa-check-circle',
    'interview_scheduled' => 'fa-calendar-check',
    'interviewed' => 'fa-user-check',
    'offered' => 'fa-handshake',
    'hired' => 'fa-briefcase',
    'rejected' => 'fa-times-circle',
    'withdrawn' => 'fa-user-slash',
    'on_hold' => 'fa-pause-circle',
    'filtered_out' => 'fa-filter',
];
?>
<?php
$pipelineSort = (string) ($advancedFilters['sort'] ?? 'ats_desc');
$nextAtsSort = $pipelineSort === 'ats_desc' ? 'ats_asc' : 'ats_desc';
$sortQueryParams = [];
foreach (($advancedFilters ?? []) as $filterKey => $filterValue) {
    if ($filterValue === '' || $filterValue === null) {
        continue;
    }
    $sortQueryParams[$filterKey] = $filterValue;
}
$sortQueryParams['stage'] = $safeActiveStage ?? 'all';
$sortQueryParams['sort'] = $nextAtsSort;
$atsSortUrl = base_url('recruiter/jobs/view/' . (int) ($job['id'] ?? 0) . '?' . http_build_query($sortQueryParams));
$atsSortIcon = $pipelineSort === 'ats_asc' ? 'fa-sort-up' : 'fa-sort-down';
?>
<?php if (empty($paginatedApplications)): ?>
    <div class="pipeline-empty">
        <i class="fas fa-user-slash"></i>
        <strong>No candidates found in this stage.</strong>
    </div>
<?php else: ?>
    <section class="response-results" id="candidatePipelineTable" aria-label="Candidate responses">
        <div class="response-bulk-strip">
            <label class="response-select-all">
                <input type="checkbox" class="select-all pipeline-check" aria-label="Select all candidates" onchange="togglePipelineCandidates(this)">
                <span>Select all</span>
            </label>
            <button type="button" class="response-bulk-action" onclick="executeBulkAction('shortlist')"><i class="fas fa-check"></i> Shortlist</button>
            <button type="button" class="response-bulk-action" onclick="executeBulkAction('reject')"><i class="fas fa-ban"></i> Reject</button>
            <button type="button" class="response-bulk-action" onclick="openBulkEmailModal()"><i class="fas fa-envelope"></i> Email</button>
            <button type="button" class="response-bulk-action" onclick="openBulkMessageModal()"><i class="fas fa-comments"></i> Message</button>
            <a class="response-bulk-action <?= in_array($pipelineSort, ['ats_desc', 'ats_asc'], true) ? 'is-active' : '' ?>" href="<?= esc($atsSortUrl) ?>">
                <i class="fas <?= esc($atsSortIcon) ?>"></i> Match sort
            </a>
        </div>

        <div class="response-card-list">
            <?php foreach ($paginatedApplications as $app): ?>
                <?php
                    $rawAppStatus = (string) ($app['status'] ?? 'applied');
                    $appStatus = ['hold' => 'on_hold', '' => 'applied'][$rawAppStatus] ?? $rawAppStatus;
                    $tone = $statusTones[$appStatus] ?? 'neutral';
                    $appliedAt = !empty($app['applied_at']) ? date('d M, Y', strtotime($app['applied_at'])) : '-';
                    $lastActive = !empty($app['last_login']) ? date('M d, Y', strtotime($app['last_login'])) : 'Never';
                    $activity = $app['recruiter_activity'] ?? [];
                    $atsScore = (int) ($app['ats_score'] ?? 0);
                    $allCandidateSkills = array_values((array) ($app['candidate_skills'] ?? []));
                    $allRequiredSkills = array_values((array) ($app['required_skills'] ?? []));
                    $candidateSkills = array_slice($allCandidateSkills, 0, 6);
                    $candidateSkillMap = array_flip(array_map(static fn ($skill): string => strtolower(trim((string) $skill)), $allCandidateSkills));
                    $matchedSkills = [];
                    $missingSkills = [];
                    foreach ($allRequiredSkills as $requiredSkill) {
                        $skillKey = strtolower(trim((string) $requiredSkill));
                        if ($skillKey === '') {
                            continue;
                        }
                        if (isset($candidateSkillMap[$skillKey])) {
                            $matchedSkills[] = (string) $requiredSkill;
                        } else {
                            $missingSkills[] = (string) $requiredSkill;
                        }
                    }
                    $tags = array_values(array_filter(array_map('trim', explode(',', (string) ($app['recruiter_tags'] ?? '')))));
                    $note = trim((string) ($app['recruiter_notes'] ?? ''));
                    $notePreview = strlen($note) > 110 ? substr($note, 0, 110) . '...' : $note;
                    $communication = $app['communication_summary'] ?? [];
                    $emailCount = (int) ($communication['email_count'] ?? 0);
                    $messageCount = (int) ($communication['message_count'] ?? 0);
                    $latestAt = !empty($communication['latest_at']) ? date('M d', strtotime((string) $communication['latest_at'])) : '';
                    $latestType = (string) ($communication['latest_type'] ?? '');
                    $latestDirection = (string) ($communication['latest_direction'] ?? '');
                    $latestActor = in_array($latestDirection, ['incoming', 'inbound'], true) ? 'Candidate' : (in_array($latestDirection, ['outgoing', 'outbound'], true) ? 'Recruiter' : 'Latest');
                    $latestLabel = trim($latestActor . ' ' . $latestType);
                    $latestPreview = trim((string) ($communication['latest_subject'] ?? ''));
                    if ($latestPreview === '') {
                        $latestPreview = trim((string) ($communication['latest_preview'] ?? ''));
                    }
                    $hasUnreadCommunication = !empty($communication['has_unread']);
                    $needsFollowUp = !empty($communication['needs_followup']);
                    $hasCommunication = ($emailCount + $messageCount) > 0;
                    $communicationState = 'No contact';
                    $communicationStateClass = 'is-muted';
                    $communicationStateIcon = 'fa-minus-circle';
                    if ($hasUnreadCommunication) {
                        $communicationState = 'Unread reply';
                        $communicationStateClass = 'is-unread';
                        $communicationStateIcon = 'fa-circle';
                    } elseif (in_array($latestDirection, ['incoming', 'inbound'], true)) {
                        $communicationState = 'Candidate replied';
                        $communicationStateClass = 'is-replied';
                        $communicationStateIcon = 'fa-reply';
                    } elseif ($needsFollowUp) {
                        $communicationState = 'Needs follow-up';
                        $communicationStateClass = 'is-followup';
                        $communicationStateIcon = 'fa-clock';
                    } elseif ($hasCommunication) {
                        $communicationState = 'Contacted';
                        $communicationStateClass = 'is-contacted';
                        $communicationStateIcon = 'fa-check-circle';
                    }
                    $communicationPayload = [
                        'candidateName' => (string) ($app['candidate_name'] ?? '-'),
                        'candidateEmail' => '',
                        'emailCount' => $emailCount,
                        'messageCount' => $messageCount,
                        'stateLabel' => $communicationState,
                        'hasUnread' => $hasUnreadCommunication,
                        'needsFollowUp' => $needsFollowUp,
                        'latestPreview' => $latestPreview,
                        'items' => array_values((array) ($communication['items'] ?? [])),
                    ];
                    $reviewPayload = $communicationPayload + [
                        'applicationId' => (int) ($app['id'] ?? 0),
                        'candidateId' => (int) ($app['candidate_id'] ?? 0),
                        'stage' => (string) ($statuses[$appStatus] ?? ucfirst(str_replace('_', ' ', $appStatus))),
                        'stageKey' => (string) $appStatus,
                        'atsScore' => $atsScore,
                        'skillMatch' => (int) ($app['skill_match'] ?? 0),
                        'experience' => (string) ($app['experience_display'] ?? '-'),
                        'location' => (string) ($app['candidate_location'] ?? '-'),
                        'phone' => (string) ($app['candidate_phone'] ?? ''),
                        'appliedAt' => $appliedAt,
                        'lastActive' => $lastActive,
                        'matchedSkills' => array_values($matchedSkills),
                        'missingSkills' => array_values($missingSkills),
                        'candidateSkills' => array_values($allCandidateSkills),
                        'requiredSkills' => array_values($allRequiredSkills),
                        'atsReason' => count($matchedSkills) . ' of ' . count($allRequiredSkills) . ' required skills matched, with experience and profile completeness included in the score.',
                        'tags' => array_values($tags),
                        'notes' => $note,
                        'resumeUrl' => !empty($app['resume_path']) ? base_url('recruiter/candidate/' . (int) ($app['candidate_id'] ?? 0) . '/download-resume?application_id=' . (int) ($app['id'] ?? 0) . '&job_id=' . (int) ($job['id'] ?? 0)) : '',
                        'resumePreviewUrl' => !empty($app['resume_path']) ? base_url('recruiter/candidate/' . (int) ($app['candidate_id'] ?? 0) . '/preview-resume?application_id=' . (int) ($app['id'] ?? 0) . '&job_id=' . (int) ($job['id'] ?? 0)) : '',
                        'profileUrl' => base_url('recruiter/candidate/' . (int) ($app['candidate_id'] ?? 0) . '?application_id=' . (int) ($app['id'] ?? 0) . '&job_id=' . (int) ($job['id'] ?? 0)),
                    ];
                    $communicationJson = htmlspecialchars(
                        json_encode($reviewPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?: '{}',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    $initials = strtoupper(substr(trim((string) ($app['candidate_name'] ?? 'C')), 0, 1));
                    $candidatePhotoPath = trim((string) ($app['profile_photo'] ?? ''));
                    $candidatePhotoUrl = $candidatePhotoPath !== ''
                        ? (preg_match('/^https?:\/\//i', $candidatePhotoPath) ? $candidatePhotoPath : base_url($candidatePhotoPath))
                        : '';
                    $currentSummary = trim((string) ($app['current_role_summary'] ?? ''));
                    $educationSummary = trim((string) ($app['latest_education_summary'] ?? ''));
                    $preferredLocations = trim((string) ($app['preferred_locations'] ?? ''));
                    if ($preferredLocations === '') {
                        $preferredLocations = trim((string) ($app['candidate_location'] ?? ''));
                    }
                    $noticePeriod = trim((string) ($app['notice_period'] ?? ''));
                    $salaryText = trim((string) ($app['current_salary'] ?? ''));
                    $salaryLabel = 'Current CTC';
                    if ($salaryText === '') {
                        $salaryText = trim((string) ($app['expected_salary'] ?? ''));
                        $salaryLabel = 'Expected CTC';
                    }
                    if ($salaryText !== '' && is_numeric($salaryText)) {
                        $salaryText = number_format((float) $salaryText, 2) . ' LPA';
                    }
                    $workflow = (array) ($app['workflow'] ?? []);
                    $followUpAt = trim((string) ($workflow['follow_up_at'] ?? ''));
                    $followUpLabel = $followUpAt !== '' ? date('M d', strtotime($followUpAt)) : '';
                    $lastOutcome = trim((string) ($workflow['last_outcome'] ?? ''));
                    $lastOutcomeLabel = $lastOutcome !== '' ? ucwords(str_replace('_', ' ', $lastOutcome)) : '';
                    $contactUrl = base_url('recruiter/candidate/' . (int) ($app['candidate_id'] ?? 0) . '/view-contact?application_id=' . (int) ($app['id'] ?? 0) . '&job_id=' . (int) ($job['id'] ?? 0));
                    $notesUrl = base_url('recruiter/applications/' . (int) ($app['id'] ?? 0) . '/notes');
                    $followUpUrl = base_url('recruiter/applications/' . (int) ($app['id'] ?? 0) . '/follow-up');
                    $outcomeUrl = base_url('recruiter/applications/' . (int) ($app['id'] ?? 0) . '/communication-outcome');
                ?>
                <article class="response-card is-reviewable js-open-candidate-review" data-application-row="<?= (int) $app['id'] ?>" data-review="<?= $communicationJson ?>">
                    <div class="response-card-check">
                        <input type="checkbox" class="pipeline-check" name="candidate_ids[]" value="<?= (int) $app['id'] ?>" data-email="<?= esc($app['candidate_email'] ?? '') ?>" aria-label="Select <?= esc($app['candidate_name'] ?? 'candidate') ?>">
                    </div>

                    <div class="response-card-main">
                        <div class="response-candidate-head">
                            <?php if ($candidatePhotoUrl !== ''): ?>
                                <img src="<?= esc($candidatePhotoUrl) ?>" alt="<?= esc($app['candidate_name'] ?? 'Candidate') ?>" class="response-avatar response-avatar-photo">
                            <?php else: ?>
                                <div class="response-avatar" aria-hidden="true"><?= esc($initials ?: 'C') ?></div>
                            <?php endif; ?>
                            <div class="response-candidate-copy">
                                <div class="response-name-row">
                                    <h3><?= esc($app['candidate_name'] ?? '-') ?></h3>
                                    <?php if ($atsScore >= 70): ?>
                                        <span class="response-recommended">Recommended</span>
                                    <?php endif; ?>
                                </div>
                                <div class="response-meta-line">
                                    <span><i class="fas fa-briefcase"></i> <?= esc($app['experience_display'] ?? '-') ?></span>
                                    <?php if ($salaryText !== ''): ?>
                                        <span title="<?= esc($salaryLabel) ?>"><i class="fas fa-rupee-sign"></i> <?= esc($salaryText) ?></span>
                                    <?php endif; ?>
                                    <?php if ($noticePeriod !== ''): ?>
                                        <span><i class="far fa-clock"></i> <?= esc($noticePeriod) ?></span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-map-marker-alt"></i> <?= esc($app['candidate_location'] ?? '-') ?></span>
                                    <span><i class="fas fa-calendar-alt"></i> Applied <?= esc($appliedAt) ?></span>
                                </div>
                                <?php if (!empty($app['candidate_headline'])): ?>
                                    <div class="response-headline"><?= esc($app['candidate_headline']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($tags) || $note !== ''): ?>
                                    <div class="response-recruiter-insight">
                                        <span class="response-insight-label"><i class="fas fa-sticky-note"></i> Notes</span>
                                        <?php if (!empty($tags)): ?>
                                            <div class="pipeline-tag-list">
                                                <?php foreach (array_slice($tags, 0, 2) as $tag): ?>
                                                    <span class="status-pill is-note-tag"><?= esc($tag) ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($tags) > 2): ?>
                                                    <span class="status-pill is-muted">+<?= count($tags) - 2 ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($note !== ''): ?>
                                            <span class="pipeline-note-preview" title="<?= esc($note) ?>"><?= esc($notePreview) ?></span>
                                        <?php else: ?>
                                            <span class="response-muted-line">Tagged for follow-up</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="response-profile-snapshot">
                            <div class="response-detail-row">
                                <span class="response-field-label">Current</span>
                                <div class="response-detail-value"><?= esc($currentSummary) ?></div>
                            </div>
                            <div class="response-detail-row">
                                <span class="response-field-label">Education</span>
                                <div class="response-detail-value"><?= esc($educationSummary !== '' ? $educationSummary : 'Not provided') ?></div>
                            </div>
                            <div class="response-detail-row">
                                <span class="response-field-label">Pref. locations</span>
                                <div class="response-detail-value"><?= esc($preferredLocations !== '' ? $preferredLocations : 'Not provided') ?></div>
                            </div>
                            <div class="response-detail-row response-detail-row-inline">
                                <span class="response-field-label">Current stage</span>
                                <div class="hm-status-drop response-status-menu">
                                    <button class="stage-pill <?= esc($tone) ?> hm-status-drop-btn" type="button" title="Change status">
                                        <i class="fas <?= esc($stageIcons[$appStatus] ?? 'fa-circle') ?>"></i>
                                        <?= esc($statuses[$appStatus] ?? ucfirst(str_replace('_', ' ', $appStatus))) ?>
                                    </button>
                                    <div class="hm-status-drop-menu response-status-dropdown">
                                        <?php foreach ($statuses as $sv => $sl): ?>
                                            <?php if ($sv !== $appStatus && in_array($sv, ['applied','shortlisted','on_hold','rejected'], true)): ?>
                                                <a class="hm-status-drop-item hm-pipeline-status-change" href="#" data-application-id="<?= (int)$app['id'] ?>" data-status="<?= esc($sv === 'on_hold' ? 'hold' : $sv) ?>" data-label="<?= esc($sl) ?>"><?= esc($sl) ?></a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="response-detail-row">
                                <span class="response-field-label">Activity</span>
                                <div class="response-detail-value"><?= (int) ($activity['profile_viewed_count'] ?? 0) ?> profile views, <?= (int) ($activity['resume_downloaded_count'] ?? 0) ?> resume downloads; last active <?= esc($lastActive) ?></div>
                            </div>
                            <div class="response-detail-row">
                                <span class="response-field-label">Contact</span>
                                <div class="response-detail-value response-contact-reveal" data-contact-target="<?= (int) $app['id'] ?>">
                                    <button type="button"
                                            class="response-contact-button js-view-contact"
                                            data-contact-url="<?= esc($contactUrl) ?>"
                                            aria-label="View contact details for <?= esc($app['candidate_name'] ?? 'candidate') ?>">
                                        <i class="fas fa-address-card"></i> View contact
                                    </button>
                                    <span class="response-contact-hint">Logs contact view</span>
                                </div>
                            </div>
                        </div>

                        <div class="response-fit-panel">
                            <div class="response-skills-block">
                                <span class="response-field-label">Key skills</span>
                                <?php if (!empty($candidateSkills)): ?>
                                    <div class="pipeline-skill-list">
                                        <?php foreach ($candidateSkills as $skill): ?>
                                            <span class="status-pill"><?= esc($skill) ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($allCandidateSkills) > 6): ?>
                                            <span class="status-pill">+<?= count($allCandidateSkills) - 6 ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="response-muted-line">No skills listed</span>
                                <?php endif; ?>
                            </div>

                            <div class="response-fit-summary">
                                <div>
                                    <span class="response-field-label">Requirements</span>
                                    <div class="pipeline-skill-list">
                                        <span class="status-pill"><?= count($matchedSkills) ?> of <?= count($allRequiredSkills) ?> matched</span>
                                        <?php foreach (array_slice($matchedSkills, 0, 3) as $skill): ?>
                                            <span class="status-pill"><?= esc($skill) ?></span>
                                        <?php endforeach; ?>
                                        <?php foreach (array_slice($missingSkills, 0, 3) as $skill): ?>
                                            <span class="status-pill is-muted"><?= esc($skill) ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($matchedSkills) + count($missingSkills) > 6): ?>
                                            <span class="status-pill is-muted">+<?= count($matchedSkills) + count($missingSkills) - 6 ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="response-card-side">
                        <div class="response-match-box">
                            <div class="response-match-ring" style="--match-score: <?= min(100, max(0, $atsScore)) ?>%;">
                                <strong><?= $atsScore ?>%</strong>
                            </div>
                            <div class="response-match-meta">
                                <span class="response-field-label">Match</span>
                                <span><?= (int) ($app['skill_match'] ?? 0) ?>% skills</span>
                            </div>
                        </div>

                        <button type="button" class="communication-stack js-open-communication-drawer" data-communication="<?= $communicationJson ?>" aria-label="Open communication history for <?= esc($app['candidate_name'] ?? 'candidate') ?>">
                            <div class="communication-status-line">
                                <span class="communication-state <?= esc($communicationStateClass) ?>">
                                    <i class="fas <?= esc($communicationStateIcon) ?>"></i>
                                    <?= esc($communicationState) ?>
                                </span>
                            </div>
                            <div class="communication-counts">
                                <span class="communication-chip"><i class="fas fa-at"></i><?= $emailCount ?> email<?= $emailCount === 1 ? '' : 's' ?></span>
                                <span class="communication-chip"><i class="fas fa-comments"></i><?= $messageCount ?> msg<?= $messageCount === 1 ? '' : 's' ?></span>
                            </div>
                            <?php if ($latestPreview !== ''): ?>
                                <div class="communication-latest" title="<?= esc($latestPreview) ?>">
                                    <strong><?= esc($latestLabel ?: 'Latest') ?><?= $latestAt !== '' ? ' - ' . esc($latestAt) : '' ?></strong>
                                    <span class="communication-preview"><?= esc($latestPreview) ?></span>
                                </div>
                            <?php endif; ?>
                        </button>

                        <div class="candidate-cockpit" data-application-id="<?= (int) $app['id'] ?>">
                            <span class="sidebar-section-label">Contact &amp; follow-up</span>
                            <?php if ($followUpLabel !== '' || $lastOutcomeLabel !== ''): ?>
                                <div class="candidate-cockpit-status">
                                    <?php if ($followUpLabel !== ''): ?>
                                    <span><i class="far fa-clock"></i> Follow-up <?= esc($followUpLabel) ?></span>
                                    <?php endif; ?>
                                    <?php if ($lastOutcomeLabel !== ''): ?>
                                        <span><i class="fas fa-history"></i> <?= esc($lastOutcomeLabel) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="candidate-cockpit-actions">
                                <button type="button" class="cockpit-action js-contact-action" data-mode="call" data-contact-url="<?= esc($contactUrl) ?>" data-outcome-url="<?= esc($outcomeUrl) ?>"><i class="fas fa-phone"></i> Call</button>
                                <button type="button" class="cockpit-action js-contact-action" data-mode="whatsapp" data-contact-url="<?= esc($contactUrl) ?>" data-outcome-url="<?= esc($outcomeUrl) ?>"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                                <button type="button" class="cockpit-action js-toggle-cockpit-panel" data-panel="note"><i class="fas fa-comment-alt"></i> Note</button>
                                <button type="button" class="cockpit-action js-toggle-cockpit-panel" data-panel="followup"><i class="far fa-calendar"></i> Follow-up</button>
                            </div>

                            <form class="cockpit-panel cockpit-note-panel js-inline-note-form" data-panel-name="note" action="<?= esc($notesUrl) ?>" hidden>
                                <textarea name="notes" maxlength="5000" rows="3" placeholder="Private recruiter note..."><?= esc($note) ?></textarea>
                                <input type="text" name="tags" maxlength="255" value="<?= esc(implode(', ', $tags)) ?>" placeholder="Tags, separated by commas">
                                <div class="quick-tag-list" aria-label="Quick tags">
                                    <?php foreach (['Strong fit', 'Immediate joiner', 'Salary concern', 'Follow up', 'Good communication'] as $quickTag): ?>
                                        <button type="button" class="quick-tag js-quick-tag" data-tag="<?= esc($quickTag) ?>"><?= esc($quickTag) ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <button type="submit" class="cockpit-save">Save note</button>
                            </form>

                            <form class="cockpit-panel js-followup-form" data-panel-name="followup" action="<?= esc($followUpUrl) ?>" hidden>
                                <div class="followup-presets">
                                    <button type="button" class="js-followup-preset" data-days="0">Today</button>
                                    <button type="button" class="js-followup-preset" data-days="1">Tomorrow</button>
                                    <button type="button" class="js-followup-preset" data-days="3">3 days</button>
                                </div>
                                <label>Custom <input type="date" name="follow_up_date" min="<?= date('Y-m-d') ?>" value="<?= $followUpAt !== '' ? esc(date('Y-m-d', strtotime($followUpAt))) : '' ?>"></label>
                                <button type="submit" class="cockpit-save">Set follow-up</button>
                            </form>

                            <form class="cockpit-panel js-outcome-form" data-panel-name="outcome" action="<?= esc($outcomeUrl) ?>" hidden>
                                <input type="hidden" name="channel" value="call">
                                <strong>Log communication outcome</strong>
                                <select name="outcome" required>
                                    <option value="">Choose outcome</option>
                                    <option value="connected">Connected</option>
                                    <option value="no_answer">No answer</option>
                                    <option value="callback">Call back requested</option>
                                    <option value="interested">Interested</option>
                                    <option value="not_interested">Not interested</option>
                                    <option value="wrong_number">Wrong number</option>
                                    <option value="message_sent">Message sent</option>
                                </select>
                                <input type="text" name="notes" maxlength="500" placeholder="Optional outcome note">
                                <button type="submit" class="cockpit-save">Log outcome</button>
                            </form>
                        </div>

                        <div class="response-actions response-icon-actions" aria-label="Candidate actions">
                            <span class="sidebar-section-label">Hiring decision</span>
                            <div class="response-action-cluster is-decision">
                                <button type="button" class="response-action-icon js-review-stage-action" data-application-id="<?= (int) $app['id'] ?>" data-status="shortlisted" title="Shortlist" aria-label="Shortlist <?= esc($app['candidate_name'] ?? 'candidate') ?>">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="response-action-icon js-open-schedule-interview" data-application-id="<?= (int) $app['id'] ?>" data-candidate-name="<?= esc($app['candidate_name'] ?? 'Candidate') ?>" data-candidate-email="<?= esc($app['candidate_email'] ?? '') ?>" title="Schedule interview" aria-label="Schedule interview">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                                <button type="button" class="response-action-icon js-review-stage-action" data-application-id="<?= (int) $app['id'] ?>" data-status="hold" title="Hold" aria-label="Put on hold">
                                    <i class="fas fa-pause"></i>
                                </button>
                                <button type="button" class="response-action-icon is-danger js-review-stage-action" data-application-id="<?= (int) $app['id'] ?>" data-status="rejected" title="Reject" aria-label="Reject <?= esc($app['candidate_name'] ?? 'candidate') ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <span class="sidebar-section-label">Profile &amp; resume</span>
                            <div class="response-action-cluster is-profile">
                                <a href="<?= base_url('recruiter/candidate/' . (int) $app['candidate_id'] . '?application_id=' . (int) $app['id'] . '&job_id=' . (int) $job['id']) ?>" class="response-action-icon" title="Full profile" aria-label="Full profile">
                                    <i class="fas fa-user"></i>
                                </a>
                                <?php if (!empty($app['resume_path'])): ?>
                                    <a href="<?= base_url('recruiter/candidate/' . (int) ($app['candidate_id'] ?? 0) . '/preview-resume?application_id=' . (int) ($app['id'] ?? 0) . '&job_id=' . (int) ($job['id'] ?? 0)) ?>" class="response-action-icon" target="_blank" rel="noopener" title="Preview resume" aria-label="Preview resume">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('recruiter/candidate/' . (int) ($app['candidate_id'] ?? 0) . '/download-resume?application_id=' . (int) ($app['id'] ?? 0) . '&job_id=' . (int) ($job['id'] ?? 0)) ?>" class="response-action-icon" title="Download resume" aria-label="Download resume">
                                        <i class="fas fa-download"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($appStatus === 'ai_interview_completed'): ?>
                                    <button type="button" class="response-action-icon response-action-ai view-ai-report-btn" data-candidate-id="<?= (int) ($app['candidate_id'] ?? 0) ?>" data-job-id="<?= (int) ($job['id'] ?? 0) ?>" data-jobrole="<?= esc($job['title'] ?? '') ?>" data-candidate-name="<?= esc($app['candidate_name'] ?? '-') ?>" title="AI interview report" aria-label="AI interview report">
                                        <span>AI</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </aside>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($pager->getPageCount() > 1): ?>
        <div class="p-3 bg-white">
            <?= $pager->links('default', 'portal_full') ?>
        </div>
    <?php endif; ?>

    <div class="communication-drawer-backdrop js-communication-drawer-close" id="communicationDrawerBackdrop"></div>
    <aside class="communication-drawer" id="communicationDrawer" aria-hidden="true" aria-labelledby="communicationDrawerTitle">
        <div class="communication-drawer-head">
            <button type="button" class="communication-drawer-close js-communication-drawer-close" aria-label="Close candidate review">
                <i class="fas fa-times"></i>
            </button>
            <h3 class="communication-drawer-title" id="communicationDrawerTitle">Candidate review</h3>
            <div class="communication-drawer-subtitle" id="communicationDrawerSubtitle"></div>
        </div>
        <div class="communication-drawer-body">
            <div class="communication-drawer-stats" id="communicationDrawerStats"></div>
            <div class="communication-timeline" id="communicationDrawerTimeline"></div>
        </div>
    </aside>
    <div class="schedule-interview-modal" id="scheduleInterviewModal" aria-hidden="true">
        <form class="schedule-interview-dialog" id="scheduleInterviewForm">
            <input type="hidden" name="application_id" value="">
            <div class="schedule-interview-head">
                <div>
                    <h3 class="schedule-interview-title">Schedule Interview</h3>
                    <div class="schedule-interview-subtitle" id="scheduleInterviewSubtitle"></div>
                </div>
                <button type="button" class="schedule-interview-close js-schedule-interview-close" aria-label="Close schedule interview">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="schedule-interview-body">
                <div class="schedule-interview-grid">
                    <div class="schedule-field">
                        <label for="scheduleInterviewDate">Date</label>
                        <input type="date" id="scheduleInterviewDate" name="interview_date" required>
                    </div>
                    <div class="schedule-field">
                        <label for="scheduleInterviewTime">Time</label>
                        <input type="time" id="scheduleInterviewTime" name="interview_time" required>
                    </div>
                    <div class="schedule-field">
                        <label for="scheduleInterviewDuration">Duration</label>
                        <select id="scheduleInterviewDuration" name="duration_minutes">
                            <option value="30">30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60" selected>60 minutes</option>
                            <option value="90">90 minutes</option>
                        </select>
                    </div>
                    <div class="schedule-field">
                        <label for="scheduleInterviewMode">Mode</label>
                        <select id="scheduleInterviewMode" name="interview_mode">
                            <option value="online">Online</option>
                            <option value="phone">Phone</option>
                            <option value="in_person">In person</option>
                        </select>
                    </div>
                </div>
                <div class="schedule-field">
                    <label for="scheduleInterviewLocation">Meeting link or location</label>
                    <input type="text" id="scheduleInterviewLocation" name="interview_location" maxlength="255" placeholder="Google Meet link, office address, or phone details">
                </div>
                <div class="schedule-field">
                    <label for="scheduleInterviewMessage">Message to candidate</label>
                    <textarea id="scheduleInterviewMessage" name="message" maxlength="1000" placeholder="Add context, preparation notes, or interviewer details."></textarea>
                </div>
                <label class="schedule-check">
                    <input type="checkbox" name="send_email" value="1" checked>
                    Send email invitation to candidate
                </label>
            </div>
            <div class="schedule-interview-foot">
                <button type="button" class="review-action-btn js-schedule-interview-close">Cancel</button>
                <button type="submit" class="review-action-btn">
                    <i class="fas fa-paper-plane"></i> Send Invitation
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>
