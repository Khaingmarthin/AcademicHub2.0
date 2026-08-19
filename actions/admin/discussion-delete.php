<?php
/**
 * Admin Career Discussions - Delete a discussion and all its replies.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/discussions/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsDiscussionId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($ucsDiscussionId === false || $ucsDiscussionId < 1) {
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
        // MyISAM has no cascade support; remove replies + reports manually.
        $ucsStmt = $pdo->prepare("DELETE FROM discussion_replies WHERE discussion_id = :id");
        $ucsStmt->execute([':id' => $ucsDiscussionId]);

        $ucsStmt = $pdo->prepare(
            "DELETE FROM discussion_reports WHERE content_type = 'reply' AND content_id NOT IN (SELECT id FROM discussion_replies)"
        );
        $ucsStmt->execute();

        $ucsStmt = $pdo->prepare("DELETE FROM discussion_reports WHERE content_type = 'discussion' AND content_id = :id");
        $ucsStmt->execute([':id' => $ucsDiscussionId]);

        $ucsStmt = $pdo->prepare("DELETE FROM discussions WHERE id = :id");
        $ucsStmt->execute([':id' => $ucsDiscussionId]);

        discussion_flash('success', 'Discussion "' . $ucsDiscussion['title'] . '" and its replies have been deleted.');
    }
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to delete the discussion. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;