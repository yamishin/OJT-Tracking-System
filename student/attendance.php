<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

// Fetch Approved Schedule
$stmt = $pdo->prepare("SELECT * FROM student_schedules WHERE student_id = ? AND status = 'approved' ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
$stmt->execute([$studentId]);
$mySchedule = $stmt->fetchAll();

// Fetch Attendance Records
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY log_date DESC");
$stmt->execute([$studentId]);
$records = $stmt->fetchAll();

// Total VERIFIED hours only
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = ? AND verification_status = 'verified'");
$stmt->execute([$studentId]);
$totalHours = (float)$stmt->fetchColumn();

// Total PENDING hours
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = ? AND verification_status = 'pending'");
$stmt->execute([$studentId]);
$pendingHours = (float)$stmt->fetchColumn();

student_layout_top('Attendance', 'Log your daily time in and time out');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<!-- Header Panel with Schedule & Totals -->
<div class="panel" style="margin-bottom: 24px; background: linear-gradient(135deg, var(--light-blue-dark), var(--light-blue)); color: white; border: none;">
    <div class="panel-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="font-family: var(--font-heading); font-size: 22px; margin-bottom: 8px;">Log your hours</h2>
            
            <!-- Approved Schedule Tags -->
            <div style="margin-top: 8px;">
                <?php if (empty($mySchedule)): ?>
                    <span style="font-size:12px; background:rgba(255,255,255,0.2); padding:6px 12px; border-radius:4px;"><i class="fas fa-exclamation-circle"></i> No approved schedule yet</span>
                <?php else: 
                    foreach ($mySchedule as $ms): ?>
                        <span style="font-size:12px; background:rgba(255,255,255,0.2); padding:6px 12px; border-radius:4px; display:inline-block; margin-right:6px; margin-bottom:6px; border: 1px solid rgba(255,255,255,0.3);">
                            <strong><?= substr($ms['day_of_week'], 0, 3) ?></strong>: <?= date('h:i A', strtotime($ms['time_start'])) ?> - <?= date('h:i A', strtotime($ms['time_end'])) ?>
                        </span>
                    <?php endforeach; 
                endif; ?>
            </div>
        </div>
        
        <div style="display: flex; gap: 20px; text-align: right;">
            <div>
                <div style="font-size: 12px; text-transform: uppercase; opacity: 0.8;">Verified Hours</div>
                <div style="font-size: 24px; font-weight: 800; color: #E8F5E9;"><?= number_format($totalHours, 2) ?></div>
            </div>
            <?php if ($pendingHours > 0): ?>
            <div>
                <div style="font-size: 12px; text-transform: uppercase; opacity: 0.8;">Pending (Uncounted)</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--gray-300);"><?= number_format($pendingHours, 2) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <button type="button" class="btn btn-primary" onclick="openLogModal()" style="background: var(--white); color: var(--light-blue-dark); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="fas fa-clock"></i> Log Time
        </button>
    </div>
</div>

<!-- Attendance History Table -->
<div class="panel">
    <div class="panel-header" style="flex-wrap: wrap; gap: 10px;">
        <div>
            <h3>Attendance History</h3>
            <div class="sub" id="recordCount"><?= count($records) ?> record(s)</div>
        </div>
        
        <!-- Search and Filter -->
        <div style="display: flex; gap: 10px; margin-left: auto;">
            <div class="field" style="margin: 0; width: 180px;">
                <input type="text" id="searchDate" placeholder="Search date..." style="padding: 8px 12px; font-size: 13px;">
            </div>
            <div class="field" style="margin: 0;">
                <select id="filterStatus" style="padding: 8px 12px; font-size: 13px;">
                    <option value="all">All Statuses</option>
                    <option value="verified">Verified</option>
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table" id="attendanceTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time In / Out</th>
                        <th>Hours</th>
                        <th>Verification</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="5" class="empty">No attendance yet. Click "Log Time" to start.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): 
                            $v_status = $r['verification_status'] ?? 'pending';
                            $row_opacity = ($v_status === 'pending') ? '0.6' : '1';
                            $badge_color = ($v_status === 'verified') ? 'background:#E8F5E9; color:#1B5E20;' : (($v_status === 'rejected') ? 'background:var(--red-light); color:var(--red-dark);' : 'background:var(--gray-200); color:var(--gray-600);');
                        ?>
                            <tr class="att-row" data-status="<?= e($v_status) ?>" style="opacity: <?= $row_opacity ?>;">
                                <td class="att-date">
                                    <?= date('M d, Y (D)', strtotime($r['log_date'])) ?>
                                    <?php if ($v_status === 'pending'): ?>
                                        <br><small style="color:var(--gray-500);"><i class="fas fa-hourglass-half"></i> Waiting for Supervisor</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color:var(--gray-600);"><?= $r['time_in'] ? date('h:i A', strtotime($r['time_in'])) : '—' ?></span>
                                    <span style="margin:0 8px; color:var(--gray-400);">→</span>
                                    <span style="color:var(--gray-600);"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '—' ?></span>
                                </td>
                                <td><strong><?= number_format((float)$r['total_hours'], 2) ?></strong></td>
                                <td>
                                    <span class="badge" style="<?= $badge_color ?>">
                                        <i class="fas <?= $v_status === 'verified' ? 'fa-check-circle' : 'fa-clock' ?>"></i> 
                                        <?= ucfirst($v_status) ?>
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

