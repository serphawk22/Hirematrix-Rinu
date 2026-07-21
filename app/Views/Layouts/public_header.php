<?php
$bodyClass = trim('hirematrix-app public-header-page ' . ($body_class ?? ''));
?>
<script>
    (function () {
        try {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('hm-system-dark-preload', 'hm-dark-preload');
            }
        } catch (error) {}
    })();
</script>
<style>
html.hm-system-dark-preload,
html.hm-system-dark-preload body,
html.hm-dark-preload,
html.hm-dark-preload body {
    background: #0d1117 !important;
}

html.hm-system-dark-preload #overlayer,
html.hm-dark-preload #overlayer {
    background: #0d1117 !important;
}

html.hm-system-dark-preload .loader .spinner-border,
html.hm-system-dark-preload .loader .spinner-border.text-primary,
html.hm-dark-preload .loader .spinner-border,
html.hm-dark-preload .loader .spinner-border.text-primary {
    color: #1FB7B5 !important;
}

/* ===============================
   NAVBAR BASE — FULL WIDTH, TRANSPARENT
================================= */
header.site-navbar.landing-header,
header.site-navbar.landing-header.navbar-scrolled,
header.site-navbar.landing-header.site-navbar-target {
    background: transparent !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    z-index: 1050 !important;
    isolation: isolate;
    transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease !important;
}

/* ===============================
   FULL-WIDTH INNER ROW
================================= */
header.site-navbar.landing-header .container-fluid {
    padding-left: 40px !important;
    padding-right: 40px !important;
    max-width: 100% !important;
    width: 100% !important;
}

