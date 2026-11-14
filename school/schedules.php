<?php
require_once __DIR__ . '/../includes/school-layout.php';

// 1. Fetch PENDING schedule requests
$stmt = $pdo->query("
    SELECT ss.*, u.first_name, u.last_name, u.id as student_id, cp.company_name
    FROM student_schedules ss
    JOIN users u ON u.id = ss.student_id
    JOIN student_profiles sp ON sp.user_id = u.id
    JOIN company_profiles cp ON cp.user_id = sp.company_id
    WHERE ss.status = 'pending'
    ORDER BY ss.requested_at DESC
");
$pendingRequests = $stmt->fetchAll();

// 2. Fetch PROCESSED schedule requests (Approved / Rejected)
$stmt = $pdo->query("
    SELECT ss.*, u.first_name, u.last_name, u.id as student_id, cp.company_name
    FROM student_schedules ss
    JOIN users u ON u.id = ss.student_id
    JOIN student_profiles sp ON sp.user_id = u.id
    JOIN company_profiles cp ON cp.user_id = sp.company_id
    WHERE ss.status IN ('approved', 'rejected')
    ORDER BY ss.requested_at DESC
    LIMIT 20
");
$processedRequests = $stmt->fetchAll();

school_layout_top('Schedule Requests', 'Approve or reject company schedule requests');
?>

<style>
    .calendar-grid-mini { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 12px; }
    .cal-day { background: var(--gray-100); border: 1px solid var(--gray-200); border-radius: 4px; padding: 8px; text-align: center; }
    .cal-day h5 { font-size: 10px; text-transform: uppercase; color: var(--gray-500); margin-bottom: 4px; }
    .cal-time { font-size: 11px; font-weight: 700; color: var(--light-blue-dark); }
    .cal-off { font-size: 11px; color: var(--gray-400); font-style: italic; }
    
    /* Greyed out styles for processed requests */
    .processed-card { opacity: 0.75; background: var(--gray-100); }
    .processed-card .cal-day { background: var(--white); }
</style>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<!-- ========================================== -->
<!-- PENDING REQUESTS -->
<!-- ========================================== -->
<h2 style="font-family: var(--font-heading); font-size: 20px; color: var(--gray-800); margin-bottom: 16px; margin-top: 10px;">
    <i class="fas fa-clock" style="color: var(--warning);"></i> Pending Approvals
</h2>

<?php if (empty($pendingRequests)): ?>
    <div class="panel" style="margin-bottom: 32px;">
        <div class="panel-body empty-state" style="padding: 40px;">
            <div class="em-icon"><i class="fas fa-check-circle" style="color:var(--success);"></i></div>
            <h4>All Caught Up!</h4>
            <p>No pending schedule requests.</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($pendingRequests as $s): ?>
        <div class="panel" style="margin-bottom: 24px;">
            <div class="panel-header">
                <h3><i class="fas fa-user"></i> <?= e($s['first_name'] . ' ' . $s['last_name']) ?></h3>
                <div class="sub"><?= e($s['company_name']) ?> · Requested <?= time_ago($s['requested_at']) ?></div>
            </div>
            <div class="panel-body">
                <div class="calendar-grid-mini">
                    <?php 
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    foreach ($days as $day): 
                        $time = $s[$day];
                    ?>
                        <div class="cal-day">
                            <h5><?= substr(ucfirst($day), 0, 3) ?></h5>
                            <?php if ($time): ?>
                                <div class="cal-time"><?= e($time) ?></div>
                            <?php else: ?>
                                <div class="cal-off">Off</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form action="/PractiTrack/php/school/schedule-handler.php" method="POST" style="display:flex; gap:10px; margin-top: 20px;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="student_id" value="<?= $s['student_id'] ?>">
                    <button type="submit" name="decision" value="approved" class="btn" style="background:#E8F5E9; color:#1B5E20; flex:1;"><i class="fas fa-check"></i> Approve Schedule</button>
                    <button type="submit" name="decision" value="rejected" class="btn" style="background:var(--red-light); color:var(--red-dark); flex:1;"><i class="fas fa-times"></i> Reject Request</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- ========================================== -->
<!-- PROCESSED REQUESTS (HISTORY) -->
<!-- ========================================== -->
<h2 style="font-family: var(--font-heading); font-size: 20px; color: var(--gray-800); margin-bottom: 16px; margin-top: 40px;">
    <i class="fas fa-history" style="color: var(--gray-400);"></i> Processed Requests
</h2>

<?php if (empty($processedRequests)): ?>
    <div class="panel">
        <div class="panel-body empty-state" style="padding: 30px;">
            <p>No processed schedule requests yet.</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($processedRequests as $s): ?>
        <div class="panel processed-card" style="margin-bottom: 24px;">
            <div class="panel-header" style="background: transparent;">
                <h3><i class="fas fa-user"></i> <?= e($s['first_name'] . ' ' . $s['last_name']) ?></h3>
                <div class="sub"><?= e($s['company_name']) ?></div>
            </div>
            <div class="panel-body">
                <div class="calendar-grid-mini">
                    <?php 
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    foreach ($days as $day): 
                        $time = $s[$day];
                    ?>
                        <div class="cal-day">
                            <h5><?= substr(ucfirst($day), 0, 3) ?></h5>
                            <?php if ($time): ?>
                                <div class="cal-time"><?= e($time) ?></div>
                            <?php else: ?>
                                <div class="cal-off">Off</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Status Badge -->
                <div style="margin-top: 16px; text-align: right;">
                    <?php if ($s['status'] === 'approved'): ?>
                        <span class="badge approved" style="font-size: 13px; padding: 6px 12px;"><i class="fas fa-check-circle"></i> Approved</span>
                    <?php else: ?>
                        <span class="badge rejected" style="font-size: 13px; padding: 6px 12px;"><i class="fas fa-times-circle"></i> Rejected</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php school_layout_bottom(); ?>