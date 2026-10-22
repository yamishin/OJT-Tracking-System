<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('student');

$action = $_POST['action'] ?? '';

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/student/profile.php');
}

$userId = $_SESSION['user_id'];

if ($action === 'update_profile') {
    $studentNo = trim($_POST['student_no'] ?? '');

    // Validate USN
    if ($studentNo !== '') {
        if (!preg_match('/^\d{1,11}$/', $studentNo)) {
            set_flash('error', 'USN must be numbers only, maximum 11 digits.');
            redirect('/PractiTrack/student/profile.php');
        }

        // Check uniqueness (excluding self)
        $stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE student_no = ? AND user_id != ?");
        $stmt->execute([$studentNo, $userId]);
        if ($stmt->fetch()) {
            set_flash('error', 'USN is already used by another student.');
            redirect('/PractiTrack/student/profile.php');
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE id=?");
    $stmt->execute([
        trim($_POST['first_name']),
        trim($_POST['last_name']),
        trim($_POST['email']),
        trim($_POST['phone']) ?: null,
        $userId
    ]);

    $stmt = $pdo->prepare("UPDATE student_profiles SET student_no=?, course=?, year_level=?, section=?, school_name=?, required_hours=? WHERE user_id=?");
    $stmt->execute([
        $studentNo ?: null,
        trim($_POST['course']) ?: null,
        trim($_POST['year_level']) ?: null,
        trim($_POST['section']) ?: null,
        trim($_POST['school_name']) ?: null,
        (int)$_POST['required_hours'],
        $userId
    ]);

    $_SESSION['name'] = trim($_POST['first_name']) . ' ' . trim($_POST['last_name']);
    set_flash('success', 'Profile updated successfully.');
    redirect('/PractiTrack/student/profile.php');
}

if ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 8) {
        set_flash('error', 'New password must be at least 8 characters.');
        redirect('/PractiTrack/student/profile.php');
    }
    if ($new !== $confirm) {
        set_flash('error', 'New passwords do not match.');
        redirect('/PractiTrack/student/profile.php');
    }

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        set_flash('error', 'Current password is incorrect.');
        redirect('/PractiTrack/student/profile.php');
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $userId]);

    set_flash('success', 'Password changed successfully.');
    redirect('/PractiTrack/student/profile.php');
}

redirect('/PractiTrack/student/profile.php');