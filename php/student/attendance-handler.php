<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('student');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/student/attendance.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'log_attendance') {
    $studentId = $_SESSION['user_id'];
    $logDate = $_POST['log_date'] ?? date('Y-m-d');
    $timeIn = $_POST['time_in'] ?: null;
    $timeOut = $_POST['time_out'] ?: null;
    $remarks = trim($_POST['remarks'] ?? '') ?: null;

    if (!$timeIn || !$timeOut) {
        set_flash('error', 'Time In and Time Out are required.');
        redirect('/PractiTrack/student/attendance.php');
    }

    $start = strtotime($timeIn);
    $end = strtotime($timeOut);
    if ($end < $start) $end += 86400; // overnight

    $rawHours = round(($end - $start) / 3600, 2);
    $finalHours = $rawHours;
    $status = '';
    $lunchDeducted = 0;

    // Server-side calculation mirroring the JS
    if ($rawHours >= 8) {
        $finalHours = $rawHours - 1;
        $lunchDeducted = 1;
    } elseif ($rawHours >= 3.5 && $rawHours < 4.5) {
        $status = 'Half Day';
    } elseif ($rawHours < 8) {
        $status = 'Undertime';
    }

    // Overtime Logic
    $outHour = (int)date('H', $end);
    $outMinute = (int)date('i', $end);
    
    if ($outHour >= 19) {
        if ($outHour > 19 || ($outHour == 19 && $outMinute > 0)) {
            $status = 'Exceeded Overtime';
        } else {
            $status = 'Overtime';
        }
    } elseif ($rawHours >= 8) {
        $status = 'Whole Day';
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO attendance (student_id, log_date, time_in, time_out, total_hours, lunch_deducted, status, remarks)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE time_in=VALUES(time_in), time_out=VALUES(time_out),
                               total_hours=VALUES(total_hours), lunch_deducted=VALUES(lunch_deducted), status=VALUES(status), remarks=VALUES(remarks)");
        $stmt->execute([$studentId, $logDate, $timeIn, $timeOut, $finalHours, $lunchDeducted, $status, $remarks]);

        set_flash('success', 'Attendance logged: ' . number_format($finalHours, 2) . ' hours. Status: ' . $status);
    } catch (Exception $e) {
        set_flash('error', 'Failed to log attendance: ' . $e->getMessage());
    }

    redirect('/PractiTrack/student/attendance.php');
}

redirect('/PractiTrack/student/attendance.php');