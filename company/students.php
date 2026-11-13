<?php
require_once __DIR__ . '/../includes/company-layout.php';
$companyId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, sp.student_no, sp.course, sp.required_hours,
    (SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = u.id) as rendered_hours
    FROM users u
    JOIN student_profiles sp ON sp.user_id = u.id
    WHERE sp.company_id = ? AND sp.application_status = 'accepted'
    ORDER BY u.last_name ASC
");
$stmt->execute([$companyId]);
$students = $stmt->fetchAll();

company_layout_top('My Students', 'Manage and evaluate your assigned interns');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header" style="flex-wrap: wrap; gap: 10px;">
        <div>
            <h3>Intern List</h3>
            <div class="sub" id="studentCount"><?= count($students) ?> student(s)</div>
        </div>
        
        <div style="display: flex; gap: 10px; margin-left: auto; align-items: center;">
            <div class="field" style="margin: 0; width: 200px;">
                <input type="text" id="searchStudent" placeholder="Search name or USN..." style="padding: 8px 12px; font-size: 13px;">
            </div>
            <div class="field" style="margin: 0;">
                <select id="filterProgress" style="padding: 8px 12px; font-size: 13px;">
                    <option value="all">All Progress</option>
                    <option value="completed">Completed (100%)</option>
                    <option value="ongoing">Ongoing (Under 100%)</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="openAddStudentModal()" style="background:var(--gray-800);">
                <i class="fas fa-plus"></i> Add Student
            </button>
        </div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table" id="studentTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>USN</th>
                        <th>Course</th>
                        <th>Progress</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="5" class="empty">No students assigned to your company yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): 
                            $progress = $s['required_hours'] > 0 ? min(100, round(($s['rendered_hours'] / $s['required_hours']) * 100)) : 0;
                        ?>
                            <tr class="student-row" data-progress="<?= $progress ?>">
                                <td>
                                    <strong class="s-name"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong><br>
                                    <small style="color:var(--gray-400);"><?= e($s['email']) ?></small>
                                </td>
                                <td class="s-usn"><?= e($s['student_no'] ?? '—') ?></td>
                                <td><?= e($s['course'] ?? '—') ?></td>
                                <td>
                                    <div style="font-size:12px; font-weight:600; margin-bottom:4px;"><?= $progress ?>%</div>
                                    <div class="progress-block" style="margin:0;">
                                        <div class="pb-bar" style="height:6px;">
                                            <div class="pb-fill" style="width: <?= $progress ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="/PractiTrack/company/student-view.php?id=<?= $s['id'] ?>" class="btn btn-sm" style="background:#E0F2FE; color:#0284C7;">
                                        <i class="fas fa-search"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD STUDENT MODAL -->
<div class="modal-overlay" id="addStudentModal">
    <div class="modal-box" style="max-width: 500px; padding: 0;">
        <form action="/PractiTrack/php/company/add-student-handler.php" method="POST" style="display: flex; flex-direction: column; max-height: 85vh;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Add Student</h3>
                <button type="button" class="modal-close" onclick="closeAddModal()"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-body">
                <p style="font-size: 13px; color: var(--gray-600); margin-bottom: 16px;">
                    Enter the student's USN to invite them to your company. The school admin will verify this request.
                </p>
                
                <div class="field">
                    <label>Student USN (Numbers only)</label>
                    <input type="text" name="student_no" placeholder="e.g., 20211234567" required maxlength="11" inputmode="numeric">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" onclick="closeAddModal()" style="background:var(--gray-200); color:var(--gray-800);">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Send Request</button>
            </div>
        </form>
    </div>
</div>

<script>
    const addModal = document.getElementById('addStudentModal');
    function openAddStudentModal() { addModal.classList.add('open'); }
    function closeAddModal() { addModal.classList.remove('open'); }
    window.addEventListener('click', (e) => { if (e.target === addModal) closeAddModal(); });

    const searchInput = document.getElementById('searchStudent');
    const filterSelect = document.getElementById('filterProgress');
    const rows = document.querySelectorAll('.student-row');
    const countDisplay = document.getElementById('studentCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterTerm = filterSelect.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.querySelector('.s-name').textContent.toLowerCase();
            const usn = row.querySelector('.s-usn').textContent.toLowerCase();
            const progress = parseInt(row.getAttribute('data-progress'));

            const matchesSearch = name.includes(searchTerm) || usn.includes(searchTerm);
            
            let matchesFilter = true;
            if (filterTerm === 'completed') matchesFilter = (progress === 100);
            else if (filterTerm === 'ongoing') matchesFilter = (progress < 100);

            if (matchesSearch && matchesFilter) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        countDisplay.textContent = `${visibleCount} student(s)`;
    }

    if (searchInput && filterSelect) {
        searchInput.addEventListener('input', filterTable);
        filterSelect.addEventListener('change', filterTable);
    }
</script>
<?php company_layout_bottom(); ?>