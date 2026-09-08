<?php
/**
 * NexaWork - Client Analytics & Insights (Client Server)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$pageTitle = 'Analytics & Hiring Insights';
$sidebarRole = 'client';
$userId = currentUserId();

// Summary Metrics
$stmt = db()->prepare('SELECT COUNT(*) FROM projects WHERE client_id = ?');
$stmt->execute([$userId]);
$totalProjects = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM bids b JOIN projects p ON b.project_id = p.id WHERE p.client_id = ?');
$stmt->execute([$userId]);
$totalBidsReceived = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM contracts WHERE client_id = ? AND status = "active"');
$stmt->execute([$userId]);
$activeContractsCount = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payer_id = ?');
$stmt->execute([$userId]);
$totalSpending = (float) $stmt->fetchColumn();

// Monthly project data for chart
$stmt = db()->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM projects WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month ORDER BY month");
$stmt->execute([$userId]);
$monthlyProjects = $stmt->fetchAll();

// Monthly spending for chart
$stmt = db()->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total
    FROM payments WHERE payer_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month ORDER BY month");
$stmt->execute([$userId]);
$monthlySpending = $stmt->fetchAll();

// Bids per project
$stmt = db()->prepare('SELECT p.title, COUNT(b.id) as bid_count FROM projects p LEFT JOIN bids b ON p.id = b.project_id WHERE p.client_id = ? GROUP BY p.id ORDER BY bid_count DESC LIMIT 6');
$stmt->execute([$userId]);
$bidsPerProject = $stmt->fetchAll();

// Category distribution
$stmt = db()->prepare('SELECT category, COUNT(*) as count FROM projects WHERE client_id = ? GROUP BY category ORDER BY count DESC');
$stmt->execute([$userId]);
$catBreakdown = $stmt->fetchAll();

// Project performance table
$stmt = db()->prepare('SELECT p.*, (SELECT COUNT(*) FROM bids WHERE project_id = p.id) as bid_count, c.status as contract_status
    FROM projects p
    LEFT JOIN contracts c ON c.project_id = p.id
    WHERE p.client_id = ?
    ORDER BY p.created_at DESC');
$stmt->execute([$userId]);
$projectPerformance = $stmt->fetchAll();

$extraJs = '<script>
document.addEventListener("DOMContentLoaded", function() {
    createChart("projectsChart", "bar",
        ' . json_encode(array_column($monthlyProjects, 'month')) . ',
        [{ label: "Projects Posted", data: ' . json_encode(array_map('intval', array_column($monthlyProjects, 'count'))) . ', backgroundColor: "#8B5CF6" }]
    );
    createChart("spendingChart", "line",
        ' . json_encode(array_column($monthlySpending, 'month')) . ',
        [{ label: "Spending (₹)", data: ' . json_encode(array_map('floatval', array_column($monthlySpending, 'total'))) . ', borderColor: "#4F7CFF", fill: true, backgroundColor: "rgba(79, 124, 255, 0.15)" }]
    );
    createChart("bidsChart", "bar",
        ' . json_encode(array_column($bidsPerProject, 'title')) . ',
        [{ label: "Proposals Received", data: ' . json_encode(array_map('intval', array_column($bidsPerProject, 'bid_count'))) . ', backgroundColor: "#F59E0B" }]
    );
    createChart("categoryChart", "doughnut",
        ' . json_encode(array_column($catBreakdown, 'category')) . ',
        [{ label: "Projects", data: ' . json_encode(array_map('intval', array_column($catBreakdown, 'count'))) . ', backgroundColor: ["#4F7CFF", "#8B5CF6", "#22D3EE", "#22C55E", "#F59E0B"] }]
    );
});
</script>';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-chart-line text-primary me-2"></i>Analytics & Hiring Insights</h4>
        <p class="text-muted mb-0">Track your project postings, proposal velocity, expenditure trends, and hiring performance.</p>
    </div>
    <a href="<?= baseUrl('client/post-project.php') ?>" class="btn btn-primary fw-bold">
        <i class="fas fa-plus-circle me-1"></i>Post New Project
    </a>
</div>

<!-- Key Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-gradient-primary">
            <div class="stat-value"><?= number_format($totalProjects) ?></div>
            <div class="stat-label">Projects Posted</div>
            <i class="fas fa-folder-open stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-warning">
            <div class="stat-value"><?= number_format($totalBidsReceived) ?></div>
            <div class="stat-label">Proposals Received</div>
            <i class="fas fa-gavel stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-info">
            <div class="stat-value"><?= number_format($activeContractsCount) ?></div>
            <div class="stat-label">Active Contracts</div>
            <i class="fas fa-file-contract stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-success">
            <div class="stat-value"><?= formatMoney($totalSpending) ?></div>
            <div class="stat-label">Total Expenditure</div>
            <i class="fas fa-indian-rupee-sign stat-icon"></i>
        </div>
    </div>
</div>

<!-- Charts Grid (Row 1) -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-transparent py-3 fw-bold">
                <i class="fas fa-calendar-alt me-2 text-primary"></i>Projects Posted (Monthly)
            </div>
            <div class="card-body">
                <canvas id="projectsChart" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-transparent py-3 fw-bold">
                <i class="fas fa-wallet me-2 text-success"></i>Spending & Escrow Flow (Monthly ₹)
            </div>
            <div class="card-body">
                <canvas id="spendingChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Charts Grid (Row 2) -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-transparent py-3 fw-bold">
                <i class="fas fa-paper-plane me-2 text-warning"></i>Proposal Inflow per Project
            </div>
            <div class="card-body">
                <canvas id="bidsChart" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-transparent py-3 fw-bold">
                <i class="fas fa-pie-chart me-2 text-info"></i>Projects by Category
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="width: 260px; height: 260px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Project Performance Summary Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent py-3 fw-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-table me-2 text-primary"></i>Project Hiring Breakdown</span>
        <a href="<?= baseUrl('client/projects.php') ?>" class="btn btn-sm btn-outline-primary fs-7">Manage Projects</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light fs-7">
                <tr>
                    <th>Project Title</th>
                    <th>Category</th>
                    <th>Budget</th>
                    <th>Proposals Inflow</th>
                    <th>Posted On</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projectPerformance as $p): ?>
                <tr>
                    <td>
                        <a href="<?= baseUrl('project-detail.php?id=' . $p['id']) ?>" class="fw-bold text-decoration-none text-dark dark-mode-text">
                            <?= e($p['title']) ?>
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fs-7">
                            <?= e($p['category']) ?>
                        </span>
                    </td>
                    <td><span class="fw-bold text-success"><?= formatMoney((float)$p['budget']) ?></span></td>
                    <td>
                        <span class="badge bg-light text-dark border px-3 py-1 rounded-pill fw-semibold">
                            <i class="fas fa-gavel text-warning me-1"></i><?= (int)$p['bid_count'] ?> Proposals
                        </span>
                    </td>
                    <td><small class="text-muted"><?= formatDate($p['created_at']) ?></small></td>
                    <td><?= getStatusBadge($p['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
