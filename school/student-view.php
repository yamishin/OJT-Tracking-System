<?php
require_once __DIR__ . '/../includes/school-layout.php';

$studentId = (int)($_GET['id'] ?? 0);

// 1. Fetch Student Info
$stmt = $pdo->prepare("SELECT u.*, sp.* FROM users u JOIN student_profiles sp ON sp.user_id = u.id WHERE u.id = ? AND u.role = 'student'");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('/PractiTrack/school/students.php');
}

// 2. Fetch Company Info
$company = null;
if ($student['company_id']) {
    $stmt = $pdo->prepare("SELECT company_name FROM company_profiles WHERE user_id = ?");
    $stmt->execute([$student['company_id']]);
    $company = $stmt->fetch();
}

// 3. Calculate Progress
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = ?");
$stmt->execute([$studentId]);
$renderedHours = (float)$stmt->fetchColumn();
$requiredHours = (int)$student['required_hours'];
$progressPercent = $requiredHours > 0 ? min(100, round(($renderedHours / $requiredHours) * 100)) : 0;

// 4. Fetch Documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$studentId]);
$documents = $stmt->fetchAll();

// 5. Fetch Attendance
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY log_date DESC");
$stmt->execute([$studentId]);
$attendance = $stmt->fetchAll();

// 6. Fetch Weekly Reports
$stmt = $pdo->prepare("SELECT * FROM weekly_reports WHERE student_id = ? ORDER BY week_start DESC");
$stmt->execute([$studentId]);
$reports = $stmt->fetchAll();

// 7. Fetch Final Evaluation
$stmt = $pdo->prepare("SELECT * FROM final_evaluations WHERE student_id = ?");
$stmt->execute([$studentId]);
$finalEval = $stmt->fetch();

school_layout_top('Student Profile', 'Review progress, documents, and evaluations');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div style="margin-bottom: 20px;">
    <a href="/PractiTrack/school/students.php" style="color:var(--gray-600); font-size:14px; font-weight:600;"><i class="fas fa-arrow-left"></i> Back to Student List</a>
</div>

<div class="grid-2">
    <!-- Student Details -->
    <div class="panel">
        <div class="panel-header"><h3>Student Details</h3></div>
        <div class="panel-body">
            <p style="margin-bottom:8px;"><strong>Name:</strong> <?= e($student['first_name'] . ' ' . $student['last_name']) ?></p>
            <p style="margin-bottom:8px;"><strong>USN:</strong> <?= e($student['student_no']) ?></p>
            <p style="margin-bottom:8px;"><strong>Course:</strong> <?= e($student['course'] ?? '—') ?></p>
            <p style="margin-bottom:8px;"><strong>Email:</strong> <?= e($student['email']) ?></p>
            <hr style="margin:16px 0; border:0; border-top:1px solid var(--gray-200);">
            <p style="margin-bottom:8px;"><strong>Assigned Company:</strong> <?= $company ? '<span style="color:var(--light-blue-dark); font-weight:bold;">'.e($company['company_name']).'</span>' : '<em style="color:var(--gray-400);">Not assigned yet</em>' ?></p>
        </div>
    </div>

    <!-- Progress Overview -->
    <div class="panel">
        <div class="panel-header"><h3>OJT Progress</h3></div>
        <div class="panel-body">
            <div class="progress-block" style="margin-bottom: 10px;">
                <div class="pb-head">
                    <span>Hours Rendered</span>
                    <span><?= number_format($renderedHours, 1) ?> / <?= $requiredHours ?> hrs</span>
                </div>
                <div class="pb-bar" style="height:16px;">
                    <!-- Fixed $progress to $progressPercent and removed the purple background -->
                    <div class="pb-fill" data-width="<?= $progressPercent ?>%" style="width: <?= $progressPercent ?>%;"></div>
                </div>
            </div>
            <p style="text-align: right; font-weight: 700; color: var(--light-blue-dark); margin-top: 5px;"><?= $progressPercent ?>% Complete</p>

            <?php if ($finalEval): ?>
                <div class="alert alert-success" style="margin-top: 20px; margin-bottom:0; padding: 12px;">
                    <i class="fas fa-trophy" style="font-size:20px; color:var(--success);"></i>
                    <div>
                        <strong>Final Company Score: <?= $finalEval['score'] ?> / 100</strong>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Document Tracking -->
