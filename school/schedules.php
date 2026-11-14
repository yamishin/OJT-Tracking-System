<?php
require_once __DIR__ . '/../includes/school-layout.php';

// Fetch pending schedule requests grouped by student
$stmt = $pdo->query("
    SELECT u.first_name, u.last_name, u.id as student_id, cp.company_name
    FROM users u
    JOIN student_profiles sp ON sp.user_id = u.id
    JOIN company_profiles cp ON cp.user_id = sp.company_id
    WHERE u.id IN (SELECT DISTINCT student_id FROM student_schedules WHERE status = 'pending')
");
$studentsWithRequests = $stmt->fetchAll();

school_layout_top('Schedule Requests', 'Approve or reject company schedule requests');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<?php if (empty($studentsWithRequests)): ?>
    <div class="panel"><div class="panel-body empty-state">No pending schedule requests.</div></div>
<?php else: ?>
    <?php foreach ($studentsWithRequests as $s): 
        // Fetch their requested schedules
        $stmt2 = $pdo->prepare("SELECT * FROM student_schedules WHERE student_id = ? AND status = 'pending' ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
        $stmt2->execute([$s['student_id']]);
        $requests = $stmt2->fetchAll();
    ?>
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fas fa-user"></i> <?= e($s['first_name'] . ' ' . $s['last_name']) ?></h3>
                <div class="sub"><?= e($s['company_name']) ?></div>
            </div>
            <div class="panel-body">
                <table class="data-table" style="margin-bottom: 16px;">
                    <thead><tr><th>Day</th><th>Time Start</th><th>Time End</th></tr></thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><strong><?= e($r['day_of_week']) ?></strong></td>
                                <td><?= date('h:i A', strtotime($r['time_start'])) ?></td>
                                <td><?= date('h:i A', strtotime($r['time_end'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <form action="/PractiTrack/php/school/schedule-handler.php" method="POST" style="display:flex; gap:10px;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="student_id" value="<?= $s['student_id'] ?>">
                    <button type="submit" name="decision" value="approved" class="btn" style="background:#E8F5E9; color:#1B5E20; flex:1;"><i class="fas fa-check"></i> Approve Schedule</button>
                    <button type="submit" name="decision" value="rejected" class="btn" style="background:var(--red-light); color:var(--red-dark); flex:1;"><i class="fas fa-times"></i> Reject</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php school_layout_bottom(); ?>