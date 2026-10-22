<?php
// php/functions.php

// 1. Determine the session name based on the URL (FORCED)
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';

// We use strpos to check the folder name in the URL
if (strpos($currentUrl, '/admin/') !== false) {
    session_name('PRACTITRACK_ADMIN');
} elseif (strpos($currentUrl, '/company/') !== false) {
    session_name('PRACTITRACK_COMPANY');
} elseif (strpos($currentUrl, '/school/') !== false) {
    session_name('PRACTITRACK_SCHOOL');
} elseif (strpos($currentUrl, '/student/') !== false) {
    session_name('PRACTITRACK_STUDENT');
} else {
    // Default for root pages (index.php, login.php, register.php)
    session_name('PRACTITRACK_MAIN');
}

// 2. Start the session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Helper functions
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}
// ... (keep the rest of your functions: csrf, flash, etc.)

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function set_flash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

function get_flash($key) {
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

function require_login($role = null) {
    if (!is_logged_in()) {
        set_flash('error', 'Please log in first.');
        redirect('../login.php'); // Go up one folder to find login
    }
    if ($role && current_role() !== $role) {
        set_flash('error', 'Unauthorized access.');
        redirect('../login.php');
    }
}

function dashboard_for_role($role) {
    $map = [
        'student' => '../student/dashboard.php',
        'company' => '../company/dashboard.php',
        'school'  => '../school/dashboard.php',
        'admin'   => '../admin/dashboard.php',
    ];
    return $map[$role] ?? '../login.php';
}

function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M d, Y', $timestamp);
}