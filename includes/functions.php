<?php
/**
 * FreelanceHub - Helper Functions
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ============================================================
// SECURITY FUNCTIONS
// ============================================================

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken(?string $token): bool
{
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token ?? '');
}

/**
 * Output CSRF hidden input field
 */
function csrfField(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCsrfToken() . '">';
}

/**
 * Sanitize output for XSS prevention
 */
function e(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input
 */
function sanitize(string $input): string
{
    return trim(strip_tags($input));
}

// ============================================================
// URL & REDIRECT
// ============================================================

function baseUrl(string $path = ''): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ============================================================
// FLASH MESSAGES
// ============================================================

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function displayFlash(): string
{
    $flash = getFlash();
    if (!$flash) return '';

    $type = $flash['type'];
    $message = e($flash['message']);
    return "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">
        {$message}
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
    </div>";
}

// ============================================================
// USER FUNCTIONS
// ============================================================

function getUserById(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function getUserFullName(array $user): string
{
    return e($user['first_name'] . ' ' . $user['last_name']);
}

function getFreelancerProfile(int $userId): ?array
{
    $stmt = db()->prepare('SELECT fp.*, u.first_name, u.last_name, u.email, u.avatar
        FROM freelancer_profiles fp
        JOIN users u ON fp.user_id = u.id
        WHERE fp.user_id = ?');
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
    if (!$profile) {
        // Auto-create profile if missing
        db()->prepare('INSERT IGNORE INTO freelancer_profiles (user_id) VALUES (?)')->execute([$userId]);
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
    }
    return $profile ?: null;
}

function getClientProfile(int $userId): ?array
{
    $stmt = db()->prepare('SELECT cp.*, u.first_name, u.last_name, u.email, u.avatar
        FROM client_profiles cp
        JOIN users u ON cp.user_id = u.id
        WHERE cp.user_id = ?');
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
    if (!$profile) {
        // Auto-create profile if missing
        db()->prepare('INSERT IGNORE INTO client_profiles (user_id) VALUES (?)')->execute([$userId]);
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
    }
    return $profile ?: null;
}

function getAvatarUrl(?string $avatar): string
{
    if ($avatar && file_exists(UPLOAD_PATH . 'profiles/' . $avatar)) {
        return baseUrl('assets/uploads/profiles/' . $avatar);
    }
    return baseUrl('assets/images/default-avatar.svg');
}

// ============================================================
// NOTIFICATION FUNCTIONS
// ============================================================

function createNotification(int $userId, string $type, string $title, string $message, ?string $link = null): void
{
    $stmt = db()->prepare('INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $type, $title, $message, $link]);
}

function getUnreadNotificationCount(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function getNotifications(int $userId, int $limit = 10): array
{
    $stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// ============================================================
// PROJECT FUNCTIONS
// ============================================================

function getProjectById(int $id): ?array
{
    $stmt = db()->prepare('SELECT p.*, u.first_name, u.last_name, cp.company_name
        FROM projects p
        JOIN users u ON p.client_id = u.id
        LEFT JOIN client_profiles cp ON cp.user_id = u.id
        WHERE p.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getProjectSkills(int $projectId): array
{
    $stmt = db()->prepare('SELECT s.* FROM skills s
        JOIN project_skills ps ON s.id = ps.skill_id
        WHERE ps.project_id = ?');
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

function getProjectBidCount(int $projectId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM bids WHERE project_id = ?');
    $stmt->execute([$projectId]);
    return (int) $stmt->fetchColumn();
}

// ============================================================
// FILE UPLOAD & SECURITY
// ============================================================

function uploadFile(array $file, string $directory, array $allowedTypes = []): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return null;
    }

    // Blacklist executable extensions
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $dangerousExts = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'inc', 'pl', 'py', 'cgi', 'sh', 'exe', 'bat', 'js', 'html', 'htm'];
    if (in_array($extension, $dangerousExts, true)) {
        return null;
    }

    // Server-side MIME type verification using finfo
    if (function_exists('finfo_open') && !empty($file['tmp_name'])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!empty($allowedTypes) && !in_array($mime, $allowedTypes, true)) {
            return null;
        }
    } elseif (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes, true)) {
        return null;
    }

    $cleanDir = preg_replace('/[^a-zA-Z0-9_\-]/', '', $directory);
    $uploadDir = UPLOAD_PATH . $cleanDir . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }

    return null;
}


// ============================================================
// PAGINATION
// ============================================================

function paginate(int $total, int $page, int $perPage = ITEMS_PER_PAGE): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current_page'=> $page,
        'total_pages' => $totalPages,
        'offset'      => $offset,
    ];
}

function renderPagination(array $pagination, string $baseUrl): string
{
    if ($pagination['total_pages'] <= 1) return '';

    $html = '<nav><ul class="pagination justify-content-center">';
    $current = $pagination['current_page'];
    $total = $pagination['total_pages'];

    // Previous
    $prevDisabled = $current <= 1 ? 'disabled' : '';
    $html .= "<li class=\"page-item {$prevDisabled}\"><a class=\"page-link\" href=\"{$baseUrl}&page=" . ($current - 1) . "\">&laquo;</a></li>";

    for ($i = max(1, $current - 2); $i <= min($total, $current + 2); $i++) {
        $active = $i === $current ? 'active' : '';
        $html .= "<li class=\"page-item {$active}\"><a class=\"page-link\" href=\"{$baseUrl}&page={$i}\">{$i}</a></li>";
    }

    // Next
    $nextDisabled = $current >= $total ? 'disabled' : '';
    $html .= "<li class=\"page-item {$nextDisabled}\"><a class=\"page-link\" href=\"{$baseUrl}&page=" . ($current + 1) . "\">&raquo;</a></li>";

    $html .= '</ul></nav>';
    return $html;
}

// ============================================================
// FORMATTING
// ============================================================

function formatMoney(float $amount): string
{
    $negative = $amount < 0;
    $amount = abs($amount);
    $parts = explode('.', number_format($amount, 2, '.', ''));
    $integer = $parts[0];
    $decimal = $parts[1] ?? '00';

    if (strlen($integer) > 3) {
        $lastThree = substr($integer, -3);
        $rest = substr($integer, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $formatted = ($rest !== '' ? $rest . ',' : '') . $lastThree;
    } else {
        $formatted = $integer;
    }

    $result = CURRENCY_SYMBOL . $formatted . '.' . $decimal;
    return $negative ? '-' . $result : $result;
}

function getMinProjectBudget(): float
{
    return (float) getSetting('min_project_budget', (string) MIN_PROJECT_BUDGET);
}

function formatDate(string $date): string
{
    return date('M d, Y', strtotime($date));
}

function formatDateTime(string $datetime): string
{
    return date('M d, Y h:i A', strtotime($datetime));
}

function timeAgo(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return formatDate($datetime);
}

function renderStars(float $rating): string
{
    $html = '<div class="star-rating">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-warning"></i>';
        }
    }
    $html .= ' <span class="text-muted">(' . number_format($rating, 1) . ')</span></div>';
    return $html;
}

function getStatusBadge(string $status): string
{
    $badges = [
        'open'         => 'success',
        'in_progress'  => 'primary',
        'completed'    => 'info',
        'cancelled'    => 'danger',
        'pending'      => 'warning',
        'accepted'     => 'success',
        'rejected'     => 'danger',
        'withdrawn'    => 'secondary',
        'active'       => 'primary',
        'disputed'     => 'danger',
        'escrow'       => 'warning',
        'released'     => 'success',
        'refunded'     => 'info',
        'paid'         => 'success',
    ];
    $color = $badges[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

// ============================================================
// SITE SETTINGS
// ============================================================

function getSetting(string $key, string $default = ''): string
{
    static $settings = null;
    if ($settings === null) {
        $stmt = db()->query('SELECT setting_key, setting_value FROM site_settings');
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings[$key] ?? $default;
}

// ============================================================
// AI RECOMMENDATION (Multi-factor smart matching)
// ============================================================

function calculateMatchScore(int $matchingSkills, int $totalSkills, float $avgRating, int $completedProjects, float $budgetFit = 1.0): int
{
    if ($totalSkills === 0) {
        $skillScore = 40;
    } else {
        $skillScore = (int) round(($matchingSkills / $totalSkills) * 50);
    }

    $ratingScore = (int) round(($avgRating / 5) * 25);
    $experienceScore = min(15, $completedProjects * 3);
    $budgetScore = (int) round($budgetFit * 10);

    return min(100, $skillScore + $ratingScore + $experienceScore + $budgetScore);
}

function calculateTrustScore(array $profile): int
{
    $rating = (float) ($profile['avg_rating'] ?? 0);
    $reviews = (int) ($profile['total_reviews'] ?? 0);
    $completed = (int) ($profile['completed_projects'] ?? 0);
    $verified = (int) ($profile['is_verified'] ?? 0);

    $score = 20;
    $score += min(30, (int) round($rating * 6));
    $score += min(20, $reviews * 2);
    $score += min(20, $completed * 4);
    $score += $verified ? 10 : 0;

    return min(100, $score);
}

function renderMatchBadge(int $score): string
{
    $class = $score >= 80 ? 'match-excellent' : ($score >= 60 ? 'match-good' : 'match-fair');
    return '<span class="match-badge ' . $class . '"><i class="fas fa-bolt me-1"></i>' . $score . '% Match</span>';
}

function renderTrustBadge(int $score): string
{
    $label = $score >= 80 ? 'Trusted Pro' : ($score >= 60 ? 'Verified' : 'Rising');
    return '<span class="trust-badge" title="Trust Score: ' . $score . '/100"><i class="fas fa-shield-alt me-1"></i>' . $label . '</span>';
}

function getRecommendedFreelancers(int $projectId, int $limit = 5): array
{
    $project = getProjectById($projectId);
    $projectBudget = (float) ($project['budget'] ?? 0);

    $stmt = db()->prepare('
        SELECT fp.*, u.first_name, u.last_name, u.avatar, u.is_verified,
            COUNT(fs.skill_id) as matching_skills,
            (SELECT COUNT(*) FROM project_skills WHERE project_id = ?) as total_skills
        FROM freelancer_profiles fp
        JOIN users u ON fp.user_id = u.id
        JOIN freelancer_skills fs ON fs.freelancer_id = fp.id
        WHERE fs.skill_id IN (SELECT skill_id FROM project_skills WHERE project_id = ?)
        AND u.is_active = 1
        GROUP BY fp.id
        ORDER BY (COUNT(fs.skill_id) / GREATEST((SELECT COUNT(*) FROM project_skills WHERE project_id = ?), 1)) DESC,
                 fp.avg_rating DESC
        LIMIT ?
    ');
    $stmt->execute([$projectId, $projectId, $projectId, $limit]);
    $results = $stmt->fetchAll();

    foreach ($results as &$row) {
        $hourlyEstimate = (float) $row['hourly_rate'] * 40;
        $budgetFit = $projectBudget > 0 ? min(1.0, $projectBudget / max($hourlyEstimate, 1)) : 1.0;
        $row['match_score'] = calculateMatchScore(
            (int) $row['matching_skills'],
            (int) $row['total_skills'],
            (float) $row['avg_rating'],
            (int) $row['completed_projects'],
            $budgetFit
        );
        $row['trust_score'] = calculateTrustScore($row);
    }
    unset($row);

    return $results;
}

function getRecommendedProjects(int $freelancerUserId, int $limit = 5): array
{
    $profile = getFreelancerProfile($freelancerUserId);
    if (!$profile) return [];

    $stmt = db()->prepare('
        SELECT p.*, u.first_name, u.last_name,
            COUNT(ps.skill_id) as matching_skills,
            (SELECT COUNT(*) FROM project_skills WHERE project_id = p.id) as total_skills
        FROM projects p
        JOIN users u ON p.client_id = u.id
        JOIN project_skills ps ON ps.project_id = p.id
        WHERE p.status = "open"
        AND ps.skill_id IN (SELECT skill_id FROM freelancer_skills WHERE freelancer_id = ?)
        AND p.id NOT IN (SELECT project_id FROM bids WHERE freelancer_id = ?)
        GROUP BY p.id
        ORDER BY matching_skills DESC, p.created_at DESC
        LIMIT ?
    ');
    $stmt->execute([$profile['id'], $freelancerUserId, $limit]);
    $results = $stmt->fetchAll();

    foreach ($results as &$row) {
        $row['match_score'] = calculateMatchScore(
            (int) $row['matching_skills'],
            max(1, (int) $row['total_skills']),
            (float) $profile['avg_rating'],
            (int) $profile['completed_projects']
        );
    }
    unset($row);

    return $results;
}

function getLiveActivity(int $limit = 12): array
{
    $activities = [];

    $bids = db()->query('
        SELECT b.created_at, u.first_name, p.title, "bid" as type
        FROM bids b
        JOIN users u ON b.freelancer_id = u.id
        JOIN projects p ON b.project_id = p.id
        ORDER BY b.created_at DESC LIMIT ' . (int) $limit
    )->fetchAll();

    foreach ($bids as $b) {
        $activities[] = [
            'type' => 'bid',
            'icon' => 'fa-gavel',
            'text' => e($b['first_name']) . ' bid on "' . e($b['title']) . '"',
            'time' => $b['created_at'],
        ];
    }

    $contracts = db()->query('
        SELECT c.created_at, u.first_name, p.title, "hire" as type
        FROM contracts c
        JOIN users u ON c.freelancer_id = u.id
        JOIN projects p ON c.project_id = p.id
        ORDER BY c.created_at DESC LIMIT ' . (int) $limit
    )->fetchAll();

    foreach ($contracts as $c) {
        $activities[] = [
            'type' => 'hire',
            'icon' => 'fa-handshake',
            'text' => e($c['first_name']) . ' was hired for "' . e($c['title']) . '"',
            'time' => $c['created_at'],
        ];
    }

    $reviews = db()->query('
        SELECT r.created_at, u.first_name, r.rating, "review" as type
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.id
        ORDER BY r.created_at DESC LIMIT ' . (int) $limit
    )->fetchAll();

    foreach ($reviews as $r) {
        $activities[] = [
            'type' => 'review',
            'icon' => 'fa-star',
            'text' => e($r['first_name']) . ' left a ' . $r['rating'] . '-star review',
            'time' => $r['created_at'],
        ];
    }

    usort($activities, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));

    return array_slice($activities, 0, $limit);
}

function getLeaderboard(int $limit = 20): array
{
    $stmt = db()->prepare('
        SELECT fp.*, u.first_name, u.last_name, u.avatar, u.is_verified,
            (fp.avg_rating * 20 + fp.completed_projects * 5 + fp.total_earnings / 10000) as leaderboard_score
        FROM freelancer_profiles fp
        JOIN users u ON fp.user_id = u.id
        WHERE u.is_active = 1
        ORDER BY leaderboard_score DESC, fp.avg_rating DESC
        LIMIT ?
    ');
    $stmt->execute([$limit]);
    $results = $stmt->fetchAll();

    $rank = 1;
    foreach ($results as &$row) {
        $row['rank'] = $rank++;
        $row['trust_score'] = calculateTrustScore($row);
    }
    unset($row);

    return $results;
}

function getCategoryStats(int $limit = 6): array
{
    $icons = [
        'Web Development' => ['fa-code', '#4F7CFF'],
        'Mobile Development' => ['fa-mobile-alt', '#22D3EE'],
        'Design' => ['fa-paint-brush', '#8B5CF6'],
        'Writing' => ['fa-pen-fancy', '#F59E0B'],
        'Marketing' => ['fa-bullhorn', '#22C55E'],
        'Data Science' => ['fa-chart-line', '#4F7CFF'],
    ];

    $stmt = db()->prepare('
        SELECT category, COUNT(*) as count
        FROM projects WHERE status = "open"
        GROUP BY category ORDER BY count DESC LIMIT ?
    ');
    $stmt->execute([$limit]);
    $categories = $stmt->fetchAll();

    foreach ($categories as &$cat) {
        $iconData = $icons[$cat['category']] ?? ['fa-folder', '#64748B'];
        $cat['icon'] = $iconData[0];
        $cat['color'] = $iconData[1];
    }
    unset($cat);

    return $categories;
}

function globalSearch(string $query, int $limit = 8): array
{
    $query = trim($query);
    if (strlen($query) < 2) return ['projects' => [], 'freelancers' => [], 'skills' => []];

    $like = '%' . $query . '%';

    $projects = db()->prepare('SELECT id, title, category, budget FROM projects WHERE status = "open" AND (title LIKE ? OR description LIKE ?) LIMIT ?');
    $projects->execute([$like, $like, $limit]);
    $projectResults = $projects->fetchAll();

    $freelancers = db()->prepare('
        SELECT fp.user_id as id, u.first_name, u.last_name, fp.title, fp.avg_rating
        FROM freelancer_profiles fp
        JOIN users u ON fp.user_id = u.id
        WHERE u.is_active = 1 AND (u.first_name LIKE ? OR u.last_name LIKE ? OR fp.title LIKE ? OR fp.bio LIKE ?)
        LIMIT ?
    ');
    $freelancers->execute([$like, $like, $like, $like, $limit]);
    $freelancerResults = $freelancers->fetchAll();

    $skills = db()->prepare('SELECT id, name, category FROM skills WHERE name LIKE ? LIMIT ?');
    $skills->execute([$like, $limit]);
    $skillResults = $skills->fetchAll();

    return [
        'projects' => $projectResults,
        'freelancers' => $freelancerResults,
        'skills' => $skillResults,
    ];
}

// ============================================================
// CONTRACT MILESTONES
// ============================================================

function tableExists(string $table): bool
{
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];

    $stmt = db()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$table]);
    $cache[$table] = ((int) $stmt->fetchColumn()) > 0;
    return $cache[$table];
}

function getContractMilestones(int $contractId): array
{
    if (!tableExists('contract_milestones')) return [];

    $stmt = db()->prepare('SELECT * FROM contract_milestones WHERE contract_id = ? ORDER BY sort_order, id');
    $stmt->execute([$contractId]);
    return $stmt->fetchAll();
}

function getMilestoneProgress(int $contractId): int
{
    $milestones = getContractMilestones($contractId);
    if (empty($milestones)) return 0;

    $paid = count(array_filter($milestones, fn($m) => $m['status'] === 'paid'));
    return (int) round(($paid / count($milestones)) * 100);
}

// ============================================================
// ACHIEVEMENTS
// ============================================================

function checkAndAwardAchievements(int $freelancerProfileId): void
{
    $stmt = db()->prepare('SELECT * FROM freelancer_profiles WHERE id = ?');
    $stmt->execute([$freelancerProfileId]);
    $profile = $stmt->fetch();
    if (!$profile) return;

    $achievements = db()->query('SELECT * FROM achievements')->fetchAll();

    foreach ($achievements as $achievement) {
        $earned = false;
        switch ($achievement['criteria']) {
            case str_contains($achievement['criteria'], 'completed_projects'):
                $earned = $profile['completed_projects'] >= 1;
                break;
            case str_contains($achievement['criteria'], 'avg_rating'):
                $earned = $profile['avg_rating'] >= 4.5 && $profile['total_reviews'] >= 10;
                break;
        }

        if ($earned) {
            $check = db()->prepare('SELECT id FROM freelancer_achievements WHERE freelancer_id = ? AND achievement_id = ?');
            $check->execute([$freelancerProfileId, $achievement['id']]);
            if (!$check->fetch()) {
                $insert = db()->prepare('INSERT INTO freelancer_achievements (freelancer_id, achievement_id) VALUES (?, ?)');
                $insert->execute([$freelancerProfileId, $achievement['id']]);
                createNotification(
                    $profile['user_id'],
                    'achievement',
                    'Achievement Unlocked!',
                    'You earned the "' . $achievement['name'] . '" badge!',
                    baseUrl('freelancer/profile.php')
                );
            }
        }
    }
}

// ============================================================
// EMAIL (PHPMailer wrapper)
// ============================================================

function sendEmail(string $to, string $subject, string $body): bool
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        error_log("PHPMailer not installed. Email to {$to}: {$subject}");
        return false;
    }

    require_once $autoload;

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email error: ' . $e->getMessage());
        return false;
    }
}

function sendVerificationEmail(int $userId, string $email, string $name): bool
{
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = db()->prepare('INSERT INTO email_verification_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $token, $expires]);

    $verifyUrl = baseUrl('verify-email.php?token=' . $token);
    $body = "<h2>Welcome to " . APP_NAME . ", {$name}!</h2>
        <p>Please verify your email address by clicking the link below:</p>
        <p><a href=\"{$verifyUrl}\" style=\"background:#4F7CFF;color:#fff;padding:12px 24px;text-decoration:none;border-radius:6px;\">Verify Email</a></p>
        <p>This link expires in 24 hours.</p>";

    return sendEmail($email, 'Verify Your ' . APP_NAME . ' Account', $body);
}

function sendPasswordResetEmail(int $userId, string $email, string $name): bool
{
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = db()->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $token, $expires]);

    $resetUrl = baseUrl('reset-password.php?token=' . $token);
    $body = "<h2>Password Reset Request</h2>
        <p>Hi {$name},</p>
        <p>Click the link below to reset your password:</p>
        <p><a href=\"{$resetUrl}\" style=\"background:#4F7CFF;color:#fff;padding:12px 24px;text-decoration:none;border-radius:6px;\">Reset Password</a></p>
        <p>This link expires in 1 hour. If you didn't request this, ignore this email.</p>";

    return sendEmail($email, 'Reset Your ' . APP_NAME . ' Password', $body);
}

// ============================================================
// TRANSLATIONS (Multi-language support)
// ============================================================

$translations = [
    'en' => [
        'welcome' => 'Welcome',
        'dashboard' => 'Dashboard',
        'projects' => 'Projects',
        'messages' => 'Messages',
        'profile' => 'Profile',
        'logout' => 'Logout',
        'login' => 'Login',
        'register' => 'Register',
        'search' => 'Search',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'view' => 'View',
        'submit' => 'Submit',
        'loading' => 'Loading...',
        'no_results' => 'No results found',
    ],
    'es' => [
        'welcome' => 'Bienvenido',
        'dashboard' => 'Panel',
        'projects' => 'Proyectos',
        'messages' => 'Mensajes',
        'profile' => 'Perfil',
        'logout' => 'Cerrar sesión',
        'login' => 'Iniciar sesión',
        'register' => 'Registrarse',
        'search' => 'Buscar',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'delete' => 'Eliminar',
        'edit' => 'Editar',
        'view' => 'Ver',
        'submit' => 'Enviar',
        'loading' => 'Cargando...',
        'no_results' => 'No se encontraron resultados',
    ],
    'fr' => [
        'welcome' => 'Bienvenue',
        'dashboard' => 'Tableau de bord',
        'projects' => 'Projets',
        'messages' => 'Messages',
        'profile' => 'Profil',
        'logout' => 'Déconnexion',
        'login' => 'Connexion',
        'register' => 'S\'inscrire',
        'search' => 'Rechercher',
        'save' => 'Enregistrer',
        'cancel' => 'Annuler',
        'delete' => 'Supprimer',
        'edit' => 'Modifier',
        'view' => 'Voir',
        'submit' => 'Soumettre',
        'loading' => 'Chargement...',
        'no_results' => 'Aucun résultat trouvé',
    ],
    'hi' => [
        'welcome' => 'स्वागत है',
        'dashboard' => 'डैशबोर्ड',
        'projects' => 'प्रोजेक्ट्स',
        'messages' => 'संदेश',
        'profile' => 'प्रोफ़ाइल',
        'logout' => 'लॉग आउट',
        'login' => 'लॉग इन',
        'register' => 'रजिस्टर करें',
        'search' => 'खोजें',
        'save' => 'सुरक्षित करें',
        'cancel' => 'रद्द करें',
        'delete' => 'हटाएं',
        'edit' => 'संपादित करें',
        'view' => 'देखें',
        'submit' => 'जमा करें',
        'loading' => 'लोड हो रहा है...',
        'no_results' => 'कोई परिणाम नहीं मिला',
    ],
];

function __($key): string
{
    global $translations;
    $lang = $_SESSION['language'] ?? 'en';
    return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
}

// ============================================================
// SECURITY HEADERS & AUDIT LOGGING
// ============================================================

function applySecurityHeaders(): void
{
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

function logActivity(?int $userId, string $actionType, string $description): void
{
    try {
        if (!tableExists('activity_logs')) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = db()->prepare('INSERT INTO activity_logs (user_id, action_type, description, ip_address) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $actionType, $description, $ip]);
    } catch (Exception $e) {
        // Fallback silently
    }
}

function evalPasswordStrength(string $password): array
{
    $length = strlen($password);
    $score = 0;

    if ($length >= 8) $score += 25;
    if ($length >= 12) $score += 15;
    if (preg_match('/[A-Z]/', $password)) $score += 20;
    if (preg_match('/[a-z]/', $password)) $score += 20;
    if (preg_match('/[0-9]/', $password)) $score += 10;
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 10;

    $score = min(100, $score);
    $label = $score >= 80 ? 'Strong' : ($score >= 50 ? 'Medium' : 'Weak');
    $color = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');

    return [
        'score' => $score,
        'label' => $label,
        'color' => $color,
    ];
}

