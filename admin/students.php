<?php
require_once __DIR__ . '/../includes/admin-layout.php';

// Fetch all courses for the dropdown
$stmt = $pdo->query("SELECT * FROM courses ORDER BY name ASC");
$courses = $stmt->fetchAll();

// Fetch all students
$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.is_active, sp.student_no, sp.course, sp.required_hours,
    cp.company_name,
    (SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = u.id) as rendered_hours
    FROM users u
    JOIN student_profiles sp ON sp.user_id = u.id
    LEFT JOIN company_profiles cp ON cp.user_id = sp.company_id
    WHERE u.role = 'student'
    ORDER BY u.last_name ASC
");
$students = $stmt->fetchAll();

admin_layout_top('Manage Students', 'View all student accounts and their OJT progress');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $msg ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header" style="flex-wrap: wrap; gap: 10px;">
        <div>
            <h3>All Registered Students</h3>
            <div class="sub" id="studentCount"><?= count($students) ?> student(s)</div>
        </div>
        
        <!-- Search & Filter -->
        <div style="display: flex; gap: 10px; margin-left: auto; align-items: center;">
            <div class="field" style="margin: 0; width: 200px;">
                <input type="text" id="searchStudent" placeholder="Search name or USN..." style="padding: 8px 12px; font-size: 13px;">
            </div>
            <div class="field" style="margin: 0;">
                <select id="filterStatus" style="padding: 8px 12px; font-size: 13px;">
                    <option value="all">All Statuses</option>
                    <option value="active">Active Accounts</option>
                    <option value="inactive">Deactivated Accounts</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="openAddModal()" style="background:var(--gray-800);">
                <i class="fas fa-user-plus"></i> Add Student
            </button>
        </div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table" id="studentTable">
                <thead>
                    <tr>
                        <th>Name & USN</th>
                        <th>Course</th>
                        <th>Assigned Company</th>
                        <th>Progress</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="5" class="empty">No students registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): 
                            $progress = $s['required_hours'] > 0 ? min(100, round(($s['rendered_hours'] / $s['required_hours']) * 100)) : 0;
                            $statusClass = $s['is_active'] ? 'active' : 'inactive';
                        ?>
                            <tr class="student-row" data-status="<?= $statusClass ?>">
                                <td>
                                    <strong class="s-name"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong><br>
                                    <small class="s-usn" style="color:var(--gray-600); font-weight:600;">USN: <?= e($s['student_no']) ?></small><br>
                                    <small style="color:var(--gray-400);"><?= e($s['email']) ?></small>
                                </td>
                                <td><?= e($s['course'] ?? '—') ?></td>
                                <td>
                                    <?php if ($s['company_name']): ?>
                                        <span style="font-weight:600; color:var(--light-blue-dark);"><?= e($s['company_name']) ?></span>
                                    <?php else: ?>
                                        <span class="badge pending">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size:12px; font-weight:600; margin-bottom:4px;">
                                        <?= number_format($s['rendered_hours'], 1) ?> / <?= $s['required_hours'] ?> hrs (<?= $progress ?>%)
                                    </div>
                                    <div class="progress-block" style="margin:0;">
                                        <div class="pb-bar" style="height:6px;">
                                            <div class="pb-fill" style="width: <?= $progress ?>%; background:#1E293B;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="/PractiTrack/admin/student-view.php?id=<?= $s['id'] ?>" class="btn btn-sm" style="background:#F1F5F9; color:#1E293B; font-weight:600;">View</a>
                                        
                                        <form action="/PractiTrack/php/admin/student-handler.php" method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $s['is_active'] ?>">
                                            
                                            <?php if ($s['is_active']): ?>
                                                <button type="submit" class="btn btn-sm" style="background:var(--red-light); color:var(--red-dark);" data-confirm="Deactivate this student? They will no longer be able to log in.">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-sm" style="background:#E8F5E9; color:#1B5E20;">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
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
<!-- ADD STUDENT MODAL -->
<!-- ========================================== -->
<div class="modal-overlay" id="addStudentModal">
    <div class="modal-box" style="max-width: 600px; padding: 0;">
        <form action="/PractiTrack/php/admin/student-handler.php" method="POST" style="display: flex; flex-direction: column; max-height: 85vh;">
            <input type="hidden" name="action" value="add_student">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Add New Student</h3>
                <button type="button" class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field">
                        <label>First Name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="field">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required>
                    </div>
                    <div class="field full">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="field">
                        <label>USN (Max 11 digits)</label>
                        <input type="text" name="student_no" maxlength="11" inputmode="numeric" required>
                    </div>
                    <div class="field">
                        <label>Course</label>
                        <select name="course" required>
                            <option value="">— Select Course —</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= e($c['name']) ?>"><?= e($c['code']) ?> - <?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field full">
                        <label>Required Hours</label>
                        <input type="number" name="required_hours" value="486" required>
                    </div>
                </div>
                <div class="form-hint" style="margin-top:10px;">
                    <em>Default password for this student will be <strong>PractiTrack2026</strong>. They can change it after logging in.</em>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" onclick="closeModal()" style="background:var(--gray-200); color:var(--gray-800);">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Create Student</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Modal Logic ---
    const addModal = document.getElementById('addStudentModal');
    function openAddModal() { addModal.classList.add('open'); }
    function closeModal() { addModal.classList.remove('open'); }

    // Close on clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === addModal) closeModal();
    });

    // --- Search & Filter Logic ---
    const searchInput = document.getElementById('searchStudent');
    const filterSelect = document.getElementById('filterStatus');
    const rows = document.querySelectorAll('.student-row');
    const countDisplay = document.getElementById('studentCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterTerm = filterSelect.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.querySelector('.s-name').textContent.toLowerCase();
            const usn = row.querySelector('.s-usn').textContent.toLowerCase();
            const status = row.getAttribute('data-status');

            const matchesSearch = name.includes(searchTerm) || usn.includes(searchTerm);
            const matchesFilter = (filterTerm === 'all') || (status === filterTerm);

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

<?php admin_layout_bottom(); ?>