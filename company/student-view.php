<?php
require_once __DIR__ . '/../includes/company-layout.php';

$companyId = $_SESSION['user_id'];
$studentId = (int)($_GET['id'] ?? 0);

// Verify this student belongs to this company
$stmt = $pdo->prepare("SELECT u.*, sp.* FROM users u JOIN student_profiles sp ON sp.user_id = u.id WHERE u.id = ? AND sp.company_id = ?");
$stmt->execute([$studentId, $companyId]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student not found or not assigned to you.');
    redirect('/PractiTrack/company/students.php');
}

// Fetch reports
$stmt = $pdo->prepare("SELECT * FROM weekly_reports WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$studentId]);
$reports = $stmt->fetchAll();

// Fetch final evaluation if exists
$stmt = $pdo->prepare("SELECT * FROM final_evaluations WHERE student_id = ? AND company_id = ?");
$stmt->execute([$studentId, $companyId]);
$finalEval = $stmt->fetch();

company_layout_top('Student Profile', 'Evaluate intern: ' . $student['first_name'] . ' ' . $student['last_name']);
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><span>✓</span><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><span>⚠</span><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="grid-2">
    <!-- Student Info -->
    <div class="panel">
        <div class="panel-header"><h3>Intern Details</h3></div>
        <div class="panel-body">
            <p><strong>Name:</strong> <?= e($student['first_name'] . ' ' . $student['last_name']) ?></p>
            <p><strong>USN:</strong> <?= e($student['student_no']) ?></p>
            <p><strong>Course:</strong> <?= e($student['course']) ?></p>
            <p><strong>Email:</strong> <?= e($student['email']) ?></p>
            <p><strong>Required Hours:</strong> <?= $student['required_hours'] ?></p>
        </div>
    </div>

    <!-- Final Evaluation -->
    <div class="panel">
        <div class="panel-header"><h3>Final Evaluation</h3></div>
        <div class="panel-body">
            <?php if ($finalEval): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <span><strong>Completed!</strong> Score: <?= $finalEval['score'] ?>/100<br><?= e($finalEval['remarks']) ?></span>
                </div>
            <?php else: ?>
                <form action="/PractiTrack/php/company/evaluate-handler.php" method="POST">
                    <input type="hidden" name="action" value="final_eval">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                    
                    <div class="field">
                        <label>Final Score (1-100)</label>
                        <input type="number" name="score" min="1" max="100" required>
                    </div>
                    <div class="field">
                        <label>Remarks / Feedback</label>
                        <textarea name="remarks" placeholder="Overall performance..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="background:var(--company-primary);">Submit Final Evaluation</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Weekly Reports Review -->
<div class="panel">
    <div class="panel-header">
        <h3>Submitted Reports</h3>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Dates</th>
                        <th>Type</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Action / Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="5" class="empty">No reports submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td><?= date('M d', strtotime($r['week_start'])) ?> - <?= date('M d', strtotime($r['week_end'])) ?></td>
                                <td><?= ucfirst($r['report_type']) ?></td>
                                <td><?= number_format((float)$r['hours_rendered'], 2) ?></td>
                                <td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                                <td>
                                    <?php if ($r['status'] === 'submitted'): ?>
                                        <form action="/PractiTrack/php/company/evaluate-handler.php" method="POST" style="display:flex; gap:5px;">
                                            <input type="hidden" name="action" value="weekly_eval">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                            <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                            <input type="text" name="feedback" placeholder="Feedback..." required style="padding:4px; font-size:12px; border:1px solid #ccc; border-radius:4px; width:150px;">
                                            <button type="submit" name="decision" value="approved" class="btn btn-sm" style="background:#E8F5E9; color:#1B5E20;">Approve</button>
                                            <button type="submit" name="decision" value="rejected" class="btn btn-sm" style="background:var(--red-light); color:var(--red-dark);">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:13px; color:var(--gray-600);"><?= e($r['company_feedback'] ?? 'No feedback') ?></span>
                                    <?php endif; ?>
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