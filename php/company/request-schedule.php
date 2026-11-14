<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('company');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/company/students.php');
}

$studentId = (int)$_POST['student_id'];
$companyId = $_SESSION['user_id'];
$days = $_POST['day'] ?? [];
$starts = $_POST['start'] ?? [];
$ends = $_POST['end'] ?? [];

// Verify student
$stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE user_id = ? AND company_id = ?");
$stmt->execute([$studentId, $companyId]);
if (!$stmt->fetch()) {
    set_flash('error', 'Unauthorized action.');
    redirect('/PractiTrack/company/students.php');
}

try {
    $pdo->beginTransaction();

    // Delete old pending requests for this student
    $pdo->prepare("DELETE FROM student_schedules WHERE student_id = ? AND status = 'pending'")->execute([$studentId]);

    // Insert new schedule requests
    $stmt = $pdo->prepare("INSERT INTO student_schedules (student_id, day_of_week, time_start, time_end, status) VALUES (?, ?, ?, ?, 'pending')");
    
    for ($i = 0; $i < count($days); $i++) {
        if (!empty($days[$i]) && !empty($starts[$i]) && !empty($ends[$i])) {
            $stmt->execute([$studentId, $days[$i], $starts[$i], $ends[$i]]);
        }
    }

    // Notify Instructor
    $schoolId = $pdo->query("SELECT id FROM users WHERE role = 'school' LIMIT 1")->fetchColumn();
    if ($schoolId) {
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'New Schedule Request', 'A company has requested a new work schedule for a student.', 'info')")
            ->execute([$schoolId]);
    }

    $pdo->commit();
    set_flash('success', 'Schedule request submitted for Instructor approval.');
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Failed to submit schedule: ' . $e->getMessage());
}

redirect("/PractiTrack/company/student-view.php?id=$studentId");