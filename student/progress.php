<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

// 1. Hours Progress
$stmt = $pdo->prepare("SELECT required_hours FROM student_profiles WHERE user_id = ?");
$stmt->execute([$studentId]);
$requiredHours = (int)($stmt->fetchColumn() ?: 486);

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = ?");
$stmt->execute([$studentId]);
$totalHours = (float)$stmt->fetchColumn();

$remaining = max(0, $requiredHours - $totalHours);
$progress = $requiredHours > 0 ? min(100, round(($totalHours / $requiredHours) * 100, 1)) : 0;
$daysNeeded = $remaining > 0 ? ceil($remaining / 8) : 0;
$expectedDate = date('F d, Y', strtotime("+$daysNeeded weekdays"));

// 2. Document Tracking Stats
$stmt = $pdo->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$studentId]);
$documents = $stmt->fetchAll();

$totalDocs = count($documents);
$approvedDocs = 0;
$pendingDocs = 0;
$rejectedDocs = 0;

foreach ($documents as $d) {
    if ($d['status'] === 'approved') $approvedDocs++;
    elseif ($d['status'] === 'pending') $pendingDocs++;
    elseif ($d['status'] === 'rejected') $rejectedDocs++;
}

// 3. Weekly Breakdown
$stmt = $pdo->prepare("SELECT WEEK(log_date, 1) AS wk, MIN(log_date) AS wk_start, SUM(total_hours) AS hrs
                       FROM attendance WHERE student_id = ?
                       GROUP BY wk ORDER BY wk DESC LIMIT 12");
$stmt->execute([$studentId]);
$weekly = $stmt->fetchAll();

student_layout_top('Progress', 'Track your OJT completion and document status');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-label">Rendered</div>
        <div class="stat-value"><?= number_format($totalHours, 1) ?></div>
        <div class="stat-sub">hours</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="fas fa-bullseye"></i></div>
        <div class="stat-label">Required</div>
        <div class="stat-value"><?= $requiredHours ?></div>
        <div class="stat-sub">total hours</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-label">Remaining</div>
        <div class="stat-value"><?= number_format($remaining, 1) ?></div>
        <div class="stat-sub">hours left</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-label">Est. Completion</div>
        <div class="stat-value" style="font-size:16px;"><?= $expectedDate ?></div>
        <div class="stat-sub"><?= $daysNeeded ?> working day(s)</div>
    </div>
</div>

<div class="grid-2">
    <!-- Overall Hours Progress -->
    <div class="panel">
        <div class="panel-header">
            <h3>Hours Progress</h3>
            <span class="badge submitted"><?= $progress ?>% Complete</span>
        </div>
        <div class="panel-body">
            <div class="progress-block">
                <div class="pb-head">
                    <span>Rendered Hours</span>
                    <span><?= number_format($totalHours, 1) ?> / <?= $requiredHours ?></span>
                </div>
                <div class="pb-bar" style="height:16px;">
                    <div class="pb-fill" data-width="<?= $progress ?>%"></div>
                </div>
            </div>

            <div class="alert alert-info" style="margin-top:20px;margin-bottom:0;">
                <i class="fas fa-lightbulb" style="font-size:18px;"></i>
                <span>
                    At 8 hours per working day, you need approximately
                    <strong><?= $daysNeeded ?> more days</strong> to complete your OJT.
                </span>
            </div>
        </div>
    </div>

    <!-- Document Status Tracker -->
    <div class="panel">
        <div class="panel-header">
            <h3>Document Status</h3>
            <a href="/PractiTrack/student/documents.php" class="btn btn-sm btn-ghost">View All</a>
        </div>
        <div class="panel-body">
            <div style="display: flex; justify-content: space-between; text-align: center; margin-bottom: 20px;">
                <div>
                    <div style="font-size: 24px; font-weight: bold; color: var(--success);"><?= $approvedDocs ?></div>
                    <div style="font-size: 12px; color: var(--gray-600);">Approved</div>
                </div>
                <div>
                    <div style="font-size: 24px; font-weight: bold; color: var(--warning);"><?= $pendingDocs ?></div>
                    <div style="font-size: 12px; color: var(--gray-600);">Pending</div>
                </div>
                <div>
                    <div style="font-size: 24px; font-weight: bold; color: var(--red);"><?= $rejectedDocs ?></div>
                    <div style="font-size: 12px; color: var(--gray-600);">Rejected</div>
                </div>
            </div>

            <?php if ($totalDocs > 0): ?>
                <div class="progress-block" style="margin-bottom: 10px;">
                    <div class="pb-head">
                        <span>Approval Rate</span>
                        <span><?= $approvedDocs ?> / <?= $totalDocs ?> docs</span>
                    </div>
                    <div class="pb-bar">
                        <div class="pb-fill green" data-width="<?= ($approvedDocs / $totalDocs) * 100 ?>%"></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 10px;">
                    <p style="font-size: 13px;">No documents uploaded yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>Weekly Breakdown</h3>
        <div class="sub">Last 12 weeks</div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Week Starting</th><th>Hours Rendered</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($weekly)): ?>
                        <tr><td colspan="2" class="empty">No data yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($weekly as $w): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($w['wk_start'])) ?></td>
                                <td><strong><?= number_format((float)$w['hrs'], 2) ?> hrs</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php student_layout_bottom(); ?>