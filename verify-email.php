<?php
/**
 * FreelanceHub - Email Verification
 */
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    setFlash('danger', 'Invalid verification link.');
    redirect(baseUrl('login.php'));
}

if (verifyEmail($token)) {
    setFlash('success', 'Email verified successfully! You can now login.');
} else {
    setFlash('danger', 'Invalid or expired verification link.');
}

redirect(baseUrl('login.php'));
