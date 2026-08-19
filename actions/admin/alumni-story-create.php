<?php
/**
 * Admin Alumni Stories - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-stories-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/alumni/stories/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/alumni/stories/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    alumni_story_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = alumni_story_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    ucs_delete_upload($ucsClean['cover_image'] ?? null);
    $_SESSION['alumni_story_errors'] = $ucsErrors;
    $_SESSION['alumni_story_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

// Published stories without an explicit editorial date are dated today.
$ucsPublicationDate = $ucsClean['publication_date'];
if ($ucsClean['status'] === 'published' && $ucsPublicationDate === null) {
    $ucsPublicationDate = date('Y-m-d');
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO alumni_stories
            (alumni_profile_id, admin_id, title, summary, content,
             career_field, cover_image, publication_date, status)
         VALUES
            (:alumni_profile_id, :admin_id, :title, :summary, :content,
             :career_field, :cover_image, :publication_date, :status)"
    );
    $ucsStmt->execute([
        ':alumni_profile_id' => $ucsClean['alumni_profile_id'],
        ':admin_id'          => (int) $_SESSION['admin_id'],
        ':title'             => $ucsClean['title'],
        ':summary'           => $ucsClean['summary'],
        ':content'           => $ucsClean['content'],
        ':career_field'      => $ucsClean['career_field'] !== '' ? $ucsClean['career_field'] : null,
        ':cover_image'       => $ucsClean['cover_image'],
        ':publication_date'  => $ucsPublicationDate,
        ':status'            => $ucsClean['status'],
    ]);

    alumni_story_flash('success', 'Alumni story "' . $ucsClean['title'] . '" created successfully.');
} catch (Throwable $e) {
    alumni_story_flash('error', 'Unable to create the alumni story. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;