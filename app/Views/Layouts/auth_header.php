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
   NAVBAR — FULLY TRANSPARENT
================================= */
/* ===============================
   CONTENT BLUR BEHIND NAVBAR
================================= */
header.site-navbar.landing-header::before {
    content: '';
    position: absolute;
    inset: 0;
    backdrop-filter: blur(0px);
    -webkit-backdrop-filter: blur(0px);
    -webkit-mask-image: linear-gradient(
        to bottom,
        rgba(0,0,0,1) 0%,
        rgba(0,0,0,0.6) 60%,
        rgba(0,0,0,0) 100%
    );
    mask-image: linear-gradient(
        to bottom,
        rgba(0,0,0,1) 0%,
        rgba(0,0,0,0.6) 60%,
        rgba(0,0,0,0) 100%
    );
    pointer-events: none;
    z-index: -1;
    transition: backdrop-filter 0.3s ease;
}
header.site-navbar.landing-header::before {
    content: '';
    position: absolute;
    inset: 0;
    backdrop-filter: var(--navbar-blur, blur(0px));
    -webkit-backdrop-filter: var(--navbar-blur, blur(0px));
    pointer-events: none;
    z-index: -1;
    transition: backdrop-filter 0.3s ease;
}
/* ===============================
   NAVBAR BASE
================================= */
header.site-navbar.landing-header {
    background: transparent !important;
    background-color: transparent !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    z-index: 1050 !important;
    transition: background-color 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease, backdrop-filter 0.3s ease !important;
}

header.site-navbar.landing-header .container-fluid,
header.site-navbar.landing-header .row.landing-header-row {
    background: transparent !important;
    background-color: transparent !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: none !important;
    box-shadow: none !important;
}

/* ===============================
   NAVBAR SCROLLED — must come
   AFTER the base rule so it wins
================================= */
header.site-navbar.landing-header.navbar-scrolled {
    background: rgba(255, 255, 255, 0.88) !important;
    background-color: rgba(255, 255, 255, 0.88) !important;
    backdrop-filter: blur(18px) !important;
    -webkit-backdrop-filter: blur(18px) !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.07) !important;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08) !important;
}
 
/* ===============================
   NAVBAR — SPREAD LOGO & ACTIONS
================================= */
header.site-navbar.landing-header .container-fluid {
    padding-left: 232px !important;
    padding-right: 232px !important;
    max-width: 98% !important;
}

header.site-navbar.landing-header .row.landing-header-row {
    width: 100% !important;
    justify-content: space-between !important;
}

/* Logo — flush left */
header.site-navbar.landing-header .site-logo {
    padding-left: 0 !important;
    margin-left: 0 !important;
}

/* Actions — flush right */
header.site-navbar.landing-header .landing-header-actions {
    padding-right: 0 !important;
    margin-right: 0 !important;
}

/* Nav in middle — centered */
header.site-navbar.landing-header .site-navigation {
    display: flex !important;
    justify-content: center !important;
}

header.site-navbar.landing-header,
header.site-navbar.landing-header.site-navbar-target,
.site-wrap header.landing-header {
    background: transparent !important;
    background-color: transparent !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    z-index: 1050 !important;
}

/* The inner row — also transparent */
header.site-navbar.landing-header .container-fluid,
header.site-navbar.landing-header .row.landing-header-row {
    background: transparent !important;
    background-color: transparent !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: none !important;
    box-shadow: none !important;
}
 

/* ===============================
   PUSH CONTENT BELOW FIXED NAVBAR
================================= */
.site-wrap > .auth-page-shell,
.site-wrap > section:first-of-type {
    padding-top: 80px !important;
}

/* ===============================
   LOGO TEXT COLORS
================================= */
header.site-navbar.landing-header .landing-header-logo-text {
    color: #16212B !important;
}
 
