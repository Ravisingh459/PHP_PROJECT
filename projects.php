<?php
/**
 * FreelanceHub - Browse Projects (Public)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Browse Projects';
$page = max(1, (int)($_GET['page'] ?? 1));
$search = sanitize($_GET['q'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$minBudget = (float)($_GET['min_budget'] ?? 0);
$maxBudget = (float)($_GET['max_budget'] ?? 0);
$skillId = (int)($_GET['skill'] ?? 0);

$where = ['p.status = "open"'];
$params = [];

if ($search) {
    $where[] = '(p.title LIKE ? OR p.description LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($category) {
    $where[] = 'p.category = ?';
    $params[] = $category;
}
if ($minBudget > 0) {
    $where[] = 'p.budget >= ?';
    $params[] = $minBudget;
}
if ($maxBudget > 0) {
    $where[] = 'p.budget <= ?';
    $params[] = $maxBudget;
}

$whereClause = implode(' AND ', $where);
$joinSkill = $skillId ? 'JOIN project_skills ps ON ps.project_id = p.id AND ps.skill_id = ?' : '';

$countSql = "SELECT COUNT(DISTINCT p.id) FROM projects p {$joinSkill} WHERE {$whereClause}";
$countParams = $skillId ? array_merge([$skillId], $params) : $params;
$countStmt = db()->prepare($countSql);
$countStmt->execute($countParams);
$total = (int) $countStmt->fetchColumn();

$pagination = paginate($total, $page);

$sql = "SELECT DISTINCT p.*, u.first_name, u.last_name,
    (SELECT COUNT(*) FROM bids WHERE project_id = p.id) as bid_count
    FROM projects p
    JOIN users u ON p.client_id = u.id
    {$joinSkill}
    WHERE {$whereClause}
    ORDER BY p.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
$stmt = db()->prepare($sql);
$stmt->execute($countParams);
$projects = $stmt->fetchAll();

$skills = db()->query('SELECT * FROM skills ORDER BY name')->fetchAll();
$categories = db()->query('SELECT DISTINCT category FROM projects ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/header.php';
echo displayFlash();
?>

<div class="container py-5">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="filter-sidebar">
                <h5 class="fw-bold mb-3"><i class="fas fa-filter me-2"></i>Filters</h5>
                <form method="GET" action="">
                    <div class="mb-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Keywords...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Min Budget</label>
                            <input type="number" name="min_budget" class="form-control" value="<?= $minBudget ?: '' ?>" placeholder="₹0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Max Budget</label>
                            <input type="number" name="max_budget" class="form-control" value="<?= $maxBudget ?: '' ?>" placeholder="₹10,00,000">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    <a href="<?= baseUrl('projects.php') ?>" class="btn btn-outline-secondary w-100 mt-2">Clear</a>
                </form>
            </div>
        </div>

        <!-- Projects List -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Projects <span class="text-muted fs-6">(<?= $total ?> found)</span></h2>
            </div>

            <?php if (empty($projects)): ?>
                <div class="card p-5 text-center">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No projects found</h5>
                    <p class="text-muted">Try adjusting your filters or check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($projects as $project): ?>
                <div class="card mb-3 project-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="mb-2">
                                    <span class="badge bg-light text-dark me-2"><?= e($project['category']) ?></span>
                                    <small class="text-muted">Posted <?= timeAgo($project['created_at']) ?></small>
                                </div>
                                <h5>
                                    <a href="<?= baseUrl('project-detail.php?id=' . $project['id']) ?>" class="text-decoration-none">
                                        <?= e($project['title']) ?>
                                    </a>
                                </h5>
                                <p class="text-muted mb-2"><?= e(substr($project['description'], 0, 200)) ?>...</p>
                                <?php $projectSkills = getProjectSkills($project['id']); if ($projectSkills): ?>
                                    <div class="mb-2">
                                        <?php foreach ($projectSkills as $ps): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary me-1"><?= e($ps['name']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">by <?= e($project['first_name'] . ' ' . $project['last_name']) ?></small>
                            </div>
                            <div class="text-end ms-3">
                                <div class="budget mb-2"><?= formatMoney((float)$project['budget']) ?></div>
                                <small class="text-muted d-block"><i class="fas fa-gavel me-1"></i><?= $project['bid_count'] ?> bids</small>
                                <small class="text-muted d-block"><i class="fas fa-clock me-1"></i><?= formatDate($project['deadline']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?= renderPagination($pagination, baseUrl('projects.php?' . http_build_query(array_filter([
                    'q' => $search, 'category' => $category, 'skill' => $skillId ?: null,
                    'min_budget' => $minBudget ?: null, 'max_budget' => $maxBudget ?: null
                ])))) ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
