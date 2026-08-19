<?php
/**
 * Alumni Mentorship - Quick availability toggle (alumnus side).
 *
 * Lets the alumnus turn mentorship availability on/off from the inbox
 * without opening the profile editor. Suspended mentors cannot re-enable.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';

student_require_login();

$ucsProfile = alumni_current_profile($pdo);
if ($ucsProfile === null) {
    header('Location: ' . BASE_URL . '/alumni.php');
    exit;
}

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    mentorship_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . BASE_URL . '/mentorship-inbox.php');
    exit;
}

$ucsAvailable = $_POST['available'] ?? 0;
$ucsAvailable = in_array($ucsAvailable, [1, '1'], true) ? 1 : 0;

if ($ucsAvailable === 1 && (int) $ucsProfile['mentorship_suspended'] === 1) {
    mentorship_flash('error', 'Mentorship has been disabled for your account by an administrator.');
    header('Location: ' . BASE_URL . '/mentorship-inbox.php');
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE alumni_profiles SET mentorship_available = :available WHERE id = :id"
    );
    $ucsStmt->execute([
        ':available' => $ucsAvailable,
        ':id'        => (int) $ucsProfile['id'],
    ]);
} catch (PDOException $e) {
    mentorship_flash('error', 'Unable to update your mentorship availability. Please try again.');
    header('Location: ' . BASE_URL . '/mentorship-inbox.php');
    exit;
}

mentorship_flash(
    'success',
    $ucsAvailable === 1
        ? 'You are now available for mentorship.'
        : 'You are no longer accepting mentorship requests.'
);
header('Location: ' . BASE_URL . '/mentorship-inbox.php');
exit;