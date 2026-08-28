<?php
/**
 * Admin Career Discussions - Change a discussion status.
 *
 * action = 'open'   -> restore / reopen a discussion
 * action = 'closed' -> lock a discussion (no new replies)
 * action = 'hidden' -> remove a discussion from the public site
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