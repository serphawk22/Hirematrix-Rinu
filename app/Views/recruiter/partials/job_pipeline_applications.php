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
<style>
/* ============================================================
   RECRUITER PIPELINE — APPLICATIONS PARTIAL THEME CSS
   Light + Dark (body.dark) — no CSS variables, hard color codes
   ============================================================ */

/* ══════════════════════════════════════════
   EMPTY STATE
══════════════════════════════════════════ */
.pipeline-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 4rem 2rem;
    text-align: center;
    color: #64748B;
    background: #FFFFFF;
    border-radius: 12px;
    border: 1px solid #D9ECE5;
}
.pipeline-empty i {
    font-size: 2.5rem;
    color: #94A3B8;
    margin-bottom: 4px;
}
.pipeline-empty strong {
    font-size: 1rem;
    font-weight: 600;
    color: #16212B;
}
body.dark .pipeline-empty {
    background: #162327;
    border-color: #23343A;
    color: #94A3B8;
}
body.dark .pipeline-empty i     { color: #4A5C63; }
body.dark .pipeline-empty strong { color: #F8FAFC; }

/* ══════════════════════════════════════════
   TABLE WRAP
══════════════════════════════════════════ */
.pipeline-table-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 12px;
    border: 1px solid #D9ECE5;
    background: #FFFFFF;
}
body.dark .pipeline-table-wrap {
    border-color: #23343A;
    background: #162327;
}

/* ══════════════════════════════════════════
   TABLE BASE
══════════════════════════════════════════ */
.pipeline-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    /* Force every column to its minimum natural width so rows stay single-line */
    table-layout: auto;
}

/* ── Head ── */
.pipeline-table thead tr {
    background: #EDF8F5;
    border-bottom: 2px solid #D9ECE5;
}
body.dark .pipeline-table thead tr {
    background: #1B2A2F;
    border-bottom: 2px solid #23343A;
}

.pipeline-table thead th {
    padding: 10px 12px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748B;
    background: #EDF8F5;
    border: none;
    white-space: nowrap;
    vertical-align: middle;
}
body.dark .pipeline-table thead th {
    color: #94A3B8;
    background: #1B2A2F;
}

