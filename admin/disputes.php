<?php
/**
 * NexaWork - Admin Dispute Management & Escrow Resolution Module
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Disputes & Escrow Resolution';
$sidebarRole = 'admin';
$adminId = currentUserId();

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $disputeId = (int)($_POST['dispute_id'] ?? 0);
    $resolutionAction = $_POST['resolution_action'] ?? 'close_dispute';
    $resolutionNotes = sanitize($_POST['resolution_notes'] ?? '');

    $stmt = db()->prepare('
        SELECT d.*, c.id as contract_id, c.project_id, c.client_id, c.freelancer_id, c.amount as contract_amount, p.title as project_title
        FROM disputes d
        JOIN contracts c ON d.contract_id = c.id
        JOIN projects p ON c.project_id = p.id
        WHERE d.id = ?
    ');
    $stmt->execute([$disputeId]);
    $dispute = $stmt->fetch();

    if ($dispute && in_array($dispute['status'], ['open', 'under_review'])) {
        try {
            db()->beginTransaction();

            $disputeStatus = 'resolved';

            if ($resolutionAction === 'release_freelancer') {
                // 1. Release escrow payment to freelancer
                db()->prepare('UPDATE payments SET status = "released", released_at = NOW() WHERE contract_id = ? AND status = "escrow"')
                    ->execute([$dispute['contract_id']]);

                // 2. Mark contract & project as completed
                db()->prepare('UPDATE contracts SET status = "completed", progress = 100, end_date = CURDATE() WHERE id = ?')
                    ->execute([$dispute['contract_id']]);
                db()->prepare('UPDATE projects SET status = "completed" WHERE id = ?')
                    ->execute([$dispute['project_id']]);

                // 3. Update freelancer profile earnings & completed count
                db()->prepare('UPDATE freelancer_profiles SET total_earnings = total_earnings + ?, completed_projects = completed_projects + 1 WHERE user_id = ?')
                    ->execute([$dispute['contract_amount'], $dispute['freelancer_id']]);

                $fp = getFreelancerProfile((int)$dispute['freelancer_id']);
                if ($fp) {
                    checkAndAwardAchievements((int)$fp['id']);
                }

                // 4. Notifications
                createNotification(
                    (int)$dispute['freelancer_id'],
                    'dispute_resolved',
                    'Dispute Resolved - Payment Released',
                    'Admin resolved the dispute for "' . $dispute['project_title'] . '". Payment of ' . formatMoney((float)$dispute['contract_amount']) . ' has been released to your account.',
                    baseUrl('freelancer/earnings.php')
                );
                createNotification(
                    (int)$dispute['client_id'],
                    'dispute_resolved',
                    'Dispute Resolved - Funds Released',
                    'Admin resolved the dispute for "' . $dispute['project_title'] . '". Payment of ' . formatMoney((float)$dispute['contract_amount']) . ' was released to the freelancer.',
                    baseUrl('client/contracts.php')
                );

                $fullResolution = '[Payment Released to Freelancer] ' . $resolutionNotes;

            } elseif ($resolutionAction === 'refund_client') {
                // 1. Refund escrow payment to client
                db()->prepare('UPDATE payments SET status = "refunded" WHERE contract_id = ? AND status = "escrow"')
                    ->execute([$dispute['contract_id']]);

                // 2. Cancel contract & project
                db()->prepare('UPDATE contracts SET status = "cancelled", end_date = CURDATE() WHERE id = ?')
                    ->execute([$dispute['contract_id']]);
                db()->prepare('UPDATE projects SET status = "cancelled" WHERE id = ?')
                    ->execute([$dispute['project_id']]);

                // 3. Notifications
                createNotification(
                    (int)$dispute['client_id'],
                    'dispute_resolved',
                    'Dispute Resolved - Funds Refunded',
                    'Admin resolved the dispute for "' . $dispute['project_title'] . '". Escrow payment of ' . formatMoney((float)$dispute['contract_amount']) . ' has been refunded to your account.',
                    baseUrl('client/payments.php')
                );
                createNotification(
                    (int)$dispute['freelancer_id'],
                    'dispute_resolved',
                    'Dispute Resolved - Contract Cancelled',
                    'Admin resolved the dispute for "' . $dispute['project_title'] . '". The contract was cancelled and escrow refunded to the client.',
                    baseUrl('freelancer/contracts.php')
                );

                $fullResolution = '[Escrow Refunded to Client] ' . $resolutionNotes;

            } else {
                $disputeStatus = 'closed';
                $fullResolution = '[Closed without Escrow Transfer] ' . $resolutionNotes;
            }

            // Update dispute record
            db()->prepare('UPDATE disputes SET status = ?, resolution = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ?')
                ->execute([$disputeStatus, $fullResolution, $adminId, $disputeId]);

            db()->commit();
            setFlash('success', 'Dispute successfully resolved and escrow actions processed.');
        } catch (Exception $e) {
            db()->rollBack();
            error_log('Dispute resolution error: ' . $e->getMessage());
            setFlash('danger', 'Failed to resolve dispute. Please try again.');
        }
    }
    redirect(baseUrl('admin/disputes.php'));
}

$disputes = db()->query('
    SELECT d.*, c.amount as contract_amount, c.status as contract_status, p.title as project_title,
        u_raiser.first_name as raiser_fname, u_raiser.last_name as raiser_lname, u_raiser.role as raiser_role,
        u_client.first_name as client_fname, u_client.last_name as client_lname,
        u_freelancer.first_name as freelancer_fname, u_freelancer.last_name as freelancer_lname,
        (SELECT py.status FROM payments py WHERE py.contract_id = c.id ORDER BY py.id DESC LIMIT 1) as payment_status,
        (SELECT py.transaction_ref FROM payments py WHERE py.contract_id = c.id ORDER BY py.id DESC LIMIT 1) as transaction_ref
    FROM disputes d
    JOIN contracts c ON d.contract_id = c.id
    JOIN projects p ON c.project_id = p.id
    JOIN users u_raiser ON d.raised_by = u_raiser.id
    JOIN users u_client ON c.client_id = u_client.id
    JOIN users u_freelancer ON c.freelancer_id = u_freelancer.id
    ORDER BY d.created_at DESC
')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Dispute & Escrow Resolution</h4>
        <p class="text-muted mb-0">Review open contract disputes, allocate escrow funds, and resolve conflicts.</p>
    </div>
</div>

<?php if (empty($disputes)): ?>
    <div class="card p-5 text-center">
        <i class="fas fa-gavel fa-3x text-muted mb-3 opacity-50"></i>
        <h5>No Disputes Found</h5>
        <p class="text-muted mb-0">There are currently no raised disputes in the marketplace.</p>
    </div>
<?php else: ?>
    <?php foreach ($disputes as $dispute): ?>
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold"><?= e($dispute['project_title']) ?></h6>
                <small class="text-muted">Raised <?= timeAgo($dispute['created_at']) ?> by <?= e($dispute['raiser_fname'] . ' ' . $dispute['raiser_lname']) ?> (<?= ucfirst($dispute['raiser_role']) ?>)</small>
            </div>
            <div>
                <?= getStatusBadge($dispute['status']) ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <small class="text-muted d-block">Client</small>
                    <strong><?= e($dispute['client_fname'] . ' ' . $dispute['client_lname']) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Freelancer</small>
                    <strong><?= e($dispute['freelancer_fname'] . ' ' . $dispute['freelancer_lname']) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Contract Value</small>
                    <span class="fs-5 fw-bold text-primary"><?= formatMoney((float)$dispute['contract_amount']) ?></span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Escrow Payment Ref</small>
                    <code><?= e($dispute['transaction_ref'] ?? 'N/A') ?></code> (<?= getStatusBadge($dispute['payment_status'] ?? 'pending') ?>)
                </div>
            </div>

            <div class="p-3 bg-light rounded mb-3">
                <h6 class="fw-semibold text-danger mb-1"><i class="fas fa-exclamation-circle me-1"></i>Reason for Dispute</h6>
                <p class="mb-0 text-dark"><?= nl2br(e($dispute['reason'])) ?></p>
            </div>

            <?php if (in_array($dispute['status'], ['open', 'under_review'])): ?>
            <hr>
            <form method="POST" class="mt-3">
                <?= csrfField() ?>
                <input type="hidden" name="dispute_id" value="<?= $dispute['id'] ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Resolution Action *</label>
                        <select name="resolution_action" class="form-select" required>
                            <option value="release_freelancer">Release Escrow to Freelancer (Complete Contract)</option>
                            <option value="refund_client">Refund Escrow to Client (Cancel Contract)</option>
                            <option value="close_dispute">Close Dispute without Escrow Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Admin Explanation / Notes *</label>
                        <input type="text" name="resolution_notes" class="form-control" placeholder="Reason for this decision..." required>
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-success px-4" onclick="return confirm('Process this dispute resolution and update escrow funds?')">
                        <i class="fas fa-gavel me-1"></i>Execute Resolution
                    </button>
                </div>
            </form>
            <?php elseif ($dispute['resolution']): ?>
            <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded mt-3">
                <h6 class="fw-bold text-success mb-1"><i class="fas fa-check-circle me-1"></i>Resolution Findings</h6>
                <p class="mb-0 small"><?= nl2br(e($dispute['resolution'])) ?></p>
                <?php if ($dispute['resolved_at']): ?>
                <small class="text-muted d-block mt-2">Resolved on <?= formatDateTime($dispute['resolved_at']) ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
