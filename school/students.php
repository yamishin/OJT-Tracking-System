<?php
require_once __DIR__ . '/../includes/school-layout.php';

// Fetch all students along with their company info
$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, sp.student_no, sp.course, sp.required_hours, sp.application_status,
    cp.company_name,
    (SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = u.id) as rendered_hours
    FROM users u
    JOIN student_profiles sp ON sp.user_id = u.id
    LEFT JOIN company_profiles cp ON cp.user_id = sp.company_id
    WHERE u.role = 'student'
    ORDER BY u.last_name ASC
");
$students = $stmt->fetchAll();

school_layout_top('Student Monitoring', 'View and track all students in the program');
?>

<div class="panel">
    <div class="panel-header" style="flex-wrap: wrap; gap: 10px;">
        <div>
            <h3>All Students</h3>
            <div class="sub" id="studentCount"><?= count($students) ?> student(s)</div>
        </div>
        
        <!-- Search and Filter -->
        <div style="display: flex; gap: 10px; margin-left: auto;">
            <div class="field" style="margin: 0; width: 200px;">
                <input type="text" id="searchStudent" placeholder="Search name or USN..." style="padding: 8px 12px; font-size: 13px;">
            </div>
            <div class="field" style="margin: 0;">
                <select id="filterStatus" style="padding: 8px 12px; font-size: 13px;">
                    <option value="all">All Students</option>
                    <option value="accepted">Assigned to Company</option>
                    <option value="pending">Pending Application</option>
                    <option value="unassigned">Unassigned</option>
                </select>
            </div>
        </div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
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
                        ?>
                            <tr class="student-row" data-status="<?= $s['application_status'] ?>">
                                <td>
                                    <strong class="student-name"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong><br>
                                    <small class="student-usn" style="color:var(--gray-600); font-weight:600;">USN: <?= e($s['student_no']) ?></small>
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
                                            <div class="pb-fill" style="width: <?= $progress ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="/PractiTrack/school/student-view.php?id=<?= $s['id'] ?>" class="btn btn-sm" style="background:#E0F2FE; color:#0284C7;">
                                        <i class="fas fa-folder-open"></i> View Details
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

<script>
    const searchInput = document.getElementById('searchStudent');
    const filterSelect = document.getElementById('filterStatus');
    const rows = document.querySelectorAll('.student-row');
    const countDisplay = document.getElementById('studentCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterTerm = filterSelect.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.querySelector('.student-name').textContent.toLowerCase();
            const usn = row.querySelector('.student-usn').textContent.toLowerCase();
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

<?php school_layout_bottom(); ?>