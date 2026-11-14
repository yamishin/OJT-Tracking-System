<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/functions.php';
require_login('admin');

$userName = $_SESSION['name'] ?? 'Administrator';
$initials = strtoupper(substr($userName, 0, 1));
$currentPage = basename($_SERVER['PHP_SELF']);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unreadCount = (int)$stmt->fetchColumn();

function admin_layout_top($pageTitle = 'Dashboard', $pageSubtitle = '')
{
    global $userName, $initials, $currentPage, $unreadCount;
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($pageTitle) ?> — PractiTrack Admin</title>
        <link rel="stylesheet" href="/PractiTrack/assets/css/global.css">
        <link rel="stylesheet" href="/PractiTrack/assets/css/student.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            .sidebar { background: linear-gradient(180deg, #1E293B 0%, #0F172A 100%); }
            .sidebar-brand .logo-mark { background: var(--red); color: var(--white); }
            .sidebar-menu a.active::before { background: var(--red); }
            .sidebar-menu a.active { color: #1E293B; }
            .user-chip .avatar { background: #1E293B !important; }
            .btn-logout {
                display: flex; align-items: center; gap: 12px;
                padding: 12px 14px; border-radius: var(--radius-sm);
                font-size: 14px; font-weight: 500; color: rgba(255,255,255,0.85);
                transition: all 0.2s ease;
            }
            .btn-logout:hover { background: rgba(229, 57, 53, 0.2); color: #FFCDD2; }
        </style>
    </head>
    <body>
        <div class="app-shell">
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-brand">
                    <div class="logo-mark">PT</div>
                    <h1>Practi<span>Track</span></h1>
                </div>
                <div class="sidebar-role" style="color:#94A3B8;">Admin Control Panel</div>

                <!-- MAIN NAVIGATION -->
                <nav class="sidebar-menu" style="flex: 1; overflow-y: auto;">
                    <a href="/PractiTrack/admin/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-home"></i></span> Dashboard
                    </a>
                    <a href="/PractiTrack/admin/applications.php" class="<?= $currentPage === 'applications.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-clipboard-check"></i></span> OJT Applications
                    </a>
                    <a href="/PractiTrack/admin/manage-users.php" class="<?= $currentPage === 'manage-users.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-users-cog"></i></span> Manage Accounts
                    </a>
                    <a href="/PractiTrack/admin/students.php" class="<?= $currentPage === 'students.php' || $currentPage === 'student-view.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-user-graduate"></i></span> Students
                    </a>
                    <a href="/PractiTrack/admin/companies.php" class="<?= $currentPage === 'companies.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-building"></i></span> Company Partners
                    </a>
                    <a href="/PractiTrack/admin/courses.php" class="<?= $currentPage === 'courses.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-book"></i></span> Manage Courses
                    </a>
                    <a href="/PractiTrack/admin/notification.php" class="<?= $currentPage === 'notification.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-bell"></i></span> Notifications
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge-count"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                </nav>

                <!-- SEPARATED LOGOUT -->
                <div style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <a href="/PractiTrack/logout.php" class="btn-logout">
                        <span class="icon"><i class="fas fa-sign-out-alt"></i></span> Logout
                    </a>
                </div>

                <div class="sidebar-footer">&copy; <?= date('Y') ?> PractiTrack</div>
            </aside>

            <div class="main">
                <header class="topbar">
                    <div class="topbar-left">
                        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <h2><?= e($pageTitle) ?></h2>
                            <?php if ($pageSubtitle): ?><p><?= e($pageSubtitle) ?></p><?php endif; ?>
                        </div>
                    </div>
                    <div class="topbar-right">
                        <a href="/PractiTrack/admin/notification.php" class="topbar-icon" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadCount > 0): ?><span class="dot"></span><?php endif; ?>
                        </a>
                        <div class="user-chip">
                            <div class="avatar" style="background:#1E293B;"><?= e($initials) ?></div>
                            <div class="info">
                                <div class="name"><?= e($userName) ?></div>
                                <div class="role">Admin</div>
                            </div>
                        </div>
                    </div>
                </header>
                <main class="content">
                <?php
            }

            function admin_layout_bottom() {
                ?>
                </main>
            </div>
        </div>
        <script src="/PractiTrack/assets/js/student.js"></script>
    </body>
    </html>
    <?php
}