<?php
/**
 * FreelanceHub - Client Edit Project
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$userId = currentUserId();
$projectId = (int)($_GET['id'] ?? 0);
$project = getProjectById($projectId);

if (!$project || (int)$project['client_id'] !== $userId) {
    setFlash('danger', 'Project not found.');
    redirect(baseUrl('client/projects.php'));
}

$pageTitle = 'Edit Project';
$sidebarRole = 'client';
$errors = [];
$skills = db()->query('SELECT * FROM skills ORDER BY name')->fetchAll();
$projectSkillIds = array_column(getProjectSkills($projectId), 'id');
$categories = ['Web Development', 'Mobile Development', 'Design', 'Writing', 'Marketing', 'Data Science', 'Other'];

if (isPost()) {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $budget = (float)($_POST['budget'] ?? 0);
        $deadline = $_POST['deadline'] ?? '';
        $status = $_POST['status'] ?? $project['status'];
        $selectedSkills = $_POST['skills'] ?? [];

        if (empty($title)) $errors[] = 'Title is required.';
        if (empty($description)) $errors[] = 'Description is required.';
        if ($budget < getMinProjectBudget()) {
            $errors[] = 'Minimum budget is ' . formatMoney(getMinProjectBudget()) . '.';
        }

        if (empty($errors)) {
            $attachment = $project['attachment'];
            if (!empty($_FILES['attachment']['name'])) {
                $newFile = uploadFile($_FILES['attachment'], 'projects', array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES));
                if ($newFile) $attachment = $newFile;
            }

            $stmt = db()->prepare('UPDATE projects SET title=?, description=?, category=?, budget=?, deadline=?, status=?, attachment=? WHERE id=? AND client_id=?');
            $stmt->execute([$title, $description, $category, $budget, $deadline, $status, $attachment, $projectId, $userId]);

            db()->prepare('DELETE FROM project_skills WHERE project_id = ?')->execute([$projectId]);
            if (!empty($selectedSkills)) {
                $skillStmt = db()->prepare('INSERT INTO project_skills (project_id, skill_id) VALUES (?, ?)');
                foreach ($selectedSkills as $skillId) {
                    $skillStmt->execute([$projectId, (int)$skillId]);
                }
            }

            setFlash('success', 'Project updated successfully.');
            redirect(baseUrl('client/projects.php'));
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="fw-bold mb-4">Edit Project</h4>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required value="<?= e($project['title']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="6" required><?= e($project['description']) ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $project['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Budget (₹)</label>
                    <input type="number" name="budget" class="form-control" min="<?= (int) getMinProjectBudget() ?>" step="1" required value="<?= $project['budget'] ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Deadline</label>
                    <input type="date" name="deadline" class="form-control" required value="<?= $project['deadline'] ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['open','in_progress','completed','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $project['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Skills</label>
                <select name="skills[]" class="form-select" multiple size="5">
                    <?php foreach ($skills as $skill): ?>
                        <option value="<?= $skill['id'] ?>" <?= in_array($skill['id'], $projectSkillIds) ? 'selected' : '' ?>><?= e($skill['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Attachment</label>
                <input type="file" name="attachment" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Update Project</button>
            <a href="<?= baseUrl('client/projects.php') ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
