<?php
require_once __DIR__ . '/../includes/admin-layout.php';

$studentId = (int)($_GET['id'] ?? 0);

// Fetch Student Info
$stmt = $pdo->prepare("
    SELECT u.*, sp.student_no, sp.course, sp.required_hours, sp.company_id, cp.company_name 
    FROM users u 
    JOIN student_profiles sp ON sp.user_id = u.id 
    LEFT JOIN company_profiles cp ON cp.user_id = sp.company_id
    WHERE u.id = ? AND u.role = 'student'
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('/PractiTrack/admin/students.php');
}

// Fetch Total Hours
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = ?");
$stmt->execute([$studentId]);
$renderedHours = (float)$stmt->fetchColumn();
$requiredHours = (int)$student['required_hours'];
$progress = $requiredHours > 0 ? min(100, round(($renderedHours / $requiredHours) * 100)) : 0;

// Fetch Documents Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE student_id = ?");
$stmt->execute([$studentId]);
$docCount = (int)$stmt->fetchColumn();

// Fetch Reports Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM weekly_reports WHERE student_id = ?");
$stmt->execute([$studentId]);
$reportCount = (int)$stmt->fetchColumn();

// Fetch Final Evaluation
$stmt = $pdo->prepare("SELECT * FROM final_evaluations WHERE student_id = ?");
$stmt->execute([$studentId]);
$finalEval = $stmt->fetch();

admin_layout_top('Student Details', 'Full system overview');
?>

<div style="margin-bottom: 20px;">
    <a href="/PractiTrack/admin/students.php" style="color:var(--gray-600); font-size:14px; font-weight:600;">← Back to Student List</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-label">Hours Rendered</div>
        <!-- ... -->
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon"><i class="fas fa-folder"></i></div>
        <div class="stat-label">Documents</div>
        <!-- ... -->
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
        <div class="stat-label">Reports</div>
        <!-- ... -->
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="fas fa-award"></i></div>
        <div class="stat-label">Final Grade</div>
        <!-- ... -->
    </div>
</div>

<div class="grid-2">
    <!-- Student Profile Details -->
    <div class="panel">
        <div class="panel-header"><h3>Student Information</h3></div>
        <div class="panel-body">
            <p style="margin-bottom:12px;"><strong>Full Name:</strong> <?= e($student['first_name'] . ' ' . $student['last_name']) ?></p>
            <p style="margin-bottom:12px;"><strong>USN:</strong> <?= e($student['student_no']) ?></p>
            <p style="margin-bottom:12px;"><strong>Email:</strong> <?= e($student['email']) ?></p>
            <p style="margin-bottom:12px;"><strong>Course:</strong> <?= e($student['course'] ?? '—') ?></p>
            <p style="margin-bottom:12px;"><strong>Account Status:</strong> <?= $student['is_active'] ? '<span style="color:green; font-weight:bold;">Active</span>' : '<span style="color:red; font-weight:bold;">Deactivated</span>' ?></p>
            <p style="margin-bottom:12px;"><strong>Registered On:</strong> <?= date('M d, Y', strtotime($student['created_at'])) ?></p>
        </div>
    </div>

    <!-- Company & Evaluation -->
    <div class="panel">
        <div class="panel-header"><h3>OJT Assignment & Evaluation</h3></div>
        <div class="panel-body">
            <p style="margin-bottom:12px;"><strong>Assigned Company:</strong> 
                <?= $student['company_name'] ? '<span style="color:#0F766E; font-weight:bold;">'.e($student['company_name']).'</span>' : '<em style="color:var(--gray-400);">Not assigned yet</em>' ?>
            </p>
            <hr style="margin:16px 0; border:0; border-top:1px solid var(--gray-200);">
            
            <?php if ($finalEval): ?>
                <div class="alert alert-success" style="margin-bottom:0;">
                    <span>✅</span>
                    <div>
                        <strong>Evaluation Score: <?= $finalEval['score'] ?> / 100</strong><br>
                        <p style="margin-top:8px; font-size:13px;">"<?= nl2br(e($finalEval['remarks'])) ?>"</p>
                        <small style="color:var(--gray-600);">Submitted on <?= date('M d, Y', strtotime($finalEval['created_at'])) ?></small>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding:20px;">
                    <div class="em-icon">⏳</div>
                    <h4>No Final Evaluation Yet</h4>
                    <p>The assigned company has not submitted a final grade.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php admin_layout_bottom(); ?>