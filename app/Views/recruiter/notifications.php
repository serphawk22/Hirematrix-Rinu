<?= view('Layouts/recruiter_header', ['title' => 'Notifications']) ?>
<style>
     .page-board-title{
        font-size: 26px !important; 
    font-weight: 700 !important;
    color: var(--foreground) !important;
    margin: 0;
    }
    body.dark .page-board-title{
        font-size: 26px !important;
    font-weight: 700 !important;
    color: #F8FAFC !important;
    margin: 0;
    }
    .hm-page-content,.recruiter-notifications-jobboard{
         background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
}
body.dark .hm-page-content,body.dark .recruiter-notifications-jobboard,body.dark .recruiter-notification-card{
    background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%) !important; 
    border: 1px solid #23343A; !important;
} 
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    background: #16212b14;
    color: #0D8A90;
    border: none;
    text-decoration: none !important;
    white-space: nowrap;
    cursor: pointer;
}
body.dark .status-pill {
    background: #7a8b9650;
    color: #0D8A90;
}
   .btn-primary,.btn-outline-primary {  
        background: transparent !important;
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important;
    padding: 8px 20px;
    border-radius: 6px !important;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-primary:hover, .btn-primary:focus, .btn-outline-primary:focus, .btn-outline-primary:hover {
 background:  #1FB7B5 !important;
    color: #ffffff !important;
    transform: translateY(-1px);

}
.page-board-header.page-board-header-tight.recruiter-page-board-header,body.dark .page-board-header.page-board-header-tight.recruiter-page-board-header{
    border:none !important;
}
h5{
    color:#16212B !important;
}
body.dark h5{
    color:#F8FAFC !important;
}
.text-muted{
    color:#64748B !important;
}

</style>
<div class="recruiter-notifications-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">All Notifications</h1>
            <p class="page-board-subtitle">Track candidate activity, applications, and recruiter actions in one place.</p>
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
                <span class="icon-bell-slash text-muted mb-3 d-inline-block" style="font-size: 1rem;"></span>
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
                                    <h5 class="mb-3" style="font-size:1rem;">
                                        <?= esc($notification['title']) ?>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge badge-primary">New</span>
                                        <?php endif; ?>
                                    </h5>
                                    <small class="text-muted" style="font-size:12px;color:#64748B !important;"><?= time_ago($notification['created_at']) ?></small>
                                </div>

                                <p class="mb-3" style="font-size:13px;color:#64748B !important;"><?= esc($notification['message']) ?></p>

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