header.site-navbar.landing-header .row.landing-header-row {
    width: 100% !important;
    justify-content: space-between !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

/* ===============================
   LOGO & ACTIONS — flush to edges
================================= */
header.site-navbar.landing-header .site-logo {
    padding-left: 0 !important;
    margin-left: 0 !important;
}

header.site-navbar.landing-header .landing-header-actions {
    padding-right: 0 !important;
    margin-right: 0 !important;
}

header.site-navbar.landing-header .site-navigation {
    display: flex !important;
    justify-content: center !important;
}

/* ===============================
   LOGO TEXT
================================= */
header.site-navbar.landing-header .landing-header-logo-text {
    color: #16212B !important;
    font-size: 1.3rem;
    font-weight: 500 !important;
}

/* ===============================
   PUSH CONTENT BELOW NAVBAR
================================= */
.site-wrap > .auth-page-shell,
.site-wrap > section:first-of-type {
    padding-top: 80px !important;
}

/* ===============================
   ACTIONS ROW
================================= */
.landing-header-actions {
    overflow: visible !important;
    position: relative;
    z-index: 1060;
    gap: 16px;
}

.landing-header-row {
    overflow: visible !important;
}

@media (max-width: 767.98px) {
    header.site-navbar.landing-header {
        min-height: 64px !important;
    }

    header.site-navbar.landing-header .container-fluid {
        padding-left: 20px !important;
        padding-right: 20px !important;
    }

    header.site-navbar.landing-header .row.landing-header-row {
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        min-height: 64px !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    header.site-navbar.landing-header .site-logo {
        flex: 1 1 auto !important;
        max-width: calc(100% - 104px) !important;
        min-width: 0 !important;
        padding-left: 0 !important;
        padding-right: 8px !important;
    }

    header.site-navbar.landing-header .landing-header-logo-link,
    header.site-navbar.landing-header .site-logo a {
        max-width: 100% !important;
    }

    header.site-navbar.landing-header .landing-header-logo-text {
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    header.site-navbar.landing-header .site-navigation {
        display: none !important;
    }

    header.site-navbar.landing-header .landing-header-actions {
        flex: 0 0 auto !important;
        margin-left: auto !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        text-align: right !important;
    }

    header.site-navbar.landing-header .landing-header-actions .site-menu-toggle {
        display: none !important;
        margin: 0 !important;
    }

    header.site-navbar.landing-header .landing-header-actions .btn,
    header.site-navbar.landing-header .landing-header-actions .btn-outline-primary,
    header.site-navbar.landing-header .landing-header-actions .btn-primary {
        min-width: 86px !important;
        padding: 8px 14px !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
    }

    body.landing-page header.site-navbar.landing-header,
    body.landing-page header.site-navbar.landing-header.navbar-scrolled,
    body.landing-page header.site-navbar.landing-header.site-navbar-target {
        background: #ffffff !important;
        border-bottom: 1px solid #D9ECE5 !important;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06) !important;
    }

    .site-wrap > .auth-page-shell,
    .site-wrap > section:first-of-type {
        padding-top: 104px !important;
    }

    body.public-auth-page header.site-navbar.landing-header {
        background: #ffffff !important;
        border-bottom: 1px solid #D9ECE5 !important;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06) !important;
        position: sticky !important;
        top: 0 !important;
    }

    body.public-auth-page .site-wrap > .auth-page-shell,
    body.public-auth-page .site-wrap > section:first-of-type {
        padding-top: 24px !important;
    }

    body.login-auth-page header.site-navbar.landing-header .site-logo {
        max-width: 100% !important;
    }

    body.login-auth-page header.site-navbar.landing-header .landing-header-actions {
        display: none !important;
    }

    html.hm-system-dark-preload body.public-auth-page header.site-navbar.landing-header,
    html.hm-dark-preload body.public-auth-page header.site-navbar.landing-header {
        background: #0d1117 !important;
        border-bottom-color: #23343A !important;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.28) !important;
    }
}

/* ===============================
   SIGN IN BUTTON
================================= */
.btn-outline-primary {
    background: transparent !important;
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important;
    padding: 8px 20px;
    border-radius: 6px !important;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-outline-primary:hover {
    background: #1FB7B5 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
}

/* ===============================
   REGISTER BUTTON
================================= */
.btn-primary {
    background: transparent !important;
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important;
    padding: 8px 20px;
    border-radius: 6px !important;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #1FB7B5 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
}

/* ===============================
   REGISTER DROPDOWN WRAPPER
================================= */
.register-dropdown {
    position: relative;
    display: inline-flex;
    align-items: center;
    overflow: visible !important;
}

.reg-chevron {
    display: inline-block;
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 4px solid currentColor;
    transition: transform 0.2s ease;
    flex-shrink: 0;
    margin-left: 2px;
}

.register-dropdown.open .reg-chevron {
    transform: rotate(180deg);
}

/* ===============================
   DROPDOWN PANEL
================================= */
.register-dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    left: auto;
    min-width: 140px;
    background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%);
    border: 1px solid #D9ECE5;
    border-radius: 4px;
    padding: 4px;
    z-index: 9999;
    animation: dropFadeIn 0.16s ease both;
}

@keyframes dropFadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}

.register-dropdown:hover .register-dropdown-menu,
.register-dropdown.open .register-dropdown-menu {
    display: block;
}

.register-dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 7px;
    font-size: 13.5px;
    font-weight: 500;
    text-decoration: none !important;
    transition: background 0.15s;
    white-space: nowrap;
    color: #1FB7B5;
}

.register-dropdown-menu a:hover {
    color: #0D8A90;
    font-weight: 600;
}

.landing-header-logo-image {
    width: 40px;
    height: 40px;
    border-radius: 0;
    object-fit: contain;
    background: transparent;
    border: 0;
}

