<?php
/**
 * NexaWork - Freelancer Dashboard (Arjun Singh Panel)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$userId = currentUserId();
$pageTitle = 'Freelancer Dashboard';
$sidebarRole = 'freelancer';
$profile = getFreelancerProfile($userId);
$user = currentUser();

$stats = [];
$stmt = db()->prepare('SELECT COUNT(*) FROM bids WHERE freelancer_id = ?');
$stmt->execute([$userId]);
$stats['total_bids'] = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM bids WHERE freelancer_id = ? AND status = "pending"');
$stmt->execute([$userId]);
$stats['pending_bids'] = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM contracts WHERE freelancer_id = ? AND status = "active"');
$stmt->execute([$userId]);
$stats['active_contracts'] = (int) $stmt->fetchColumn();

$stats['earnings'] = (float) ($profile['total_earnings'] ?? 0);
$stats['rating'] = (float) ($profile['avg_rating'] ?? 0);

$recommendedProjects = getRecommendedProjects($userId, 5);

$stmt = db()->prepare('SELECT b.*, p.title, p.budget, p.client_id, u.first_name as client_fname, u.last_name as client_lname FROM bids b JOIN projects p ON b.project_id = p.id JOIN users u ON p.client_id = u.id WHERE b.freelancer_id = ? ORDER BY b.created_at DESC LIMIT 5');
$stmt->execute([$userId]);
$recentBids = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<!-- Welcome Hero Banner -->
<div class="card bg-primary text-white mb-4 shadow-sm border-0" style="background: linear-gradient(135deg, #4F7CFF, #8B5CF6) !important;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= getAvatarUrl($user['avatar']) ?>" class="rounded-circle border border-2 border-white shadow-sm" width="64" height="64" style="object-fit:cover;">
                <div>
                    <h4 class="fw-bold mb-1">Welcome back, <?= e($user['first_name'] . ' ' . $user['last_name']) ?>! 👋</h4>
                    <p class="mb-0 text-white-50"><i class="fas fa-briefcase me-1"></i><?= e($profile['title'] ?? 'Full Stack Developer') ?> &bull; <i class="fas fa-map-marker-alt me-1"></i><?= e($profile['location'] ?? 'Bangalore, India') ?> &bull; <i class="fas fa-check-circle text-warning me-1"></i>Verified Freelancer</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="<?= baseUrl('freelancer/projects.php') ?>" class="btn btn-light text-primary fw-bold btn-sm px-3">
                    <i class="fas fa-search me-1"></i>Find Projects
                </a>
                <a href="<?= baseUrl('freelancer/portfolio.php') ?>" class="btn btn-outline-light btn-sm px-3">
                    <i class="fas fa-plus me-1"></i>Add Portfolio
                </a>
                <a href="<?= baseUrl('freelancer/earnings.php') ?>" class="btn btn-outline-light btn-sm px-3">
                    <i class="fas fa-wallet me-1"></i>Earnings
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stat Cards Row -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-gradient-primary">
            <div class="stat-value"><?= number_format($stats['total_bids']) ?></div>
            <div class="stat-label">Total Proposals</div>
            <i class="fas fa-gavel stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-warning">
            <div class="stat-value"><?= number_format($stats['active_contracts']) ?></div>
            <div class="stat-label">Active Contracts</div>
            <i class="fas fa-file-contract stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-success">
            <div class="stat-value"><?= formatMoney($stats['earnings']) ?></div>
            <div class="stat-label">Total Earnings</div>
            <i class="fas fa-indian-rupee-sign stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-info">
            <div class="stat-value"><?= number_format($stats['rating'], 1) ?> ⭐</div>
            <div class="stat-label">Avg Client Rating</div>
            <i class="fas fa-star stat-icon"></i>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- AI Smart Match Recommendations -->
    <div class="col-lg-7">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-brain text-primary me-2"></i>AI Smart Match Projects</h6>
                <a href="<?= baseUrl('freelancer/projects.php') ?>" class="btn btn-sm btn-outline-primary fw-semibold">Browse All Projects</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recommendedProjects)): ?>
                    <p class="p-4 text-muted text-center mb-0">No smart recommendations found right now. Check back soon or update your skills profile!</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-7">
                            <tr>
                                <th>Project Title</th>
                                <th>Budget</th>
                                <th>AI Match</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recommendedProjects as $rp): ?>
                            <tr>
                                <td>
                                    <a href="<?= baseUrl('project-detail.php?id=' . $rp['id']) ?>" class="fw-semibold text-decoration-none">
                                        <?= e($rp['title']) ?>
                                    </a>
                                    <small class="d-block text-muted"><?= e($rp['category'] ?? 'Software Development') ?></small>
                                </td>
                                <td><span class="fw-bold text-success"><?= formatMoney((float)$rp['budget']) ?></span></td>
                                <td><?= renderMatchBadge($rp['match_score'] ?? 85) ?></td>
                                <td>
                                    <a href="<?= baseUrl('project-detail.php?id=' . $rp['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i>Bid
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Proposals Timeline -->
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-history text-warning me-2"></i>My Recent Bids</h6>
                <a href="<?= baseUrl('freelancer/bids.php') ?>" class="btn btn-sm btn-outline-secondary fs-7">View All Bids</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentBids)): ?>
                    <p class="p-4 text-muted text-center mb-0">You haven't submitted any proposals yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentBids as $bid): ?>
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 fw-semibold text-truncate me-2" style="max-width: 220px;">
                                    <?= e($bid['title']) ?>
                                </h6>
                                <?= getStatusBadge($bid['status']) ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center text-muted fs-7">
                                <span><i class="fas fa-user me-1"></i>Client: <?= e($bid['client_fname'] . ' ' . $bid['client_lname']) ?></span>
                                <span class="fw-bold text-success"><?= formatMoney((float)$bid['proposed_budget']) ?></span>
                            </div>
                            <small class="text-muted fs-8 d-block mt-1"><i class="fas fa-clock me-1"></i>Submitted <?= timeAgo($bid['created_at']) ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
