<?php
/**
 * NexaWork - Client Payments & Escrow Ledger (Client Server)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$pageTitle = 'Payments & Escrow Ledger';
$sidebarRole = 'client';
$userId = currentUserId();

// Fetch transaction history
$stmt = db()->prepare('SELECT py.*, p.title as project_title, p.category, u.first_name as fl_fname, u.last_name as fl_lname, u.avatar as fl_avatar
    FROM payments py
    JOIN contracts c ON py.contract_id = c.id
    JOIN projects p ON c.project_id = p.id
    JOIN users u ON py.payee_id = u.id
    WHERE py.payer_id = ?
    ORDER BY py.created_at DESC');
$stmt->execute([$userId]);
$payments = $stmt->fetchAll();

// Spending metrics
$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payer_id = ? AND status = "escrow"');
$stmt->execute([$userId]);
$inEscrow = (float) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payer_id = ? AND status = "released"');
$stmt->execute([$userId]);
$totalReleased = (float) $stmt->fetchColumn();

$totalSpent = $inEscrow + $totalReleased;

// Monthly analytics chart data
$stmt = db()->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total FROM payments WHERE payer_id = ? GROUP BY month ORDER BY month");
$stmt->execute([$userId]);
$monthlySpending = $stmt->fetchAll();

$extraJs = '<script>document.addEventListener("DOMContentLoaded", function() {
    createChart("spendingChart", "bar", ' . json_encode(array_column($monthlySpending, 'month')) . ',
        [{ label: "Total Spending (₹)", data: ' . json_encode(array_map('floatval', array_column($monthlySpending, 'total'))) . ', backgroundColor: "#4F7CFF" }]
    );
});</script>';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-wallet text-primary me-2"></i>Payments & Escrow Ledger</h4>
        <p class="text-muted mb-0">Track all project deposits, escrow balances, and released payments.</p>
    </div>
    <a href="<?= baseUrl('client/contracts.php') ?>" class="btn btn-outline-primary fw-semibold">
        <i class="fas fa-file-contract me-1"></i>View Contracts
    </a>
</div>

<!-- Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-gradient-warning">
            <div class="stat-value"><?= formatMoney($inEscrow) ?></div>
            <div class="stat-label">In Escrow Protection</div>
            <i class="fas fa-lock stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-success">
            <div class="stat-value"><?= formatMoney($totalReleased) ?></div>
            <div class="stat-label">Released to Freelancers</div>
            <i class="fas fa-check-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-info">
            <div class="stat-value"><?= formatMoney($totalSpent) ?></div>
            <div class="stat-label">Total Spend (Lifetime)</div>
            <i class="fas fa-indian-rupee-sign stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-primary">
            <div class="stat-value"><?= count($payments) ?></div>
            <div class="stat-label">Total Transactions</div>
            <i class="fas fa-receipt stat-icon"></i>
        </div>
    </div>
</div>

<!-- 100% Escrow Protection Guarantee Banner -->
<div class="card bg-primary text-white p-4 mb-4 shadow-sm border-0" style="background: linear-gradient(135deg, #4F7CFF, #8B5CF6) !important;">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white bg-opacity-20 rounded-circle p-3">
            <i class="fas fa-shield-alt fa-2x text-warning"></i>
        </div>
        <div>
            <h5 class="fw-bold mb-1">100% Escrow Protection Guaranteed</h5>
            <p class="mb-0 text-white-50">Your funds remain safely deposited in NexaWork Escrow until you review, verify, and approve the completed project milestones.</p>
        </div>
    </div>
</div>

<!-- Monthly Spending Chart & Transactions -->
<div class="row g-4 mb-4">
    <div class="col-lg-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-transparent py-3 fw-bold">
                <i class="fas fa-chart-bar me-2 text-primary"></i>Monthly Expenditure Overview (₹)
            </div>
            <div class="card-body">
                <canvas id="spendingChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Transaction History Log -->
<div class="card shadow-sm">
    <div class="card-header bg-transparent py-3 fw-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list-alt me-2 text-primary"></i>Transaction History Log (<?= count($payments) ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light fs-7">
                <tr>
                    <th>Date & Time</th>
                    <th>Project</th>
                    <th>Payee Freelancer</th>
                    <th>Amount (₹)</th>
                    <th>Reference Code</th>
                    <th>Escrow Status</th>
                    <th class="text-end">Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">No payment transactions recorded yet.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($payments as $py): ?>
                    <tr>
                        <td>
                            <span class="fw-semibold text-dark dark-mode-text"><?= formatDate($py['created_at']) ?></span>
                            <small class="d-block text-muted"><?= date('h:i A', strtotime($py['created_at'])) ?></small>
                        </td>
                        <td>
                            <strong class="text-primary"><?= e($py['project_title']) ?></strong>
                            <small class="d-block text-muted"><?= e($py['category'] ?? 'Software Project') ?></small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= getAvatarUrl($py['fl_avatar'] ?? '') ?>" class="rounded-circle" width="28" height="28" style="object-fit:cover;">
                                <span class="fs-7 fw-semibold"><?= e($py['fl_fname'] . ' ' . $py['fl_lname']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold text-success fs-6"><?= formatMoney((float)$py['amount']) ?></span>
                        </td>
                        <td>
                            <code class="bg-light px-2 py-1 rounded text-dark border fs-8 dark-mode-card"><?= e($py['transaction_ref'] ?? 'TXN-ESC-' . $py['id']) ?></code>
                        </td>
                        <td><?= getStatusBadge($py['status']) ?></td>
                        <td class="text-end">
                            <a href="<?= baseUrl('invoice.php?id=' . $py['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="Tax Invoice">
                                <i class="fas fa-file-invoice me-1"></i>Invoice
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#receiptModal<?= $py['id'] ?>" title="View Receipt">
                                <i class="fas fa-receipt me-1"></i>Receipt
                            </button>


                            <!-- Receipt Modal -->
                            <div class="modal fade text-start" id="receiptModal<?= $py['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold"><i class="fas fa-receipt text-primary me-2"></i>Official Payment Receipt</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="text-center mb-4 pb-3 border-bottom">
                                                <h4 class="fw-bold text-primary mb-1"><?= e(APP_NAME) ?></h4>
                                                <small class="text-muted">GST Registered Payment Invoice</small>
                                            </div>

                                            <div class="d-flex justify-content-between mb-2 fs-7">
                                                <span class="text-muted">Transaction Ref:</span>
                                                <code class="fw-bold"><?= e($py['transaction_ref'] ?? 'TXN-ESC-' . $py['id']) ?></code>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 fs-7">
                                                <span class="text-muted">Payment Date:</span>
                                                <strong><?= formatDateTime($py['created_at']) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 fs-7">
                                                <span class="text-muted">Project:</span>
                                                <strong><?= e($py['project_title']) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 fs-7">
                                                <span class="text-muted">Payee Freelancer:</span>
                                                <strong><?= e($py['fl_fname'] . ' ' . $py['fl_lname']) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3 fs-7">
                                                <span class="text-muted">Payment Method / Escrow:</span>
                                                <span class="badge bg-success">NexaWork Escrow</span>
                                            </div>

                                            <hr>

                                            <div class="d-flex justify-content-between align-items-center py-2">
                                                <span class="fw-bold fs-6">Amount Paid:</span>
                                                <span class="fw-bold text-success fs-4"><?= formatMoney((float)$py['amount']) ?></span>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print Receipt</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
