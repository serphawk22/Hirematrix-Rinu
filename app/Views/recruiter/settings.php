<?php
$allowedTabs = ['account', 'workflow', 'mailbox', 'appearance', 'language'];
$activeTab = (string) ($activeTab ?? 'account');
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'account';
}
$verifiedRecruiterEmail = (string) ($verifiedRecruiterEmail ?? '');
$mailDomain = str_contains($verifiedRecruiterEmail, '@') ? substr(strrchr($verifiedRecruiterEmail, '@'), 1) : '';
$suggestedMailHost = $mailDomain !== '' ? 'mail.' . $mailDomain : '';
$workflowSettings = (array) ($workflowSettings ?? []);
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
                    <a href="#workflow" class="recruiter-settings-nav-link <?= $activeTab === 'workflow' ? 'is-active' : '' ?>" data-settings-tab="workflow">
                        <span>
                            Hiring Workflow
                            <small>Decisions and emails</small>
                        </span>
                    </a>
                    <a href="#mailbox" class="recruiter-settings-nav-link <?= $activeTab === 'mailbox' ? 'is-active' : '' ?>" data-settings-tab="mailbox">
                        <span>
                            Email Sync
                            <small>Company mailbox</small>
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

                <section class="recruiter-settings-panel <?= $activeTab === 'workflow' ? 'is-active' : '' ?>" data-settings-panel="workflow">
                    <div class="recruiter-settings-panel-title">Hiring Workflow</div>
                    <div class="recruiter-settings-panel-copy">Control candidate decision communication. Rejection emails are optional and disabled until you turn them on.</div>

                    <form method="post" action="<?= base_url('recruiter/settings/workflow') ?>" class="recruiter-settings-card recruiter-workflow-card">
                        <?= csrf_field() ?>
                        <div class="recruiter-settings-card-copy recruiter-workflow-copy">
                            <h6>Rejection Email</h6>
                            <p>When enabled, candidates rejected from the pipeline receive a polite email using the template below.</p>

                            <div class="recruiter-workflow-options">
                                <label class="recruiter-workflow-toggle">
                                    <input type="checkbox" name="send_rejection_email" value="1" <?= (int) ($workflowSettings['send_rejection_email'] ?? 0) === 1 ? 'checked' : '' ?>>
                                    <span>Send rejection email when moving an applicant to Rejected</span>
                                </label>
                                <label class="recruiter-workflow-toggle">
                                    <input type="checkbox" name="rejection_email_use_mailbox" value="1" <?= (int) ($workflowSettings['rejection_email_use_mailbox'] ?? 1) === 1 ? 'checked' : '' ?>>
                                    <span>Use connected company mailbox when available</span>
                                </label>
                                <label class="recruiter-workflow-toggle">
                                    <input type="checkbox" name="rejection_email_allow_system_fallback" value="1" <?= (int) ($workflowSettings['rejection_email_allow_system_fallback'] ?? 1) === 1 ? 'checked' : '' ?>>
                                    <span>Allow system email fallback if mailbox delivery is unavailable</span>
                                </label>
                                <label class="recruiter-workflow-toggle">
                                    <input type="checkbox" name="rejection_email_cc_self" value="1" <?= (int) ($workflowSettings['rejection_email_cc_self'] ?? 0) === 1 ? 'checked' : '' ?>>
                                    <span>CC my recruiter login email on fallback emails</span>
                                </label>
                            </div>

                            <div class="form-group mt-3">
                                <label>Subject</label>
                                <input type="text" name="rejection_email_subject" class="form-control" maxlength="255" value="<?= esc(old('rejection_email_subject', $workflowSettings['rejection_email_subject'] ?? '')) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email Body</label>
                                <textarea name="rejection_email_body" class="form-control" rows="9" maxlength="4000" required><?= esc(old('rejection_email_body', $workflowSettings['rejection_email_body'] ?? '')) ?></textarea>
                                <small class="text-muted">Available tokens: {candidate_name}, {job_title}, {company_name}, {recruiter_name}</small>
                            </div>
                        </div>
                        <div class="recruiter-settings-actions recruiter-workflow-actions">
                            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-save"></i> Save Workflow</button>
                        </div>
                    </form>
                </section>

                <section class="recruiter-settings-panel <?= $activeTab === 'mailbox' ? 'is-active' : '' ?>" data-settings-panel="mailbox">
                    <div class="recruiter-settings-panel-title">Recruiter Email Synchronization</div>
                    <div class="recruiter-settings-panel-copy">Connect the same verified company email used by your recruiter account. HireMatrix never stores your mailbox password.</div>

                    <?php if (!empty($mailboxConnection) && ($mailboxConnection['status'] ?? '') === 'connected'): ?>
                        <div class="recruiter-settings-card recruiter-mailbox-connected-card">
                            <div class="recruiter-settings-card-copy">
                                <h6><i class="fas fa-check-circle text-success"></i> <?= esc(ucfirst((string) $mailboxConnection['provider'])) ?> connected</h6>
                                <p><?= esc((string) $mailboxConnection['email']) ?></p>
                                <small class="text-muted">
                                    Last synchronized: <?= !empty($mailboxConnection['last_synced_at']) ? date('M d, Y h:i A', strtotime($mailboxConnection['last_synced_at'])) : 'Not synchronized yet' ?>
                                </small>
                                <?php if (!empty($mailboxConnection['last_error'])): ?>
                                    <div class="text-danger small mt-2"><?= esc((string) $mailboxConnection['last_error']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="recruiter-settings-actions">
                                <form method="post" action="<?= base_url('recruiter/mailbox/sync') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline-primary"><i class="fas fa-sync"></i> Sync now</button>
                                </form>
                                <form method="post" action="<?= base_url('recruiter/mailbox/disconnect') ?>" onsubmit="return confirm('Disconnect this company mailbox?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-unlink"></i> Disconnect</button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="recruiter-settings-card">
                            <div class="recruiter-settings-card-copy">
                                <h6>Google Workspace</h6>
                                <p>For company mailboxes hosted by Google, including custom company domains.</p>
                            </div>
                            <div class="recruiter-settings-actions">
                                <?php if (!empty($mailboxProviders['google'])): ?>
                                    <a href="<?= base_url('recruiter/mailbox/connect/google') ?>" class="btn btn-outline-primary"><i class="fab fa-google"></i> Connect Google</a>
                                <?php else: ?>
                                    <span class="badge badge-warning">OAuth configuration required</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="recruiter-settings-card">
                            <div class="recruiter-settings-card-copy">
                                <h6>Microsoft 365</h6>
                                <p>For company mailboxes hosted by Microsoft Outlook or Exchange Online.</p>
                            </div>
                            <div class="recruiter-settings-actions">
                                <?php if (!empty($mailboxProviders['microsoft'])): ?>
                                    <a href="<?= base_url('recruiter/mailbox/connect/microsoft') ?>" class="btn btn-outline-primary"><i class="fab fa-microsoft"></i> Connect Microsoft</a>
                                <?php else: ?>
                                    <span class="badge badge-warning">OAuth configuration required</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <form method="post" action="<?= base_url('recruiter/mailbox/connect-custom') ?>" class="recruiter-settings-card recruiter-mailbox-custom-card">
                            <?= csrf_field() ?>
                            <div class="recruiter-settings-card-copy recruiter-mailbox-custom-copy">
                                <h6>Other Provider (IMAP/SMTP)</h6>
                                <p>For cPanel, private hosting, Zoho, or another company mail server. Use an app password when your provider supports one.</p>
                                <div class="row mt-3">
                                    <div class="form-group col-md-6">
                                        <label>Verified mailbox</label>
                                        <input type="email" name="mailbox_username" class="form-control" value="<?= esc(old('mailbox_username', $verifiedRecruiterEmail)) ?>" readonly required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Mailbox password / app password</label>
                                        <input type="password" name="mailbox_password" class="form-control" autocomplete="new-password" required>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label>IMAP host</label>
                                        <input type="text" name="imap_host" class="form-control" value="<?= esc(old('imap_host', $suggestedMailHost)) ?>" placeholder="mail.example.com" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>IMAP port</label>
                                        <select name="imap_port" class="form-control" required>
                                            <option value="993" <?= old('imap_port', '993') === '993' ? 'selected' : '' ?>>993</option>
                                            <option value="143" <?= old('imap_port') === '143' ? 'selected' : '' ?>>143</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>IMAP security</label>
                                        <select name="imap_encryption" class="form-control" required>
                                            <option value="ssl" <?= old('imap_encryption', 'ssl') === 'ssl' ? 'selected' : '' ?>>SSL/TLS</option>
                                            <option value="tls" <?= old('imap_encryption') === 'tls' ? 'selected' : '' ?>>STARTTLS</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label>SMTP host</label>
                                        <input type="text" name="smtp_host" class="form-control" value="<?= esc(old('smtp_host', $suggestedMailHost)) ?>" placeholder="mail.example.com" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>SMTP port</label>
                                        <select name="smtp_port" class="form-control" required>
                                            <option value="465" <?= old('smtp_port', '465') === '465' ? 'selected' : '' ?>>465</option>
                                            <option value="587" <?= old('smtp_port') === '587' ? 'selected' : '' ?>>587</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>SMTP security</label>
                                        <select name="smtp_encryption" class="form-control" required>
                                            <option value="ssl" <?= old('smtp_encryption', 'ssl') === 'ssl' ? 'selected' : '' ?>>SSL/TLS</option>
                                            <option value="tls" <?= old('smtp_encryption') === 'tls' ? 'selected' : '' ?>>STARTTLS</option>
                                        </select>
                                    </div>
                                </div>
                                <small class="text-muted">The connection is tested before saving. Credentials are encrypted using the portal encryption key.</small>
                            </div>
                            <div class="recruiter-settings-actions recruiter-mailbox-custom-actions">
                                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-plug"></i> Test and Connect</button>
                            </div>
                        </form>
                    <?php endif; ?>
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
