<?php
require_once __DIR__ . '/../includes/company-layout.php';

$companyId = $_SESSION['user_id'];
$studentId = (int)($_GET['id'] ?? 0);

// Verify student belongs to this company and is accepted
$stmt = $pdo->prepare("SELECT u.*, sp.* FROM users u JOIN student_profiles sp ON sp.user_id = u.id WHERE u.id = ? AND sp.company_id = ? AND sp.application_status = 'accepted'");
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

// Fetch documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$studentId]);
$documents = $stmt->fetchAll();

// Fetch final evaluation
$stmt = $pdo->prepare("SELECT * FROM final_evaluations WHERE student_id = ? AND company_id = ?");
$stmt->execute([$studentId, $companyId]);
$finalEval = $stmt->fetch();

company_layout_top('Student Evaluation', 'Review and evaluate intern');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div style="margin-bottom: 20px;">
    <a href="/PractiTrack/company/students.php" style="color:var(--gray-600); font-size:14px; font-weight:600;"><i class="fas fa-arrow-left"></i> Back to Students</a>
</div>

<div class="grid-2">
    <!-- Student Info -->
    <div class="panel">
        <div class="panel-header"><h3>Intern Details</h3></div>
        <div class="panel-body">
            <p style="margin-bottom:8px;"><strong>Name:</strong> <?= e($student['first_name'] . ' ' . $student['last_name']) ?></p>
            <p style="margin-bottom:8px;"><strong>USN:</strong> <?= e($student['student_no']) ?></p>
            <p style="margin-bottom:8px;"><strong>Course:</strong> <?= e($student['course']) ?></p>
            <p style="margin-bottom:8px;"><strong>Email:</strong> <?= e($student['email']) ?></p>
            <p style="margin-bottom:8px;"><strong>Required Hours:</strong> <?= $student['required_hours'] ?></p>
        </div>
    </div>

    <!-- Final Evaluation -->
    <div class="panel">
        <div class="panel-header"><h3>Final Evaluation</h3></div>
        <div class="panel-body">
            <?php if ($finalEval): ?>
                <div class="alert alert-success" style="margin-bottom:0; padding: 20px;">
                    <i class="fas fa-trophy" style="font-size:30px; color:var(--success); margin-right:15px;"></i>
                    <div>
                        <strong style="font-size:18px;">Score: <?= $finalEval['score'] ?> / 100</strong><br>
                        <p style="margin-top:8px; font-size:14px;">"<?= nl2br(e($finalEval['remarks'])) ?>"</p>
                    </div>
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
                    <button type="submit" class="btn btn-primary">Submit Final Evaluation</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Document Tracking -->
<div class="panel">
    <div class="panel-header">
        <h3>Student Documents</h3>
        <div class="sub">View submitted requirements</div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>File</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                        <tr><td colspan="4" class="empty">No documents submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($documents as $d): ?>
                            <tr>
                                <td><strong><?= e($d['doc_type']) ?></strong></td>
                                <td><a href="/PractiTrack/<?= e($d['file_path']) ?>" target="_blank" style="color:var(--light-blue-dark);font-weight:600;"><i class="fas fa-file-pdf"></i> View File</a></td>
                                <td><?= date('M d, Y', strtotime($d['submitted_at'])) ?></td>
                                <td><span class="badge <?= e($d['status']) ?>"><?= e($d['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Weekly Reports Review -->
<div class="panel">
    <div class="panel-header">
        <h3>Weekly & Monthly Reports</h3>
        <div class="sub">Evaluate student submissions</div>
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