<?php
/**
 * Admin Career Discussions - Resolve or dismiss a report.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/discussions/reports.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsReportId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$ucsAction   = (string) ($_POST['action'] ?? '');

if ($ucsReportId === false || $ucsReportId < 1
    || !in_array($ucsAction, ['resolve', 'dismiss'], true)) {
    discussion_flash('error', 'That report could not be found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsNewStatus = $ucsAction === 'resolve' ? 'resolved' : 'dismissed';

try {
    $ucsStmt = $pdo->prepare("SELECT id, status FROM discussion_reports WHERE id = :id LIMIT 1");
    $ucsStmt->execute([':id' => $ucsReportId]);
    $ucsReport = $ucsStmt->fetch() ?: null;

    if ($ucsReport === null) {
        discussion_flash('error', 'That report could not be found.');
    } else {
        $ucsStmt = $pdo->prepare(
            "UPDATE discussion_reports
             SET status = :status, resolved_by_admin_id = :admin_id, resolved_at = NOW()
             WHERE id = :id"
        );
        $ucsStmt->execute([
            ':status'    => $ucsNewStatus,
            ':admin_id'  => (int) $_SESSION['admin_id'],
            ':id'        => $ucsReportId,
        ]);

        discussion_flash(
            'success',
            $ucsAction === 'resolve' ? 'The report has been marked as resolved.' : 'The report has been dismissed.'
        );
    }
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to update the report. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;