<?php
/**
 * Alumni self-service - submit story handler.
 *
 * Only a verified alumnus may submit a story. The story is stored with
 * status 'pending' and must be approved by an admin before it appears
 * publicly. Alumni cannot set their own status to 'published' directly.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';
require_once __DIR__ . '/../../includes/helpers/alumni-stories-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-upload.php';

student_require_login();

$ucsProfile = alumni_current_profile($pdo);
if ($ucsProfile === null) {
    header('Location: ' . BASE_URL . '/alumni.php');
    exit;
}

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['alumni_story_flash'] = ['type' => 'error', 'message' => 'Your session has expired. Please try again.'];
    header('Location: ' . BASE_URL . '/alumni-story-create.php');
    exit;
}

$ucsResult = alumni_story_submit_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    ucs_delete_upload($ucsClean['cover_image'] ?? null);
    $_SESSION['alumni_story_errors'] = $ucsErrors;
    $_SESSION['alumni_story_old']    = $ucsClean;
    header('Location: ' . BASE_URL . '/alumni-story-create.php');
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO alumni_stories
            (alumni_profile_id, title, summary, content,
             career_field, cover_image, status)
         VALUES
            (:alumni_profile_id, :title, :summary, :content,
             :career_field, :cover_image, 'pending')"
    );
    $ucsStmt->execute([
        ':alumni_profile_id' => $ucsClean['alumni_profile_id'],
        ':title'             => $ucsClean['title'],
        ':summary'           => $ucsClean['summary'],
        ':content'           => $ucsClean['content'],
        ':career_field'      => $ucsClean['career_field'] !== '' ? $ucsClean['career_field'] : null,
        ':cover_image'       => $ucsClean['cover_image'],
    ]);

    $_SESSION['alumni_story_flash'] = ['type' => 'success', 'message' => 'Your story has been submitted for review. You will see it published once it is approved by the university office.'];
} catch (Throwable $e) {
    $_SESSION['alumni_story_flash'] = ['type' => 'error', 'message' => 'Unable to submit your story. Please try again.'];
}

header('Location: ' . BASE_URL . '/alumni-dashboard.php');
exit;
