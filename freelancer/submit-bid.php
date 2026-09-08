<?php
/**
 * FreelanceHub - Freelancer Submit Bid
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$userId = currentUserId();
$projectId = (int)($_GET['project_id'] ?? 0);
$project = getProjectById($projectId);

if (!$project || $project['status'] !== 'open') {
    setFlash('danger', 'Project not available for bidding.');
    redirect(baseUrl('freelancer/projects.php'));
}

// Check existing bid
$stmt = db()->prepare('SELECT id FROM bids WHERE project_id = ? AND freelancer_id = ?');
$stmt->execute([$projectId, $userId]);
if ($stmt->fetch()) {
    setFlash('info', 'You have already submitted a bid for this project.');
    redirect(baseUrl('freelancer/bids.php'));
}

$pageTitle = 'Submit Bid';
$sidebarRole = 'freelancer';
$errors = [];

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $budget = (float)($_POST['proposed_budget'] ?? 0);
    $days = (int)($_POST['delivery_days'] ?? 0);
    $coverLetter = sanitize($_POST['cover_letter'] ?? '');

    if ($budget <= 0) $errors[] = 'Please enter a valid budget.';
    if ($days <= 0) $errors[] = 'Please enter valid delivery days.';
    if (empty($coverLetter)) $errors[] = 'Cover letter is required.';

    if (empty($errors)) {
        $stmt = db()->prepare('INSERT INTO bids (project_id, freelancer_id, proposed_budget, delivery_days, cover_letter) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$projectId, $userId, $budget, $days, $coverLetter]);

        createNotification($project['client_id'], 'new_bid', 'New Bid Received',
            currentUser()['first_name'] . ' submitted a bid on: ' . $project['title'],
            baseUrl('client/view-bids.php?id=' . $projectId));

        setFlash('success', 'Bid submitted successfully!');
        redirect(baseUrl('freelancer/bids.php'));
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="fw-bold mb-4">Submit Bid</h4>
<p class="text-muted">Project: <strong><?= e($project['title']) ?></strong> | Budget: <?= formatMoney((float)$project['budget']) ?></p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Your Proposed Budget (₹)</label>
                    <input type="number" id="bid-budget" name="proposed_budget" class="form-control" required step="0.01" value="<?= e($_POST['proposed_budget'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Delivery Time (days)</label>
                    <input type="number" name="delivery_days" class="form-control" required min="1" value="<?= e($_POST['delivery_days'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Cover Letter *</label>
                    <button type="button" id="btn-ai-review" class="btn btn-sm btn-outline-primary"><i class="fas fa-robot me-1"></i> NexaAI Review Proposal</button>
                </div>
                <textarea id="bid-cover-letter" name="cover_letter" class="form-control" rows="8" required placeholder="Explain why you're the best fit for this project..."><?= e($_POST['cover_letter'] ?? '') ?></textarea>
            </div>

            <div id="ai-review-box" class="alert alert-info d-none mb-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0"><i class="fas fa-brain me-2"></i>NexaAI Proposal Score: <span id="ai-score-val" class="badge bg-primary fs-6">85/100</span></h6>
                    <small id="ai-rating-label" class="fw-bold text-dark"></small>
                </div>
                <ul id="ai-tips-list" class="mt-2 mb-0 small ps-3"></ul>
            </div>

            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane me-2"></i>Submit Bid</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnReview = document.getElementById('btn-ai-review');
    const reviewBox = document.getElementById('ai-review-box');
    const scoreVal = document.getElementById('ai-score-val');
    const ratingLabel = document.getElementById('ai-rating-label');
    const tipsList = document.getElementById('ai-tips-list');

    if (btnReview) {
        btnReview.addEventListener('click', function() {
            const coverLetter = document.getElementById('bid-cover-letter').value.trim();
            const proposedBudget = document.getElementById('bid-budget').value || 0;
            const projectBudget = <?= (float)$project['budget'] ?>;

            if (!coverLetter) {
                alert('Please write your cover letter before analyzing.');
                document.getElementById('bid-cover-letter').focus();
                return;
            }

            btnReview.disabled = true;
            btnReview.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Analyzing...';

            const formData = new FormData();
            formData.append('action', 'analyze_proposal');
            formData.append('cover_letter', coverLetter);
            formData.append('proposed_budget', proposedBudget);
            formData.append('project_budget', projectBudget);

            fetch('<?= baseUrl('api/ai_assistant.php') ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnReview.disabled = false;
                btnReview.innerHTML = '<i class="fas fa-robot me-1"></i> NexaAI Review Proposal';
                if (data.score) {
                    scoreVal.textContent = data.score + '/100';
                    ratingLabel.textContent = data.rating_label;
                    tipsList.innerHTML = '';
                    if (data.tips && data.tips.length > 0) {
                        data.tips.forEach(t => {
                            tipsList.innerHTML += '<li>' + t + '</li>';
                        });
                    } else {
                        tipsList.innerHTML = '<li class="text-success">Great job! Your proposal is well structured and ready to submit.</li>';
                    }
                    reviewBox.classList.remove('d-none');
                }
            })
            .catch(err => {
                btnReview.disabled = false;
                btnReview.innerHTML = '<i class="fas fa-robot me-1"></i> NexaAI Review Proposal';
                alert('Error analyzing proposal.');
            });
        });
    }
});
</script>


<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
