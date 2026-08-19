<?php
/**
 * Alumni Mentorship - Submit a mentorship request (student side).
 *
 * Only a logged-in student may request mentorship. Server-side guards
 * enforce: the target is a verified, public, non-suspended mentor who has
 * turned on availability, the student is not requesting themselves, and no
 * pending or accepted request already exists between the pair.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';

student_require_login();

$ucsUser = student_current_user();

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    mentorship_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . BASE_URL . '/mentorship-request.php?id=' . (int) ($_POST['alumni_profile_id'] ?? 0));
    exit;
}

$ucsResult = mentorship_validate_request_input($_POST, $pdo, (int) $ucsUser['id']);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

$ucsRedirect = BASE_URL . '/mentorship-request.php?id=' . (int) $ucsClean['alumni_profile_id'];

if (!empty($ucsErrors)) {
    $_SESSION['mentorship_errors'] = $ucsErrors;
    $_SESSION['mentorship_old']    = $ucsClean;
    header('Location: ' . $ucsRedirect);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO mentorship_requests (student_id, alumni_profile_id, message)
         VALUES (:student_id, :alumni_profile_id, :message)"
    );
    $ucsStmt->execute([
        ':student_id'       => (int) $ucsUser['id'],
        ':alumni_profile_id' => (int) $ucsClean['alumni_profile_id'],
        ':message'          => $ucsClean['message'],
    ]);
} catch (Throwable $e) {
    mentorship_flash('error', 'Unable to send your mentorship request. Please try again.');
    header('Location: ' . $ucsRedirect);
    exit;
}

mentorship_flash('success', 'Your mentorship request has been sent. The alumnus will review it shortly.');
header('Location: ' . BASE_URL . '/my-mentorship.php');
exit;