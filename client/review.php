<?php
/**
 * FreelanceHub - Client Leave Review
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$userId = currentUserId();
$contractId = (int)($_GET['contract_id'] ?? 0);

$stmt = db()->prepare('SELECT c.*, p.title FROM contracts c JOIN projects p ON c.project_id = p.id WHERE c.id = ? AND c.client_id = ?');
$stmt->execute([$contractId, $userId]);
$contract = $stmt->fetch();

if (!$contract) {
    setFlash('danger', 'Contract not found.');
    redirect(baseUrl('client/contracts.php'));
}

$pageTitle = 'Leave Review';
$sidebarRole = 'client';

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = sanitize($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        setFlash('danger', 'Please select a rating between 1 and 5.');
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO reviews (contract_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$contractId, $userId, $contract['freelancer_id'], $rating, $comment]);

            // Update freelancer average rating
            $stmt = db()->prepare('SELECT AVG(rating) as avg_r, COUNT(*) as total FROM reviews WHERE reviewee_id = ?');
            $stmt->execute([$contract['freelancer_id']]);
            $ratingData = $stmt->fetch();
            db()->prepare('UPDATE freelancer_profiles SET avg_rating = ?, total_reviews = ? WHERE user_id = ?')
                ->execute([round($ratingData['avg_r'], 2), $ratingData['total'], $contract['freelancer_id']]);

            $profile = getFreelancerProfile($contract['freelancer_id']);
            if ($profile) checkAndAwardAchievements($profile['id']);

            createNotification($contract['freelancer_id'], 'review_received', 'New Review Received!',
                'You received a ' . $rating . '-star review.', baseUrl('freelancer/reviews.php'));

            setFlash('success', 'Review submitted successfully!');
            redirect(baseUrl('client/contracts.php'));
        } catch (PDOException $e) {
            setFlash('danger', 'You have already reviewed this contract.');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="fw-bold mb-4">Review Freelancer</h4>
<p class="text-muted">Project: <?= e($contract['title']) ?></p>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-4 text-center">
                <label class="form-label d-block">Rating</label>
                <div class="star-input fs-3">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="far fa-star text-warning" style="cursor:pointer;" data-value="<?= $i ?>"></i>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingValue" value="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Your Review</label>
                <textarea name="comment" class="form-control" rows="5" placeholder="Share your experience working with this freelancer..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
