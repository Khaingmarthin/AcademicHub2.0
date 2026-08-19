<?php
/**
 * Admin Career Discussions - Change a discussion status.
 *
 * action = 'open'   -> restore / reopen a discussion
 * action = 'closed' -> lock a discussion (no new replies)
 * action = 'hidden' -> remove a discussion from the public site
 *
 * Hiding a discussion also resolves any open reports filed against it so
 * the moderation queue stays clean.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/discussions/index.php';
$ucsFromReports = isset($_POST['from_reports']) && (int) $_POST['from_reports'] === 1;
if ($ucsFromReports) {
    $ucsReturnUrl = ROOT_URL . '/admin/discussions/reports.php';
}

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsDiscussionId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$ucsAction       = (string) ($_POST['action'] ?? '');

if ($ucsDiscussionId === false || $ucsDiscussionId < 1
    || !in_array($ucsAction, DISCUSSION_STATUSES, true)) {
    discussion_flash('error', 'That discussion could not be found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare("SELECT id, title FROM discussions WHERE id = :id LIMIT 1");
    $ucsStmt->execute([':id' => $ucsDiscussionId]);
    $ucsDiscussion = $ucsStmt->fetch() ?: null;

    if ($ucsDiscussion === null) {
        discussion_flash('error', 'That discussion could not be found.');
    } else {
        $ucsStmt = $pdo->prepare("UPDATE discussions SET status = :status WHERE id = :id");
        $ucsStmt->execute([':status' => $ucsAction, ':id' => $ucsDiscussionId]);

        if ($ucsAction === 'hidden') {
            $ucsResolveStmt = $pdo->prepare(
                "UPDATE discussion_reports
                 SET status = 'resolved', resolved_by_admin_id = :admin_id, resolved_at = NOW()
                 WHERE content_type = 'discussion' AND content_id = :id AND status = 'open'"
            );
            $ucsResolveStmt->execute([
                ':admin_id' => (int) $_SESSION['admin_id'],
                ':id'       => $ucsDiscussionId,
            ]);
        }

        $ucsMessages = [
            'open'   => 'Discussion "' . $ucsDiscussion['title'] . '" has been reopened.',
            'closed' => 'Discussion "' . $ucsDiscussion['title'] . '" has been closed.',
            'hidden' => 'Discussion "' . $ucsDiscussion['title'] . '" has been hidden from the public site.',
        ];
        discussion_flash('success', $ucsMessages[$ucsAction]);
    }
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to update the discussion. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;