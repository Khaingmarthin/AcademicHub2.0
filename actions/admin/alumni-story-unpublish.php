<?php
/**
 * Admin Alumni Stories - Unpublish handler.
 *
 * Takes a published story down from the public page while keeping it in the
 * admin module for later republishing. The editorial publication date is
 * preserved so republishing keeps the original date.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-stories-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/alumni/stories/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    alumni_story_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, title, status FROM alumni_stories WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsStory = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsStory = null;
}

if ($ucsStory === null || $ucsId === false || $ucsId < 1) {
    alumni_story_flash('error', 'Alumni story not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

if ((string) $ucsStory['status'] !== 'published') {
    alumni_story_flash('success', 'Alumni story "' . $ucsStory['title'] . '" is not currently published.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("UPDATE alumni_stories SET status = 'unpublished' WHERE id = :id")->execute([':id' => $ucsId]);

    alumni_story_flash('success', 'Alumni story "' . $ucsStory['title'] . '" unpublished successfully.');
} catch (Throwable $e) {
    alumni_story_flash('error', 'Unable to unpublish the alumni story. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;