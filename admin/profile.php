<?php
/**
 * NexaWork - Admin Profile Management Module
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Admin Profile';
$sidebarRole = 'admin';
$userId = currentUserId();
$user = currentUser();

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        // Validate email uniqueness if changed
        if ($email !== $user['email']) {
            $checkEmail = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $checkEmail->execute([$email, $userId]);
            if ($checkEmail->fetchColumn()) {
                setFlash('danger', 'Email address is already in use by another account.');
                redirect(baseUrl('admin/profile.php'));
            }
        }

        $avatar = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $newAvatar = uploadFile($_FILES['avatar'], 'profiles', ALLOWED_IMAGE_TYPES);
            if ($newAvatar) {
                $avatar = $newAvatar;
            }
        }

        db()->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, avatar = ? WHERE id = ?')
            ->execute([$firstName, $lastName, $email, $phone, $avatar, $userId]);

        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        setFlash('success', 'Admin profile information updated successfully.');
        redirect(baseUrl('admin/profile.php'));

    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $user['password'])) {
            setFlash('danger', 'Current password is incorrect.');
        } elseif (strlen($newPassword) < 6) {
            setFlash('warning', 'New password must be at least 6 characters long.');
        } elseif ($newPassword !== $confirmPassword) {
            setFlash('warning', 'New password and confirmation do not match.');
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $userId]);
            setFlash('success', 'Admin account password changed successfully.');
        }
        redirect(baseUrl('admin/profile.php'));
    }
}

$user = getUserById($userId);

// Fetch admin statistics
$totalUsersManaged = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalProjectsManaged = (int)db()->query('SELECT COUNT(*) FROM projects')->fetchColumn();
$totalReportsResolved = (int)db()->query('SELECT COUNT(*) FROM reports WHERE status = "resolved"')->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-user-shield me-2 text-primary"></i>Admin Profile & Credentials</h4>
        <p class="text-muted mb-0">Manage your system administrator account details, security credentials, and preferences.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Admin Info Card -->
    <div class="col-lg-4">
        <div class="card text-center p-4">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <img src="<?= getAvatarUrl($user['avatar']) ?>" id="avatarPreview" class="rounded-circle shadow-sm" width="120" height="120" style="object-fit:cover; border: 4px solid var(--primary-light);">
                <span class="position-absolute bottom-0 end-0 bg-success border border-light rounded-circle p-2" title="Active Admin"></span>
            </div>
            
            <h5 class="fw-bold mb-1"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h5>
            <p class="text-muted small mb-2"><i class="fas fa-envelope me-1"></i><?= e($user['email']) ?></p>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mx-auto mb-3">
                <i class="fas fa-shield-alt me-1"></i>System Administrator
            </span>

            <hr>

            <div class="text-start fs-7">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><i class="fas fa-calendar-alt me-2"></i>Member Since</span>
                    <span class="fw-bold"><?= formatDate($user['created_at']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><i class="fas fa-phone me-2"></i>Phone</span>
                    <span class="fw-bold"><?= e($user['phone'] ?? 'Not set') ?></span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted"><i class="fas fa-check-circle me-2 text-success"></i>Account Status</span>
                    <span class="badge bg-success">Active</span>
                </div>
            </div>
        </div>

        <!-- Quick System Stats -->
        <div class="card mt-4 p-3">
            <h6 class="fw-bold mb-3"><i class="fas fa-chart-pie me-2 text-primary"></i>Admin Operations Overview</h6>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">Total Platform Users</span>
                <span class="badge bg-secondary rounded-pill"><?= number_format($totalUsersManaged) ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">Total Projects</span>
                <span class="badge bg-secondary rounded-pill"><?= number_format($totalProjectsManaged) ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">Resolved User Reports</span>
                <span class="badge bg-success rounded-pill"><?= number_format($totalReportsResolved) ?></span>
            </div>
        </div>
    </div>

    <!-- Right Column: Profile Edit & Password Forms -->
    <div class="col-lg-8">
        <!-- Update Profile Form -->
        <div class="card mb-4">
            <div class="card-header bg-transparent fw-bold py-3">
                <i class="fas fa-user-edit me-2 text-primary"></i>Edit Profile Details
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Profile Avatar</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="file" name="avatar" data-preview="avatarPreview" accept="image/*" class="form-control">
                        </div>
                        <div class="form-text">Allowed formats: JPG, PNG, WEBP. Max size: 5MB.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">First Name *</label>
                            <input type="text" name="first_name" class="form-control" value="<?= e($user['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" value="<?= e($user['last_name']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address *</label>
                            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="+91 98765 43210">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Form -->
        <div class="card">
            <div class="card-header bg-transparent fw-bold py-3">
                <i class="fas fa-key me-2 text-warning"></i>Change Admin Password
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password *</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="Enter current admin password">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New Password *</label>
                            <input type="password" name="new_password" class="form-control" required placeholder="Minimum 6 characters">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm New Password *</label>
                            <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter new password">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-warning px-4 fw-bold">
                            <i class="fas fa-shield-alt me-2"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
