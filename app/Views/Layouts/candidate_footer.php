        </main>
<?php
$candidateId = (int) (session()->get('user_id') ?? 0);
$premiumMentorSubscription = null;
if ($candidateId > 0) {
    try {
        $premiumMentorSubscription = (new \App\Models\SubscriptionModel())->getUserActiveSubscription($candidateId);
    } catch (\Throwable $e) {
        $premiumMentorSubscription = null;
    }
}
$premiumMentorUrl = $premiumMentorSubscription ? base_url('premium-mentor') : base_url('premium/plans?service=mentor');
$premiumMentorLabel = $premiumMentorSubscription ? 'AI Career Mentor' : 'Unlock AI Mentor';
$premiumMentorSubLabel = $premiumMentorSubscription ? 'Open your mentor' : 'View plans';
?>



<!-- SCRIPTS -->
<?php
    $candidateAssetPath = '/' . trim((string) parse_url(current_url(), PHP_URL_PATH), '/');
    $candidateAssetOptions = is_array($candidateAssets ?? null) ? $candidateAssets : [];
    $candidateAssetEnabled = static function (string $key, bool $default = false) use ($candidateAssetOptions): bool {
        return array_key_exists($key, $candidateAssetOptions) ? (bool) $candidateAssetOptions[$key] : $default;
    };
    $candidateNeedsIsotope = $candidateAssetEnabled('isotope');
    $candidateNeedsStickyfill = $candidateAssetEnabled('stickyfill');
    $candidateNeedsFancybox = $candidateAssetEnabled('fancybox');
    $candidateNeedsEasing = $candidateAssetEnabled('easing');
    $candidateNeedsCounter = $candidateAssetEnabled('counter');
    $candidateNeedsOwlCarousel = $candidateAssetEnabled('owl-carousel');
    $candidateNeedsBootstrapSelect = $candidateAssetEnabled('bootstrap-select');
    $candidateNeedsApplicationActions = $candidateAssetEnabled(
        'application-actions',
        str_contains($candidateAssetPath, '/candidate/applications')
    );
?>
<script src="<?= base_url('jobboard/js/jquery.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/bootstrap.bundle.min.js') ?>"></script>
<?php if ($candidateNeedsIsotope): ?>
    <script src="<?= base_url('jobboard/js/isotope.pkgd.min.js') ?>"></script>
<?php endif; ?>
<?php if ($candidateNeedsStickyfill): ?>
    <script src="<?= base_url('jobboard/js/stickyfill.min.js') ?>"></script>
<?php endif; ?>
<?php if ($candidateNeedsFancybox): ?>
    <script src="<?= base_url('jobboard/js/jquery.fancybox.min.js') ?>"></script>
<?php endif; ?>
<?php if ($candidateNeedsEasing): ?>
    <script src="<?= base_url('jobboard/js/jquery.easing.1.3.js') ?>"></script>
<?php endif; ?>
<?php if ($candidateNeedsCounter): ?>
    <script src="<?= base_url('jobboard/js/jquery.waypoints.min.js') ?>"></script>
    <script src="<?= base_url('jobboard/js/jquery.animateNumber.min.js') ?>"></script>
<?php endif; ?>
<?php if ($candidateNeedsOwlCarousel): ?>
    <script src="<?= base_url('jobboard/js/owl.carousel.min.js') ?>"></script>
<?php endif; ?>
<?php if ($candidateNeedsBootstrapSelect): ?>
    <script src="<?= base_url('jobboard/js/bootstrap-select.min.js') ?>"></script>
<?php endif; ?>
<script src="<?= base_url('jobboard/js/custom.js?v=' . @filemtime(FCPATH . 'jobboard/js/custom.js')) ?>"></script>
<script src="<?= base_url('jobboard/js/candidate-pages.js?v=' . @filemtime(FCPATH . 'jobboard/js/candidate-pages.js')) ?>"></script>
<?php if ($candidateNeedsApplicationActions): ?>
    <script src="<?= base_url('jobboard/js/candidate-application-actions.js?v=' . @filemtime(FCPATH . 'jobboard/js/candidate-application-actions.js')) ?>"></script>
