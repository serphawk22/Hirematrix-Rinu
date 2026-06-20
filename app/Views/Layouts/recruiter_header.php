<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="base-url" content="<?= base_url() ?>">
    <title><?= esc($title ?? 'Recruiter Portal') ?></title>
    <script>
        (function () {
            try {
                var darkTheme = localStorage.getItem('hm_theme') === 'dark'
                    || localStorage.getItem('recruiter-theme') === 'dark';
                if (darkTheme) {
                    document.documentElement.classList.add('hm-dark-preload');
                }
            } catch (error) {}
        })();
    </script>
    <link rel="icon" type="image/png" href="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/custom-bs.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/jquery.fancybox.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/bootstrap-select.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/icomoon/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/line-icons/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/owl.carousel.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/animate.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/recruiter-pages.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/fontawesome-all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/responsive.css?v=' . @filemtime(FCPATH . 'jobboard/css/responsive.css')) ?>">

    <style>
    /* ═══════════════════════════════════════════
       DESIGN TOKENS
    ═══════════════════════════════════════════ */
    :root {
        --sb-width:          250px;
        --sb-width-mini:      66px;
        --topbar-h:           60px;

        /* brand */
        --hm-primary:        #1FB7B5;
        --hm-primary-dark:   #0D8A90;
        --hm-secondary:      #53B86C;
        --hm-accent:         #B5D84E;
        --hm-brand-grad:     linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);

        /* surface */
        --hm-bg:             #F4FBFA;
        --hm-bg-2:           #EEF9F2;
        --hm-surface-grad:   linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%);
        --hm-card:           #ffffff;

        /* text */
        --hm-text:           #16212B;
        --hm-muted:          #64748B;
        --hm-light:          #94A3B8;

        /* borders */
        --hm-border:         #D9ECE5;

        /* states */
        --hm-active-bg:      #E0F5F0;
        --hm-hover-bg:       #EBF7F4;
    }

    /* ═══════════════════════════════════════════
       RESET GLOBAL THEME INTERFERENCE
       (recruiter-pages.css / Bootstrap orange)
    ═══════════════════════════════════════════ */
    body.recruiter-jobboard .hm-sidebar a,
    body.recruiter-jobboard .hm-sidebar a:hover,
    body.recruiter-jobboard .hm-sidebar a:focus,
    body.recruiter-jobboard .hm-sidebar a:active,
    body.recruiter-jobboard .hm-sidebar a.active,
    body.recruiter-jobboard .hm-sidebar button {
        color: inherit !important;
        background-color: transparent;
    }
    body.recruiter-jobboard .hm-sidebar *:focus { outline: none; }

    /* ═══════════════════════════════════════════
       BODY & LAYOUT
    ═══════════════════════════════════════════ */
    body.recruiter-jobboard {
        margin: 0; padding: 0;
        background: var(--hm-surface-grad);
    }

    body.dark.recruiter-jobboard { 
        --hm-bg: #000000 !important;
        --hm-bg-2: #000000 !important;
        --hm-surface-grad: #000000 !important;
        --hm-card: #000000 !important;
        --hm-text: #FFFFFF !important; 
        --hm-muted: #94A3B8;
        --hm-light: #7A8B96;
        --hm-border: #23343A;
        --hm-active-bg: #162327;
        --hm-hover-bg: #151D21;
    }

    /* ═══════════════════════════════════════════════════
       SHARED TYPOGRAPHY
       Align recruiter pages with candidate page rhythm
    ═══════════════════════════════════════════════════ */
    body.recruiter-jobboard,
    body.recruiter-jobboard button,
    body.recruiter-jobboard input,
    body.recruiter-jobboard select,
    body.recruiter-jobboard textarea,
    body.recruiter-jobboard table,
    body.recruiter-jobboard th,
    body.recruiter-jobboard td {
        font-family: var(--portal-font-family, "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif) !important;
        letter-spacing: 0 !important;
    }

    body.recruiter-jobboard h1,
    body.recruiter-jobboard h2,
    body.recruiter-jobboard h3,
    body.recruiter-jobboard h4,
    body.recruiter-jobboard h5,
    body.recruiter-jobboard h6,
    body.recruiter-jobboard .page-board-title,
    body.recruiter-jobboard .section-title,
    body.recruiter-jobboard .card-header h5,
    body.recruiter-jobboard .card-header h6,
    body.recruiter-jobboard .modal-title,
    body.recruiter-jobboard .dashboard-metric-title {
        font-family: var(--portal-font-family, "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif) !important;
        letter-spacing: 0 !important;
        text-transform: none;
    }

    body.recruiter-jobboard .page-board-title,
    body.recruiter-jobboard h1 {
        font-size: 1.85rem !important;
        line-height: 1.2 !important;
        font-weight: 750 !important;
    }

    body.recruiter-jobboard .section-title,
    body.recruiter-jobboard h2 {
        font-size: 1.45rem !important;
        line-height: 1.25 !important;
        font-weight: 720 !important;
    }

    body.recruiter-jobboard .card-header h5,
    body.recruiter-jobboard .card-header h6,
    body.recruiter-jobboard h3,
    body.recruiter-jobboard .modal-title {
        font-size: 1rem !important;
        line-height: 1.35 !important;
        font-weight: 700 !important;
    }

    body.recruiter-jobboard .page-board-subtitle,
    body.recruiter-jobboard .text-muted,
    body.recruiter-jobboard small,
    body.recruiter-jobboard p,
    body.recruiter-jobboard li,
    body.recruiter-jobboard td,
    body.recruiter-jobboard .form-control,
    body.recruiter-jobboard .btn {
        line-height: 1.55 !important;
        letter-spacing: 0 !important;
    }

    body.recruiter-jobboard .page-board-subtitle,
    body.recruiter-jobboard .text-muted,
    body.recruiter-jobboard small {
        font-size: 0.94rem !important;
    }

    body.recruiter-jobboard .page-board-kicker,
    body.recruiter-jobboard .section-eyebrow,
    body.recruiter-jobboard .settings-side-title,
    body.recruiter-jobboard .hm-sb-section {
        font-size: 0.75rem !important;
        line-height: 1.2 !important;
        font-weight: 750 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.06em !important;
    }

    body.recruiter-jobboard label,
    body.recruiter-jobboard .recruiter-summary-label,
    body.recruiter-jobboard .recruiter-info-label,
    body.recruiter-jobboard .dashboard-metric-title,
    body.recruiter-jobboard .status-pill,
    body.recruiter-jobboard .badge,
    body.recruiter-jobboard .btn,
    body.recruiter-jobboard .custom-control-label,
    body.recruiter-jobboard .action-item-label,
    body.recruiter-jobboard .hm-sb-item .sb-label,
    body.recruiter-jobboard .card-header .font-weight-bold {
        text-transform: none !important;
        letter-spacing: 0 !important;
    }

    body.recruiter-jobboard .recruiter-dashboard-main .recruiter-stat-card .dashboard-metric-title,
    body.recruiter-jobboard .recruiter-dashboard-main .recruiter-stat-card .text-primary.dashboard-metric-title,
    body.recruiter-jobboard .recruiter-dashboard-main .recruiter-stat-card .text-info.dashboard-metric-title,
    body.recruiter-jobboard .recruiter-dashboard-main .recruiter-stat-card .text-warning.dashboard-metric-title,
    body.recruiter-jobboard .recruiter-dashboard-main .recruiter-stat-card .text-success.dashboard-metric-title {
        text-transform: none !important;
        letter-spacing: 0 !important;
    }

    @media (max-width: 767.98px) {
        body.recruiter-jobboard .page-board-title,
        body.recruiter-jobboard h1 {
            font-size: 1.55rem !important;
        }

        body.recruiter-jobboard .section-title,
        body.recruiter-jobboard h2 {
            font-size: 1.28rem !important;
        }
    }

    /* ═══════════════════════════════════════════════════
       SHARED SURFACES & CONTROLS
       Keep recruiter pages visually consistent
    ═══════════════════════════════════════════════════ */
    body.recruiter-jobboard .card,
    body.recruiter-jobboard .dashboard-panel,
    body.recruiter-jobboard .recruiter-dashboard-panel-card,
    body.recruiter-jobboard .recruiter-table-card,
    body.recruiter-jobboard .recruiter-filter-card,
    body.recruiter-jobboard .recruiter-form-card,
    body.recruiter-jobboard .recruiter-help-card,
    body.recruiter-jobboard .recruiter-info-card,
    body.recruiter-jobboard .recruiter-review-card,
    body.recruiter-jobboard .recruiter-review-summary-card,
    body.recruiter-jobboard .recruiter-alert,
    body.recruiter-jobboard .recruiter-notification-card,
    body.recruiter-jobboard .recruiter-notification-empty,
    body.recruiter-jobboard .recruiter-action-center-empty,
    body.recruiter-jobboard .table-responsive,
    body.recruiter-jobboard .table-responsive-wrap {
        background: var(--hm-card) !important;
        border-color: var(--hm-border) !important;
        box-shadow: none !important;
    }

    body.recruiter-jobboard .page-board-header,
    body.recruiter-jobboard .page-board-header.page-board-header-tight,
    body.recruiter-jobboard .recruiter-page-board-header {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }

    body.recruiter-jobboard .card,
    body.recruiter-jobboard .dashboard-panel,
    body.recruiter-jobboard .recruiter-dashboard-panel-card,
    body.recruiter-jobboard .recruiter-table-card,
    body.recruiter-jobboard .recruiter-filter-card,
    body.recruiter-jobboard .recruiter-form-card,
    body.recruiter-jobboard .recruiter-help-card,
    body.recruiter-jobboard .recruiter-info-card,
    body.recruiter-jobboard .recruiter-review-card,
    body.recruiter-jobboard .recruiter-review-summary-card,
    body.recruiter-jobboard .recruiter-notification-card,
    body.recruiter-jobboard .recruiter-notification-empty {
        border: 1px solid var(--hm-border) !important;
    }

    body.recruiter-jobboard .card-header,
    body.recruiter-jobboard .card-footer,
    body.recruiter-jobboard .recruiter-section-header,
    body.recruiter-jobboard .thead-light th,
    body.recruiter-jobboard .table thead th {
        background: var(--hm-card) !important;
        border-color: var(--hm-border) !important;
        color: var(--hm-muted) !important;
        font-size: 12.5px !important;
        font-weight: 700 !important;
        line-height: 1.3 !important;
        letter-spacing: 0.04em !important;
    }

    body.recruiter-jobboard .card-body,
    body.recruiter-jobboard .card-footer,
    body.recruiter-jobboard .modal-body,
    body.recruiter-jobboard .modal-footer {
        background: transparent !important;
    }

    body.recruiter-jobboard .table th,
    body.recruiter-jobboard .table td,
    body.recruiter-jobboard tbody tr,
    body.recruiter-jobboard tbody td {
        border-color: var(--hm-border) !important;
    }

    body.recruiter-jobboard .table td,
    body.recruiter-jobboard .table tbody td,
    body.recruiter-jobboard p,
    body.recruiter-jobboard li { 
        /* color: #FFFFFF !important;  */
        font-size: 13.5px !important;
        line-height: 1.45 !important;
    }

    body.recruiter-jobboard label,
    body.recruiter-jobboard .custom-control-label,
    body.recruiter-jobboard th,
    body.recruiter-jobboard .card-header .font-weight-bold {
        color: var(--hm-muted) !important;
    }

    body.recruiter-jobboard .form-control,
    body.recruiter-jobboard .custom-select,
    body.recruiter-jobboard select,
    body.recruiter-jobboard textarea,
    body.recruiter-jobboard input[type="text"],
    body.recruiter-jobboard input[type="email"],
    body.recruiter-jobboard input[type="number"],
    body.recruiter-jobboard input[type="date"],
    body.recruiter-jobboard input[type="time"] {
        background: var(--hm-card) !important;
        color: var(--hm-text) !important;
        border: 1px solid var(--hm-border) !important;
        box-shadow: none !important;
    }

    body.recruiter-jobboard .form-control::placeholder,
    body.recruiter-jobboard textarea::placeholder,
    body.recruiter-jobboard input::placeholder {
        color: var(--hm-light) !important;
    }

    body.recruiter-jobboard .form-control:focus,
    body.recruiter-jobboard .custom-select:focus,
    body.recruiter-jobboard select:focus,
    body.recruiter-jobboard textarea:focus,
    body.recruiter-jobboard input:focus {
        border-color: var(--hm-primary-dark) !important;
        box-shadow: none !important;
        outline: none !important;
    }

    body.recruiter-jobboard select option,
    body.recruiter-jobboard .custom-select option {
        background: var(--hm-card) !important;
        color: var(--hm-text) !important;
    }
 
    body.recruiter-jobboard .btn-primary,
    body.recruiter-jobboard .btn-outline-primary {
        background: transparent !important;
        color: var(--hm-primary) !important;
        border: 1.5px solid var(--hm-primary) !important;
        box-shadow: none !important;
    }

    body.recruiter-jobboard .btn-primary:hover,
    body.recruiter-jobboard .btn-primary:focus,
    body.recruiter-jobboard .btn-outline-primary:hover,
    body.recruiter-jobboard .btn-outline-primary:focus {
        background: var(--hm-primary) !important;
        color: #FFFFFF !important;
        border-color: var(--hm-primary) !important;
        box-shadow: none !important;
    }

    body.recruiter-jobboard .btn-outline-secondary {
        background: transparent !important;
        color: var(--hm-muted) !important;
        border: 1px solid var(--hm-border) !important;
        box-shadow: none !important;
    }

    body.recruiter-jobboard .btn-outline-secondary:hover,
    body.recruiter-jobboard .btn-outline-secondary:focus {
        background: var(--hm-hover-bg) !important;
        color: var(--hm-text) !important;
        border-color: var(--hm-primary) !important;
    }

    body.recruiter-jobboard .btn-link {
        color: var(--hm-primary) !important;
        text-decoration: none !important;
        box-shadow: none !important;
    }

    body.recruiter-jobboard .btn-link:hover,
    body.recruiter-jobboard .btn-link:focus {
        color: var(--hm-primary-dark) !important;
        text-decoration: none !important;
    }

    body.recruiter-jobboard .portal-pagination-wrap,
    body.recruiter-jobboard .pagination-wrap {
        align-items: center !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 12px !important;
        justify-content: space-between !important;
        margin-top: 18px !important;
        padding: 14px 0 0 !important;
    }

    body.recruiter-jobboard .portal-pagination-meta span,
    body.recruiter-jobboard .pagination-wrap span {
        color: var(--hm-muted) !important;
        font-size: 13px !important;
        font-weight: 700 !important;
    }

    body.recruiter-jobboard .portal-pagination,
    body.recruiter-jobboard .custom-pagination {
        align-items: center !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
        justify-content: flex-end !important;
        margin-left: auto !important;
    }

    body.recruiter-jobboard .portal-pagination a,
    body.recruiter-jobboard .custom-pagination a {
        align-items: center !important;
        background: var(--hm-card) !important;
        border: 1.5px solid rgba(31, 183, 181, 0.38) !important;
        border-radius: 6px !important;
        color: var(--hm-primary) !important;
        display: inline-flex !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        height: 34px !important;
        justify-content: center !important;
        line-height: 1 !important;
        min-width: 34px !important;
        padding: 0 11px !important;
        text-decoration: none !important;
        transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease !important;
    }

    body.recruiter-jobboard .portal-pagination a.prev,
    body.recruiter-jobboard .portal-pagination a.next,
    body.recruiter-jobboard .custom-pagination a.prev,
    body.recruiter-jobboard .custom-pagination a.next {
        min-width: 64px !important;
    }

    body.recruiter-jobboard .portal-pagination a.active,
    body.recruiter-jobboard .portal-pagination a:hover,
    body.recruiter-jobboard .portal-pagination a:focus,
    body.recruiter-jobboard .custom-pagination a.active,
    body.recruiter-jobboard .custom-pagination a:hover,
    body.recruiter-jobboard .custom-pagination a:focus {
        background: var(--hm-primary) !important;
        border-color: var(--hm-primary) !important;
        color: #ffffff !important;
        outline: none !important;
        box-shadow: none !important;
        transform: none !important;
    }

    body.dark.recruiter-jobboard .portal-pagination a,
    body.dark.recruiter-jobboard .custom-pagination a {
        background: #162327 !important;
        border-color: rgba(45, 212, 191, 0.36) !important;
        color: #7dd3fc !important;
    }

    body.dark.recruiter-jobboard .portal-pagination a.active,
    body.dark.recruiter-jobboard .portal-pagination a:hover,
    body.dark.recruiter-jobboard .portal-pagination a:focus,
    body.dark.recruiter-jobboard .custom-pagination a.active,
    body.dark.recruiter-jobboard .custom-pagination a:hover,
    body.dark.recruiter-jobboard .custom-pagination a:focus {
        background: var(--hm-primary) !important;
        border-color: var(--hm-primary) !important;
        color: #ffffff !important;
    }

    @media (max-width: 767.98px) {
        body.recruiter-jobboard .portal-pagination-wrap,
        body.recruiter-jobboard .pagination-wrap {
            align-items: stretch !important;
            flex-direction: column !important;
            text-align: center !important;
        }

        body.recruiter-jobboard .portal-pagination,
        body.recruiter-jobboard .custom-pagination {
            justify-content: center !important;
            margin-left: 0 !important;
        }
    }

    body.recruiter-jobboard .text-danger,
    body.recruiter-jobboard .btn-link.text-danger,
    body.recruiter-jobboard a.text-danger {
        color: #EF4444 !important;
    }

    body.recruiter-jobboard .status-pill,
    body.recruiter-jobboard .badge-primary,
    body.recruiter-jobboard .badge-info {
        background: rgba(31, 183, 181, 0.12) !important;
        color: var(--hm-primary-dark) !important;
        border: 1px solid rgba(31, 183, 181, 0.18) !important;
    }

    body.recruiter-jobboard .badge,
    body.recruiter-jobboard .status-pill {
        border-radius: 999px !important;
        box-shadow: none !important;
    }

    body.recruiter-jobboard .badge-secondary,
    body.recruiter-jobboard .badge-light {
        background: rgba(22, 33, 43, 0.06) !important;
        color: var(--hm-muted) !important;
        border: 1px solid var(--hm-border) !important;
    }

    body.recruiter-jobboard .badge-warning {
        background: rgba(245, 158, 11, 0.12) !important;
        color: #B45309 !important;
        border: 1px solid rgba(245, 158, 11, 0.2) !important;
    }

    body.recruiter-jobboard .badge-danger {
        background: rgba(239, 68, 68, 0.12) !important;
        color: #DC2626 !important;
        border: 1px solid rgba(239, 68, 68, 0.2) !important;
    }

    body.recruiter-jobboard .bootstrap-select {
        width: 100% !important;
    }

    body.recruiter-jobboard .bootstrap-select.form-control,
    body.recruiter-jobboard .bootstrap-select.custom-select {
        height: auto !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    body.recruiter-jobboard .bootstrap-select > .dropdown-toggle {
        min-height: 46px;
        background: var(--hm-card) !important;
        color: var(--hm-text) !important;
        border: 1px solid var(--hm-border) !important;
        border-radius: 6px !important;
        box-shadow: none !important;
        padding: 0.7rem 2.25rem 0.7rem 1rem !important;
        font-weight: 400 !important;
    }

    body.recruiter-jobboard .bootstrap-select > .dropdown-toggle:focus,
    body.recruiter-jobboard .bootstrap-select > .dropdown-toggle:hover,
    body.recruiter-jobboard .bootstrap-select.show > .dropdown-toggle {
        background: var(--hm-card) !important;
        color: var(--hm-text) !important;
        border-color: var(--hm-primary-dark) !important;
        box-shadow: none !important;
        outline: none !important;
    }

    body.recruiter-jobboard .bootstrap-select .filter-option,
    body.recruiter-jobboard .bootstrap-select .filter-option-inner,
    body.recruiter-jobboard .bootstrap-select .filter-option-inner-inner {
        color: inherit !important;
        line-height: 1.4 !important;
        font-weight: 400 !important;
    }

    body.recruiter-jobboard .bootstrap-select > .dropdown-toggle.bs-placeholder,
    body.recruiter-jobboard .bootstrap-select > .dropdown-toggle.bs-placeholder .filter-option-inner-inner {
        color: var(--hm-light) !important;
        font-weight: 400 !important;
    }

    body.recruiter-jobboard .bootstrap-select .bs-caret,
    body.recruiter-jobboard .bootstrap-select .caret {
        color: var(--hm-muted) !important;
    }

    body.recruiter-jobboard .bootstrap-select .dropdown-menu {
        background: var(--hm-card) !important;
        border: 1px solid var(--hm-border) !important;
        border-radius: 6px !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin-top: 2px !important;
        overflow: hidden !important;
    }

    body.recruiter-jobboard .bootstrap-select .dropdown-menu .inner {
        background: transparent !important;
    }

    body.recruiter-jobboard .bootstrap-select .dropdown-item,
    body.recruiter-jobboard .bootstrap-select .dropdown-menu li a,
    body.recruiter-jobboard .bootstrap-select .dropdown-menu li a span.text {
        color: var(--hm-text) !important;
        background: transparent !important;
    }

    body.recruiter-jobboard .bootstrap-select .dropdown-item,
    body.recruiter-jobboard .bootstrap-select .dropdown-menu li a {
        padding: 0.7rem 1rem !important;
        font-weight: 400 !important;
        line-height: 1.4 !important;
        border: 0 !important;
    }

    body.recruiter-jobboard .bootstrap-select .dropdown-item:hover,
    body.recruiter-jobboard .bootstrap-select .dropdown-item:focus,
    body.recruiter-jobboard .bootstrap-select .dropdown-menu li a:hover,
    body.recruiter-jobboard .bootstrap-select .dropdown-menu li a:focus {
        background: rgba(31, 183, 181, 0.1) !important;
        color: var(--hm-primary-dark) !important;
    }
 
    body.recruiter-jobboard .site-navbar,
    body.recruiter-jobboard .site-mobile-menu { display: none !important; }
    body.recruiter-jobboard .site-wrap {
        display: flex;
        min-height: 100vh;
        margin: 0; padding: 0;
    }

    /* ═══════════════════════════════════════════
       SIDEBAR SHELL
    ═══════════════════════════════════════════ */
    .hm-sidebar {
        position: fixed;
        top: 0; left: 0; bottom: 0;
        width: var(--sb-width);
        background: var(--hm-surface-grad);
        border-right: 1.5px solid var(--hm-border);
        display: flex;
        flex-direction: column;
        z-index: 1050;
        transition: width .28s cubic-bezier(.4,0,.2,1);
        overflow: hidden;
    }
    .hm-sidebar.sb-collapsed { width: var(--sb-width-mini); }

    /* ── HEAD (logo + brand + toggle) ── */
    .hm-sb-head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 12px 0 10px;
        height: var(--topbar-h);
        border-bottom: 1.5px solid var(--hm-border);
        flex-shrink: 0;
        cursor: pointer;
        user-select: none;
        transition: background .15s;
    }
    .hm-sb-head:hover { background: rgba(31,183,181,.07); }

    .hm-sb-logo {
        width: 42px; height: 42px !important;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        transition: transform .2s;
    } 
    .hm-sb-logo img { height: 30px !important; width: auto; display: block; }

    .hm-sb-brand {
        font-size: 19px;
        font-weight: 800;
        color: var(--hm-text);
        white-space: nowrap;
        overflow: hidden;
        transition: opacity .2s, max-width .28s cubic-bezier(.4,0,.2,1);
        max-width: 160px;
        letter-spacing: -.3px;
    }
    .hm-sidebar.sb-collapsed .hm-sb-brand { opacity: 0; max-width: 0; }

    .hm-sb-chevron {
        margin-left: auto;
        flex-shrink: 0;
        color: var(--hm-light);
        transition: transform .28s cubic-bezier(.4,0,.2,1), opacity .2s;
        font-size: 13px;
    }
    .hm-sidebar.sb-collapsed .hm-sb-chevron { transform: rotate(180deg); opacity: 0; }

    /* ── NAV BODY ── */
    .hm-sb-body {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 8px 0 4px;
        scrollbar-width: thin;
        scrollbar-color: var(--hm-border) transparent;
    }
    .hm-sb-body::-webkit-scrollbar { width: 3px; }
    .hm-sb-body::-webkit-scrollbar-thumb { background: var(--hm-border); border-radius: 3px; }

    /* section labels */
    .hm-sb-section {
        padding: 12px 16px 4px;
        font-size: 10.5px;
        font-weight: 700;
        color: var(--hm-light);
        text-transform: uppercase;
        letter-spacing: .8px;
        white-space: nowrap;
        overflow: hidden;
        transition: opacity .2s, max-height .2s;
        max-height: 40px;
    }
    .hm-sidebar.sb-collapsed .hm-sb-section {
        opacity: 0; max-height: 0; padding-top: 0; padding-bottom: 0;
    }

    /* ── NAV ITEM ── high specificity to beat global theme */
    body.recruiter-jobboard .hm-sidebar .hm-sb-item {
        display: flex !important;
        align-items: center !important;
        gap: 11px !important;
        padding: 10px 13px !important;
        margin: 2px 8px !important;
        border-radius: 10px !important;
         color: var(--hm-text) !important;
        font-size: 14.5px !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        cursor: pointer !important;
        white-space: nowrap !important;
        position: relative !important;
        transition: background .15s, color .15s !important;
        border: none !important;
        background: transparent !important;
        width: calc(100% - 16px) !important;
        text-align: left !important;
        line-height: 1.4 !important;
        box-shadow: none !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-item:hover {
        background: var(--hm-hover-bg) !important;
        color: var(--hm-text) !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-item.active {
        background: var(--hm-active-bg) !important;
        color: var(--hm-primary-dark) !important;
        font-weight: 600 !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-item .sb-icon {
        font-size: 17px !important;
        width: 21px !important;
        text-align: center !important;
        flex-shrink: 0 !important;
         color: var(--hm-text) !important;
        line-height: 1 !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-item.active .sb-icon {
        color: var(--hm-primary-dark) !important;
    }
    /* active left accent bar */
    body.recruiter-jobboard .hm-sidebar .hm-sb-item.active::before {
       display:none;
    }
    /* label */
    body.recruiter-jobboard .hm-sidebar .hm-sb-item .sb-label {
        flex: 1 !important;
        min-width: 0 !important;
        overflow: hidden !important;
        transition: opacity .2s, max-width .28s cubic-bezier(.4,0,.2,1) !important;
        max-width: 160px !important;
        font-size: 14.5px !important;
         color: var(--hm-text) !important;
    }
    .hm-sidebar.sb-collapsed .hm-sb-item .sb-label { opacity: 0; max-width: 0; }

    /* dropdown arrow */
    .hm-sb-item .sb-arrow {
        font-size: 11px; flex-shrink: 0;
        transition: transform .22s, opacity .2s, max-width .28s;
        max-width: 20px; overflow: hidden;
        color: var(--hm-light);
    }
    .hm-sb-item.sb-open .sb-arrow { transform: rotate(180deg); }
    .hm-sidebar.sb-collapsed .hm-sb-item .sb-arrow { opacity: 0; max-width: 0; }

    /* ── SUB-MENU ── */
    .hm-sb-sub { max-height: 0; overflow: hidden; transition: max-height .28s ease; }
    .hm-sb-sub.sb-open { max-height: 200px; }
    .hm-sidebar.sb-collapsed .hm-sb-sub { display: none; }

    body.recruiter-jobboard .hm-sidebar .hm-sb-subitem {
        display: flex !important;
        align-items: center !important;
        padding: 8px 13px 8px 48px !important;
        margin: 1px 8px !important;
        border-radius: 8px !important;
        color: var(--hm-muted) !important;
        font-size: 13.5px !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        transition: background .15s, color .15s !important;
        position: relative !important;
        background: transparent !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-subitem::before {
        display:none;
        content: '';
        position: absolute;
        left: 29px;
        width: 5px; height: 5px;
        border-radius: 50%;
        background: currentColor;
        opacity: .35;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-subitem:hover {
        background: var(--hm-hover-bg) !important;
        color: var(--hm-text) !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-subitem.active {
        color: var(--hm-primary-dark) !important;
        font-weight: 600 !important;
        background: var(--hm-active-bg) !important;
    }

    /* ── TOOLTIP (mini mode) ── */
    .sb-tooltip {
        display: none;
        position: absolute;
        left: calc(var(--sb-width-mini) + 8px);
        top: 50%; transform: translateY(-50%);
        background: #1e293b;
        color: #fff;
        font-size: 12.5px; font-weight: 500;
        padding: 5px 11px;
        border-radius: 7px;
        white-space: nowrap;
        pointer-events: none;
        z-index: 2000;
        box-shadow: 0 2px 8px rgba(0,0,0,.18);
    }
    .sb-tooltip::before {
        content: '';
        position: absolute;
        right: 100%; top: 50%; transform: translateY(-50%);
        border: 5px solid transparent;
        border-right-color: #1e293b;
    }
    .hm-sidebar.sb-collapsed .hm-sb-item:hover .sb-tooltip { display: block; }

    /* ── NOTIFICATION BADGE ── */
    .sb-badge {
        margin-left: auto;
        min-width: 18px; height: 18px;
        background: var(--hm-primary);
        border-radius: 9px;
        font-size: 10.5px; font-weight: 700;
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        padding: 0 5px;
        flex-shrink: 0;
        transition: opacity .2s;
    }
    .hm-sidebar.sb-collapsed .sb-badge { opacity: 0; }

    /* ═══════════════════════════════════════════
       SIDEBAR FOOTER — PROFILE CARD
    ═══════════════════════════════════════════ */
    .hm-sb-foot { flex-shrink: 0; }

    /* profile submenu — sits above the card */
    .hm-sb-profile-sub {
        max-height: 0;
        overflow: hidden;
        transition: max-height .32s ease;
        background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
        border-top: 1.5px solid var(--hm-border);
    }
    .hm-sb-profile-sub.prof-open { max-height: 240px; }
    .hm-sidebar.sb-collapsed .hm-sb-profile-sub { display: none; }

    body.recruiter-jobboard .hm-sidebar .hm-sb-profile-sub a {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 8px 14px 8px 16px !important;
        margin: 2px 6px !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        color: var(--hm-muted) !important;
        text-decoration: none !important;
        transition: background .15s, color .15s !important;
        background: transparent !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-profile-sub a:hover {
        background: var(--hm-hover-bg) !important;
        color: var(--hm-text) !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-profile-sub a.active {
        color: var(--hm-primary-dark) !important;
        font-weight: 600 !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-profile-sub a i {
        width: 17px; text-align: center;
        font-size: 13.5px; flex-shrink: 0;
        color: var(--hm-light) !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-profile-sub a:hover i,
    body.recruiter-jobboard .hm-sidebar .hm-sb-profile-sub a.active i {
        color: var(--hm-primary-dark) !important;
    }

    /* divider */
    .hm-sb-profile-sub .prof-sub-divider {
        height: 1px; background: var(--hm-border); margin: 3px 12px;
    }

    /* logout row */
    body.recruiter-jobboard .hm-sidebar .hm-sb-profile-sub a.prof-logout {
        color: #dc2626 !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-profile-sub a.prof-logout i {
        color: #dc2626 !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-profile-sub a.prof-logout:hover {
        background: #fff1f1 !important;
        color: #b91c1c !important;
    }
    body.recruiter-jobboard .hm-sidebar .hm-sb-profile-sub a.prof-logout:hover i {
        color: #b91c1c !important;
    }

    /* translate row */
    .hm-sb-translate-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 7px 14px 7px 16px;
        margin: 2px 6px;
        border-radius: 8px;
    }
    .hm-sb-translate-row > i {
        width: 17px; text-align: center;
        font-size: 13.5px; flex-shrink: 0;
        color: var(--hm-light);
    }
    .hm-sb-translate-row .translate-widget-wrap {
        flex: 1; min-width: 0;
        overflow: hidden; height: 30px; position: relative;
    }
    .hm-sb-translate-row .goog-te-gadget > span,
    .hm-sb-translate-row .goog-te-gadget img,
    .hm-sb-translate-row .goog-logo-link { display: none !important; }
    .hm-sb-translate-row .goog-te-gadget { font-size: 0 !important; }
    .hm-sb-translate-row .goog-te-combo,
    .hm-sb-translate-row select {
        font-size: 13px !important;
        font-family: inherit !important;
        width: 100% !important;
        height: 30px !important;
        border-radius: 7px !important;
        border: 1px solid var(--hm-border) !important;
        padding: 0 7px !important;
        color: var(--hm-text) !important;
        background: #fff !important;
        cursor: pointer !important;
        outline: none !important;
        box-shadow: none !important;
    }
    .hm-sb-translate-row .goog-te-combo:hover,
    .hm-sb-translate-row select:hover {
        border-color: var(--hm-primary) !important;
    }

    /* profile card — the clickable row at very bottom */
    .hm-sb-profile {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 11px 13px;
        border-top: 1.5px solid var(--hm-border);
        flex-shrink: 0;
        overflow: hidden;
        background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
        position: relative;
        cursor: pointer;
        transition: background .15s;
    } 

    /* avatar circle — brand gradient with white initial */
    .hm-sb-profile-avatar {
        width: 38px; height: 38px;
        border-radius: 50%;
        background: var(--hm-brand-grad);
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; font-weight: 700; color: #fff;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(31,183,181,.35);
        transition: transform .2s;
        letter-spacing: 0;
    } 

    .hm-sb-profile-info {
        flex: 1; min-width: 0; overflow: hidden;
        transition: opacity .2s, max-width .28s cubic-bezier(.4,0,.2,1);
        max-width: 155px;
    }
    .hm-sb-profile-name {
        font-size: 13.5px; font-weight: 700;
        color: var(--hm-text);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .hm-sb-profile-role {
        font-size: 11.5px; color: var(--hm-muted);
        margin-top: 1px; white-space: nowrap;
    }
    .hm-sidebar.sb-collapsed .hm-sb-profile-info { opacity: 0; max-width: 0; }

    .hm-sb-profile-chevron {
        flex-shrink: 0;
        color: var(--hm-light);
        font-size: 12px;
        transition: transform .22s, opacity .2s, max-width .28s;
        max-width: 20px; overflow: hidden;
    }
    .hm-sb-profile.prof-open .hm-sb-profile-chevron { transform: rotate(180deg); }
    .hm-sidebar.sb-collapsed .hm-sb-profile-chevron { opacity: 0; max-width: 0; }

    /* profile tooltip (mini mode) */
    .hm-sb-profile .sb-tooltip {
        display: none;
        position: absolute;
        left: calc(var(--sb-width-mini) + 8px);
        top: 50%; transform: translateY(-50%);
        background: #1e293b; color: #fff;
        font-size: 12.5px; font-weight: 500;
        padding: 5px 11px; border-radius: 7px;
        white-space: nowrap; pointer-events: none;
        z-index: 2000; box-shadow: 0 2px 8px rgba(0,0,0,.18);
    }
    .hm-sb-profile .sb-tooltip::before {
        content: '';
        position: absolute;
        right: 100%; top: 50%; transform: translateY(-50%);
        border: 5px solid transparent;
        border-right-color: #1e293b;
    }
    .hm-sidebar.sb-collapsed .hm-sb-profile:hover .sb-tooltip { display: block; }

    /* ═══════════════════════════════════════════
       MAIN CONTENT AREA
    ═══════════════════════════════════════════ */
    .hm-main {
        margin-left: var(--sb-width);
        flex: 1; display: flex; flex-direction: column;
        min-width: 0; min-height: 100vh;
        transition: margin-left .28s cubic-bezier(.4,0,.2,1);
    }
    body.recruiter-jobboard.sb-collapsed-body .hm-main {
        margin-left: var(--sb-width-mini);
    }

    /* ── TOPBAR ── */
    .hm-topbar {
        height: var(--topbar-h);
        background: rgba(255,255,255,.75);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-bottom: 1.5px solid var(--hm-border);
        display: flex; align-items: center;
        padding: 0 24px; gap: 12px;
        position: sticky; top: 0; z-index: 100; flex-shrink: 0;
    }
    .hm-topbar-title {
        font-size: 15.5px; font-weight: 600; color: var(--hm-text);
    }
    .hm-topbar-right {
        margin-left: auto; display: flex; align-items: center; gap: 6px;
    }
    .hm-tb-btn {
        width: 38px; height: 38px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        color: var(--hm-muted); text-decoration: none;
        background: none; border: none; cursor: pointer;
        position: relative; transition: background .15s; font-size: 17px;
    }
    .hm-tb-btn:hover { background: var(--hm-hover-bg); color: var(--hm-text); }
    .hm-notif-dot {
        position: absolute; top: 7px; right: 7px;
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--hm-primary); border: 2px solid #fff;
    }
    .hm-notif-badge {
        position: absolute; top: 4px; right: 4px;
        min-width: 16px; height: 16px;
        background: var(--hm-primary); border-radius: 8px;
        font-size: 9.5px; font-weight: 700; color: #fff;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid #fff; padding: 0 3px;
    }

    /* mobile toggle */
    .hm-mobile-toggle {
        display: none; background: none; border: none;
        cursor: pointer; color: var(--hm-muted);
        font-size: 21px; padding: 4px; align-items: center;
    }

    /* overlay */
    .hm-sb-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.28); z-index: 1040;
        backdrop-filter: blur(2px);
    }
    .hm-sb-overlay.show { display: block; }

    .hm-page-content { flex: 1; padding: 0; }
    body.recruiter-jobboard .recruiter-global-hero { margin: 0; }

    /* ═══════════════════════════════════════════
       RESPONSIVE
    ═══════════════════════════════════════════ */
    @media (max-width: 991px) {
        .hm-sidebar {
            transform: translateX(-100%);
            width: var(--sb-width) !important;
            transition: transform .28s cubic-bezier(.4,0,.2,1);
        }
        .hm-sidebar.sb-mobile-open { transform: translateX(0); }
        .hm-sidebar .hm-sb-brand,
        .hm-sidebar .sb-label,
        .hm-sidebar .sb-arrow,
        .hm-sidebar .hm-sb-section,
        .hm-sidebar .hm-sb-chevron,
        .hm-sidebar .sb-badge,
        .hm-sidebar .hm-sb-profile-info,
        .hm-sidebar .hm-sb-profile-chevron {
            opacity: 1 !important; max-width: none !important; max-height: none !important;
        }
        .hm-sidebar .hm-sb-section { padding: 12px 16px 4px !important; }
        .hm-main { margin-left: 0 !important; }
        .hm-mobile-toggle { display: flex !important; }
    }
    /* ═══════════════════════════════════════════
   DARK THEME — SIDEBAR
═══════════════════════════════════════════ */

:root.dark .hm-sidebar,
body.dark .hm-sidebar {
  background: #000000 !important;
    border-right-color: #23343A;
}

/* ── Head (logo + brand row) ── */
:root.dark .hm-sb-head,
body.dark .hm-sb-head {
    border-bottom-color: #23343A;
}
:root.dark .hm-sb-head:hover,
body.dark .hm-sb-head:hover {
    background: rgba(31, 183, 181, 0.08);
}
:root.dark .hm-sb-brand,
body.dark .hm-sb-brand {
    color: #F8FAFC;
}
:root.dark .hm-sb-chevron,
body.dark .hm-sb-chevron {
    color: #7A8B96;
}

/* ── Section labels ── */
:root.dark .hm-sb-section,
body.dark .hm-sb-section {
    color: #7A8B96;
}

/* ── Nav items ── */
body.dark .hm-sidebar .hm-sb-item {
    color: #94A3B8 !important;
}
body.dark .hm-sidebar .hm-sb-item:hover {
    background: rgba(31, 183, 181, 0.1) !important;
    color: #F8FAFC !important;
}
body.dark .hm-sidebar .hm-sb-item.active {
    background: rgba(13, 138, 144, 0.2) !important;
    color: #1FB7B5 !important;
}
body.dark .hm-sidebar .hm-sb-item.active .sb-icon {
    color: #1FB7B5 !important;
}
body.dark .hm-sidebar .hm-sb-item.active::before {
   display:none;
}
body.dark .hm-sidebar .hm-sb-item .sb-arrow {
    color: #7A8B96;
}

/* ── Notification badge ── */
body.dark .hm-sidebar .sb-badge {
    background: #1FB7B5;
}

/* ── Sub-items ── */
body.dark .hm-sidebar .hm-sb-subitem {
    color: #94A3B8 !important;
}
body.dark .hm-sidebar .hm-sb-subitem:hover {
    background: rgba(31, 183, 181, 0.1) !important;
    color: #F8FAFC !important;
}
body.dark .hm-sidebar .hm-sb-subitem.active {
    background: rgba(13, 138, 144, 0.2) !important;
    color: #1FB7B5 !important;
    font-weight: 600 !important;
}

/* ── Tooltip (mini mode) ── */
body.dark .hm-sidebar .sb-tooltip {
    background: #0E1619;
    color: #F8FAFC;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}
body.dark .hm-sidebar .sb-tooltip::before {
    border-right-color: #0E1619;
}

/* ── Profile submenu ── */
body.dark .hm-sidebar .hm-sb-profile-sub {
  background: #000000 !important;
    border-top-color: #23343A;
}
body.dark .hm-sidebar .hm-sb-profile-sub a {
    color: #94A3B8 !important;
}
body.dark .hm-sidebar .hm-sb-profile-sub a:hover {
    background: rgba(31, 183, 181, 0.1) !important;
    color: #F8FAFC !important;
}
body.dark .hm-sidebar .hm-sb-profile-sub a.active {
    color: #1FB7B5 !important;
}
body.dark .hm-sidebar .hm-sb-profile-sub a i {
    color: #7A8B96 !important;
}
body.dark .hm-sidebar .hm-sb-profile-sub a:hover i,
body.dark .hm-sidebar .hm-sb-profile-sub a.active i {
    color: #1FB7B5 !important;
}
body.dark .hm-sidebar .hm-sb-profile-sub .prof-sub-divider {
    background: #23343A;
}

/* logout */
body.dark .hm-sidebar .hm-sb-profile-sub a.prof-logout {
    color: #f87171 !important;
}
body.dark .hm-sidebar .hm-sb-profile-sub a.prof-logout i {
    color: #f87171 !important;
}
body.dark .hm-sidebar .hm-sb-profile-sub a.prof-logout:hover {
    background: rgba(220, 38, 38, 0.15) !important;
    color: #fca5a5 !important;
}
body.dark .hm-sidebar .hm-sb-profile-sub a.prof-logout:hover i {
    color: #fca5a5 !important;
}

/* translate select */
body.dark .hm-sb-translate-row > i {
    color: #7A8B96;
}
body.dark .hm-sb-translate-row .goog-te-combo,
body.dark .hm-sb-translate-row select {
    background: #1B2A2F !important;
    border-color: #23343A !important;
    color: #F8FAFC !important;
}
body.dark .hm-sb-translate-row .goog-te-combo:hover,
body.dark .hm-sb-translate-row select:hover {
    border-color: #1FB7B5 !important;
}

/* ── Profile card (bottom) ── */
body.dark .hm-sidebar .hm-sb-profile {
   background: #000000 !important;
    border-top-color: #23343A;
}
body.dark .hm-sidebar .hm-sb-profile:hover {
    background: rgba(31, 183, 181, 0.08) !important;
}
body.dark .hm-sidebar .hm-sb-profile-name {
    color: #F8FAFC;
}
body.dark .hm-sidebar .hm-sb-profile-role {
    color: #94A3B8;
}
body.dark .hm-sidebar .hm-sb-profile-chevron {
    color: #7A8B96;
}
body.dark .hm-sidebar .hm-sb-profile .sb-tooltip {
    background: #0E1619;
    color: #F8FAFC;
}
body.dark .hm-sidebar .hm-sb-profile .sb-tooltip::before {
    border-right-color: #0E1619;
}

/* ── Scrollbar ── */
body.dark .hm-sb-body {
    scrollbar-color: #23343A transparent;
}
body.dark .hm-sb-body::-webkit-scrollbar-thumb {
    background: #23343A;
}
.hm-sb-theme-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 14px 6px 16px;
    margin: 2px 6px;
    border-radius: 8px;
}
.hm-sb-theme-row > i {
    width: 17px; text-align: center;
    font-size: 13.5px; color: var(--hm-light); flex-shrink: 0;
}
.hm-theme-label {
    flex: 1; font-size: 14px; font-weight: 500; color: var(--hm-muted);
}

/* pill */
.hm-theme-pill {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    width: 80px; height: 36px;
    border-radius: 999px;
    background: #EAECEF;
    border: 1px solid #D8DCE1;
    padding: 0 6px;
    cursor: pointer;
    user-select: none;
    flex-shrink: 0;
    transition: background .3s, border-color .3s;
}
body.dark .hm-theme-pill {
    background: #1B2A2F;
    border-color: #23343A;
}

/* sliding knob */
.hm-knob {
    position: absolute;
    top: 3px; left: 3px;
    width: 28px; height: 28px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,.18), 0 0 0 .5px rgba(0,0,0,.06);
    transition: transform .28s cubic-bezier(.4,0,.2,1), background .3s;
    z-index: 2;
    pointer-events: none;
}
body.dark .hm-knob {
    transform: translateX(44px);
    background: #2D4A52;
    box-shadow: 0 1px 4px rgba(0,0,0,.4);
}

/* icons */
.hm-tp-icon {
    width: 22px; height: 22px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; z-index: 1; flex-shrink: 0;
}
.hm-tp-sun  { color: var(--hm-teal); }
.hm-tp-moon { color: #7A8B96; }
body.dark .hm-tp-sun { color: var(--hm-primary); }
body.dark .hm-tp-moon { color: #94A3B8; }

/* Keep the global recruiter loader on the HireMatrix teal theme. */
html.hm-dark-preload,
html.hm-dark-preload body,
body.recruiter-jobboard.dark,
body.recruiter-jobboard.dark-mode {
    background: #0d1117;
}

html.hm-dark-preload #overlayer,
body.recruiter-jobboard.dark #overlayer,
body.recruiter-jobboard.dark-mode #overlayer {
    background: #0d1117 !important;
}

body.recruiter-jobboard .loader .spinner-border,
body.recruiter-jobboard .loader .spinner-border.text-primary,
body.recruiter-jobboard .loader .spinner-border.text-success,
html.hm-dark-preload .loader .spinner-border {
    color: var(--hm-primary, #1FB7B5) !important;
}

    </style>
</head>

<body id="top" class="hirematrix-app recruiter-jobboard">
<div id="overlayer"></div>
<div class="loader">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>

<?php
$recruiterId = (int)(session()->get('user_id') ?? 0);
$recruiterUnreadNotificationCount = $recruiterId > 0
    ? (int) model('NotificationModel')->getUnreadCount($recruiterId)
    : 0;
$currentUri = uri_string();
$isActive = fn(string $path) => str_starts_with($currentUri, $path) ? 'active' : '';
?>

<div class="hm-sb-overlay" id="hmOverlay"></div>

<div class="site-wrap">

    <!-- ═══════════ SIDEBAR ═══════════ -->
    <aside class="hm-sidebar" id="hmSidebar" aria-label="Recruiter navigation">

        <!-- LOGO + BRAND = collapse toggle -->
        <div class="hm-sb-head" id="hmSbToggle" role="button" tabindex="0"
             aria-label="Toggle sidebar" title="Toggle sidebar">
            <div class="hm-sb-logo">
                <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix logo">
            </div>
            <span class="hm-sb-brand">
                Hire<span style="color:var(--hm-secondary);">Matrix</span>
            </span> 
        </div>

        <!-- NAV -->
        <nav class="hm-sb-body"> 
            <a href="<?= base_url('recruiter/dashboard') ?>"
               class="hm-sb-item <?= $isActive('recruiter/dashboard') ?>">
                <i class="fas fa-th-large sb-icon"></i>
                <span class="sb-label">Dashboard</span>
                <span class="sb-tooltip">Dashboard</span>
            </a>

            <button class="hm-sb-item <?= ($isActive('recruiter/jobs') || $isActive('recruiter/post_job')) ? 'active sb-open' : '' ?>"
                    id="hmJobsBtn" aria-expanded="false">
                <i class="fas fa-briefcase sb-icon"></i>
                <span class="sb-label">Jobs</span>
                <i class="fas fa-chevron-down sb-arrow"></i>
                <span class="sb-tooltip">Jobs</span>
            </button>
            <div class="hm-sb-sub <?= ($isActive('recruiter/jobs') || $isActive('recruiter/post_job')) ? 'sb-open' : '' ?>"
                 id="hmJobsSub">
                <a href="<?= base_url('recruiter/jobs') ?>"
                   class="hm-sb-subitem <?= ($isActive('recruiter/jobs') && !$isActive('recruiter/post_job')) ? 'active' : '' ?>">
                   My Jobs
                </a>
                <a href="<?= base_url('recruiter/post_job') ?>"
                   class="hm-sb-subitem <?= $isActive('recruiter/post_job') ? 'active' : '' ?>">
                   Post a Job
                </a>
            </div>

            <a href="<?= base_url('recruiter/candidates') ?>"
               class="hm-sb-item <?= $isActive('recruiter/candidates') ?>">
                <i class="fas fa-users sb-icon"></i>
                <span class="sb-label">Candidates</span>
                <span class="sb-tooltip">Candidates</span>
            </a>

            <a href="<?= base_url('recruiter/slots') ?>"
               class="hm-sb-item <?= $isActive('recruiter/slots') ?>">
                <i class="fas fa-calendar-alt sb-icon"></i>
                <span class="sb-label">Interview Slots</span>
                <span class="sb-tooltip">Slots</span>
            </a> 

            <a href="<?= base_url('notifications') ?>"
               class="hm-sb-item <?= $isActive('notifications') ?>">
                <i class="fas fa-bell sb-icon"></i>
                <span class="sb-label">Notifications</span>
                <?php if ($recruiterUnreadNotificationCount > 0): ?>
                    <span class="sb-badge">
                        <?= $recruiterUnreadNotificationCount > 99 ? '99+' : $recruiterUnreadNotificationCount ?>
                    </span>
                <?php endif; ?>
                <span class="sb-tooltip">Notifications</span>
            </a> 

        </nav>

        <!-- FOOTER: profile submenu + profile card -->
        <div class="hm-sb-foot">

            <!-- opens upward above the card -->
            <div class="hm-sb-profile-sub" id="hmProfileSub">
                <a href="<?= base_url('recruiter/company-profile') ?>"
                   class="<?= $isActive('recruiter/company-profile') ?>">
                    <i class="fas fa-building"></i> Company Profile
                </a>
                <a href="<?= base_url('recruiter/settings') ?>"
                   class="<?= $isActive('recruiter/settings') ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="<?= base_url('logout') ?>" class="prof-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <!-- profile card toggle -->
            <div class="hm-sb-profile" id="hmProfileCard" role="button" tabindex="0" aria-expanded="false">
                <div class="hm-sb-profile-avatar">
                    <?= strtoupper(substr(session()->get('user_name') ?? 'R', 0, 1)) ?>
                </div>
                <div class="hm-sb-profile-info">
                    <div class="hm-sb-profile-name"><?= esc(session()->get('user_name') ?? 'Recruiter') ?></div>
                    <div class="hm-sb-profile-role">Recruiter</div>
                </div>
                <i class="fas fa-chevron-up hm-sb-profile-chevron"></i>
                <span class="sb-tooltip"><?= esc(session()->get('user_name') ?? 'Recruiter') ?></span>
            </div>

        </div>

    </aside>
    <!-- ═══════════ END SIDEBAR ═══════════ -->

    <!-- ═══════════ MAIN ═══════════ -->
    <div class="hm-main" id="hmMain"> 

        <div class="hm-page-content">
        <main>

<script>
(function () {
    'use strict';
 
    var sidebar     = document.getElementById('hmSidebar');
    var toggleArea  = document.getElementById('hmSbToggle');
    var mobileBtn   = document.getElementById('hmMobileToggle');
    var overlay     = document.getElementById('hmOverlay');
    var jobsBtn     = document.getElementById('hmJobsBtn');
    var jobsSub     = document.getElementById('hmJobsSub');
    var profileCard = document.getElementById('hmProfileCard');
    var profileSub  = document.getElementById('hmProfileSub');
 
    var KEY       = 'hm_sb_collapsed';
    var collapsed = localStorage.getItem(KEY) === '1';
 
    function applyCollapsed() {
        sidebar.classList.toggle('sb-collapsed', collapsed);
        document.body.classList.toggle('sb-collapsed-body', collapsed);
    }
    applyCollapsed();
 
    toggleArea.addEventListener('click', function () {
        if (window.innerWidth <= 991) return;
        collapsed = !collapsed;
        localStorage.setItem(KEY, collapsed ? '1' : '0');
        applyCollapsed();
    });
    toggleArea.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleArea.click(); }
    });
 
    profileCard && profileCard.addEventListener('click', function () {
        if (sidebar.classList.contains('sb-collapsed')) return;
        var open = profileSub.classList.toggle('prof-open');
        profileCard.classList.toggle('prof-open', open);
        profileCard.setAttribute('aria-expanded', open);
    });
    profileCard && profileCard.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); profileCard.click(); }
    });
 
    function openMobile() {
        sidebar.classList.add('sb-mobile-open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeMobile() {
        sidebar.classList.remove('sb-mobile-open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    mobileBtn && mobileBtn.addEventListener('click', openMobile);
    overlay.addEventListener('click', closeMobile);
 
    jobsBtn && jobsBtn.addEventListener('click', function () {
        var open = jobsSub.classList.toggle('sb-open');
        this.classList.toggle('sb-open', open);
        this.setAttribute('aria-expanded', open);
    });
 
})();
</script><script>
 (function () {
    var THEME_KEY = 'hm_theme';
    var pill = document.getElementById('hmThemePill');

    function applyTheme(dark) {
        document.body.classList.toggle('dark', dark);
        if (pill) pill.setAttribute('aria-checked', dark);
    }

    applyTheme(localStorage.getItem(THEME_KEY) === 'dark');

    window.hmToggleTheme = function () {
        var isDark = document.body.classList.contains('dark');
        localStorage.setItem(THEME_KEY, isDark ? 'light' : 'dark');
        applyTheme(!isDark);
    };
})();
</script>
