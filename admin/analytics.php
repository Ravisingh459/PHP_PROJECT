<?php
/**
 * NexaWork - Admin Analytics & Reporting Center
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// CSV Export Request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="nexawork_analytics_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Report', 'NexaWork Financial & Performance Summary']);
    fputcsv($output, ['Generated Date', date('Y-m-d H:i:s')]);
    fputcsv($output, []);

    // Revenue metrics
    $totalRevenue = (float)db()->query("SELECT SUM(amount) FROM payments WHERE status = 'released'")->fetchColumn();
    $escrowVolume = (float)db()->query("SELECT SUM(amount) FROM payments WHERE status = 'escrow'")->fetchColumn();
    $totalUsers = (int)db()->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
    $totalProjects = (int)db()->query("SELECT COUNT(*) FROM projects")->fetchColumn();

    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Released Revenue (INR)', number_format($totalRevenue, 2)]);
    fputcsv($output, ['Current Escrow Locked (INR)', number_format($escrowVolume, 2)]);
    fputcsv($output, ['Total Active Users', $totalUsers]);
    fputcsv($output, ['Total Projects Posted', $totalProjects]);
    fputcsv($output, []);

    // Category distribution
    fputcsv($output, ['Category', 'Projects Count']);
    $catData = db()->query("SELECT category, COUNT(*) as count FROM projects GROUP BY category ORDER BY count DESC")->fetchAll();
    foreach ($catData as $c) {
        fputcsv($output, [$c['category'], $c['count']]);
    }

    fclose($output);
    exit;
}

$pageTitle = 'Analytics & Reports';
$sidebarRole = 'admin';

// Metrics
$totalRevenue = (float)db()->query("SELECT SUM(amount) FROM payments WHERE status = 'released'")->fetchColumn();
$escrowVolume = (float)db()->query("SELECT SUM(amount) FROM payments WHERE status = 'escrow'")->fetchColumn();
$totalUsers = (int)db()->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$totalProjects = (int)db()->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalBids = (int)db()->query("SELECT COUNT(*) FROM bids")->fetchColumn();

// Chart Data
$earnings = db()->query("SELECT DATE_FORMAT(released_at, '%b %Y') as month, SUM(amount) as total FROM payments WHERE status = 'released' AND released_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY released_at")->fetchAll();
$categoryBreakdown = db()->query("SELECT category, COUNT(*) as count FROM projects GROUP BY category ORDER BY count DESC")->fetchAll();
$bidStats = db()->query("SELECT status, COUNT(*) as count FROM bids GROUP BY status")->fetchAll();

// Top Earners Table
$topEarners = db()->query("
    SELECT fp.*, u.first_name, u.last_name, u.email, u.avatar
    FROM freelancer_profiles fp
    JOIN users u ON fp.user_id = u.id
    ORDER BY fp.total_earnings DESC LIMIT 5
")->fetchAll();

$categoryLabels = array_column($categoryBreakdown, 'category');
$categoryCounts = array_map('intval', array_column($categoryBreakdown, 'count'));

$extraJs = '<script>document.addEventListener("DOMContentLoaded", function() {
    createChart("earningsChart", "line", ' . json_encode(array_column($earnings, 'month')) . ',
        [{ label: "Revenue Released (₹)", data: ' . json_encode(array_map('floatval', array_column($earnings, 'total'))) . ', borderColor: "#4F7CFF", fill: true, backgroundColor: "rgba(79,124,255,0.15)", tension: 0.3 }]
    );
    createChart("categoryChart", "doughnut", ' . json_encode($categoryLabels) . ',
        [{ data: ' . json_encode($categoryCounts) . ', backgroundColor: ["#4F7CFF","#8B5CF6","#22D3EE","#F59E0B","#22C55E","#3B62D9","#94A3B8"] }]
    );
});</script>';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-0">Platform Analytics & Intelligence</h4>
        <p class="text-muted mb-0">Real-time marketplace revenue, escrow security metrics, and user growth breakdown.</p>
    </div>
    <div>
        <a href="<?= baseUrl('admin/analytics.php?export=csv') ?>" class="btn btn-outline-success fw-bold"><i class="fas fa-file-csv me-2"></i> Export Financial Report</a>
    </div>
</div>

<!-- Stats Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 shadow-sm border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3 text-success fs-4">
                    <i class="fas fa-indian-rupee-sign"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Revenue Released</div>
                    <h4 class="fw-bold mb-0"><?= formatMoney($totalRevenue) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 shadow-sm border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3 text-warning fs-4">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <div class="text-muted small">Escrow Locked Volume</div>
                    <h4 class="fw-bold mb-0"><?= formatMoney($escrowVolume) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 shadow-sm border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3 text-primary fs-4">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Users</div>
                    <h4 class="fw-bold mb-0"><?= number_format($totalUsers) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 shadow-sm border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3 text-info fs-4">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Projects</div>
                    <h4 class="fw-bold mb-0"><?= number_format($totalProjects) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent fw-bold py-3">
                <i class="fas fa-chart-line me-2 text-success"></i>Monthly Released Revenue Curve
            </div>
            <div class="card-body">
                <canvas id="earningsChart" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent fw-bold py-3">
                <i class="fas fa-chart-pie me-2 text-primary"></i>Projects by Category
            </div>
            <div class="card-body">
                <canvas id="categoryChart" height="280"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Performers Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent fw-bold py-3 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-trophy me-2 text-warning"></i>Top Freelance Earners</span>
        <a href="<?= baseUrl('admin/users.php?role=freelancer') ?>" class="btn btn-sm btn-link text-decoration-none">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Freelancer</th>
                    <th>Title</th>
                    <th>Rating</th>
                    <th>Completed Projects</th>
                    <th>Total Earnings</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topEarners as $f): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= getAvatarUrl($f['avatar']) ?>" class="rounded-circle" width="36" height="36" alt="Avatar">
                            <div>
                                <a href="<?= baseUrl('freelancer-profile.php?id=' . $f['user_id']) ?>" class="fw-bold text-dark text-decoration-none" target="_blank"><?= e($f['first_name'] . ' ' . $f['last_name']) ?></a>
                                <div class="small text-muted"><?= e($f['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($f['title'] ?? 'Freelancer') ?></td>
                    <td>⭐ <?= number_format((float)$f['avg_rating'], 1) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= (int)$f['completed_projects'] ?></span></td>
                    <td class="fw-bold text-success"><?= formatMoney((float)$f['total_earnings']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
