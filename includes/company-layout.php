<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/functions.php';
require_login('company');

$userName = $_SESSION['name'] ?? 'Company';
$initials = strtoupper(substr($userName, 0, 1));
$currentPage = basename($_SERVER['PHP_SELF']);

function company_layout_top($pageTitle = 'Dashboard', $pageSubtitle = '')
{
    global $userName, $initials, $currentPage;
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($pageTitle) ?> — PractiTrack Company</title>
        <link rel="stylesheet" href="/PractiTrack/assets/css/global.css">
        <link rel="stylesheet" href="/PractiTrack/assets/css/student.css"> <!-- Reuse student layout styles -->
        <link rel="stylesheet" href="/PractiTrack/assets/css/company.css">

        <style>
            .sidebar {
                background: linear-gradient(180deg, #0F766E 0%, #115E59 100%);
            }

            /* Teal */
            .sidebar-brand .logo-mark {
                background: var(--white);
                color: #0F766E;
            }

            .sidebar-menu a.active::before {
                background: var(--white);
            }

            .sidebar-menu a.active {
                color: #0F766E;
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
                <div class="sidebar-role" style="color:#5EEAD4;">Company Portal</div>

                <nav class="sidebar-menu">
                    <a href="/PractiTrack/company/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                        <span class="icon">🏠</span> Dashboard
                    </a>
                    <a href="/PractiTrack/company/students.php" class="<?= $currentPage === 'students.php' || $currentPage === 'student-view.php' ? 'active' : '' ?>">
                        <span class="icon">🎓</span> Students
                    </a>
                    <a href="/PractiTrack/logout.php">
                        <span class="icon">🚪</span> Logout
                    </a>
                </nav>

                <div class="sidebar-footer">
                    &copy; <?= date('Y') ?> PractiTrack
                </div>
            </aside>

            <div class="main">
                <header class="topbar">
                    <div class="topbar-left">
                        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>
                        <div>
                            <h2><?= e($pageTitle) ?></h2>
                            <?php if ($pageSubtitle): ?>
                                <p><?= e($pageSubtitle) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="topbar-right">
                        <div class="user-chip">
                            <div class="avatar" style="background:linear-gradient(135deg, #0F766E, var(--light-blue));"><?= e($initials) ?></div>
                            <div class="info">
                                <div class="name"><?= e($userName) ?></div>
                                <div class="role">Company</div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="content">
                <?php
            }

            function company_layout_bottom()
            {
                ?>
                </main>
            </div>
        </div>
        <script src="/PractiTrack/assets/js/student.js"></script> <!-- Reuse student.js for menu toggle -->
    </body>

    </html>
<?php
            }
