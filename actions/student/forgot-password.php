<?php
/**
 * Student Forgot Password handler.
 *
 * Validates the email, generates a password-reset token, and (in production)
 * sends the reset link via email. For now the token URL is logged to the
 * error log — integrate your preferred mailer before going live.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/email-service.php';

if (student_is_logged_in()) {
    header('Location: ' . BASE_URL . '/student-dashboard.php');
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));

// Always show a generic success message to prevent email enumeration.
$genericSuccess = 'If an account with that email exists, a password-reset link has been sent. Please check your inbox.';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Show the form-side validation error for empty/invalid email.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($email === '') {
            $_SESSION['student_forgot_errors'] = ['Email is required.'];
        } else {
            $_SESSION['student_forgot_errors'] = ['Please enter a valid email address.'];
        }
    } else {
        $_SESSION['student_forgot_success'] = '';
    }
    header('Location: ' . BASE_URL . '/forgot-password-student.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT id FROM students WHERE email = :email AND status = 1 LIMIT 1"
    );
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch() ?: null;

    if ($row !== null) {
        $token = bin2hex(random_bytes(32));
        student_create_reset_token((int) $row['id'], $token, 3600);

        $resetUrl = ROOT_URL . '/pages/reset-password-student.php?token=' . $token;

        $subject = 'Reset Your Password — ' . (APP_NAME ?? 'Academic Hub');
        $html = '
        <div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;color:#333;">
            <h2 style="color:#2563eb;">Password Reset Request</h2>
            <p>Hello,</p>
            <p>We received a request to reset the password for your student account. Click the button below to set a new password:</p>
            <p style="text-align:center;margin:30px 0;">
                <a href="' . htmlspecialchars($resetUrl) . '" style="background-color:#2563eb;color:#fff;padding:14px 28px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;">Reset Password</a>
            </p>
            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break:break-all;color:#2563eb;">' . htmlspecialchars($resetUrl) . '</p>
            <p>This link will expire in <strong>1 hour</strong>.</p>
            <p>If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
            <hr style="border:none;border-top:1px solid #e2e8f0;margin:30px 0;">
            <p style="font-size:12px;color:#94a3b8;">' . htmlspecialchars(APP_NAME ?? 'Academic Hub') . ' &mdash; Password Reset Service</p>
        </div>';

        $sent = ucs_send_email($email, $subject, $html);

        if (!$sent) {
            error_log('[Student Password Reset] Email send failed for: ' . $email . '  URL: ' . $resetUrl);
        }
    }

    // Always show the same success message.
    $_SESSION['student_forgot_success'] = $genericSuccess;
} catch (PDOException $e) {
    $_SESSION['student_forgot_success'] = $genericSuccess;
} catch (Exception $e) {
    $_SESSION['student_forgot_success'] = $genericSuccess;
}

header('Location: ' . BASE_URL . '/forgot-password-student.php');
exit;
