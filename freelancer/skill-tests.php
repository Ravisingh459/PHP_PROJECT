<?php
/**
 * NexaWork - Freelancer Skill Assessment Tests (Arjun Singh Panel)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('freelancer');

$pageTitle = 'Skill Assessments & Badges';
$sidebarRole = 'freelancer';
$userId = currentUserId();
$profile = getFreelancerProfile($userId);

$tests = db()->query('SELECT st.*, s.name as skill_name, s.category as skill_category 
    FROM skill_tests st 
    JOIN skills s ON st.skill_id = s.id 
    WHERE st.is_active = 1 
    ORDER BY st.id ASC')->fetchAll();

$stmt = db()->prepare('SELECT fst.*, st.title 
    FROM freelancer_skill_tests fst 
    JOIN skill_tests st ON fst.skill_test_id = st.id 
    WHERE fst.freelancer_id = ?');
$stmt->execute([$profile['id']]);
$completedTests = $stmt->fetchAll();

$completedMap = [];
$passedCount = 0;
foreach ($completedTests as $ct) {
    $completedMap[$ct['skill_test_id']] = $ct;
    if ($ct['passed']) $passedCount++;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-graduation-cap text-primary me-2"></i>Skill Tests & Verified Badges</h4>
        <p class="text-muted mb-0">Pass timed skill assessments to earn verified skill badges and rank higher on client searches.</p>
    </div>
</div>

<!-- Stats Summary Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-primary border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Available Skill Tests</div>
                    <div class="fw-bold fs-4"><?= count($tests) ?> Assessments</div>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                    <i class="fas fa-list-check fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-success border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Verified Badges Earned</div>
                    <div class="fw-bold fs-4 text-success"><?= $passedCount ?> Passed</div>
                </div>
                <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                    <i class="fas fa-certificate fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-info border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">AI Match Boost</div>
                    <div class="fw-bold fs-4 text-info">+25% Boost</div>
                </div>
                <div class="bg-info bg-opacity-10 text-info rounded-circle p-3">
                    <i class="fas fa-rocket fa-lg"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Verified Skill Badges Banner -->
<div class="card bg-gradient-primary text-white p-4 mb-4 shadow-sm border-0" style="background: linear-gradient(135deg, #4F7CFF, #8B5CF6) !important;">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white bg-opacity-20 rounded-circle p-3">
            <i class="fas fa-shield-alt fa-2x text-warning"></i>
        </div>
        <div>
            <h5 class="fw-bold mb-1">Why Take Skill Tests?</h5>
            <p class="mb-0 text-white-50">Verified skill badges are displayed directly on your public profile and client proposals. Freelancers with 2+ verified badges receive 4x more direct project invitations from top Indian clients.</p>
        </div>
    </div>
</div>

<!-- Skill Assessment Grid -->
<div class="row g-4">
    <?php foreach ($tests as $test): 
        $questions = json_decode($test['questions'], true) ?: [];
        $questionCount = count($questions);
        $isCompleted = isset($completedMap[$test['id']]);
        $testResult = $completedMap[$test['id']] ?? null;
    ?>
    <div class="col-md-6">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fw-semibold">
                        <i class="fas fa-code me-1"></i><?= e($test['skill_name']) ?>
                    </span>
                    <span class="text-muted fs-7"><i class="fas fa-clock me-1 text-warning"></i><?= (int)$test['duration_minutes'] ?> Mins</span>
                </div>

                <h5 class="fw-bold mb-2"><?= e($test['title']) ?></h5>
                <p class="text-muted small flex-grow-1 mb-3">
                    Comprehensive assessment covering core principles, best practices, and practical problem solving in <?= e($test['skill_name']) ?>.
                </p>

                <div class="d-flex justify-content-between align-items-center bg-light p-2 px-3 rounded mb-3 fs-7 text-muted dark-mode-card">
                    <span><i class="fas fa-question-circle me-1"></i><?= $questionCount ?> Questions</span>
                    <span><i class="fas fa-percentage me-1"></i>Pass Score: <?= (int)$test['passing_score'] ?>%</span>
                </div>

                <div class="mt-auto pt-2">
                    <?php if ($isCompleted): ?>
                        <?php if ($testResult['passed']): ?>
                            <div class="p-2 px-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success"><i class="fas fa-check-circle me-2"></i>Score: <?= (int)$testResult['score'] ?>% (Passed)</span>
                                <span class="badge bg-success"><i class="fas fa-award me-1"></i>Verified Badge</span>
                            </div>
                        <?php else: ?>
                            <div class="p-2 px-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-danger"><i class="fas fa-times-circle me-2"></i>Score: <?= (int)$testResult['score'] ?>% (Failed)</span>
                                <a href="<?= baseUrl('freelancer/take-test.php?id=' . $test['id']) ?>" class="btn btn-sm btn-outline-danger">Retake Test</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= baseUrl('freelancer/take-test.php?id=' . $test['id']) ?>" class="btn btn-primary w-100 fw-bold">
                            <i class="fas fa-play me-2"></i>Start Assessment Test
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
