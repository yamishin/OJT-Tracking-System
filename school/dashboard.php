<?php
require_once __DIR__ . '/../includes/school-layout.php';

// Count students
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$totalStudents = (int)$stmt->fetchColumn();

// Count total documents
$stmt = $pdo->query("SELECT COUNT(*) FROM documents");
$totalDocs = (int)$stmt->fetchColumn();

school_layout_top('Dashboard', 'School / Supervisor Overview');
?>

<div class="stats-grid">
    <div class="stat-card" style="border-left-color: #7C3AED;">
        <div class="stat-icon" style="background:#EDE9FE; color:#7C3AED;">🎓</div>
        <div class="stat-label">Total OJT Students</div>
        <div class="stat-value"><?= $totalStudents ?></div>
        <div class="stat-sub">in the system</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📁</div>
        <div class="stat-label">Documents Submitted</div>
        <div class="stat-value"><?= $totalDocs ?></div>
        <div class="stat-sub">all students</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>Quick Actions</h3>
    </div>
    <div class="panel-body">
        <p style="margin-bottom: 16px; color: var(--gray-600);">Monitor student progress, track their required documents, and review their final grades.</p>
        <a href="/PractiTrack/school/students.php" class="btn btn-primary" style="background:#7C3AED;">View Student List</a>
    </div>
</div>

<?php school_layout_bottom(); ?>