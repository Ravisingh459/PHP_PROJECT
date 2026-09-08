<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'FAQ';

$faqs = [
    ['q' => 'How do I create an account?', 'a' => 'Click Register, choose Client or Freelancer, fill in your details, and verify your email. You can start posting projects or bidding immediately after verification.'],
    ['q' => 'How does Smart Match scoring work?', 'a' => 'Our engine scores freelancers and projects based on skill alignment, ratings, experience, and budget fit. Higher match percentages mean better alignment for your needs.'],
    ['q' => 'How does escrow payment work?', 'a' => 'When you hire a freelancer, funds are held in escrow. For milestone contracts, you release payment per approved milestone. For standard contracts, payment is released when work is 100% complete.'],
    ['q' => 'Is NexaWork free to use?', 'a' => 'Registration is free. Platform fees may apply on completed transactions. Check project and contract details for specific fee information.'],
    ['q' => 'How do I become a top-ranked freelancer?', 'a' => 'Complete projects successfully, earn high reviews, pass skill tests, and maintain an active profile. Check the Leaderboard to see top performers.'],
    ['q' => 'What if there is a dispute?', 'a' => 'Raise a dispute from your contract page. Our admin team will review the case and help mediate a fair resolution between both parties.'],
    ['q' => 'Can I work with clients across India?', 'a' => 'Yes! NexaWork connects freelancers with clients from Bangalore, Mumbai, Delhi, Hyderabad, Pune, Chennai, and all major Indian cities. Set your location and availability in your profile to attract the right opportunities.'],
    ['q' => 'How do I reset my password?', 'a' => 'Click Forgot Password on the login page, enter your email, and follow the reset link sent to your inbox. Links expire after one hour.'],
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container text-center text-white py-5">
        <h1 class="display-5 fw-bold">Frequently Asked Questions</h1>
        <p class="lead opacity-90 mb-0">Quick answers to common questions</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="accordion" id="faqAccordion">
                <?php foreach ($faqs as $i => $faq): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                            <?= e($faq['q']) ?>
                        </button>
                    </h2>
                    <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted"><?= e($faq['a']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-5">
                <p class="text-muted">Didn't find your answer?</p>
                <a href="<?= baseUrl('contact.php') ?>" class="btn btn-primary">Contact Us</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
