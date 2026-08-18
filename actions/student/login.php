<?php
/**
 * Student Login handler.
 *
 * Validates the submitted email/password against the students table using
 * password_verify(), checks the account status, then creates the student
 * session and redirects to the Student Dashboard. All user-facing errors are
 * intentionally generic to avoid revealing account details.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';

// Already authenticated students are sent straight to the dashboard.
if (student_is_logged_in()) {
    header('Location: ' . BASE_URL . '/student-dashboard.php');
    exit;
}

$ucsErrors   = [];
$ucsEmail    = trim((string) ($_POST['email'] ?? ''));
$ucsPassword = (string) ($_POST['password'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Server-side validation (never rely on client-side only).
    if ($ucsEmail === '') {
        $ucsErrors[] = 'Email is required.';
    } elseif (!filter_var($ucsEmail, FILTER_VALIDATE_EMAIL)) {
        $ucsErrors[] = 'Please enter a valid email address.';
    }

    if ($ucsPassword === '') {
        $ucsErrors[] = 'Password is required.';
    }

    if (empty($ucsErrors)) {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT id, student_id, name, email, password, status
                 FROM students
                 WHERE email = :email
                 LIMIT 1"
            );
            $ucsStmt->execute([':email' => $ucsEmail]);
            $ucsStudent = $ucsStmt->fetch() ?: null;

            if ($ucsStudent === null || !password_verify($ucsPassword, $ucsStudent['password'])) {
                // Generic message — do not reveal whether the email exists.
                $ucsErrors[] = 'Invalid email or password.';
            } elseif ((int) $ucsStudent['status'] !== 1) {
                $ucsErrors[] = 'Your student account is currently inactive. Please contact the university administration.';
            } else {
                // Successful authentication.
                session_regenerate_id(true);

                $_SESSION['student_id']    = (int) $ucsStudent['id'];
                $_SESSION['student_name']  = (string) $ucsStudent['name'];
                $_SESSION['student_email'] = (string) $ucsStudent['email'];
                $_SESSION['student_last_activity'] = time();

                header('Location: ' . BASE_URL . '/student-dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $ucsErrors[] = 'Unable to process your request. Please try again later.';
        }
    }

    // Carry the submitted email back so the user does not have to retype it.
    $_SESSION['student_login_email'] = $ucsEmail;
    $_SESSION['student_login_errors'] = $ucsErrors;
}

header('Location: ' . BASE_URL . '/student-login.php');
exit;