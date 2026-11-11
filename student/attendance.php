<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY log_date DESC");
$stmt->execute([$studentId]);
$records = $stmt->fetchAll();

// Total standard hours (for progress)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = ?");
$stmt->execute([$studentId]);
$totalHours = (float)$stmt->fetchColumn();

// Total overtime hours (for display)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(overtime_hours),0) FROM attendance WHERE student_id = ?");
$stmt->execute([$studentId]);
$totalOvertime = (float)$stmt->fetchColumn();

student_layout_top('Attendance', 'Log your daily time in and time out');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<!-- Header Panel with Log Time Button -->
<div class="panel" style="margin-bottom: 24px; background: linear-gradient(135deg, var(--light-blue-dark), var(--light-blue)); color: white; border: none;">
    <div class="panel-body" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-family: var(--font-heading); font-size: 22px; margin-bottom: 4px;">Ready to log your hours?</h2>
            <p style="font-size: 14px; opacity: 0.9;">
                Standard Hours: <strong><?= number_format($totalHours, 2) ?></strong> 
                <?php if ($totalOvertime > 0): ?>
                    | Overtime: <strong style="color: #FFEDD5;"><?= number_format($totalOvertime, 2) ?></strong>
                <?php endif; ?>
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openLogModal()" style="background: var(--white); color: var(--light-blue-dark); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="fas fa-clock"></i> Log Time
        </button>
    </div>
</div>

<!-- Attendance History Table -->
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
                        <th>Standard Hours</th>
                        <th>Punctuality</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="6" class="empty">No attendance yet. Click "Log Time" to start.</td></tr>
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
                                            if($r['punctuality'] == 'Early') echo '#E8F5E9; color:#1B5E20;';
                                            elseif($r['punctuality'] == 'On Time') echo '#E0F2FE; color:#0284C7;';
                                            else echo '#FEE2E2; color:#991B1B;';
                                        ?>">
                                        <?= e($r['punctuality'] ?? 'On Time') ?>
                                    </span>
                                </td>
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
                                    <button type="button" class="btn btn-sm btn-ghost" onclick="viewAttendance(<?= htmlspecialchars(json_encode($r)) ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- LOG TIME MODAL -->
<!-- ========================================== -->
<div class="modal-overlay" id="logTimeModal">
    <!-- Added padding: 0 to modal-box -->
    <div class="modal-box" style="max-width: 500px; padding: 0;">
        
        <!-- The form is the flex container -->
        <form action="/PractiTrack/php/student/attendance-handler.php" method="POST" style="display: flex; flex-direction: column; max-height: 85vh;">
            <input type="hidden" name="action" value="log_attendance">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="modal-header">
                <h3><i class="fas fa-clock"></i> Log Attendance</h3>
                <button type="button" class="modal-close" onclick="closeLogModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body">
                <div class="field">
                    <label>Date</label>
                    <input type="date" name="log_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-grid" style="gap:12px;">
                    <div class="field">
                        <label>Time In</label>
                        <input type="time" id="time_in" name="time_in" required>
                    </div>
                    <div class="field">
                        <label>Time Out</label>
                        <input type="time" id="time_out" name="time_out" required>
                    </div>
                </div>

                <div class="form-grid" style="gap:12px; margin-top: 12px;">
                    <div class="field">
                        <label>Hours (Standard)</label>
                        <input type="text" id="total_hours_display" readonly value="0.00" style="background:var(--gray-100); font-weight:bold; color:var(--light-blue-dark);">
                    </div>
                    <div class="field">
                        <label>Punctuality</label>
                        <input type="text" id="punctuality_display" readonly value="—" style="background:var(--gray-100); font-weight:bold;">
                    </div>
                </div>

                <div class="field" style="margin-top: 12px;">
                    <label>Auto-Status</label>
                    <input type="text" id="status_display" readonly value="Waiting..." style="background:var(--gray-100); font-weight:bold;">
                </div>

                <div class="field" style="margin-top: 12px;">
                    <label>Remarks (optional)</label>
                    <input type="text" name="remarks" placeholder="e.g., Worked on documentation">
                </div>
                
                <div class="form-hint" style="margin-top:10px;">
                    <em>* 8+ hours automatically deducts 1 hour for lunch. Max standard time is 7:00 PM.</em>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm" onclick="closeLogModal()" style="background:var(--gray-200); color:var(--gray-800);">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Submit Log</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- VIEW DETAILS MODAL -->
<!-- ========================================== -->
<div class="modal-overlay" id="attendanceModal">
    <div class="modal-box" style="padding: 0;">
        <div class="modal-header" style="background:var(--light-blue-light); border-bottom:2px solid var(--light-blue);">
            <h3 style="color:var(--light-blue-dark);"><i class="fas fa-clipboard-list"></i> Attendance Details</h3>
            <button class="modal-close" onclick="closeModal('attendanceModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding: 0;">
            <div class="detail-row">
                <span class="detail-label">Date Logged</span>
                <span class="detail-value" id="m_date"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time In / Out</span>
                <span class="detail-value" id="m_times"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Punctuality</span>
                <span class="detail-value" id="m_punctuality"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Standard Hours</span>
                <span class="detail-value" id="m_standard_hours" style="color:var(--light-blue-dark);"></span>
            </div>
            <div class="detail-row overtime-row" id="m_overtime_row" style="display:none; background:#FFF8E1; border-left:4px solid var(--warning);">
                <span class="detail-label" style="color:#B26A00;">Overtime Hours</span>
                <span class="detail-value" id="m_overtime_hours" style="color:#B26A00; font-size:16px;"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value" id="m_status"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Remarks</span>
                <span class="detail-value" id="m_remarks" style="font-weight:normal; color:var(--gray-600); font-style:italic;"></span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-sm" onclick="closeModal('attendanceModal')" style="background:var(--gray-200); color:var(--gray-800);">Close</button>
        </div>
    </div>
</div>

<script>
    // --- Log Time Modal Logic ---
    const logModal = document.getElementById('logTimeModal');
    
    function openLogModal() {
        logModal.classList.add('open');
    }

    function closeLogModal() {
        logModal.classList.remove('open');
    }

    // --- View Details Modal Logic ---
    const viewModal = document.getElementById('attendanceModal');

    function viewAttendance(data) {
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

        document.getElementById('m_date').textContent = formatDate(data.log_date);
        document.getElementById('m_times').textContent = `${formatTime(data.time_in)} - ${formatTime(data.time_out)}`;
        document.getElementById('m_punctuality').textContent = data.punctuality || 'On Time';
        document.getElementById('m_status').textContent = data.status;
        document.getElementById('m_remarks').textContent = data.remarks || 'No remarks provided.';
        document.getElementById('m_standard_hours').textContent = parseFloat(data.total_hours).toFixed(2) + ' hours';

        const otHours = parseFloat(data.overtime_hours || 0);
        const otRow = document.getElementById('m_overtime_row');
        
        if (otHours > 0) {
            otRow.style.display = 'flex';
            document.getElementById('m_overtime_hours').textContent = otHours.toFixed(2) + ' hours';
        } else {
            otRow.style.display = 'none';
        }

        viewModal.classList.add('open');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }

    // Close on clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === logModal) closeLogModal();
        if (e.target === viewModal) closeModal('attendanceModal');
    });
</script>

<?php student_layout_bottom(); ?>