<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY log_date DESC");
$stmt->execute([$studentId]);
$records = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = ?");
$stmt->execute([$studentId]);
$totalHours = (float)$stmt->fetchColumn();

student_layout_top('Attendance', 'Log your daily time in and time out');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><span>✓</span><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><span>⚠</span><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3>Log Attendance</h3>
        <div class="sub">Total hours rendered: <strong><?= number_format($totalHours, 2) ?></strong></div>
    </div>
    <div class="panel-body">
        <form action="/PractiTrack/php/student/attendance-handler.php" method="POST">
            <input type="hidden" name="action" value="log_attendance">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-grid">
                <div class="field full">
                    <label>Date</label>
                    <input type="date" name="log_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <!-- Grouped Time In & Out -->
                <div class="field" style="border-right: 1px solid var(--gray-200); padding-right: 10px;">
                    <label>Time In</label>
                    <input type="time" id="time_in" name="time_in" required>
                </div>
                <div class="field" style="padding-left: 10px;">
                    <label>Time Out</label>
                    <input type="time" id="time_out" name="time_out" required>
                </div>

                <!-- Auto-calculated fields -->
                <div class="field">
                    <label>Computed Hours</label>
                    <input type="text" id="total_hours_display" readonly value="0.00" style="background:var(--gray-100); font-weight:bold; color:var(--light-blue-dark);">
                </div>
                <div class="field">
                    <label>Auto-Status</label>
                    <input type="text" id="status_display" readonly value="Waiting..." style="background:var(--gray-100); border:none;">
                </div>

                <div class="field full">
                    <label>Remarks (optional)</label>
                    <input type="text" name="remarks" placeholder="e.g., Worked on documentation">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Log Attendance</button>
            <div class="form-hint" style="margin-top:10px;">
                <em>* 8+ hours automatically deducts 1 hour for lunch. Max standard time is 7:00 PM.</em>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>Attendance History</h3>
        <div class="sub"><?= count($records) ?> record(s)</div>
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
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="5" class="empty">No attendance yet. Log your first entry above.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td><?= date('M d, Y (D)', strtotime($r['log_date'])) ?></td>
                                <td>
                                    <span style="color:var(--gray-600);"><?= $r['time_in'] ? date('h:i A', strtotime($r['time_in'])) : '—' ?></span>
                                    <span style="margin:0 8px; color:var(--gray-400);">→</span>
                                    <span style="color:var(--gray-600);"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '—' ?></span>
                                </td>
                                <td><strong><?= number_format((float)$r['total_hours'], 2) ?></strong></td>
                                <td>
                                    <span class="badge" style="background:
                                        <?php 
                                            if($r['status'] == 'Whole Day') echo '#E8F5E9; color:#1B5E20;';
                                            elseif($r['status'] == 'Half Day') echo '#E0F2FE; color:#0284C7;';
                                            elseif($r['status'] == 'Undertime') echo '#FFF8E1; color:#B26A00;';
                                            elseif($r['status'] == 'Overtime') echo '#FFEDD5; color:#C2410C;';
                                            else echo '#FEE2E2; color:#991B1B;';
                                        ?>">
                                        <?= e($r['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-ghost" onclick="viewAttendance(<?= htmlspecialchars(json_encode($r)) ?>)">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- The Modal Popup -->
<div class="modal-overlay" id="attendanceModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Attendance Details</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="detail-row">
                <span class="detail-label">Date Logged</span>
                <span class="detail-value" id="m_date"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time In</span>
                <span class="detail-value" id="m_time_in"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time Out</span>
                <span class="detail-value" id="m_time_out"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Lunch Break</span>
                <span class="detail-value" id="m_lunch"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Final Hours Rendered</span>
                <span class="detail-value" id="m_hours" style="color:var(--light-blue-dark); font-size:16px;"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Calculated Status</span>
                <span class="detail-value" id="m_status"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Remarks</span>
                <span class="detail-value" id="m_remarks" style="font-weight:normal; color:var(--gray-600); font-style:italic;"></span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-sm" onclick="closeModal()" style="background:var(--gray-200); color:var(--gray-800);">Close</button>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('attendanceModal');

    function viewAttendance(data) {
        // Format Times
        const formatTime = (timeStr) => {
            if (!timeStr) return '—';
            let [h, m] = timeStr.split(':');
            h = parseInt(h);
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return `${h}:${m} ${ampm}`;
        };

        const formatDate = (dateStr) => {
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        };

        // Populate Modal
        document.getElementById('m_date').textContent = formatDate(data.log_date);
        document.getElementById('m_time_in').textContent = formatTime(data.time_in);
        document.getElementById('m_time_out').textContent = formatTime(data.time_out);
        document.getElementById('m_lunch').textContent = (data.lunch_deducted == 1) ? 'Deducted (-1 hr)' : 'Not Deducted';
        document.getElementById('m_hours').textContent = parseFloat(data.total_hours).toFixed(2) + ' hours';
        document.getElementById('m_status').textContent = data.status;
        document.getElementById('m_remarks').textContent = data.remarks || 'No remarks provided.';

        // Show Modal
        modal.classList.add('open');
    }

    function closeModal() {
        modal.classList.remove('open');
    }

    // Close modal if clicking outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
</script>

<?php student_layout_bottom(); ?>