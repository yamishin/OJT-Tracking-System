<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

// Fetch reports for display
$stmt = $pdo->prepare("SELECT * FROM weekly_reports WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$studentId]);
$reports = $stmt->fetchAll();

// Fetch UNLOGGED attendance (not already in weekly_reports)
// Using FIND_IN_SET to check if the date is inside the logged_dates column
$stmt = $pdo->prepare("
    SELECT * FROM attendance 
    WHERE student_id = ? 
    AND log_date NOT IN (
        SELECT log_date FROM weekly_report_attendance wra
        JOIN weekly_reports wr ON wr.id = wra.report_id
        WHERE wr.student_id = ?
    )
    ORDER BY log_date ASC
");
$stmt->execute([$studentId, $studentId]);
$unloggedAttendance = $stmt->fetchAll();

student_layout_top('Reports', 'Generate weekly or monthly reports from your attendance');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>...</span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span>...</span></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3>Generate New Report</h3>
    </div>
    <div class="panel-body">
        <form action="/PractiTrack/php/student/report-handler.php" method="POST">
            <input type="hidden" name="action" value="submit_report">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-grid">
                <div class="field">
                    <label>Report Type</label>
                    <select name="report_type" id="report_type" required>
                        <option value="weekly">Weekly Report</option>
                        <option value="monthly">Monthly Summary</option>
                    </select>
                </div>
                <div class="field">
                    <label>Hours Rendered (Auto-computed)</label>
                    <input type="number" step="0.01" name="hours_rendered" id="hours_rendered" placeholder="Select days below" readonly style="background:var(--gray-100);">
                </div>
                
                <div class="field full">
                    <label>Select Days Worked (Only Unlogged days show here)</label>
                    <div style="max-height: 200px; overflow-y: auto; border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm); padding: 10px;">
                        <?php if (empty($unloggedAttendance)): ?>
                            <p style="color:var(--gray-400); font-size:13px;">No unassigned attendance records found. Please log attendance first.</p>
                        <?php else: ?>
                            <?php foreach ($unloggedAttendance as $a): ?>
                                <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px; cursor:pointer;">
                                    <input type="checkbox" name="attendance_ids[]" value="<?= $a['id'] ?>" data-hours="<?= $a['total_hours'] ?>" class="att-checkbox" style="width:16px; height:16px;">
                                    <span><?= date('M d, Y (D)', strtotime($a['log_date'])) ?> — <strong><?= number_format((float)$a['total_hours'], 2) ?> hrs</strong> (<?= e($a['status']) ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field full">
                    <label>Activities Performed</label>
                    <textarea name="activities" placeholder="Describe the tasks and activities you did..." required></textarea>
                </div>
                <div class="field full">
                    <label>Learnings / Insights</label>
                    <textarea name="learnings" placeholder="What did you learn? (optional)"></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit Report</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>My Reports</h3>
        <div class="sub"><?= count($reports) ?> report(s)</div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Dates Covered</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="5" class="empty">No reports yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td><strong><?= ucfirst($r['report_type']) ?></strong></td>
                                <td><?= date('M d', strtotime($r['week_start'])) ?> – <?= date('M d, Y', strtotime($r['week_end'])) ?></td>
                                <td><?= number_format((float)$r['hours_rendered'], 2) ?></td>
                                <td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                                <td><?= e($r['feedback'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Auto-calculate total hours when checkboxes are checked
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.att-checkbox');
    const hoursInput = document.getElementById('hours_rendered');

    function calcHours() {
        let total = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                total += parseFloat(cb.dataset.hours || 0);
            }
        });
        hoursInput.value = total.toFixed(2);
    }

    checkboxes.forEach(cb => cb.addEventListener('change', calcHours));
});
</script>

<?php student_layout_bottom(); ?>