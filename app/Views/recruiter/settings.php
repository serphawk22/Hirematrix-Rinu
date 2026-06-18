<?php
$allowedTabs = ['account', 'appearance', 'language'];
$activeTab = (string) ($activeTab ?? 'account');
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'account';
}
?>

<?= view('Layouts/recruiter_header') ?>

<div class="recruiter-settings-jobboard">
    <div class="container-fluid py-5">
        <div class="page-board-header page-board-header-tight recruiter-page-board-header">
            <div class="page-board-copy">
                <span class="page-board-kicker">Recruiter settings</span>
                <h1 class="page-board-title">Settings</h1>
                <p class="page-board-subtitle">Manage account security, display theme, and page translation from one place.</p>
            </div>
        </div>

        <?php if (session()->getFlashdata('success') || session()->getFlashdata('error')): ?>
            <div class="recruiter-settings-flash">
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success mb-0"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger mb-0"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="recruiter-settings-shell">
            <aside class="recruiter-settings-side">
                <div class="recruiter-settings-side-title">Settings</div>
                <nav class="recruiter-settings-nav" id="recruiterSettingsNav">
                    <a href="#account" class="recruiter-settings-nav-link <?= $activeTab === 'account' ? 'is-active' : '' ?>" data-settings-tab="account">
                        <span>
                            Account
                            <small>Password and security</small>
                        </span>
                    </a>
                    <a href="#appearance" class="recruiter-settings-nav-link <?= $activeTab === 'appearance' ? 'is-active' : '' ?>" data-settings-tab="appearance">
                        <span>
                            Appearance
                            <small>Theme preference</small>
                        </span>
                    </a>
                    <a href="#language" class="recruiter-settings-nav-link <?= $activeTab === 'language' ? 'is-active' : '' ?>" data-settings-tab="language">
                        <span>
                            Language
                            <small>Page translation</small>
                        </span>
                    </a>
                </nav>
            </aside>

            <div class="recruiter-settings-content">
                <section class="recruiter-settings-panel <?= $activeTab === 'account' ? 'is-active' : '' ?>" data-settings-panel="account">
                    <div class="recruiter-settings-panel-title">Account Security</div>
                    <div class="recruiter-settings-panel-copy">Use the secure password page when you need to update your login credentials.</div>

                    <div class="recruiter-settings-card">
                        <div class="recruiter-settings-card-copy">
                            <h6>Change Password</h6>
                            <p>Your password is managed on a dedicated secure page.</p>
                        </div>
                        <div class="recruiter-settings-actions">
                            <a href="<?= base_url('account/change-password') ?>" class="btn btn-outline-primary">
                                <i class="fas fa-lock"></i> Open Change Password
                            </a>
                        </div>
                    </div>
                </section>

                <section class="recruiter-settings-panel <?= $activeTab === 'appearance' ? 'is-active' : '' ?>" data-settings-panel="appearance">
                    <div class="recruiter-settings-panel-title">Appearance</div>
                    <div class="recruiter-settings-panel-copy">Control the recruiter portal theme.</div>

                    <div class="recruiter-settings-card">
                        <div class="recruiter-settings-theme-row">
                            <div class="recruiter-settings-card-copy">
                                <h6>Theme</h6>
                                <p>Switch between light and dark mode for the recruiter portal.</p>
                            </div>
                            <div class="hm-theme-pill" id="hmThemePill" onclick="hmToggleTheme()"
                                 role="switch" aria-checked="false" aria-label="Toggle dark mode">
                                <div class="hm-knob"></div>
                                <div class="hm-tp-icon hm-tp-sun"><i class="fas fa-sun"></i></div>
                                <div class="hm-tp-icon hm-tp-moon"><i class="fas fa-moon"></i></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="recruiter-settings-panel <?= $activeTab === 'language' ? 'is-active' : '' ?>" data-settings-panel="language">
                    <div class="recruiter-settings-panel-title">Language</div>
                    <div class="recruiter-settings-panel-copy">Choose a page translation language for the recruiter portal.</div>

                    <div class="recruiter-settings-card">
                        <div class="recruiter-settings-card-copy">
                            <h6>Select Language</h6>
                            <p>Use the language selector below to translate the current page.</p>
                        </div>
                        <div class="recruiter-settings-language-widget">
                            <?= view('components/google_translate_widget') ?>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var nav = document.getElementById('recruiterSettingsNav');
    if (!nav) {
        return;
    }

    var links = nav.querySelectorAll('[data-settings-tab]');
    var panels = document.querySelectorAll('[data-settings-panel]');

    function activate(tab, pushState) {
        links.forEach(function (link) {
            link.classList.toggle('is-active', link.getAttribute('data-settings-tab') === tab);
        });

        panels.forEach(function (panel) {
            panel.classList.toggle('is-active', panel.getAttribute('data-settings-panel') === tab);
        });

        if (pushState && window.history && window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState(null, '', url.toString());
        }
    }

    links.forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            activate(link.getAttribute('data-settings-tab'), true);
        });
    });
});
</script>

<?= view('Layouts/recruiter_footer') ?>
