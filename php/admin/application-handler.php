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

try {
    $pdo->beginTransaction();

    // 1. Update the student's current profile status
    $stmt = $pdo->prepare("UPDATE student_profiles SET application_status = ? WHERE user_id = ?");
    $stmt->execute([$decision, $studentId]);

    // 2. Update the latest history record for this student/company
    $stmt = $pdo->prepare("UPDATE application_history SET status = ? WHERE student_id = ? AND company_id = ? AND status = 'pending'");
    $stmt->execute([$decision, $studentId, $companyId]);

    // 3. Notify Student
    $msg = ($decision === 'accepted') 
        ? "Congratulations! Your application has been approved. You can now access your dashboard." 
        : "We regret to inform you that your application was rejected. Please apply to another company.";

    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)")
        ->execute([$studentId, "Application $decision", $msg, ($decision === 'accepted' ? 'success' : 'warning')]);

    // 4. Notify Company
    $msgCompany = ($decision === 'accepted') 
        ? "A student has been officially assigned to your company." 
        : "A student application for your company was rejected.";

    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Student Application Update', ?, 'info')")
        ->execute([$companyId, $msgCompany]);

    $pdo->commit();
    set_flash('success', "Student application has been $decision.");
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Failed to process application: ' . $e->getMessage());
}

redirect('/PractiTrack/admin/applications.php');