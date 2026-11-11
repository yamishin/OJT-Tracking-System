<?php
require_once __DIR__ . '/../includes/school-layout.php';

$userId = $_SESSION['user_id'];

// Mark all as read when visiting this page
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$userId]);
$notifs = $stmt->fetchAll();

school_layout_top('Notifications', 'Your recent alerts and updates');
?>

<div class="panel">
    <div class="panel-header">
        <h3>All Notifications</h3>
        <div class="sub"><?= count($notifs) ?> total</div>
    </div>
    <div class="panel-body no-pad">
        <?php if (empty($notifs)): ?>
            <div class="empty-state">
                <div class="em-icon"><i class="fas fa-bell-slash" style="color:var(--gray-300);"></i></div>
                <h4>No notifications yet</h4>
                <p>We'll notify you when something important happens.</p>
            </div>
        <?php else: ?>
            <ul class="notif-list">
                <?php foreach ($notifs as $n): ?>
                    <li class="notif-item">
                        <div class="n-icon"><i class="fas fa-bell"></i></div>
                        <div class="n-body">
                            <div class="n-title"><?= e($n['title']) ?></div>
                            <div class="n-message"><?= e($n['message']) ?></div>
                            <div class="n-time"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?> · <?= time_ago($n['created_at']) ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php school_layout_bottom(); ?>