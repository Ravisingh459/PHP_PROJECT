<?php
/**
 * FreelanceHub - Admin Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Admin Dashboard';
$sidebarRole = 'admin';

$stats = [
    'users' => (int) db()->query('SELECT COUNT(*) FROM users WHERE role != "admin"')->fetchColumn(),
    'freelancers' => (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "freelancer"')->fetchColumn(),
    'clients' => (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "client"')->fetchColumn(),
    'projects' => (int) db()->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'revenue' => (float) db()->query('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = "released"')->fetchColumn(),
    'pending_reports' => (int) db()->query('SELECT COUNT(*) FROM reports WHERE status = "pending"')->fetchColumn(),
    'open_disputes' => (int) db()->query('SELECT COUNT(*) FROM disputes WHERE status = "open"')->fetchColumn(),
];

// Chart data
$monthlyUsers = db()->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM users WHERE role != 'admin' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month")->fetchAll();
$monthlyProjects = db()->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM projects WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month")->fetchAll();

$extraJs = '<script>document.addEventListener("DOMContentLoaded", function() {
    createChart("usersChart", "line", ' . json_encode(array_column($monthlyUsers, 'month')) . ',
        [{ label: "New Users", data: ' . json_encode(array_map('intval', array_column($monthlyUsers, 'count'))) . ', borderColor: "#4F7CFF", fill: false }]
    );
    createChart("projectsChart", "bar", ' . json_encode(array_column($monthlyProjects, 'month')) . ',
        [{ label: "Projects", data: ' . json_encode(array_map('intval', array_column($monthlyProjects, 'count'))) . ', backgroundColor: "#22C55E" }]
    );
});</script>';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="fw-bold mb-4">Admin Dashboard</h4>

<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="stat-card bg-gradient-primary"><div class="stat-value"><?= $stats['users'] ?></div><div class="stat-label">Total Users</div><i class="fas fa-users stat-icon"></i></div></div>
    <div class="col-md-3"><div class="stat-card bg-gradient-success"><div class="stat-value"><?= $stats['freelancers'] ?></div><div class="stat-label">Freelancers</div><i class="fas fa-laptop-code stat-icon"></i></div></div>
    <div class="col-md-3"><div class="stat-card bg-gradient-info"><div class="stat-value"><?= $stats['clients'] ?></div><div class="stat-label">Clients</div><i class="fas fa-building stat-icon"></i></div></div>
    <div class="col-md-3"><div class="stat-card bg-gradient-warning"><div class="stat-value"><?= $stats['projects'] ?></div><div class="stat-label">Projects</div><i class="fas fa-folder stat-icon"></i></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="stat-card bg-gradient-success"><div class="stat-value"><?= formatMoney($stats['revenue']) ?></div><div class="stat-label">Total Revenue</div><i class="fas fa-indian-rupee-sign stat-icon"></i></div></div>
    <div class="col-md-4"><div class="stat-card bg-gradient-danger"><div class="stat-value"><?= $stats['pending_reports'] ?></div><div class="stat-label">Pending Reports</div><i class="fas fa-flag stat-icon"></i></div></div>
    <div class="col-md-4"><div class="stat-card bg-gradient-warning"><div class="stat-value"><?= $stats['open_disputes'] ?></div><div class="stat-label">Open Disputes</div><i class="fas fa-gavel stat-icon"></i></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-6"><div class="card"><div class="card-header">User Growth</div><div class="card-body"><canvas id="usersChart" height="250"></canvas></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header">Projects Posted</div><div class="card-body"><canvas id="projectsChart" height="250"></canvas></div></div></div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
