<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('student');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/student/weekly-report.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'submit_report') {
    $studentId = $_SESSION['user_id'];
    $reportType = $_POST['report_type'] ?? 'weekly';
    $hours = (float)$_POST['hours_rendered'];
    $activities = trim($_POST['activities']);
    $learnings = trim($_POST['learnings'] ?? '');
    $attendanceIds = $_POST['attendance_ids'] ?? [];

    if (empty($attendanceIds) || $activities === '') {
        set_flash('error', 'Please select at least one day and fill in the activities.');
        redirect('/PractiTrack/student/weekly-report.php');
    }

    // Get start and end dates based on selected attendance
    $inQuery = str_repeat('?,', count($attendanceIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT MIN(log_date) as start_date, MAX(log_date) as end_date FROM attendance WHERE id IN ($inQuery) AND student_id = ?");
    $params = array_merge($attendanceIds, [$studentId]);
    $stmt->execute($params);
    $dates = $stmt->fetch();

    try {
        $pdo->beginTransaction();

        // 1. Insert Report
        $stmt = $pdo->prepare("INSERT INTO weekly_reports 
            (student_id, report_type, week_start, week_end, hours_rendered, activities, learnings, status, submitted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted', NOW())");
        $stmt->execute([$studentId, $reportType, $dates['start_date'], $dates['end_date'], $hours, $activities, $learnings]);
        
        $reportId = $pdo->lastInsertId();

        // 2. Insert which attendance logs were used
        $stmtAtt = $pdo->prepare("INSERT INTO weekly_report_attendance (report_id, attendance_id, log_date) 
                                  SELECT ?, id, log_date FROM attendance WHERE id = ?");
        foreach ($attendanceIds as $aId) {
            $stmtAtt->execute([$reportId, $aId]);
        }

        // 3. Insert Notification
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')")
            ->execute([$studentId, ucfirst($reportType) . ' Report Submitted', "Your report for {$dates['start_date']} to {$dates['end_date']} has been submitted."]);

        $pdo->commit();
        set_flash('success', 'Report submitted successfully!');
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('error', 'Failed to submit report: ' . $e->getMessage());
    }

    redirect('/PractiTrack/student/weekly-report.php');
}

redirect('/PractiTrack/student/weekly-report.php');