<?php endif; ?>
<script src="<?= base_url('jobboard/js/notification-actions.js?v=' . @filemtime(FCPATH . 'jobboard/js/notification-actions.js')) ?>"></script>
<!-- Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= base_url('sw.js') ?>')
        .then(reg => console.log('Service Worker registered'))
        .catch(err => console.log('Service Worker registration failed:', err));
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* -- Mobile: search drawer -- */
    const mobileToggle  = document.getElementById('mobileSearchToggle');
    const mobileDrawer  = document.getElementById('mobileSearchDrawer');
    const mobileOverlay = document.getElementById('mobileSearchOverlay');
    if (mobileToggle && mobileDrawer && mobileOverlay) {
        mobileToggle.addEventListener('click', () => {
            mobileDrawer.classList.add('is-open');
            mobileOverlay.classList.add('is-open');
            document.body.classList.add('mobile-search-open');
            setTimeout(() => { mobileDrawer.querySelector('input[name="search"]').focus(); }, 100);
        });
        mobileOverlay.addEventListener('click', () => {
            mobileDrawer.classList.remove('is-open');
            mobileOverlay.classList.remove('is-open');
            document.body.classList.remove('mobile-search-open');
        });
    }

    /* -- Hamburger drawer -- */
    const hmToggle  = document.getElementById('hmDrawerToggle');
    const hmDrawer  = document.getElementById('hmDrawer');
    const hmOverlay = document.getElementById('hmDrawerOverlay');
    const hmClose   = document.getElementById('hmDrawerClose');
    if (hmToggle && hmDrawer && hmOverlay) {
        hmToggle.addEventListener('click', (e) => {
            e.preventDefault();
            hmDrawer.classList.add('is-open');
            hmOverlay.classList.add('is-open');
            hmDrawer.setAttribute('aria-hidden', 'false');
            document.body.classList.add('hm-drawer-open');
        });
        [hmClose, hmOverlay].forEach(el => {
            if (el) el.addEventListener('click', () => {
                hmDrawer.classList.remove('is-open');
                hmOverlay.classList.remove('is-open');
                hmDrawer.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('hm-drawer-open');
            });
        });
    }

    /* -- Language Menu -- */
    const languageMenu  = document.getElementById('candidateLanguageMenu');
    const languageBtn   = document.getElementById('candidateLanguageBtn');
    const languagePanel = document.getElementById('candidateLanguagePanel');
    if (languageBtn && languagePanel) {
        languageBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = languagePanel.classList.toggle('is-open');
            languageBtn.classList.toggle('is-active', isOpen);
            languageBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    /* -- Nav section hover menus -- */
    const navHoverMode = window.matchMedia('(hover: hover) and (pointer: fine)');
    const candidateLeftnav = document.getElementById('candLeftnav');
    const syncCandidateLeftnavShell = () => {
        if (!candidateLeftnav || !navHoverMode.matches) {
            document.body.classList.remove('candidate-leftnav-expanded');
            return;
        }
        const hasFocusInside = candidateLeftnav.contains(document.activeElement);
        document.body.classList.toggle(
            'candidate-leftnav-expanded',
            candidateLeftnav.matches(':hover') || hasFocusInside
        );
    };
    if (candidateLeftnav) {
        candidateLeftnav.addEventListener('mouseenter', syncCandidateLeftnavShell);
        candidateLeftnav.addEventListener('mouseleave', syncCandidateLeftnavShell);
        candidateLeftnav.addEventListener('focusin', syncCandidateLeftnavShell);
        candidateLeftnav.addEventListener('focusout', function () {
            window.setTimeout(syncCandidateLeftnavShell, 0);
        });
        if (navHoverMode.addEventListener) {
            navHoverMode.addEventListener('change', syncCandidateLeftnavShell);
        }
        syncCandidateLeftnavShell();
    }
    const leftnavSectionKeys = ['overview', 'jobs', 'companies', 'career'];
    const collapseOtherLeftnavSections = (activeKey) => {
        leftnavSectionKeys.forEach(function (otherKey) {
            if (otherKey === activeKey) return;
            const otherBtn = document.querySelector('[data-section="' + otherKey + '"]');
            const otherBody = otherBtn && otherBtn.nextElementSibling;
            if (!otherBtn || !otherBody) return;
            otherBody.classList.add('is-collapsed');
            otherBtn.setAttribute('aria-expanded', 'false');
            try { localStorage.setItem('navCollapsed_' + otherKey, '1'); } catch (e) {}
        });
    };

    leftnavSectionKeys.forEach(function (key) {
        const btn = document.querySelector('[data-section="' + key + '"]');
        const body = btn && btn.nextElementSibling;
        const section = btn && btn.closest('.cand-leftnav__section');
        if (!btn || !body || !section) return;

        const hasActiveLink = () => !!section.querySelector('.cand-leftnav__link.is-active');
        const setCollapsed = (collapsed) => {
            body.classList.toggle('is-collapsed', collapsed);
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        };
        const syncDesktopHoverState = () => {
            section.classList.toggle('has-active-link', hasActiveLink());
            if (navHoverMode.matches) {
                setCollapsed(true);
                return;
            }
            try {
                setCollapsed(localStorage.getItem('navCollapsed_' + key) === '1');
            } catch (e) {
                setCollapsed(false);
            }
        };

        syncDesktopHoverState();

        section.addEventListener('mouseenter', function () {
            if (navHoverMode.matches) {
                collapseOtherLeftnavSections(key);
                setCollapsed(false);
            }
        });

        section.addEventListener('mouseleave', function () {
            if (navHoverMode.matches) {
                setCollapsed(true);
            }
        });

        btn.addEventListener('click', function (event) {
            if (navHoverMode.matches) {
                event.preventDefault();
                const willCollapse = !body.classList.contains('is-collapsed');
                if (!willCollapse) {
                    collapseOtherLeftnavSections(key);
                }
                setCollapsed(willCollapse);
                return;
            }
            const collapsed = body.classList.contains('is-collapsed') ? false : true;
            if (!collapsed) {
                collapseOtherLeftnavSections(key);
            }
            setCollapsed(collapsed);
            try { localStorage.setItem('navCollapsed_' + key, collapsed ? '1' : '0'); } catch (e) {}
        });

        btn.addEventListener('keydown', function (event) {
            if (!navHoverMode.matches || (event.key !== 'Enter' && event.key !== ' ')) {
                return;
            }
            event.preventDefault();
            const willCollapse = !body.classList.contains('is-collapsed');
            if (!willCollapse) {
                collapseOtherLeftnavSections(key);
            }
            setCollapsed(willCollapse);
        });

        if (navHoverMode.addEventListener) {
            navHoverMode.addEventListener('change', syncDesktopHoverState);
        }
    });

    /* -- Avatar Dropdown -- */
    const avatarMenu     = document.getElementById('candidateAvatarMenu');
    const avatarBtn      = document.getElementById('candidateAvatarBtn');
    const avatarDropdown = document.getElementById('candidateAvatarDropdown');
    if (avatarBtn && avatarDropdown) {
        avatarBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            avatarDropdown.classList.toggle('is-open');
            avatarBtn.setAttribute('aria-expanded', avatarDropdown.classList.contains('is-open') ? 'true' : 'false');
        });
    }

    const leftnavUser     = document.getElementById('candidateLeftnavUser');
    const leftnavUserBtn  = document.getElementById('candidateLeftnavUserBtn');
    const leftnavDropdown = document.getElementById('candidateLeftnavUserDropdown');
    if (leftnavUserBtn && leftnavDropdown) {
        const openLeftnavUserMenu = () => {
            leftnavDropdown.classList.add('is-open');
            leftnavUserBtn.setAttribute('aria-expanded', 'true');
        };
        const closeLeftnavUserMenu = () => {
            leftnavDropdown.classList.remove('is-open');
            leftnavUserBtn.setAttribute('aria-expanded', 'false');
        };

        if (leftnavUser) {
            leftnavUser.addEventListener('mouseenter', function () {
                if (navHoverMode && navHoverMode.matches) {
                    openLeftnavUserMenu();
                }
            });

            leftnavUser.addEventListener('mouseleave', function () {
                if (navHoverMode && navHoverMode.matches) {
                    closeLeftnavUserMenu();
                }
            });

            leftnavUser.addEventListener('focusin', openLeftnavUserMenu);
            leftnavUser.addEventListener('focusout', function (event) {
                if (!leftnavUser.contains(event.relatedTarget)) {
                    closeLeftnavUserMenu();
                }
            });
        }

        leftnavUserBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = leftnavDropdown.classList.toggle('is-open');
            leftnavUserBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    /* -- Global Close on Click Outside -- */
    document.addEventListener('click', (e) => {
        if (languageMenu && languagePanel && !languageMenu.contains(e.target)) {
            languagePanel.classList.remove('is-open');
            if (languageBtn) {
                languageBtn.classList.remove('is-active');
                languageBtn.setAttribute('aria-expanded', 'false');
            }
        }
        if (avatarMenu && avatarDropdown && !avatarMenu.contains(e.target)) {
            avatarDropdown.classList.remove('is-open');
            if (avatarBtn) avatarBtn.setAttribute('aria-expanded', 'false');
        }
        if (leftnavUser && leftnavDropdown && !leftnavUser.contains(e.target)) {
            leftnavDropdown.classList.remove('is-open');
            if (leftnavUserBtn) leftnavUserBtn.setAttribute('aria-expanded', 'false');
        }
    });
});
</script>

 </div>

<?= view('candidate/partials/chatbot_widget') ?>

</body>

</html>
    
