<?php
/**
 * Career Discussions - Update discussion handler (author only).
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

student_require_login();

$ucsUser    = student_current_user();
$ucsId      = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$ucsFormUrl = BASE_URL . '/career-discussion-edit.php?id=' . ($ucsId ?: 0);

if ($ucsId === false || $ucsId < 1) {
    discussion_flash('error', 'Invalid discussion.');
    header('Location: ' . BASE_URL . '/career-discussions.php');
    exit;
}

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, author_student_id, status
         FROM discussions
         WHERE id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsDiscussion = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsDiscussion = null;
}

if ($ucsDiscussion === null) {
    discussion_flash('error', 'Discussion not found.');
    header('Location: ' . BASE_URL . '/career-discussions.php');
    exit;
}

if ((int) $ucsDiscussion['author_student_id'] !== (int) $ucsUser['id']) {
    discussion_flash('error', 'You can only edit your own discussions.');
    header('Location: ' . BASE_URL . '/career-discussion-details.php?id=' . $ucsId);
    exit;
}

if ((string) $ucsDiscussion['status'] === 'closed') {
    discussion_flash('error', 'This discussion has been closed and cannot be edited.');
    header('Location: ' . BASE_URL . '/career-discussion-details.php?id=' . $ucsId);
    exit;
}

$ucsResult = discussion_validate_discussion_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['discussion_errors'] = $ucsErrors;
    $_SESSION['discussion_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE discussions
         SET category_id = :category_id,
             title       = :title,
             content     = :content,
             updated_at  = NOW()
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':category_id' => $ucsClean['category_id'],
        ':title'       => $ucsClean['title'],
        ':content'     => $ucsClean['content'],
        ':id'          => $ucsId,
    ]);
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to update your discussion. Please try again.');
    header('Location: ' . $ucsFormUrl);
    exit;
}

discussion_flash('success', 'Your discussion has been updated.');
header('Location: ' . BASE_URL . '/career-discussion-details.php?id=' . $ucsId);
exit;
