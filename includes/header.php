<?php
/**
 * FreelanceHub - Header Template
 */
if (!defined('FREELANCEHUB')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/auth.php';

applySecurityHeaders();

$currentUser = currentUser();
$darkMode = $_SESSION['dark_mode'] ?? false;
$unreadNotifications = $currentUser ? getUnreadNotificationCount($currentUser['id']) : 0;
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="<?= e($_SESSION['language'] ?? 'en') ?>" data-bs-theme="<?= $darkMode ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e(APP_NAME) ?> — <?= e(APP_TAGLINE) ?>. Hire top Indian freelancers from Bangalore, Mumbai, Delhi and more. Payments in ₹.">
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body class="<?= $darkMode ? 'dark-mode' : '' ?>">

<?php if (!isset($sidebarRole) && !isset($isDashboard)): ?>
<!-- Public Navigation (Shown on main public pages) -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= baseUrl() ?>">
            <span class="brand-icon"><i class="fas fa-bolt"></i></span><?= e(APP_NAME) ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= baseUrl('projects.php') ?>"><?= __('projects') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= baseUrl('freelancers.php') ?>">Freelancers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= baseUrl('categories.php') ?>">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= baseUrl('leaderboard.php') ?>"><i class="fas fa-trophy me-1"></i>Leaderboard</a>
                </li>
            </ul>

            <ul class="navbar-nav align-items-center gap-2">
                <!-- Dark Mode Toggle -->
                <li class="nav-item">
                    <button class="btn btn-sm btn-outline-secondary rounded-circle" id="darkModeToggle" title="Toggle Dark Mode">
                        <i class="fas <?= $darkMode ? 'fa-sun' : 'fa-moon' ?>"></i>
                    </button>
                </li>

                <!-- Language Selector -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-globe"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item lang-switch" href="#" data-lang="en">English</a></li>
                        <li><a class="dropdown-item lang-switch" href="#" data-lang="es">Español</a></li>
                        <li><a class="dropdown-item lang-switch" href="#" data-lang="fr">Français</a></li>
                    </ul>
                </li>

                <?php if ($currentUser): ?>
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" id="notificationDropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                    <?= $unreadNotifications ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="width:320px;">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <div id="notificationList">
                                <?php
                                $notifications = getNotifications($currentUser['id'], 5);
                                if (empty($notifications)):
                                ?>
                                    <li><span class="dropdown-item text-muted">No notifications</span></li>
                                <?php else: ?>
                                     <?php foreach ($notifications as $notif): ?>
                                         <li>
                                             <a class="dropdown-item notification-item text-wrap <?= !$notif['is_read'] ? 'unread' : '' ?>"
                                                href="<?= e($notif['link'] ?? '#') ?>" data-id="<?= $notif['id'] ?>">
                                                 <strong class="d-block text-wrap mb-1"><?= e($notif['title']) ?></strong>
                                                 <small class="d-block text-muted text-wrap mb-1"><?= e($notif['message']) ?></small>
                                                 <small class="text-muted d-block fs-8"><?= timeAgo($notif['created_at']) ?></small>
                                             </a>
                                         </li>
                                     <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center text-primary" href="<?= baseUrl('notifications.php') ?>">View All</a></li>
                        </ul>
                    </li>

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <img src="<?= getAvatarUrl($currentUser['avatar']) ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                            <?= e($currentUser['first_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= baseUrl('dashboard.php') ?>"><i class="fas fa-tachometer-alt me-2"></i><?= __('dashboard') ?></a></li>
                            <li><a class="dropdown-item" href="<?= baseUrl(currentUserRole() . '/profile.php') ?>"><i class="fas fa-user me-2"></i><?= __('profile') ?></a></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('messages.php') ?>"><i class="fas fa-envelope me-2"></i><?= __('messages') ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= baseUrl('logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i><?= __('logout') ?></a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= baseUrl('login.php') ?>"><?= __('login') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm px-3" href="<?= baseUrl('register.php') ?>"><?= __('register') ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main class="main-content">
<?php endif; ?>
