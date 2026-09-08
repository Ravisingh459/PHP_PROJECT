<?php
/**
 * FreelanceHub - Dashboard Sidebar Include
 * Usage: include with $sidebarRole set to 'client', 'freelancer', or 'admin'
 */
$role = $sidebarRole ?? currentUserRole();
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$currentUser = currentUser();
$darkMode = $_SESSION['dark_mode'] ?? false;
$unreadNotifications = $currentUser ? getUnreadNotificationCount($currentUser['id']) : 0;

$menus = [
    'client' => [
        ['url' => 'client/index.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
        ['url' => 'client/post-project.php', 'icon' => 'fa-plus-circle', 'label' => 'Post Project'],
        ['url' => 'client/projects.php', 'icon' => 'fa-folder-open', 'label' => 'My Projects'],
        ['url' => 'client/contracts.php', 'icon' => 'fa-file-contract', 'label' => 'Contracts'],
        ['url' => 'client/payments.php', 'icon' => 'fa-credit-card', 'label' => 'Payments'],
        ['url' => 'messages.php', 'icon' => 'fa-envelope', 'label' => 'Messages'],
        ['url' => 'client/analytics.php', 'icon' => 'fa-chart-bar', 'label' => 'Analytics'],
        ['url' => 'client/profile.php', 'icon' => 'fa-user', 'label' => 'Profile'],
    ],
    'freelancer' => [
        ['url' => 'freelancer/index.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
        ['url' => 'freelancer/projects.php', 'icon' => 'fa-search', 'label' => 'Find Projects'],
        ['url' => 'freelancer/bids.php', 'icon' => 'fa-gavel', 'label' => 'My Bids'],
        ['url' => 'freelancer/contracts.php', 'icon' => 'fa-file-contract', 'label' => 'Contracts'],
        ['url' => 'freelancer/portfolio.php', 'icon' => 'fa-images', 'label' => 'Portfolio'],
        ['url' => 'freelancer/earnings.php', 'icon' => 'fa-indian-rupee-sign', 'label' => 'Earnings'],
        ['url' => 'messages.php', 'icon' => 'fa-envelope', 'label' => 'Messages'],
        ['url' => 'freelancer/reviews.php', 'icon' => 'fa-star', 'label' => 'Reviews'],
        ['url' => 'freelancer/skill-tests.php', 'icon' => 'fa-graduation-cap', 'label' => 'Skill Tests'],
        ['url' => 'freelancer/profile.php', 'icon' => 'fa-user', 'label' => 'Profile'],
    ],
    'admin' => [
        ['url' => 'admin/index.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
        ['url' => 'admin/users.php', 'icon' => 'fa-users', 'label' => 'Users'],
        ['url' => 'admin/projects.php', 'icon' => 'fa-folder-open', 'label' => 'Projects'],
        ['url' => 'messages.php', 'icon' => 'fa-envelope', 'label' => 'Messages'],
        ['url' => 'admin/reviews.php', 'icon' => 'fa-star', 'label' => 'Reviews'],
        ['url' => 'admin/reports.php', 'icon' => 'fa-flag', 'label' => 'Reports'],
        ['url' => 'admin/disputes.php', 'icon' => 'fa-gavel', 'label' => 'Disputes'],
        ['url' => 'admin/analytics.php', 'icon' => 'fa-chart-line', 'label' => 'Analytics'],
        ['url' => 'admin/activity-logs.php', 'icon' => 'fa-shield-halved', 'label' => 'Activity Logs'],
        ['url' => 'admin/profile.php', 'icon' => 'fa-user-shield', 'label' => 'Profile'],
        ['url' => 'admin/settings.php', 'icon' => 'fa-cog', 'label' => 'Settings'],
    ],
];

$menuItems = $menus[$role] ?? [];
$roleTitle = ucfirst($role) . ' Navigation';
?>

<div class="dashboard-wrapper">
    <!-- Sidebar (Full Height Left Sidebar) -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header p-3 border-bottom d-flex align-items-center justify-content-between">
            <a href="<?= baseUrl() ?>" class="text-decoration-none d-flex align-items-center gap-2">
                <span class="brand-icon"><i class="fas fa-bolt"></i></span>
                <h5 class="fw-bold text-primary mb-0"><?= e(APP_NAME) ?></h5>
            </a>
            <button class="btn-close text-reset d-lg-none" id="sidebarCloseBtn" aria-label="Close"></button>
        </div>
        
        <nav class="sidebar-nav">
            <div class="px-3 pt-2 pb-1">
                <small class="text-uppercase text-muted fw-bold tracking-wider fs-7"><?= e($roleTitle) ?></small>
            </div>
            <ul class="nav flex-column">
                <?php foreach ($menuItems as $item):
                    $itemPath = trim($item['url'], '/');
                    $isExactMatch = str_ends_with($currentUri, $itemPath);
                    $isScriptMatch = ($currentScript === basename($item['url']));
                    $isActive = ($isExactMatch || $isScriptMatch) ? 'active' : '';
                ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive ?>" href="<?= baseUrl($item['url']) ?>">
                            <i class="fas <?= $item['icon'] ?> me-2"></i><?= e($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <div class="sidebar-footer p-3 border-top mt-auto">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <img src="<?= getAvatarUrl($currentUser['avatar'] ?? '') ?>" alt="User" class="rounded-circle flex-shrink-0" width="32" height="32">
                    <div class="text-truncate">
                        <div class="fw-bold fs-7 text-truncate"><?= e($currentUser['first_name'] ?? 'User') ?></div>
                        <div class="text-muted fs-8 text-capitalize text-truncate"><?= e($role) ?></div>
                    </div>
                </div>
            </div>
            <a href="<?= baseUrl('logout.php') ?>" class="btn btn-outline-danger btn-sm w-100">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </aside>

    <!-- Main Dashboard Content Area -->
    <div class="dashboard-content">
        <!-- Integrated Top Header Bar -->
        <div class="dashboard-topbar d-flex justify-content-between align-items-center p-3 shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
                    <i class="fas fa-bars me-1"></i>Menu
                </button>
                <ul class="nav d-none d-md-flex align-items-center gap-1">
                    <li class="nav-item">
                        <a class="nav-link text-muted py-0 px-2 <?= $currentScript === 'projects.php' ? 'fw-bold text-primary' : '' ?>" href="<?= baseUrl('projects.php') ?>"><?= __('projects') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-muted py-0 px-2 <?= $currentScript === 'freelancers.php' ? 'fw-bold text-primary' : '' ?>" href="<?= baseUrl('freelancers.php') ?>">Freelancers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-muted py-0 px-2 <?= $currentScript === 'categories.php' ? 'fw-bold text-primary' : '' ?>" href="<?= baseUrl('categories.php') ?>">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-muted py-0 px-2 <?= $currentScript === 'leaderboard.php' ? 'fw-bold text-primary' : '' ?>" href="<?= baseUrl('leaderboard.php') ?>"><i class="fas fa-trophy text-warning me-1"></i>Leaderboard</a>
                    </li>
                </ul>
            </div>
            <div class="ms-auto d-flex align-items-center gap-3">
                <!-- Settings Gear Shortcut -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="dropdown" title="Quick Settings">
                        <i class="fas fa-cog"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm fs-7">
                        <?php if ($role === 'admin'): ?>
                            <li><h6 class="dropdown-header">System Settings</h6></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('admin/settings.php') ?>"><i class="fas fa-sliders-h me-2 text-primary"></i>Site Settings</a></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('admin/profile.php') ?>"><i class="fas fa-user-shield me-2 text-info"></i>Admin Credentials</a></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('admin/analytics.php') ?>"><i class="fas fa-chart-line me-2 text-success"></i>Platform Analytics</a></li>
                        <?php elseif ($role === 'freelancer'): ?>
                            <li><h6 class="dropdown-header">Freelancer Settings</h6></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('freelancer/settings.php') ?>"><i class="fas fa-cog me-2 text-primary"></i>Account Settings</a></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('freelancer/profile.php') ?>"><i class="fas fa-user me-2 text-info"></i>Professional Profile</a></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('freelancer/earnings.php') ?>"><i class="fas fa-wallet me-2 text-success"></i>Earnings & Payouts</a></li>
                        <?php else: ?>
                            <li><h6 class="dropdown-header">Client Settings</h6></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('client/profile.php') ?>"><i class="fas fa-user me-2 text-primary"></i>Account & Profile</a></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('client/projects.php') ?>"><i class="fas fa-folder-open me-2 text-info"></i>My Projects</a></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('client/payments.php') ?>"><i class="fas fa-credit-card me-2 text-success"></i>Payments</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Dark Mode Toggle -->
                <button class="btn btn-sm btn-outline-secondary rounded-circle" id="darkModeToggle" title="Toggle Dark Mode">
                    <i class="fas <?= $darkMode ? 'fa-sun' : 'fa-moon' ?>"></i>
                </button>

                <!-- Language Switcher -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="dropdown" title="Change Language">
                        <i class="fas fa-globe"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end fs-7">
                        <li><a class="dropdown-item lang-switch" href="#" data-lang="en"><i class="fas fa-check text-success me-2 <?= ($_SESSION['language'] ?? 'en') === 'en' ? '' : 'invisible' ?>"></i>English</a></li>
                        <li><a class="dropdown-item lang-switch" href="#" data-lang="hi"><i class="fas fa-check text-success me-2 <?= ($_SESSION['language'] ?? '') === 'hi' ? '' : 'invisible' ?>"></i>हिन्दी (Hindi)</a></li>
                        <li><a class="dropdown-item lang-switch" href="#" data-lang="es"><i class="fas fa-check text-success me-2 <?= ($_SESSION['language'] ?? '') === 'es' ? '' : 'invisible' ?>"></i>Español</a></li>
                        <li><a class="dropdown-item lang-switch" href="#" data-lang="fr"><i class="fas fa-check text-success me-2 <?= ($_SESSION['language'] ?? '') === 'fr' ? '' : 'invisible' ?>"></i>Français</a></li>
                    </ul>
                </div>

                <!-- Notifications Dropdown -->
                <div class="dropdown">
                    <a class="text-secondary position-relative text-decoration-none p-1" href="#" data-bs-toggle="dropdown" id="notificationDropdown" title="Notifications">
                        <i class="fas fa-bell fs-5"></i>
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                <?= $unreadNotifications ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown shadow-sm" style="width:320px;">
                        <li><h6 class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notifications</span>
                            <span class="badge bg-primary rounded-pill"><?= $unreadNotifications ?> new</span>
                        </h6></li>
                        <div id="notificationList">
                            <?php
                            $notifications = getNotifications($currentUser['id'] ?? 0, 5);
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
                        <li><a class="dropdown-item text-center text-primary fw-semibold" href="<?= baseUrl('notifications.php') ?>">View All Notifications</a></li>
                    </ul>
                </div>

                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <img src="<?= getAvatarUrl($currentUser['avatar'] ?? '') ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">
                        <span class="fw-semibold fs-7 d-none d-sm-inline"><?= e($currentUser['first_name'] ?? 'User') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <div class="dropdown-header d-flex align-items-center gap-2">
                                <img src="<?= getAvatarUrl($currentUser['avatar'] ?? '') ?>" class="rounded-circle" width="36" height="36">
                                <div>
                                    <div class="fw-bold text-dark dark-mode-text"><?= e(($currentUser['first_name'] ?? 'User') . ' ' . ($currentUser['last_name'] ?? '')) ?></div>
                                    <small class="text-muted text-capitalize"><?= e($role) ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item fs-7" href="<?= baseUrl('dashboard.php') ?>"><i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard</a></li>
                        <li><a class="dropdown-item fs-7" href="<?= baseUrl(currentUserRole() . '/profile.php') ?>"><i class="fas fa-user me-2 text-info"></i>Profile</a></li>
                        <li><a class="dropdown-item fs-7" href="<?= baseUrl('messages.php') ?>"><i class="fas fa-envelope me-2 text-warning"></i>Messages</a></li>
                        <?php
                        $userSettingsUrl = match($role) {
                            'admin' => baseUrl('admin/settings.php'),
                            'freelancer' => baseUrl('freelancer/settings.php'),
                            'client' => baseUrl('client/profile.php'),
                            default => baseUrl('dashboard.php')
                        };
                        ?>
                        <li><a class="dropdown-item fs-7" href="<?= $userSettingsUrl ?>"><i class="fas fa-cog me-2 text-secondary"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item fs-7 text-danger" href="<?= baseUrl('logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="dashboard-body p-4">
