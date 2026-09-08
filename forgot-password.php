<?php
/**
 * FreelanceHub - Forgot Password
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) redirectToDashboard();

$pageTitle = 'Forgot Password';
$success = false;

if (isPost()) {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlash('danger', 'Invalid security token.');
    } else {
        requestPasswordReset($_POST['email'] ?? '');
        $success = true;
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
    <link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <h3><i class="fas fa-key me-2"></i>Reset Password</h3>
            <p class="text-muted">Enter your email to receive a reset link</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                If an account exists with that email, a password reset link has been sent.
            </div>
            <a href="<?= baseUrl('login.php') ?>" class="btn btn-primary w-100">Back to Login</a>
        <?php else: ?>
            <form method="POST">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">Send Reset Link</button>
                <p class="text-center mb-0"><a href="<?= baseUrl('login.php') ?>">Back to Login</a></p>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
