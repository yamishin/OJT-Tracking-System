<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('company');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/company/students.php');
}

$attendanceId = (int)$_POST['attendance_id'];
$studentId = (int)$_POST['student_id'];
$decision = $_POST['decision'];
$companyId = $_SESSION['user_id'];

$stmt = $pdo->prepare("UPDATE attendance SET verification_status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?");
$stmt->execute([$decision, $companyId, $attendanceId]);

$msg = ($decision === 'verified') 
    ? "A daily time record has been verified and counted towards your hours." 
    : "A daily time record has been rejected by your supervisor.";

$pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'DTR Verification', ?, ?)")
    ->execute([$studentId, $msg, ($decision === 'verified' ? 'success' : 'warning')]);

set_flash('success', "Attendance record has been $decision.");
redirect("/PractiTrack/company/student-view.php?id=$studentId");