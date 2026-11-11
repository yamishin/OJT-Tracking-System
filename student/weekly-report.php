<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

// Fetch reports for display
$stmt = $pdo->prepare("SELECT * FROM weekly_reports WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$studentId]);
$reports = $stmt->fetchAll();

// Fetch UNLOGGED attendance (not already in weekly_reports)
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
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<!-- Header Panel with Add Button -->
<div class="panel" style="margin-bottom: 24px; background: linear-gradient(135deg, var(--light-blue-dark), var(--light-blue)); color: white; border: none;">
    <div class="panel-body" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-family: var(--font-heading); font-size: 22px; margin-bottom: 4px;">My Reports</h2>
            <p style="font-size: 14px; opacity: 0.9;">Create and track your weekly narratives or monthly summaries.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openReportModal()" style="background: var(--white); color: var(--light-blue-dark); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="fas fa-plus"></i> Add Report
        </button>
    </div>
</div>

<!-- Table Panel -->
<div class="panel">
    <div class="panel-header" style="flex-wrap: wrap; gap: 10px;">
        <h3>Report History</h3>
        
        <!-- Search and Filter -->
        <div style="display: flex; gap: 10px; margin-left: auto;">
            <div class="field" style="margin: 0; width: 200px;">
                <input type="text" id="searchReport" placeholder="Search reports..." style="padding: 8px 12px; font-size: 13px;">
            </div>
            <div class="field" style="margin: 0;">
                <select id="filterReport" style="padding: 8px 12px; font-size: 13px;">
                    <option value="all">All Types</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
        </div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table" id="reportsTable">
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
                        <tr><td colspan="5" class="empty">No reports yet. Click "Add Report" to create one.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): ?>
                            <tr class="report-row">
                                <td class="report-type"><strong><?= ucfirst($r['report_type']) ?></strong></td>
                                <td class="report-dates"><?= date('M d', strtotime($r['week_start'])) ?> – <?= date('M d, Y', strtotime($r['week_end'])) ?></td>
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

<!-- ========================================== -->
<!-- ADD REPORT MODAL -->
<!-- ========================================== -->
<div class="modal-overlay" id="addReportModal">
    <div class="modal-box" style="max-width: 600px; padding: 0;">
        
        <!-- We enforce the flex container on the form itself -->
        <form action="/PractiTrack/php/student/report-handler.php" method="POST" style="display: flex; flex-direction: column; max-height: 85vh;">
            <input type="hidden" name="action" value="submit_report">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="modal-header">
                <h3><i class="fas fa-file-signature"></i> Create New Report</h3>
                <button type="button" class="modal-close" onclick="closeReportModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body">
                <div class="form-grid">
                    <div class="field">
                        <label>Report Type</label>
                        <select name="report_type" id="report_type" required>
                            <option value="weekly">Weekly Report</option>
                            <option value="monthly">Monthly Summary</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Hours Rendered (Auto)</label>
                        <input type="number" step="0.01" name="hours_rendered" id="hours_rendered" placeholder="Select days below" readonly style="background:var(--gray-100); font-weight:bold; color:var(--light-blue-dark);">
                    </div>
                </div>
                
                <div class="field">
                    <label>Select Days Worked (Only Unlogged days show here)</label>
                    <div style="max-height: 150px; overflow-y: auto; border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm); padding: 10px; background:var(--gray-100);">
                        <?php if (empty($unloggedAttendance)): ?>
                            <p style="color:var(--gray-400); font-size:13px; margin:0;">No unassigned attendance records found. Please log attendance first.</p>
                        <?php else: ?>
                            <?php foreach ($unloggedAttendance as $a): ?>
                                <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px; cursor:pointer;">
                                    <input type="checkbox" name="attendance_ids[]" value="<?= $a['id'] ?>" data-hours="<?= $a['total_hours'] ?>" class="att-checkbox" style="width:16px; height:16px;">
                                    <span style="font-size: 13px;"><?= date('M d, Y (D)', strtotime($a['log_date'])) ?> — <strong><?= number_format((float)$a['total_hours'], 2) ?> hrs</strong></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field">
                    <label>Activities Performed</label>
                    <textarea name="activities" placeholder="Describe the tasks and activities you did..." required style="min-height: 80px;"></textarea>
                </div>
                <div class="field" style="margin-bottom: 0;">
                    <label>Learnings / Insights (optional)</label>
                    <textarea name="learnings" placeholder="What did you learn?" style="min-height: 60px;"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm" onclick="closeReportModal()" style="background:var(--gray-200); color:var(--gray-800);">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Submit Report</button>
            </div>
        </form>
        
    </div>
</div>

<script>
    // --- Modal Logic ---
    const reportModal = document.getElementById('addReportModal');

    function openReportModal() {
        reportModal.classList.add('open');
    }

    function closeReportModal() {
        reportModal.classList.remove('open');
    }

    // Close on clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === reportModal) closeReportModal();
    });

    // --- Auto-calculate hours ---
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

    // --- Search & Filter Logic ---
    const searchInput = document.getElementById('searchReport');
    const filterSelect = document.getElementById('filterReport');
    const rows = document.querySelectorAll('.report-row');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterTerm = filterSelect.value.toLowerCase();

        rows.forEach(row => {
            const type = row.querySelector('.report-type').textContent.toLowerCase();
            const dates = row.querySelector('.report-dates').textContent.toLowerCase();
            
            const matchesSearch = dates.includes(searchTerm) || type.includes(searchTerm);
            const matchesFilter = filterTerm === 'all' || type.includes(filterTerm);

            if (matchesSearch && matchesFilter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput && filterSelect) {
        searchInput.addEventListener('input', filterTable);
        filterSelect.addEventListener('change', filterTable);
    }
</script>

<?php student_layout_bottom(); ?>