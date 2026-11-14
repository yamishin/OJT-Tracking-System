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
    // Update the schedule status
    $stmt = $pdo->prepare("UPDATE student_schedules SET status = ? WHERE student_id = ?");
    $stmt->execute([$decision, $studentId]);

    // Notify Student
    $msg = "Your requested work schedule has been $decision by the Instructor.";
    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Schedule $decision', ?, 'info')")->execute([$studentId, $msg]);

    // Notify Company
    $stmt = $pdo->prepare("SELECT company_id FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$studentId]);
    $companyId = $stmt->fetchColumn();
    if ($companyId) {
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Schedule $decision', ?, 'info')")->execute([$companyId, $msg]);
    }

    set_flash('success', "Schedule request $decision successfully.");
} catch (Exception $e) {
    set_flash('error', 'Database error: ' . $e->getMessage());
}

redirect('/PractiTrack/school/schedules.php');