/* ===============================
   DARK MODE
================================= */
@media (prefers-color-scheme: dark) {

    body {
        background: #111111 !important;
    }

    /* ── Navbar always transparent ── */
    header.site-navbar.landing-header,
    header.site-navbar.landing-header.navbar-scrolled,
    header.site-navbar.landing-header.site-navbar-target {
        background: transparent !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        border: none !important;
        border-bottom: none !important;
        box-shadow: none !important;
    }

    @media (max-width: 767.98px) {
        body.landing-page header.site-navbar.landing-header,
        body.landing-page header.site-navbar.landing-header.navbar-scrolled,
        body.landing-page header.site-navbar.landing-header.site-navbar-target {
            background: #111111 !important;
            border-bottom: 1px solid #23343A !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.34) !important;
        }
    }

    /* ── Logo text ── */
    header.site-navbar.landing-header .landing-header-logo-text {
        color: #F8FAFC !important;
    }

    /* ── Sign In button ── */
    .btn-outline-primary {
        background: transparent !important;
        border: 1.5px solid #1FB7B5 !important;
        color: #1FB7B5 !important;
    }
    .btn-outline-primary:hover {
        background: #1FB7B5 !important;
        color: #ffffff !important;
    }

    /* ── Primary button ── */
    .btn-primary {
        background: transparent !important;
        border: 1.5px solid #1FB7B5 !important;
        color: #1FB7B5 !important;
    }
    .btn-primary:hover {
        background: #1FB7B5 !important;
        color: #ffffff !important;
    }

    /* ── Dropdown panel ── */
    .register-dropdown-menu {
        background: #111111 !important;
        border-color: #23343A !important;
    }
    .register-dropdown-menu a {
        color: #1FB7B5 !important;
    }
    .register-dropdown-menu a:hover {
        background: #1B2A2F !important;
        color: #ffffff !important;
    }

    /* ── Mobile hamburger icon ── */
    .icon-menu {
        color: #F8FAFC !important;
    }
}
</style>

<body id="top" class="<?= esc($bodyClass) ?>">
<div id="overlayer"></div>
<div class="loader">
    <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
</div>

<div class="site-wrap">
    <div class="site-mobile-menu site-navbar-target">
        <div class="site-mobile-menu-header">
            <div class="site-mobile-menu-close mt-3">
                <span class="icon-close2 js-menu-toggle"></span>
            </div>
        </div>
        <div class="site-mobile-menu-body"></div>
    </div>

    <header class="site-navbar site-navbar-target landing-header">
        <div class="container-fluid">
            <div class="row align-items-center landing-header-row">

                <!-- Logo -->
                <div class="site-logo col-auto">
                    <a href="<?= site_url('/') ?>" class="d-inline-flex align-items-center landing-header-logo-link" aria-label="Go to landing page">
                        <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix Logo" class="landing-header-logo-image">
                        <span class="landing-header-logo-text" style="text-transform: none;">HireMatrix</span>
                    </a>
                </div>

                <!-- Desktop Nav (empty, kept for structure) -->
                <nav class="mx-auto site-navigation col-xl">
                    <ul class="site-menu js-clone-nav d-none d-lg-flex ml-0 pl-0 landing-header-nav"></ul>
                </nav>

                <!-- Right Actions -->
                <div class="right-cta-menu text-right d-flex justify-content-end align-items-center col-auto landing-header-actions">
                    <a href="<?= site_url('register') ?>" class="btn btn-outline-primary" role="button">Sign Up</a>
                    <a href="#" class="site-menu-toggle js-menu-toggle d-inline-block d-lg-none mt-lg-2 ml-3">
                        <span class="icon-menu h3 m-0 p-0 mt-2"></span>
                    </a>
                </div>

            </div>
        </div>
    </header>

    <script>
    (function () {
        const navbar = document.querySelector('header.site-navbar.landing-header');
        if (!navbar) return;
        function onScroll() {
            if (window.scrollY > 10) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();
    </script>

    <script>
    document.querySelectorAll('.register-dropdown').forEach(function (dropdown) {
        const btn = dropdown.querySelector('.btn-primary');
        let closeTimer = null;

        dropdown.addEventListener('mouseenter', function () {
            clearTimeout(closeTimer);
            dropdown.classList.add('open');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        });

        dropdown.addEventListener('mouseleave', function () {
            closeTimer = setTimeout(function () {
                dropdown.classList.remove('open');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }, 100);
        });

        if (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isOpen = dropdown.classList.contains('open');
                document.querySelectorAll('.register-dropdown.open').forEach(function (el) {
                    el.classList.remove('open');
                    el.querySelector('.btn-primary')?.setAttribute('aria-expanded', 'false');
                });
                if (!isOpen) {
                    dropdown.classList.add('open');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        }
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.register-dropdown.open').forEach(function (el) {
            el.classList.remove('open');
            el.querySelector('.btn-primary')?.setAttribute('aria-expanded', 'false');
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.register-dropdown.open').forEach(function (el) {
            el.classList.remove('open');
            el.querySelector('.btn-primary')?.setAttribute('aria-expanded', 'false');
        });
    });
    </script>
