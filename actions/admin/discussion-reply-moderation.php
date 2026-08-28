<?php
/**
 * Admin Career Discussions - Hide / restore a reply.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

admin_require_login();

$ucsReturnTo  = (string) ($_POST['return_to'] ?? '');
$ucsReturnDiscussionId = filter_var($_POST['discussion_id'] ?? null, FILTER_VALIDATE_INT);
if ($ucsReturnTo === 'view' && $ucsReturnDiscussionId !== false && $ucsReturnDiscussionId > 0) {
    $ucsReturnUrl = ROOT_URL . '/admin/discussions/view.php?id=' . $ucsReturnDiscussionId;
} else {
    $ucsReturnUrl = ROOT_URL . '/admin/discussions/index.php';
}

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsReplyId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$ucsAction  = (string) ($_POST['action'] ?? '');

if ($ucsReplyId === false || $ucsReplyId < 1
    || !in_array($ucsAction, DISCUSSION_REPLY_STATUSES, true)) {
    discussion_flash('error', 'That reply could not be found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare("SELECT id, discussion_id FROM discussion_replies WHERE id = :id LIMIT 1");
    $ucsStmt->execute([':id' => $ucsReplyId]);
    $ucsReply = $ucsStmt->fetch() ?: null;

    if ($ucsReply === null) {
        discussion_flash('error', 'That reply could not be found.');
    } else {
        $ucsStmt = $pdo->prepare("UPDATE discussion_replies SET status = :status WHERE id = :id");
        $ucsStmt->execute([':status' => $ucsAction, ':id' => $ucsReplyId]);

        discussion_flash(
            'success',
            $ucsAction === 'hidden'
                ? 'The reply has been hidden from the public discussion.'
                : 'The reply has been restored.'
        );
    }
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to update the reply. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;