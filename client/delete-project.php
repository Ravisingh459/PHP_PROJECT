<?php
/**
 * FreelanceHub - Client Delete Project
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$userId = currentUserId();
$projectId = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM projects WHERE id = ? AND client_id = ? AND status = "open"');
$stmt->execute([$projectId, $userId]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('danger', 'Cannot delete this project.');
    redirect(baseUrl('client/projects.php'));
}

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$projectId]);
    setFlash('success', 'Project deleted successfully.');
    redirect(baseUrl('client/projects.php'));
}

$pageTitle = 'Delete Project';
$sidebarRole = 'client';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
    <div class="card-body text-center p-5">
        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
        <h4>Delete Project?</h4>
        <p>Are you sure you want to delete "<strong><?= e($project['title']) ?></strong>"? This action cannot be undone.</p>
        <form method="POST" class="d-inline">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-danger">Yes, Delete</button>
            <a href="<?= baseUrl('client/projects.php') ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
