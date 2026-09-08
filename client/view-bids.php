<?php
/**
 * FreelanceHub - Client View Bids & Hire Freelancer
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$userId = currentUserId();
$projectId = (int)($_GET['id'] ?? 0);
$project = getProjectById($projectId);

if (!$project || (int)$project['client_id'] !== $userId) {
    setFlash('danger', 'Project not found.');
    redirect(baseUrl('client/projects.php'));
}

$pageTitle = 'View Bids - ' . $project['title'];
$sidebarRole = 'client';

// Handle hire action
if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';
    $bidId = (int)($_POST['bid_id'] ?? 0);

    if ($action === 'accept') {
        $stmt = db()->prepare('SELECT * FROM bids WHERE id = ? AND project_id = ? AND status = "pending"');
        $stmt->execute([$bidId, $projectId]);
        $bid = $stmt->fetch();

        if ($bid) {
            try {
                db()->beginTransaction();

                // Accept bid
                db()->prepare('UPDATE bids SET status = "accepted" WHERE id = ?')->execute([$bidId]);
                db()->prepare('UPDATE bids SET status = "rejected" WHERE project_id = ? AND id != ?')->execute([$projectId, $bidId]);
                db()->prepare('UPDATE projects SET status = "in_progress" WHERE id = ?')->execute([$projectId]);

                // Create contract
                $stmt = db()->prepare('INSERT INTO contracts (project_id, client_id, freelancer_id, bid_id, amount, start_date) VALUES (?, ?, ?, ?, ?, CURDATE())');
                $stmt->execute([$projectId, $userId, $bid['freelancer_id'], $bidId, $bid['proposed_budget']]);
                $contractId = (int) db()->lastInsertId();

                // Create escrow payment
                $txnRef = 'TXN-' . date('Y') . '-' . str_pad((string)$contractId, 4, '0', STR_PAD_LEFT);
                db()->prepare('INSERT INTO payments (contract_id, payer_id, payee_id, amount, status, transaction_ref, description) VALUES (?, ?, ?, ?, "escrow", ?, ?)')
                    ->execute([$contractId, $userId, $bid['freelancer_id'], $bid['proposed_budget'], $txnRef, $project['title'] . ' - Escrow']);

                db()->commit();

                createNotification($bid['freelancer_id'], 'project_assigned', 'Project Assigned!',
                    'You have been hired for: ' . $project['title'],
                    baseUrl('freelancer/contracts.php'));

                setFlash('success', 'Freelancer hired successfully! Payment placed in escrow.');
                redirect(baseUrl('client/contracts.php'));
            } catch (Exception $e) {
                db()->rollBack();
                setFlash('danger', 'Failed to hire freelancer.');
            }
        }
    } elseif ($action === 'reject') {
        db()->prepare('UPDATE bids SET status = "rejected" WHERE id = ? AND project_id = ?')->execute([$bidId, $projectId]);
        setFlash('info', 'Bid rejected.');
    }
}

$stmt = db()->prepare('SELECT b.*, u.first_name, u.last_name, u.avatar, u.email,
    fp.title as freelancer_title, fp.avg_rating, fp.completed_projects, fp.hourly_rate
    FROM bids b
    JOIN users u ON b.freelancer_id = u.id
    LEFT JOIN freelancer_profiles fp ON fp.user_id = u.id
    WHERE b.project_id = ?
    ORDER BY b.created_at DESC');
$stmt->execute([$projectId]);
$bids = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<!-- AI Smart Freelancer Recommendations -->
<?php
$aiRecommended = getRecommendedFreelancers($projectId, 3);
if (!empty($aiRecommended)):
?>
<div class="card mb-4 border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: #fff;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="fas fa-brain text-warning fs-4"></i>
            <div>
                <h5 class="fw-bold mb-0 text-white">NexaAI Recommended Talent Matches</h5>
                <small class="text-white-50">Top-rated freelancers matching your project skills & budget parameters</small>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ($aiRecommended as $rec): ?>
            <div class="col-md-4">
                <div class="p-3 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <img src="<?= getAvatarUrl($rec['avatar']) ?>" class="rounded-circle" width="40" height="40" style="object-fit:cover;">
                        <div class="text-truncate">
                            <strong class="text-white d-block text-truncate"><?= e($rec['first_name'] . ' ' . $rec['last_name']) ?></strong>
                            <small class="text-white-50 d-block text-truncate"><?= e($rec['title'] ?? 'Freelancer') ?></small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 small">
                        <?= renderMatchBadge($rec['match_score']) ?>
                        <a href="<?= baseUrl('freelancer-profile.php?id=' . $rec['user_id']) ?>" target="_blank" class="btn btn-xs btn-outline-light">View Profile</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($bids)): ?>

    <div class="card p-5 text-center">
        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
        <h5>No bids yet</h5>
        <p class="text-muted">Freelancers haven't submitted bids for this project yet.</p>
    </div>
<?php else: ?>
    <?php foreach ($bids as $bid): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="d-flex align-items-start">
                        <img src="<?= getAvatarUrl($bid['avatar']) ?>" class="rounded-circle me-3" width="56" height="56">
                        <div>
                            <h5 class="mb-1">
                                <a href="<?= baseUrl('freelancer-profile.php?id=' . $bid['freelancer_id']) ?>"><?= e($bid['first_name'] . ' ' . $bid['last_name']) ?></a>
                            </h5>
                            <p class="text-muted small mb-1"><?= e($bid['freelancer_title'] ?? 'Freelancer') ?></p>
                            <?= renderStars((float)($bid['avg_rating'] ?? 0)) ?>
                            <span class="text-muted small ms-2"><?= $bid['completed_projects'] ?? 0 ?> projects</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6>Cover Letter</h6>
                        <p class="text-muted"><?= nl2br(e($bid['cover_letter'])) ?></p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="mb-2">
                        <span class="fs-4 fw-bold text-primary"><?= formatMoney((float)$bid['proposed_budget']) ?></span>
                        <small class="d-block text-muted"><?= $bid['delivery_days'] ?> days delivery</small>
                    </div>
                    <?= getStatusBadge($bid['status']) ?>
                    <p class="text-muted small mt-2">Submitted <?= timeAgo($bid['created_at']) ?></p>

                    <?php if ($bid['status'] === 'pending' && $project['status'] === 'open'): ?>
                    <div class="mt-3 d-flex gap-2 justify-content-md-end">
                        <form method="POST" class="d-inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="accept">
                            <input type="hidden" name="bid_id" value="<?= $bid['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Hire this freelancer?')">
                                <i class="fas fa-check me-1"></i>Hire
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="bid_id" value="<?= $bid['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
                        </form>
                        <a href="<?= baseUrl('messages.php?to=' . $bid['freelancer_id']) ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
