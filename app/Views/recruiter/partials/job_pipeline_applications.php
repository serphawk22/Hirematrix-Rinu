<?php
$statusTones = $statusTones ?? [
    'applied' => 'neutral',
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

<?php if (empty($paginatedApplications)): ?>
    <div class="pipeline-empty">
        <i class="fas fa-user-slash"></i>
        <strong>No candidates found in this stage.</strong>
    </div>
<?php else: ?>
    <div class="pipeline-table-wrap">
        <table class="pipeline-table" id="candidatePipelineTable">
            <thead>
                <tr>
                    <th style="width: 44px;"><input type="checkbox" class="select-all pipeline-check" aria-label="Select all candidates in this table" onchange="togglePipelineCandidates(this)"></th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Stage</th>
                    <th>Experience</th>
                    <th>Skills</th>
                    <th>Tags</th>
                    <th>Notes</th>
                    <th>Applied</th>
                    <th>Last Active</th>
                    <th>ATS Match</th>
                    <th>Activity</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paginatedApplications as $app): ?>
                    <?php
                        $appStatus = $app['status'] ?? 'applied';
                        $tone = $statusTones[$appStatus] ?? 'neutral';
                        $appliedAt = !empty($app['applied_at']) ? date('d M, Y', strtotime($app['applied_at'])) : '-';
                        $lastActive = !empty($app['last_login']) ? date('M d, Y', strtotime($app['last_login'])) : 'Never';
                        $activity = $app['recruiter_activity'] ?? [];
                        $atsScore = (int) ($app['ats_score'] ?? 0);
                        $candidateSkills = array_slice((array) ($app['candidate_skills'] ?? []), 0, 4);
                        $tags = array_values(array_filter(array_map('trim', explode(',', (string) ($app['recruiter_tags'] ?? '')))));
                        $note = trim((string) ($app['recruiter_notes'] ?? ''));
                        $notePreview = strlen($note) > 90 ? substr($note, 0, 90) . '...' : $note;
                    ?>
                    <tr data-application-row="<?= (int) $app['id'] ?>">
                        <td><input type="checkbox" class="pipeline-check" name="candidate_ids[]" value="<?= (int) $app['id'] ?>" data-email="<?= esc($app['candidate_email'] ?? '') ?>"></td>
                        <td>#<?= (int) $app['id'] ?></td>
                        <td class="candidate-name-cell">
                            <strong><?= esc($app['candidate_name'] ?? '-') ?></strong>
                            <small><?= esc($app['candidate_email'] ?? '-') ?></small>
                        </td>
                        <td><span class="stage-pill <?= esc($tone) ?>"><i class="fas <?= esc($stageIcons[$appStatus] ?? 'fa-circle') ?>"></i><?= esc($statuses[$appStatus] ?? ucfirst(str_replace('_', ' ', $appStatus))) ?></span></td>
                        <td><?= esc($app['experience_display'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($candidateSkills)): ?>
                                <div class="pipeline-skill-list">
                                    <?php foreach ($candidateSkills as $skill): ?>
                                        <span class="pipeline-mini-chip"><?= esc($skill) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count((array) ($app['candidate_skills'] ?? [])) > 4): ?>
                                        <span class="pipeline-mini-chip">+<?= count((array) $app['candidate_skills']) - 4 ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($tags)): ?>
                                <div class="pipeline-tag-list">
                                    <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                        <span class="pipeline-mini-chip"><?= esc($tag) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($note !== ''): ?>
                                <small class="pipeline-note-preview" title="<?= esc($note) ?>"><?= esc($notePreview) ?></small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($appliedAt) ?></td>
                        <td><?= esc($lastActive) ?></td>
                        <td>
                            <span class="ats-score">
                                <strong><?= $atsScore ?>%</strong>
                                <span class="ats-score-bar"><span style="width: <?= min(100, max(0, $atsScore)) ?>%;"></span></span>
                            </span>
                        </td>
                        <td>
                            <div class="activity-stack">
                                <span><i class="far fa-eye"></i><?= (int) ($activity['profile_viewed_count'] ?? 0) ?> views</span>
                                <span class="ml-2"><i class="fas fa-download"></i><?= (int) ($activity['resume_downloaded_count'] ?? 0) ?> resumes</span>
                            </div>
                        </td>
                        <td>
                            <div class="pipeline-row-actions">
                                <select class="pipeline-status-select" onchange="updateApplicationStatus(<?= (int) $app['id'] ?>, this.value)">
                                    <?php foreach ($statuses as $statusKey => $statusText): ?>
                                        <option value="<?= esc($statusKey) ?>" <?= $appStatus === $statusKey ? 'selected' : '' ?>><?= esc($statusText) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="<?= base_url('recruiter/candidate/' . (int) $app['candidate_id'] . '?application_id=' . (int) $app['id'] . '&job_id=' . (int) $job['id']) ?>" class="pipeline-row-action" title="Open profile"><i class="fas fa-user"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pager->getTotal() > 10): ?>
        <div class="p-3 bg-white">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
