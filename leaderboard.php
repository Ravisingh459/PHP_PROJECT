<?php
/**
 * NexaWork - Freelancer Leaderboard
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Leaderboard';
$leaderboard = getLeaderboard(25);

require_once __DIR__ . '/includes/header.php';
?>

<div class="leaderboard-hero">
    <div class="container text-center text-white py-5">
        <h1 class="display-5 fw-bold mb-2"><i class="fas fa-trophy me-2"></i>Top Talent Leaderboard</h1>
        <p class="lead opacity-90 mb-0">Ranked by ratings, completed projects, and total earnings</p>
    </div>
</div>

<div class="container py-5">
    <?php if (empty($leaderboard)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-users fa-3x mb-3 opacity-50"></i>
            <p>No freelancers ranked yet. Be the first to complete a project!</p>
        </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($leaderboard as $index => $fl):
            $isTop3 = $fl['rank'] <= 3;
            $medalClass = $fl['rank'] === 1 ? 'gold' : ($fl['rank'] === 2 ? 'silver' : ($fl['rank'] === 3 ? 'bronze' : ''));
        ?>
        <div class="col-lg-6">
            <div class="card leaderboard-card <?= $isTop3 ? 'leaderboard-top' : '' ?> h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="leaderboard-rank <?= $medalClass ?>">
                        <?php if ($fl['rank'] <= 3): ?>
                            <i class="fas fa-medal"></i>
                        <?php else: ?>
                            #<?= $fl['rank'] ?>
                        <?php endif; ?>
                    </div>
                    <img src="<?= getAvatarUrl($fl['avatar']) ?>" alt="" class="rounded-circle" width="56" height="56">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="mb-0"><?= e($fl['first_name'] . ' ' . $fl['last_name']) ?></h5>
                            <?= renderTrustBadge($fl['trust_score']) ?>
                        </div>
                        <p class="text-muted small mb-2"><?= e($fl['title'] ?? 'Freelancer') ?></p>
                        <div class="d-flex gap-3 small">
                            <?= renderStars((float) $fl['avg_rating']) ?>
                            <span><i class="fas fa-briefcase me-1"></i><?= $fl['completed_projects'] ?> projects</span>
                            <span class="text-success fw-semibold"><?= formatMoney((float) $fl['total_earnings']) ?></span>
                        </div>
                    </div>
                    <a href="<?= baseUrl('freelancer-profile.php?id=' . $fl['user_id']) ?>" class="btn btn-outline-primary btn-sm">View</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
