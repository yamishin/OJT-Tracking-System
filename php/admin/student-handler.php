<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('admin');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/admin/students.php');
}

$action = $_POST['action'] ?? '';

//  ADD NEW STUDENT
if ($action === 'add_student') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $studentNo = trim($_POST['student_no']);
    $course = trim($_POST['course']);
    $requiredHours = (int)$_POST['required_hours'];
    
    $generatedPassword = 'PractiTrack2026';
    $hash = password_hash($generatedPassword, PASSWORD_DEFAULT);

    // Check duplicates
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        set_flash('error', 'Email is already registered.');
        redirect('/PractiTrack/admin/students.php');
    }

    $stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE student_no = ?");
    $stmt->execute([$studentNo]);
    if ($stmt->fetch()) {
        set_flash('error', 'USN is already registered.');
        redirect('/PractiTrack/admin/students.php');
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, email, password_hash) VALUES ('student', ?, ?, ?, ?)");
        $stmt->execute([$firstName, $lastName, $email, $hash]);
        $userId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, student_no, course, required_hours) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $studentNo, $course, $requiredHours]);

        $pdo->commit();
        set_flash('success', "Student account created!<br><br><strong>Email:</strong> $email<br><strong>USN:</strong> $studentNo<br><strong>Password:</strong> $generatedPassword");
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('error', 'Failed to create student: ' . $e->getMessage());
    }

    redirect('/PractiTrack/admin/students.php');
}


//  TOGGLE ACTIVE STATUS 
if ($action === 'toggle_active') {
    $studentId = (int)$_POST['student_id'];
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus === 1 ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'student'");
    $stmt->execute([$newStatus, $studentId]);

    $statusText = $newStatus === 1 ? 'activated' : 'deactivated';
    set_flash('success', "Student account successfully $statusText.");
    redirect('/PractiTrack/admin/students.php');
}

// Fallback
redirect('/PractiTrack/admin/students.php');