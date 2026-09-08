<?php
/**
 * NexaWork - Freelancer Contracts (Arjun Singh Panel)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$pageTitle = 'My Contracts';
$sidebarRole = 'freelancer';
$userId = currentUserId();
$profile = getFreelancerProfile($userId);

$stmt = db()->prepare('SELECT c.*, p.title as project_title, p.category, u.first_name as client_fname, u.last_name as client_lname, u.avatar as client_avatar
    FROM contracts c 
    JOIN projects p ON c.project_id = p.id 
    JOIN users u ON c.client_id = u.id
    WHERE c.freelancer_id = ? 
    ORDER BY c.created_at DESC');
$stmt->execute([$userId]);
$contracts = $stmt->fetchAll();

$activeContracts = array_filter($contracts, fn($c) => $c['status'] === 'active');
$completedContracts = array_filter($contracts, fn($c) => $c['status'] === 'completed');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-file-contract text-primary me-2"></i>My Work Contracts</h4>
        <p class="text-muted mb-0">Manage active client projects, milestone progress, escrow funds, and certificates.</p>
    </div>
</div>

<!-- Contract Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
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
    <div class="col-md-4">
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
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-success border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Completed & Certified</div>
                    <div class="fw-bold fs-4 text-success"><?= count($completedContracts) ?></div>
                </div>
                <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                    <i class="fas fa-certificate fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($contracts)): ?>
<div class="card p-5 text-center shadow-sm">
    <i class="fas fa-file-contract fa-4x text-muted mb-3 opacity-50"></i>
    <h5>No Contracts Found</h5>
    <p class="text-muted">You do not have any active or completed client contracts yet.</p>
    <a href="<?= baseUrl('freelancer/projects.php') ?>" class="btn btn-primary w-auto mx-auto fw-bold">
        <i class="fas fa-search me-1"></i>Find Projects to Bid On
    </a>
</div>
<?php else: ?>
    <?php foreach ($contracts as $contract): ?>
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <!-- Project & Client Details -->
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill">
                            <?= e($contract['category'] ?? 'Software Development') ?>
                        </span>
                        <?= getStatusBadge($contract['status']) ?>
                    </div>
                    <h5 class="fw-bold mb-2"><?= e($contract['project_title']) ?></h5>
                    <div class="d-flex align-items-center gap-3 text-muted fs-7 mb-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= getAvatarUrl($contract['client_avatar'] ?? '') ?>" class="rounded-circle" width="28" height="28">
                            <span>Client: <strong><?= e($contract['client_fname'] . ' ' . $contract['client_lname']) ?></strong></span>
                        </div>
                        <div><i class="fas fa-calendar-alt me-1"></i>Started: <?= formatDate($contract['created_at']) ?></div>
                    </div>
                    <div class="fs-6">
                        <span class="text-muted">Contract Value:</span>
                        <strong class="text-success fs-5 ms-1"><?= formatMoney((float)$contract['amount']) ?></strong>
                    </div>
                </div>

                <!-- Milestone & Progress Controls -->
                <div class="col-lg-5 border-start-lg ps-lg-4">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold fs-7">Milestone Progress</span>
                            <span class="fw-bold text-primary"><?= (int)$contract['progress'] ?>%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= (int)$contract['progress'] ?>%"></div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <?php if ($contract['status'] === 'completed'): ?>
                            <?php
                            $cert = db()->prepare('SELECT * FROM certificates WHERE contract_id = ?');
                            $cert->execute([$contract['id']]);
                            $certificate = $cert->fetch();
                            ?>
                            <?php if ($certificate): ?>
                                <a href="<?= baseUrl('certificate.php?code=' . $certificate['certificate_code']) ?>" target="_blank" class="btn btn-outline-success fw-semibold">
                                    <i class="fas fa-certificate me-2"></i>View Verified Certificate
                                </a>
                            <?php else: ?>
                                <?php
                                $code = 'CERT-FH-' . date('Y') . '-' . str_pad((string)$contract['id'], 4, '0', STR_PAD_LEFT);
                                db()->prepare('INSERT INTO certificates (contract_id, freelancer_id, certificate_code) VALUES (?, ?, ?)')
                                    ->execute([$contract['id'], $profile['id'], $code]);
                                ?>
                                <a href="<?= baseUrl('certificate.php?code=' . $code) ?>" target="_blank" class="btn btn-success fw-semibold">
                                    <i class="fas fa-award me-2"></i>Generate Completion Certificate
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <a href="<?= baseUrl('messages.php') ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-envelope me-2"></i>Message Client
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
