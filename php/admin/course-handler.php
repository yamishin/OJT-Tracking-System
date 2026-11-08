<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('admin');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/admin/courses.php');
}

$action = $_POST['action'] ?? '';

// ADD COURSE
if ($action === 'add') {
    $code = trim($_POST['code']);
    $name = trim($_POST['name']);

    if ($code && $name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (code, name) VALUES (?, ?)");
            $stmt->execute([$code, $name]);
            set_flash('success', 'Course added successfully.');
        } catch (Exception $e) {
            set_flash('error', 'Failed to add course. The Course Code might already exist.');
        }
    } else {
        set_flash('error', 'Please fill in both Course Code and Name.');
    }
    redirect('/PractiTrack/admin/courses.php');
}

// DELETE COURSE
if ($action === 'delete') {
    $id = (int)$_POST['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$id]);
        set_flash('success', 'Course deleted successfully.');
    } catch (Exception $e) {
        set_flash('error', 'Failed to delete course. It might be assigned to a student.');
    }
    
    redirect('/PractiTrack/admin/courses.php');
}

// Fallback
redirect('/PractiTrack/admin/courses.php');