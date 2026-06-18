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
    color: #FFFFFF !important;
    margin: 0;
    }
    .hm-page-content,.recruiter-notifications-jobboard{
         background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    ) !important;
}
body.dark .hm-page-content,body.dark .recruiter-notifications-jobboard {
    background: #000000 !important; 
} 
body.dark .recruiter-notification-card{
    background: #000000 !important;
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
    background: #000000 !important;
    color: #0D8A90;
      border: 1px solid rgba(31, 183, 181, 0.15) !important;
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
    color:#FFFFFF !important;
}
.text-muted{
    color:#64748B !important;
}
/* ── Full-width page ── */
.container-fluid {
    max-width: 100% !important;
    padding-left: 24px !important;
    padding-right: 24px !important;
}
/* ── 2 notifications per row ── */
.recruiter-notification-list {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 16px !important;
}
 small.text-muted,p.mb-3{
    color:#64748B !important;
    font-size:12px !important;
}
body.dark .page-board-subtitle,body.dark small.text-muted,body.dark p.mb-3{
    color:#FFFFFF !important;
    font-size:12px !important;
}
@media (max-width: 768px) {
    .recruiter-notification-list {
        grid-template-columns: 1fr !important;
    }
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
                                    <small class="text-muted"><?= time_ago($notification['created_at']) ?></small>
                                </div>

                                <p class="mb-3"><?= esc($notification['message']) ?></p>

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
