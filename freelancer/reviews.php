<?php
/**
 * NexaWork - Freelancer Reviews & Ratings (Arjun Singh Panel)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$pageTitle = 'Reviews & Client Feedback';
$sidebarRole = 'freelancer';
$userId = currentUserId();
$profile = getFreelancerProfile($userId);

$stmt = db()->prepare('SELECT r.*, u.first_name, u.last_name, u.avatar as reviewer_avatar, p.title as project_title, p.category
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    JOIN contracts c ON r.contract_id = c.id
    JOIN projects p ON c.project_id = p.id
    WHERE r.reviewee_id = ?
    ORDER BY r.created_at DESC');
$stmt->execute([$userId]);
$reviews = $stmt->fetchAll();

$avgRating = (float)($profile['avg_rating'] ?? 0);
$totalReviews = (int)($profile['total_reviews'] ?? count($reviews));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-star text-warning me-2"></i>Reviews & Client Feedback</h4>
        <p class="text-muted mb-0">Ratings and testimonials received from clients upon project completion.</p>
    </div>
</div>

<!-- Overall Rating Summary Card -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-4">
        <div class="row align-items-center g-4">
            <div class="col-md-4 text-center border-end-md">
                <div class="display-2 fw-bold text-primary mb-1"><?= number_format($avgRating, 1) ?></div>
                <div class="mb-2"><?= renderStars($avgRating) ?></div>
                <p class="text-muted mb-0 fw-semibold">Based on <?= $totalReviews ?> verified client reviews</p>
            </div>
            <div class="col-md-8">
                <h6 class="fw-bold mb-3"><i class="fas fa-award text-success me-2"></i>Performance Ratings Breakdown</h6>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-7 text-muted" style="width: 120px;">Quality of Work</span>
                        <div class="progress flex-grow-1" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 96%"></div>
                        </div>
                        <span class="fs-7 fw-bold text-dark">4.9</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-7 text-muted" style="width: 120px;">Communication</span>
                        <div class="progress flex-grow-1" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: 94%"></div>
                        </div>
                        <span class="fs-7 fw-bold text-dark">4.7</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-7 text-muted" style="width: 120px;">Adherence to Deadline</span>
                        <div class="progress flex-grow-1" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: 98%"></div>
                        </div>
                        <span class="fs-7 fw-bold text-dark">4.9</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reviews List -->
<h5 class="fw-bold mb-3"><i class="fas fa-comments text-primary me-2"></i>Client Testimonials (<?= count($reviews) ?>)</h5>

<?php if (empty($reviews)): ?>
<div class="card p-5 text-center shadow-sm">
    <i class="fas fa-star fa-4x text-muted mb-3 opacity-50"></i>
    <h5>No Reviews Received Yet</h5>
    <p class="text-muted">Complete projects successfully to receive ratings and feedback from clients.</p>
</div>
<?php else: ?>
    <?php foreach ($reviews as $review): ?>
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= getAvatarUrl($review['reviewer_avatar'] ?? '') ?>" class="rounded-circle" width="44" height="44" style="object-fit:cover;">
                    <div>
                        <h6 class="fw-bold mb-0"><?= e($review['first_name'] . ' ' . $review['last_name']) ?></h6>
                        <small class="text-muted"><i class="fas fa-folder me-1"></i><?= e($review['project_title']) ?></small>
                    </div>
                </div>
                <div class="text-end">
                    <div><?= renderStars((float)$review['rating']) ?></div>
                    <small class="text-muted fs-8"><i class="fas fa-clock me-1"></i><?= timeAgo($review['created_at']) ?></small>
                </div>
            </div>
            <?php if ($review['comment']): ?>
                <div class="p-3 bg-light rounded text-dark dark-mode-card border-start border-primary border-3">
                    <p class="mb-0 fs-7 italic">"<?= e($review['comment']) ?>"</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
