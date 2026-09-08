<?php
/**
 * FreelanceHub - API: Notifications (AJAX)
 */
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = currentUserId();

switch ($action) {
    case 'count':
        jsonResponse(['count' => getUnreadNotificationCount($userId)]);
        break;

    case 'mark_read':
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(['success' => false], 403);
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
        } else {
            db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$userId]);
        }
        jsonResponse(['success' => true]);
        break;

    case 'list':
        jsonResponse(['notifications' => getNotifications($userId, 20)]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
