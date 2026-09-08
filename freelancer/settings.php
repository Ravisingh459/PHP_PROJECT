<?php
/**
 * FreelanceHub - Freelancer Settings Module
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$pageTitle = 'Freelancer Settings';
$sidebarRole = 'freelancer';
$userId = currentUserId();
$user = getUserById($userId);
$profile = getFreelancerProfile($userId);

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['form_action'] ?? 'account';

    if ($action === 'account') {
        $phone = sanitize($_POST['phone'] ?? '');
        $lang = in_array($_POST['language'] ?? '', ['en', 'hi', 'es', 'fr']) ? $_POST['language'] : 'en';
        $darkMode = isset($_POST['dark_mode']) ? 1 : 0;

        db()->prepare('UPDATE users SET phone=?, language=?, dark_mode=? WHERE id=?')
            ->execute([$phone, $lang, $darkMode, $userId]);
        
        $_SESSION['language'] = $lang;
        $_SESSION['dark_mode'] = (bool)$darkMode;

        setFlash('success', 'Account settings updated successfully.');
        redirect(baseUrl('freelancer/settings.php'));

    } elseif ($action === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $user['password'])) {
            setFlash('danger', 'Current password is incorrect.');
        } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            setFlash('danger', 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.');
        } elseif ($newPassword !== $confirmPassword) {
            setFlash('danger', 'New password and confirmation do not match.');
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hashedPassword, $userId]);
            setFlash('success', 'Password updated successfully!');
        }
        redirect(baseUrl('freelancer/settings.php'));

    } elseif ($action === 'preferences') {
        $title = sanitize($_POST['title'] ?? '');
        $hourlyRate = (float)($_POST['hourly_rate'] ?? 0);
        $availability = in_array($_POST['availability'] ?? '', ['available', 'busy', 'unavailable']) ? $_POST['availability'] : 'available';
        $location = sanitize($_POST['location'] ?? '');

        db()->prepare('UPDATE freelancer_profiles SET title=?, hourly_rate=?, availability=?, location=? WHERE user_id=?')
            ->execute([$title, $hourlyRate, $availability, $location, $userId]);

        setFlash('success', 'Work preferences saved successfully.');
        redirect(baseUrl('freelancer/settings.php'));
    }
}

$user = getUserById($userId);
$profile = getFreelancerProfile($userId);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-cog me-2 text-primary"></i>Freelancer Account & Settings</h4>
        <p class="text-muted mb-0">Manage your account credentials, work availability, preferences, and security.</p>
    </div>
    <a href="<?= baseUrl('freelancer/profile.php') ?>" class="btn btn-outline-primary">
        <i class="fas fa-user-edit me-2"></i>Edit Full Profile
    </a>
</div>

<div class="row g-4">
    <!-- Quick Profile Card -->
    <div class="col-lg-4">
        <div class="card shadow-sm text-center p-4 h-100">
            <img src="<?= getAvatarUrl($user['avatar']) ?>" class="rounded-circle mx-auto mb-3" width="96" height="96" style="object-fit:cover;">
            <h5 class="fw-bold mb-1"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h5>
            <p class="text-muted mb-2"><?= e($profile['title'] ?? 'Freelancer') ?></p>
            <span class="badge bg-primary-subtle text-primary text-capitalize w-auto mx-auto mb-3 px-3 py-2">
                <i class="fas fa-circle fs-8 me-1 <?= ($profile['availability'] ?? '') === 'available' ? 'text-success' : (($profile['availability'] ?? '') === 'busy' ? 'text-warning' : 'text-danger') ?>"></i>
                <?= e(ucfirst($profile['availability'] ?? 'available')) ?>
            </span>
            
            <hr>
            
            <div class="text-start fs-7">
                <div class="mb-2"><i class="fas fa-envelope text-muted me-2"></i><?= e($user['email']) ?></div>
                <div class="mb-2"><i class="fas fa-phone text-muted me-2"></i><?= e($user['phone'] ?: 'Not specified') ?></div>
                <div class="mb-2"><i class="fas fa-map-marker-alt text-muted me-2"></i><?= e($profile['location'] ?: 'Not specified') ?></div>
                <div class="mb-2"><i class="fas fa-tag text-muted me-2"></i>₹<?= number_format((float)($profile['hourly_rate'] ?? 0), 2) ?>/hr</div>
            </div>
        </div>
    </div>

    <!-- Settings Forms -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <ul class="nav nav-tabs card-header-tabs m-0" id="settingsTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active py-3" id="account-tab" data-bs-toggle="tab" data-bs-target="#tab-account" type="button" role="tab">
                            <i class="fas fa-user-cog me-2 text-primary"></i>Account
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3" id="work-tab" data-bs-toggle="tab" data-bs-target="#tab-work" type="button" role="tab">
                            <i class="fas fa-briefcase me-2 text-success"></i>Work Preferences
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3" id="security-tab" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">
                            <i class="fas fa-lock me-2 text-danger"></i>Security
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="settingsTabContent">
                    
                    <!-- Account & General Settings -->
                    <div class="tab-pane fade show active" id="tab-account" role="tabpanel">
                        <h5 class="fw-bold mb-3">General Information</h5>
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="form_action" value="account">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                                <small class="text-muted">Registered email address cannot be changed directly.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="+91 98765 43210">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Preferred Language</label>
                                    <select name="language" class="form-select">
                                        <option value="en" <?= ($user['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                                        <option value="hi" <?= ($user['language'] ?? '') === 'hi' ? 'selected' : '' ?>>हिन्दी (Hindi)</option>
                                        <option value="es" <?= ($user['language'] ?? '') === 'es' ? 'selected' : '' ?>>Español</option>
                                        <option value="fr" <?= ($user['language'] ?? '') === 'fr' ? 'selected' : '' ?>>Français</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Theme Mode</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="dark_mode" value="1" id="dark_mode_switch" <?= $user['dark_mode'] ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="dark_mode_switch">Enable Dark Mode</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary fw-semibold mt-2">
                                <i class="fas fa-save me-2"></i>Save Account Settings
                            </button>
                        </form>
                    </div>

                    <!-- Work Preferences -->
                    <div class="tab-pane fade" id="tab-work" role="tabpanel">
                        <h5 class="fw-bold mb-3">Availability & Rate Settings</h5>
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="form_action" value="preferences">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Professional Title</label>
                                <input type="text" name="title" class="form-control" value="<?= e($profile['title'] ?? '') ?>" placeholder="e.g. Senior Full Stack Developer">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Hourly Rate (₹)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" min="0" name="hourly_rate" class="form-control" value="<?= e((string)($profile['hourly_rate'] ?? 0)) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Current Availability</label>
                                    <select name="availability" class="form-select">
                                        <option value="available" <?= ($profile['availability'] ?? '') === 'available' ? 'selected' : '' ?>>Available for Work</option>
                                        <option value="busy" <?= ($profile['availability'] ?? '') === 'busy' ? 'selected' : '' ?>>Busy / Limited</option>
                                        <option value="unavailable" <?= ($profile['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Location</label>
                                <input type="text" name="location" class="form-control" value="<?= e($profile['location'] ?? '') ?>" placeholder="City, Country">
                            </div>
                            <button type="submit" class="btn btn-success fw-semibold mt-2">
                                <i class="fas fa-save me-2"></i>Save Work Preferences
                            </button>
                        </form>
                    </div>

                    <!-- Security & Password -->
                    <div class="tab-pane fade" id="tab-security" role="tabpanel">
                        <h5 class="fw-bold mb-3">Change Password</h5>
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="form_action" value="password">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Current Password *</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">New Password *</label>
                                <input type="password" name="new_password" class="form-control" required minlength="<?= PASSWORD_MIN_LENGTH ?>">
                                <small class="text-muted">Minimum <?= PASSWORD_MIN_LENGTH ?> characters.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Confirm New Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-danger fw-semibold mt-2">
                                <i class="fas fa-key me-2"></i>Update Password
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