<!-- LOG TIME MODAL -->
<div class="modal-overlay" id="logTimeModal">
    <div class="modal-box" style="max-width: 500px; padding: 0;">
        <form action="/PractiTrack/php/student/attendance-handler.php" method="POST" style="display: flex; flex-direction: column; max-height: 85vh;">
            <input type="hidden" name="action" value="log_attendance">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="modal-header">
                <h3><i class="fas fa-clock"></i> Log Attendance</h3>
                <button type="button" class="modal-close" onclick="closeLogModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info" style="margin-bottom:16px; font-size:13px;">
                    <i class="fas fa-info-circle"></i> Logged hours are <strong>Pending</strong> until your Company Supervisor verifies them.
                </div>

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
                    <label>Remarks (optional)</label>
                    <input type="text" name="remarks" placeholder="e.g., Worked on documentation">
                </div>
                <div class="form-hint" style="margin-top:10px;">
                    <em>* 8+ hrs deducts 1 hr lunch. Overtime must be a full 1 hr to count. Max time is 7:00 PM.</em>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm" onclick="closeLogModal()" style="background:var(--gray-200); color:var(--gray-800);">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Submit Log</button>
            </div>
        </form>
    </div>
</div>

<!-- VIEW DETAILS MODAL -->
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
                <span class="detail-label">Hours Rendered</span>
                <span class="detail-value" id="m_hours" style="color:var(--light-blue-dark);"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Verification Status</span>
                <span class="detail-value" id="m_verification"></span>
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
    // --- Modal Logic ---
    const logModal = document.getElementById('logTimeModal');
    function openLogModal() { logModal.classList.add('open'); }
    function closeLogModal() { logModal.classList.remove('open'); }

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
        document.getElementById('m_hours').textContent = parseFloat(data.total_hours).toFixed(2) + ' hours';
        document.getElementById('m_remarks').textContent = data.remarks || 'No remarks provided.';
        
        let vHtml = '';
        if (data.verification_status === 'verified') vHtml = '<span class="badge" style="background:#E8F5E9; color:#1B5E20;">Verified</span>';
        else if (data.verification_status === 'rejected') vHtml = '<span class="badge" style="background:var(--red-light); color:var(--red-dark);">Rejected</span>';
        else vHtml = '<span class="badge" style="background:var(--gray-200); color:var(--gray-600);">Pending</span>';
        
        document.getElementById('m_verification').innerHTML = vHtml;
        viewModal.classList.add('open');
    }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    
    window.addEventListener('click', (e) => {
        if (e.target === logModal) closeLogModal();
        if (e.target === viewModal) closeModal('attendanceModal');
    });

    // --- Search & Filter ---
    const searchInput = document.getElementById('searchDate');
    const filterStatus = document.getElementById('filterStatus');
    const rows = document.querySelectorAll('.att-row');
    const countDisplay = document.getElementById('recordCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusTerm = filterStatus.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const dateText = row.querySelector('.att-date').textContent.toLowerCase();
            const rowStatus = row.getAttribute('data-status');

            const matchesSearch = dateText.includes(searchTerm);
            const matchesStatus = (statusTerm === 'all') || (rowStatus === statusTerm);

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        countDisplay.textContent = `${visibleCount} record(s)`;
    }

    if (searchInput && filterStatus) {
        searchInput.addEventListener('input', filterTable);
        filterStatus.addEventListener('change', filterTable);
    }
</script>

<?php student_layout_bottom(); ?>