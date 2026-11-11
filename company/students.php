<?php
require_once __DIR__ . '/../includes/company-layout.php';

$companyId = $_SESSION['user_id'];

// Fetch only ACCEPTED students for this company
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, sp.student_no, sp.course, sp.required_hours,
    (SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = u.id) as rendered_hours
    FROM users u
    JOIN student_profiles sp ON sp.user_id = u.id
    WHERE sp.company_id = ? AND sp.application_status = 'accepted'
    ORDER BY u.last_name ASC
");
$stmt->execute([$companyId]);
$students = $stmt->fetchAll();

company_layout_top('My Students', 'Manage and evaluate your assigned interns');
?>

<div class="panel">
    <div class="panel-header">
        <h3>Intern List</h3>
        <div class="sub"><?= count($students) ?> student(s)</div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>USN</th>
                        <th>Course</th>
                        <th>Progress</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="5" class="empty">No students assigned to your company yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): 
                            $progress = $s['required_hours'] > 0 ? min(100, round(($s['rendered_hours'] / $s['required_hours']) * 100)) : 0;
                        ?>
                            <tr>
                                <td>
                                    <strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong><br>
                                    <small style="color:var(--gray-400);"><?= e($s['email']) ?></small>
                                </td>
                                <td><?= e($s['student_no'] ?? '—') ?></td>
                                <td><?= e($s['course'] ?? '—') ?></td>
                                <td>
                                    <div style="font-size:12px; font-weight:600; margin-bottom:4px;"><?= $progress ?>%</div>
                                    <div class="progress-block" style="margin:0;">
                                        <div class="pb-bar" style="height:6px;">
                                            <div class="pb-fill" style="width: <?= $progress ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="/PractiTrack/company/student-view.php?id=<?= $s['id'] ?>" class="btn btn-sm" style="background:#E0F2FE; color:#0284C7;">
                                        <i class="fas fa-search"></i> Evaluate
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php company_layout_bottom(); ?>