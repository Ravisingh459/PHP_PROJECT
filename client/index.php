<?php
/**
 * FreelanceHub - Client Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$userId = currentUserId();
$pageTitle = 'Client Dashboard';
$sidebarRole = 'client';

// Dashboard stats
$stats = [];
$stmt = db()->prepare('SELECT COUNT(*) FROM projects WHERE client_id = ?');
$stmt->execute([$userId]);
$stats['projects'] = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM projects WHERE client_id = ? AND status = "open"');
$stmt->execute([$userId]);
$stats['open'] = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM contracts WHERE client_id = ? AND status = "active"');
$stmt->execute([$userId]);
$stats['active_contracts'] = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM bids b JOIN projects p ON b.project_id = p.id WHERE p.client_id = ? AND b.status = "pending"');
$stmt->execute([$userId]);
$stats['pending_bids'] = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payer_id = ?');
$stmt->execute([$userId]);
$stats['total_spent'] = (float) $stmt->fetchColumn();

// Recent projects
$stmt = db()->prepare('SELECT p.*, (SELECT COUNT(*) FROM bids WHERE project_id = p.id) as bid_count
    FROM projects p WHERE client_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$userId]);
$recentProjects = $stmt->fetchAll();

// Recent bids
$stmt = db()->prepare('SELECT b.*, p.title as project_title, u.first_name, u.last_name, u.avatar
    FROM bids b
    JOIN projects p ON b.project_id = p.id
    JOIN users u ON b.freelancer_id = u.id
    WHERE p.client_id = ? ORDER BY b.created_at DESC LIMIT 5');
$stmt->execute([$userId]);
$recentBids = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<h4 class="fw-bold mb-4">Dashboard Overview</h4>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-gradient-primary">
            <div class="stat-value"><?= $stats['projects'] ?></div>
            <div class="stat-label">Total Projects</div>
            <i class="fas fa-folder-open stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-success">
            <div class="stat-value"><?= $stats['open'] ?></div>
            <div class="stat-label">Open Projects</div>
            <i class="fas fa-door-open stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-warning">
            <div class="stat-value"><?= $stats['pending_bids'] ?></div>
            <div class="stat-label">Pending Bids</div>
            <i class="fas fa-gavel stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-info">
            <div class="stat-value"><?= formatMoney($stats['total_spent']) ?></div>
            <div class="stat-label">Total Spent</div>
            <i class="fas fa-indian-rupee-sign stat-icon"></i>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>Recent Projects</span>
                <a href="<?= baseUrl('client/projects.php') ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Project</th><th>Budget</th><th>Bids</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentProjects as $p): ?>
                        <tr>
                            <td><a href="<?= baseUrl('project-detail.php?id=' . $p['id']) ?>"><?= e($p['title']) ?></a></td>
                            <td><?= formatMoney((float)$p['budget']) ?></td>
                            <td><?= $p['bid_count'] ?></td>
                            <td><?= getStatusBadge($p['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Recent Bids</div>
            <div class="card-body">
                <?php foreach ($recentBids as $bid): ?>
                <div class="d-flex align-items-center mb-3">
                    <img src="<?= getAvatarUrl($bid['avatar']) ?>" class="rounded-circle me-2" width="40" height="40">
                    <div class="flex-grow-1">
                        <strong><?= e($bid['first_name'] . ' ' . $bid['last_name']) ?></strong>
                        <small class="d-block text-muted"><?= e($bid['project_title']) ?></small>
                    </div>
                    <div class="text-end">
                        <strong><?= formatMoney((float)$bid['proposed_budget']) ?></strong>
                        <?= getStatusBadge($bid['status']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
