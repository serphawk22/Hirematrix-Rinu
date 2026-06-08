<?= view('Layouts/candidate_header', ['title' => 'Settings']) ?>

<?php $activeTab = (string) (service('request')->getGet('tab') ?? 'visibility'); ?>

<div class="settings-jobboard">
    <section class="site-section pt-0 content-wrap settings-content-canvas">
        <div class="container-fluid">
            <div class="page-board-header page-board-header-tight">
                <div class="page-board-copy">
                    <span class="page-board-kicker"> Account settings</span>
                    <h1 class="page-board-title">Settings</h1>
                    <p class="page-board-subtitle">Control profile visibility, notifications, and account security from one compact panel.</p>
                </div>
                
            </div>

            <?php if (session()->getFlashdata('settings_success') || session()->getFlashdata('error')): ?>
                <div class="settings-flash-wrap">
                    <?php if (session()->getFlashdata('settings_success')): ?>
                        <div class="alert alert-success mb-0"><?= esc(session()->getFlashdata('settings_success')) ?></div>
                    <?php endif; ?>
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger mb-0"><?= esc(session()->getFlashdata('error')) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="settings-shell">
                <aside class="settings-side">
                    <div class="settings-side-title">Account Settings</div>
                    <nav class="settings-nav" id="settingsNav">
                        <a href="#visibility" class="settings-nav-link <?= $activeTab === 'visibility' ? 'is-active' : '' ?>" data-settings-tab="visibility">
                            <span><i class="fas fa-eye"></i></span>
                            <span>
                                Profile Visibility
                                <small>Recruiter access</small>
                            </span>
                        </a>
                        <a href="#notifications" class="settings-nav-link <?= $activeTab === 'notifications' ? 'is-active' : '' ?>" data-settings-tab="notifications">
                            <span><i class="fas fa-bell"></i></span>
                            <span>
                                Notifications
                                <small>Job alerts and channels</small>
                            </span>
                        </a>
                        <a href="#account" class="settings-nav-link <?= $activeTab === 'account' ? 'is-active' : '' ?>" data-settings-tab="account">
                            <span><i class="fas fa-lock"></i></span>
                            <span>
                                Account
                                <small>Password and security</small>
                            </span>
                        </a>
                        <a href="#appearance" class="settings-nav-link <?= $activeTab === 'appearance' ? 'is-active' : '' ?>" data-settings-tab="appearance">
                            <span><i class="fas fa-paint-brush"></i></span>
                            <span>
                                Appearance
                                <small>Theme and display</small>
                            </span>
                        </a>
                        <a href="#language" class="settings-nav-link <?= $activeTab === 'language' ? 'is-active' : '' ?>" data-settings-tab="language">
                            <span><i class="fas fa-globe"></i></span>
                            <span>
                                Language
                                <small>Page translation</small>
                            </span>
                        </a>

                    </nav>
                </aside>

                <div class="settings-content">
                    <section class="settings-panel <?= $activeTab === 'visibility' ? 'is-active' : '' ?>" data-settings-panel="visibility">
                        <div class="settings-panel-title">Profile Visibility</div>
                        <div class="settings-panel-copy">Control whether recruiters can discover your profile outside the jobs you already applied for.</div>

                        <div class="settings-card">
                            <form method="post" action="<?= base_url('candidate/update-settings') ?>" id="visibilitySettingsForm">
                                <?= csrf_field() ?>
                                <div class="switch-row">
                                    <div class="switch-copy">
                                        <label for="allow_public_recruiter_visibility">Visible To Recruiters</label>
                                        <small>When off, recruiters can access your profile only after you apply to one of their jobs.</small>
                                    </div>
                                    <input type="hidden" name="allow_public_recruiter_visibility" value="0">
                                    <label class="switch-toggle" for="allow_public_recruiter_visibility">
                                        <input type="checkbox" name="allow_public_recruiter_visibility" id="allow_public_recruiter_visibility" value="1" <?= (int) ($user['allow_public_recruiter_visibility'] ?? 1) === 1 ? 'checked' : '' ?>>
                                        <span class="switch-toggle-slider"></span>
                                    </label>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="settings-panel <?= $activeTab === 'notifications' ? 'is-active' : '' ?>" data-settings-panel="notifications">
                        <div class="settings-panel-title">Notifications</div>
                        <div class="settings-panel-copy">Manage job alert activation and choose where alert updates should reach you.</div>

                        <div class="settings-card">
                            <form method="post" action="<?= base_url('candidate/update-notification-settings') ?>" id="notificationSettingsForm">
                                <?= csrf_field() ?>
                                <div class="switch-row">
                                    <div class="switch-copy">
                                        <label for="job_alerts_enabled">Job Alerts</label>
                                        <small>Turn profile-based job alerts on or off without changing the preferences saved in your profile.</small>
                                    </div>
                                    <input type="hidden" name="job_alerts_enabled" value="0">
                                    <label class="switch-toggle" for="job_alerts_enabled">
                                        <input type="checkbox" name="job_alerts_enabled" id="job_alerts_enabled" value="1" <?= (int) ($user['job_alerts_enabled'] ?? 1) === 1 ? 'checked' : '' ?>>
                                        <span class="switch-toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="switch-row">
                                    <div class="switch-copy">
                                        <label for="job_alert_notify_in_app">In-App Notifications</label>
                                        <small>Show matching job updates inside the portal.</small>
                                    </div>
                                    <input type="hidden" name="job_alert_notify_in_app" value="0">
                                    <label class="switch-toggle" for="job_alert_notify_in_app">
                                        <input type="checkbox" name="job_alert_notify_in_app" id="job_alert_notify_in_app" value="1" <?= (int) ($user['job_alert_notify_in_app'] ?? 1) === 1 ? 'checked' : '' ?>>
                                        <span class="switch-toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="switch-row">
                                    <div class="switch-copy">
                                        <label for="job_alert_notify_email">Email Notifications</label>
                                        <small>Send matching jobs to your registered email address.</small>
                                    </div>
                                    <input type="hidden" name="job_alert_notify_email" value="0">
                                    <label class="switch-toggle" for="job_alert_notify_email">
                                        <input type="checkbox" name="job_alert_notify_email" id="job_alert_notify_email" value="1" <?= (int) ($user['job_alert_notify_email'] ?? 1) === 1 ? 'checked' : '' ?>>
                                        <span class="switch-toggle-slider"></span>
                                    </label>
                                </div>
                                <p class="mb-0 text-muted small">Job alert criteria are now taken automatically from the Preferences section in your profile.</p>
                            </form>
                        </div>
                    </section>

                    <section class="settings-panel <?= $activeTab === 'account' ? 'is-active' : '' ?>" data-settings-panel="account">
                        <div class="settings-panel-title">Account Security</div>
                        <div class="settings-panel-copy">Use the secure password change page to update your account credentials.</div>

                        <div class="settings-card">
                            <h6>Change Password</h6>
                            <p>Your password is managed on a dedicated secure page. Open it when you want to update your credentials.</p>
                            <div class="settings-actions">
                                <a href="<?= base_url('account/change-password') ?>" class="btn btn-outline-primary">Open Change Password</a>
                            </div>
                        </div>
                    </section>

                    <section class="settings-panel <?= $activeTab === 'appearance' ? 'is-active' : '' ?>" data-settings-panel="appearance">
                        <div class="settings-panel-title">Appearance</div>
                        <div class="settings-panel-copy">Customize the visual theme of your candidate portal.</div>

                        <div class="settings-card">
                            <div class="appearance-settings">
                                <div class="appearance-option-item">
                                    <div class="appearance-option-icon"><i class="fas fa-moon"></i></div>
                                    <div class="appearance-option-text">
                                        <strong>Dark Mode</strong>
                                        <p>Adjust the appearance of HireMatrix to reduce glare and give your eyes a break.</p>
                                    </div>
                                </div>

                                <div class="appearance-radio-group mt-2">
                                    <label class="appearance-radio-item">
                                        <span>Off</span>
                                        <input type="radio" name="theme-preference" value="light" id="themeOff">
                                        <span class="radio-mark"></span>
                                    </label>
                                    <label class="appearance-radio-item">
                                        <span>On</span>
                                        <input type="radio" name="theme-preference" value="dark" id="themeOn">
                                        <span class="radio-mark"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="settings-panel <?= $activeTab === 'language' ? 'is-active' : '' ?>" data-settings-panel="language">
                        <div class="settings-panel-title">Language</div>
                        <div class="settings-panel-copy">Choose a page translation language for the candidate portal.</div>

                        <div class="settings-card settings-language-card">
                            <div class="appearance-settings">
                                <div class="appearance-option-item">
                                    <div class="appearance-option-icon"><i class="fas fa-language"></i></div>
                                    <div class="appearance-option-text">
                                        <strong>Translate Page</strong>
                                        <p>Select your preferred language from the available translation options.</p>
                                    </div>
                                </div>

                                <div class="settings-language-widget">
                                    <div class="settings-language-picker" id="settingsLanguagePicker">
                                        <button type="button" class="settings-language-picker-btn" id="settingsLanguagePickerBtn" aria-haspopup="listbox" aria-expanded="false">
                                            <span>Select Language</span>
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                        <div class="settings-language-picker-menu" role="listbox">
                                            <button type="button" class="settings-language-picker-option" data-value="" role="option" aria-selected="true">Select Language</button>
                                            <button type="button" class="settings-language-picker-option" data-value="bn" role="option">Bengali</button>
                                            <button type="button" class="settings-language-picker-option" data-value="gu" role="option">Gujarati</button>
                                            <button type="button" class="settings-language-picker-option" data-value="hi" role="option">Hindi</button>
                                            <button type="button" class="settings-language-picker-option" data-value="kn" role="option">Kannada</button>
                                            <button type="button" class="settings-language-picker-option" data-value="ml" role="option">Malayalam</button>
                                            <button type="button" class="settings-language-picker-option" data-value="mr" role="option">Marathi</button>
                                            <button type="button" class="settings-language-picker-option" data-value="pa" role="option">Punjabi (Gurmukhi)</button>
                                            <button type="button" class="settings-language-picker-option" data-value="ta" role="option">Tamil</button>
                                            <button type="button" class="settings-language-picker-option" data-value="te" role="option">Telugu</button>
                                            <button type="button" class="settings-language-picker-option" data-value="en" role="option">English</button>
                                        </div>
                                    </div>
                                    <div class="settings-language-engine" aria-hidden="true">
                                        <?= view('components/google_translate_widget') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const picker = document.getElementById('settingsLanguagePicker');
    const button = document.getElementById('settingsLanguagePickerBtn');
    if (!picker || !button) return;

    function getGoogleSelect() {
        return document.querySelector('.settings-language-engine .goog-te-combo');
    }

    button.addEventListener('click', function (event) {
        event.stopPropagation();
        const isOpen = picker.classList.toggle('is-open');
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    picker.querySelectorAll('.settings-language-picker-option').forEach(function (option) {
        option.addEventListener('click', function () {
            const source = getGoogleSelect();
            const value = option.dataset.value || '';
            if (source) {
                source.value = value;
                source.dispatchEvent(new Event('change', { bubbles: true }));
            }
            button.querySelector('span').textContent = option.textContent;
            picker.querySelectorAll('.settings-language-picker-option').forEach(function (node) {
                node.setAttribute('aria-selected', node === option ? 'true' : 'false');
            });
            picker.classList.remove('is-open');
            button.setAttribute('aria-expanded', 'false');
        });
    });

    document.addEventListener('click', function () {
        picker.classList.remove('is-open');
        button.setAttribute('aria-expanded', 'false');
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function pollSettings() {
        // Fetch current database values
        fetch('<?= base_url('candidate/get-settings-ajax') ?>')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.settings) {
                    const settings = data.settings;
                    
                    const visibilityCheckbox = document.getElementById('allow_public_recruiter_visibility');
                    const alertsCheckbox = document.getElementById('job_alerts_enabled');
                    const inAppCheckbox = document.getElementById('job_alert_notify_in_app');
                    const emailCheckbox = document.getElementById('job_alert_notify_email');
                    
                    if (visibilityCheckbox) {
                        const isVisible = settings.allow_public_recruiter_visibility === 1;
                        if (visibilityCheckbox.checked !== isVisible) {
                            visibilityCheckbox.checked = isVisible;
                            // Update progress bar
                            const progressBar = document.querySelector('.settings-progress-bar');
                            if (progressBar) {
                                progressBar.style.width = isVisible ? '100%' : '35%';
                                progressBar.setAttribute('aria-valuenow', isVisible ? '100' : '35');
                            }
                        }
                    }
                    if (alertsCheckbox) {
                        alertsCheckbox.checked = settings.job_alerts_enabled === 1;
                    }
                    if (inAppCheckbox) {
                        inAppCheckbox.checked = settings.job_alert_notify_in_app === 1;
                    }
                    if (emailCheckbox) {
                        emailCheckbox.checked = settings.job_alert_notify_email === 1;
                    }
                }
            })
            .catch(err => console.error("Error polling settings:", err));
    }
    
    // Poll settings status every 3 seconds to keep sync'd with mobile changes
    setInterval(pollSettings, 3000);
});
</script>
<?= view('Layouts/candidate_footer') ?>
