<?= view('Layouts/recruiter_header', ['title' => 'Messages']) ?>
<?php
$candidateId = (int) ($candidate['id'] ?? 0);
$applicationId = (int) ($applicationId ?? 0);
$jobId = (int) ($jobId ?? 0);
$emailActivityId = (int) ($emailActivityId ?? 0);
$communicationItems = $communicationItems ?? [];
$profileUrl = base_url('recruiter/candidate/' . $candidateId);
$profileParams = [];
if ($applicationId > 0) {
    $profileParams['application_id'] = $applicationId;
}
if ($jobId > 0) {
    $profileParams['job_id'] = $jobId;
}
if ($profileParams !== []) {
    $profileUrl .= '?' . http_build_query($profileParams);
}
?>

<div class="applications-jobboard recruiter-messages-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-comments"></i> Recruiter messages</span>
                <h1 class="page-board-title">Messages</h1>
                <p class="page-board-subtitle">
                    Keep the candidate conversation organized and continue the hiring discussion from one place.
                    <?php if (!empty($applicationContext['job_title'])): ?>
                        Context: <?= esc((string) $applicationContext['job_title']) ?>.
                    <?php endif; ?>
                </p>
            </div>
            <div class="page-board-actions">
                <a href="<?= esc($profileUrl) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-user mr-1"></i> Full Profile
                </a>
                <a href="<?= base_url('notifications') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-bell mr-1"></i> Notifications
                </a>
            </div>
        </div>
    </div>

    <div class="container content-wrap pb-5">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm recruiter-message-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Conversation with <?= esc((string) ($candidate['name'] ?? 'Candidate')) ?></h5>
                <small class="text-muted"><?= count($communicationItems) ?> item<?= count($communicationItems) === 1 ? '' : 's' ?></small>
            </div>
            <div class="card-body recruiter-message-list">
                <?php if ($communicationItems === []): ?>
                    <p class="recruiter-message-empty">No messages yet.</p>
                <?php else: ?>
                    <?php $unreadSeparatorShown = false; ?>
                    <?php foreach ($communicationItems as $item): ?>
                        <?php
                        $source = (string) ($item['source'] ?? 'Portal Message');
                        $isEmail = str_contains(strtolower($source), 'email');
                        $isOutbound = ($item['direction'] ?? '') === 'outbound';
                        $isHighlight = $emailActivityId > 0 && (string) ($item['id'] ?? '') === 'email-' . $emailActivityId;
                        ?>
                        <?php if ($isHighlight && !$unreadSeparatorShown): ?>
                            <div class="recruiter-message-unread-separator" role="separator" aria-label="New reply">
                                <span>New reply</span>
                            </div>
                            <?php $unreadSeparatorShown = true; ?>
                        <?php endif; ?>
                        <div class="recruiter-message-row <?= $isOutbound ? 'is-outbound' : 'is-inbound' ?>">
                            <span class="recruiter-message-sender-icon" aria-hidden="true">
                                <i class="fas <?= $isOutbound ? 'fa-user-tie' : 'fa-user' ?>"></i>
                            </span>
                            <div class="recruiter-message-bubble <?= $isOutbound ? 'recruiter-message-bubble--self' : 'recruiter-message-bubble--other' ?> <?= $isHighlight ? 'recruiter-message-bubble--highlight' : '' ?>" id="<?= esc((string) ($item['id'] ?? '')) ?>">
                                <div class="recruiter-message-meta">
                                    <span class="recruiter-message-chip <?= $isEmail ? 'recruiter-message-chip--email' : 'recruiter-message-chip--portal' ?>">
                                        <?= $isEmail ? 'Email' : 'Portal Message' ?>
                                    </span>
                                    <span><?= esc((string) ($item['sender'] ?? 'Candidate')) ?></span>
                                    <span>&bull;</span>
                                    <span><?= date('M d, Y h:i A', strtotime((string) ($item['time'] ?? 'now'))) ?></span>
                                </div>
                                <?php if (trim((string) ($item['subject'] ?? '')) !== ''): ?>
                                    <strong class="recruiter-message-subject"><?= esc((string) $item['subject']) ?></strong>
                                <?php endif; ?>
                                <div class="recruiter-message-body"><?= nl2br(esc((string) ($item['body'] ?? ''))) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white">
                <form method="post" action="<?= base_url('recruiter/candidate/' . $candidateId . '/send-message') ?>" class="recruiter-message-composer" id="recruiterMessageComposer">
                    <?= csrf_field() ?>
                    <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                    <input type="hidden" name="job_id" value="<?= $jobId ?>">
                    <input type="hidden" name="return_to" value="<?= esc((string) ($returnUrl ?? current_url())) ?>">
                    <div class="form-group mb-2">
                        <textarea name="message" class="form-control" rows="3" maxlength="1000" placeholder="Write your reply..." required></textarea>
                    </div>
                    <div class="recruiter-message-composer-actions">
                        <span class="recruiter-message-send-status" role="status" aria-live="polite"></span>
                        <button type="submit" class="btn btn-primary recruiter-message-send-button">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            <span>Send Reply</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var list = document.querySelector('.recruiter-message-list');
    var highlighted = document.querySelector('.recruiter-message-bubble--highlight');
    if (highlighted) {
        highlighted.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else if (list) {
        list.scrollTop = list.scrollHeight;
    }

    var composer = document.getElementById('recruiterMessageComposer');
    if (composer) {
        composer.addEventListener('submit', function () {
            var button = composer.querySelector('.recruiter-message-send-button');
            var label = button ? button.querySelector('span') : null;
            var icon = button ? button.querySelector('i') : null;
            var status = composer.querySelector('.recruiter-message-send-status');
            if (button) {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
            }
            if (label) label.textContent = 'Sending...';
            if (icon) icon.className = 'fas fa-spinner fa-spin';
            if (status) status.textContent = 'Sending your reply';
        });
    }
});
</script>

<?= view('Layouts/recruiter_footer') ?>
