<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('admin');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/admin/applications.php');
}

$studentId = (int)$_POST['student_id'];
$companyId = (int)$_POST['company_id'];
$decision  = $_POST['decision']; // 'accepted' or 'rejected'

// 1. Update student status
$stmt = $pdo->prepare("UPDATE student_profiles SET application_status = ? WHERE user_id = ?");
$stmt->execute([$decision, $studentId]);

// 2. Notify Student
$msg = ($decision === 'accepted') 
    ? "Congratulations! Your application has been approved." 
    : "We regret to inform you that your application was rejected.";

$pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)")
    ->execute([$studentId, "Application $decision", $msg, ($decision === 'accepted' ? 'success' : 'warning')]);

// 3. Notify Company
$msgCompany = ($decision === 'accepted') 
    ? "A student has been officially assigned to your company." 
    : "A student application for your company was rejected.";

$pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Student Application Update', ?, 'info')")
    ->execute([$companyId, $msgCompany]);

set_flash('success', "Student application has been $decision.");
redirect('/PractiTrack/admin/applications.php');