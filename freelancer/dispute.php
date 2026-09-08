<?php
/**
 * FreelanceHub - Raise Dispute
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer', 'client');

$userId = currentUserId();
$contractId = (int)($_GET['contract_id'] ?? 0);

$stmt = db()->prepare('SELECT c.*, p.title FROM contracts c JOIN projects p ON c.project_id = p.id WHERE c.id = ? AND (c.freelancer_id = ? OR c.client_id = ?)');
$stmt->execute([$contractId, $userId, $userId]);
$contract = $stmt->fetch();

if (!$contract) {
    setFlash('danger', 'Contract not found.');
    redirect(baseUrl('dashboard.php'));
}

$pageTitle = 'Raise Dispute';
$sidebarRole = currentUserRole();

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $reason = sanitize($_POST['reason'] ?? '');
    if (!empty($reason)) {
        db()->prepare('INSERT INTO disputes (contract_id, raised_by, reason) VALUES (?, ?, ?)')->execute([$contractId, $userId, $reason]);
        db()->prepare('UPDATE contracts SET status = "disputed" WHERE id = ?')->execute([$contractId]);
        
        $admins = db()->query('SELECT id FROM users WHERE role = "admin"')->fetchAll();
        foreach ($admins as $adm) {
            createNotification((int)$adm['id'], 'new_dispute', 'New Dispute Raised', 'A dispute was raised for contract: ' . $contract['title'], baseUrl('admin/disputes.php'));
        }

        setFlash('success', 'Dispute raised. Admin will review shortly.');
        redirect(baseUrl($sidebarRole . '/contracts.php'));
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="fw-bold mb-4">Raise Dispute</h4>
<p>Contract: <strong><?= e($contract['title']) ?></strong></p>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Reason for Dispute</label>
                <textarea name="reason" class="form-control" rows="6" required placeholder="Describe the issue in detail..."></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Submit Dispute</button>
            <a href="<?= baseUrl($sidebarRole . '/contracts.php') ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
