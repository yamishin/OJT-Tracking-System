<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

// Fetch user and profile data
$stmt = $pdo->prepare("SELECT u.*, sp.student_no, sp.course, sp.school_name, sp.required_hours FROM users u JOIN student_profiles sp ON sp.user_id = u.id WHERE u.id = ?");
$stmt->execute([$studentId]);
$user = $stmt->fetch();

student_layout_top('My Profile', 'Manage your personal information and password');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="grid-2">
    <!-- Personal Info -->
    <div class="panel">
        <div class="panel-header"><h3><i class="fas fa-user-edit"></i> Personal Information</h3></div>
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
                    <div class="field full">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= e($user['email']) ?>" required>
                    </div>
                    <div class="field">
                        <label>USN (Read Only)</label>
                        <input type="text" value="<?= e($user['student_no']) ?>" readonly style="background:var(--gray-100);">
                    </div>
                    <div class="field">
                        <label>Course (Read Only)</label>
                        <input type="text" value="<?= e($user['course']) ?>" readonly style="background:var(--gray-100);">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:10px;">Update Profile</button>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="panel">
        <div class="panel-header"><h3><i class="fas fa-lock"></i> Change Password</h3></div>
        <div class="panel-body">
            <form action="/PractiTrack/php/student/profile-handler.php" method="POST">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="field">
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
                <button type="submit" class="btn btn-outline" style="margin-top:10px;">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php student_layout_bottom(); ?>