<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes App</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="index.php">Notes App</a>
        </div>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <a href="index.php">My Notes</a>
                <a href="create_note.php">Create Note</a>
                <a href="voice_note.php">🎤 Voice Note</a>
                <a href="trash.php">Trash</a>
                <a href="analytics.php">Analytics</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>
    <main class="container">
