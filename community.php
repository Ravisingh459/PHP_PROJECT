<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Community';

$stats = [
    'users'       => (int) db()->query('SELECT COUNT(*) FROM users WHERE role != "admin"')->fetchColumn(),
    'freelancers' => (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "freelancer"')->fetchColumn(),
    'projects'    => (int) db()->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container text-center text-white py-5">
        <h1 class="display-5 fw-bold">NexaWork Community</h1>
        <p class="lead opacity-90 mb-0">Connect, collaborate, and grow together</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4 text-center mb-5">
        <div class="col-md-4">
            <div class="card p-4">
                <div class="fs-2 fw-bold text-primary"><?= number_format($stats['users']) ?>+</div>
                <p class="text-muted mb-0">Community Members</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4">
                <div class="fs-2 fw-bold text-primary"><?= number_format($stats['freelancers']) ?>+</div>
                <p class="text-muted mb-0">Active Freelancers</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4">
                <div class="fs-2 fw-bold text-primary"><?= number_format($stats['projects']) ?>+</div>
                <p class="text-muted mb-0">Projects Posted</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card p-4 h-100">
                <h5 class="fw-bold"><i class="fas fa-trophy text-warning me-2"></i>Leaderboard</h5>
                <p class="text-muted">See top-performing freelancers ranked by ratings, projects, and earnings.</p>
                <a href="<?= baseUrl('leaderboard.php') ?>" class="btn btn-outline-primary">View Leaderboard</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-4 h-100">
                <h5 class="fw-bold"><i class="fas fa-comments text-primary me-2"></i>Discussions</h5>
                <p class="text-muted">Message clients and freelancers directly through our built-in chat system.</p>
                <a href="<?= baseUrl(isLoggedIn() ? 'messages.php' : 'login.php') ?>" class="btn btn-outline-primary">Open Messages</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-4 h-100">
                <h5 class="fw-bold"><i class="fas fa-certificate text-success me-2"></i>Skill Tests</h5>
                <p class="text-muted">Earn certifications by passing skill assessments and stand out to clients.</p>
                <a href="<?= baseUrl(isLoggedIn() && currentUserRole() === 'freelancer' ? 'freelancer/skill-tests.php' : 'register.php?role=freelancer') ?>" class="btn btn-outline-primary">Take Skill Tests</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-4 h-100">
                <h5 class="fw-bold"><i class="fas fa-folder text-info me-2"></i>Browse Projects</h5>
                <p class="text-muted">Explore open projects across categories and find your next opportunity.</p>
                <a href="<?= baseUrl('projects.php') ?>" class="btn btn-outline-primary">Browse Projects</a>
            </div>
        </div>
    </div>

    <div class="text-center mt-5">
        <h4 class="fw-bold">Join the community today</h4>
        <a href="<?= baseUrl('register.php') ?>" class="btn btn-primary btn-lg mt-2">Sign Up Free</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
