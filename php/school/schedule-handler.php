<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('school');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/school/schedules.php');
}

$studentId = (int)$_POST['student_id'];
$decision = $_POST['decision'];

try {
    $pdo->beginTransaction();

    // Update the pending schedules to approved or rejected
    $stmt = $pdo->prepare("UPDATE student_schedules SET status = ? WHERE student_id = ? AND status = 'pending'");
    $stmt->execute([$decision, $studentId]);

    // If approved, we can optionally delete old approved schedules to replace them, 
    // but for now, we'll just keep the history.
    if ($decision === 'approved') {
        // Delete old approved ones so only the newest approved is active
        $pdo->prepare("DELETE FROM student_schedules WHERE student_id = ? AND status = 'approved' AND id NOT IN (SELECT id FROM (SELECT id FROM student_schedules WHERE student_id = ? ORDER BY created_at DESC LIMIT 10) as t)")->execute([$studentId, $studentId]);
    }

    // Notify Company
    $stmt = $pdo->prepare("SELECT company_id FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$studentId]);
    $companyId = $stmt->fetchColumn();

    $msg = "The work schedule for a student has been $decision.";
    if ($companyId) {
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Schedule $decision', ?, 'info')")->execute([$companyId, $msg]);
    }
    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Schedule $decision', ?, 'info')")->execute([$studentId, $msg]);

    $pdo->commit();
    set_flash('success', "Schedule request $decision.");
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Failed to process schedule: ' . $e->getMessage());
}

redirect('/PractiTrack/school/schedules.php');