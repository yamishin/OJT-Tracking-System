<?php
require_once __DIR__ . '/../includes/school-layout.php';

// Count students
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$totalStudents = (int)$stmt->fetchColumn();

// Count partner companies
$stmt = $pdo->query("SELECT COUNT(*) FROM company_profiles WHERE is_partner = 1");
$totalCompanies = (int)$stmt->fetchColumn();

// Count pending documents
$stmt = $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'pending'");
$pendingDocs = (int)$stmt->fetchColumn();

school_layout_top('Dashboard', 'School / Supervisor Overview');
?>

<div class="stats-grid">
    <div class="stat-card" style="border-left-color: #0288D1;">
        <div class="stat-icon" style="background:#E0F2FE; color:#0288D1;"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-label">Total OJT Students</div>
        <div class="stat-value"><?= $totalStudents ?></div>
        <div class="stat-sub">in the system</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-building"></i></div>
        <div class="stat-label">Partner Companies</div>
        <div class="stat-value"><?= $totalCompanies ?></div>
        <div class="stat-sub">active partners</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
        <div class="stat-label">Pending Documents</div>
        <div class="stat-value"><?= $pendingDocs ?></div>
        <div class="stat-sub">needs review</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>Quick Actions</h3>
    </div>
    <div class="panel-body">
        <p style="margin-bottom: 16px; color: var(--gray-600);">Monitor student progress, track their required documents, and review their final grades.</p>
        <div style="display:flex; gap:10px;">
            <a href="/PractiTrack/school/students.php" class="btn btn-primary" style="background:#0277BD;">View Student List</a>
            <a href="/PractiTrack/school/companies.php" class="btn btn-outline">View Company Partners</a>
        </div>
    </div>
</div>

<?php school_layout_bottom(); ?>