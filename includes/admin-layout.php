<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/functions.php';
require_login('admin');

$userName = $_SESSION['name'] ?? 'Administrator';
$initials = strtoupper(substr($userName, 0, 1));
$currentPage = basename($_SERVER['PHP_SELF']);

function admin_layout_top($pageTitle = 'Dashboard', $pageSubtitle = '')
{
    global $userName, $initials, $currentPage;
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
            /* Dark Slate Theme for Admin */
            .sidebar {
                background: linear-gradient(180deg, #1E293B 0%, #0F172A 100%);
            }

            .sidebar-brand .logo-mark {
                background: var(--red);
                color: var(--white);
            }

            .sidebar-menu a.active::before {
                background: var(--red);
            }

            .sidebar-menu a.active {
                color: #1E293B;
            }

            .user-chip .avatar {
                background: #1E293B !important;
            }
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

                <nav class="sidebar-menu">
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
                    <a href="/PractiTrack/logout.php">
                        <span class="icon"><i class="fas fa-sign-out-alt"></i></span> Logout
                    </a>
                </nav>
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

            function admin_layout_bottom()
            {
                ?>
                </main>
            </div>
        </div>
        <script src="/PractiTrack/assets/js/student.js"></script>
    </body>

    </html>
<?php
            }