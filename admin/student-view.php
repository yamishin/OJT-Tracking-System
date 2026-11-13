<?php
require_once __DIR__ . '/../includes/admin-layout.php';

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
    redirect('/PractiTrack/admin/students.php');
}

// 2. Calculate Progress
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

admin_layout_top('Student Profile', 'Full system overview');
?>

<style>
    .tab-container { display: flex; border-bottom: 2px solid var(--gray-200); margin-bottom: 20px; gap: 10px; overflow-x: auto; }
    .tab-btn { background: transparent; border: none; padding: 12px 20px; font-size: 14px; font-weight: 600; color: var(--gray-500); cursor: pointer; position: relative; transition: all 0.2s ease; white-space: nowrap; }
    .tab-btn:hover { color: var(--gray-800); }
    .tab-btn.active { color: var(--light-blue-dark); }
    .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background: var(--light-blue-dark); }
    .tab-content { display: none; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    
    .docs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; padding: 20px 0; }
    .doc-box { background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); padding: 20px; display: flex; flex-direction: column; transition: all 0.3s ease; }
    .doc-box:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--light-blue); }
    .doc-icon { width: 44px; height: 44px; border-radius: var(--radius-md); background: var(--light-blue-light); color: var(--light-blue-dark); display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; }
    .doc-title { font-size: 15px; font-weight: 700; color: var(--gray-800); margin-bottom: 4px; }
    .doc-file { font-size: 12px; color: var(--gray-400); margin-bottom: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .doc-footer { margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px dashed var(--gray-200); }
</style>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="/PractiTrack/admin/students.php" style="color:var(--gray-600); font-size:14px; font-weight:600;"><i class="fas fa-arrow-left"></i> Back to Student List</a>
    <span class="badge" style="background: <?= $student['is_active'] ? '#E8F5E9; color:#1B5E20;' : 'var(--red-light); color:var(--red-dark);' ?>; padding: 6px 12px; font-size:13px;">
        <i class="fas <?= $student['is_active'] ? 'fa-check-circle' : 'fa-ban' ?>"></i> 
        <?= $student['is_active'] ? 'Active Account' : 'Deactivated Account' ?>
    </span>
</div>

<!-- TOP SUMMARY CARDS -->
<div class="grid-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 28px;">
    <div class="panel" style="margin-bottom: 0;">
        <div class="panel-header"><h3><i class="fas fa-id-card"></i> Profile Details</h3></div>
        <div class="panel-body">
            <p style="margin-bottom:10px;"><strong>Name:</strong> <?= e($student['first_name'] . ' ' . $student['last_name']) ?></p>
            <p style="margin-bottom:10px;"><strong>USN:</strong> <?= e($student['student_no']) ?></p>
            <p style="margin-bottom:10px;"><strong>Course:</strong> <?= e($student['course'] ?? '—') ?></p>
            <p style="margin-bottom:0;"><strong>Company:</strong> <?= $student['company_name'] ? '<span style="color:var(--light-blue-dark); font-weight:bold;">'.e($student['company_name']).'</span>' : '<em style="color:var(--gray-400);">Unassigned</em>' ?></p>
        </div>
    </div>

    <div class="panel" style="margin-bottom: 0;">
        <div class="panel-header"><h3><i class="fas fa-chart-line"></i> OJT Progress</h3></div>
        <div class="panel-body" style="display:flex; flex-direction:column; justify-content:center;">
            <div style="text-align: center; margin-bottom: 16px;">
                <div style="font-size: 36px; font-weight: 800; color: var(--light-blue-dark);"><?= $progress ?>%</div>
                <div style="font-size: 14px; color: var(--gray-600); font-weight: 600;"><?= number_format($renderedHours, 1) ?> / <?= $requiredHours ?> Hours</div>
            </div>
            <div class="progress-block" style="margin:0;">
                <div class="pb-bar" style="height: 12px;">
                    <div class="pb-fill" style="width: <?= $progress ?>%;"></div>
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
            <button class="tab-btn active" onclick="openTab(this, 'attendance')"><i class="fas fa-calendar-check"></i> Attendance</button>
            <button class="tab-btn" onclick="openTab(this, 'documents')"><i class="fas fa-folder-open"></i> Documents</button>
            <button class="tab-btn" onclick="openTab(this, 'reports')"><i class="fas fa-file-alt"></i> Reports</button>
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
                            <th>Status</th>
                            <th>Verification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance)): ?>
                            <tr><td colspan="5" class="empty">No attendance logs.</td></tr>
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
                                    <td><span class="badge <?= strtolower(str_replace(' ', '', $a['status'])) ?>"><?= e($a['status']) ?></span></td>
                                    <td>
                                        <span class="badge" style="background: <?= $v_status === 'verified' ? '#E8F5E9; color:#1B5E20;' : ($v_status === 'rejected' ? 'var(--red-light); color:var(--red-dark);' : 'var(--gray-200); color:var(--gray-600);') ?>">
                                            <?= ucfirst($v_status) ?>
                                        </span>
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
                            <div class="doc-footer">
                                <span style="font-size:12px; color:var(--gray-400);"><?= date('M d, Y', strtotime($d['submitted_at'])) ?></span>
                                <a href="/PractiTrack/<?= e($d['file_path']) ?>" target="_blank" class="btn btn-sm btn-ghost"><i class="fas fa-eye"></i> View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 3: Weekly Reports -->
        <div id="tab-reports" class="tab-content">
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Dates</th><th>Type</th><th>Hours</th><th>Status</th><th>Action</th></tr></thead>
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
                                    <td>
                                        <!-- VIEW BUTTON triggers the modal -->
                                        <button type="button" class="btn btn-sm btn-ghost" onclick="viewReport(<?= htmlspecialchars(json_encode($r)) ?>)">
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
</div>