/* ===============================
   HEADER ACTIONS ROW
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

/* ===============================
   SIGN IN + REGISTER — LINK STYLE
================================= */
@media (max-width: 767.98px) {
    header.site-navbar.landing-header {
        min-height: 64px !important;
    }

    header.site-navbar.landing-header .container-fluid {
        padding-left: 20px !important;
        padding-right: 20px !important;
        max-width: 100% !important;
    }

    header.site-navbar.landing-header .row.landing-header-row {
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        justify-content: space-between !important;
        min-height: 64px !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    header.site-navbar.landing-header .site-logo {
        flex: 1 1 auto !important;
        max-width: calc(100% - 104px) !important;
        min-width: 0 !important;
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
        padding-left: 8px !important;
        text-align: right !important;
    }

    header.site-navbar.landing-header .landing-header-actions .btn,
    header.site-navbar.landing-header .landing-header-actions .btn-outline-primary,
    header.site-navbar.landing-header .landing-header-actions .btn-primary {
        min-width: 86px !important;
        padding: 8px 14px !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
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

 .btn-outline-primary {

    background: rgba(255,255,255,.04);

    border: 2px solid #0D8A90;;

    color: #0D8A90 !important;

    padding: 8px 20px;

    border-radius: 4px !important;

    font-size: 14px;
    font-weight: 600;

    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);

    transition: all .25s ease;
}

.btn-outline-primary:hover {

     background: linear-gradient(
            135deg, 
      #1FB7B5 0%,
      #53B86C 55%,
      #B5D84E 100%
        ) !important;

    border: none !important;

    color: #ffffff !important;

    text-decoration: none;
     transform: translateY(-1px);

}
/* ===============================
   REGISTER DROPDOWN TOGGLE
================================= */
.btn-primary {

    background:
        linear-gradient(
            135deg, 
      #1FB7B5 0%,
      #53B86C 55%,
      #B5D84E 100%
        ) !important;

    border: none !important;

    color: #ffffff !important;

    padding: 8px 20px;

    border-radius: 4px !important;

    font-size: 14px;
    font-weight: 600; 

    transition: all .25s ease;
}

.btn-primary:hover {

    transform: translateY(-1px);

    color: #ffffff !important;

}
.register-dropdown-toggle {
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
    background: none !important;
    border: none !important;
    padding: 0 !important;
    cursor: pointer !important;
    font-family: inherit !important;
    line-height: inherit !important;
}

/* Chevron */
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
   REGISTER DROPDOWN WRAPPER
================================= */
.register-dropdown {
    position: relative;
    display: inline-flex;
    align-items: center;
    overflow: visible !important;
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
    background:  linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    );
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid #D9ECE5;;
    border-radius: 4px;
    padding: 4px;
    z-index: 9999;
    overflow: visible;

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
    color:#1FB7B5;
}

.register-dropdown-menu a:hover {
     color: #0D8A90;
    font-weight: 600; 
}
 
    </style>
<body id="top" class="<?= esc($bodyClass) ?>">
<div id="overlayer"></div>
<div class="loader">
    <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
</div>

 
 
   <script>
 
/* ===============================
   REGISTER DROPDOWN
   Hover on desktop, click on touch
================================= */
document.querySelectorAll('.register-dropdown').forEach(function (dropdown) {
    const btn = dropdown.querySelector('.register-dropdown-toggle');
    let closeTimer = null;

    /* Open on mouseenter */
    dropdown.addEventListener('mouseenter', function () {
        clearTimeout(closeTimer);
        dropdown.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
    });

    /* Small delay on mouseleave so cursor can move into the panel */
    dropdown.addEventListener('mouseleave', function () {
        closeTimer = setTimeout(function () {
            dropdown.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }, 100);
    });

    /* Click toggle — for touch devices */
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = dropdown.classList.contains('open');

        document.querySelectorAll('.register-dropdown.open').forEach(function (el) {
            el.classList.remove('open');
            el.querySelector('.register-dropdown-toggle')
              ?.setAttribute('aria-expanded', 'false');
        });

        if (!isOpen) {
            dropdown.classList.add('open');
            btn.setAttribute('aria-expanded', 'true');
        }
    });
});

/* Close on outside click */
document.addEventListener('click', function () {
    document.querySelectorAll('.register-dropdown.open').forEach(function (el) {
        el.classList.remove('open');
        el.querySelector('.register-dropdown-toggle')
          ?.setAttribute('aria-expanded', 'false');
    });
});

/* Close on Escape */
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.register-dropdown.open').forEach(function (el) {
        el.classList.remove('open');
        el.querySelector('.register-dropdown-toggle')
          ?.setAttribute('aria-expanded', 'false');
    });
});
</script>
     <script>
(function () {
    const navbar = document.querySelector('header.site-navbar.landing-header');
    if (!navbar) return;

    function onScroll() {
        const pseudo = navbar; 
        if (window.scrollY > 10) {
            navbar.style.setProperty('--navbar-blur', 'blur(14px)');
        } else {
            navbar.style.setProperty('--navbar-blur', 'blur(0px)');
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
        </script>
