<?php
/**
 * Career Discussions - Create discussion handler (students + alumni).
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

student_require_login();

$ucsUser     = student_current_user();
$ucsFormUrl  = BASE_URL . '/career-discussion-create.php';

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsFormUrl);
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

$ucsDiscussionId = null;
try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO discussions
            (category_id, author_student_id, title, content, status, is_pinned)
         VALUES
            (:category_id, :author_student_id, :title, :content, 'open', 0)"
    );
    $ucsStmt->execute([
        ':category_id'       => $ucsClean['category_id'],
        ':author_student_id' => (int) $ucsUser['id'],
        ':title'             => $ucsClean['title'],
        ':content'           => $ucsClean['content'],
    ]);
    $ucsDiscussionId = (int) $pdo->lastInsertId();
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to create your discussion. Please try again.');
    header('Location: ' . $ucsFormUrl);
    exit;
}

discussion_flash('success', 'Your career question has been posted. Alumni and students can now reply.');
header('Location: ' . BASE_URL . '/career-discussion-details.php?id=' . $ucsDiscussionId);
exit;