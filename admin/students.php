<?php
require_once __DIR__ . '/../includes/admin-layout.php';

// Fetch all students along with their company name (if assigned)
$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.is_active, sp.student_no, sp.course, sp.required_hours,
    cp.company_name,
    (SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = u.id) as rendered_hours
    FROM users u
    JOIN student_profiles sp ON sp.user_id = u.id
    LEFT JOIN company_profiles cp ON cp.user_id = sp.company_id
    WHERE u.role = 'student'
    ORDER BY u.last_name ASC
");
$students = $stmt->fetchAll();

admin_layout_top('Manage Students', 'View all student accounts and their OJT progress');
?>

<div class="panel">
    <div class="panel-header">
        <h3>All Registered Students</h3>
        <div class="sub"><?= count($students) ?> student(s)</div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name & USN</th>
                        <th>Course</th>
                        <th>Assigned Company</th>
                        <th>Progress</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="5" class="empty">No students registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): 
                            $progress = $s['required_hours'] > 0 ? min(100, round(($s['rendered_hours'] / $s['required_hours']) * 100)) : 0;
                        ?>
                            <tr>
                                <td>
                                    <strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong><br>
                                    <small style="color:var(--gray-600); font-weight:600;">USN: <?= e($s['student_no']) ?></small><br>
                                    <small style="color:var(--gray-400);"><?= e($s['email']) ?></small>
                                </td>
                                <td><?= e($s['course'] ?? '—') ?></td>
                                <td>
                                    <?php if ($s['company_name']): ?>
                                        <span style="font-weight:600; color:#0F766E;"><?= e($s['company_name']) ?></span>
                                    <?php else: ?>
                                        <span class="badge pending">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size:12px; font-weight:600; margin-bottom:4px;">
                                        <?= number_format($s['rendered_hours'], 1) ?> / <?= $s['required_hours'] ?> hrs (<?= $progress ?>%)
                                    </div>
                                    <div class="progress-block" style="margin:0;">
                                        <div class="pb-bar" style="height:6px;">
                                            <div class="pb-fill" style="width: <?= $progress ?>%; background:#1E293B;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="/PractiTrack/admin/student-view.php?id=<?= $s['id'] ?>" class="btn btn-sm" style="background:#F1F5F9; color:#1E293B; font-weight:600;">View Details</a>
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