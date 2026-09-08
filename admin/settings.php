<?php
/**
 * NexaWork - Admin Site Settings Module
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Site Settings';
$sidebarRole = 'admin';

$toggleKeys = [
    'enable_registration', 'require_email_verify', 'maintenance_mode',
    'force_ssl', 'enable_email_notifs', 'enable_skill_tests',
    'enable_ai_matchmaking', 'enable_review_moderation'
];

if (isPost() && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $settings = $_POST['settings'] ?? [];
    
    // Ensure unchecked switches submit '0'
    foreach ($toggleKeys as $tk) {
        if (!isset($settings[$tk])) {
            $settings[$tk] = '0';
        }
    }

    foreach ($settings as $key => $value) {
        $cleanVal = sanitize($value);
        db()->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?')
            ->execute([$key, $cleanVal, $cleanVal]);
    }
    setFlash('success', 'Site configuration settings saved successfully.');
    redirect(baseUrl('admin/settings.php'));
}

$allSettings = db()->query('SELECT * FROM site_settings')->fetchAll();
$settingsMap = [];
foreach ($allSettings as $s) {
    $settingsMap[$s['setting_key']] = $s['setting_value'];
}

$get = fn($key, $default = '') => $settingsMap[$key] ?? $default;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
echo displayFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-sliders-h me-2 text-primary"></i>System & Site Settings</h4>
        <p class="text-muted mb-0">Configure global marketplace parameters, security rules, payment fees, and feature toggles.</p>
    </div>
</div>

<div class="card">
    <div class="card-header bg-transparent p-0 border-bottom">
        <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="settingsTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active py-3" id="general-tab" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
                    <i class="fas fa-cog me-2"></i>General & Branding
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-3" id="financial-tab" data-bs-toggle="tab" data-bs-target="#tab-financial" type="button" role="tab">
                    <i class="fas fa-indian-rupee-sign me-2 text-success"></i>Financial & Escrow
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-3" id="security-tab" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">
                    <i class="fas fa-shield-alt me-2 text-danger"></i>Security & Access
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-3" id="email-tab" data-bs-toggle="tab" data-bs-target="#tab-email" type="button" role="tab">
                    <i class="fas fa-paper-plane me-2 text-info"></i>Email & Notifications
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-3" id="ai-tab" data-bs-toggle="tab" data-bs-target="#tab-ai" type="button" role="tab">
                    <i class="fas fa-robot me-2 text-warning"></i>AI & Feature Flags
                </button>
            </li>
        </ul>
    </div>

    <form method="POST">
        <?= csrfField() ?>
        <div class="card-body p-4">
            <div class="tab-content" id="settingsTabContent">
                
                <!-- General Settings -->
                <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                    <h5 class="fw-bold mb-3"><i class="fas fa-globe me-2 text-primary"></i>Branding & Platform Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Site Name *</label>
                            <input type="text" name="settings[site_name]" class="form-control" value="<?= e($get('site_name', APP_NAME)) ?>" required>
                            <div class="form-text">Platform name displayed across emails, headers, and footer.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tagline *</label>
                            <input type="text" name="settings[site_tagline]" class="form-control" value="<?= e($get('site_tagline', 'AI-Powered Talent Marketplace')) ?>" required>
                            <div class="form-text">Short slogan shown on home page & metadata.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Support Email *</label>
                            <input type="email" name="settings[site_email]" class="form-control" value="<?= e($get('site_email', 'support@nexawork.com')) ?>" required>
                            <div class="form-text">Primary contact address for system support inquiries.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Phone</label>
                            <input type="text" name="settings[contact_phone]" class="form-control" value="<?= e($get('contact_phone', '+91 98765 43210')) ?>">
                            <div class="form-text">Customer helpline number.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Currency Symbol</label>
                            <input type="text" name="settings[currency_symbol]" class="form-control" value="<?= e($get('currency_symbol', '₹')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Platform Country</label>
                            <input type="text" name="settings[platform_country]" class="form-control" value="<?= e($get('platform_country', 'India')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Default Timezone</label>
                            <select name="settings[site_timezone]" class="form-select">
                                <option value="Asia/Kolkata" <?= $get('site_timezone', 'Asia/Kolkata') === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata (IST +5:30)</option>
                                <option value="UTC" <?= $get('site_timezone') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Financial Settings -->
                <div class="tab-pane fade" id="tab-financial" role="tabpanel">
                    <h5 class="fw-bold mb-3"><i class="fas fa-wallet me-2 text-success"></i>Fees, Limits & Escrow Rules</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Platform Commission Rate (%) *</label>
                            <div class="input-group">
                                <input type="number" step="0.1" min="0" max="50" name="settings[commission_rate]" class="form-control" value="<?= e($get('commission_rate', '10')) ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Percentage deducted from completed project contract amounts.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Escrow Release Hold (Days)</label>
                            <div class="input-group">
                                <input type="number" min="0" max="30" name="settings[escrow_hold_days]" class="form-control" value="<?= e($get('escrow_hold_days', '7')) ?>">
                                <span class="input-group-text">days</span>
                            </div>
                            <div class="form-text">Automatic escrow release period after project completion.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Minimum Project Budget (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="settings[min_project_budget]" class="form-control" value="<?= e($get('min_project_budget', '5000')) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Maximum Project Budget (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="settings[max_project_budget]" class="form-control" value="<?= e($get('max_project_budget', '500000')) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Minimum Freelancer Payout (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="settings[min_payout_amount]" class="form-control" value="<?= e($get('min_payout_amount', '1000')) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Applicable GST / Service Tax (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="settings[tax_rate_percent]" class="form-control" value="<?= e($get('tax_rate_percent', '18')) ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="tab-pane fade" id="tab-security" role="tabpanel">
                    <h5 class="fw-bold mb-3"><i class="fas fa-user-shield me-2 text-danger"></i>Authentication & System Security</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card p-3 h-100 bg-body-tertiary">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enable_registration" name="settings[enable_registration]" value="1" <?= $get('enable_registration', '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="enable_registration">Enable Public Registration</label>
                                </div>
                                <small class="text-muted">Allow new clients and freelancers to create accounts publicly.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card p-3 h-100 bg-body-tertiary">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="require_email_verify" name="settings[require_email_verify]" value="1" <?= $get('require_email_verify', '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="require_email_verify">Require Email Verification</label>
                                </div>
                                <small class="text-muted">New users must verify email address before posting projects or bidding.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card p-3 h-100 bg-body-tertiary border-warning">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="maintenance_mode" name="settings[maintenance_mode]" value="1" <?= $get('maintenance_mode', '0') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-warning" for="maintenance_mode">Maintenance Mode</label>
                                </div>
                                <small class="text-muted">Temporarily restrict access for non-admin users during site updates.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card p-3 h-100 bg-body-tertiary">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="force_ssl" name="settings[force_ssl]" value="1" <?= $get('force_ssl', '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="force_ssl">Force HTTPS / SSL</label>
                                </div>
                                <small class="text-muted">Redirect all HTTP requests to secure HTTPS protocol.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Login Attempt Limit</label>
                            <input type="number" name="settings[max_login_attempts]" class="form-control" value="<?= e($get('max_login_attempts', '5')) ?>">
                            <div class="form-text">Temporary lock out after consecutive failed passwords.</div>
                        </div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="tab-pane fade" id="tab-email" role="tabpanel">
                    <h5 class="fw-bold mb-3"><i class="fas fa-mail-bulk me-2 text-info"></i>SMTP & Automated Email Configuration</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">System Sender Name</label>
                            <input type="text" name="settings[mail_from_name]" class="form-control" value="<?= e($get('mail_from_name', 'NexaWork Support')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">System Sender Email</label>
                            <input type="email" name="settings[mail_from_address]" class="form-control" value="<?= e($get('mail_from_address', 'no-reply@nexawork.com')) ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">SMTP Server Host</label>
                            <input type="text" name="settings[smtp_host]" class="form-control" value="<?= e($get('smtp_host', 'smtp.nexawork.com')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">SMTP Port</label>
                            <input type="number" name="settings[smtp_port]" class="form-control" value="<?= e($get('smtp_port', '587')) ?>">
                        </div>
                        <div class="col-12 mt-3">
                            <div class="card p-3 bg-body-tertiary">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enable_email_notifs" name="settings[enable_email_notifs]" value="1" <?= $get('enable_email_notifs', '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="enable_email_notifs">Enable System Email Notifications</label>
                                </div>
                                <small class="text-muted">Send automated transaction, contract, and report update emails to users.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI & Features Settings -->
                <div class="tab-pane fade" id="tab-ai" role="tabpanel">
                    <h5 class="fw-bold mb-3"><i class="fas fa-brain me-2 text-warning"></i>AI & Platform Feature Modules</h5>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card p-3 h-100 bg-body-tertiary">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enable_skill_tests" name="settings[enable_skill_tests]" value="1" <?= $get('enable_skill_tests', '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="enable_skill_tests">Skill Verification Tests</label>
                                </div>
                                <small class="text-muted">Enable timed quizzes & certificates for freelancer profiles.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 h-100 bg-body-tertiary">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enable_ai_matchmaking" name="settings[enable_ai_matchmaking]" value="1" <?= $get('enable_ai_matchmaking', '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="enable_ai_matchmaking">AI Smart Matchmaking</label>
                                </div>
                                <small class="text-muted">Auto-recommend top freelancers to clients based on project requirements.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 h-100 bg-body-tertiary">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enable_review_moderation" name="settings[enable_review_moderation]" value="1" <?= $get('enable_review_moderation', '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="enable_review_moderation">Admin Review Moderation</label>
                                </div>
                                <small class="text-muted">Require admin approval before client reviews are published on freelancer profiles.</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer bg-transparent p-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small"><i class="fas fa-info-circle me-1"></i>All settings are saved directly into system configuration database.</span>
            <button type="submit" class="btn btn-primary px-4 fw-bold">
                <i class="fas fa-save me-2"></i>Save All Settings
            </button>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../includes/sidebar-close.php';
require_once __DIR__ . '/../includes/footer.php';
