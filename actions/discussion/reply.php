<?php
/**
 * Career Discussions - Reply/comment handler (students + alumni).
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';
require_once __DIR__ . '/../../includes/helpers/notification-helper.php';

student_require_login();

$ucsUser = student_current_user();

$ucsDiscussionId = filter_var($_POST['discussion_id'] ?? null, FILTER_VALIDATE_INT);
$ucsReturnUrl    = BASE_URL . '/career-discussion-details.php?id=' . (int) $ucsDiscussionId;

if ($ucsDiscussionId === false || $ucsDiscussionId < 1) {
    discussion_flash('error', 'That discussion could not be found.');
    header('Location: ' . BASE_URL . '/career-discussions.php');
    exit;
}

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl . '#reply-form');
    exit;
}

// Load the target discussion (hidden discussions cannot be replied to).
$ucsDiscussion = null;
try {
    $ucsStmt = $pdo->prepare("SELECT id, status, author_student_id, title FROM discussions WHERE id = :id LIMIT 1");
    $ucsStmt->execute([':id' => $ucsDiscussionId]);
    $ucsDiscussion = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsDiscussion = null;
}

if ($ucsDiscussion === null) {
    discussion_flash('error', 'That discussion could not be found.');
    header('Location: ' . BASE_URL . '/career-discussions.php');
    exit;
}

if ((string) $ucsDiscussion['status'] === 'hidden') {
    discussion_flash('error', 'This discussion is not available for replies.');
    header('Location: ' . BASE_URL . '/career-discussions.php');
    exit;
}

if ((string) $ucsDiscussion['status'] === 'closed') {
    discussion_flash('error', 'This discussion has been closed and is no longer accepting replies.');
    header('Location: ' . $ucsReturnUrl . '#replies');
    exit;
}

$ucsResult = discussion_validate_reply_input($_POST);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['discussion_reply_errors'] = $ucsErrors;
    $_SESSION['discussion_reply_old']    = $ucsClean['content'];
    header('Location: ' . $ucsReturnUrl . '#reply-form');
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO discussion_replies
            (discussion_id, author_student_id, content, status)
         VALUES
            (:discussion_id, :author_student_id, :content, 'visible')"
    );
    $ucsStmt->execute([
        ':discussion_id'       => $ucsDiscussionId,
        ':author_student_id'   => (int) $ucsUser['id'],
        ':content'             => $ucsClean['content'],
    ]);

    // Notify the discussion author if someone else replies.
    if ((int) $ucsDiscussion['author_student_id'] !== (int) $ucsUser['id']) {
        $ucsNotifLink = BASE_URL . '/career-discussion-details.php?id=' . (int) $ucsDiscussionId;
        ucs_create_notification(
            $pdo,
            (int) $ucsDiscussion['author_student_id'],
            'discussion_reply',
            'New reply on: ' . $ucsDiscussion['title'],
            $ucsUser['name'] . ' replied to your discussion.',
            $ucsNotifLink
        );
    }

    discussion_flash('success', 'Your reply has been posted.');
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to post your reply. Please try again.');
}

header('Location: ' . $ucsReturnUrl . '#replies');
exit;