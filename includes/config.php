<?php
/**
 * FreelanceHub - Application Configuration
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('FREELANCEHUB')) {
    define('FREELANCEHUB', true);
}

// Application settings
define('APP_NAME', 'NexaWork');
define('APP_TAGLINE', 'AI-Powered Talent Marketplace');
define('APP_URL', 'http://localhost/PHP/freelancehub');
define('APP_VERSION', '2.0.0');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'freelancehub');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Session configuration
define('SESSION_NAME', 'freelancehub_session');
define('SESSION_LIFETIME', 7200); // 2 hours

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// File upload settings
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Email configuration (PHPMailer)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM', 'noreply@nexawork.com');
define('MAIL_FROM_NAME', 'NexaWork');

// Currency (Indian Rupee)
define('CURRENCY_CODE', 'INR');
define('CURRENCY_SYMBOL', '₹');
define('MIN_PROJECT_BUDGET', 5000);
define('MAX_PROJECT_BUDGET', 5000000);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Timezone (India)
date_default_timezone_set('Asia/Kolkata');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');
    session_name(SESSION_NAME);
    session_start();
}
