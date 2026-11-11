<?php
require_once __DIR__ . '/../includes/admin-layout.php';

$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, sp.student_no, sp.course, cp.company_name, sp.company_id
    FROM users u
    JOIN student_profiles sp ON sp.user_id = u.id
    JOIN company_profiles cp ON cp.user_id = sp.company_id
    WHERE sp.application_status = 'pending'
    ORDER BY u.last_name ASC
");
$applications = $stmt->fetchAll();

admin_layout_top('OJT Applications', 'Review student applications for company assignment');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3>Pending Applications</h3>
        <div class="sub"><?= count($applications) ?> student(s) waiting</div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>USN / Course</th>
                        <th>Applied Company</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr><td colspan="4" class="empty">No pending applications.</td></tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><strong><?= e($app['first_name'] . ' ' . $app['last_name']) ?></strong></td>
                                <td><?= e($app['student_no']) ?> <br><small style="color:var(--gray-400);"><?= e($app['course']) ?></small></td>
                                <td><span style="font-weight:600; color:#0F766E;"><?= e($app['company_name']) ?></span></td>
                                <td>
                                    <form action="/PractiTrack/php/admin/application-handler.php" method="POST" style="display:flex; gap:6px;">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="student_id" value="<?= $app['id'] ?>">
                                        <input type="hidden" name="company_id" value="<?= $app['company_id'] ?>">
                                        <button type="submit" name="decision" value="accepted" class="btn btn-sm" style="background:#E8F5E9; color:#1B5E20;">Approve</button>
                                        <button type="submit" name="decision" value="rejected" class="btn btn-sm" style="background:var(--red-light); color:var(--red-dark);">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php admin_layout_bottom(); ?>