<?php
/**
 * Alumni Mentorship - Report a mentorship request (student side).
 *
 * Only the student who sent the request may report it. Duplicate reports
 * from the same student on the same request are blocked by the unique
 * constraint and reported as an error (MyISAM duplicate-key check).
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';

student_require_login();

$ucsUser = student_current_user();

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    mentorship_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . BASE_URL . '/my-mentorship.php');
    exit;
}

$ucsResult = mentorship_validate_report_input($_POST, $pdo, (int) $ucsUser['id']);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['mentorship_report_errors'] = $ucsErrors;
    $_SESSION['mentorship_report_old']    = $ucsClean;
    header('Location: ' . BASE_URL . '/my-mentorship.php');
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO mentorship_reports
            (request_id, reporter_student_id, reason, details)
         VALUES
            (:request_id, :reporter_student_id, :reason, :details)"
    );
    $ucsStmt->execute([
        ':request_id'          => (int) $ucsClean['request_id'],
        ':reporter_student_id' => (int) $ucsUser['id'],
        ':reason'              => $ucsClean['reason'],
        ':details'             => $ucsClean['details'] !== '' ? $ucsClean['details'] : null,
    ]);
} catch (Throwable $e) {
    // Duplicate report (MyISAM returns ER_DUP_ENTRY 1062 in errorInfo[1]).
    if ($e instanceof PDOException && isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
        mentorship_flash('error', 'You have already reported this mentorship request.');
    } else {
        mentorship_flash('error', 'Unable to submit your report. Please try again.');
    }
    header('Location: ' . BASE_URL . '/my-mentorship.php');
    exit;
}

mentorship_flash('success', 'Your report has been submitted. It will be reviewed by an administrator.');
header('Location: ' . BASE_URL . '/my-mentorship.php');
exit;