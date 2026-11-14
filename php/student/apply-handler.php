<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('student');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/student/companies.php');
}

$studentId = $_SESSION['user_id'];
$companyId = (int)$_POST['company_id'];

$stmt = $pdo->prepare("SELECT application_status FROM student_profiles WHERE user_id = ?");
$stmt->execute([$studentId]);
$currentStatus = $stmt->fetchColumn();

if ($currentStatus === 'pending' || $currentStatus === 'accepted') {
    set_flash('error', 'You already have a pending or accepted application.');
    redirect('/PractiTrack/student/companies.php');
}

try {
    $pdo->beginTransaction();

    // 1. Update current profile status
    $stmt = $pdo->prepare("UPDATE student_profiles SET company_id = ?, application_status = 'pending' WHERE user_id = ?");
    $stmt->execute([$companyId, $studentId]);

    // 2. Log into History
    $stmt = $pdo->prepare("INSERT INTO application_history (student_id, company_id, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$studentId, $companyId]);

    // 3. Notify Admin
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $adminId = $stmt->fetchColumn();
    if ($adminId) {
        $studentName = $_SESSION['name'] ?? 'A student';
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'New OJT Application', ?, 'info')")
            ->execute([$adminId, "$studentName has applied to a company. Please review."]);
    }

    $pdo->commit();
    set_flash('success', 'Application submitted! Please wait for the Admin to approve it.');
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Failed to submit application: ' . $e->getMessage());
}

redirect('/PractiTrack/student/companies.php');