<?php
/**
 * FreelanceHub - Categories Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Categories';

$categories = db()->query('SELECT category, COUNT(*) as count, AVG(budget) as avg_budget FROM projects WHERE status = "open" GROUP BY category ORDER BY count DESC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <h2 class="section-title text-center">Browse Categories</h2>
    <p class="section-subtitle text-center">Explore projects by category</p>

    <div class="row g-4">
        <?php
        $icons = ['Web Development'=>'fa-code','Mobile Development'=>'fa-mobile-alt','Design'=>'fa-paint-brush','Writing'=>'fa-pen-fancy','Marketing'=>'fa-bullhorn','Data Science'=>'fa-chart-line'];
        $colors = ['#4F7CFF','#22D3EE','#8B5CF6','#F59E0B','#22C55E','#3B62D9'];
        $i = 0;
        foreach ($categories as $cat):
        ?>
        <div class="col-md-4">
            <a href="<?= baseUrl('projects.php?category=' . urlencode($cat['category'])) ?>" class="text-decoration-none">
                <div class="card category-card h-100">
                    <div class="category-icon" style="background:<?= $colors[$i % count($colors)] ?>">
                        <i class="fas <?= $icons[$cat['category']] ?? 'fa-folder' ?>"></i>
                    </div>
                    <h5><?= e($cat['category']) ?></h5>
                    <p class="text-muted"><?= $cat['count'] ?> open projects</p>
                    <p class="small text-muted">Avg budget: <?= formatMoney((float)$cat['avg_budget']) ?></p>
                </div>
            </a>
        </div>
        <?php $i++; endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
