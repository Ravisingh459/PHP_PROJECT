<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'About Us';

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container text-center text-white py-5">
        <h1 class="display-5 fw-bold">About <?= e(APP_NAME) ?></h1>
        <p class="lead opacity-90 mb-0">Building the future of freelance work in India and beyond</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-6">
            <h2 class="section-title">Our Mission</h2>
            <p class="text-muted">NexaWork connects businesses with verified freelancers through AI-powered matching, milestone escrow, and transparent collaboration tools. We believe great work happens when the right talent meets the right opportunity.</p>
            <p class="text-muted">From startups in Bangalore to enterprises in Mumbai, thousands of clients and freelancers trust NexaWork to get projects done efficiently and securely.</p>
        </div>
        <div class="col-lg-6">
            <div class="card p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="fas fa-bullseye text-primary me-2"></i>What We Stand For</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i><strong>Trust</strong> — Verified profiles and trust scores</li>
                    <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i><strong>Transparency</strong> — Milestone-based escrow payments</li>
                    <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i><strong>Intelligence</strong> — Smart match scoring for better hires</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i><strong>Community</strong> — A marketplace built for growth</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2 text-center">
        <div class="col-md-4">
            <div class="feature-card h-100">
                <div class="feature-icon bg-teal mx-auto"><i class="fas fa-users"></i></div>
                <h5>10,000+ Users</h5>
                <p class="text-muted small mb-0">Growing community of clients and freelancers</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card h-100">
                <div class="feature-icon bg-coral mx-auto"><i class="fas fa-briefcase"></i></div>
                <h5>5,000+ Projects</h5>
                <p class="text-muted small mb-0">Successfully posted and completed</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card h-100">
                <div class="feature-icon bg-purple mx-auto"><i class="fas fa-star"></i></div>
                <h5>4.8 Avg Rating</h5>
                <p class="text-muted small mb-0">Highly rated by our community</p>
            </div>
        </div>
    </div>

    <div class="text-center mt-5">
        <a href="<?= baseUrl('register.php') ?>" class="btn btn-primary btn-lg me-2">Join NexaWork</a>
        <a href="<?= baseUrl('contact.php') ?>" class="btn btn-outline-primary btn-lg">Contact Us</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
