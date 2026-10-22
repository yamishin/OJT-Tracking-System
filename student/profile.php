<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT u.*, sp.student_no, sp.course, sp.year_level, sp.section, sp.school_name, sp.required_hours
                       FROM users u
                       LEFT JOIN student_profiles sp ON sp.user_id = u.id
                       WHERE u.id = ?");
$stmt->execute([$studentId]);
$user = $stmt->fetch();

student_layout_top('My Profile', 'Manage your personal and academic information');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><span>✓</span><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><span>⚠</span><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div>
            <h3>Profile Information</h3>
            <div class="sub">Keep your details up to date</div>
        </div>
    </div>
    <div class="panel-body">
        <form action="/PractiTrack/php/student/profile-handler.php" method="POST">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-grid">
                <div class="field">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= e($user['first_name']) ?>" required>
                </div>
                <div class="field">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= e($user['last_name']) ?>" required>
                </div>
                <div class="field">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= e($user['email']) ?>" required>
                </div>
                <div class="field">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>">
                </div>
                <div class="field">
    <label>USN (University Student Number)</label>
    <input type="text"
           name="student_no"
           id="student_no"
           value="<?= e($user['student_no'] ?? '') ?>"
           maxlength="11"
           inputmode="numeric"
           pattern="[0-9]{1,11}"
           placeholder="e.g., 20211234567">
    <div class="hint">Numbers only — max 11 digits</div>
</div>
                <div class="field">
                    <label>Course</label>
                    <input type="text" name="course" value="<?= e($user['course'] ?? '') ?>" placeholder="e.g., BS Information Technology">
                </div>
                <div class="field">
                    <label>Year Level</label>
                    <select name="year_level">
                        <?php foreach (['1st Year','2nd Year','3rd Year','4th Year','5th Year'] as $y): ?>
                            <option value="<?= $y ?>" <?= ($user['year_level'] ?? '') === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Section</label>
                    <input type="text" name="section" value="<?= e($user['section'] ?? '') ?>">
                </div>
                <div class="field full">
                    <label>School Name</label>
                    <input type="text" name="school_name" value="<?= e($user['school_name'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Required OJT Hours</label>
                    <input type="number" name="required_hours" value="<?= (int)($user['required_hours'] ?? 486) ?>" min="1">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>Change Password</h3>
    </div>
    <div class="panel-body">
        <form action="/PractiTrack/php/student/profile-handler.php" method="POST">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-grid">
                <div class="field full">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="field">
                    <label>New Password</label>
                    <input type="password" name="new_password" minlength="8" required>
                </div>
                <div class="field">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" minlength="8" required>
                </div>
            </div>

            <button type="submit" class="btn btn-outline">Update Password</button>
        </form>
    </div>
</div>

<?php student_layout_bottom(); ?>