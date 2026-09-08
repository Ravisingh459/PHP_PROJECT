<?php
/**
 * FreelanceHub - Client Profile
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$pageTitle = 'My Profile';
$sidebarRole = 'client';
$userId = currentUserId();
$user = currentUser();
$profile = getClientProfile($userId);

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $companyName = sanitize($_POST['company_name'] ?? '');
    $companyWebsite = sanitize($_POST['company_website'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    $location = sanitize($_POST['location'] ?? '');

    $avatar = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $newAvatar = uploadFile($_FILES['avatar'], 'profiles', ALLOWED_IMAGE_TYPES);
        if ($newAvatar) $avatar = $newAvatar;
    }

    db()->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, avatar=? WHERE id=?')
        ->execute([$firstName, $lastName, $phone, $avatar, $userId]);
    db()->prepare('UPDATE client_profiles SET company_name=?, company_website=?, bio=?, location=? WHERE user_id=?')
        ->execute([$companyName, $companyWebsite, $bio, $location, $userId]);

    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    setFlash('success', 'Profile updated successfully.');
    redirect(baseUrl('client/profile.php'));
}

$user = getUserById($userId);
$profile = getClientProfile($userId);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<h4 class="fw-bold mb-4">My Profile</h4>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="text-center mb-4">
                <img src="<?= getAvatarUrl($user['avatar']) ?>" id="avatarPreview" class="rounded-circle mb-2" width="100" height="100" style="object-fit:cover;">
                <div><input type="file" name="avatar" data-preview="avatarPreview" accept="image/*" class="form-control form-control-sm w-auto mx-auto"></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?= e($user['first_name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?= e($user['last_name']) ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="<?= e($profile['company_name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Website</label>
                    <input type="url" name="company_website" class="form-control" value="<?= e($profile['company_website'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" value="<?= e($profile['location'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Bio</label>
                <textarea name="bio" class="form-control" rows="4"><?= e($profile['bio'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
