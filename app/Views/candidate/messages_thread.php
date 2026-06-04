        <?= view('Layouts/candidate_header', ['title' => 'Messages']) ?>
<?php
$messages = $messages ?? [];
$recruiter = $recruiter ?? [];
$recruiterId = (int) ($recruiterId ?? 0);
$applicationId = (int) ($applicationId ?? 0);
?>

<div class="applications-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-comments"></i> Candidate messages</span>
                <h1 class="page-board-title">Messages</h1>
                <p class="page-board-subtitle">Keep the recruiter conversation organized and continue the hiring discussion from one place.</p>
            </div>
            <div class="page-board-actions">
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

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Conversation with <?= esc($recruiter['name'] ?? 'Recruiter') ?></h5>
            </div>
            <div class="card-body candidate-message-list">
                <?php if (empty($messages)): ?>
                    <p class="text-muted mb-0">No messages yet.</p>
                <?php else: ?>
                    <?php foreach ($messages as $item): ?>
                        <?php $fromCandidate = ($item['sender_role'] ?? '') === 'candidate'; ?>
                        <div class="mb-3 d-flex <?= $fromCandidate ? 'justify-content-end' : 'justify-content-start' ?>">
                            <div class="candidate-message-bubble <?= $fromCandidate ? 'candidate-message-bubble--self' : 'candidate-message-bubble--other' ?>">
                                <div class="candidate-message-meta">
                                    <?= $fromCandidate ? 'You' : esc($recruiter['name'] ?? 'Recruiter') ?> • <?= date('M d, Y h:i A', strtotime($item['created_at'])) ?>
                                </div>
                                <div><?= nl2br(esc($item['message'] ?? '')) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white">
                <form method="post" action="<?= base_url('candidate/messages/' . $recruiterId . '/reply') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                    <div class="form-group mb-2">
                        <textarea name="message" class="form-control" rows="3" maxlength="1000" placeholder="Write your reply..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= view('Layouts/candidate_footer') ?>
    
