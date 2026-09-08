<?php
/**
 * FreelanceHub - Browse Freelancers (Public)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Find Freelancers';
$page = max(1, (int)($_GET['page'] ?? 1));
$search = sanitize($_GET['q'] ?? '');
$skillId = (int)($_GET['skill'] ?? 0);
$minRating = (float)($_GET['min_rating'] ?? 0);
$location = sanitize($_GET['location'] ?? '');

$where = ['u.is_active = 1', 'u.role = "freelancer"'];
$params = [];

if ($search) {
    $where[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR fp.title LIKE ? OR fp.bio LIKE ?)';
    $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"]);
}
if ($minRating > 0) {
    $where[] = 'fp.avg_rating >= ?';
    $params[] = $minRating;
}
if ($location) {
    $where[] = 'fp.location LIKE ?';
    $params[] = "%{$location}%";
}

$whereClause = implode(' AND ', $where);
$joinSkill = $skillId ? 'JOIN freelancer_skills fs ON fs.freelancer_id = fp.id AND fs.skill_id = ?' : '';

$countSql = "SELECT COUNT(DISTINCT fp.id) FROM freelancer_profiles fp JOIN users u ON fp.user_id = u.id {$joinSkill} WHERE {$whereClause}";
$countParams = $skillId ? array_merge([$skillId], $params) : $params;
$countStmt = db()->prepare($countSql);
$countStmt->execute($countParams);
$total = (int) $countStmt->fetchColumn();
$pagination = paginate($total, $page);

$sql = "SELECT DISTINCT fp.*, u.first_name, u.last_name, u.avatar, u.is_verified
    FROM freelancer_profiles fp
    JOIN users u ON fp.user_id = u.id
    {$joinSkill}
    WHERE {$whereClause}
    ORDER BY fp.avg_rating DESC, fp.completed_projects DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
$stmt = db()->prepare($sql);
$stmt->execute($countParams);
$freelancers = $stmt->fetchAll();

$skills = db()->query('SELECT * FROM skills ORDER BY name')->fetchAll();

require_once __DIR__ . '/includes/header.php';
echo displayFlash();
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="filter-sidebar">
                <h5 class="fw-bold mb-3"><i class="fas fa-filter me-2"></i>Filters</h5>
                <form method="GET">
                    <div class="mb-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="q" class="form-control" value="<?= e($search) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Skill</label>
                        <select name="skill" class="form-select">
                            <option value="">All Skills</option>
                            <?php foreach ($skills as $skill): ?>
                                <option value="<?= $skill['id'] ?>" <?= $skillId === (int)$skill['id'] ? 'selected' : '' ?>><?= e($skill['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Min Rating</label>
                        <select name="min_rating" class="form-select">
                            <option value="0">Any Rating</option>
                            <?php for ($r = 4; $r <= 5; $r += 0.5): ?>
                                <option value="<?= $r ?>" <?= $minRating == $r ? 'selected' : '' ?>><?= $r ?>+ stars</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= e($location) ?>" placeholder="e.g. Bangalore, Mumbai, Pune">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                </form>
            </div>
        </div>

        <div class="col-lg-9">
            <h2 class="mb-4">Freelancers in India <span class="text-muted fs-6">(<?= $total ?>)</span></h2>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <?php
                $indianCities = ['Bangalore', 'Mumbai', 'Hyderabad', 'Pune', 'Delhi', 'Chennai', 'Kolkata'];
                foreach ($indianCities as $city):
                    $active = stripos($location, $city) !== false ? 'active' : '';
                ?>
                <a href="<?= baseUrl('freelancers.php?location=' . urlencode($city)) ?>" class="btn btn-sm btn-outline-primary <?= $active ?>"><?= $city ?></a>
                <?php endforeach; ?>
                <?php if ($location): ?>
                <a href="<?= baseUrl('freelancers.php') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
            <div class="row g-4">
                <?php foreach ($freelancers as $fl): ?>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex">
                            <img src="<?= getAvatarUrl($fl['avatar']) ?>" class="rounded-circle me-3" width="64" height="64" style="object-fit:cover;">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">
                                    <a href="<?= baseUrl('freelancer-profile.php?id=' . $fl['user_id']) ?>" class="text-decoration-none">
                                        <?= e($fl['first_name'] . ' ' . $fl['last_name']) ?>
                                    </a>
                                </h5>
                                <p class="text-muted small mb-1"><?= e($fl['title'] ?? 'Freelancer') ?></p>
                                <?= renderTrustBadge(calculateTrustScore($fl)) ?>
                                <?= renderStars((float)$fl['avg_rating']) ?>
                                <div class="d-flex gap-3 mt-2 small text-muted">
                                    <span><i class="fas fa-briefcase me-1"></i><?= $fl['completed_projects'] ?> projects</span>
                                    <span><i class="fas fa-indian-rupee-sign me-1"></i><?= formatMoney((float)$fl['hourly_rate']) ?>/hr</span>
                                    <?php if ($fl['location']): ?>
                                        <span><i class="fas fa-map-marker-alt me-1"></i><?= e($fl['location']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4"><?= renderPagination($pagination, baseUrl('freelancers.php?' . http_build_query(array_filter([
                'q' => $search, 'skill' => $skillId ?: null, 'min_rating' => $minRating ?: null, 'location' => $location
            ])))) ?></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
