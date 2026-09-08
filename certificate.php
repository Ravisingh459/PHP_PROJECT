<?php
/**
 * FreelanceHub - Project Completion Certificate
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$code = $_GET['code'] ?? '';
$stmt = db()->prepare('SELECT cert.*, fp.user_id, u.first_name, u.last_name, p.title as project_title, c.amount, c.end_date
    FROM certificates cert
    JOIN freelancer_profiles fp ON cert.freelancer_id = fp.id
    JOIN users u ON fp.user_id = u.id
    JOIN contracts c ON cert.contract_id = c.id
    JOIN projects p ON c.project_id = p.id
    WHERE cert.certificate_code = ?');
$stmt->execute([$code]);
$cert = $stmt->fetch();

if (!$cert) {
    setFlash('danger', 'Certificate not found.');
    redirect(baseUrl());
}

$pageTitle = 'Certificate of Completion';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="certificate-preview mx-auto" style="max-width:700px;">
        <div class="mb-4">
            <i class="fas fa-certificate fa-4x text-primary"></i>
        </div>
        <h6 class="text-muted text-uppercase letter-spacing">Certificate of Completion</h6>
        <h2 class="my-4">This certifies that</h2>
        <h1 class="fw-bold text-primary mb-4"><?= e($cert['first_name'] . ' ' . $cert['last_name']) ?></h1>
        <p class="fs-5">has successfully completed the project</p>
        <h3 class="fw-bold my-3">"<?= e($cert['project_title']) ?>"</h3>
        <p class="text-muted">Project Value: <?= formatMoney((float)$cert['amount']) ?></p>
        <hr class="my-4">
        <div class="d-flex justify-content-between">
            <div>
                <small class="text-muted">Certificate ID</small>
                <p class="fw-bold"><?= e($cert['certificate_code']) ?></p>
            </div>
            <div>
                <small class="text-muted">Issued Date</small>
                <p class="fw-bold"><?= formatDate($cert['issued_at']) ?></p>
            </div>
            <div>
                <small class="text-muted">Platform</small>
                <p class="fw-bold"><?= e(APP_NAME) ?></p>
            </div>
        </div>
        <button onclick="window.print()" class="btn btn-primary mt-4"><i class="fas fa-print me-2"></i>Print Certificate</button>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
