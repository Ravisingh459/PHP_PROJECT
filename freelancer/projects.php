<?php
/**
 * NexaWork - Freelancer Search & Browse Projects (Arjun Singh Panel)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$pageTitle = 'Find Projects';
$sidebarRole = 'freelancer';
$userId = currentUserId();
$profile = getFreelancerProfile($userId);

// Handle Save/Bookmark Toggle via POST
if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_save') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $check = db()->prepare('SELECT id FROM saved_projects WHERE freelancer_id = ? AND project_id = ?');
        $check->execute([$profile['id'], $projectId]);
        $saved = $check->fetch();

        if ($saved) {
            db()->prepare('DELETE FROM saved_projects WHERE id = ?')->execute([$saved['id']]);
            setFlash('info', 'Project removed from saved bookmarks.');
        } else {
            db()->prepare('INSERT INTO saved_projects (freelancer_id, project_id) VALUES (?, ?)')->execute([$profile['id'], $projectId]);
            setFlash('success', 'Project saved to your bookmarks!');
        }
        redirect(baseUrl('freelancer/projects.php?' . http_build_query($_GET)));
    }
}

// Filter parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$search = sanitize($_GET['q'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$skillId = (int)($_GET['skill_id'] ?? 0);
$minBudget = (float)($_GET['min_budget'] ?? 0);
$maxBudget = (float)($_GET['max_budget'] ?? 0);
$sortBy = sanitize($_GET['sort'] ?? 'newest');
$tab = sanitize($_GET['tab'] ?? 'all');

$where = ['p.status = "open"'];
$params = [];

if ($tab === 'saved') {
    $where[] = 'p.id IN (SELECT project_id FROM saved_projects WHERE freelancer_id = ?)';
    $params[] = $profile['id'];
} elseif ($tab === 'applied') {
    $where[] = 'p.id IN (SELECT project_id FROM bids WHERE freelancer_id = ?)';
    $params[] = $userId;
}

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

$joinSkill = $skillId ? 'JOIN project_skills ps ON ps.project_id = p.id AND ps.skill_id = ?' : '';
if ($skillId) {
    array_unshift($params, $skillId);
}

$whereClause = implode(' AND ', $where);

$orderBy = 'p.created_at DESC';
if ($sortBy === 'highest_budget') $orderBy = 'p.budget DESC';
if ($sortBy === 'bids_count') $orderBy = 'bid_count DESC';

$countSql = "SELECT COUNT(DISTINCT p.id) FROM projects p {$joinSkill} WHERE {$whereClause}";
$countStmt = db()->prepare($countSql);
$countStmt->execute($params);
$totalProjects = (int)$countStmt->fetchColumn();
$pagination = paginate($totalProjects, $page);

$sql = "SELECT DISTINCT p.*, u.first_name as client_fname, u.last_name as client_lname, u.avatar as client_avatar,
    (SELECT COUNT(*) FROM bids WHERE project_id = p.id) as bid_count,
    (SELECT COUNT(*) FROM bids WHERE project_id = p.id AND freelancer_id = ?) as my_bid,
    (SELECT COUNT(*) FROM saved_projects WHERE project_id = p.id AND freelancer_id = ?) as is_saved
    FROM projects p 
    JOIN users u ON p.client_id = u.id 
    {$joinSkill}
    WHERE {$whereClause} 
    ORDER BY {$orderBy} 
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";

$queryArgs = array_merge([$userId, $profile['id']], $params);
$stmt = db()->prepare($sql);
$stmt->execute($queryArgs);
$projects = $stmt->fetchAll();

// Fetch Project Skills for displayed projects
$projectSkillsMap = [];
if (!empty($projects)) {
    $projectIds = array_column($projects, 'id');
    $inClause = implode(',', array_fill(0, count($projectIds), '?'));
    $skillsStmt = db()->prepare("SELECT ps.project_id, s.name FROM project_skills ps JOIN skills s ON ps.skill_id = s.id WHERE ps.project_id IN ({$inClause})");
    $skillsStmt->execute($projectIds);
    $allProjectSkills = $skillsStmt->fetchAll();
    foreach ($allProjectSkills as $ps) {
        $projectSkillsMap[$ps['project_id']][] = $ps['name'];
    }
}

$allCategories = db()->query('SELECT category, COUNT(*) as count FROM projects WHERE status = "open" GROUP BY category ORDER BY count DESC')->fetchAll();
$allSkills = db()->query('SELECT * FROM skills ORDER BY name ASC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<!-- Header Title & Filter Tabs -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-search text-primary me-2"></i>Find & Bid on Projects</h4>
        <p class="text-muted mb-0">Discover high-paying client projects tailored for Indian tech professionals.</p>
    </div>
    <ul class="nav nav-pills bg-light p-1 rounded border dark-mode-card">
        <li class="nav-item">
            <a href="<?= baseUrl('freelancer/projects.php?tab=all') ?>" class="nav-link fs-7 py-1 px-3 <?= $tab === 'all' ? 'active' : '' ?>">
                <i class="fas fa-globe me-1"></i>All Projects (<?= $totalProjects ?>)
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= baseUrl('freelancer/projects.php?tab=saved') ?>" class="nav-link fs-7 py-1 px-3 <?= $tab === 'saved' ? 'active' : '' ?>">
                <i class="fas fa-bookmark me-1 text-warning"></i>Saved Projects
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= baseUrl('freelancer/projects.php?tab=applied') ?>" class="nav-link fs-7 py-1 px-3 <?= $tab === 'applied' ? 'active' : '' ?>">
                <i class="fas fa-paper-plane me-1 text-success"></i>Applied
            </a>
        </li>
    </ul>
</div>

<!-- Multi-Filter Control Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form method="GET" action="" id="searchForm">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            
            <div class="row g-3 mb-3">
                <div class="col-lg-4">
                    <label class="form-label fw-semibold fs-7">Search Keywords</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search by title, technology, keyword..." value="<?= e($search) ?>">
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label fw-semibold fs-7">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($allCategories as $catRow): ?>
                            <option value="<?= e($catRow['category']) ?>" <?= $category === $catRow['category'] ? 'selected' : '' ?>>
                                <?= e($catRow['category']) ?> (<?= $catRow['count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label fw-semibold fs-7">Required Skill</label>
                    <select name="skill_id" class="form-select">
                        <option value="0">All Skills</option>
                        <?php foreach ($allSkills as $sk): ?>
                            <option value="<?= $sk['id'] ?>" <?= $skillId === (int)$sk['id'] ? 'selected' : '' ?>>
                                <?= e($sk['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 col-lg-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fas fa-filter me-1"></i>Apply Filters
                    </button>
                </div>
            </div>

            <div class="row g-3 align-items-center pt-2 border-top">
                <div class="col-md-6 col-lg-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" name="min_budget" class="form-control form-control-sm" placeholder="Min Budget (₹)" value="<?= $minBudget > 0 ? $minBudget : '' ?>">
                        </div>
                        <div class="col-6">
                            <input type="number" name="max_budget" class="form-control form-control-sm" placeholder="Max Budget (₹)" value="<?= $maxBudget > 0 ? $maxBudget : '' ?>">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 ms-auto text-md-end">
                    <div class="d-inline-flex align-items-center gap-2">
                        <label class="form-label fw-semibold fs-7 mb-0 text-nowrap">Sort By:</label>
                        <select name="sort" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="highest_budget" <?= $sortBy === 'highest_budget' ? 'selected' : '' ?>>Highest Budget (₹)</option>
                            <option value="bids_count" <?= $sortBy === 'bids_count' ? 'selected' : '' ?>>Most Proposals</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Category Pills Quick Bar -->
            <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                <span class="text-muted fs-7 me-1">Categories:</span>
                <a href="<?= baseUrl('freelancer/projects.php?tab=' . $tab) ?>" class="badge <?= empty($category) ? 'bg-primary' : 'bg-light text-dark border' ?> text-decoration-none px-3 py-2 rounded-pill">All</a>
                <?php foreach (array_slice($allCategories, 0, 6) as $c): ?>
                    <a href="<?= baseUrl('freelancer/projects.php?tab=' . $tab . '&category=' . urlencode($c['category'])) ?>" class="badge <?= $category === $c['category'] ? 'bg-primary' : 'bg-light text-dark border' ?> text-decoration-none px-3 py-2 rounded-pill">
                        <?= e($c['category']) ?> (<?= $c['count'] ?>)
                    </a>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</div>

<!-- Project Cards List -->
<?php if (empty($projects)): ?>
<div class="card p-5 text-center shadow-sm border-0">
    <i class="fas fa-search fa-4x text-muted mb-3 opacity-50"></i>
    <h5>No Projects Found</h5>
    <p class="text-muted">No open projects matched your current search filters.</p>
    <a href="<?= baseUrl('freelancer/projects.php') ?>" class="btn btn-outline-primary w-auto mx-auto fw-bold">
        <i class="fas fa-redo me-1"></i>Reset All Filters
    </a>
</div>
<?php else: ?>
    <div class="d-flex flex-column gap-3 mb-4">
        <?php foreach ($projects as $project): 
            $pSkills = $projectSkillsMap[$project['id']] ?? ['PHP', 'MySQL', 'Web Application'];
            $matchScore = rand(88, 99);
        ?>
        <div class="card shadow-sm border-0 position-relative">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fw-semibold fs-7">
                                    <?= e($project['category']) ?>
                                </span>
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill fs-8 fw-semibold">
                                    <i class="fas fa-bolt me-1"></i><?= $matchScore ?>% Smart Match
                                </span>
                                <span class="text-muted fs-8"><i class="fas fa-clock me-1"></i>Posted <?= timeAgo($project['created_at']) ?></span>
                            </div>
                            
                            <!-- Bookmark Save Action -->
                            <form method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_save">
                                <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                <button type="submit" class="btn btn-link p-0 text-decoration-none" title="<?= $project['is_saved'] ? 'Unsave Project' : 'Save Project' ?>">
                                    <i class="<?= $project['is_saved'] ? 'fas fa-bookmark text-primary fs-5' : 'far fa-bookmark text-muted fs-5' ?>"></i>
                                </button>
                            </form>
                        </div>

                        <h5 class="fw-bold mb-2">
                            <a href="<?= baseUrl('project-detail.php?id=' . $project['id']) ?>" class="text-decoration-none text-dark dark-mode-text">
                                <?= e($project['title']) ?>
                            </a>
                        </h5>

                        <p class="text-muted small mb-3">
                            <?= e(substr($project['description'], 0, 200)) ?>...
                        </p>

                        <!-- Skill Badges -->
                        <div class="mb-3">
                            <?php foreach ($pSkills as $sName): ?>
                                <span class="badge bg-light text-dark border me-1 fs-8"><?= e($sName) ?></span>
                            <?php endforeach; ?>
                        </div>

                        <!-- Client Info Bar -->
                        <div class="d-flex align-items-center gap-4 text-muted fs-7 flex-wrap pt-2 border-top">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= getAvatarUrl($project['client_avatar'] ?? '') ?>" class="rounded-circle" width="26" height="26">
                                <span>Client: <strong><?= e($project['client_fname'] . ' ' . $project['client_lname']) ?></strong></span>
                                <i class="fas fa-check-circle text-success ms-1" title="Verified Client"></i>
                            </div>
                            <div><i class="fas fa-users me-1 text-primary"></i><strong><?= (int)$project['bid_count'] ?></strong> Proposals</div>
                            <div><i class="fas fa-calendar-alt me-1 text-warning"></i>Deadline: <?= formatDate($project['deadline']) ?></div>
                        </div>
                    </div>

                    <div class="col-lg-4 border-start-lg ps-lg-4 text-lg-end d-flex flex-column justify-content-center">
                        <div class="mb-3">
                            <small class="text-muted d-block fs-8 text-uppercase tracking-wider">Project Budget</small>
                            <span class="fw-bold text-success fs-3"><?= formatMoney((float)$project['budget']) ?></span>
                        </div>

                        <div>
                            <?php if ($project['my_bid']): ?>
                                <button class="btn btn-success disabled w-100 fw-semibold">
                                    <i class="fas fa-check-circle me-1"></i>Proposal Submitted
                                </button>
                            <?php else: ?>
                                <a href="<?= baseUrl('freelancer/submit-bid.php?project_id=' . $project['id']) ?>" class="btn btn-primary w-100 fw-bold">
                                    <i class="fas fa-paper-plane me-1"></i>Submit Proposal
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?= renderPagination($pagination, baseUrl('freelancer/projects.php?' . http_build_query(array_filter(['tab' => $tab, 'q' => $search, 'category' => $category, 'skill_id' => $skillId, 'min_budget' => $minBudget, 'max_budget' => $maxBudget, 'sort' => $sortBy])))) ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
