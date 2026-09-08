<?php
/**
 * FreelanceHub - API: User Settings (AJAX)
 */
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

if (!isPost() || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false], 403);
}

$action = $_POST['action'] ?? '';
$userId = currentUserId();

switch ($action) {
    case 'toggle_dark_mode':
        $darkMode = !($_SESSION['dark_mode'] ?? false);
        $_SESSION['dark_mode'] = $darkMode;
        db()->prepare('UPDATE users SET dark_mode = ? WHERE id = ?')->execute([$darkMode ? 1 : 0, $userId]);
        jsonResponse(['success' => true, 'dark_mode' => $darkMode]);
        break;

    case 'set_language':
        $lang = in_array($_POST['language'] ?? '', ['en', 'es', 'fr', 'hi']) ? $_POST['language'] : 'en';
        $_SESSION['language'] = $lang;
        db()->prepare('UPDATE users SET language = ? WHERE id = ?')->execute([$lang, $userId]);
        jsonResponse(['success' => true, 'language' => $lang]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
