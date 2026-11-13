<?php
require_once __DIR__ . '/../includes/admin-layout.php';

$stmt = $pdo->query("SELECT * FROM courses ORDER BY name ASC");
$courses = $stmt->fetchAll();

admin_layout_top('Manage Courses', 'Add academic programs and set default OJT requirements');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="grid-2" style="grid-template-columns: 1fr 1.5fr; gap: 24px;">
    <!-- Add Course Form -->
    <div class="panel">
        <div class="panel-header"><h3><i class="fas fa-book-medical"></i> Add New Course</h3></div>
        <div class="panel-body">
            <form action="/PractiTrack/php/admin/course-handler.php" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="field">
                    <label>Course Code</label>
                    <input type="text" name="code" placeholder="e.g., BSIT" required>
                </div>
                <div class="field">
                    <label>Course Name</label>
                    <input type="text" name="name" placeholder="e.g., BS Information Technology" required>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Required OJT Hours</label>
                        <input type="number" name="duration_hours" value="486" required>
                    </div>
                    <div class="field">
                        <label>Year Level</label>
                        <select name="year_level" required>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year" selected>4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" value="2025-2026" placeholder="e.g., 2025-2026" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="background:var(--gray-800); width:100%; margin-top:10px;">
                    <i class="fas fa-plus"></i> Add Course
                </button>
            </form>
        </div>
    </div>

    <!-- List of Courses -->
    <div class="panel">
        <div class="panel-header"><h3><i class="fas fa-list"></i> Existing Courses</h3></div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code & Name</th>
                            <th>Hours</th>
                            <th>Year / Level</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr><td colspan="4" class="empty">No courses added yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($c['code']) ?></strong><br>
                                        <small style="color:var(--gray-600);"><?= e($c['name']) ?></small>
                                    </td>
                                    <td><span class="badge" style="background:var(--light-blue-light); color:var(--light-blue-dark);"><?= e($c['duration_hours'] ?? '486') ?> hrs</span></td>
                                    <td>
                                        <?= e($c['year_level'] ?? '4th Year') ?><br>
                                        <small style="color:var(--gray-400);">AY <?= e($c['academic_year'] ?? '2025-2026') ?></small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm" style="background:var(--red-light); color:var(--red-dark);" onclick="confirmDelete(<?= $c['id'] ?>, '<?= e($c['name']) ?>')">
                                            <i class="fas fa-trash"></i>
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

<!-- DELETE CONFIRMATION MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box" style="max-width: 400px; text-align: center; padding: 0;">
        <div class="modal-header" style="background:#FEE2E2; border-bottom:2px solid var(--red); justify-content: center;">
            <h3 style="color:var(--red-dark);"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
        </div>
        <div class="modal-body" style="padding: 30px 20px;">
            <p style="font-size: 16px; color: var(--gray-800); margin-bottom: 10px;">
                Are you sure you want to delete <br><strong id="del_course_name" style="color:var(--red);"></strong>?
            </p>
            <p style="font-size: 13px; color: var(--gray-600);">This action cannot be undone.</p>
        </div>
        <div class="modal-footer" style="display:flex; justify-content:center; gap:10px; background:var(--gray-100);">
            <button type="button" class="btn btn-sm" onclick="closeModal()" style="background:var(--gray-200); color:var(--gray-800);">Cancel</button>
            <form action="/PractiTrack/php/admin/course-handler.php" method="POST" style="margin:0;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="del_course_id">
                <button type="submit" class="btn btn-sm btn-primary" style="background:var(--red);">Yes, Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        document.getElementById('del_course_id').value = id;
        document.getElementById('del_course_name').textContent = name;
        document.getElementById('deleteModal').classList.add('open');
    }
    function closeModal() {
        document.getElementById('deleteModal').classList.remove('open');
    }
</script>

<?php admin_layout_bottom(); ?>