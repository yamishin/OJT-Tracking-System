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
// REGISTER (Students only)
// ============================================
if ($action === 'register') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid session.');
        redirect('/PractiTrack/register.php');
    }

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $password  = $_POST['password']        ?? '';
    $confirm   = $_POST['confirm_password']?? '';
    $studentNo = trim($_POST['student_no'] ?? '');

    $errors = [];
    if ($firstName === '' || $lastName === '') $errors[] = 'Names are required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if (strlen($password) < 8) $errors[] = 'Password must be 8+ chars.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if ($studentNo === '' || !preg_match('/^\d{1,11}$/', $studentNo)) $errors[] = 'Valid USN is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Email already registered.';
        
        $stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE student_no = ?");
        $stmt->execute([$studentNo]);
        if ($stmt->fetch()) $errors[] = 'USN already registered.';
    }

    if ($errors) {
        $_SESSION['old'] = $_POST;
        set_flash('error', implode(' ', $errors));
        redirect('/PractiTrack/register.php');
    }

    try {
        $pdo->beginTransaction();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, email, password_hash) VALUES ('student', ?, ?, ?, ?)");
        $stmt->execute([$firstName, $lastName, $email, $hash]);
        $userId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, student_no) VALUES (?, ?)");
        $stmt->execute([$userId, $studentNo]);

        $pdo->commit();

        // Auto-login the student
        session_write_close();
        session_name('PRACTITRACK_STUDENT');
        session_start();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $userId;
        $_SESSION['role']    = 'student';
        $_SESSION['name']    = $firstName . ' ' . $lastName;
        $_SESSION['email']   = $email;

        unset($_SESSION['old']);
        set_flash('success', 'Welcome! Account created.');
        redirect('/PractiTrack/student/dashboard.php');

    } catch (Exception $ex) {
        $pdo->rollBack();
        set_flash('error', 'Registration failed.');
        redirect('/PractiTrack/register.php');
    }
}

set_flash('error', 'Invalid request.');
redirect('/PractiTrack/login.php');