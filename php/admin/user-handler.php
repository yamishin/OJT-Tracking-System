<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('admin');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/admin/manage-users.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'create_user') {
    $role = $_POST['role'];
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $orgName = trim($_POST['org_name'] ?? '');
    
    // Default generated password
    $generatedPassword = 'PractiTrack2026';
    $hash = password_hash($generatedPassword, PASSWORD_DEFAULT);

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        set_flash('error', 'Email is already registered.');
        redirect('/PractiTrack/admin/manage-users.php');
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$role, $firstName, $lastName, $email, $hash]);
        $userId = $pdo->lastInsertId();

        if ($role === 'company') {
            $pdo->prepare("INSERT INTO company_profiles (user_id, company_name) VALUES (?, ?)")->execute([$userId, $orgName]);
        } elseif ($role === 'school') {
            $pdo->prepare("INSERT INTO school_profiles (user_id, school_name) VALUES (?, ?)")->execute([$userId, $orgName]);
        } elseif ($role === 'admin') {
            $pdo->prepare("INSERT INTO admin_profiles (user_id) VALUES (?)")->execute([$userId]);
        }

        $pdo->commit();
        
        set_flash('success', "Account created successfully!<br><br><strong>Email:</strong> $email<br><strong>Password:</strong> $generatedPassword<br><br><em>Please copy this password and give it to the user.</em>");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('error', 'Failed to create user: ' . $e->getMessage());
    }
    
    redirect('/PractiTrack/admin/manage-users.php');
}