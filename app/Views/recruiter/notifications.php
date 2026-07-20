<?= view('Layouts/recruiter_header', ['title' => 'Notifications']) ?>
<div class="recruiter-notifications-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">All Notifications</h1>
            <p class="page-board-subtitle recruiter-muted-text">Track candidate activity, applications, and recruiter actions in one place.</p>
            <div class="company-profile-meta">
                <span class="status-pill"><strong id="recruiterUnreadCount"><?= number_format((int) $unread_count) ?></strong> Unread</span>
                <span class="status-pill"><strong><?= number_format((int) ($total_count ?? count($notifications ?? []))) ?></strong> Total</span>
            </div>
        </div>
        <div class="page-board-actions">
            <?php if ($unread_count > 0): ?>
                <form method="post" action="<?= base_url('notifications/mark-all-read') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary js-mark-all-notifications-read">
                    <span class="icon-check mr-1"></span> Mark All as Read
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div id="recruiterNotificationAjaxAlert"></div>

    <?php if (empty($notifications)): ?>
        <div class="card shadow-sm recruiter-notification-empty">
            <div class="card-body py-5 text-center">
                <span class="icon-bell-slash text-muted mb-3 d-inline-block recruiter-text-1rem"></span>
                <h5>No notifications yet</h5>
                <p class="text-muted mb-0">New candidate activity and replies will appear here.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="recruiter-notification-list">
            <?php foreach ($notifications as $notification): ?>
                <?php
                    $config = model('NotificationModel')->getNotificationConfig($notification['type']);
                    $compatibleIcons = [
                        'candidate_email_reply' => 'fas fa-envelope',
                        'candidate_message_reply' => 'fas fa-reply',
                        'interview_scheduled' => 'fas fa-calendar-check',
                        'interview_booked' => 'fas fa-calendar-check',
                        'interview_rescheduled' => 'fas fa-calendar-alt',
                        'application_status_changed' => 'fas fa-tasks',
                        'offer_sent' => 'fas fa-file-alt',
                    ];
                    $notificationIcon = $compatibleIcons[$notification['type']] ?? ($config['icon'] ?? 'fas fa-bell');
                ?>
                <div class="card recruiter-notification-card <?= $notification['is_read'] ? 'is-read' : 'is-unread' ?> is-notification-<?= esc($config['color'] ?? 'info') ?>" data-notification-card="<?= (int) $notification['id'] ?>">
                    <div class="card-body">
                        <div class="recruiter-notification-row">
                            <div class="recruiter-notification-icon" aria-hidden="true">
                                <i class="<?= esc($notificationIcon) ?>"></i>
                            </div>

                            <div class="recruiter-notification-copy">
                                <div class="recruiter-notification-head">
                                    <h5 class="recruiter-notification-title">
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="recruiter-unread-dot" aria-label="Unread"></span>
                                        <?php endif; ?>
                                        <?= esc($notification['title']) ?>
                                    </h5>
                                    <time class="recruiter-notification-time" datetime="<?= esc(date('c', strtotime($notification['created_at']))) ?>"><?= time_ago($notification['created_at']) ?></time>
                                </div>

                                <p class="recruiter-notification-message"><?= esc($notification['message']) ?></p>

                                <div class="recruiter-notification-actions">
                                    <?php if ($notification['action_link']): ?>
                                        <form method="post" action="<?= base_url('notifications/mark-read/' . $notification['id']) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-primary recruiter-notification-open"><?= esc($config['action_text'] ?? 'Open') ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                                        </form>
                                    <?php endif; ?>

                                    <div class="recruiter-notification-links">
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="post" action="<?= base_url('notifications/mark-read/' . $notification['id']) ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-link js-mark-notification-read" data-notification-id="<?= (int) $notification['id'] ?>">
                                                Mark as Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?= base_url('notifications/delete/' . $notification['id']) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-link text-danger js-delete-notification" data-notification-id="<?= (int) $notification['id'] ?>" data-confirm-message="Delete this notification?">
                                             Delete
                                            </button>
                                        </form>
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
