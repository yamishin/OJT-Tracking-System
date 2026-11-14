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

// Verify student belongs to this company
$stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE user_id = ? AND company_id = ?");
$stmt->execute([$studentId, $companyId]);
if (!$stmt->fetch()) {
    set_flash('error', 'Unauthorized action.');
    redirect('/PractiTrack/company/students.php');
}

// Collect calendar data (format: "08:00 - 17:00" or empty)
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$scheduleData = [];

foreach ($days as $day) {
    $start = $_POST["{$day}_start"] ?? '';
    $end = $_POST["{$day}_end"] ?? '';
    
    if (!empty($start) && !empty($end)) {
        $scheduleData[$day] = "$start - $end";
    } else {
        $scheduleData[$day] = null;
    }
}

try {
    $pdo->beginTransaction();

    // Check if student already has a schedule record
    $stmt = $pdo->prepare("SELECT id, status FROM student_schedules WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing schedule
        $stmt = $pdo->prepare("UPDATE student_schedules SET 
            monday = ?, tuesday = ?, wednesday = ?, thursday = ?, friday = ?, saturday = ?, sunday = ?, 
            status = 'pending', requested_at = NOW() 
            WHERE student_id = ?");
        $stmt->execute([
            $scheduleData['monday'], $scheduleData['tuesday'], $scheduleData['wednesday'], 
            $scheduleData['thursday'], $scheduleData['friday'], $scheduleData['saturday'], 
            $scheduleData['sunday'], $studentId
        ]);
    } else {
        // Insert new schedule
        $stmt = $pdo->prepare("INSERT INTO student_schedules 
            (student_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([
            $studentId, $scheduleData['monday'], $scheduleData['tuesday'], $scheduleData['wednesday'], 
            $scheduleData['thursday'], $scheduleData['friday'], $scheduleData['saturday'], 
            $scheduleData['sunday']
        ]);
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