<?php
/**
 * FreelanceHub - Freelancer Profile Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$pageTitle = 'My Profile';
$sidebarRole = 'freelancer';
$userId = currentUserId();
$user = getUserById($userId);
$profile = getFreelancerProfile($userId);
$allSkills = db()->query('SELECT * FROM skills ORDER BY name')->fetchAll();

// Get freelancer's skills
$stmt = db()->prepare('SELECT s.*, fs.proficiency FROM skills s JOIN freelancer_skills fs ON s.id = fs.skill_id WHERE fs.freelancer_id = ?');
$stmt->execute([$profile['id']]);
$mySkills = $stmt->fetchAll();
$mySkillIds = array_column($mySkills, 'id');

// Get achievements
$stmt = db()->prepare('SELECT a.* FROM achievements a JOIN freelancer_achievements fa ON a.id = fa.achievement_id WHERE fa.freelancer_id = ?');
$stmt->execute([$profile['id']]);
$achievements = $stmt->fetchAll();

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $title = sanitize($_POST['title'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    $hourlyRate = (float)($_POST['hourly_rate'] ?? 0);
    $experience = (int)($_POST['experience_years'] ?? 0);
    $location = sanitize($_POST['location'] ?? '');
    $availability = $_POST['availability'] ?? 'available';
    $selectedSkills = $_POST['skills'] ?? [];

    $avatar = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $newAvatar = uploadFile($_FILES['avatar'], 'profiles', ALLOWED_IMAGE_TYPES);
        if ($newAvatar) $avatar = $newAvatar;
    }

    // Resume upload
    $resume = $profile['resume'];
    if (!empty($_FILES['resume']['name'])) {
        $newResume = uploadFile($_FILES['resume'], 'resumes', ALLOWED_DOC_TYPES);
        if ($newResume) $resume = $newResume;
    }

    db()->prepare('UPDATE users SET first_name=?, last_name=?, avatar=? WHERE id=?')
        ->execute([$firstName, $lastName, $avatar, $userId]);
    db()->prepare('UPDATE freelancer_profiles SET title=?, bio=?, hourly_rate=?, experience_years=?, location=?, availability=?, resume=? WHERE user_id=?')
        ->execute([$title, $bio, $hourlyRate, $experience, $location, $availability, $resume, $userId]);

    // Update skills
    db()->prepare('DELETE FROM freelancer_skills WHERE freelancer_id = ?')->execute([$profile['id']]);
    if (!empty($selectedSkills)) {
        $skillStmt = db()->prepare('INSERT INTO freelancer_skills (freelancer_id, skill_id, proficiency) VALUES (?, ?, "intermediate")');
        foreach ($selectedSkills as $skillId) {
            $skillStmt->execute([$profile['id'], (int)$skillId]);
        }
    }

    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    setFlash('success', 'Profile updated successfully!');
    redirect(baseUrl('freelancer/profile.php'));
}

$user = getUserById($userId);
$profile = getFreelancerProfile($userId);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<h4 class="fw-bold mb-4">Professional Profile</h4>

<?php if (!empty($achievements)): ?>
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-trophy me-2"></i>Achievement Badges</div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($achievements as $ach): ?>
            <div class="col-md-2 col-4">
                <div class="achievement-badge">
                    <div class="badge-icon"><i class="fas fa-<?= e($ach['icon']) ?>"></i></div>
                    <small class="fw-bold"><?= e($ach['name']) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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
            <div class="mb-3">
                <label class="form-label">Professional Title</label>
                <input type="text" name="title" class="form-control" value="<?= e($profile['title'] ?? '') ?>" placeholder="e.g. Full Stack Developer">
            </div>
            <div class="mb-3">
                <label class="form-label">Bio</label>
                <textarea name="bio" class="form-control" rows="4"><?= e($profile['bio'] ?? '') ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Hourly Rate (₹)</label>
                    <input type="number" name="hourly_rate" class="form-control" value="<?= $profile['hourly_rate'] ?? 0 ?>" step="0.01">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Experience (years)</label>
                    <input type="number" name="experience_years" class="form-control" value="<?= $profile['experience_years'] ?? 0 ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Availability</label>
                    <select name="availability" class="form-select">
                        <?php foreach (['available','busy','unavailable'] as $a): ?>
                            <option value="<?= $a ?>" <?= ($profile['availability'] ?? '') === $a ? 'selected' : '' ?>><?= ucfirst($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" value="<?= e($profile['location'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Skills</label>
                <select name="skills[]" class="form-select" multiple size="6">
                    <?php foreach ($allSkills as $skill): ?>
                        <option value="<?= $skill['id'] ?>" <?= in_array($skill['id'], $mySkillIds) ? 'selected' : '' ?>><?= e($skill['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Resume (PDF/DOC)</label>
                <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
                <?php if ($profile['resume'] ?? ''): ?>
                    <small class="text-success"><i class="fas fa-check me-1"></i>Resume uploaded</small>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Save Profile</button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
