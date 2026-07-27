<style>
.response-aging-pill {
  align-items: center;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 999px;
  color: #9a3412;
  display: inline-flex;
  font-size: 11px;
  font-weight: 700;
  gap: 5px;
  padding: 4px 9px;
  white-space: nowrap;
}
.response-aging-pill.is-danger {
  background: #fef2f2;
  border-color: #fecaca;
  color: #b91c1c;
}
body.dark .response-aging-pill {
  background: rgba(154, 52, 18, .22);
  border-color: rgba(251, 146, 60, .42);
  color: #fdba74;
}
body.dark .response-aging-pill.is-danger {
  background: rgba(153, 27, 27, .24);
  border-color: rgba(248, 113, 113, .42);
  color: #fca5a5;
}
/* ============================================================
   Candidate Pipeline Card — Compact "Option B" redesign
   Uses the existing HireMatrix --hm-* token system.
   Falls back to hardcoded hex where tokens aren't guaranteed.
   (NOTE: this block styles .cc-* classes, which are not used by
   the markup below — left untouched as requested, harmless.)
   ============================================================ */

.response-card-list--compact {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.cc-card {
  position: relative;
  background: var(--hm-surface, #ffffff);
  border: 1px solid var(--hm-border, #e6ebef);
  border-radius: 16px;
  padding: 20px 24px 16px;
  box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
  transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

body.dark .cc-card {
  background: var(--hm-surface-dark, #151b23);
  border-color: var(--hm-border-dark, #26303b);
}

.cc-card:hover {
  box-shadow: 0 4px 14px rgba(16, 24, 40, 0.08);
  border-color: var(--primary, #1FB7B5);
}

.cc-head {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.cc-head-check {
  padding-top: 6px;
}

.cc-head-check input {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.cc-avatar {
  flex-shrink: 0;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #e7f1ff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 18px;
}

.cc-avatar-photo {
  object-fit: cover;
}

.cc-head-copy {
  flex: 1;
  min-width: 0;
}

.cc-name-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.cc-name {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  color: var(--hm-text, #101828);
}

body.dark .cc-name {
  color: var(--hm-text-dark, #f2f5f7);
}

.cc-recommended {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 999px;
  background: rgba(83, 184, 108, 0.14);
  color: var(--secondary, #53B86C);
  white-space: nowrap;
}

.cc-sub-line {
  margin-top: 4px;
  font-size: 13.5px;
  color: var(--hm-text-muted, #667085);
}

.cc-match-ring {
  flex-shrink: 0;
  width: 68px;
  height: 68px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(closest-side, var(--hm-surface, #fff) 79%, transparent 80% 100%),
    conic-gradient(#2563eb calc(var(--match-score, 0%) * 1%), #dfe6ee 0);
  transform: rotate(0deg);
}

body.dark .cc-match-ring {
  background:
    radial-gradient(closest-side, var(--hm-surface-dark, #151b23) 79%, transparent 80% 100%),
    conic-gradient(#2563eb calc(var(--match-score, 0%) * 1%), #33404c 0);
}

.cc-match-ring strong {
  font-size: 16px;
  font-weight: 700;
  color: #2563eb;
  line-height: 1;
}

.cc-match-caption {
  font-size: 10px;
  color: var(--hm-text-muted, #667085);
  margin-top: 2px;
}

.cc-grid {
  margin-top: 18px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px 20px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--hm-border, #eef1f4);
}

body.dark .cc-grid {
  border-bottom-color: var(--hm-border-dark, #26303b);
}

.cc-grid-item {
  min-width: 0;
}

.cc-field-label {
  display: block;
  font-size: 12px;
  color: var(--hm-text-muted, #98a2b3);
  margin-bottom: 3px;
}

.cc-field-value {
  font-size: 14.5px;
  font-weight: 600;
  color: var(--hm-text, #101828);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

body.dark .cc-field-value {
  color: var(--hm-text-dark, #f2f5f7);
}

.cc-more-pill {
  display: inline-block;
  margin-left: 4px;
  font-size: 11px;
  font-weight: 600;
  padding: 1px 7px;
  border-radius: 999px;
  background: var(--hm-chip-bg, #f2f4f7);
  color: var(--hm-text-muted, #667085);
}

.cc-primary-actions {
  margin-top: 14px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.cc-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 16px;
  border-radius: 10px;
  border: 1px solid var(--hm-border, #d0d5dd);
  background: var(--hm-surface, #fff);
  color: var(--hm-text, #344054);
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

body.dark .cc-btn {
  background: var(--hm-surface-dark, #1b232c);
  border-color: var(--hm-border-dark, #33404c);
  color: var(--hm-text-dark, #e6e9ec);
}

.cc-btn:hover {
  border-color: var(--primary, #1FB7B5);
  color: var(--primary, #1FB7B5);
}

.cc-btn-success {
  border-color: rgba(83, 184, 108, 0.4);
  color: var(--secondary, #53B86C);
}

.cc-btn-success:hover {
  background: rgba(83, 184, 108, 0.1);
  border-color: var(--secondary, #53B86C);
  color: var(--secondary, #53B86C);
}

.cc-btn-danger {
  border-color: rgba(220, 38, 38, 0.35);
  color: #dc2626;
}

.cc-btn-danger:hover {
  background: rgba(220, 38, 38, 0.08);
  border-color: #dc2626;
  color: #dc2626;
}

.cc-expand-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  margin: 10px auto 0;
  border-radius: 50%;
  border: 1px solid var(--hm-border, #e6ebef);
  background: var(--hm-surface, #fff);
  color: var(--hm-text-muted, #667085);
  cursor: pointer;
  transition: transform 0.2s ease, color 0.15s ease;
}

body.dark .cc-expand-toggle {
  background: var(--hm-surface-dark, #1b232c);
  border-color: var(--hm-border-dark, #33404c);
  color: var(--hm-text-muted, #9aa4b2);
}

.cc-expand-toggle:hover {
  color: var(--primary, #1FB7B5);
  border-color: var(--primary, #1FB7B5);
}

.cc-expand-toggle.is-open i {
  transform: rotate(180deg);
}

.cc-expand-toggle i {
  transition: transform 0.2s ease;
}

.cc-details {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px dashed var(--hm-border, #e6ebef);
  display: flex;
  flex-direction: column;
  gap: 14px;
}

body.dark .cc-details {
  border-top-color: var(--hm-border-dark, #26303b);
}

.cc-details-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.cc-details-match-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--hm-text-muted, #667085);
}

/* ============================================================
   LAYOUT FIX v3 — response-card (actual markup used in the view)
   Two-column grid: checkbox | candidate details | sidebar.
   - Key skills row, Requirements row stacked below it (not side by side)
     inside the profile snapshot (the standalone "fit panel" duplicate
     block has been removed).
   - Hiring decision + Profile & resume are their own rows inside the
     profile snapshot, rendered as small, fixed-size icon buttons
     (not full-width stretched pills).
   - Contact & follow-up is ONE button row (View contact + Call +
     WhatsApp + Note + Follow-up together), living in the right sidebar.
   ============================================================ */

.response-card {
  position: relative !important;
  display: grid !important;
  grid-template-columns: 26px minmax(0, 1fr) 300px !important;
  column-gap: 28px !important;
  row-gap: 16px !important;
  align-items: start !important;
}

.response-card-check {
  grid-column: 1 !important;
  grid-row: 1 / -1 !important;
  padding-top: 6px !important;
}

/* Main content column: head -> profile-snapshot only now */
.response-card-main {
  grid-column: 2 !important;
  grid-row: 1 !important;
  width: 100% !important;
  min-width: 0 !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 16px !important;
}

.response-candidate-head {
  padding-right: 0 !important;
  width: 100% !important;
}

/* Profile snapshot: clean label / value list, like the reference layout,
   instead of a scattered wrapped row of mismatched widths */
.response-profile-snapshot {
  display: grid !important;
  grid-template-columns: 130px minmax(0, 1fr) !important;
  row-gap: 10px !important;
  column-gap: 16px !important;
  width: 100% !important;
  padding-bottom: 14px !important;
  border-bottom: 1px solid var(--hm-border, #eef1f4) !important;
}

body.dark .response-profile-snapshot {
  border-bottom-color: var(--hm-border-dark, #26303b) !important;
}

.response-profile-snapshot .response-detail-row,
.response-profile-snapshot .response-detail-row-inline {
  display: contents !important;
}

.response-profile-snapshot .response-field-label {
  align-self: start !important;
  padding-top: 2px !important;
}

/* Neutralize any inherited flex/stretch behaviour on the value cell
   itself so nested action clusters don't get forced to full width */
.response-profile-snapshot .response-detail-value {
  display: block !important;
  width: 100% !important;
}

/* ---- Hiring decision / Profile & resume action clusters ----
   These live inline inside profile-snapshot rows now. Force them to
   be small, left-aligned, fixed-size icon buttons — never stretched
   pills — regardless of any base .response-action-cluster /
   .response-action-icon rules defined elsewhere in the app. The
   extra `.response-profile-snapshot` ancestor bumps specificity so
   these rules win the cascade. */
.response-profile-snapshot .response-action-cluster {
  display: flex !important;
  flex-direction: row !important;
  flex-wrap: wrap !important;
  align-items: center !important;
  justify-content: flex-start !important;
  gap: 8px !important;
  width: fit-content !important;
  max-width: auto !important;
}
 
.response-profile-snapshot .response-action-icon:hover {
  border-color: var(--primary, #1FB7B5) !important;
  color: var(--primary, #1FB7B5) !important;
  background: rgba(31, 183, 181, 0.08) !important;
}

/* Shortlist (check) — success tint */
.response-profile-snapshot .response-action-icon[title="Shortlist"] {
  background: rgba(83, 184, 108, 0.1) !important;
  border-color: rgba(83, 184, 108, 0.3) !important;
  color: var(--secondary, #53B86C) !important;
}
.response-profile-snapshot .response-action-icon[title="Shortlist"]:hover {
  background: rgba(83, 184, 108, 0.18) !important;
  border-color: var(--secondary, #53B86C) !important;
}

/* Reject (x) — danger tint */
.response-profile-snapshot .response-action-icon.is-danger {
  background: rgba(220, 38, 38, 0.06) !important;
  border-color: rgba(220, 38, 38, 0.25) !important;
  color: #dc2626 !important;
}
.response-profile-snapshot .response-action-icon.is-danger:hover {
  background: rgba(220, 38, 38, 0.12) !important;
  border-color: #dc2626 !important;
  color: #dc2626 !important;
}

/* AI badge — slightly wider since it holds text, not just an icon */
.response-profile-snapshot .response-action-icon.response-action-ai {
  flex: 0 0 auto !important;
  width: auto !important;
  min-width: 32px !important;
  max-width: none !important;
  padding: 0 10px !important;
  font-weight: 700 !important;
  font-size: 11px !important;
  letter-spacing: 0.3px !important;
}

/* Sidebar: match score, communication, contact/follow-up —
   one clean stacked column instead of split across main + sidebar */
.response-card-side {
  grid-column: 3 !important;
  grid-row: 1 !important;
  width: 100% !important;
  max-width: 100% !important;
  margin-top: 0 !important;
  padding-top: 0 !important;
  border-top: none !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 14px !important;
}

.response-match-box {
  position: static !important;
  display: flex !important;
  flex-direction: row !important;
  align-items: center !important;
  gap: 12px !important;
  margin: 0 !important;
  padding-bottom: 14px !important;
  border-bottom: 1px solid var(--hm-border, #eef1f4) !important;
}

body.dark .response-match-box {
  border-bottom-color: var(--hm-border-dark, #26303b) !important;
}

.response-match-meta {
  display: flex !important;
  flex-direction: column !important;
  align-items: flex-start !important;
}

.communication-stack {
  order: initial !important;
  width: 100% !important;
  text-align: left !important;
  padding-bottom: 14px !important;
  border-bottom: 1px solid var(--hm-border, #eef1f4) !important;
}

body.dark .communication-stack {
  border-bottom-color: var(--hm-border-dark, #26303b) !important;
}

.communication-counts {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
}

/* Contact & follow-up: ONE section — the "View contact" button lives in the
   same button row as Call / WhatsApp / Note / Follow-up, not a separate row */
.candidate-cockpit {
  order: initial !important;
  width: 100% !important;
}

.candidate-cockpit-actions {
  display: flex !important;
  flex-direction: row !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
}

.cockpit-action {
  flex: 0 0 auto !important;
}

/* ---------- Responsive ---------- */
@media (max-width: 900px) {
  .response-card {
    grid-template-columns: 26px minmax(0, 1fr) !important;
  }
  .response-card-side {
    grid-column: 1 / -1 !important;
    grid-row: 2 !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
  }
  .response-match-box,
  .communication-stack,
  .candidate-cockpit {
    flex: 1 1 240px !important;
    min-width: 220px !important;
    border-bottom: none !important;
    padding-bottom: 0 !important;
  }
}

@media (max-width: 720px) {
  .cc-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .cc-head {
    flex-wrap: wrap;
  }
  .cc-match-ring {
    margin-left: auto;
  }
}

@media (max-width: 600px) {
  .response-card {
    grid-template-columns: 1fr !important;
  }
  .response-card-check {
    grid-row: 1 !important;
    padding-top: 0 !important;
  }
  .response-card-main {
    grid-column: 1 !important;
    grid-row: 2 !important;
  }
  .response-card-side {
    grid-column: 1 !important;
    grid-row: 3 !important;
    flex-direction: column !important;
  }
  .response-match-box,
  .communication-stack,
  .candidate-cockpit {
    flex: 1 1 auto !important;
  }
  .response-profile-snapshot {
    grid-template-columns: 1fr !important;
  }
  .response-profile-snapshot .response-detail-row,
  .response-profile-snapshot .response-detail-row-inline {
    display: flex !important;
    flex-direction: column !important;
    gap: 2px !important;
  }
}

@media (max-width: 480px) {
  .cc-grid {
    grid-template-columns: 1fr;
  }
  .cc-primary-actions {
    flex-direction: column;
  }
  .cc-btn {
    width: 100%;
    justify-content: center;
  }
}
 
.recruiter-pipeline-page.response-action-icon {
    background: #F8FBFD;
    border-color: #D7E3EA;
    border-radius: 8px;
    color: #0D8A90 !important;
    height: 32px;
    width: 100%;
}
.response-profile-snapshot .response-action-icon:hover {
    border-color:#1FB7B5 !important;
    color:#1FB7B5 !important;
    background: rgba(31, 183, 181, 0.08) !important;
}
.response-profile-snapshot.response-action-icon[title="Shortlist"]
Specificity: (0,3,0)
 {
    background: rgba(83, 184, 108, 0.1) !important;
    border-color: rgba(83, 184, 108, 0.3) !important;
    color:  #53B86C !important;
}
.response-profile-snapshot .response-action-icon[title="Shortlist"] {
    background: rgba(83, 184, 108, 0.1) !important;
    border-color: rgba(83, 184, 108, 0.3) !important;
    color:   #53B86C !important;
}
.response-profile-snapshot .response-action-icon[title="Shortlist"]:hover {
    background: rgba(83, 184, 108, 0.18) !important;
    border-color:  #53B86C !important;
}
    </style>
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
                    $responseIndicators = (array) ($app['response_indicators'] ?? []);
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
                                    <?php foreach ($responseIndicators as $responseIndicator): ?>
                                        <span class="response-aging-pill <?= ($responseIndicator['tone'] ?? '') === 'danger' ? 'is-danger' : '' ?>"
                                              title="<?= esc($responseIndicator['detail'] ?? '') ?>">
                                            <i class="<?= esc($responseIndicator['icon'] ?? 'fas fa-clock') ?>"></i>
                                            <?= esc($responseIndicator['type'] === 'unreviewed'
                                                ? 'Unreviewed ' . ($responseIndicator['age_days'] ?? 0) . 'd'
                                                : ($responseIndicator['type'] === 'feedback_overdue'
                                                    ? 'Feedback overdue'
                                                    : 'Not contacted')) ?>
                                        </span>
                                    <?php endforeach; ?>
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
                            
                            <div class="response-detail-row">
                                <span class="response-field-label">Activity</span>
                                <div class="response-detail-value"><?= (int) ($activity['profile_viewed_count'] ?? 0) ?> profile views, <?= (int) ($activity['resume_downloaded_count'] ?? 0) ?> resume downloads; last active <?= esc($lastActive) ?></div>
                            </div>
                            <div class="response-detail-row">
                                <span class="response-field-label">Key skills</span>
                                <div class="response-detail-value">
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
                            </div>
                            <div class="response-detail-row">
                                <span class="response-field-label">Requirements</span>
                                <div class="response-detail-value">
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

                            <div class="response-detail-row">
                                <span class="response-field-label">Hiring decision</span>
                                <div class="response-detail-value">
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
                                </div>
                            </div>

                            <div class="response-detail-row">
                                <span class="response-field-label">Profile &amp; resume</span>
                                <div class="response-detail-value">
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
                          <div> <span class="response-field-label">Current stage :</span><div class="hm-status-drop response-status-menu">
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
                                </div></div>
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
                                <button type="button"
                                        class="cockpit-action response-contact-button js-view-contact"
                                        data-contact-url="<?= esc($contactUrl) ?>"
                                        data-contact-target="<?= (int) $app['id'] ?>"
                                        title="View contact details"
                                        aria-label="View contact details for <?= esc($app['candidate_name'] ?? 'candidate') ?>">
                                    <i class="fas fa-address-card"></i> Contact
                                </button>
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
