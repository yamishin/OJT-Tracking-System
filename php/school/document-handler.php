<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('school');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/school/students.php');
}

$docId     = (int)$_POST['doc_id'];
$studentId = (int)$_POST['student_id'];
$decision  = $_POST['decision']; // 'approved' or 'rejected'
$remarks   = trim($_POST['remarks'] ?? '') ?: null;

// Update Document
$stmt = $pdo->prepare("UPDATE documents SET status = ?, remarks = ?, reviewed_at = NOW() WHERE id = ? AND student_id = ?");
$stmt->execute([$decision, $remarks, $docId, $studentId]);

// Notify Student
$pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)")
    ->execute([
        $studentId,
        "Document $decision",
        "Your school instructor has $decision a document you submitted. Remarks: " . ($remarks ?? 'None'),
        ($decision === 'approved') ? 'success' : 'warning'
    ]);

set_flash('success', "Document has been $decision.");
redirect("/PractiTrack/school/student-view.php?id=$studentId");