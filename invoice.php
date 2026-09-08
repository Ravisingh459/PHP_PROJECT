<?php
/**
 * NexaWork - Official Printable PDF Tax Invoice
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$paymentId = (int)($_GET['id'] ?? 0);
$userId = currentUserId();
$userRole = currentUserRole();

// Fetch payment details
$sql = 'SELECT py.*, c.project_id, c.start_date, p.title as project_title, p.category,
        client.first_name as client_fname, client.last_name as client_lname, client.email as client_email, cp.company_name, cp.location as client_location,
        fl.first_name as fl_fname, fl.last_name as fl_lname, fl.email as fl_email, fp.location as fl_location
        FROM payments py
        JOIN contracts c ON py.contract_id = c.id
        JOIN projects p ON c.project_id = p.id
        JOIN users client ON py.payer_id = client.id
        LEFT JOIN client_profiles cp ON client.id = cp.user_id
        JOIN users fl ON py.payee_id = fl.id
        LEFT JOIN freelancer_profiles fp ON fl.id = fp.user_id
        WHERE py.id = ?';

$stmt = db()->prepare($sql);
$stmt->execute([$paymentId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('danger', 'Invoice not found.');
    redirect(baseUrl('dashboard.php'));
}

// Security Check: Only Payer, Payee or Admin can view
if ($userRole !== 'admin' && $invoice['payer_id'] !== $userId && $invoice['payee_id'] !== $userId) {
    setFlash('danger', 'Access denied to this invoice.');
    redirect(baseUrl('dashboard.php'));
}

$pageTitle = 'Tax Invoice #' . ($invoice['transaction_ref'] ?? 'INV-' . $invoice['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #0B1020; font-family: 'Inter', system-ui, sans-serif; color: #F8FAFC; }
        .invoice-card { background: #131B30; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); padding: 40px; margin-top: 40px; margin-bottom: 40px; border: 1px solid #1E2D4A; }
        .invoice-header { border-bottom: 2px solid #4F7CFF; padding-bottom: 20px; margin-bottom: 30px; }
        .brand-logo { color: #4F7CFF; font-weight: 800; font-size: 24px; text-decoration: none; }
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff; }
            .invoice-card { box-shadow: none; border: none; padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

<div class="container max-w-800">
    <div class="d-flex justify-content-between align-items-center mt-4 no-print">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i> Print / Download PDF Invoice</button>
    </div>

    <div class="invoice-card">
        <!-- Header -->
        <div class="invoice-header d-flex justify-content-between align-items-start">
            <div>
                <a href="#" class="brand-logo"><i class="fas fa-bolt me-2 text-warning"></i>NexaWork</a>
                <p class="text-muted small mb-0">AI-Powered Talent Marketplace Inc.</p>
                <small class="text-muted">GSTIN: 29AAAAA0000A1Z5 | Reg: KA/2026/NX88</small>
            </div>
            <div class="text-end">
                <h4 class="fw-bold text-uppercase text-secondary mb-1">TAX INVOICE</h4>
                <div class="small fw-bold"># <?= e($invoice['transaction_ref'] ?? 'INV-' . $invoice['id']) ?></div>
                <div class="small text-muted">Date: <?= formatDate($invoice['created_at']) ?></div>
                <div class="small"><span class="badge bg-success">Status: <?= ucfirst($invoice['status']) ?></span></div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="row mb-4">
            <div class="col-6">
                <h6 class="fw-bold text-muted text-uppercase fs-7 mb-2">Billed To (Client)</h6>
                <div class="fw-bold fs-6"><?= e($invoice['client_fname'] . ' ' . $invoice['client_lname']) ?></div>
                <?php if ($invoice['company_name']): ?><div class="small text-muted"><?= e($invoice['company_name']) ?></div><?php endif; ?>
                <div class="small text-muted"><?= e($invoice['client_email']) ?></div>
                <div class="small text-muted"><?= e($invoice['client_location'] ?? 'India') ?></div>
            </div>
            <div class="col-6 text-end">
                <h6 class="fw-bold text-muted text-uppercase fs-7 mb-2">Payee (Freelancer)</h6>
                <div class="fw-bold fs-6"><?= e($invoice['fl_fname'] . ' ' . $invoice['fl_lname']) ?></div>
                <div class="small text-muted"><?= e($invoice['fl_email']) ?></div>
                <div class="small text-muted"><?= e($invoice['fl_location'] ?? 'Remote') ?></div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="bg-light fs-7">
                    <tr>
                        <th>Item & Description</th>
                        <th>Category</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong class="text-dark"><?= e($invoice['project_title']) ?></strong>
                            <div class="small text-muted">Milestone Contract Deliverable Escrow Payment</div>
                        </td>
                        <td><?= e($invoice['category'] ?? 'Development') ?></td>
                        <td class="text-center">1</td>
                        <td class="text-end fw-bold"><?= formatMoney((float)$invoice['amount']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="row justify-content-end">
            <div class="col-md-5">
                <div class="d-flex justify-content-between py-1 fs-7">
                    <span class="text-muted">Subtotal:</span>
                    <span><?= formatMoney((float)$invoice['amount']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 fs-7">
                    <span class="text-muted">Platform Service Fee (0% Promo):</span>
                    <span>₹0.00</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between py-2 align-items-center">
                    <span class="fw-bold fs-6">Total Amount Paid:</span>
                    <span class="fw-bold text-success fs-4"><?= formatMoney((float)$invoice['amount']) ?></span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-top pt-4 mt-4 text-center small text-muted">
            <p class="mb-1">Thank you for choosing <strong>NexaWork</strong> – India's trusted AI talent marketplace.</p>
            <small>This is a computer-generated tax invoice and requires no physical signature.</small>
        </div>
    </div>
</div>

</body>
</html>
