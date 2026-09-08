<?php
/**
 * NexaWork - Public Freelancer Profile Showcase
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$userId = (int)($_GET['id'] ?? 0);
$profile = getFreelancerProfile($userId);

if (!$profile) {
    setFlash('danger', 'Freelancer not found.');
    redirect(baseUrl('freelancers.php'));
}

$pageTitle = $profile['first_name'] . ' ' . $profile['last_name'] . ' - Freelancer Profile';

$stmt = db()->prepare('SELECT s.name, fs.proficiency FROM skills s JOIN freelancer_skills fs ON s.id = fs.skill_id WHERE fs.freelancer_id = ?');
$stmt->execute([$profile['id']]);
$skills = $stmt->fetchAll();

$stmt = db()->prepare('SELECT * FROM portfolios WHERE freelancer_id = ? ORDER BY created_at DESC');
$stmt->execute([$profile['id']]);
$portfolios = $stmt->fetchAll();

$stmt = db()->prepare('SELECT r.*, u.first_name, u.last_name, u.avatar FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.reviewee_id = ? ORDER BY r.created_at DESC LIMIT 10');
$stmt->execute([$userId]);
$reviews = $stmt->fetchAll();

$stmt = db()->prepare('SELECT a.* FROM achievements a JOIN freelancer_achievements fa ON a.id = fa.achievement_id WHERE fa.freelancer_id = ?');
$stmt->execute([$profile['id']]);
$achievements = $stmt->fetchAll();

$trustScore = calculateTrustScore($profile);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar Profile Card -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 text-center p-4 mb-4">
                <div class="position-relative d-inline-block mx-auto mb-3">
                    <img src="<?= getAvatarUrl($profile['avatar']) ?>" class="rounded-circle shadow" width="120" height="120" style="object-fit:cover;" alt="Avatar">
                    <?php if (!empty($profile['is_verified'])): ?>
                        <span class="position-absolute bottom-0 end-0 bg-success text-white rounded-circle p-2 shadow" style="width: 32px; height: 32px; font-size: 12px;" title="Verified Profile">
                            <i class="fas fa-check"></i>
                        </span>
                    <?php endif; ?>
                </div>

                <h3 class="fw-bold mb-1"><?= e($profile['first_name'] . ' ' . $profile['last_name']) ?></h3>
                <p class="text-muted mb-2"><?= e($profile['title'] ?? 'Top Freelancer') ?></p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <?= renderTrustBadge($trustScore) ?>
                    <span class="badge bg-<?= $profile['availability'] === 'available' ? 'success' : 'warning' ?> rounded-pill px-3 py-2">
                        <i class="fas fa-circle me-1" style="font-size: 8px;"></i><?= ucfirst($profile['availability']) ?>
                    </span>
                </div>

                <?= renderStars((float)$profile['avg_rating']) ?>
                <p class="small text-muted mb-4"><?= $profile['total_reviews'] ?> total client reviews</p>

                <div class="text-start bg-light p-3 rounded-3 mb-4 small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Location:</span>
                        <span class="fw-bold"><?= e($profile['location'] ?? 'Remote') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><i class="fas fa-indian-rupee-sign me-2 text-success"></i>Hourly Rate:</span>
                        <span class="fw-bold text-success"><?= formatMoney((float)$profile['hourly_rate']) ?>/hr</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><i class="fas fa-check-circle me-2 text-info"></i>Completed Jobs:</span>
                        <span class="fw-bold"><?= $profile['completed_projects'] ?> projects</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted"><i class="fas fa-clock me-2 text-warning"></i>Experience:</span>
                        <span class="fw-bold"><?= $profile['experience_years'] ?> years</span>
                    </div>
                </div>

                <?php if (!empty($profile['resume'])): ?>
                    <a href="<?= baseUrl('assets/uploads/resumes/' . e($profile['resume'])) ?>" target="_blank" class="btn btn-outline-secondary w-100 mb-2 fw-bold"><i class="fas fa-file-pdf text-danger me-2"></i>Download Resume / CV</a>
                <?php endif; ?>

                <?php if (isLoggedIn()): ?>
                    <?php if (currentUserRole() === 'client'): ?>
                        <a href="<?= baseUrl('client/post-project.php') ?>" class="btn btn-success w-100 mb-2 fw-bold"><i class="fas fa-handshake me-2"></i>Invite to Job</a>
                    <?php endif; ?>
                    <a href="<?= baseUrl('messages.php?to=' . $userId) ?>" class="btn btn-primary w-100 mb-2 fw-bold"><i class="fas fa-paper-plane me-2"></i>Send Message</a>
                    <a href="<?= baseUrl('report.php?user_id=' . $userId) ?>" class="btn btn-link text-danger btn-sm text-decoration-none mt-1"><i class="fas fa-flag me-1"></i>Report Profile</a>
                <?php else: ?>
                    <a href="<?= baseUrl('login.php') ?>" class="btn btn-primary w-100 fw-bold"><i class="fas fa-lock me-2"></i>Login to Hire</a>
                <?php endif; ?>

            </div>

            <!-- Skills Card -->
            <?php if (!empty($skills)): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent fw-bold py-3"><i class="fas fa-tools me-2 text-primary"></i>Verified Skills</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($skills as $skill): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-2">
                                <?= e($skill['name']) ?> 
                                <?php if (!empty($skill['proficiency'])): ?>
                                    <small class="opacity-75"> (<?= ucfirst($skill['proficiency']) ?>)</small>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Badges & Achievements -->
            <?php if (!empty($achievements)): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent fw-bold py-3"><i class="fas fa-award me-2 text-warning"></i>Achievements</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($achievements as $ach): ?>
                            <span class="badge bg-warning bg-opacity-15 text-dark border border-warning px-3 py-2" title="<?= e($ach['description']) ?>">
                                <i class="fas fa-<?= e($ach['icon']) ?> me-1 text-warning"></i><?= e($ach['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- About Card -->
            <?php if ($profile['bio']): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent fw-bold py-3"><i class="fas fa-user-circle me-2 text-primary"></i>About Me</div>
                <div class="card-body">
                    <p class="text-secondary leading-relaxed mb-0"><?= nl2br(e($profile['bio'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Portfolio Showcase -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-laptop-code me-2 text-success"></i>Portfolio Showcase</span>
                    <span class="badge bg-secondary rounded-pill"><?= count($portfolios) ?> items</span>
                </div>
                <div class="card-body">
                    <?php if (empty($portfolios)): ?>
                        <p class="text-muted my-3 text-center">No portfolio items uploaded yet.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($portfolios as $item): ?>
                            <div class="col-md-6">
                                <div class="card h-100 border overflow-hidden shadow-sm hover-top">
                                    <div class="position-relative bg-dark" style="height: 160px;">
                                        <?php if ($item['image']): ?>
                                            <img src="<?= baseUrl('assets/uploads/portfolios/' . e($item['image'])) ?>" class="w-100 h-100" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-secondary text-white">
                                                <i class="fas fa-code fa-2x opacity-50"></i>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($item['project_url']): ?>
                                            <a href="<?= e($item['project_url']) ?>" target="_blank" class="btn btn-sm btn-light rounded-circle position-absolute top-0 end-0 m-2 shadow" title="Visit Demo">
                                                <i class="fas fa-external-link-alt text-primary"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold mb-1"><?= e($item['title']) ?></h6>
                                        <p class="text-muted small mb-2 text-truncate-2"><?= e($item['description']) ?></p>
                                        <?php if (!empty($item['technologies'])): ?>
                                            <div class="small text-primary fw-semibold"><?= e($item['technologies']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Client Reviews -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent fw-bold py-3"><i class="fas fa-star me-2 text-warning"></i>Client Feedback & Ratings</div>
                <div class="card-body">
                    <?php if (empty($reviews)): ?>
                        <p class="text-muted my-3 text-center">No client reviews yet.</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= getAvatarUrl($review['avatar']) ?>" class="rounded-circle" width="32" height="32" alt="Avatar">
                                    <strong class="text-dark"><?= e($review['first_name'] . ' ' . $review['last_name']) ?></strong>
                                </div>
                                <?= renderStars((float)$review['rating']) ?>
                            </div>
                            <p class="text-secondary small mb-1"><?= e($review['comment'] ?? 'Client left a rating with no comment.') ?></p>
                            <small class="text-muted"><?= formatDate($review['created_at']) ?></small>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
