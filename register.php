<?php
require_once 'php/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$flashError = get_flash('error');
$old        = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

$selectedRole = $old['role'] ?? 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Account — PractiTrack</title>
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
            <h2>Start tracking your <span class="accent">On-the-Job Training</span> today.</h2>
            <p>Create your account in less than a minute. Choose your role and get instant access to your dashboard.</p>
            <div class="side-features">
                <div class="item"><span class="tick">✓</span> Free for students</div>
                <div class="item"><span class="tick">✓</span> Company &amp; school portals</div>
                <div class="item"><span class="tick">✓</span> Automatic hour computations</div>
                <div class="item"><span class="tick">✓</span> Secure, encrypted passwords</div>
            </div>
        </div>
        <div class="side-footer">
            &copy; <?= date('Y') ?> PractiTrack
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <a href="index.php" class="back-home">← Back to Home</a>
            <h1>Create Account</h1>
            <p class="subtitle">Fill in your details to get started.</p>

            <?php if ($flashError): ?>
                <div class="alert alert-error"><span>⚠</span><span><?= e($flashError) ?></span></div>
            <?php endif; ?>

            <form action="php/auth.php" method="POST" id="registerForm" novalidate>
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <!-- Only Students can register themselves now -->
                <div class="role-tabs" style="display:none;">
                    <input type="radio" name="role" id="reg-role-student" value="student" checked>
                </div>
                
                <div class="alert alert-info" style="margin-bottom:20px;">
                    <span>🎓</span> <span>Creating a Student Account. Companies and Schools are registered by the Admin.</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name"
                               value="<?= e($old['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name"
                               value="<?= e($old['last_name'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           value="<?= e($old['email'] ?? '') ?>"
                           placeholder="you@example.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number <span style="color:var(--gray-400);font-weight:400;">(optional)</span></label>
                    <input type="text" id="phone" name="phone"
                           value="<?= e($old['phone'] ?? '') ?>"
                           placeholder="09XX XXX XXXX">
                </div>

                <!-- Company Field -->
                <div class="form-group" id="companyField" style="display: none;">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name"
                           value="<?= e($old['company_name'] ?? '') ?>"
                           placeholder="e.g., Acme Corporation">
                </div>

                <!-- Student Field (Starts Visible!) -->
                <div class="form-group" id="studentField">
                    <label for="student_no">USN (University Student Number)</label>
                    <input type="text"
                           id="student_no"
                           name="student_no"
                           value="<?= e($old['student_no'] ?? '') ?>"
                           placeholder="e.g., 20211234567"
                           inputmode="numeric"
                           pattern="[0-9]{1,11}"
                           maxlength="11"
                           autocomplete="off">
                    <div class="form-hint">Numbers only — maximum 11 digits</div>
                </div>

                <!-- School Field -->
                <div class="form-group" id="schoolField" style="display: none;">
                    <label for="school_name">School / University Name</label>
                    <input type="text" id="school_name" name="school_name"
                           value="<?= e($old['school_name'] ?? '') ?>"
                           placeholder="e.g., State University">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input type="password" id="password" name="password"
                                   placeholder="Min. 8 characters" required minlength="8" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-target="password">👁</button>
                        </div>
                        <div class="form-hint">At least 8 characters</div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-wrap">
                            <input type="password" id="confirm_password" name="confirm_password"
                                   placeholder="Re-type password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-target="confirm_password">👁</button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                <div class="auth-divider">or</div>
                <div class="auth-footer">
                    Already have an account? <a href="login.php">Log in</a>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="assets/js/auth.js"></script>
</body>
</html>