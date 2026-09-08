<?php
/**
 * FreelanceHub - Notifications Page
 */
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$userId = currentUserId();
$pageTitle = 'Notifications';

if (isset($_GET['mark_all'])) {
    db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$userId]);
    redirect(baseUrl('notifications.php'));
}

$notifications = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$notifications->execute([$userId]);
$notificationList = $notifications->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Notifications</h2>
        <a href="<?= baseUrl('notifications.php?mark_all=1') ?>" class="btn btn-outline-primary btn-sm">Mark All Read</a>
    </div>

    <?php if (empty($notificationList)): ?>
        <div class="card p-5 text-center">
            <i class="fas fa-bell fa-3x text-muted mb-3"></i>
            <p class="text-muted">No notifications yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notificationList as $notif): ?>
        <div class="card mb-2 <?= !$notif['is_read'] ? 'border-primary' : '' ?>">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <div>
                    <strong><?= e($notif['title']) ?></strong>
                    <?php if (!$notif['is_read']): ?><span class="badge bg-primary ms-2">New</span><?php endif; ?>
                    <p class="text-muted mb-0 small"><?= e($notif['message']) ?></p>
                </div>
                <div class="text-end">
                    <small class="text-muted d-block"><?= timeAgo($notif['created_at']) ?></small>
                    <?php if ($notif['link']): ?>
                        <a href="<?= e($notif['link']) ?>" class="btn btn-sm btn-outline-primary mt-1">View</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
