<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Help Center';

$helpTopics = [
    ['icon' => 'fa-user-plus', 'title' => 'Getting Started', 'desc' => 'Create an account, verify your email, and set up your profile.', 'link' => 'register.php'],
    ['icon' => 'fa-folder-open', 'title' => 'Posting Projects', 'desc' => 'Learn how to post, edit, and manage projects as a client.', 'link' => 'client/post-project.php'],
    ['icon' => 'fa-gavel', 'title' => 'Submitting Bids', 'desc' => 'Find projects and submit winning proposals as a freelancer.', 'link' => 'projects.php'],
    ['icon' => 'fa-layer-group', 'title' => 'Milestone Escrow', 'desc' => 'Break contracts into milestones and release payments safely.', 'link' => 'client/contracts.php'],
    ['icon' => 'fa-envelope', 'title' => 'Messaging', 'desc' => 'Communicate with clients or freelancers in real time.', 'link' => 'messages.php'],
    ['icon' => 'fa-shield-alt', 'title' => 'Disputes & Safety', 'desc' => 'Report issues and resolve conflicts through our dispute system.', 'link' => 'contact.php'],
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container text-center text-white py-5">
        <h1 class="display-5 fw-bold">Help Center</h1>
        <p class="lead opacity-90 mb-0">Find answers and get support</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4 mb-5">
        <?php foreach ($helpTopics as $topic): ?>
        <div class="col-md-6 col-lg-4">
            <a href="<?= baseUrl($topic['link']) ?>" class="text-decoration-none">
                <div class="card help-card h-100 p-4">
                    <div class="feature-icon bg-teal mb-3"><i class="fas <?= $topic['icon'] ?>"></i></div>
                    <h5 class="text-dark"><?= e($topic['title']) ?></h5>
                    <p class="text-muted small mb-0"><?= e($topic['desc']) ?></p>
                    <span class="text-primary small fw-semibold mt-2 d-inline-block">Learn more <i class="fas fa-arrow-right ms-1"></i></span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card p-4 text-center">
        <h4 class="fw-bold">Still need help?</h4>
        <p class="text-muted">Our support team is ready to assist you.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= baseUrl('faq.php') ?>" class="btn btn-outline-primary">Browse FAQ</a>
            <a href="<?= baseUrl('contact.php') ?>" class="btn btn-primary">Contact Support</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
