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

    $inHour = (int)date('H', $start);
    $inMinute = (int)date('i', $start);
    $outHour = (int)date('H', $end);
    $outMinute = (int)date('i', $end);

    // 1. Punctuality
    $punctuality = 'On Time';
    if ($inHour < 8) $punctuality = 'Early';
    elseif ($inHour == 8 && $inMinute > 0) $punctuality = 'Late';
    elseif ($inHour > 8) $punctuality = 'Late';

    // 2. Cap at 7:00 PM (19:00)
    $isExceeded = false;
    if ($outHour >= 19 && $end < strtotime('tomorrow', $start)) {
        $end = strtotime(date('Y-m-d', $start) . ' 19:00:00');
        if ($outHour > 19 || ($outHour == 19 && $outMinute > 0)) $isExceeded = true;
    }

    // 3. Math breakdown
    $rawHours = ($end - $start) / 3600;
    $finalHours = $rawHours;
    $overtimeHours = 0;
    $status = '';
    $lunchDeducted = 0;

    // If they work 8+ hours, deduct lunch
    if ($rawHours >= 8) {
        $finalHours = $rawHours - 1; // Deduct lunch
        $lunchDeducted = 1;
        
        // If they worked more than 9 hours (8 + 1 lunch), the rest is overtime
        if ($finalHours > 8) {
            $overtimeHours = $finalHours - 8;
            $finalHours = 8; // Cap standard hours at 8
        }
    } elseif ($rawHours >= 3.5 && $rawHours < 4.5) {
        $status = 'Half Day';
    } else {
        $status = 'Undertime';
    }

    // 4. Status Labels
    if ($isExceeded) $status = 'Exceeded Overtime';
    elseif ($outHour == 19 && $outMinute == 0) $status = 'Overtime';
    elseif ($rawHours >= 8) $status = 'Whole Day';

    $finalHours = round($finalHours, 2);
    $overtimeHours = round($overtimeHours, 2);

    try {
        $stmt = $pdo->prepare("INSERT INTO attendance (student_id, log_date, time_in, time_out, total_hours, overtime_hours, lunch_deducted, status, punctuality, remarks)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE time_in=VALUES(time_in), time_out=VALUES(time_out),
                               total_hours=VALUES(total_hours), overtime_hours=VALUES(overtime_hours), lunch_deducted=VALUES(lunch_deducted), 
                               status=VALUES(status), punctuality=VALUES(punctuality), remarks=VALUES(remarks)");
        
        $stmt->execute([$studentId, $logDate, $timeIn, $timeOut, $finalHours, $overtimeHours, $lunchDeducted, $status, $punctuality, $remarks]);

        set_flash('success', 'Attendance logged: ' . number_format($finalHours, 2) . ' standard hours. Status: ' . $status);
    } catch (Exception $e) {
        set_flash('error', 'Failed to log attendance: ' . $e->getMessage());
    }

    redirect('/PractiTrack/student/attendance.php');
}

redirect('/PractiTrack/student/attendance.php');