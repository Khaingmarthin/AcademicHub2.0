<?php
// Login and session authentication helpers
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /pages/student/login.php'); // Or public login
        exit;
    }
}
?>
