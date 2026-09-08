<?php
/**
 * NexaWork - Integrated Real-Time Messaging Hub
 * Shared module across Admin Panel, Client Panel & Freelancer Panel
 */
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$userId = currentUserId();
$currentUser = currentUser();
$sidebarRole = currentUserRole() ?? 'client';
$pageTitle = 'Messages & Chat';
$partnerId = (int)($_GET['to'] ?? $_GET['partner'] ?? 0);

// Fetch conversation list
$stmt = db()->prepare('
    SELECT u.id as partner_id, u.first_name, u.last_name, u.avatar, u.role, u.is_active,
        (SELECT body FROM messages m2 WHERE (m2.sender_id = ? AND m2.receiver_id = u.id) OR (m2.sender_id = u.id AND m2.receiver_id = ?) ORDER BY m2.created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages m3 WHERE (m3.sender_id = ? AND m3.receiver_id = u.id) OR (m3.sender_id = u.id AND m3.receiver_id = ?) ORDER BY m3.created_at DESC LIMIT 1) as last_time,
        (SELECT COUNT(*) FROM messages m4 WHERE m4.sender_id = u.id AND m4.receiver_id = ? AND m4.is_read = 0) as unread
    FROM users u
    WHERE u.id IN (
        SELECT DISTINCT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
        FROM messages WHERE sender_id = ? OR receiver_id = ?
    )
    ORDER BY last_time DESC
');
$stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
$conversationList = $stmt->fetchAll();

// If partnerId specified but not in conversation list, fetch user details to initiate chat
$partner = $partnerId ? getUserById($partnerId) : null;

// Handle send message via POST
if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $body = sanitize($_POST['body'] ?? '');

    if ($receiverId && $body) {
        $stmt = db()->prepare('INSERT INTO messages (sender_id, receiver_id, body) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $receiverId, $body]);

        createNotification($receiverId, 'new_message', 'New Message',
            $currentUser['first_name'] . ' sent you a message.', baseUrl('messages.php?to=' . $userId));

        if (isAjax()) {
            jsonResponse(['success' => true]);
        }
        redirect(baseUrl('messages.php?to=' . $receiverId));
    }
}

// Fetch message history for active conversation
$messages = [];
if ($partner) {
    $stmt = db()->prepare('SELECT m.*, u.first_name, u.last_name FROM messages m JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC');
    $stmt->execute([$userId, $partnerId, $partnerId, $userId]);
    $messages = $stmt->fetchAll();

    // Mark messages as read
    db()->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?')->execute([$partnerId, $userId]);
}

$lastMsgId = !empty($messages) ? (int) end($messages)['id'] : 0;
$extraJs = $partner ? '<script>
const CURRENT_USER_ID = ' . $userId . ';
document.addEventListener("DOMContentLoaded", function() {
    Chat.init(' . $partnerId . ', ' . $lastMsgId . ');
    $("#sendMessageBtn").on("click", function(e) { e.preventDefault(); Chat.send(); });
    $("#messageInput").on("keypress", function(e) { 
        if (e.which === 13 && !e.shiftKey) { 
            e.preventDefault(); 
            Chat.send(); 
        } 
    });

    // Quick prompt chips
    $(".quick-prompt-chip").on("click", function() {
        const text = $(this).text();
        $("#messageInput").val(text);
    });

    // Conversation Search Filter
    $("#searchConv").on("keyup", function() {
        const val = $(this).val().toLowerCase();
        $(".conv-link-item").each(function() {
            const name = $(this).attr("data-name").toLowerCase();
            $(this).toggle(name.includes(val));
        });
    });
});
</script>' : '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-comments text-primary me-2"></i>Messages & Workspace Chat</h4>
        <p class="text-muted mb-0">Real-time messaging center for Clients, Freelancers, and System Admins.</p>
    </div>
</div>

<div class="card shadow-sm border-0 overflow-hidden">
    <div class="row g-0" style="min-height: 620px;">
        <!-- Left Column: Conversations List -->
        <div class="col-md-5 col-lg-4 border-end">
            <div class="p-3 border-bottom bg-light dark-mode-card">
                <div class="input-group">
                    <span class="input-group-text bg-white dark-mode-card text-muted border-end-0"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchConv" class="form-control border-start-0 ps-0" placeholder="Search conversations...">
                </div>
            </div>

            <div class="overflow-auto" style="max-height: 550px;">
                <?php if (empty($conversationList) && !$partner): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-envelope-open fa-3x mb-2 opacity-50"></i>
                        <p class="mb-0 fs-7">No active conversations yet.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush" id="conversationGroup">
                        <?php foreach ($conversationList as $conv): 
                            $isActiveConv = ($partnerId === (int)$conv['partner_id']);
                            $partnerName = $conv['first_name'] . ' ' . $conv['last_name'];
                        ?>
                        <a href="<?= baseUrl('messages.php?to=' . $conv['partner_id']) ?>" 
                           class="list-group-item list-group-item-action p-3 conv-link-item <?= $isActiveConv ? 'active bg-primary bg-opacity-10 border-start border-primary border-4' : '' ?>"
                           data-name="<?= e($partnerName) ?>">
                            <div class="d-flex align-items-center gap-3">
                                <div class="position-relative flex-shrink-0">
                                    <img src="<?= getAvatarUrl($conv['avatar'] ?? '') ?>" class="rounded-circle" width="44" height="44" style="object-fit:cover;">
                                    <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-1" title="Online"></span>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold mb-0 text-truncate text-dark dark-mode-text" style="max-width: 140px;"><?= e($partnerName) ?></h6>
                                        <small class="text-muted fs-8"><?= $conv['last_time'] ? timeAgo($conv['last_time']) : '' ?></small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted text-truncate me-2 fs-7" style="max-width: 160px;">
                                            <?= e($conv['last_message'] ?? 'No messages yet') ?>
                                        </small>
                                        <?php if ($conv['unread'] > 0): ?>
                                            <span class="badge bg-danger rounded-pill fs-8"><?= $conv['unread'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Active Chat Window -->
        <div class="col-md-7 col-lg-8 d-flex flex-column">
            <?php if ($partner): ?>
                <!-- Partner Header -->
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-white dark-mode-card">
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?= getAvatarUrl($partner['avatar'] ?? '') ?>" class="rounded-circle" width="42" height="42" style="object-fit:cover;">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark dark-mode-text"><?= e($partner['first_name'] . ' ' . $partner['last_name']) ?></h6>
                            <span class="badge bg-primary bg-opacity-10 text-primary text-capitalize fs-8">
                                <i class="fas fa-user-tag me-1"></i><?= e($partner['role']) ?>
                            </span>
                            <small class="text-muted ms-2 fs-8"><i class="fas fa-circle text-success me-1 fs-9"></i>Active Now</small>
                        </div>
                    </div>

                    <div>
                        <?php if ($partner['role'] === 'freelancer'): ?>
                            <a href="<?= baseUrl('freelancer-profile.php?id=' . $partner['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-external-link-alt me-1"></i>View Profile
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Messages Container -->
                <div class="chat-messages p-4 flex-grow-1 overflow-auto" id="chatMessages" style="max-height: 440px;">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-hand-wave fa-3x mb-2 text-warning"></i>
                            <p class="mb-0">Start the conversation! Say hi to <?= e($partner['first_name']) ?>.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): 
                            $isSent = ($msg['sender_id'] == $userId);
                        ?>
                        <div class="chat-message <?= $isSent ? 'sent' : 'received' ?>">
                            <div class="fw-normal"><?= nl2br(e($msg['body'])) ?></div>
                            <small class="opacity-75 d-block mt-1 text-end fs-8"><?= date('h:i A', strtotime($msg['created_at'])) ?></small>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Quick Response Chips -->
                <div class="px-3 py-2 bg-light border-top dark-mode-card d-flex gap-2 flex-wrap fs-8">
                    <span class="text-muted me-1">Quick Prompts:</span>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill quick-prompt-chip fs-8">Hi! Thanks for reaching out.</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill quick-prompt-chip fs-8">Can you share the project update?</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill quick-prompt-chip fs-8">Milestone complete! Please review.</button>
                </div>

                <!-- Input Footer -->
                <div class="p-3 border-top bg-white dark-mode-card">
                    <form id="messageForm" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="receiver_id" value="<?= $partnerId ?>">
                        <div class="input-group">
                            <textarea class="form-control" id="messageInput" name="body" rows="2" placeholder="Type your message here... (Press Enter to send)" required></textarea>
                            <button type="button" id="sendMessageBtn" class="btn btn-primary px-4 fw-bold">
                                <i class="fas fa-paper-plane me-1"></i>Send
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="d-flex align-items-center justify-content-center h-100 p-5 text-muted">
                    <div class="text-center">
                        <i class="fas fa-comments fa-4x text-primary mb-3 opacity-50"></i>
                        <h5>No Conversation Selected</h5>
                        <p class="mb-0">Choose a contact from the left list to view or start a conversation.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/sidebar-close.php';
require_once __DIR__ . '/includes/footer.php';
