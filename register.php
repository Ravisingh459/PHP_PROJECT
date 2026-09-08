<?php
/**
 * FreelanceHub - User Registration
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectToDashboard();
}

$pageTitle = 'Register';
$defaultRole = in_array($_GET['role'] ?? '', ['client', 'freelancer']) ? $_GET['role'] : 'client';
$errors = [];

if (isPost()) {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $result = registerUser($_POST);
        if ($result['success']) {
            setFlash('success', 'Registration successful! Please check your email to verify your account.');
            redirect(baseUrl('login.php'));
        } else {
            $errors = $result['errors'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <h3><i class="fas fa-bolt me-2"></i><?= e(APP_NAME) ?></h3>
            <p class="text-muted">Join India's talent marketplace</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrfField() ?>

            <div class="mb-3">
                <label class="form-label">I want to</label>
                <div class="d-flex gap-3">
                    <div class="form-check flex-fill">
                        <input class="form-check-input" type="radio" name="role" id="roleClient" value="client" <?= $defaultRole === 'client' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="roleClient">
                            <i class="fas fa-building me-1"></i> Hire Freelancers
                        </label>
                    </div>
                    <div class="form-check flex-fill">
                        <input class="form-check-input" type="radio" name="role" id="roleFreelancer" value="freelancer" <?= $defaultRole === 'freelancer' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="roleFreelancer">
                            <i class="fas fa-laptop-code me-1"></i> Find Work
                        </label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" required value="<?= e($_POST['first_name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required value="<?= e($_POST['last_name'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Phone (optional)</label>
                <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?= e($_POST['phone'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" id="reg-password" name="password" class="form-control" required minlength="<?= PASSWORD_MIN_LENGTH ?>">
                <div class="progress mt-2" style="height: 6px;">
                    <div id="password-strength-bar" class="progress-bar bg-danger" role="progressbar" style="width: 0%;"></div>
                </div>
                <small id="password-strength-text" class="text-muted d-block mt-1">Minimum <?= PASSWORD_MIN_LENGTH ?> characters with letters, numbers & symbols</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" id="reg-confirm-password" name="confirm_password" class="form-control" required>
            </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pwd = document.getElementById('reg-password');
    const bar = document.getElementById('password-strength-bar');
    const text = document.getElementById('password-strength-text');

    if (pwd) {
        pwd.addEventListener('input', function() {
            const val = pwd.value;
            let score = 0;
            if (val.length >= 8) score += 25;
            if (val.length >= 12) score += 15;
            if (/[A-Z]/.test(val)) score += 20;
            if (/[a-z]/.test(val)) score += 20;
            if (/[0-9]/.test(val)) score += 10;
            if (/[^a-zA-Z0-9]/.test(val)) score += 10;

            score = Math.min(100, score);
            bar.style.width = score + '%';

            if (score >= 80) {
                bar.className = 'progress-bar bg-success';
                text.innerHTML = '<span class="text-success fw-bold">Strong password!</span>';
            } else if (score >= 50) {
                bar.className = 'progress-bar bg-warning';
                text.innerHTML = '<span class="text-warning fw-bold">Medium password</span>';
            } else {
                bar.className = 'progress-bar bg-danger';
                text.innerHTML = '<span class="text-danger">Weak password - add numbers & symbols</span>';
            }
        });
    }
});
</script>


            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" required>
                <label class="form-check-label" for="terms">I agree to the <a href="<?= baseUrl('terms.php') ?>">Terms of Service</a></label>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">Create Account</button>

            <p class="text-center text-muted mb-0">
                Already have an account? <a href="<?= baseUrl('login.php') ?>">Login</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>
