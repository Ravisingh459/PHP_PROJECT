<?php
/**
 * NexaWork - Freelancer Portfolio Showcase (Arjun Singh Panel)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$pageTitle = 'Portfolio Showcase';
$sidebarRole = 'freelancer';
$userId = currentUserId();
$profile = getFreelancerProfile($userId);

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $technologies = sanitize($_POST['technologies'] ?? '');
        $projectUrl = sanitize($_POST['project_url'] ?? '');
        $completedDate = $_POST['completed_date'] ?? null;

        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = uploadFile($_FILES['image'], 'portfolios', ALLOWED_IMAGE_TYPES);
        }

        $stmt = db()->prepare('INSERT INTO portfolios (freelancer_id, title, description, image, project_url, technologies, completed_date) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$profile['id'], $title, $description, $image, $projectUrl, $technologies, $completedDate ?: null]);
        setFlash('success', 'New portfolio project added to your showcase!');
    } elseif ($action === 'delete') {
        $id = (int)($_POST['portfolio_id'] ?? 0);
        db()->prepare('DELETE FROM portfolios WHERE id = ? AND freelancer_id = ?')->execute([$id, $profile['id']]);
        setFlash('info', 'Portfolio item deleted.');
    }
    redirect(baseUrl('freelancer/portfolio.php'));
}

$stmt = db()->prepare('SELECT * FROM portfolios WHERE freelancer_id = ? ORDER BY created_at DESC');
$stmt->execute([$profile['id']]);
$portfolios = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-images text-primary me-2"></i>My Portfolio Showcase</h4>
        <p class="text-muted mb-0">Showcase your completed web applications, tech stack, live demos, and client projects.</p>
    </div>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addPortfolioModal">
        <i class="fas fa-plus me-2"></i>Add New Project
    </button>
</div>

<?php if (empty($portfolios)): ?>
<div class="card p-5 text-center shadow-sm">
    <i class="fas fa-folder-open fa-4x text-muted mb-3 opacity-50"></i>
    <h5>No Portfolio Projects Added Yet</h5>
    <p class="text-muted">Adding project showcases increases your chances of getting hired by clients by over 3x!</p>
    <button class="btn btn-primary w-auto mx-auto fw-bold" data-bs-toggle="modal" data-bs-target="#addPortfolioModal">
        <i class="fas fa-plus me-2"></i>Add Your First Portfolio Item
    </button>
</div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($portfolios as $item): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 overflow-hidden">
            <div class="position-relative bg-dark" style="height: 180px;">
                <?php if ($item['image']): ?>
                    <img src="<?= baseUrl('assets/uploads/portfolios/' . e($item['image'])) ?>" class="w-100 h-100" style="object-fit: cover;">
                <?php else: ?>
                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-gradient-primary text-white">
                        <i class="fas fa-code fa-3x opacity-75"></i>
                    </div>
                <?php endif; ?>
                <?php if ($item['project_url']): ?>
                    <a href="<?= e($item['project_url']) ?>" target="_blank" class="btn btn-sm btn-light rounded-circle position-absolute top-0 end-0 m-2 shadow" title="Visit Live Demo">
                        <i class="fas fa-external-link-alt text-primary"></i>
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-4 d-flex flex-column">
                <h5 class="fw-bold mb-2"><?= e($item['title']) ?></h5>
                <p class="text-muted small flex-grow-1 mb-3"><?= e($item['description']) ?></p>
                
                <?php if (!empty($item['technologies'])): ?>
                <div class="mb-3">
                    <?php 
                    $techs = array_map('trim', explode(',', $item['technologies']));
                    foreach ($techs as $tech): 
                    ?>
                        <span class="badge bg-secondary bg-opacity-10 text-dark border me-1 mb-1 fs-8"><?= e($tech) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-auto fs-7 text-muted">
                    <span><i class="fas fa-calendar-alt me-1"></i><?= $item['completed_date'] ? formatDate($item['completed_date']) : 'Recent' ?></span>
                    <form method="POST" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="portfolio_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this portfolio item?')">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add Portfolio Modal -->
<div class="modal fade" id="addPortfolioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>Add Portfolio Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Project Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. E-Commerce Payment Integration App" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Project Description *</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe the key features, problems solved, and architecture..." required></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Technologies Used</label>
                            <input type="text" name="technologies" class="form-control" placeholder="PHP, React, MySQL, Stripe API">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Live Demo URL</label>
                            <input type="url" name="project_url" class="form-control" placeholder="https://example.com">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Completed Date</label>
                            <input type="date" name="completed_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Project Screenshot / Cover Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="fas fa-save me-2"></i>Save to Portfolio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
