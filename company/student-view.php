<?php
require_once __DIR__ . '/../includes/company-layout.php';

$companyId = $_SESSION['user_id'];
$studentId = (int)($_GET['id'] ?? 0);

// Verify student belongs to this company and is accepted
$stmt = $pdo->prepare("SELECT u.*, sp.* FROM users u JOIN student_profiles sp ON sp.user_id = u.id WHERE u.id = ? AND sp.company_id = ? AND sp.application_status = 'accepted'");
$stmt->execute([$studentId, $companyId]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student not found or not assigned to you.');
    redirect('/PractiTrack/company/students.php');
}

// Fetch reports
$stmt = $pdo->prepare("SELECT * FROM weekly_reports WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$studentId]);
$reports = $stmt->fetchAll();

// Fetch documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$studentId]);
$documents = $stmt->fetchAll();

// Fetch attendance
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY log_date DESC");
$stmt->execute([$studentId]);
$attendance = $stmt->fetchAll();

// Calculate Progress (Verified only)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = ? AND verification_status = 'verified'");
$stmt->execute([$studentId]);
$renderedHours = (float)$stmt->fetchColumn();
$progress = $student['required_hours'] > 0 ? min(100, round(($renderedHours / $student['required_hours']) * 100)) : 0;

// Fetch final evaluation
$stmt = $pdo->prepare("SELECT * FROM final_evaluations WHERE student_id = ? AND company_id = ?");
$stmt->execute([$studentId, $companyId]);
$finalEval = $stmt->fetch();

company_layout_top('Student Evaluation', 'Review and evaluate intern');
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
    
    .docs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; padding: 10px 0; }
    .doc-box { background: var(--white); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); padding: 16px; display: flex; flex-direction: column; transition: all 0.3s ease; }
    .doc-box:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--light-blue); }
    .doc-icon { width: 36px; height: 36px; border-radius: var(--radius-sm); background: var(--light-blue-light); color: var(--light-blue-dark); display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
    .doc-title { font-size: 14px; font-weight: 700; color: var(--gray-800); margin-bottom: 4px; }
    .doc-file { font-size: 12px; color: var(--gray-400); margin-bottom: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .doc-footer { margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px dashed var(--gray-200); }
</style>

<div style="margin-bottom: 20px;">
    <a href="/PractiTrack/company/students.php" style="color:var(--gray-600); font-size:14px; font-weight:600;"><i class="fas fa-arrow-left"></i> Back to Students</a>
</div>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<!-- TOP SUMMARY CARDS -->
<div class="grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-bottom: 28px;">
    
    <!-- Student Info -->
    <div class="panel" style="margin-bottom: 0;">
        <div class="panel-header"><h3><i class="fas fa-user-graduate"></i> Intern Details</h3></div>
        <div class="panel-body">
            <p style="margin-bottom:10px;"><strong>Name:</strong> <?= e($student['first_name'] . ' ' . $student['last_name']) ?></p>
            <p style="margin-bottom:10px;"><strong>USN:</strong> <?= e($student['student_no']) ?></p>
            <p style="margin-bottom:10px;"><strong>Course:</strong> <?= e($student['course']) ?></p>
            <p style="margin-bottom:0;"><strong>Email:</strong> <?= e($student['email']) ?></p>
        </div>
    </div>

    <!-- Progress -->
    <div class="panel" style="margin-bottom: 0;">
        <div class="panel-header"><h3><i class="fas fa-chart-line"></i> OJT Progress</h3></div>
        <div class="panel-body" style="display:flex; flex-direction:column; justify-content:center;">
            <div style="text-align: center; margin-bottom: 16px;">
                <div style="font-size: 36px; font-weight: 800; color: var(--light-blue-dark);"><?= $progress ?>%</div>
                <div style="font-size: 14px; color: var(--gray-600); font-weight: 600;"><?= number_format($renderedHours, 1) ?> / <?= $student['required_hours'] ?> Hours</div>
            </div>
            <div class="progress-block" style="margin:0;">
                <div class="pb-bar" style="height: 12px;">
                    <div class="pb-fill" style="width: <?= $progress ?>%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Final Evaluation Form / Result -->
    <div class="panel" style="margin-bottom: 0;">
        <div class="panel-header"><h3><i class="fas fa-award"></i> Final Evaluation</h3></div>
        <div class="panel-body">
            <?php if ($finalEval): ?>
                <div class="alert alert-success" style="margin-bottom:0; padding: 12px;">
                    <i class="fas fa-trophy" style="font-size:24px; color:var(--success);"></i>
                    <div>
                        <strong style="font-size:16px;">Score: <?= $finalEval['score'] ?> / 100</strong>
                        <p style="margin-top:4px; font-size:12px;"><?= e($finalEval['remarks']) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <form action="/PractiTrack/php/company/evaluate-handler.php" method="POST">
                    <input type="hidden" name="action" value="final_eval">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                    
                    <div class="field" style="margin-bottom:8px;">
                        <input type="number" name="score" min="1" max="100" placeholder="Score (1-100)" required style="padding:8px;">
                    </div>
                    <div class="field" style="margin-bottom:8px;">
                        <input type="text" name="remarks" placeholder="Remarks..." required style="padding:8px;">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary" style="width:100%;">Submit Grade</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- TABBED SECTION -->
<div class="panel" style="padding: 0;">
    <div class="panel-body" style="padding: 20px 24px 24px;">
        
        <div class="tab-container">
            <button class="tab-btn active" onclick="openTab(this, 'reports')"><i class="fas fa-file-alt"></i> Weekly Reports</button>
            <button class="tab-btn" onclick="openTab(this, 'documents')"><i class="fas fa-folder-open"></i> Documents</button>
            <button class="tab-btn" onclick="openTab(this, 'attendance')"><i class="fas fa-calendar-check"></i> Attendance Log</button>
        </div>

        <!-- Tab 1: Weekly Reports -->
        <div id="tab-reports" class="tab-content active">
            
            <!-- Filter Reports -->
            <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                <div class="field" style="margin: 0; width: 200px;">
                    <input type="text" id="searchRep" placeholder="Search dates..." style="padding: 8px 12px; font-size: 13px;">
                </div>
                <div class="field" style="margin: 0;">
                    <select id="filterRep" style="padding: 8px 12px; font-size: 13px;">
                        <option value="all">All Statuses</option>
                        <option value="submitted">Submitted</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Dates Covered</th>
                            <th>Type</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr><td colspan="5" class="empty">No reports submitted yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                                <tr class="row-rep" data-status="<?= e($r['status']) ?>">
                                    <td class="search-date"><?= date('M d', strtotime($r['week_start'])) ?> - <?= date('M d', strtotime($r['week_end'])) ?></td>
                                    <td><?= ucfirst($r['report_type']) ?></td>
                                    <td><?= number_format((float)$r['hours_rendered'], 2) ?></td>
                                    <td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                                    <td>
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

        <!-- Tab 2: Documents (Box Design) -->
        <div id="tab-documents" class="tab-content">
            
            <!-- Filter Documents -->
            <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                <div class="field" style="margin: 0; width: 200px;">
                    <input type="text" id="searchDoc" placeholder="Search document type..." style="padding: 8px 12px; font-size: 13px;">
                </div>
                <div class="field" style="margin: 0;">
                    <select id="filterDoc" style="padding: 8px 12px; font-size: 13px;">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="docs-grid">
                <?php if (empty($documents)): ?>
                    <div class="empty-state" style="grid-column: 1/-1;">No documents uploaded by this student.</div>
                <?php else: ?>
                    <?php foreach ($documents as $d): 
                        $ext = strtolower(pathinfo($d['file_name'], PATHINFO_EXTENSION));
                        $icon = ($ext === 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
                    ?>
                        <div class="doc-box row-doc" data-status="<?= e($d['status']) ?>">
                            <div style="display:flex; justify-content:space-between;">
                                <div class="doc-icon"><i class="fas <?= $icon ?>"></i></div>
                                <span class="badge <?= e($d['status']) ?>"><?= e($d['status']) ?></span>
                            </div>
                            <div class="doc-title search-doctype"><?= e($d['doc_type']) ?></div>
                            <div class="doc-file"><?= e($d['file_name']) ?></div>
                            <div class="doc-footer">
                                <span style="font-size:12px; color:var(--gray-400);"><?= date('M d, Y', strtotime($d['submitted_at'])) ?></span>
                                <a href="/PractiTrack/<?= e($d['file_path']) ?>" target="_blank" class="btn btn-sm btn-ghost" style="padding:6px 10px;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 3: Attendance -->
        <div id="tab-attendance" class="tab-content">
            
            <!-- Filter Attendance -->
            <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                <div class="field" style="margin: 0; width: 200px;">
                    <input type="text" id="searchAtt" placeholder="Search date..." style="padding: 8px 12px; font-size: 13px;">
                </div>
                <div class="field" style="margin: 0;">
                    <select id="filterAtt" style="padding: 8px 12px; font-size: 13px;">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table">
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
                        <?php if (empty($attendance)): ?>
                            <tr><td colspan="5" class="empty">No attendance logs recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($attendance as $a): 
                                $v_status = $a['verification_status'] ?? 'pending';
                            ?>
                                <tr class="row-att" data-status="<?= e($v_status) ?>">
                                    <td class="search-date"><?= date('M d, Y (D)', strtotime($a['log_date'])) ?></td>
                                    <td>
                                        <?= $a['time_in'] ? date('h:i A', strtotime($a['time_in'])) : '—' ?>
                                        <span style="margin:0 4px; color:var(--gray-400);">→</span>
                                        <?= $a['time_out'] ? date('h:i A', strtotime($a['time_out'])) : '—' ?>
                                    </td>
                                    <td><strong><?= number_format((float)$a['total_hours'], 2) ?></strong></td>
                                    <td>
                                        <span class="badge" style="background: <?= $v_status === 'verified' ? '#E8F5E9; color:#1B5E20;' : ($v_status === 'rejected' ? 'var(--red-light); color:var(--red-dark);' : 'var(--gray-200); color:var(--gray-600);') ?>">
                                            <?= ucfirst($v_status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <!-- VIEW BUTTON FOR ATTENDANCE -->
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

<!-- ========================================== -->
<!-- VIEW ATTENDANCE MODAL (With Verify Form) -->
<!-- ========================================== -->
<div class="modal-overlay" id="viewAttModal">
    <div class="modal-box" style="max-width: 500px; padding: 0;">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-check"></i> DTR Verification</h3>
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
                <span class="detail-label">Status</span>
                <span class="detail-value" id="att_status"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Remarks</span>
                <span class="detail-value" id="att_remarks" style="font-weight:normal; color:var(--gray-600); font-style:italic;"></span>
            </div>

            <!-- VERIFY FORM -->
            <div id="verify_section" style="margin-top: 24px; display: none; border-top: 2px dashed var(--gray-200); padding-top: 20px;">
                <h4 style="font-size:14px; color:var(--gray-800); margin-bottom:12px;"><i class="fas fa-check-double" style="color:var(--success);"></i> Action</h4>
                
                <form action="/PractiTrack/php/company/verify-attendance.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="attendance_id" id="v_attendance_id">
                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="decision" value="verified" class="btn" style="background: #E8F5E9; color: #1B5E20; flex: 1;"><i class="fas fa-check"></i> Verify DTR</button>
                        <button type="submit" name="decision" value="rejected" class="btn" style="background: var(--red-light); color: var(--red-dark); flex: 1;"><i class="fas fa-times"></i> Reject</button>
                    </div>
                </form>
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

    // --- View Report Modal Logic ---
    const viewReportModal = document.getElementById('viewReportModal');

    function viewReport(data) {
        document.getElementById('v_type').textContent = data.report_type;
        const start = new Date(data.week_start).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const end = new Date(data.week_end).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        document.getElementById('v_dates').textContent = `${start} - ${end}`;
        document.getElementById('v_status').innerHTML = `<span class="badge ${data.status}">${data.status}</span>`;
        document.getElementById('v_activities').innerHTML = data.activities.replace(/\n/g, '<br>');
        document.getElementById('v_learnings').innerHTML = data.learnings ? data.learnings.replace(/\n/g, '<br>') : '<em>No learnings provided.</em>';

        document.getElementById('v_student_id').value = data.student_id;
        document.getElementById('v_report_id').value = data.id;

        const evalSection = document.getElementById('eval_section');
        const feedbackSection = document.getElementById('feedback_section');

        if (data.status === 'submitted') {
            evalSection.style.display = 'block';
            feedbackSection.style.display = 'none';
        } else {
            evalSection.style.display = 'none';
            feedbackSection.style.display = 'block';
            document.getElementById('v_feedback').innerHTML = data.company_feedback ? data.company_feedback.replace(/\n/g, '<br>') : 'No feedback provided.';
        }

        viewReportModal.classList.add('open');
    }

    function closeViewModal() {
        viewReportModal.classList.remove('open');
    }

    // --- View Attendance Modal Logic ---
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
        document.getElementById('att_remarks').textContent = data.remarks || 'No remarks provided.';
        
        let vHtml = '';
        if (data.verification_status === 'verified') vHtml = '<span class="badge" style="background:#E8F5E9; color:#1B5E20;">Verified</span>';
        else if (data.verification_status === 'rejected') vHtml = '<span class="badge" style="background:var(--red-light); color:var(--red-dark);">Rejected</span>';
        else vHtml = '<span class="badge" style="background:var(--gray-200); color:var(--gray-600);">Pending</span>';
        
        document.getElementById('att_status').innerHTML = vHtml;

        // Show verification form only if pending
        if (data.verification_status === 'pending' || !data.verification_status) {
            document.getElementById('verify_section').style.display = 'block';
            document.getElementById('v_attendance_id').value = data.id;
        } else {
            document.getElementById('verify_section').style.display = 'none';
        }

        viewAttModal.classList.add('open');
    }

    function closeAttModal() {
        viewAttModal.classList.remove('open');
    }

    // --- Filter Logic ---
    function setupFilter(searchId, filterId, rowClass, textClass) {
        const searchInput = document.getElementById(searchId);
        const filterSelect = document.getElementById(filterId);
        const rows = document.querySelectorAll('.' + rowClass);

        function filterData() {
            if(!searchInput || !filterSelect) return;
            const term = searchInput.value.toLowerCase();
            const status = filterSelect.value;

            rows.forEach(row => {
                const textEl = row.querySelector('.' + textClass);
                const text = textEl ? textEl.textContent.toLowerCase() : '';
                const rowStatus = row.getAttribute('data-status');

                const matchText = text.includes(term);
                const matchStatus = (status === 'all') || (rowStatus === status);

                if (matchText && matchStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if(searchInput && filterSelect) {
            searchInput.addEventListener('input', filterData);
            filterSelect.addEventListener('change', filterData);
        }
    }

    setupFilter('searchRep', 'filterRep', 'row-rep', 'search-date');
    setupFilter('searchDoc', 'filterDoc', 'row-doc', 'search-doctype');
    setupFilter('searchAtt', 'filterAtt', 'row-att', 'search-date');
</script>

<?php company_layout_bottom(); ?>