<div class="panel">
    <div class="panel-header">
        <h3>Document Tracking</h3>
        <div class="sub">Approve or reject submitted documents</div>
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
                        <th>Action / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                        <tr><td colspan="5" class="empty">No documents submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($documents as $d): ?>
                            <tr>
                                <td><strong><?= e($d['doc_type']) ?></strong></td>
                                <td><a href="/PractiTrack/<?= e($d['file_path']) ?>" target="_blank" style="color:var(--light-blue-dark);font-weight:600;"><i class="fas fa-file-pdf"></i> View File</a></td>
                                <td><?= date('M d, Y', strtotime($d['submitted_at'])) ?></td>
                                <td><span class="badge <?= e($d['status']) ?>"><?= e($d['status']) ?></span></td>
                                <td>
                                    <?php if ($d['status'] === 'pending'): ?>
                                        <form action="/PractiTrack/php/school/document-handler.php" method="POST" style="display:flex; gap:5px;">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                            <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                                            <input type="text" name="remarks" placeholder="Remarks..." style="padding:4px; font-size:12px; border:1px solid #ccc; border-radius:4px; width:120px;">
                                            <button type="submit" name="decision" value="approved" class="btn btn-sm" style="background:#E8F5E9; color:#1B5E20;">Approve</button>
                                            <button type="submit" name="decision" value="rejected" class="btn btn-sm" style="background:var(--red-light); color:var(--red-dark);">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:13px; color:var(--gray-600);"><?= e($d['remarks'] ?: 'No remarks') ?></span>
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

<!-- Weekly Reports Tracking -->
<div class="panel">
    <div class="panel-header">
        <h3>Weekly & Monthly Reports</h3>
        <div class="sub">View student submissions and company feedback</div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Dates Covered</th>
                        <th>Type</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Company Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="5" class="empty">No reports submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td><?= date('M d', strtotime($r['week_start'])) ?> - <?= date('M d, Y', strtotime($r['week_end'])) ?></td>
                                <td><span class="badge" style="background:#F3F4F6; color:#374151;"><?= ucfirst($r['report_type']) ?></span></td>
                                <td><?= number_format((float)$r['hours_rendered'], 2) ?></td>
                                <td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                                <td><?= e($r['company_feedback'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Attendance History Tracking -->
<div class="panel">
    <div class="panel-header">
        <h3>Attendance History</h3>
        <div class="sub">Daily time logs</div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time In / Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance)): ?>
                        <tr><td colspan="5" class="empty">No attendance records yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $a): ?>
                            <tr>
                                <td><?= date('M d, Y (D)', strtotime($a['log_date'])) ?></td>
                                <td>
                                    <span style="color:var(--gray-600);"><?= $a['time_in'] ? date('h:i A', strtotime($a['time_in'])) : '—' ?></span>
                                    <span style="margin:0 8px; color:var(--gray-400);">→</span>
                                    <span style="color:var(--gray-600);"><?= $a['time_out'] ? date('h:i A', strtotime($a['time_out'])) : '—' ?></span>
                                </td>
                                <td><strong><?= number_format((float)$a['total_hours'], 2) ?></strong></td>
                                <td>
                                    <span class="badge" style="background:
                                        <?php 
                                            if($a['status'] == 'Whole Day') echo '#E8F5E9; color:#1B5E20;';
                                            elseif($a['status'] == 'Half Day') echo '#E0F2FE; color:#0284C7;';
                                            elseif($a['status'] == 'Undertime') echo '#FFF8E1; color:#B26A00;';
                                            elseif($a['status'] == 'Overtime') echo '#FFEDD5; color:#C2410C;';
                                            else echo '#FEE2E2; color:#991B1B;';
                                        ?>">
                                        <?= e($a['status']) ?>
                                    </span>
                                </td>
                                <td><?= e($a['remarks'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>  

<?php school_layout_bottom(); ?>