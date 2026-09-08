<?php
/**
 * NexaWork - Admin Reviews Management Module
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Manage Reviews';
$sidebarRole = 'admin';
$adminId = currentUserId();

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_review') {
        $contractId = (int)($_POST['contract_id'] ?? 0);
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment = sanitize($_POST['comment'] ?? '');

        $stmt = db()->prepare('SELECT c.*, p.title FROM contracts c JOIN projects p ON c.project_id = p.id WHERE c.id = ?');
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();

        if ($contract) {
            try {
                $insert = db()->prepare('INSERT INTO reviews (contract_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
                $insert->execute([$contractId, $contract['client_id'], $contract['freelancer_id'], $rating, $comment]);

                // Update freelancer profile stats
                $rStmt = db()->prepare('SELECT AVG(rating) as avg_r, COUNT(*) as total FROM reviews WHERE reviewee_id = ?');
                $rStmt->execute([$contract['freelancer_id']]);
                $rData = $rStmt->fetch();

                db()->prepare('UPDATE freelancer_profiles SET avg_rating = ?, total_reviews = ? WHERE user_id = ?')
                    ->execute([round((float)$rData['avg_r'], 2), (int)$rData['total'], $contract['freelancer_id']]);

                $fp = getFreelancerProfile((int)$contract['freelancer_id']);
                if ($fp) {
                    checkAndAwardAchievements((int)$fp['id']);
                }

                createNotification(
                    (int)$contract['freelancer_id'],
                    'review_received',
                    'New Review Added',
                    'You received a ' . $rating . '-star review for "' . $contract['title'] . '".',
                    baseUrl('freelancer/reviews.php')
                );

                setFlash('success', 'Review added successfully.');
            } catch (PDOException $e) {
                setFlash('warning', 'A review for this contract already exists.');
            }
        } else {
            setFlash('danger', 'Selected contract not found.');
        }
    } elseif ($action === 'delete_review') {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        $stmt = db()->prepare('SELECT reviewee_id FROM reviews WHERE id = ?');
        $stmt->execute([$reviewId]);
        $revieweeId = $stmt->fetchColumn();

        db()->prepare('DELETE FROM reviews WHERE id = ?')->execute([$reviewId]);

        if ($revieweeId) {
            $rStmt = db()->prepare('SELECT AVG(rating) as avg_r, COUNT(*) as total FROM reviews WHERE reviewee_id = ?');
            $rStmt->execute([$revieweeId]);
            $rData = $rStmt->fetch();
            $avgR = $rData['total'] > 0 ? round((float)$rData['avg_r'], 2) : 0;

            db()->prepare('UPDATE freelancer_profiles SET avg_rating = ?, total_reviews = ? WHERE user_id = ?')
                ->execute([$avgR, (int)$rData['total'], $revieweeId]);
        }

        setFlash('info', 'Review deleted successfully.');
    }
    redirect(baseUrl('admin/reviews.php'));
}

// Fetch all available contracts for adding reviews
$availableContracts = db()->query('
    SELECT c.id, p.title, u_c.first_name as client_fname, u_c.last_name as client_lname,
        u_f.first_name as freelancer_fname, u_f.last_name as freelancer_lname
    FROM contracts c
    JOIN projects p ON c.project_id = p.id
    JOIN users u_c ON c.client_id = u_c.id
    JOIN users u_f ON c.freelancer_id = u_f.id
    ORDER BY c.created_at DESC
')->fetchAll();

// Fetch all existing reviews
$reviews = db()->query('
    SELECT r.*,
        u1.first_name as reviewer_fname, u1.last_name as reviewer_lname,
        u2.first_name as reviewee_fname, u2.last_name as reviewee_lname,
        p.title as project_title
    FROM reviews r
    JOIN users u1 ON r.reviewer_id = u1.id
    JOIN users u2 ON r.reviewee_id = u2.id
    JOIN contracts c ON r.contract_id = c.id
    JOIN projects p ON c.project_id = p.id
    ORDER BY r.created_at DESC
')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Manage Reviews</h4>
        <p class="text-muted mb-0">Manage client reviews, post admin-moderated feedback, and monitor ratings.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReviewModal">
        <i class="fas fa-plus me-2"></i>Add New Review
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Project</th>
                    <th>Reviewer (Client)</th>
                    <th>Reviewee (Freelancer)</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No reviews found. Click "Add New Review" above to post one.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td><strong><?= e($r['project_title']) ?></strong></td>
                        <td><?= e($r['reviewer_fname'] . ' ' . $r['reviewer_lname']) ?></td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary"><?= e($r['reviewee_fname'] . ' ' . $r['reviewee_lname']) ?></span></td>
                        <td><?= renderStars((float)$r['rating']) ?></td>
                        <td><small class="text-muted"><?= e($r['comment'] ?? 'No comment provided.') ?></small></td>
                        <td><small><?= formatDate($r['created_at']) ?></small></td>
                        <td class="text-end">
                            <form method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Review">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Review Modal -->
<div class="modal fade" id="addReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_review">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-star text-warning me-2"></i>Add Review for Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Contract *</label>
                        <select name="contract_id" class="form-select" required>
                            <option value="">-- Choose Contract --</option>
                            <?php foreach ($availableContracts as $ac): ?>
                                <option value="<?= $ac['id'] ?>">
                                    <?= e($ac['title']) ?> (Client: <?= e($ac['client_fname']) ?> -> Freelancer: <?= e($ac['freelancer_fname']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 text-center">
                        <label class="form-label d-block fw-semibold">Rating (1 to 5 Stars) *</label>
                        <div class="star-input fs-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star text-warning" style="cursor:pointer;" data-value="<?= $i ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingValue" value="5" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Review Comment *</label>
                        <textarea name="comment" class="form-control" rows="4" required placeholder="Write detailed review feedback..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Post Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
