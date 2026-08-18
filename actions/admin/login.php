<?php
/**
 * Admin Login handler.
 *
 * Validates the submitted email/password against the admins table using
 * password_verify(), checks the account status, then creates the admin
 * session and redirects to the Admin Dashboard. All user-facing errors are
 * intentionally generic to avoid revealing account details.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Already authenticated admins are sent straight to the dashboard.
if (admin_is_logged_in()) {
    header('Location: ' . ROOT_URL . '/admin/dashboard.php');
    exit;
}

$ucsErrors   = [];
$ucsEmail    = trim((string) ($_POST['email'] ?? ''));
$ucsPassword = (string) ($_POST['password'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection: reject requests that do not carry a valid token.
    $ucsCsrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!admin_csrf_verify($ucsCsrfToken)) {
        $ucsErrors[] = 'Your session has expired. Please try again.';
    } else {
        // Server-side validation (never rely on client-side only).
        if ($ucsEmail === '') {
            $ucsErrors[] = 'Email is required.';
        } elseif (!filter_var($ucsEmail, FILTER_VALIDATE_EMAIL)) {
            $ucsErrors[] = 'Please enter a valid email address.';
        }

        if ($ucsPassword === '') {
            $ucsErrors[] = 'Password is required.';
        }
    }

    if (empty($ucsErrors)) {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT id, name, email, password, status
                 FROM admins
                 WHERE email = :email
                 LIMIT 1"
            );
            $ucsStmt->execute([':email' => $ucsEmail]);
            $ucsAdmin = $ucsStmt->fetch() ?: null;

            if ($ucsAdmin === null || !password_verify($ucsPassword, $ucsAdmin['password'])) {
                // Generic message — do not reveal whether the email exists.
                $ucsErrors[] = 'Invalid email or password.';
            } elseif ((int) $ucsAdmin['status'] !== 1) {
                $ucsErrors[] = 'Your admin account is currently inactive. Please contact the system administrator.';
            } else {
                // Successful authentication.
                session_regenerate_id(true);

                $_SESSION['admin_id']    = (int) $ucsAdmin['id'];
                $_SESSION['admin_name']  = (string) $ucsAdmin['name'];
                $_SESSION['admin_email'] = (string) $ucsAdmin['email'];
                $_SESSION['admin_last_activity'] = time();

                header('Location: ' . ROOT_URL . '/admin/dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $ucsErrors[] = 'Unable to process your request. Please try again later.';
        }
    }

    // Carry the submitted email back so the user does not have to retype it.
    $_SESSION['admin_login_email']  = $ucsEmail;
    $_SESSION['admin_login_errors'] = $ucsErrors;
}

header('Location: ' . ROOT_URL . '/admin/login.php');
exit;
