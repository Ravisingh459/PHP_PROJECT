<?php
/**
 * FreelanceHub - Logout
 */
require_once __DIR__ . '/includes/auth.php';

logoutUser();
setFlash('success', 'You have been logged out successfully.');
redirect(baseUrl('login.php'));
