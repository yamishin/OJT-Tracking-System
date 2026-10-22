<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('student');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/student/documents.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'upload_document') {
    $studentId = $_SESSION['user_id'];
    $docType = trim($_POST['doc_type'] ?? '');

    if ($docType === '') {
        set_flash('error', 'Please select a document type.');
        redirect('/PractiTrack/student/documents.php');
    }

    if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'Please select a file to upload.');
        redirect('/PractiTrack/student/documents.php');
    }

    $file = $_FILES['document'];
    $maxSize = 5 * 1024 * 1024;
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    if ($file['size'] > $maxSize) {
        set_flash('error', 'File too large. Max 5MB.');
        redirect('/PractiTrack/student/documents.php');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        set_flash('error', 'File type not allowed.');
        redirect('/PractiTrack/student/documents.php');
    }

    $uploadDir = __DIR__ . '/../../assets/uploads/documents/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

    $newName = 'doc_' . $studentId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $uploadDir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        set_flash('error', 'Failed to save file.');
        redirect('/PractiTrack/student/documents.php');
    }

    $stmt = $pdo->prepare("INSERT INTO documents (student_id, doc_type, file_name, file_path, file_size)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $studentId,
        $docType,
        $file['name'],
        'assets/uploads/documents/' . $newName,
        $file['size']
    ]);

    set_flash('success', 'Document uploaded successfully!');
    redirect('/PractiTrack/student/documents.php');
}

redirect('/PractiTrack/student/documents.php');