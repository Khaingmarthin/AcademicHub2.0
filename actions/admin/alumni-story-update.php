<?php
/**
 * Admin Alumni Stories - Update handler.
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
        "SELECT id, title, cover_image, publication_date, status
         FROM alumni_stories
         WHERE id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    alumni_story_flash('error', 'Alumni story not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = alumni_story_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    ucs_delete_upload($ucsClean['cover_image'] ?? null);
    $_SESSION['alumni_story_errors'] = $ucsErrors;
    $_SESSION['alumni_story_old']    = array_merge($ucsClean, ['id' => $ucsId]);
    header('Location: ' . ROOT_URL . '/admin/alumni/stories/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsRemoveCover = isset($_POST['remove_cover']) && (string) $_POST['remove_cover'] === '1';

    $ucsCoverImage = $ucsClean['cover_image'] !== null
        ? $ucsClean['cover_image']
        : ($ucsRemoveCover ? null : $ucsExisting['cover_image']);

    // Preserve the editorial date unless the story is published without one.
    $ucsPublicationDate = $ucsClean['publication_date'];
    if ($ucsClean['status'] === 'published' && $ucsPublicationDate === null) {
        $ucsPublicationDate = $ucsExisting['publication_date'] ?? date('Y-m-d');
    }

    $ucsStmt = $pdo->prepare(
        "UPDATE alumni_stories
         SET alumni_profile_id = :alumni_profile_id,
             title = :title,
             summary = :summary,
             content = :content,
             career_field = :career_field,
             cover_image = :cover_image,
             publication_date = :publication_date,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':alumni_profile_id' => $ucsClean['alumni_profile_id'],
        ':title'             => $ucsClean['title'],
        ':summary'           => $ucsClean['summary'],
        ':content'           => $ucsClean['content'],
        ':career_field'      => $ucsClean['career_field'] !== '' ? $ucsClean['career_field'] : null,
        ':cover_image'       => $ucsCoverImage,
        ':publication_date'  => $ucsPublicationDate,
        ':status'            => $ucsClean['status'],
        ':id'                => $ucsId,
    ]);

    if ($ucsClean['cover_image'] !== null || $ucsRemoveCover) {
        ucs_delete_upload($ucsExisting['cover_image'] ?? null);
    }

    alumni_story_flash('success', 'Alumni story "' . $ucsClean['title'] . '" updated successfully.');
} catch (Throwable $e) {
    alumni_story_flash('error', 'Unable to update the alumni story. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;