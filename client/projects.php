<?php
/**
 * FreelanceHub - Client Manage Projects
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$pageTitle = 'My Projects';
$sidebarRole = 'client';
$userId = currentUserId();

$stmt = db()->prepare('SELECT p.*, (SELECT COUNT(*) FROM bids WHERE project_id = p.id) as bid_count
    FROM projects p WHERE client_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$projects = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">My Projects</h4>
    <a href="<?= baseUrl('client/post-project.php') ?>" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Post New</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Budget</th>
                    <th>Bids</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $p): ?>
                <tr>
                    <td><a href="<?= baseUrl('project-detail.php?id=' . $p['id']) ?>"><?= e($p['title']) ?></a></td>
                    <td><?= e($p['category']) ?></td>
                    <td><?= formatMoney((float)$p['budget']) ?></td>
                    <td><a href="<?= baseUrl('client/view-bids.php?id=' . $p['id']) ?>"><?= $p['bid_count'] ?> bids</a></td>
                    <td><?= formatDate($p['deadline']) ?></td>
                    <td><?= getStatusBadge($p['status']) ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= baseUrl('client/edit-project.php?id=' . $p['id']) ?>" class="btn btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php if ($p['status'] === 'open'): ?>
                            <a href="<?= baseUrl('client/delete-project.php?id=' . $p['id']) ?>" class="btn btn-outline-danger btn-delete" title="Delete"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
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
