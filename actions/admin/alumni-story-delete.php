<?php
/**
 * Admin Alumni Stories - Delete handler.
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
        "SELECT id, title, cover_image FROM alumni_stories WHERE id = :id LIMIT 1"
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

try {
    $pdo->prepare("DELETE FROM alumni_stories WHERE id = :id")->execute([':id' => $ucsId]);

    $ucsCoverImage = (string) ($ucsStory['cover_image'] ?? '');
    if ($ucsCoverImage !== '' && strpos($ucsCoverImage, 'uploads/') === 0) {
        $ucsFilePath = UPLOAD_DIR . substr($ucsCoverImage, strlen('uploads/'));
        if (is_file($ucsFilePath)) {
            @unlink($ucsFilePath);
        }
    }

    alumni_story_flash('success', 'Alumni story "' . $ucsStory['title'] . '" deleted successfully.');
} catch (Throwable $e) {
    alumni_story_flash('error', 'Unable to delete the alumni story. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;