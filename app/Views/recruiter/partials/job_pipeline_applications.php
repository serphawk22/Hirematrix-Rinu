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
    'ai_interview_completed' => 'fa-robot',
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
    overflow-y: hidden;
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
    min-width: 1500px;
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

.communication-stack {
    background: transparent;
    border: 0;
    color: inherit;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 5px;
    max-width: 240px;
    min-width: 180px;
    padding: 0;
    text-align: left;
    white-space: normal;
}
.communication-stack:hover .communication-preview,
.communication-stack:focus .communication-preview {
    color: #0D8A90;
    text-decoration: underline;
}
.communication-counts {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}
.communication-chip {
    align-items: center;
    background: rgba(31, 183, 181, 0.10);
    border: 1px solid rgba(31, 183, 181, 0.22);
    border-radius: 999px;
    color: #0D8A90;
    display: inline-flex;
    font-size: 0.72rem;
    font-weight: 700;
    gap: 5px;
    line-height: 1.2;
    padding: 3px 8px;
    white-space: nowrap;
}
.communication-chip.is-muted {
    background: #F1F5F9;
    border-color: #E2E8F0;
    color: #64748B;
}
.communication-latest {
    color: #64748B;
    display: grid;
    gap: 2px;
    font-size: 0.74rem;
    line-height: 1.35;
    min-width: 0;
}
.communication-latest strong {
    color: #16212B;
    font-size: 0.75rem;
    font-weight: 700;
}
.communication-preview {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
body.dark .communication-chip {
    background: rgba(31, 183, 181, 0.14);
    border-color: rgba(31, 183, 181, 0.28);
    color: #5EEAD4;
}
body.dark .communication-chip.is-muted {
    background: #1E293B;
    border-color: #23343A;
    color: #94A3B8;
}

.communication-drawer-backdrop {
    background: transparent;
    bottom: 0;
    display: none;
    left: 0;
    pointer-events: none;
    position: fixed;
    right: 0;
    top: 0;
    z-index: 1039;
}
.communication-drawer-backdrop.is-open {
    display: none;
}
.communication-drawer {
    background: #FFFFFF;
    border-left: 1px solid #D9ECE5;
    bottom: 0;
    box-shadow: -12px 0 30px rgba(15, 23, 42, 0.12);
    display: flex;
    flex-direction: column;
    max-width: min(560px, 100vw);
    position: fixed;
    right: 0;
    top: 0;
    transform: translateX(104%);
    transition: transform 180ms ease;
    width: 560px;
    z-index: 1040;
}
.communication-drawer.is-open {
    transform: translateX(0);
}
.communication-drawer-head {
    border-bottom: 1px solid #D9ECE5;
    display: grid;
    gap: 4px;
    padding: 20px 22px;
}
.communication-drawer-title {
    color: #16212B;
    font-size: 1rem;
    font-weight: 800;
    margin: 0;
}
.communication-drawer-subtitle {
    color: #64748B;
    font-size: 0.82rem;
}
.communication-drawer-close {
    align-items: center;
    background: transparent;
    border: 1px solid #D9ECE5;
    border-radius: 8px;
    color: #64748B;
    display: inline-flex;
    height: 34px;
    justify-content: center;
    position: absolute;
    right: 18px;
    top: 18px;
    width: 34px;
}
.communication-drawer-body {
    display: grid;
    gap: 16px;
    overflow-y: auto;
    padding: 18px 22px 26px;
}
.review-section {
    border: 1px solid #D9ECE5;
    border-radius: 12px;
    display: grid;
    gap: 12px;
    padding: 14px;
}
.review-section-title {
    color: #16212B;
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.03em;
    margin: 0;
    text-transform: uppercase;
}
.review-metrics {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.review-metric {
    background: #F4FBFA;
    border: 1px solid #D9ECE5;
    border-radius: 10px;
    display: grid;
    gap: 3px;
    min-width: 0;
    padding: 10px;
}
.review-metric strong {
    color: #16212B;
    font-size: 1rem;
    font-weight: 850;
}
.review-metric span {
    color: #64748B;
    font-size: 0.75rem;
    font-weight: 700;
}
.review-key-values {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.review-key-value {
    display: grid;
    gap: 2px;
    min-width: 0;
}
.review-key-value span {
    color: #64748B;
    font-size: 0.72rem;
    font-weight: 700;
}
.review-key-value strong {
    color: #16212B;
    font-size: 0.84rem;
    font-weight: 750;
    overflow-wrap: anywhere;
}
.review-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.review-chip {
    background: rgba(31, 183, 181, 0.10);
    border: 1px solid rgba(31, 183, 181, 0.22);
    border-radius: 999px;
    color: #0D8A90;
    font-size: 0.74rem;
    font-weight: 750;
    line-height: 1.25;
    padding: 4px 9px;
}
.review-chip.is-missing {
    background: #FEF2F2;
    border-color: #FECACA;
    color: #991B1B;
}
.review-note-box {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    color: #475569;
    font-size: 0.82rem;
    line-height: 1.55;
    padding: 11px;
    white-space: pre-wrap;
}
.review-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.review-action-btn,
.review-action-link {
    align-items: center;
    background: transparent;
    border: 1.5px solid #1FB7B5;
    border-radius: 8px;
    color: #0D8A90;
    display: inline-flex;
    font-size: 0.78rem;
    font-weight: 800;
    gap: 6px;
    justify-content: center;
    min-height: 34px;
    padding: 7px 11px;
    text-decoration: none !important;
}
.review-action-btn:hover,
.review-action-link:hover {
    background: #1FB7B5;
    color: #FFFFFF;
}
.review-action-btn.is-danger {
    border-color: #FCA5A5;
    color: #B91C1C;
}
.review-action-btn.is-danger:hover {
    background: #B91C1C;
    border-color: #B91C1C;
    color: #FFFFFF;
}
.schedule-interview-modal {
    align-items: center;
    background: rgba(15, 23, 42, 0.55);
    display: none;
    inset: 0;
    justify-content: center;
    padding: 20px;
    position: fixed;
    z-index: 1060;
}
.schedule-interview-modal.is-open {
    display: flex;
}
.schedule-interview-dialog {
    background: #FFFFFF;
    border: 1px solid #D9ECE5;
    border-radius: 12px;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
    max-width: 620px;
    overflow: hidden;
    width: min(620px, 100%);
}
.schedule-interview-head,
.schedule-interview-foot {
    align-items: center;
    border-bottom: 1px solid #D9ECE5;
    display: flex;
    gap: 12px;
    justify-content: space-between;
    padding: 16px 18px;
}
.schedule-interview-foot {
    border-bottom: 0;
    border-top: 1px solid #D9ECE5;
    justify-content: flex-end;
}
.schedule-interview-title {
    color: #16212B;
    font-size: 1rem;
    font-weight: 800;
    margin: 0;
}
.schedule-interview-subtitle {
    color: #64748B;
    font-size: 0.82rem;
    margin-top: 2px;
}
.schedule-interview-close {
    align-items: center;
    background: transparent;
    border: 1px solid #D9ECE5;
    border-radius: 8px;
    color: #64748B;
    display: inline-flex;
    height: 36px;
    justify-content: center;
    width: 36px;
}
.schedule-interview-body {
    display: grid;
    gap: 14px;
    padding: 18px;
}
.schedule-interview-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.schedule-field {
    display: grid;
    gap: 6px;
}
.schedule-field label {
    color: #334155;
    font-size: 0.78rem;
    font-weight: 800;
    margin: 0;
}
.schedule-field input,
.schedule-field select,
.schedule-field textarea {
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    color: #16212B;
    font-size: 0.9rem;
    min-height: 40px;
    padding: 9px 10px;
    width: 100%;
}
.schedule-field textarea {
    min-height: 92px;
    resize: vertical;
}
.schedule-check {
    align-items: center;
    color: #334155;
    display: flex;
    font-size: 0.86rem;
    gap: 8px;
}
@media (max-width: 576px) {
    .schedule-interview-grid {
        grid-template-columns: 1fr;
    }
}
.pipeline-table tbody tr.is-reviewable {
    cursor: pointer;
}
body.dark .review-section,
body.dark .review-metric {
    border-color: #23343A;
}
body.dark .review-section-title,
body.dark .review-metric strong,
body.dark .review-key-value strong {
    color: #F8FAFC;
}
body.dark .review-metric,
body.dark .review-note-box {
    background: #1B2A2F;
}
body.dark .review-metric span,
body.dark .review-key-value span,
body.dark .review-note-box {
    color: #94A3B8;
}
.communication-drawer-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.communication-timeline {
    display: grid;
    gap: 10px;
}
.communication-timeline-item {
    border: 1px solid #D9ECE5;
    border-radius: 10px;
    display: grid;
    gap: 7px;
    padding: 12px;
}
.communication-timeline-meta {
    align-items: center;
    color: #64748B;
    display: flex;
    flex-wrap: wrap;
    font-size: 0.76rem;
    font-weight: 700;
    gap: 7px;
}
.communication-timeline-subject {
    color: #16212B;
    font-size: 0.86rem;
    font-weight: 800;
    line-height: 1.35;
}
.communication-timeline-preview {
    color: #64748B;
    font-size: 0.8rem;
    line-height: 1.5;
    white-space: normal;
}
.communication-empty-state {
    border: 1px dashed #D9ECE5;
    border-radius: 10px;
    color: #64748B;
    font-size: 0.86rem;
    padding: 18px;
    text-align: center;
}
body.dark .communication-drawer {
    background: #162327;
    border-left-color: #23343A;
}
body.dark .communication-drawer-head,
body.dark .communication-timeline-item {
    border-color: #23343A;
}
body.dark .communication-drawer-title,
body.dark .communication-timeline-subject {
    color: #F8FAFC;
}
body.dark .communication-drawer-subtitle,
body.dark .communication-timeline-meta,
body.dark .communication-timeline-preview,
body.dark .communication-empty-state {
    color: #94A3B8;
}
body.dark .communication-drawer-close {
    border-color: #23343A;
    color: #94A3B8;
}
body.dark .communication-latest,
body.dark .communication-latest strong {
    color: #94A3B8;
}

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
    .pipeline-table td:nth-child(10),
    .pipeline-table thead th:nth-child(11),
    .pipeline-table td:nth-child(11) {
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
    <div class="pipeline-table-wrap" style="border-radius: 20px !important;">
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
                    <th>Communication</th>
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
                        $allCandidateSkills = array_values((array) ($app['candidate_skills'] ?? []));
                        $allRequiredSkills = array_values((array) ($app['required_skills'] ?? []));
                        $candidateSkills = array_slice($allCandidateSkills, 0, 4);
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
                        $notePreview = strlen($note) > 90 ? substr($note, 0, 90) . '...' : $note;
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
                        $communicationPayload = [
                            'candidateName' => (string) ($app['candidate_name'] ?? '-'),
                            'candidateEmail' => (string) ($app['candidate_email'] ?? ''),
                            'emailCount' => $emailCount,
                            'messageCount' => $messageCount,
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
                    ?>
                    <tr data-application-row="<?= (int) $app['id'] ?>"
                        class="is-reviewable js-open-candidate-review"
                        data-review="<?= $communicationJson ?>">
                        <td><input type="checkbox" class="pipeline-check" name="candidate_ids[]" value="<?= (int) $app['id'] ?>" data-email="<?= esc($app['candidate_email'] ?? '') ?>"></td>
                        <td>#<?= (int) $app['id'] ?></td>
                        <td class="candidate-name-cell">
                            <strong><?= esc($app['candidate_name'] ?? '-') ?></strong>
                            <small><?= esc($app['candidate_email'] ?? '-') ?></small>
                        </td>
                        <td onclick="event.stopPropagation();">
                            <div class="hm-status-drop" style="position:relative;display:inline-block;">
                                <button class="status-pill hm-status-drop-btn" type="button"
                                    style="border:none;background:rgba(22,33,43,0.07);cursor:pointer;"
                                    title="Change status">
                                    <?= esc($statuses[$appStatus] ?? ucfirst(str_replace('_', ' ', $appStatus))) ?>
                                </button>
                                <div class="hm-status-drop-menu" style="display:none;position:absolute;left:0;top:100%;min-width:180px;background:#fff;border:1px solid #D9ECE5;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.08);z-index:9999;padding:4px 0;">
                                    <?php foreach ($statuses as $sv => $sl): ?>
                                        <?php if ($sv !== $appStatus && in_array($sv, ['applied','shortlisted','on_hold','rejected'], true)): ?>
                                            <a class="hm-status-drop-item hm-pipeline-status-change"
                                               href="#"
                                               style="display:block;padding:8px 16px;font-size:0.88rem;color:#16212B;text-decoration:none;"
                                               data-application-id="<?= (int)$app['id'] ?>"
                                               data-status="<?= esc($sv) ?>"
                                               data-label="<?= esc($sl) ?>">
                                                <?= esc($sl) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </td>
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
                        <td>
                            <?php if ($emailCount > 0 || $messageCount > 0): ?>
                                <button type="button" class="communication-stack js-open-communication-drawer"
                                        data-communication="<?= $communicationJson ?>"
                                        aria-label="Open communication history for <?= esc($app['candidate_name'] ?? 'candidate') ?>">
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
                            <?php else: ?>
                                <button type="button" class="communication-stack js-open-communication-drawer"
                                        data-communication="<?= $communicationJson ?>"
                                        aria-label="Open communication history for <?= esc($app['candidate_name'] ?? 'candidate') ?>">
                                    <span class="communication-chip is-muted">No conversation</span>
                                </button>
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
                                <?php if ($appStatus === 'ai_interview_completed'): ?>
                                    <button
                                        type="button"
                                        class="btn-outline-primary view-ai-report-btn"
                                        data-candidate-id="<?= (int) ($app['candidate_id'] ?? 0) ?>"
                                        data-jobrole="<?= esc($job['title'] ?? '') ?>"
                                        data-candidate-name="<?= esc($app['candidate_name'] ?? '-') ?>"
                                        title="Open AI interview report"
                                    >
                                        AI Report
                                    </button>
                                <?php endif; ?>
                                <a href="<?= base_url('recruiter/candidate/' . (int) $app['candidate_id'] . '?application_id=' . (int) $app['id'] . '&job_id=' . (int) $job['id']) ?>" class="btn- btn-outline-primary" title="Open profile">View</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

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
            <div id="communicationDrawerOverview"></div>
            <div id="communicationDrawerSkills"></div>
            <div id="communicationDrawerNotes"></div>
            <div class="communication-drawer-stats" id="communicationDrawerStats"></div>
            <div class="communication-timeline" id="communicationDrawerTimeline"></div>
            <div id="communicationDrawerActions"></div>
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
<?php endif; ?><style>\nbody.dark .dropdown-menu{background:#162327!important;border-color:#23343A!important;}body.dark .dropdown-item{color:#94A3B8!important;background:transparent!important;}\nbody.dark .dropdown-item:hover{background:rgba(31,183,181,.1)!important;color:#F8FAFC!important;}\n</style><script>\n(function(){\nvar page=document.getElementById("recruiterPipelinePage");\nif(!page)return;\ndocument.addEventListener("click",function(e){\nvar item=e.target.closest(".hm-pipeline-status-change");\nif(!item)return;\ne.preventDefault();e.stopPropagation();\nvar appId=item.dataset.applicationId,status=item.dataset.status,label=item.dataset.label;\nvar drop=item.closest(".hm-status-drop"),btn=drop?drop.querySelector(".hm-status-drop-btn"):null;\nvar base=page.dataset.statusUrlBase||"";\nvar url=base+appId;\nif(status==="shortlisted")url=base.replace("update-status/","shortlist/")+appId;\nelse if(status==="rejected")url=base.replace("update-status/","reject/")+appId;\nif(btn){btn.textContent="...";btn.disabled=true;}\nvar fd=new FormData();\nfd.append(page.dataset.csrfName,page.dataset.csrfHash);\nfd.append("status",status);\nfetch(url,{method:"POST",headers:{"X-Requested-With":"XMLHttpRequest","Accept":"application/json"},body:fd})\n.then(function(r){return r.json().catch(function(){return{success:r.ok};});})\n.then(function(p){\nif(p&&p.csrf_hash&&p.csrf_token_name){page.dataset.csrfHash=p.csrf_hash;document.querySelectorAll("input[name="+p.csrf_token_name+"]").forEach(function(i){i.value=p.csrf_hash;});}\nif(btn){btn.textContent=label;btn.disabled=false;}\n}).catch(function(){if(btn){btn.textContent=label;btn.disabled=false;}});\n});\n})();\n</script>