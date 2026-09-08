<?php
/**
 * FreelanceHub - API: Messages (AJAX)
 */
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = currentUserId();

switch ($action) {
    case 'get_conversation':
        $partnerId = (int)($_GET['partner_id'] ?? 0);
        $lastId = (int)($_GET['last_id'] ?? 0);

        $stmt = db()->prepare('SELECT m.*, DATE_FORMAT(m.created_at, "%h:%i %p") as time
            FROM messages m
            WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
            AND m.id > ?
            ORDER BY m.created_at ASC');
        $stmt->execute([$userId, $partnerId, $partnerId, $userId, $lastId]);
        $messages = $stmt->fetchAll();

        // Mark new messages as read
        if (!empty($messages)) {
            db()->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0')
                ->execute([$partnerId, $userId]);
        }

        jsonResponse(['messages' => $messages]);
        break;

    case 'send':
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid token'], 403);
        }

        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $body = sanitize($_POST['body'] ?? '');

        if (!$receiverId || !$body) {
            jsonResponse(['success' => false, 'error' => 'Invalid data'], 400);
        }

        $stmt = db()->prepare('INSERT INTO messages (sender_id, receiver_id, body) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $receiverId, $body]);

        createNotification($receiverId, 'new_message', 'New Message',
            'You have a new message.', baseUrl('messages.php?to=' . $userId));

        jsonResponse(['success' => true, 'id' => (int) db()->lastInsertId()]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
