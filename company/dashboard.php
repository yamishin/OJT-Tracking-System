<?php
require_once __DIR__ . '/../includes/company-layout.php';

$companyId = $_SESSION['user_id'];

// Total assigned students
$stmt = $pdo->prepare("SELECT COUNT(*) FROM student_profiles WHERE company_id = ?");
$stmt->execute([$companyId]);
$totalStudents = (int)$stmt->fetchColumn();

// Pending evaluations (submitted weekly reports from their students)
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM weekly_reports wr
    JOIN student_profiles sp ON sp.user_id = wr.student_id
    WHERE sp.company_id = ? AND wr.status = 'submitted'
");
$stmt->execute([$companyId]);
$pendingReports = (int)$stmt->fetchColumn();

company_layout_top('Dashboard', 'Overview of your OJT interns');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🎓</div>
        <div class="stat-label">Assigned Students</div>
        <div class="stat-value"><?= $totalStudents ?></div>
        <div class="stat-sub">active interns</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon">📝</div>
        <div class="stat-label">Pending Reports</div>
        <div class="stat-value"><?= $pendingReports ?></div>
        <div class="stat-sub">needs evaluation</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>Quick Actions</h3>
    </div>
    <div class="panel-body">
        <p style="margin-bottom: 16px; color: var(--gray-600);">Manage your interns, review their weekly reports, and complete their final evaluations.</p>
        <a href="/PractiTrack/company/students.php" class="btn btn-primary">View My Students</a>
    </div>
</div>

<?php company_layout_bottom(); ?>