<?php
/**
 * Admin Alumni - Update handler.
 *
 * Edits the alumni-specific profile fields. Student identity, academic
 * status and verification status are managed by their own workflows and
 * are never modified here.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/alumni/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    alumni_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

$ucsResult = alumni_update_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['alumni_errors'] = $ucsErrors;
    $_SESSION['alumni_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/alumni/edit.php?id=' . (int) $ucsId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE alumni_profiles
         SET current_job = :current_job,
             company = :company,
             professional_field = :professional_field,
             skills = :skills,
             bio = :bio,
             career_journey = :career_journey,
             linkedin_url = :linkedin_url,
             github_url = :github_url,
             website_url = :website_url,
             visibility = :visibility
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':current_job'          => $ucsClean['current_job'],
        ':company'              => $ucsClean['company'],
        ':professional_field'   => $ucsClean['professional_field'],
        ':skills'               => $ucsClean['skills'],
        ':bio'                  => $ucsClean['bio'],
        ':career_journey'       => $ucsClean['career_journey'],
        ':linkedin_url'         => $ucsClean['linkedin_url'],
        ':github_url'           => $ucsClean['github_url'],
        ':website_url'          => $ucsClean['website_url'],
        ':visibility'           => $ucsClean['visibility'],
        ':id'                   => $ucsClean['id'],
    ]);

    alumni_flash('success', 'Alumni Profile updated successfully.');
} catch (Throwable $e) {
    alumni_flash('error', 'Unable to update the Alumni Profile. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;