/* ── Body rows ── */
.pipeline-table tbody tr {
    background: #FFFFFF;
    border-bottom: 1px solid #D9ECE5;
    transition: background 0.15s;
}
.pipeline-table tbody tr:last-child { border-bottom: none; }
.pipeline-table tbody tr:hover      { background: #F4FBFA; }

body.dark .pipeline-table tbody tr {
    background: #162327;
    border-bottom: 1px solid #23343A;
}
body.dark .pipeline-table tbody tr:hover { background: #1B2A2F; }

/* ── Cells ── */
.pipeline-table td {
    padding: 10px 12px;
    vertical-align: middle;
    color: #16212B;
    background: transparent;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    /* Keep every cell to a single line unless it's the skills/tags cell */
    white-space: nowrap;
}
body.dark .pipeline-table td { color: #94A3B8; }

/* ── Checkbox column ── */
.pipeline-table .pipeline-check {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #1FB7B5;
}

/* ── ID column ── */
.pipeline-table td:nth-child(2) {
    font-size: 0.8rem;
    color: #94A3B8;
    font-weight: 600;
    white-space: nowrap;
}
body.dark .pipeline-table td:nth-child(2) { color: #4A5C63; }

.pipeline-table .text-right { text-align: right; }

/* ══════════════════════════════════════════
   CANDIDATE NAME CELL
   — allow wrapping here so long names don't
     blow out the layout
══════════════════════════════════════════ */
.pipeline-table .candidate-name-cell {
    min-width: 150px;
    max-width: 210px;
    white-space: normal;      /* override the nowrap above */
}
.pipeline-table .candidate-name-cell strong {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: #16212B;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pipeline-table .candidate-name-cell small {
    display: block;
    font-size: 0.75rem;
    color: #64748B;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
body.dark .pipeline-table .candidate-name-cell strong { color: #F8FAFC; }
body.dark .pipeline-table .candidate-name-cell small  { color: #94A3B8; }

/* ══════════════════════════════════════════
   STATUS PILL  (stage badge)
══════════════════════════════════════════ */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 11px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    background: rgba(22, 33, 43, 0.07);
    color: #0D8A90;
    border: none;
    text-decoration: none !important;
    white-space: nowrap;
    cursor: default;
    line-height: 1.5;
}
body.dark .status-pill {
    background: rgba(122, 139, 150, 0.18);
    color: #1FB7B5;
}

/* ══════════════════════════════════════════
   STAGE PILLS  (coloured per tone)
══════════════════════════════════════════ */
.stage-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 11px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    line-height: 1.5;
}
.stage-pill i { font-size: 0.68rem; }

.stage-pill.neutral   { background: #F1F5F9; color: #475569; }
.stage-pill.success   { background: #D1FAE5; color: #065F46; }
.stage-pill.info      { background: #DBEAFE; color: #1E40AF; }
.stage-pill.violet    { background: #EDE9FE; color: #5B21B6; }
.stage-pill.warning   { background: #FEF3C7; color: #92400E; }
.stage-pill.danger    { background: #FEE2E2; color: #991B1B; }
.stage-pill.muted     { background: #F1F5F9; color: #64748B; }

body.dark .stage-pill.neutral { background: #1E293B; color: #94A3B8; }
body.dark .stage-pill.success { background: rgba(6,78,59,0.28);   color: #6EE7B7; }
body.dark .stage-pill.info    { background: rgba(30,58,95,0.28);   color: #93C5FD; }
body.dark .stage-pill.violet  { background: rgba(91,33,182,0.22);  color: #C4B5FD; }
body.dark .stage-pill.warning { background: rgba(120,53,15,0.28);  color: #FCD34D; }
body.dark .stage-pill.danger  { background: rgba(127,29,29,0.22);  color: #FCA5A5; }
body.dark .stage-pill.muted   { background: #1E293B; color: #7A8B96; }

/* ══════════════════════════════════════════
   SKILL & TAG CHIPS
   — inline flex, nowrap, scrollable container
══════════════════════════════════════════ */
.pipeline-table td.skills-cell,
.pipeline-table td.tags-cell {
    white-space: normal;   /* allow this column to wrap chips */
    min-width: 140px;
    max-width: 220px;
}

.pipeline-skill-list,
.pipeline-tag-list {
    display: flex;
    flex-wrap: wrap;        /* wrap chips instead of stacking as block */
    gap: 4px;
    align-items: center;
}

.pipeline-mini-chip {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 500;
    background: #EDF8F5;
    color: #0D8A90;
    border: 1px solid #D9ECE5;
    white-space: nowrap;
    line-height: 1.5;
}
body.dark .pipeline-mini-chip {
    background: #1B2A2F;
    color: #1FB7B5;
    border-color: #23343A;
}

/* ══════════════════════════════════════════
   NOTE PREVIEW
══════════════════════════════════════════ */
.pipeline-note-preview {
    display: block;
    font-size: 0.75rem;
    color: #64748B;
    max-width: 180px;
    line-height: 1.4;
    cursor: help;
    white-space: normal;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
body.dark .pipeline-note-preview { color: #94A3B8; }

/* ══════════════════════════════════════════
   TEXT MUTED
══════════════════════════════════════════ */
.pipeline-table .text-muted {
    color: #94A3B8 !important;
    font-size: 0.82rem;
}
body.dark .pipeline-table .text-muted { color: #4A5C63 !important; }

/* ══════════════════════════════════════════
   ATS SCORE
══════════════════════════════════════════ */
.ats-score {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
    min-width: 68px;
}
.ats-score strong {
    font-size: 0.85rem;
    font-weight: 700;
    color: #16212B;
    line-height: 1;
}
body.dark .ats-score strong { color: #F8FAFC; }

.ats-score-bar {
    display: block;
    width: 60px;
    height: 4px;
    background: #D9ECE5;
    border-radius: 2px;
    overflow: hidden;
}
body.dark .ats-score-bar { background: #23343A; }

.ats-score-bar span {
    display: block;
    height: 100%;
    background: linear-gradient(90deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
    border-radius: 2px;
    transition: width 0.4s ease;
}

/* ══════════════════════════════════════════
   ACTIVITY STACK
══════════════════════════════════════════ */
.activity-stack {
    display: flex;
    flex-direction: column;
    gap: 3px;
    white-space: nowrap;
}
.activity-stack span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: #64748B;
}
.activity-stack span i {
    font-size: 0.68rem;
    color: #94A3B8;
}
body.dark .activity-stack span   { color: #94A3B8; }
body.dark .activity-stack span i { color: #4A5C63; }

/* ══════════════════════════════════════════
   ROW ACTIONS
══════════════════════════════════════════ */
.pipeline-row-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    justify-content: flex-end;
    white-space: nowrap;
}

/* Status select */
.pipeline-status-select {
    font-size: 0.78rem;
    font-weight: 600;
    padding: 5px 8px;
    border-radius: 6px;
    border: 1.5px solid #D9ECE5;
    background: #FFFFFF;
    color: #16212B;
    cursor: pointer;
    transition: border-color 0.2s;
    max-width: 130px;
    appearance: auto;
}
.pipeline-status-select:focus {
    outline: none;
    box-shadow: none;
    border-color: #0D8A90;
}
.pipeline-status-select:hover { border-color: #1FB7B5; }

body.dark .pipeline-status-select {
    background: #111111 !important;
    border-color: #23343A;
    color: #F8FAFC;
}
body.dark .pipeline-status-select:focus { border-color: #0D8A90; }
body.dark .pipeline-status-select:hover { border-color: #1FB7B5; }
body.dark .pipeline-status-select option {
    background: #111111 !important;
    color: #F8FAFC;
}

/* View button */
.btn-outline-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 14px;
    border-radius: 6px;
    border: 1.5px solid #1FB7B5;
    background: transparent;
    color: #1FB7B5;
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none !important;
    transition: all 0.2s;
    white-space: nowrap;
    cursor: pointer;
}
.btn-outline-primary:hover,
.btn-outline-primary:focus {
    background: #1FB7B5;
    color: #ffffff;
    transform: translateY(-1px);
    outline: none;
    box-shadow: none;
}
body.dark .btn-outline-primary {
    border-color: #1FB7B5;
    color: #1FB7B5;
    background: transparent;
}
body.dark .btn-outline-primary:hover,
body.dark .btn-outline-primary:focus {
    background: #1FB7B5;
    color: #ffffff;
}

/* Legacy icon-only action link */
.pipeline-row-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: 1.5px solid #D9ECE5;
    background: #FFFFFF;
    color: #64748B;
    font-size: 0.82rem;
    text-decoration: none !important;
    transition: all 0.2s;
    flex-shrink: 0;
}
.pipeline-row-action:hover {
    border-color: #1FB7B5;
    color: #1FB7B5;
    background: #EDF8F5;
}
body.dark .pipeline-row-action {
    background: #1B2A2F;
    border-color: #23343A;
    color: #94A3B8;
}
body.dark .pipeline-row-action:hover {
    border-color: #1FB7B5;
    color: #1FB7B5;
    background: rgba(31,183,181,0.08);
}

/* ══════════════════════════════════════════
   PAGINATION WRAPPER
══════════════════════════════════════════ */
.pipeline-table-wrap + div,
div.p-3.bg-white {
    background: #FFFFFF !important;
    border-top: 1px solid #D9ECE5;
    border-radius: 0 0 12px 12px;
    padding: 12px 16px;
}
body.dark div.p-3.bg-white {
    background: #162327 !important;
    border-top: 1px solid #23343A;
}

div.p-3 ul.pagination li.page-item a.page-link,
div.p-3 ul.pagination li.page-item span.page-link {
    color: #1FB7B5 !important;
    background-color: transparent !important;
    border-color: #D9ECE5 !important;
    font-size: 0.875rem;
    text-decoration: none !important;
    transition: all 0.15s;
}
div.p-3 ul.pagination li.page-item a.page-link:hover {
    color: #0D8A90 !important;
    background-color: #EDF8F5 !important;
    border-color: #1FB7B5 !important;
}
div.p-3 ul.pagination li.page-item.active .page-link {
    color: #ffffff !important;
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%) !important;
    border-color: #1FB7B5 !important;
}
div.p-3 ul.pagination li.page-item.disabled .page-link {
    color: #94A3B8 !important;
    border-color: #D9ECE5 !important;
}

body.dark div.p-3 ul.pagination li.page-item a.page-link,
body.dark div.p-3 ul.pagination li.page-item span.page-link {
    color: #1FB7B5 !important;
    background-color: transparent !important;
    border-color: #23343A !important;
}
body.dark div.p-3 ul.pagination li.page-item a.page-link:hover {
    background-color: #1B2A2F !important;
    border-color: #1FB7B5 !important;
}
body.dark div.p-3 ul.pagination li.page-item.disabled .page-link {
    color: #4A5C63 !important;
    border-color: #23343A !important;
}

/* ══════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════ */
@media (max-width: 768px) {
    /* Hide lower-priority columns on mobile */
    .pipeline-table thead th:nth-child(7),
    .pipeline-table td:nth-child(7),
    .pipeline-table thead th:nth-child(8),
    .pipeline-table td:nth-child(8),
    .pipeline-table thead th:nth-child(10),
    .pipeline-table td:nth-child(10) {
        display: none;
    }
    .pipeline-status-select  { max-width: 100px; font-size: 0.72rem; }
    .pipeline-table td,
    .pipeline-table thead th { padding: 8px 9px; }
}
</style>
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
                        <td><span class="status-pill"></i><?= esc($statuses[$appStatus] ?? ucfirst(str_replace('_', ' ', $appStatus))) ?></span></td>
                        <td><?= esc($app['experience_display'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($candidateSkills)): ?>
                                <div class="pipeline-skill-list">
                                    <?php foreach ($candidateSkills as $skill): ?>
                                        <span class="status-pill"><?= esc($skill) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count((array) ($app['candidate_skills'] ?? [])) > 4): ?>
                                        <span class="status-pill">+<?= count((array) $app['candidate_skills']) - 4 ?></span>
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
                                        <span class="status-pill"><?= esc($tag) ?></span>
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
                                <span> <?= (int) ($activity['profile_viewed_count'] ?? 0) ?> views</span>
                                <span class="ml-2"> <?= (int) ($activity['resume_downloaded_count'] ?? 0) ?> resumes</span>
                            </div>
                        </td>
                        <td>
                            <div class="pipeline-row-actions">
                                <select class="pipeline-status-select" onchange="updateApplicationStatus(<?= (int) $app['id'] ?>, this.value)">
                                    <?php foreach ($statuses as $statusKey => $statusText): ?>
                                        <option value="<?= esc($statusKey) ?>" <?= $appStatus === $statusKey ? 'selected' : '' ?>><?= esc($statusText) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="<?= base_url('recruiter/candidate/' . (int) $app['candidate_id'] . '?application_id=' . (int) $app['id'] . '&job_id=' . (int) $job['id']) ?>" class="btn- btn-outline-primary" title="Open profile">View</a>
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
