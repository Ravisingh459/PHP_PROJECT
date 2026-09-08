<?php
/**
 * FreelanceHub - Client Post New Project
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$pageTitle = 'Post New Project';
$sidebarRole = 'client';
$userId = currentUserId();
$errors = [];

$skills = db()->query('SELECT * FROM skills ORDER BY name')->fetchAll();
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
        $selectedSkills = $_POST['skills'] ?? [];

        if (empty($title)) $errors[] = 'Title is required.';
        if (empty($description)) $errors[] = 'Description is required.';
        if ($budget < getMinProjectBudget()) {
            $errors[] = 'Minimum budget is ' . formatMoney(getMinProjectBudget()) . '.';
        }
        if (empty($deadline) || strtotime($deadline) < time()) $errors[] = 'Valid future deadline is required.';

        if (empty($errors)) {
            try {
                db()->beginTransaction();

                $attachment = null;
                if (!empty($_FILES['attachment']['name'])) {
                    $attachment = uploadFile($_FILES['attachment'], 'projects', array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES));
                }

                $stmt = db()->prepare('INSERT INTO projects (client_id, title, description, category, budget, deadline, attachment) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $title, $description, $category, $budget, $deadline, $attachment]);
                $projectId = (int) db()->lastInsertId();

                if (!empty($selectedSkills)) {
                    $skillStmt = db()->prepare('INSERT INTO project_skills (project_id, skill_id) VALUES (?, ?)');
                    foreach ($selectedSkills as $skillId) {
                        $skillStmt->execute([$projectId, (int)$skillId]);
                    }
                }

                db()->prepare('UPDATE client_profiles SET projects_posted = projects_posted + 1 WHERE user_id = ?')->execute([$userId]);

                db()->commit();
                setFlash('success', 'Project posted successfully!');
                redirect(baseUrl('client/projects.php'));
            } catch (Exception $e) {
                db()->rollBack();
                $errors[] = 'Failed to post project. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="fw-bold mb-4">Post a New Project</h4>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3 p-3 rounded bg-gradient text-white" style="background: linear-gradient(135deg, #4F7CFF 0%, #8B5CF6 100%);">
            <div>
                <h6 class="fw-bold mb-0"><i class="fas fa-magic me-2"></i>NexaAI Scope Generator</h6>
                <small class="text-white-50">Need help writing requirements? Enter a title & category, then click Auto-Generate Scope!</small>
            </div>
            <button type="button" id="btn-ai-scope" class="btn btn-light btn-sm fw-bold"><i class="fas fa-wand-magic-sparkles me-1 text-primary"></i> Auto-Generate Scope</button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Project Title *</label>
                <input type="text" id="project-title" name="title" class="form-control" placeholder="e.g. Build an E-Commerce React Web App" required value="<?= e($_POST['title'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Description *</label>
                <textarea id="project-description" name="description" class="form-control" rows="8" required><?= e($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Category *</label>
                    <select id="project-category" name="category" class="form-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Budget (₹) *</label>
                    <input type="number" id="project-budget" name="budget" class="form-control" min="<?= (int) getMinProjectBudget() ?>" step="1" required value="<?= e($_POST['budget'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Deadline *</label>
                    <input type="date" id="project-deadline" name="deadline" class="form-control" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Skills Required</label>
                <select name="skills[]" class="form-select" multiple size="5">
                    <?php foreach ($skills as $skill): ?>
                        <option value="<?= $skill['id'] ?>"><?= e($skill['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Attachment (optional)</label>
                <input type="file" name="attachment" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane me-2"></i>Post Project</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnAi = document.getElementById('btn-ai-scope');
    if (btnAi) {
        btnAi.addEventListener('click', function() {
            const title = document.getElementById('project-title').value.trim();
            const category = document.getElementById('project-category').value;
            if (!title) {
                alert('Please enter a project title first.');
                document.getElementById('project-title').focus();
                return;
            }

            btnAi.disabled = true;
            btnAi.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating...';

            const formData = new FormData();
            formData.append('action', 'generate_scope');
            formData.append('title', title);
            formData.append('category', category);

            fetch('<?= baseUrl('api/ai_assistant.php') ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnAi.disabled = false;
                btnAi.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1 text-primary"></i> Auto-Generate Scope';
                if (data.success) {
                    document.getElementById('project-description').value = data.description;
                    if (data.suggested_budget && !document.getElementById('project-budget').value) {
                        document.getElementById('project-budget').value = data.suggested_budget;
                    }
                }
            })
            .catch(err => {
                btnAi.disabled = false;
                btnAi.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1 text-primary"></i> Auto-Generate Scope';
                alert('Error connecting to AI service.');
            });
        });
    }
});
</script>


<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
