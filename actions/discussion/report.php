<?php
/**
 * Career Discussions - Report inappropriate content (students + alumni).
 *
 * Reports a discussion or a reply for admin moderation. A reporter can file
 * at most one open report per piece of content; duplicates are rejected by
 * the unique key and reported as already submitted.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

student_require_login();

$ucsUser = student_current_user();

$ucsContentType = (string) ($_POST['content_type'] ?? '');
$ucsContentId   = filter_var($_POST['content_id'] ?? null, FILTER_VALIDATE_INT);

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . BASE_URL . '/career-discussions.php');
    exit;
}

$ucsReturnUrl = BASE_URL . '/career-discussions.php';
if ($ucsContentType === 'discussion' && $ucsContentId !== false && $ucsContentId > 0) {
    $ucsReturnUrl = BASE_URL . '/career-discussion-details.php?id=' . $ucsContentId;
}

$ucsResult = discussion_validate_report_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    discussion_flash('error', reset($ucsErrors));
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsInserted = false;
try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO discussion_reports
            (content_type, content_id, reporter_student_id, reason, details, status)
         VALUES
            (:content_type, :content_id, :reporter_student_id, :reason, :details, 'open')"
    );
    $ucsStmt->execute([
        ':content_type'       => $ucsClean['content_type'],
        ':content_id'         => $ucsClean['content_id'],
        ':reporter_student_id'=> (int) $ucsUser['id'],
        ':reason'             => $ucsClean['reason'],
        ':details'            => $ucsClean['details'] !== '' ? $ucsClean['details'] : null,
    ]);
    $ucsInserted = true;
} catch (PDOException $e) {
    // Unique key violation -> this reporter already reported the content.
    $ucsErrInfo = $e->errorInfo ?? [];
    if ((int) ($ucsErrInfo[1] ?? 0) === 1062) {
        discussion_flash('error', 'You have already reported this content. Our team will review it.');
    } else {
        discussion_flash('error', 'Unable to submit your report. Please try again.');
    }
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to submit your report. Please try again.');
}

if ($ucsInserted) {
    discussion_flash('success', 'Thank you for reporting this content. Our team will review it.');
}

header('Location: ' . $ucsReturnUrl);
exit;