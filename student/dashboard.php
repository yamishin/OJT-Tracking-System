<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT company_id, application_status FROM student_profiles WHERE user_id = ?");
$stmt->execute([$studentId]);
$appStatus = $stmt->fetch();
$isAccepted = ($appStatus && $appStatus['application_status'] === 'accepted');

// 1. Fetch student profile for required hours
$stmt = $pdo->prepare("SELECT required_hours FROM student_profiles WHERE user_id = ?");
$stmt->execute([$studentId]);
$profile = $stmt->fetch();
$requiredHours = (int)($profile['required_hours'] ?? 486);

// 2. Total Hours Rendered
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) FROM attendance WHERE student_id = ?");
$stmt->execute([$studentId]);
$totalHours = (float)$stmt->fetchColumn();

// 3. Days Present (Count of all attendance logs)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ?");
$stmt->execute([$studentId]);
$daysPresent = (int)$stmt->fetchColumn();

// 4. Documents Stats (Total and Approved)
$stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(status = 'approved') AS approved FROM documents WHERE student_id = ?");
$stmt->execute([$studentId]);
$docs = $stmt->fetch();
$docsTotal = (int)($docs['total'] ?? 0);
$docsApproved = (int)($docs['approved'] ?? 0);

// 5. Weekly Reports Stats (Total and Approved)
$stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(status = 'approved') AS approved FROM weekly_reports WHERE student_id = ?");
$stmt->execute([$studentId]);
$reports = $stmt->fetch();
$reportsTotal = (int)($reports['total'] ?? 0);
$reportsApproved = (int)($reports['approved'] ?? 0);

// 6. Compute Overall Progress
$hoursProgress = $requiredHours > 0 ? min(100, round(($totalHours / $requiredHours) * 100, 1)) : 0;
$remainingHours = max(0, $requiredHours - $totalHours);

// 7. Recent Activity
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY log_date DESC LIMIT 3");
$stmt->execute([$studentId]);
$recentAttendance = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$studentId]);
$recentNotifs = $stmt->fetchAll();

student_layout_top('Dashboard', 'Welcome back! Here\'s your OJT overview.');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if (!$isAccepted): ?>
    <div class="panel" style="text-align: center; padding: 60px 20px; border-top: 5px solid var(--red);">
        <div style="font-size: 50px; color: var(--red); margin-bottom: 20px;"><i class="fas fa-exclamation-circle"></i></div>
        <h2 style="font-size: 24px; color: var(--gray-800); margin-bottom: 10px;">Action Required</h2>
        <p style="color: var(--gray-600); margin-bottom: 24px;">You cannot access your dashboard yet. You must first apply to a partner company and wait for Admin approval.</p>
        <a href="/PractiTrack/student/companies.php" class="btn btn-primary">Find a Company Now</a>
    </div>
<?php else: ?>
    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-label">Hours Rendered</div>
            <div class="stat-value"><?= number_format($totalHours, 1) ?></div>
            <div class="stat-sub">of <?= $requiredHours ?> required</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-label">Days Present</div>
            <div class="stat-value"><?= $daysPresent ?></div>
            <div class="stat-sub">attendance logs</div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
            <div class="stat-label">Documents</div>
            <div class="stat-value"><?= $docsApproved ?> / <?= $docsTotal ?></div>
            <div class="stat-sub">approved / total</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><i class="fas fa-file-signature"></i></div>
            <div class="stat-label">Weekly Reports</div>
            <div class="stat-value"><?= $reportsApproved ?> / <?= $reportsTotal ?></div>
            <div class="stat-sub">approved / total</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- OVERALL PROGRESS -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h3>Overall Progress</h3>
                    <div class="sub">Your OJT completion status</div>
                </div>
                <span class="badge submitted"><?= $hoursProgress ?>%</span>
            </div>
            <div class="panel-body">
                <div class="progress-block">
                    <div class="pb-head">
                        <span>Hours Progress</span>
                        <span><?= number_format($totalHours, 1) ?> / <?= $requiredHours ?> hrs</span>
                    </div>
                    <div class="pb-bar">
                        <div class="pb-fill" data-width="<?= $hoursProgress ?>%"></div>
                    </div>
                </div>

                <div class="alert alert-info" style="margin-top:20px;margin-bottom:0;">
                    <i class="fas fa-info-circle" style="font-size:18px;"></i>
                    <span>
                        <strong>Remaining:</strong> <?= number_format($remainingHours, 1) ?> hours left to complete your OJT.
                    </span>
                </div>
            </div>
        </div>

        <!-- RECENT NOTIFICATIONS -->
        <div class="panel">
            <div class="panel-header">
                <h3>Recent Notifications</h3>
                <a href="/PractiTrack/student/notification.php" class="btn btn-sm btn-ghost">View All</a>
            </div>
            <div class="panel-body no-pad">
                <?php if (empty($recentNotifs)): ?>
                    <div class="empty-state">
                        <div class="em-icon"><i class="fas fa-bell-slash" style="color:var(--gray-300);"></i></div>
                        <h4>No notifications</h4>
                        <p>You're all caught up!</p>
                    </div>
                <?php else: ?>
                    <ul class="notif-list">
                        <?php foreach ($recentNotifs as $n): ?>
                            <li class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                                <div class="n-icon"><i class="fas fa-bell"></i></div>
                                <div class="n-body">
                                    <div class="n-title"><?= e($n['title']) ?></div>
                                    <div class="n-message"><?= e($n['message']) ?></div>
                                    <div class="n-time"><?= time_ago($n['created_at']) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RECENT ATTENDANCE -->
    <div class="panel">
        <div class="panel-header">
            <h3>Recent Attendance</h3>
            <a href="/PractiTrack/student/attendance.php" class="btn btn-sm btn-ghost">View All</a>
        </div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentAttendance)): ?>
                            <tr>
                                <td colspan="5" class="empty">No attendance records yet. Start logging your hours!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentAttendance as $a): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($a['log_date'])) ?></td>
                                    <td><?= $a['time_in'] ? date('h:i A', strtotime($a['time_in'])) : '—' ?></td>
                                    <td><?= $a['time_out'] ? date('h:i A', strtotime($a['time_out'])) : '—' ?></td>
                                    <td><?= number_format((float)$a['total_hours'], 2) ?></td>
                                    <td><span class="badge <?= strtolower(str_replace(' ', '', $a['status'])) ?>"><?= e($a['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php student_layout_bottom(); ?>

<?php endif; ?>