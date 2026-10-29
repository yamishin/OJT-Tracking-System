<?php
require_once __DIR__ . '/../includes/admin-layout.php';

$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalCompanies = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'company'")->fetchColumn();
$totalSchools = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'school'")->fetchColumn();

admin_layout_top('System Overview', 'PractiTrack Administration');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-label">Total Students</div>
        <div class="stat-value"><?= $totalStudents ?></div>
    </div>
    <div class="stat-card" style="border-left-color: #0F766E;">
        <div class="stat-icon" style="background:#CCFBF1; color:#0F766E;"><i class="fas fa-building"></i></div>
        <div class="stat-label">Partner Companies</div>
        <div class="stat-value"><?= $totalCompanies ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon"><i class="fas fa-school"></i></div>
        <div class="stat-label">Instructors / Schools</div>
        <div class="stat-value"><?= $totalSchools ?></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>Quick Actions</h3>
    </div>
    <div class="panel-body">
        <p style="margin-bottom: 16px; color: var(--gray-600);">Manage system access. Create company and school accounts.</p>
        <a href="/PractiTrack/admin/manage-users.php" class="btn btn-primary" style="background:var(--gray-800);">Manage Accounts</a>
    </div>
</div>

<?php admin_layout_bottom(); ?>