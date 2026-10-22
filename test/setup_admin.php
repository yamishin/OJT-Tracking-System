<?php
// setup_admin.php — run once to create default admin, then DELETE this file.
require_once 'config/db.php';

$email = 'admin@practitrack.local';
$password = 'admin123';

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    exit("Admin already exists. You can delete this file now.");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, email, password_hash, phone)
                       VALUES ('admin', 'System', 'Administrator', ?, ?, '09000000000')");
$stmt->execute([$email, $hash]);

$userId = $pdo->lastInsertId();
$pdo->prepare("INSERT INTO admin_profiles (user_id, access_level) VALUES (?, 'super')")->execute([$userId]);

echo "✅ Default admin created!<br>";
echo "Email: <b>$email</b><br>";
echo "Password: <b>$password</b><br><br>";
echo "⚠️ DELETE this file (setup_admin.php) immediately for security.";

