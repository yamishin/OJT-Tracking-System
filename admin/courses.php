<?php
require_once __DIR__ . '/../includes/admin-layout.php';

$stmt = $pdo->query("SELECT * FROM courses ORDER BY name ASC");
$courses = $stmt->fetchAll();

admin_layout_top('Manage Courses', 'Add academic programs for student organization');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="grid-2" style="grid-template-columns: 1fr 2fr;">
    <!-- Add Course Form -->
    <div class="panel">
        <div class="panel-header"><h3>Add New Course</h3></div>
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
                <button type="submit" class="btn btn-primary" style="background:var(--gray-800); width:100%;">Add Course</button>
            </form>
        </div>
    </div>

    <!-- List of Courses -->
    <div class="panel">
        <div class="panel-header"><h3>Existing Courses</h3></div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Code</th><th>Course Name</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr><td colspan="3" class="empty">No courses added yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td><strong><?= e($c['code']) ?></strong></td>
                                    <td><?= e($c['name']) ?></td>
                                    <td>
                                        <!-- Trigger Custom Modal -->
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
    <div class="modal-box" style="max-width: 400px; text-align: center;">
        <div class="modal-header" style="background:#FEE2E2; border-bottom:2px solid var(--red); justify-content: center;">
            <h3 style="color:var(--red-dark);"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
        </div>
        <div class="modal-body" style="padding: 30px 20px;">
            <p style="font-size: 16px; color: var(--gray-800); margin-bottom: 10px;">
                Are you sure you want to delete <br><strong id="del_course_name" style="color:var(--red);"></strong>?
            </p>
            <p style="font-size: 13px; color: var(--gray-600);">This action cannot be undone. Students currently assigned to this course will not be deleted, but the course will disappear from future dropdowns.</p>
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