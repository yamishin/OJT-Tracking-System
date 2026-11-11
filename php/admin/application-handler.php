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

$stmt = $pdo->prepare("UPDATE student_profiles SET application_status = ? WHERE user_id = ?");
$stmt->execute([$decision, $studentId]);

// Notify student
$msg = ($decision === 'accepted') 
    ? "Congratulations! Your application has been approved. You can now access your dashboard." 
    : "We regret to inform you that your application was rejected. Please apply to another company.";

$pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)")
    ->execute([$studentId, "Application $decision", $msg, ($decision === 'accepted' ? 'success' : 'warning')]);

set_flash('success', "Student application has been $decision.");
redirect('/PractiTrack/admin/applications.php');