<!-- ========================================== -->
<!-- VIEW REPORT MODAL (With Evaluation Form) -->
<!-- ========================================== -->
<div class="modal-overlay" id="viewReportModal">
    <div class="modal-box" style="max-width: 600px; padding: 0;">
        <div class="modal-header">
            <h3><i class="fas fa-file-alt"></i> Report Details</h3>
            <button type="button" class="modal-close" onclick="closeViewModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="modal-body">
            <div class="detail-row" style="padding: 12px 0; border-bottom: 1px solid var(--gray-200);">
                <span class="detail-label" style="font-weight:600; color:var(--gray-600);">Type:</span>
                <span class="detail-value" id="v_type" style="font-weight:bold; text-transform: capitalize;"></span>
            </div>
            <div class="detail-row" style="padding: 12px 0; border-bottom: 1px solid var(--gray-200);">
                <span class="detail-label" style="font-weight:600; color:var(--gray-600);">Dates Covered:</span>
                <span class="detail-value" id="v_dates" style="font-weight:bold;"></span>
            </div>
            <div class="detail-row" style="padding: 12px 0; border-bottom: 1px solid var(--gray-200);">
                <span class="detail-label" style="font-weight:600; color:var(--gray-600);">Status:</span>
                <span class="detail-value" id="v_status" style="font-weight:bold;"></span>
            </div>
            
            <div style="margin-top: 20px;">
                <h4 style="font-size:14px; color:var(--gray-800); margin-bottom:8px;"><i class="fas fa-tasks" style="color:var(--light-blue);"></i> Activities Performed</h4>
                <div id="v_activities" style="background:var(--gray-100); padding:12px; border-radius:var(--radius-sm); font-size:14px; line-height:1.6; color:var(--gray-600);"></div>
            </div>
            
            <div style="margin-top: 20px;">
                <h4 style="font-size:14px; color:var(--gray-800); margin-bottom:8px;"><i class="fas fa-lightbulb" style="color:var(--warning);"></i> Learnings / Insights</h4>
                <div id="v_learnings" style="background:var(--gray-100); padding:12px; border-radius:var(--radius-sm); font-size:14px; line-height:1.6; color:var(--gray-600);"></div>
            </div>

            <!-- COMPANY EVALUATION FORM (Shows only if submitted) -->
            <div id="eval_section" style="margin-top: 24px; display: none; border-top: 2px dashed var(--gray-200); padding-top: 20px;">
                <h4 style="font-size:14px; color:var(--gray-800); margin-bottom:12px;"><i class="fas fa-clipboard-check" style="color:var(--success);"></i> Evaluate this Report</h4>
                
                <form action="/PractiTrack/php/company/evaluate-handler.php" method="POST">
                    <input type="hidden" name="action" value="weekly_eval">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="student_id" id="v_student_id">
                    <input type="hidden" name="report_id" id="v_report_id">
                    
                    <div class="field">
                        <label>Feedback / Remarks</label>
                        <textarea name="feedback" placeholder="Enter your feedback here..." required style="min-height: 80px;"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button type="submit" name="decision" value="approved" class="btn" style="background: #E8F5E9; color: #1B5E20; flex: 1;">Approve Report</button>
                        <button type="submit" name="decision" value="rejected" class="btn" style="background: var(--red-light); color: var(--red-dark); flex: 1;">Reject Report</button>
                    </div>
                </form>
            </div>
            
            <!-- FEEDBACK VIEW (Shows only if already evaluated) -->
            <div id="feedback_section" style="margin-top: 20px; display: none;">
                <h4 style="font-size:14px; color:var(--gray-800); margin-bottom:8px;"><i class="fas fa-comment-dots" style="color:var(--red);"></i> Company Feedback</h4>
                <div id="v_feedback" style="background:#FFF8E1; border-left: 4px solid var(--warning); padding:12px; border-radius:var(--radius-sm); font-size:14px; line-height:1.6; color:#B26A00;"></div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-sm" onclick="closeViewModal()" style="background:var(--gray-200); color:var(--gray-800);">Close</button>
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

    const viewReportModal = document.getElementById('viewReportModal');

    function viewReport(data) {
        document.getElementById('v_type').textContent = data.report_type;
        const start = new Date(data.week_start).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const end = new Date(data.week_end).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        document.getElementById('v_dates').textContent = `${start} - ${end}`;
        document.getElementById('v_status').innerHTML = `<span class="badge ${data.status}">${data.status}</span>`;
        document.getElementById('v_activities').innerHTML = data.activities.replace(/\n/g, '<br>');
        document.getElementById('v_learnings').innerHTML = data.learnings ? data.learnings.replace(/\n/g, '<br>') : '<em>No learnings provided.</em>';

        // Form Setup
        document.getElementById('v_student_id').value = data.student_id;
        document.getElementById('v_report_id').value = data.id;

        const evalSection = document.getElementById('eval_section');
        const feedbackSection = document.getElementById('feedback_section');

        if (data.status === 'submitted') {
            // Show Evaluate Form
            evalSection.style.display = 'block';
            feedbackSection.style.display = 'none';
        } else {
            // Show Feedback
            evalSection.style.display = 'none';
            feedbackSection.style.display = 'block';
            document.getElementById('v_feedback').innerHTML = data.company_feedback ? data.company_feedback.replace(/\n/g, '<br>') : 'No feedback provided.';
        }

        viewReportModal.classList.add('open');
    }

    function closeViewModal() {
        viewReportModal.classList.remove('open');
    }
</script>

<?php admin_layout_bottom(); ?>