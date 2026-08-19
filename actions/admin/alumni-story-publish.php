<?php
/**
 * Admin Alumni Stories - Publish handler.
 *
 * Marks a draft or unpublished story as published. The editorial
 * publication date is set to today when the story does not already have one.
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
        "SELECT id, title, status, publication_date FROM alumni_stories WHERE id = :id LIMIT 1"
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

if ((string) $ucsStory['status'] === 'published') {
    alumni_story_flash('success', 'Alumni story "' . $ucsStory['title'] . '" is already published.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE alumni_stories
         SET status = 'published',
             publication_date = COALESCE(publication_date, :today)
         WHERE id = :id"
    );
    $ucsStmt->execute([':today' => date('Y-m-d'), ':id' => $ucsId]);

    alumni_story_flash('success', 'Alumni story "' . $ucsStory['title'] . '" published successfully.');
} catch (Throwable $e) {
    alumni_story_flash('error', 'Unable to publish the alumni story. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;