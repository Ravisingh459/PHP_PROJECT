<?php
/**
 * FreelanceHub - Authentication & Authorization
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Register a new user
 */
function registerUser(array $data): array
{
    $errors = [];

    // Validation
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if (empty($data['password']) || strlen($data['password']) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    }
    if ($data['password'] !== ($data['confirm_password'] ?? '')) {
        $errors[] = 'Passwords do not match.';
    }
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $errors[] = 'First and last name are required.';
    }
    if (!in_array($data['role'] ?? '', ['client', 'freelancer'])) {
        $errors[] = 'Invalid account type.';
    }

    // Check existing email
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        $errors[] = 'Email already registered.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    try {
        db()->beginTransaction();

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = db()->prepare('INSERT INTO users (email, password, role, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['email'],
            $hashedPassword,
            $data['role'],
            sanitize($data['first_name']),
            sanitize($data['last_name']),
            sanitize($data['phone'] ?? ''),
        ]);

        $userId = (int) db()->lastInsertId();

        // Create role-specific profile
        if ($data['role'] === 'freelancer') {
            $stmt = db()->prepare('INSERT INTO freelancer_profiles (user_id) VALUES (?)');
            $stmt->execute([$userId]);
        } else {
            $stmt = db()->prepare('INSERT INTO client_profiles (user_id) VALUES (?)');
            $stmt->execute([$userId]);
        }

        db()->commit();

        // Send verification email
        sendVerificationEmail($userId, $data['email'], $data['first_name']);

        return ['success' => true, 'user_id' => $userId];
    } catch (Exception $e) {
        db()->rollBack();
        error_log('Registration error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
    }
}

/**
 * Authenticate user login
 */
function loginUser(string $email, string $password): array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Update last login
    $stmt = db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $stmt->execute([$user['id']]);

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['language'] = $user['language'];
    $_SESSION['dark_mode'] = (bool) $user['dark_mode'];

    // Regenerate session ID for security
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }

    return ['success' => true, 'user' => $user];
}

/**
 * Logout user
 */
function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Get current logged-in user
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return getUserById((int) $_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function currentUserId(): ?int
{
    return isLoggedIn() ? (int) $_SESSION['user_id'] : null;
}

/**
 * Get current user role
 */
function currentUserRole(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Require authentication
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Please login to continue.');
        redirect(baseUrl('login.php'));
    }
}

/**
 * Require specific role
 */
function requireRole(string ...$roles): void
{
    requireAuth();
    if (!in_array(currentUserRole(), $roles)) {
        setFlash('danger', 'Access denied.');
        redirect(baseUrl('dashboard.php'));
    }
}

/**
 * Redirect to appropriate dashboard based on role
 */
function redirectToDashboard(): void
{
    $role = currentUserRole();
    switch ($role) {
        case 'admin':
            redirect(baseUrl('admin/index.php'));
        case 'client':
            redirect(baseUrl('client/index.php'));
        case 'freelancer':
            redirect(baseUrl('freelancer/index.php'));
        default:
            redirect(baseUrl('index.php'));
    }
}

/**
 * Verify email token
 */
function verifyEmail(string $token): bool
{
    $stmt = db()->prepare('SELECT * FROM email_verification_tokens WHERE token = ? AND expires_at > NOW()');
    $stmt->execute([$token]);
    $record = $stmt->fetch();

    if (!$record) return false;

    $stmt = db()->prepare('UPDATE users SET is_verified = 1 WHERE id = ?');
    $stmt->execute([$record['user_id']]);

    $stmt = db()->prepare('DELETE FROM email_verification_tokens WHERE id = ?');
    $stmt->execute([$record['id']]);

    return true;
}

/**
 * Request password reset
 */
function requestPasswordReset(string $email): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) return true; // Don't reveal if email exists

    return sendPasswordResetEmail($user['id'], $user['email'], $user['first_name']);
}

/**
 * Reset password with token
 */
function resetPassword(string $token, string $newPassword): array
{
    if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        return ['success' => false, 'error' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.'];
    }

    $stmt = db()->prepare('SELECT * FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() AND used = 0');
    $stmt->execute([$token]);
    $record = $stmt->fetch();

    if (!$record) {
        return ['success' => false, 'error' => 'Invalid or expired reset token.'];
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([$hashedPassword, $record['user_id']]);

    $stmt = db()->prepare('UPDATE password_reset_tokens SET used = 1 WHERE id = ?');
    $stmt->execute([$record['id']]);

    return ['success' => true];
}
