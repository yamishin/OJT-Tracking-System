<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('company');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/company/students.php');
}

$companyId = $_SESSION['user_id'];
$studentId = (int)$_POST['student_id'];
$action    = $_POST['action'] ?? '';

if ($action === 'weekly_eval') {
    $reportId = (int)$_POST['report_id'];
    $feedback = trim($_POST['feedback']);
    $decision = $_POST['decision']; // 'approved' or 'rejected'

    $stmt = $pdo->prepare("UPDATE weekly_reports SET status = ?, company_feedback = ?, company_approved_at = NOW() WHERE id = ? AND student_id = ?");
    $stmt->execute([$decision, $feedback, $reportId, $studentId]);

    // Notify student
    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)")
        ->execute([
            $studentId, 
            "Report $decision", 
            "The company has $decision your report. Feedback: $feedback", 
            ($decision === 'approved') ? 'success' : 'warning'
        ]);

    set_flash('success', "Report has been $decision.");
    redirect("/PractiTrack/company/student-view.php?id=$studentId");
}

if ($action === 'final_eval') {
    $score = (int)$_POST['score'];
    $remarks = trim($_POST['remarks']);

    $stmt = $pdo->prepare("INSERT INTO final_evaluations (student_id, company_id, score, remarks, status) VALUES (?, ?, ?, ?, 'completed')");
    $stmt->execute([$studentId, $companyId, $score, $remarks]);

    // Notify student
    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Final Evaluation Completed', 'Your company has submitted your final OJT evaluation.', 'success')")
        ->execute([$studentId]);

    set_flash('success', 'Final evaluation submitted successfully.');
    redirect("/PractiTrack/company/student-view.php?id=$studentId");
}

redirect('/PractiTrack/company/students.php');