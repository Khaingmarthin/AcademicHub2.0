<?php
/**
 * Student Profile - Update email notification preference.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/student-validation.php';

student_require_login();

$ucsReturnUrl = ROOT_URL . '/pages/student-profile.php';

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    student_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsValue = filter_var($_POST['email_notifications'] ?? null, FILTER_VALIDATE_INT);

if ($ucsValue === false || !in_array($ucsValue, [0, 1], true)) {
    student_flash('error', 'Invalid notification setting.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsStudent = student_current_user();

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE students SET email_notifications = :val WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([
        ':val' => $ucsValue,
        ':id'  => $ucsStudent['id'],
    ]);
    student_flash('success', 'Notification settings updated.');
} catch (PDOException $e) {
    student_flash('error', 'Unable to update notification settings. Please try again.');
} catch (Throwable $e) {
    student_flash('error', 'Unable to update notification settings. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
