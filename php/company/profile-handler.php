<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('company');

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/company/profile.php');
}

if ($action === 'update_profile') {
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
    $stmt->execute([trim($_POST['first_name']), trim($_POST['last_name']), trim($_POST['email']), $userId]);
    $_SESSION['name'] = trim($_POST['first_name']) . ' ' . trim($_POST['last_name']);
    set_flash('success', 'Profile updated successfully.');
}

if ($action === 'change_password') {
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($_POST['current_password'], $hash)) {
        set_flash('error', 'Current password is incorrect.');
    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
        set_flash('error', 'New passwords do not match.');
    } else {
        $newHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $userId]);
        set_flash('success', 'Password changed successfully.');
    }
}

redirect('/PractiTrack/company/profile.php');