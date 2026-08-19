<?php
/**
 * Admin Career Discussions - Pin / unpin a discussion.
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
$ucsPinned       = (int) ($_POST['pinned'] ?? 0) === 1 ? 1 : 0;

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
        $ucsStmt = $pdo->prepare("UPDATE discussions SET is_pinned = :pinned WHERE id = :id");
        $ucsStmt->execute([':pinned' => $ucsPinned, ':id' => $ucsDiscussionId]);
        discussion_flash(
            'success',
            $ucsPinned === 1
                ? 'Discussion "' . $ucsDiscussion['title'] . '" has been pinned.'
                : 'Discussion "' . $ucsDiscussion['title'] . '" has been unpinned.'
        );
    }
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to update the discussion. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;