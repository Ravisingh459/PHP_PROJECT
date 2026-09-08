<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Terms of Service';

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container text-center text-white py-5">
        <h1 class="display-5 fw-bold">Terms of Service</h1>
        <p class="lead opacity-90 mb-0">Last updated: <?= date('F j, Y') ?></p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 legal-content">
            <div class="card p-4 p-md-5">
                <h4>1. Acceptance of Terms</h4>
                <p class="text-muted">By accessing or using NexaWork, you agree to be bound by these Terms of Service. If you do not agree, please do not use the platform.</p>

                <h4>2. User Accounts</h4>
                <p class="text-muted">You must provide accurate information when registering. You are responsible for maintaining the confidentiality of your account credentials and for all activity under your account.</p>

                <h4>3. Projects & Bids</h4>
                <p class="text-muted">Clients are responsible for accurately describing projects. Freelancers must submit honest proposals and deliver work as agreed. NexaWork facilitates connections but is not a party to contracts between users.</p>

                <h4>4. Payments & Escrow</h4>
                <p class="text-muted">Payments are held in escrow until milestones are approved or project completion is confirmed. NexaWork may charge platform fees as disclosed during transactions.</p>

                <h4>5. Prohibited Conduct</h4>
                <p class="text-muted">Users may not post fraudulent projects, plagiarize work, harass others, circumvent the platform for off-platform payments, or violate applicable laws.</p>

                <h4>6. Disputes</h4>
                <p class="text-muted">Disputes between clients and freelancers should be raised through our dispute resolution system. NexaWork administrators may review and mediate disputes at their discretion.</p>

                <h4>7. Limitation of Liability</h4>
                <p class="text-muted mb-0">NexaWork is provided "as is." We are not liable for damages arising from user interactions, project outcomes, or third-party services. See our <a href="<?= baseUrl('privacy.php') ?>">Privacy Policy</a> for data handling practices.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
