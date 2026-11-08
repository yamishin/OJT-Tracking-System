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

// Check if already assigned
$stmt = $pdo->prepare("SELECT application_status FROM student_profiles WHERE user_id = ?");
$stmt->execute([$studentId]);
$currentStatus = $stmt->fetchColumn();

if ($currentStatus === 'pending' || $currentStatus === 'accepted') {
    set_flash('error', 'You cannot apply while you have a pending or accepted application.');
    redirect('/PractiTrack/student/companies.php');
}

// Update application
$stmt = $pdo->prepare("UPDATE student_profiles SET company_id = ?, application_status = 'pending' WHERE user_id = ?");
$stmt->execute([$companyId, $studentId]);

// Notify Company (Optional but good practice)
$pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'New OJT Application', 'A student has applied to your company.', 'info')")
    ->execute([$companyId]);

set_flash('success', 'Application submitted! The company will review your request.');
redirect('/PractiTrack/student/companies.php');