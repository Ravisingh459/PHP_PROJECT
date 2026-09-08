<?php
/**
 * NexaWork - Admin System Activity & Security Audit Logs
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Activity & Security Logs';
$sidebarRole = 'admin';

$page = max(1, (int)($_GET['page'] ?? 1));

// Fetch activity logs
$total = 0;
$logs = [];

if (tableExists('activity_logs')) {
    $countStmt = db()->query('SELECT COUNT(*) FROM activity_logs');
    $total = (int) $countStmt->fetchColumn();
    $pagination = paginate($total, $page, 15);

    $stmt = db()->prepare('
        SELECT a.*, u.first_name, u.last_name, u.email
        FROM activity_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$pagination['per_page'], $pagination['offset']]);
    $logs = $stmt->fetchAll();
} else {
    $pagination = paginate(0, 1);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-shield-halved text-primary me-2"></i>System Security & Audit Trail</h4>
        <p class="text-muted mb-0">Track real-time security events, user logins, administrative updates, and API prompts.</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action Type</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="fas fa-history fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">No system activity logs recorded yet.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <span class="fw-semibold text-dark"><?= formatDate($log['created_at']) ?></span>
                            <small class="d-block text-muted"><?= date('h:i:s A', strtotime($log['created_at'])) ?></small>
                        </td>
                        <td>
                            <?php if ($log['user_id']): ?>
                                <strong class="text-primary"><?= e($log['first_name'] . ' ' . $log['last_name']) ?></strong>
                                <small class="d-block text-muted"><?= e($log['email']) ?></small>
                            <?php else: ?>
                                <span class="badge bg-secondary">System / Guest</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1">
                                <?= e($log['action_type']) ?>
                            </span>
                        </td>
                        <td><?= e($log['description']) ?></td>
                        <td><code><?= e($log['ip_address'] ?? '127.0.0.1') ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer bg-white py-3">
        <?= renderPagination($pagination, baseUrl('admin/activity-logs.php')) ?>
    </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
