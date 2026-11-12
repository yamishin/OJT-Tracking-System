<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PractiTrack — OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/global.css" />
    <link rel="stylesheet" href="assets/css/homepage.css" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <header class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="nav-logo">
                <div class="logo-mark">PT</div>
                <div>Practi<span class="accent">Track</span></div>
            </a>

            <nav class="nav-menu" id="navMenu">
                <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
                <a href="#about">About</a>
                <a href="#services">Services</a>
                <a href="login.php">Log In</a>
            </nav>

            <div class="nav-actions">
                <a href="login.php" class="btn btn-primary">Log In</a>
                <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">☰</button>
            </div>
        </div>
    </header>