<?php
/**
 * Admin Reset Password handler.
 *
 * Validates the reset token, verifies the new password, and updates the
 * database. On success the admin is redirected to the login page with a
 * success message.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (admin_is_logged_in()) {
    header('Location: ' . ROOT_URL . '/admin/dashboard.php');
    exit;
}

$token            = (string) ($_POST['token'] ?? '');
$password         = (string) ($_POST['password'] ?? '');
$passwordConfirm  = (string) ($_POST['password_confirm'] ?? '');
$csrfToken        = (string) ($_POST['csrf_token'] ?? '');
$errors           = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $token === '') {
    header('Location: ' . ROOT_URL . '/admin/forgot-password.php');
    exit;
}

// CSRF check.
if (!admin_csrf_verify($csrfToken)) {
    $errors[] = 'Your session has expired. Please try again.';
    $_SESSION['admin_reset_errors'] = $errors;
    header('Location: ' . ROOT_URL . '/admin/reset-password.php?token=' . urlencode($token));
    exit;
}

// Validate the token.
$userId = admin_validate_reset_token($token);
if ($userId === false) {
    $errors[] = 'This password reset link is invalid or has expired.';
}

// Validate the passwords.
if (empty($errors)) {
    if ($password === '') {
        $errors[] = 'New password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($passwordConfirm === '') {
        $errors[] = 'Please confirm your new password.';
    } elseif ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }
}

if (empty($errors)) {
    try {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "UPDATE admins SET password = :password, updated_at = NOW() WHERE id = :id"
        );
        $stmt->execute([':password' => $newHash, ':id' => $userId]);

        // Mark the token as used.
        admin_mark_reset_token_used($token);

        $_SESSION['admin_reset_success'] = 'Your password has been reset successfully. You can now sign in with your new password.';
        header('Location: ' . ROOT_URL . '/admin/reset-password.php?token=' . urlencode($token));
        exit;
    } catch (PDOException $e) {
        $errors[] = 'Unable to reset your password. Please try again later.';
    }
}

$_SESSION['admin_reset_errors'] = $errors;
header('Location: ' . ROOT_URL . '/admin/reset-password.php?token=' . urlencode($token));
exit;
