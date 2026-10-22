<?php
require_once 'config/db.php';
$email = 'admin@practitrack.local';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    die("Admin already exists! You can delete this file.");
}

$pdo->prepare("INSERT INTO users (role, first_name, last_name, email, password_hash) VALUES ('admin', 'System', 'Administrator', ?, ?)")->execute([$email, $hash]);
$userId = $pdo->lastInsertId();
$pdo->prepare("INSERT INTO admin_profiles (user_id) VALUES (?)")->execute([$userId]);

echo "✅ Admin created!<br>Email: <b>$email</b><br>Password: <b>$password</b><br><br><strong>DELETE THIS FILE NOW.</strong>";