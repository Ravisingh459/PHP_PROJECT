<?php
/**
 * NexaWork - Client Contracts Management (Rajesh Kumar & Clients)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$pageTitle = 'Contracts & Escrow Management';
$sidebarRole = 'client';
$userId = currentUserId();
$milestonesEnabled = tableExists('contract_milestones');

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $contractId = (int)($_POST['contract_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = db()->prepare('SELECT c.*, p.title FROM contracts c JOIN projects p ON c.project_id = p.id WHERE c.id = ? AND c.client_id = ?');
    $stmt->execute([$contractId, $userId]);
    $contract = $stmt->fetch();

    if ($contract) {
        if ($action === 'release_payment') {
            db()->prepare('UPDATE payments SET status = "released", released_at = NOW() WHERE contract_id = ? AND status = "escrow"')->execute([$contractId]);
            db()->prepare('UPDATE contracts SET status = "completed", progress = 100, end_date = CURDATE() WHERE id = ?')->execute([$contractId]);
            db()->prepare('UPDATE projects SET status = "completed" WHERE id = ?')->execute([$contract['project_id']]);

            db()->prepare('UPDATE freelancer_profiles SET total_earnings = total_earnings + ?, completed_projects = completed_projects + 1 WHERE user_id = ?')
                ->execute([$contract['amount'], $contract['freelancer_id']]);

            $freelancerProfile = getFreelancerProfile($contract['freelancer_id']);
            if ($freelancerProfile) {
                checkAndAwardAchievements((int) $freelancerProfile['id']);
            }

            createNotification($contract['freelancer_id'], 'payment_released', 'Payment Released!',
                'Payment of ' . formatMoney((float)$contract['amount']) . ' has been released to your account.',
                baseUrl('freelancer/earnings.php'));

            setFlash('success', 'Escrow payment released successfully to freelancer! Contract marked completed.');

        } elseif ($action === 'update_progress') {
            $progress = min(100, max(0, (int)($_POST['progress'] ?? 0)));
            db()->prepare('UPDATE contracts SET progress = ? WHERE id = ?')->execute([$progress, $contractId]);
            setFlash('success', 'Milestone progress updated to ' . $progress . '%.');

        } elseif ($action === 'add_milestone' && $milestonesEnabled) {
            $title = sanitize($_POST['milestone_title'] ?? '');
            $amount = (float)($_POST['milestone_amount'] ?? 0);
            if ($title && $amount > 0) {
                $sortStmt = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM contract_milestones WHERE contract_id = ?');
                $sortStmt->execute([$contractId]);
                $sortOrder = (int) $sortStmt->fetchColumn();
                db()->prepare('INSERT INTO contract_milestones (contract_id, title, amount, sort_order) VALUES (?, ?, ?, ?)')
                    ->execute([$contractId, $title, $amount, $sortOrder]);
                setFlash('success', 'Milestone phase added to contract.');
            }

        } elseif ($action === 'pay_milestone' && $milestonesEnabled) {
            $milestoneId = (int)($_POST['milestone_id'] ?? 0);
            $mStmt = db()->prepare('SELECT * FROM contract_milestones WHERE id = ? AND contract_id = ? AND status = "completed"');
            $mStmt->execute([$milestoneId, $contractId]);
            $milestone = $mStmt->fetch();
            if ($milestone) {
                db()->prepare('UPDATE contract_milestones SET status = "paid", paid_at = NOW() WHERE id = ?')->execute([$milestoneId]);
                db()->prepare('UPDATE freelancer_profiles SET total_earnings = total_earnings + ? WHERE user_id = ?')
                    ->execute([$milestone['amount'], $contract['freelancer_id']]);
                
                $newProgress = getMilestoneProgress($contractId);
                db()->prepare('UPDATE contracts SET progress = ? WHERE id = ?')->execute([$newProgress, $contractId]);
                
                createNotification($contract['freelancer_id'], 'milestone_paid', 'Milestone Payment Released!',
                    'Payment of ' . formatMoney((float)$milestone['amount']) . ' for "' . $milestone['title'] . '" released.',
                    baseUrl('freelancer/earnings.php'));
                
                setFlash('success', 'Milestone payment released to freelancer.');
            }

        } elseif ($action === 'complete_milestone' && $milestonesEnabled) {
            $milestoneId = (int)($_POST['milestone_id'] ?? 0);
            db()->prepare('UPDATE contract_milestones SET status = "completed", completed_at = NOW() WHERE id = ? AND contract_id = ?')
                ->execute([$milestoneId, $contractId]);
            setFlash('success', 'Milestone marked complete.');
        }
    }
    redirect(baseUrl('client/contracts.php'));
}

$stmt = db()->prepare('SELECT c.*, p.title as project_title, p.category, u.first_name as fl_fname, u.last_name as fl_lname, u.avatar as fl_avatar
    FROM contracts c
    JOIN projects p ON c.project_id = p.id
    JOIN users u ON c.freelancer_id = u.id
    WHERE c.client_id = ?
    ORDER BY c.created_at DESC');
$stmt->execute([$userId]);
$contracts = $stmt->fetchAll();

$activeContracts = array_filter($contracts, fn($c) => $c['status'] === 'active');
$completedContracts = array_filter($contracts, fn($c) => $c['status'] === 'completed');

$totalContractVal = array_sum(array_column($contracts, 'amount'));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-file-contract text-primary me-2"></i>Client Contracts & Escrow</h4>
        <p class="text-muted mb-0">Manage active freelancer hires, escrow funding, milestones, and payment releases.</p>
    </div>
    <a href="<?= baseUrl('client/post-project.php') ?>" class="btn btn-primary fw-bold">
        <i class="fas fa-plus-circle me-1"></i>Post New Project
    </a>
</div>

<!-- Summary Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-start border-primary border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Total Contracts</div>
                    <div class="fw-bold fs-4"><?= count($contracts) ?></div>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                    <i class="fas fa-briefcase fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-start border-warning border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Active Ongoing</div>
                    <div class="fw-bold fs-4 text-warning"><?= count($activeContracts) ?></div>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3">
                    <i class="fas fa-spinner fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-start border-success border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Completed</div>
                    <div class="fw-bold fs-4 text-success"><?= count($completedContracts) ?></div>
                </div>
                <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                    <i class="fas fa-check-circle fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-start border-info border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Total Value</div>
                    <div class="fw-bold fs-5 text-info"><?= formatMoney((float)$totalContractVal) ?></div>
                </div>
                <div class="bg-info bg-opacity-10 text-info rounded-circle p-3">
                    <i class="fas fa-wallet fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($contracts)): ?>
<div class="card p-5 text-center shadow-sm border-0">
    <i class="fas fa-file-contract fa-4x text-muted mb-3 opacity-50"></i>
    <h5>No Active or Previous Contracts Found</h5>
    <p class="text-muted">Hire freelancers from your project bid proposals to create contracts and fund escrow.</p>
    <a href="<?= baseUrl('client/projects.php') ?>" class="btn btn-primary w-auto mx-auto fw-bold">
        <i class="fas fa-folder-open me-1"></i>View My Projects
    </a>
</div>
<?php else: ?>
    <?php foreach ($contracts as $contract):
        $milestones = $milestonesEnabled ? getContractMilestones($contract['id']) : [];
    ?>
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <!-- Contract Overview -->
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fw-semibold fs-7">
                            <?= e($contract['category'] ?? 'Software Project') ?>
                        </span>
                        <?= getStatusBadge($contract['status']) ?>
                    </div>

                    <h5 class="fw-bold mb-2"><?= e($contract['project_title']) ?></h5>

                    <div class="d-flex align-items-center gap-3 text-muted fs-7 mb-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= getAvatarUrl($contract['fl_avatar'] ?? '') ?>" class="rounded-circle" width="28" height="28" style="object-fit:cover;">
                            <span>Freelancer: <strong><?= e($contract['fl_fname'] . ' ' . $contract['fl_lname']) ?></strong></span>
                        </div>
                        <div><i class="fas fa-calendar-alt me-1"></i>Started: <?= formatDate($contract['start_date']) ?></div>
                    </div>

                    <div class="fs-6 mb-3">
                        <span class="text-muted">Total Contract Escrow:</span>
                        <strong class="text-success fs-4 ms-2"><?= formatMoney((float)$contract['amount']) ?></strong>
                    </div>

                    <!-- Milestones List -->
                    <?php if (!empty($milestones)): ?>
                    <div class="mt-3 bg-light p-3 rounded dark-mode-card">
                        <h6 class="fw-bold mb-2"><i class="fas fa-layer-group text-primary me-2"></i>Contract Milestones</h6>
                        <div class="list-group list-group-flush">
                            <?php foreach ($milestones as $ms): ?>
                            <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <strong class="fs-7"><?= e($ms['title']) ?></strong>
                                    <span class="text-success fw-bold ms-2 fs-7"><?= formatMoney((float)$ms['amount']) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?= getStatusBadge($ms['status']) ?>
                                    <?php if ($contract['status'] === 'active' && $ms['status'] === 'pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="contract_id" value="<?= $contract['id'] ?>">
                                        <input type="hidden" name="action" value="complete_milestone">
                                        <input type="hidden" name="milestone_id" value="<?= $ms['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success">Approve Phase</button>
                                    </form>
                                    <?php elseif ($ms['status'] === 'completed'): ?>
                                    <form method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="contract_id" value="<?= $contract['id'] ?>">
                                        <input type="hidden" name="action" value="pay_milestone">
                                        <input type="hidden" name="milestone_id" value="<?= $ms['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success fw-bold" onclick="return confirm('Release milestone payment?')">
                                            <i class="fas fa-unlock me-1"></i>Pay Milestone
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Contract Progress & Actions Column -->
                <div class="col-lg-5 border-start-lg ps-lg-4">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold fs-7">Milestone Progress</span>
                            <span class="fw-bold text-primary fs-6"><?= (int)$contract['progress'] ?>%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= (int)$contract['progress'] ?>%"></div>
                        </div>
                    </div>

                    <?php if ($contract['status'] === 'active'): ?>
                    <form method="POST" class="mb-3">
                        <?= csrfField() ?>
                        <input type="hidden" name="contract_id" value="<?= $contract['id'] ?>">
                        <input type="hidden" name="action" value="update_progress">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text fs-7">Update Progress %</span>
                            <input type="number" name="progress" class="form-control" min="0" max="100" value="<?= (int)$contract['progress'] ?>">
                            <button type="submit" class="btn btn-outline-primary fw-semibold">Save</button>
                        </div>
                    </form>

                    <form method="POST" class="mb-2">
                        <?= csrfField() ?>
                        <input type="hidden" name="contract_id" value="<?= $contract['id'] ?>">
                        <input type="hidden" name="action" value="release_payment">
                        <button type="submit" class="btn btn-success fw-bold w-100 py-2" onclick="return confirm('Are you sure you want to release full escrow payment to freelancer?')">
                            <i class="fas fa-unlock me-2"></i>Release Full Escrow Payment
                        </button>
                    </form>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <a href="<?= baseUrl('messages.php') ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-envelope me-2"></i>Message Freelancer
                        </a>

                        <a href="<?= baseUrl('client/review.php?contract_id=' . $contract['id']) ?>" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-star me-2"></i>Leave Client Review
                        </a>

                        <?php if ($contract['status'] === 'active'): ?>
                        <a href="<?= baseUrl('freelancer/dispute.php?contract_id=' . $contract['id']) ?>" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-exclamation-triangle me-2"></i>Raise Escrow Dispute
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
