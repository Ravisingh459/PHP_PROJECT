<?php
/**
 * FreelanceHub - Freelancer Earnings Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$pageTitle = 'Earnings';
$sidebarRole = 'freelancer';
$userId = currentUserId();
$profile = getFreelancerProfile($userId);

$stmt = db()->prepare('SELECT py.*, p.title as project_title FROM payments py JOIN contracts c ON py.contract_id = c.id JOIN projects p ON c.project_id = p.id WHERE py.payee_id = ? ORDER BY py.created_at DESC');
$stmt->execute([$userId]);
$payments = $stmt->fetchAll();

$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payee_id = ? AND status = "released"');
$stmt->execute([$userId]);
$totalEarned = (float) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payee_id = ? AND status = "escrow"');
$stmt->execute([$userId]);
$inEscrow = (float) $stmt->fetchColumn();

$stmt = db()->prepare("SELECT DATE_FORMAT(released_at, '%Y-%m') as month, SUM(amount) as total FROM payments WHERE payee_id = ? AND status = 'released' AND released_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month");
$stmt->execute([$userId]);
$monthlyEarnings = $stmt->fetchAll();

$extraJs = '<script>document.addEventListener("DOMContentLoaded", function() {
    createChart("earningsChart", "bar", ' . json_encode(array_column($monthlyEarnings, 'month')) . ',
        [{ label: "Earnings (₹)", data: ' . json_encode(array_map('floatval', array_column($monthlyEarnings, 'total'))) . ', backgroundColor: "#10B981" }]
    );
});</script>';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-wallet text-success me-2"></i>Earnings & Payout Center</h4>
        <p class="text-muted mb-0">Track your completed milestone payouts, escrow balance, and process bank withdrawals.</p>
    </div>
    <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#withdrawModal">
        <i class="fas fa-arrow-alt-circle-down me-1"></i>Request Withdrawal
    </button>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="stat-card bg-gradient-success"><div class="stat-value"><?= formatMoney($totalEarned) ?></div><div class="stat-label">Total Earned & Withdrawable</div><i class="fas fa-indian-rupee-sign stat-icon"></i></div></div>
    <div class="col-md-4"><div class="stat-card bg-gradient-warning"><div class="stat-value"><?= formatMoney($inEscrow) ?></div><div class="stat-label">In Pending Escrow</div><i class="fas fa-lock stat-icon"></i></div></div>
    <div class="col-md-4"><div class="stat-card bg-gradient-info"><div class="stat-value"><?= $profile['completed_projects'] ?? 0 ?></div><div class="stat-label">Projects Completed</div><i class="fas fa-check stat-icon"></i></div></div>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-transparent fw-bold py-3"><i class="fas fa-chart-line text-success me-2"></i>Monthly Revenue Curve</div>
    <div class="card-body"><canvas id="earningsChart" height="200"></canvas></div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent fw-bold py-3 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-history text-primary me-2"></i>Payout & Escrow History</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light"><tr><th>Date</th><th>Project</th><th>Amount</th><th>Status</th><th class="text-end">Invoice</th></tr></thead>
            <tbody>
                <?php if (empty($payments)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No payout history recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $py): ?>
                    <tr>
                        <td><?= formatDateTime($py['created_at']) ?></td>
                        <td class="fw-bold text-dark"><?= e($py['project_title']) ?></td>
                        <td class="fw-bold text-success"><?= formatMoney((float)$py['amount']) ?></td>
                        <td><?= getStatusBadge($py['status']) ?></td>
                        <td class="text-end">
                            <a href="<?= baseUrl('invoice.php?id=' . $py['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file-invoice me-1"></i>Invoice
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Request Withdrawal Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-building-columns text-success me-2"></i>Request Bank / UPI Payout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form onsubmit="event.preventDefault(); alert('Withdrawal request submitted! Processing via NEFT/UPI within 24 hours.'); location.reload();">
                <div class="modal-body p-4">
                    <div class="alert alert-info small mb-3">
                        Available Balance: <strong><?= formatMoney($totalEarned) ?></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payout Method</label>
                        <select class="form-select" required>
                            <option value="upi">Instant UPI Transfer (VPA)</option>
                            <option value="neft">Direct Bank NEFT / IMPS Transfer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">UPI ID / Bank Account Details</label>
                        <input type="text" class="form-control" placeholder="e.g. username@upi or A/C Number & IFSC Code" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Withdrawal Amount (₹)</label>
                        <input type="number" class="form-control" max="<?= (float)$totalEarned ?>" min="100" value="<?= min(5000, (float)$totalEarned) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4"><i class="fas fa-paper-plane me-1"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';

