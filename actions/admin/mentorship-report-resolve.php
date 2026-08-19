<?php
/**
 * Admin Alumni Mentorship - Resolve or dismiss a mentorship report.
 *
 * action = 'resolved'  -> mark a report as handled
 * action = 'dismissed' -> dismiss a report (no issue found)
 *
 * Admins can also suspend the mentor from the reports page via the separate
 * mentorship-suspend action.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';

admin_require_login();

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    mentorship_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . ROOT_URL . '/admin/mentorship/reports.php');
    exit;
}

$ucsReportId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$ucsAction   = (string) ($_POST['action'] ?? '');

if ($ucsReportId === false || $ucsReportId < 1
    || !in_array($ucsAction, ['resolved', 'dismissed'], true)) {
    mentorship_flash('error', 'That report could not be found.');
    header('Location: ' . ROOT_URL . '/admin/mentorship/reports.php');
    exit;
}

try {
    $ucsStmt = $pdo->prepare("SELECT id FROM mentorship_reports WHERE id = :id LIMIT 1");
    $ucsStmt->execute([':id' => $ucsReportId]);
    if ($ucsStmt->fetchColumn() === false) {
        mentorship_flash('error', 'That report could not be found.');
    } else {
        $ucsStmt = $pdo->prepare(
            "UPDATE mentorship_reports
             SET status = :status,
                 resolved_by_admin_id = :admin_id,
                 resolved_at = NOW()
             WHERE id = :id"
        );
        $ucsStmt->execute([
            ':status'    => $ucsAction,
            ':admin_id'  => (int) $_SESSION['admin_id'],
            ':id'        => $ucsReportId,
        ]);

        mentorship_flash(
            'success',
            $ucsAction === 'resolved'
                ? 'The mentorship report has been marked as resolved.'
                : 'The mentorship report has been dismissed.'
        );
    }
} catch (Throwable $e) {
    mentorship_flash('error', 'Unable to update the report. Please try again.');
}

header('Location: ' . ROOT_URL . '/admin/mentorship/reports.php');
exit;