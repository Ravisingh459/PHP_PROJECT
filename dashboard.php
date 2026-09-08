<?php
/**
 * FreelanceHub - Dashboard Router
 */
require_once __DIR__ . '/includes/auth.php';

requireAuth();
redirectToDashboard();
