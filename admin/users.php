<?php
/**
 * NexaWork - Admin User Management & Audit
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'User Management';
$sidebarRole = 'admin';

// Handle Actions
if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_active') {
        db()->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != "admin"')->execute([$userId]);
        setFlash('success', 'User active status toggled.');
        redirect(baseUrl('admin/users.php'));
    } elseif ($action === 'toggle_verify') {
        db()->prepare('UPDATE users SET is_verified = NOT is_verified WHERE id = ?')->execute([$userId]);
        setFlash('success', 'User verification badge updated.');
        redirect(baseUrl('admin/users.php'));
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM users WHERE id = ? AND role != "admin"')->execute([$userId]);
        setFlash('info', 'User permanently deleted.');
        redirect(baseUrl('admin/users.php'));
    }
}

// Search and Filters
$search = trim($_GET['search'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = (int)($_GET['page'] ?? 1);

$where = ['u.role != "admin"'];
$params = [];

if (!empty($search)) {
    $where[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($role) && in_array($role, ['client', 'freelancer'])) {
    $where[] = 'u.role = ?';
    $params[] = $role;
}

if ($status === 'active') {
    $where[] = 'u.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'u.is_active = 0';
}

$whereSql = implode(' AND ', $where);

// Count total
$countStmt = db()->prepare("SELECT COUNT(*) FROM users u WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $page, 10);

// Fetch users
$sql = "SELECT u.*, 
        fp.title as freelancer_title, fp.avg_rating, fp.completed_projects, fp.total_earnings,
        cp.company_name, cp.projects_posted, cp.total_spent
        FROM users u 
        LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id 
        LEFT JOIN client_profiles cp ON u.id = cp.user_id 
        WHERE {$whereSql} 
        ORDER BY u.created_at DESC 
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-0">User Management</h4>
        <p class="text-muted mb-0">Audit, verify, and moderate clients and freelancers on the platform.</p>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="client" <?= $role === 'client' ? 'selected' : '' ?>>Clients</option>
                    <option value="freelancer" <?= $role === 'freelancer' ? 'selected' : '' ?>>Freelancers</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="<?= baseUrl('admin/users.php') ?>" class="btn btn-outline-secondary" title="Reset"><i class="fas fa-undo"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table Card -->
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Verified</th>
                    <th>Activity Metrics</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No users found matching criteria.</td>
                </tr>
                <?php endif; ?>

                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= getAvatarUrl($u['avatar']) ?>" class="rounded-circle" width="40" height="40" alt="Avatar">
                            <div>
                                <h6 class="fw-bold mb-0"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></h6>
                                <small class="text-muted"><?= e($u['email']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-<?= $u['role'] === 'freelancer' ? 'primary' : 'info' ?> rounded-pill">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" class="d-inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="action" value="toggle_verify">
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" title="Click to toggle verified badge">
                                <?php if ($u['is_verified']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success"><i class="fas fa-check-circle me-1"></i>Verified</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary"><i class="fas fa-times-circle me-1"></i>Unverified</span>
                                <?php endif; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <small class="d-block">
                            <?php if ($u['role'] === 'freelancer'): ?>
                                ⭐ <?= number_format((float)$u['avg_rating'], 1) ?> | Completed: <?= (int)$u['completed_projects'] ?> | Earned: <?= formatMoney((float)$u['total_earnings']) ?>
                            <?php else: ?>
                                Projects Posted: <?= (int)$u['projects_posted'] ?> | Spent: <?= formatMoney((float)$u['total_spent']) ?>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td>
                        <?= $u['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Suspended</span>' ?>
                    </td>
                    <td><?= formatDate($u['created_at']) ?></td>
                    <td class="text-end">
                        <!-- Toggle Active -->
                        <form method="POST" class="d-inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="action" value="toggle_active">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>" title="<?= $u['is_active'] ? 'Suspend User' : 'Activate User' ?>">
                                <i class="fas fa-<?= $u['is_active'] ? 'user-slash' : 'user-check' ?>"></i>
                            </button>
                        </form>

                        <!-- Delete -->
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user permanently?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer bg-white py-3">
        <?= renderPagination($pagination, baseUrl('admin/users.php?search=' . urlencode($search) . '&role=' . urlencode($role) . '&status=' . urlencode($status))) ?>
    </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
