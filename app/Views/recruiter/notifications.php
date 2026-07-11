<?= view('Layouts/recruiter_header', ['title' => 'Notifications']) ?>
<div class="recruiter-notifications-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">All Notifications</h1>
            <p class="page-board-subtitle recruiter-muted-text">Track candidate activity, applications, and recruiter actions in one place.</p>
            <div class="company-profile-meta">
                <span class="status-pill"><strong id="recruiterUnreadCount"><?= number_format((int) $unread_count) ?></strong> Unread</span>
                <span class="status-pill"><strong><?= number_format(count($notifications ?? [])) ?></strong> Total</span>
            </div>
        </div>
        <div class="page-board-actions">
            <?php if ($unread_count > 0): ?>
                <a href="<?= base_url('notifications/mark-all-read') ?>" class="btn btn-primary js-mark-all-notifications-read">
                    <span class="icon-check mr-1"></span> Mark All as Read
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div id="recruiterNotificationAjaxAlert"></div>

    <?php if (empty($notifications)): ?>
        <div class="card shadow-sm recruiter-notification-empty">
            <div class="card-body py-5 text-center">
                <span class="icon-bell-slash text-muted mb-3 d-inline-block recruiter-text-1rem"></span>
                <h5>No Notifications</h5>
                <p class="text-muted mb-0">You're all caught up!</p>
            </div>
        </div>
    <?php else: ?>
        <div class="recruiter-notification-list">
            <?php foreach ($notifications as $notification): ?>
                <?php $config = model('NotificationModel')->getNotificationConfig($notification['type']); ?>
                <div class="card shadow-sm recruiter-notification-card <?= $notification['is_read'] ? '' : 'is-unread' ?>" data-notification-card="<?= (int) $notification['id'] ?>">
                    <div class="card-body">
                        <div class="recruiter-notification-row">
                            

                            <div class="recruiter-notification-copy">
                                <div class="recruiter-notification-head">
                                    <h5 class="mb-3 recruiter-text-1rem">
                                        <?= esc($notification['title']) ?>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge badge-primary">New</span>
                                        <?php endif; ?>
                                    </h5>
                                    <small class="text-muted recruiter-muted-12"><?= time_ago($notification['created_at']) ?></small>
                                </div>

                                <p class="mb-3 recruiter-muted-12"><?= esc($notification['message']) ?></p>

                                <div class="recruiter-notification-actions">
                                    <?php if ($notification['action_link']): ?>
                                        <a href="<?= base_url('notifications/mark-read/' . $notification['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <?= esc($config['action_text'] ?? 'Open') ?>  
                                        </a>
                                    <?php endif; ?>

                                    <div class="recruiter-notification-links">
                                        <?php if (!$notification['is_read']): ?>
                                            <a href="<?= base_url('notifications/mark-read/' . $notification['id']) ?>" class="btn btn-sm btn-link js-mark-notification-read" data-notification-id="<?= (int) $notification['id'] ?>">
                                                Mark as Read
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('notifications/delete/' . $notification['id']) ?>" class="btn btn-sm btn-link text-danger js-delete-notification" data-notification-id="<?= (int) $notification['id'] ?>" data-confirm-message="Delete this notification?">
                                             Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>
