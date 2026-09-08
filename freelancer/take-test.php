<?php
/**
 * NexaWork - Take Skill Assessment Test (Arjun Singh Panel)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$testId = (int)($_GET['id'] ?? 0);
$userId = currentUserId();
$profile = getFreelancerProfile($userId);

$stmt = db()->prepare('SELECT st.*, s.name as skill_name FROM skill_tests st JOIN skills s ON st.skill_id = s.id WHERE st.id = ? AND st.is_active = 1');
$stmt->execute([$testId]);
$test = $stmt->fetch();

if (!$test) {
    setFlash('danger', 'Skill test not found.');
    redirect(baseUrl('freelancer/skill-tests.php'));
}

$questions = json_decode($test['questions'], true) ?: [];
$pageTitle = 'Take Test - ' . $test['title'];
$sidebarRole = 'freelancer';

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $answers = $_POST['answers'] ?? [];
    $correct = 0;
    foreach ($questions as $i => $q) {
        if (isset($answers[$i]) && (int)$answers[$i] === (int)$q['answer']) {
            $correct++;
        }
    }
    $score = count($questions) > 0 ? round(($correct / count($questions)) * 100) : 0;
    $passed = $score >= $test['passing_score'];

    db()->prepare('INSERT INTO freelancer_skill_tests (freelancer_id, skill_test_id, score, passed) VALUES (?, ?, ?, ?)')
        ->execute([$profile['id'], $testId, $score, $passed ? 1 : 0]);

    if ($passed) {
        setFlash('success', "🎉 Congratulations! You passed the '{$test['title']}' with a score of {$score}%! Verified badge added to your profile.");
    } else {
        setFlash('warning', "You scored {$score}%. Passing threshold is {$test['passing_score']}%. You can review the material and retake the assessment.");
    }
    redirect(baseUrl('freelancer/skill-tests.php'));
}

$extraJs = '<script>
document.addEventListener("DOMContentLoaded", function() {
    let minutes = ' . (int)$test['duration_minutes'] . ';
    let seconds = 0;
    const timerElem = document.getElementById("testTimer");
    
    const timerInterval = setInterval(function() {
        if (seconds === 0) {
            if (minutes === 0) {
                clearInterval(timerInterval);
                alert("Time is up! Submitting your assessment test automatically.");
                document.getElementById("testForm").submit();
                return;
            }
            minutes--;
            seconds = 59;
        } else {
            seconds--;
        }
        timerElem.textContent = String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");
    }, 1000);
});
</script>';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-edit text-primary me-2"></i><?= e($test['title']) ?></h4>
        <p class="text-muted mb-0">Skill Category: <strong><?= e($test['skill_name']) ?></strong> &bull; Passing Score: <strong><?= (int)$test['passing_score'] ?>%</strong></p>
    </div>
    <div class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 fs-6 rounded-pill">
        <i class="fas fa-clock me-2"></i>Time Remaining: <span id="testTimer" class="fw-bold"><?= sprintf('%02d:00', $test['duration_minutes']) ?></span>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-3 fw-bold">
        <i class="fas fa-list-ol me-2 text-primary"></i>Assessment Questions (<?= count($questions) ?> Total)
    </div>
    <div class="card-body p-4">
        <form method="POST" id="testForm">
            <?= csrfField() ?>
            
            <?php foreach ($questions as $i => $q): ?>
            <div class="p-3 mb-4 bg-light rounded border dark-mode-card">
                <h6 class="fw-bold mb-3 text-dark dark-mode-text">
                    <span class="badge bg-primary me-2">Q<?= $i + 1 ?></span> <?= e($q['q']) ?>
                </h6>
                <div class="d-flex flex-column gap-2 ms-2">
                    <?php foreach ($q['options'] as $j => $option): ?>
                    <div class="form-check p-2 px-3 rounded border bg-white dark-mode-card">
                        <input class="form-check-input ms-0 me-2" type="radio" name="answers[<?= $i ?>]" value="<?= $j ?>" id="q<?= $i ?>o<?= $j ?>" required>
                        <label class="form-check-label fw-semibold text-secondary dark-mode-text" for="q<?= $i ?>o<?= $j ?>">
                            <?= e($option) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="d-flex justify-content-between align-items-center border-top pt-4">
                <a href="<?= baseUrl('freelancer/skill-tests.php') ?>" class="btn btn-outline-secondary" onclick="return confirm('Cancel test? Progress will be lost.')">
                    <i class="fas fa-times me-1"></i>Cancel Assessment
                </a>
                <button type="submit" class="btn btn-success btn-lg px-4 fw-bold">
                    <i class="fas fa-check-circle me-2"></i>Submit Test Answers
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
