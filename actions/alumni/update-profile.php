<?php
/**
 * Alumni self-management - update own profile handler.
 *
 * Only an authenticated, verified alumnus may update their own public
 * profile. Guards: student login + verified alumni profile ownership, plus
 * CSRF verification. Handles the optional profile photo upload.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-upload.php';

student_require_login();

$ucsProfile = alumni_current_profile($pdo);
if ($ucsProfile === null) {
    header('Location: ' . BASE_URL . '/alumni.php');
    exit;
}

$ucsProfileId = (int) $ucsProfile['id'];

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['student_alumni_flash'] = ['type' => 'error', 'message' => 'Your session has expired. Please try again.'];
    header('Location: ' . BASE_URL . '/alumni-edit.php');
    exit;
}

// Validate the text fields first; never persist anything on validation errors.
$ucsResult = alumni_update_validate_input($_POST, $pdo, $ucsProfileId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

// Mentorship-specific settings (contact email + areas) are validated
// separately so the admin profile editor never touches them.
$ucsMentorshipResult = mentorship_validate_profile_mentorship_input($_POST, $pdo);
$ucsMentorshipClean  = $ucsMentorshipResult['clean'];
$ucsErrors           = array_merge($ucsErrors, $ucsMentorshipResult['errors']);

// A suspended mentor cannot re-enable availability through the form.
$ucsSuspended = (int) $ucsProfile['mentorship_suspended'] === 1;
if ($ucsSuspended && $ucsClean['mentorship_available'] === 1) {
    $ucsClean['mentorship_available'] = 0;
}

if (!empty($ucsErrors)) {
    $_SESSION['student_alumni_errors'] = $ucsErrors;
    $_SESSION['student_alumni_old']    = $ucsClean + $ucsMentorshipClean;
    header('Location: ' . BASE_URL . '/alumni-edit.php');
    exit;
}

// Optional profile photo.
$ucsPhotoPath = (string) ($ucsProfile['profile_photo'] ?? '');
$ucsNewPhoto  = null;

try {
    $ucsNewPhoto = ucs_handle_upload('profile_photo', ['jpg', 'jpeg', 'png', 'webp', 'gif'], 2 * 1024 * 1024);
} catch (RuntimeException $e) {
    $ucsErrors[] = $e->getMessage();
}

$ucsRemovePhoto = isset($_POST['remove_photo']);

if (!empty($ucsErrors)) {
    $_SESSION['student_alumni_errors'] = $ucsErrors;
    $_SESSION['student_alumni_old']    = $ucsClean + $ucsMentorshipClean;
    header('Location: ' . BASE_URL . '/alumni-edit.php');
    exit;
}

if ($ucsNewPhoto !== null) {
    // New photo replaces any previous one.
    if ($ucsPhotoPath !== '') {
        ucs_delete_upload($ucsPhotoPath);
    }
    $ucsPhotoPath = $ucsNewPhoto;
} elseif ($ucsRemovePhoto && $ucsPhotoPath !== '') {
    ucs_delete_upload($ucsPhotoPath);
    $ucsPhotoPath = '';
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
             mentorship_available = :mentorship_available,
             mentorship_contact_email = :mentorship_contact_email,
             visibility = :visibility,
             profile_photo = :profile_photo
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
        ':mentorship_available' => $ucsClean['mentorship_available'],
        ':mentorship_contact_email' => $ucsMentorshipClean['mentorship_contact_email'] !== ''
            ? $ucsMentorshipClean['mentorship_contact_email']
            : null,
        ':visibility'           => $ucsClean['visibility'],
        ':profile_photo'        => $ucsPhotoPath !== '' ? $ucsPhotoPath : null,
        ':id'                   => $ucsProfileId,
    ]);

    // Sync the selected mentorship areas (delete + reinsert).
    $ucsDelete = $pdo->prepare("DELETE FROM alumni_mentorship_areas WHERE alumni_profile_id = :profile_id");
    $ucsDelete->execute([':profile_id' => $ucsProfileId]);
    if (count($ucsMentorshipClean['mentorship_areas']) > 0) {
        $ucsInsert = $pdo->prepare(
            "INSERT INTO alumni_mentorship_areas (alumni_profile_id, mentorship_area_id)
             VALUES (:profile_id, :area_id)"
        );
        foreach ($ucsMentorshipClean['mentorship_areas'] as $ucsAreaId) {
            $ucsInsert->execute([':profile_id' => $ucsProfileId, ':area_id' => $ucsAreaId]);
        }
    }

    $_SESSION['student_alumni_flash'] = ['type' => 'success', 'message' => 'Your alumni profile has been updated successfully.'];
} catch (Throwable $e) {
    $_SESSION['student_alumni_flash'] = ['type' => 'error', 'message' => 'Unable to update your alumni profile. Please try again.'];
}

header('Location: ' . BASE_URL . '/alumni-details.php?id=' . $ucsProfileId);
exit;