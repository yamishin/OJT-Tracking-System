<?php
// includes/student-layout.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/functions.php';
require_login('student');

$userName = $_SESSION['name'] ?? 'Student';
$userEmail = $_SESSION['email'] ?? '';
$initials = strtoupper(substr($userName, 0, 1));

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unreadCount = (int)$stmt->fetchColumn();

$currentPage = basename($_SERVER['PHP_SELF']);

function student_layout_top($pageTitle = 'Dashboard', $pageSubtitle = '')
{
    global $userName, $userEmail, $initials, $unreadCount, $currentPage;
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($pageTitle) ?> — PractiTrack</title>
        <link rel="stylesheet" href="/PractiTrack/assets/css/global.css">
        <link rel="stylesheet" href="/PractiTrack/assets/css/student.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>

    <body>

        <div class="app-shell">
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-brand">
                    <div class="logo-mark">PT</div>
                    <h1>Practi<span>Track</span></h1>
                </div>
                <div class="sidebar-role">Student Portal</div>

                <nav class="sidebar-menu">
                    <a href="/PractiTrack/student/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-home"></i></span> Dashboard
                    </a>
                    <a href="/PractiTrack/student/documents.php" class="<?= $currentPage === 'documents.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-folder-open"></i></span> Documents
                    </a>
                    <a href="/PractiTrack/student/attendance.php" class="<?= $currentPage === 'attendance.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-calendar-check"></i></span> Attendance
                    </a>
                    <a href="/PractiTrack/student/weekly-report.php" class="<?= $currentPage === 'weekly-report.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-file-alt"></i></span> Reports
                    </a>
                    <a href="/PractiTrack/student/progress.php" class="<?= $currentPage === 'progress.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-chart-line"></i></span> Progress
                    </a>
                    <a href="/PractiTrack/student/notification.php" class="<?= $currentPage === 'notification.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-bell"></i></span> Notifications
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge-count"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/PractiTrack/student/profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                        <span class="icon"><i class="fas fa-user-circle"></i></span> Profile
                    </a>
                    <a href="/PractiTrack/logout.php">
                        <span class="icon"><i class="fas fa-sign-out-alt"></i></span> Logout
                    </a>
                </nav>

                <div class="sidebar-footer">
                    &copy; <?= date('Y') ?> PractiTrack
                </div>
            </aside>

            <div class="main">
                <header class="topbar">
                    <div class="topbar-left">
                        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <h2><?= e($pageTitle) ?></h2>
                            <?php if ($pageSubtitle): ?>
                                <p><?= e($pageSubtitle) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="topbar-right">
                        <a href="/PractiTrack/student/notification.php" class="topbar-icon" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadCount > 0): ?><span class="dot"></span><?php endif; ?>
                        </a>
                        <div class="user-chip">
                            <div class="avatar"><?= e($initials) ?></div>
                            <div class="info">
                                <div class="name"><?= e($userName) ?></div>
                                <div class="role">Student</div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="content">
                <?php
            }

            function student_layout_bottom()
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
