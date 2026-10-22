<?php
require_once 'php/functions.php';

// Clear all session variables
$_SESSION = [];

// If it's set, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Restart the main session just to show the "Logged out" message
session_name('PRACTITRACK_MAIN');
session_start();
set_flash('success', 'You have been logged out.');
redirect('login.php');