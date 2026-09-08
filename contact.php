<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact Us';

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $subject && $message) {
        setFlash('success', 'Thank you! Your message has been sent. We will get back to you within 24 hours.');
        redirect(baseUrl('contact.php'));
    }
    setFlash('danger', 'Please fill in all fields with a valid email address.');
    redirect(baseUrl('contact.php'));
}

require_once __DIR__ . '/includes/header.php';
echo displayFlash();
?>

<div class="page-hero">
    <div class="container text-center text-white py-5">
        <h1 class="display-5 fw-bold">Contact Us</h1>
        <p class="lead opacity-90 mb-0">We'd love to hear from you</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-5">
            <h3 class="fw-bold mb-4">Get in Touch</h3>
            <div class="mb-4">
                <h6 class="fw-semibold"><i class="fas fa-envelope text-primary me-2"></i>Email</h6>
                <p class="text-muted mb-0">support@nexawork.com</p>
            </div>
            <div class="mb-4">
                <h6 class="fw-semibold"><i class="fas fa-phone text-primary me-2"></i>Phone</h6>
                <p class="text-muted mb-0">+91 80 4567 8900</p>
            </div>
            <div class="mb-4">
                <h6 class="fw-semibold"><i class="fas fa-map-marker-alt text-primary me-2"></i>Office</h6>
                <p class="text-muted mb-0">Koramangala, Bangalore<br>Karnataka 560034, India</p>
            </div>
            <div>
                <h6 class="fw-semibold mb-2">Business Hours</h6>
                <p class="text-muted mb-0">Mon – Fri: 9:00 AM – 6:00 PM IST</p>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card p-4">
                <h4 class="fw-bold mb-4">Send a Message</h4>
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Your Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-2"></i>Send Message</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
