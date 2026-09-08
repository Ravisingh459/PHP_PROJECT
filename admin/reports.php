<?php
/**
 * NexaWork - Admin Reports Management Module
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'User & Project Reports';
$sidebarRole = 'admin';
$adminId = currentUserId();

$statusFilter = $_GET['status'] ?? 'all';

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $reportId = (int)($_POST['report_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = db()->prepare('SELECT * FROM reports WHERE id = ?');
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if ($report) {
        if ($action === 'update_status') {
            $newStatus = $_POST['status'] ?? 'reviewed';
            $notes = sanitize($_POST['admin_notes'] ?? '');

            db()->prepare('UPDATE reports SET status = ?, admin_notes = ?, resolved_at = NOW() WHERE id = ?')
                ->execute([$newStatus, $notes, $reportId]);

            createNotification(
                (int)$report['reporter_id'],
                'report_update',
                'Report Update',
                'Your report regarding "' . $report['reason'] . '" has been updated to: ' . ucfirst($newStatus) . '.',
                baseUrl('notifications.php')
            );

            setFlash('success', 'Report status updated.');
        } elseif ($action === 'deactivate_user' && $report['reported_user_id']) {
            db()->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND role != "admin"')
                ->execute([$report['reported_user_id']]);
            db()->prepare('UPDATE reports SET status = "resolved", admin_notes = "Reported user account was deactivated by admin.", resolved_at = NOW() WHERE id = ?')
                ->execute([$reportId]);

            setFlash('success', 'Reported user has been deactivated and report marked resolved.');
        } elseif ($action === 'delete_project' && $report['reported_project_id']) {
            db()->prepare('DELETE FROM projects WHERE id = ?')
                ->execute([$report['reported_project_id']]);
            db()->prepare('UPDATE reports SET status = "resolved", admin_notes = "Reported project was removed by admin.", resolved_at = NOW() WHERE id = ?')
                ->execute([$reportId]);

            setFlash('success', 'Reported project was removed and report marked resolved.');
        } elseif ($action === 'delete_report') {
            db()->prepare('DELETE FROM reports WHERE id = ?')->execute([$reportId]);
            setFlash('info', 'Report deleted.');
        }
    }
    redirect(baseUrl('admin/reports.php' . ($statusFilter !== 'all' ? '?status=' . urlencode($statusFilter) : '')));
}

$whereClause = '';
$params = [];
if (in_array($statusFilter, ['pending', 'reviewed', 'resolved', 'dismissed'])) {
    $whereClause = 'WHERE r.status = ?';
    $params[] = $statusFilter;
}

$reports = db()->prepare("
    SELECT r.*,
        u_rep.first_name as reporter_fname, u_rep.last_name as reporter_lname, u_rep.email as reporter_email,
        u_target.id as target_user_id, u_target.first_name as target_fname, u_target.last_name as target_lname, u_target.is_active as target_is_active,
        p.id as project_id, p.title as project_title
    FROM reports r
    JOIN users u_rep ON r.reporter_id = u_rep.id
    LEFT JOIN users u_target ON r.reported_user_id = u_target.id
    LEFT JOIN projects p ON r.reported_project_id = p.id
    {$whereClause}
    ORDER BY r.created_at DESC
");
$reports->execute($params);
$reportList = $reports->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">User & Project Reports</h4>
        <p class="text-muted mb-0">Review submitted safety and policy violation reports from users.</p>
    </div>
    <div class="btn-group">
        <a href="<?= baseUrl('admin/reports.php?status=all') ?>" class="btn btn-sm btn-outline-primary <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
        <a href="<?= baseUrl('admin/reports.php?status=pending') ?>" class="btn btn-sm btn-outline-warning <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
        <a href="<?= baseUrl('admin/reports.php?status=reviewed') ?>" class="btn btn-sm btn-outline-info <?= $statusFilter === 'reviewed' ? 'active' : '' ?>">Reviewed</a>
        <a href="<?= baseUrl('admin/reports.php?status=resolved') ?>" class="btn btn-sm btn-outline-success <?= $statusFilter === 'resolved' ? 'active' : '' ?>">Resolved</a>
        <a href="<?= baseUrl('admin/reports.php?status=dismissed') ?>" class="btn btn-sm btn-outline-secondary <?= $statusFilter === 'dismissed' ? 'active' : '' ?>">Dismissed</a>
    </div>
</div>

<?php if (empty($reportList)): ?>
    <div class="card p-5 text-center">
        <i class="fas fa-flag fa-3x text-muted mb-3 opacity-50"></i>
        <h5>No Reports Found</h5>
        <p class="text-muted mb-0">No reports match the selected criteria.</p>
    </div>
<?php else: ?>
    <?php foreach ($reportList as $rep): ?>
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <strong>Report #<?= $rep['id'] ?> — <?= e($rep['reason']) ?></strong>
                <small class="text-muted ms-2">Submitted <?= timeAgo($rep['created_at']) ?></small>
            </div>
            <div><?= getStatusBadge($rep['status']) ?></div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <small class="text-muted d-block">Reported By</small>
                    <strong><?= e($rep['reporter_fname'] . ' ' . $rep['reporter_lname']) ?></strong> (<?= e($rep['reporter_email']) ?>)
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Reported Target</small>
                    <?php if ($rep['target_user_id']): ?>
                        <strong>User:</strong> <a href="<?= baseUrl('freelancer-profile.php?id=' . $rep['target_user_id']) ?>" target="_blank"><?= e($rep['target_fname'] . ' ' . $rep['target_lname']) ?></a>
                        <?= $rep['target_is_active'] ? '<span class="badge bg-success ms-1">Active</span>' : '<span class="badge bg-danger ms-1">Inactive</span>' ?>
                    <?php elseif ($rep['project_id']): ?>
                        <strong>Project:</strong> <a href="<?= baseUrl('project-detail.php?id=' . $rep['project_id']) ?>" target="_blank"><?= e($rep['project_title']) ?></a>
                    <?php else: ?>
                        <span class="text-muted">General Platform Issue</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Resolved Date</small>
                    <span><?= $rep['resolved_at'] ? formatDateTime($rep['resolved_at']) : 'Pending Review' ?></span>
                </div>
            </div>

            <div class="p-3 bg-light rounded mb-3">
                <h6 class="fw-semibold text-muted mb-1">Report Description</h6>
                <p class="mb-0 text-dark"><?= nl2br(e($rep['description'])) ?></p>
            </div>

            <?php if ($rep['admin_notes']): ?>
            <div class="p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded mb-3">
                <h6 class="fw-semibold text-info mb-1"><i class="fas fa-sticky-note me-1"></i>Admin Notes</h6>
                <p class="mb-0 small text-dark"><?= nl2br(e($rep['admin_notes'])) ?></p>
            </div>
            <?php endif; ?>

            <hr>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <form method="POST" class="d-flex align-items-center gap-2 flex-grow-1">
                    <?= csrfField() ?>
                    <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                    <input type="hidden" name="action" value="update_status">
                    
                    <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Add admin notes..." value="<?= e($rep['admin_notes'] ?? '') ?>" style="max-width: 300px;">
                    <select name="status" class="form-select form-select-sm w-auto">
                        <option value="reviewed" <?= $rep['status'] === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                        <option value="resolved" <?= $rep['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="dismissed" <?= $rep['status'] === 'dismissed' ? 'selected' : '' ?>>Dismissed</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
                </form>

                <div class="d-flex gap-2">
                    <?php if ($rep['target_user_id'] && $rep['target_is_active']): ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                        <input type="hidden" name="action" value="deactivate_user">
                        <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Deactivate this reported user account?')">
                            <i class="fas fa-user-slash me-1"></i>Deactivate User
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if ($rep['project_id']): ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                        <input type="hidden" name="action" value="delete_project">
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this reported project?')">
                            <i class="fas fa-trash me-1"></i>Remove Project
                        </button>
                    </form>
                    <?php endif; ?>

                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                        <input type="hidden" name="action" value="delete_report">
                        <button type="submit" class="btn btn-sm btn-outline-secondary btn-delete" title="Delete Report Entry">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
