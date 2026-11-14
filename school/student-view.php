<?php
require_once __DIR__ . '/../includes/school-layout.php';

$studentId = (int)($_GET['id'] ?? 0);

// 1. Fetch Student Info
$stmt = $pdo->prepare("
    SELECT u.*, sp.student_no, sp.course, sp.required_hours, sp.company_id, cp.company_name 
    FROM users u 
    JOIN student_profiles sp ON sp.user_id = u.id 
    LEFT JOIN company_profiles cp ON cp.user_id = sp.company_id
    WHERE u.id = ? AND u.role = 'student'
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('/PractiTrack/school/students.php');
}

// 2. Calculate Progress (Verified only)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = ? AND verification_status = 'verified'");
$stmt->execute([$studentId]);
$renderedHours = (float)$stmt->fetchColumn();
$requiredHours = (int)$student['required_hours'];
$progress = $requiredHours > 0 ? min(100, round(($renderedHours / $requiredHours) * 100)) : 0;

// 3. Fetch Documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$studentId]);
$documents = $stmt->fetchAll();

// 4. Fetch Weekly Reports
$stmt = $pdo->prepare("SELECT * FROM weekly_reports WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$studentId]);
$reports = $stmt->fetchAll();

// 5. Fetch Attendance
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY log_date DESC");
$stmt->execute([$studentId]);
$attendance = $stmt->fetchAll();

// 6. Fetch Final Evaluation
$stmt = $pdo->prepare("SELECT * FROM final_evaluations WHERE student_id = ?");
$stmt->execute([$studentId]);
$finalEval = $stmt->fetch();

school_layout_top('Student Profile', 'Review progress, documents, and evaluations');
?>

<style>
    .tab-container { display: flex; border-bottom: 2px solid var(--gray-200); margin-bottom: 20px; gap: 10px; overflow-x: auto; }
    .tab-btn { background: transparent; border: none; padding: 12px 20px; font-size: 14px; font-weight: 600; color: var(--gray-500); cursor: pointer; position: relative; transition: all 0.2s ease; white-space: nowrap; }
    .tab-btn:hover { color: var(--gray-800); }
    .tab-btn.active { color: #0277BD; }
    .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background: #0277BD; }
    .tab-content { display: none; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    
    .docs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; padding: 10px 0; }
    .doc-box { background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); padding: 20px; display: flex; flex-direction: column; transition: all 0.3s ease; }
    .doc-box:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: #0277BD; }
    .doc-icon { width: 44px; height: 44px; border-radius: var(--radius-md); background: #E0F2FE; color: #0277BD; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; }
    .doc-title { font-size: 15px; font-weight: 700; color: var(--gray-800); margin-bottom: 4px; }
    .doc-file { font-size: 12px; color: var(--gray-400); margin-bottom: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .doc-footer { margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px dashed var(--gray-200); }
</style>

<div style="margin-bottom: 20px;">
    <a href="/PractiTrack/school/students.php" style="color:var(--gray-600); font-size:14px; font-weight:600;"><i class="fas fa-arrow-left"></i> Back to Student List</a>
</div>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<!-- TOP SUMMARY CARDS -->
<div class="grid-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 28px;">
    
    <div class="panel" style="margin-bottom: 0;">
        <div class="panel-header"><h3><i class="fas fa-id-card"></i> Profile Details</h3></div>
        <div class="panel-body">
            <p style="margin-bottom:10px;"><strong>Name:</strong> <?= e($student['first_name'] . ' ' . $student['last_name']) ?></p>
            <p style="margin-bottom:10px;"><strong>USN:</strong> <?= e($student['student_no']) ?></p>
            <p style="margin-bottom:10px;"><strong>Course:</strong> <?= e($student['course'] ?? '—') ?></p>
            <p style="margin-bottom:10px;"><strong>Email:</strong> <?= e($student['email']) ?></p>
            <p style="margin-bottom:0;"><strong>Company:</strong> <?= $student['company_name'] ? '<span style="color:#0277BD; font-weight:bold;">'.e($student['company_name']).'</span>' : '<em style="color:var(--gray-400);">Unassigned</em>' ?></p>
        </div>
    </div>

    <div class="panel" style="margin-bottom: 0;">
        <div class="panel-header"><h3><i class="fas fa-chart-line"></i> OJT Progress</h3></div>
        <div class="panel-body" style="display:flex; flex-direction:column; justify-content:center;">
            <div style="text-align: center; margin-bottom: 16px;">
                <div style="font-size: 36px; font-weight: 800; color: #0277BD;"><?= $progress ?>%</div>
                <div style="font-size: 14px; color: var(--gray-600); font-weight: 600;"><?= number_format($renderedHours, 1) ?> / <?= $requiredHours ?> Hours</div>
            </div>
            <div class="progress-block" style="margin:0;">
                <div class="pb-bar" style="height: 12px;">
                    <div class="pb-fill" style="width: <?= $progress ?>%; background: #0277BD;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-bottom: 0;">
        <div class="panel-header"><h3><i class="fas fa-award"></i> Final Grade</h3></div>
        <div class="panel-body" style="display:flex; flex-direction:column; justify-content:center; text-align:center;">
            <?php if ($finalEval): ?>
                <div style="font-size: 40px; font-weight: 800; color: var(--success); margin-bottom: 8px;"><?= $finalEval['score'] ?><span style="font-size:20px; color:var(--gray-400);">/100</span></div>
                <p style="font-size: 13px; color: var(--gray-600); font-style: italic;">"<?= e($finalEval['remarks'] ?? 'No remarks') ?>"</p>
            <?php else: ?>
                <div style="color: var(--gray-300); font-size: 40px; margin-bottom: 8px;"><i class="fas fa-hourglass-half"></i></div>
                <p style="font-size: 13px; color: var(--gray-400);">Not yet evaluated by company.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- TABBED SECTION -->
<div class="panel" style="padding: 0;">
    <div class="panel-body" style="padding: 20px 24px 24px;">
        
        <div class="tab-container">
            <button class="tab-btn active" onclick="openTab(this, 'attendance')"><i class="fas fa-calendar-check"></i> Attendance Log</button>
            <button class="tab-btn" onclick="openTab(this, 'documents')"><i class="fas fa-folder-open"></i> Documents</button>
            <button class="tab-btn" onclick="openTab(this, 'reports')"><i class="fas fa-file-alt"></i> Weekly Reports</button>
        </div>

        <!-- Tab 1: Attendance -->
        <div id="tab-attendance" class="tab-content active">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time In / Out</th>
                            <th>Hours</th>
                            <th>Verification</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance)): ?>
                            <tr><td colspan="6" class="empty">No attendance logs.</td></tr>
                        <?php else: ?>
                            <?php foreach ($attendance as $a): 
                                $v_status = $a['verification_status'] ?? 'pending';
                            ?>
                                <tr>
                                    <td><?= date('M d, Y (D)', strtotime($a['log_date'])) ?></td>
                                    <td>
                                        <?= $a['time_in'] ? date('h:i A', strtotime($a['time_in'])) : '—' ?>
                                        <span style="color:var(--gray-400); margin:0 4px;">→</span>
                                        <?= $a['time_out'] ? date('h:i A', strtotime($a['time_out'])) : '—' ?>
                                    </td>
                                    <td><strong><?= number_format((float)$a['total_hours'], 2) ?></strong></td>
                                    <td>
                                        <span class="badge" style="background: <?= $v_status === 'verified' ? '#E8F5E9; color:#1B5E20;' : ($v_status === 'rejected' ? 'var(--red-light); color:var(--red-dark);' : 'var(--gray-200); color:var(--gray-600);') ?>">
                                            <?= ucfirst($v_status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:
                                            <?php if($a['status'] == 'Whole Day') echo '#E8F5E9; color:#1B5E20;';
                                                  elseif($a['status'] == 'Undertime') echo '#FFF8E1; color:#B26A00;';
                                                  else echo '#FEE2E2; color:#991B1B;'; ?>">
                                            <?= e($a['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-ghost" onclick="viewAttendance(<?= htmlspecialchars(json_encode($a)) ?>)">
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

        <!-- Tab 2: Documents -->
        <div id="tab-documents" class="tab-content">
            <div class="docs-grid">
                <?php if (empty($documents)): ?>
                    <div class="empty-state" style="grid-column: 1/-1;">No documents uploaded.</div>
                <?php else: ?>
                    <?php foreach ($documents as $d): 
                        $ext = strtolower(pathinfo($d['file_name'], PATHINFO_EXTENSION));
                        $icon = ($ext === 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
                    ?>
                        <div class="doc-box">
                            <div style="display:flex; justify-content:space-between;">
                                <div class="doc-icon"><i class="fas <?= $icon ?>"></i></div>
                                <span class="badge <?= e($d['status']) ?>"><?= e($d['status']) ?></span>
                            </div>
                            <div class="doc-title"><?= e($d['doc_type']) ?></div>
                            <div class="doc-file"><?= e($d['file_name']) ?></div>
                            
                            <?php if ($d['status'] === 'pending'): ?>
                                <form action="/PractiTrack/php/school/document-handler.php" method="POST" style="margin-top: 10px; border-top: 1px dashed var(--gray-200); padding-top: 10px;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                    <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                                    
                                    <div class="field" style="margin-bottom: 8px;">
                                        <input type="text" name="remarks" placeholder="Remarks (optional)..." style="padding: 6px; font-size: 12px; width: 100%; border: 1px solid var(--gray-300); border-radius: 4px;">
                                    </div>
                                    <div style="display: flex; gap: 6px;">
                                        <button type="submit" name="decision" value="approved" class="btn btn-sm" style="background: #E8F5E9; color: #1B5E20; flex: 1;">Approve</button>
                                        <button type="submit" name="decision" value="rejected" class="btn btn-sm" style="background: var(--red-light); color: var(--red-dark); flex: 1;">Reject</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="doc-footer">
                                    <span style="font-size:12px; color:var(--gray-400);"><?= date('M d, Y', strtotime($d['submitted_at'])) ?></span>
                                    <a href="/PractiTrack/<?= e($d['file_path']) ?>" target="_blank" class="btn btn-sm btn-ghost" style="padding:6px 10px;">
                                        <i class="fas fa-eye"></i> View File
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 3: Weekly Reports -->
        <div id="tab-reports" class="tab-content">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Dates Covered</th>
                            <th>Type</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Company Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr><td colspan="5" class="empty">No reports submitted.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td><?= date('M d', strtotime($r['week_start'])) ?> - <?= date('M d, Y', strtotime($r['week_end'])) ?></td>
                                    <td><span class="badge" style="background:#F3F4F6; color:#374151;"><?= ucfirst($r['report_type']) ?></span></td>
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
</div>

<!-- VIEW ATTENDANCE MODAL -->
<div class="modal-overlay" id="viewAttModal">
    <div class="modal-box" style="max-width: 500px; padding: 0;">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-check"></i> Attendance Details</h3>
            <button type="button" class="modal-close" onclick="closeAttModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="modal-body">
            <div class="detail-row">
                <span class="detail-label">Date</span>
                <span class="detail-value" id="att_date"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time In / Out</span>
                <span class="detail-value" id="att_times"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Hours</span>
                <span class="detail-value" id="att_hours" style="color:var(--light-blue-dark); font-size:16px;"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Overtime</span>
                <span class="detail-value" id="att_ot" style="color:var(--warning); font-weight:bold;"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Verification Status</span>
                <span class="detail-value" id="att_verification"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Remarks</span>
                <span class="detail-value" id="att_remarks" style="font-weight:normal; color:var(--gray-600); font-style:italic;"></span>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-sm" onclick="closeAttModal()" style="background:var(--gray-200); color:var(--gray-800);">Close</button>
        </div>
    </div>
</div>

<script>
    function openTab(btn, tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tabName).classList.add('active');
        btn.classList.add('active');
    }

    const viewAttModal = document.getElementById('viewAttModal');
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

        document.getElementById('att_date').textContent = formatDate(data.log_date);
        document.getElementById('att_times').textContent = `${formatTime(data.time_in)} - ${formatTime(data.time_out)}`;
        document.getElementById('att_hours').textContent = parseFloat(data.total_hours).toFixed(2) + ' hours';
        document.getElementById('att_ot').textContent = data.overtime_hours > 0 ? parseFloat(data.overtime_hours).toFixed(2) + ' hours' : 'None';
        document.getElementById('att_remarks').textContent = data.remarks || 'No remarks provided.';
        
        let vHtml = '';
        if (data.verification_status === 'verified') vHtml = '<span class="badge" style="background:#E8F5E9; color:#1B5E20;">Verified</span>';
        else if (data.verification_status === 'rejected') vHtml = '<span class="badge" style="background:var(--red-light); color:var(--red-dark);">Rejected</span>';
        else vHtml = '<span class="badge" style="background:var(--gray-200); color:var(--gray-600);">Pending</span>';
        
        document.getElementById('att_verification').innerHTML = vHtml;
        viewAttModal.classList.add('open');
    }
    function closeAttModal() { viewAttModal.classList.remove('open'); }
</script>

<?php school_layout_bottom(); ?>