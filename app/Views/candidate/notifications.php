<?= view('Layouts/candidate_header', ['title' => 'Notifications']) ?>

<div class="notifications-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-bell"></i> Activity feed</span>
                <h1 class="page-board-title">Notifications</h1>
                <p class="page-board-subtitle">Track application updates, recruiter actions, and portal events in one place.</p>
            </div>
            <div class="page-board-actions">
                <?php if ($unread_count > 0): ?>
                    <a href="<?= base_url('notifications/mark-all-read') ?>" class="btn btn-primary js-mark-all-notifications-read">
                        <span class="icon-check mr-1"></span> Mark All as Read
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <div id="candidateNotificationAjaxAlert"></div>
    </div>

    <div class="container content-wrap pb-5">
        <div class="row">
            <div class="col-12">
                <?php if (empty($notifications)): ?>
                    <div class="card text-center">
                        <div class="card-body py-5">
                            <span class="icon-bell-slash text-muted mb-3 d-inline-block candidate-empty-icon"></span>
                            <h5>No Notifications</h5>
                            <p class="text-muted">You're all caught up!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <?php $config = model('NotificationModel')->getNotificationConfig($notification['type']); ?>
                        
                        <div class="card mb-3 notification-card <?= $notification['is_read'] ? '' : 'is-unread' ?>" data-notification-card="<?= (int) $notification['id'] ?>">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="notification-icon <?= $config['color'] ?> mr-3">
                                        <i class="<?= $config['icon'] ?>"></i>
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="mb-1">
                                                <?= esc($notification['title']) ?>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge badge-primary">New</span>
                                                <?php endif; ?>
                                            </h5>
                                            <small class="text-muted">
                                                <?= time_ago($notification['created_at']) ?>
                                            </small>
                                        </div>
                                        
                                        <p class="mb-2"><?= esc($notification['message']) ?></p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <?php if ($notification['action_link']): ?>
                                                <a href="<?= base_url('notifications/mark-read/' . $notification['id']) ?>" 
                                                   class="btn btn-sm btn-outline-primary notification-action-btn">
                                                    <?= esc($config['action_text'] ?? 'Take Action') ?> <span class="icon-arrow-right ml-1"></span>
                                                </a>
                                            <?php else: ?>
                                                <span></span>
                                            <?php endif; ?>
                                            
                                            <div>
                                                <?php if (!$notification['is_read']): ?>
                                                    <a href="<?= base_url('notifications/mark-read/' . $notification['id']) ?>" 
                                                       class="btn btn-sm btn-link notification-link-action js-mark-notification-read"
                                                       data-notification-id="<?= (int) $notification['id'] ?>">
                                                        <span class="icon-check mr-1"></span> Mark as Read
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= base_url('notifications/delete/' . $notification['id']) ?>" 
                                                   class="btn btn-sm btn-link notification-link-danger js-delete-notification"
                                                   data-notification-id="<?= (int) $notification['id'] ?>"
                                                   data-confirm-message="Delete this notification?">
                                                    <span class="icon-trash mr-1"></span> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= view('Layouts/candidate_footer') ?>

