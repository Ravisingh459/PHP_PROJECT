<?php
/**
 * FreelanceHub - Admin Manage Projects
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Manage Projects';
$sidebarRole = 'admin';

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $projectId = (int)($_POST['project_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$projectId]);
        setFlash('info', 'Project deleted.');
    } elseif ($action === 'change_status') {
        db()->prepare('UPDATE projects SET status = ? WHERE id = ?')->execute([$_POST['status'], $projectId]);
        setFlash('success', 'Status updated.');
    }
}

$projects = db()->query('SELECT p.*, u.first_name, u.last_name, (SELECT COUNT(*) FROM bids WHERE project_id = p.id) as bid_count FROM projects p JOIN users u ON p.client_id = u.id ORDER BY p.created_at DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<h4 class="fw-bold mb-4">Manage Projects</h4>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Title</th><th>Client</th><th>Budget</th><th>Bids</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($projects as $p): ?>
                <tr>
                    <td><a href="<?= baseUrl('project-detail.php?id=' . $p['id']) ?>"><?= e($p['title']) ?></a></td>
                    <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
                    <td><?= formatMoney((float)$p['budget']) ?></td>
                    <td><?= $p['bid_count'] ?></td>
                    <td><?= getStatusBadge($p['status']) ?></td>
                    <td>
                        <form method="POST" class="d-inline-flex gap-1">
                            <?= csrfField() ?>
                            <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                            <select name="status" class="form-select form-select-sm" style="width:auto;">
                                <?php foreach (['open','in_progress','completed','cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="action" value="change_status" class="btn btn-sm btn-outline-primary">Update</button>
                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
