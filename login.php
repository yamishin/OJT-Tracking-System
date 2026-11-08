<?php
require_once 'php/functions.php';

// If already logged in, go to their dashboard
if (is_logged_in()) {
    if ($_SESSION['role'] === 'student') redirect('/PractiTrack/student/dashboard.php');
    elseif ($_SESSION['role'] === 'school') redirect('/PractiTrack/school/dashboard.php');
    elseif ($_SESSION['role'] === 'company') redirect('/PractiTrack/company/dashboard.php');
    elseif ($_SESSION['role'] === 'admin') redirect('/PractiTrack/admin/dashboard.php');
}

$flashError   = get_flash('error');
$flashSuccess = get_flash('success');
$old          = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Log In — PractiTrack</title>
    <link rel="stylesheet" href="assets/css/global.css" />
    <link rel="stylesheet" href="assets/css/auth.css" />
</head>

<body>

    <div class="auth-wrapper">
        <aside class="auth-side">
            <a href="index.php" class="side-logo">
                <div class="logo-mark">PT</div>
                <div>PractiTrack</div>
            </a>
            <div class="side-content">
                <h2>Welcome back to your <span class="accent">OJT journey</span>.</h2>
                <p>Log in to access your dashboard, submit reports, and stay on top of your training hours.</p>
            </div>
            <div class="side-footer">&copy; <?= date('Y') ?> PractiTrack</div>
        </aside>

        <main class="auth-main">
            <div class="auth-card">
                <a href="index.php" class="back-home">← Back to Home</a>
                <h1>Log In</h1>
                <p class="subtitle">Select your account type to continue.</p>

                <?php if ($flashError): ?>
                    <div class="alert alert-error"><span>⚠</span><span><?= e($flashError) ?></span></div>
                <?php endif; ?>
                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success"><span>✓</span><span><?= e($flashSuccess) ?></span></div>
                <?php endif; ?>

                <!-- Role Tabs -->
                <div class="role-tabs" style="margin-bottom: 24px;">
                    <input type="radio" id="tab-student" name="login_tab" value="student" checked onchange="toggleLoginForm()">
                    <label for="tab-student">Student</label>

                    <input type="radio" id="tab-staff" name="login_tab" value="staff" onchange="toggleLoginForm()">
                    <label for="tab-staff">Staff (Company / School / Admin)</label>
                </div>

                <!-- Student Form (USN) -->
                <form action="php/auth.php" method="POST" id="form-student" novalidate>
                    <input type="hidden" name="action" value="login_student">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="form-group">
                        <label for="student_no">USN (University Student Number)</label>
                        <input type="text" id="student_no" name="student_no"
                            value="<?= e($old['student_no'] ?? '') ?>"
                            placeholder="e.g., 20211234567"
                            inputmode="numeric" maxlength="11" required autocomplete="username">
                        <div class="form-hint">Numbers only, max 11 digits.</div>
                    </div>

                    <div class="form-group">
                        <label for="password_student">Password</label>
                        <div class="password-wrap">
                            <input type="password" id="password_student" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" data-target="password_student">👁</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Log In as Student</button>
                </form>

                <!-- Staff Form (Email) -->
                <form action="php/auth.php" method="POST" id="form-staff" style="display: none;" novalidate>
                    <input type="hidden" name="action" value="login_staff">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="form-group">
                        <label for="email_staff">Email Address</label>
                        <input type="email" id="email_staff" name="email"
                            value="<?= e($old['email'] ?? '') ?>"
                            placeholder="you@example.com" required autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label for="password_staff">Password</label>
                        <div class="password-wrap">
                            <input type="password" id="password_staff" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" data-target="password_staff">👁</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Log In as Staff</button>
                </form>

                <div class="auth-divider">or</div>
                <div class="auth-footer" style="color:var(--gray-400); font-size:13px;">
                    <i class="fas fa-info-circle"></i> Student accounts must be created by the School Administrator.
                </div>
            </div>

        </main>
    </div>

    <script>
        function toggleLoginForm() {
            const isStudent = document.getElementById('tab-student').checked;
            document.getElementById('form-student').style.display = isStudent ? 'block' : 'none';
            document.getElementById('form-staff').style.display = isStudent ? 'none' : 'block';
        }

        // Password toggles
        document.querySelectorAll('.password-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = document.getElementById(btn.dataset.target);
                if (!target) return;
                const isPwd = target.type === 'password';
                target.type = isPwd ? 'text' : 'password';
                btn.textContent = isPwd ? '🙈' : '👁';
            });
        });
    </script>
</body>

</html>