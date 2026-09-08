<?php
/**
 * NexaWork - Freelancer Track Bids & Proposals (Arjun Singh Panel)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$pageTitle = 'My Bids & Proposals';
$sidebarRole = 'freelancer';
$userId = currentUserId();

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $bidId = (int)($_POST['bid_id'] ?? 0);
    db()->prepare('UPDATE bids SET status = "withdrawn" WHERE id = ? AND freelancer_id = ? AND status = "pending"')->execute([$bidId, $userId]);
    setFlash('info', 'Proposal withdrawn successfully.');
    redirect(baseUrl('freelancer/bids.php'));
}

$stmt = db()->prepare('SELECT b.*, p.title, p.budget as project_budget, p.status as project_status, u.first_name, u.last_name, u.avatar as client_avatar
    FROM bids b 
    JOIN projects p ON b.project_id = p.id 
    JOIN users u ON p.client_id = u.id
    WHERE b.freelancer_id = ? 
    ORDER BY b.created_at DESC');
$stmt->execute([$userId]);
$bids = $stmt->fetchAll();

$pendingCount = 0;
$acceptedCount = 0;
$totalProposedValue = 0;
foreach ($bids as $b) {
    if ($b['status'] === 'pending') $pendingCount++;
    if ($b['status'] === 'accepted') $acceptedCount++;
    $totalProposedValue += (float)$b['proposed_budget'];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-gavel text-primary me-2"></i>My Proposals & Bids</h4>
        <p class="text-muted mb-0">Track all your submitted project proposals, budgets, and application statuses.</p>
    </div>
    <a href="<?= baseUrl('freelancer/projects.php') ?>" class="btn btn-primary fw-bold">
        <i class="fas fa-search me-1"></i>Find New Projects
    </a>
</div>

<!-- Proposal Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-primary border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Total Proposals</div>
                    <div class="fw-bold fs-4"><?= count($bids) ?></div>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                    <i class="fas fa-paper-plane fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-warning border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Pending Client Review</div>
                    <div class="fw-bold fs-4 text-warning"><?= $pendingCount ?></div>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3">
                    <i class="fas fa-clock fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-success border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Accepted Proposals</div>
                    <div class="fw-bold fs-4 text-success"><?= $acceptedCount ?></div>
                </div>
                <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                    <i class="fas fa-check-circle fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Proposals Table Card -->
<div class="card shadow-sm">
    <div class="card-header bg-transparent py-3 fw-bold">
        <i class="fas fa-list-alt me-2 text-primary"></i>Submitted Proposals Log (<?= count($bids) ?>)
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light fs-7">
                <tr>
                    <th>Project</th>
                    <th>Client</th>
                    <th>My Proposed Bid</th>
                    <th>Delivery</th>
                    <th>Submitted On</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bids)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">You haven't submitted any proposals yet. Browse projects to get started!</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($bids as $bid): ?>
                    <tr>
                        <td>
                            <a href="<?= baseUrl('project-detail.php?id=' . $bid['project_id']) ?>" class="fw-bold text-decoration-none">
                                <?= e($bid['title']) ?>
                            </a>
                            <small class="d-block text-muted">Project Budget: <?= formatMoney((float)$bid['project_budget']) ?></small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= getAvatarUrl($bid['client_avatar'] ?? '') ?>" class="rounded-circle" width="28" height="28">
                                <span class="fs-7 fw-semibold"><?= e($bid['first_name'] . ' ' . $bid['last_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold text-success fs-6"><?= formatMoney((float)$bid['proposed_budget']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="fas fa-calendar-check me-1 text-info"></i><?= (int)$bid['delivery_days'] ?> Days
                            </span>
                        </td>
                        <td>
                            <small class="text-muted"><?= formatDate($bid['created_at']) ?></small>
                        </td>
                        <td><?= getStatusBadge($bid['status']) ?></td>
                        <td class="text-end">
                            <a href="<?= baseUrl('project-detail.php?id=' . $bid['project_id']) ?>" class="btn btn-sm btn-outline-primary me-1" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($bid['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="bid_id" value="<?= $bid['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to withdraw this proposal?')" title="Withdraw Proposal">
                                    <i class="fas fa-times me-1"></i>Withdraw
                                </button>
                            </form>
                            <?php endif; ?>
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
