<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

$action = $_POST['action'] ?? '';

// ============================================
// LOGIN: STUDENT (Uses USN)
// ============================================
if ($action === 'login_student') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid session.');
        redirect('/PractiTrack/login.php');
    }

    $studentNo = trim($_POST['student_no'] ?? '');
    $password  = $_POST['password']        ?? '';

    if ($studentNo === '' || $password === '') {
        set_flash('error', 'Please enter your USN and password.');
        redirect('/PractiTrack/login.php');
    }

    $stmt = $pdo->prepare("
        SELECT u.* FROM users u
        INNER JOIN student_profiles sp ON sp.user_id = u.id
        WHERE sp.student_no = ? AND u.role = 'student' LIMIT 1
    ");
    $stmt->execute([$studentNo]);
    $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
        set_flash('error', 'Invalid USN or password.');
        redirect('/PractiTrack/login.php');
    }

    // CHECK IF ACCOUNT IS DEACTIVATED
    if (!$user['is_active']) {
        set_flash('error', 'Your account has been deactivated by the Administrator. Please contact your school coordinator.');
        redirect('/PractiTrack/login.php');
    }

    // Success - Start Student Session
    session_write_close();
    session_name('PRACTITRACK_STUDENT');
    session_start();
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role']    = 'student';
    $_SESSION['name']    = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['email']   = $user['email'];

    set_flash('success', 'Welcome back, ' . $user['first_name'] . '!');
    redirect('/PractiTrack/student/dashboard.php');
}

// ============================================
// LOGIN: STAFF (Company/School/Admin - Uses Email)
// ============================================
if ($action === 'login_staff') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid session.');
        redirect('/PractiTrack/login.php');
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password']   ?? '';

    if ($email === '' || $password === '') {
        set_flash('error', 'Please enter your email and password.');
        redirect('/PractiTrack/login.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('company', 'school', 'admin') LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        set_flash('error', 'Invalid email or password.');
        redirect('/PractiTrack/login.php');
    }

    // Success - Start Staff Session
    session_write_close();
    if ($user['role'] === 'school') session_name('PRACTITRACK_SCHOOL');
    elseif ($user['role'] === 'company') session_name('PRACTITRACK_COMPANY');
    elseif ($user['role'] === 'admin') session_name('PRACTITRACK_ADMIN');
    
    session_start();
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['email']   = $user['email'];

    set_flash('success', 'Welcome back, ' . $user['first_name'] . '!');
    
    if ($user['role'] === 'school') redirect('/PractiTrack/school/dashboard.php');
    elseif ($user['role'] === 'company') redirect('/PractiTrack/company/dashboard.php');
    elseif ($user['role'] === 'admin') redirect('/PractiTrack/admin/dashboard.php');
}

// ============================================
// REGISTER (Disabled)
// ============================================
if ($action === 'register') {
    set_flash('error', 'Public registration is disabled. Please contact the Administrator.');
    redirect('/PractiTrack/login.php');
}

// Catch-all fallback
set_flash('error', 'Invalid request.');
redirect('/PractiTrack/login.php');