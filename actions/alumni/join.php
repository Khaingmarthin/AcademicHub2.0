<?php
/**
 * Alumni self-service - join the alumni community handler.
 *
 * Lets an officially graduated student apply for an alumni profile. The
 * profile is stored with verification_status 'pending' and must then be
 * verified by an authorised admin through the existing verification
 * workflow. A previously rejected profile is resubmitted by moving it back
 * to 'pending'. Guards: student login, graduated status, no existing
 * pending/verified profile, plus CSRF verification.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';

student_require_login();

$ucsReturnUrl = BASE_URL . '/alumni-join.php';

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['student_alumni_flash'] = ['type' => 'error', 'message' => 'Your session has expired. Please try again.'];
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = alumni_join_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['student_alumni_errors'] = $ucsErrors;
    $_SESSION['student_alumni_old']    = $ucsClean;
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, verification_status FROM alumni_profiles WHERE student_id = :student_id LIMIT 1"
    );
    $ucsStmt->execute([':student_id' => $ucsClean['student_id']]);
    $ucsExisting = $ucsStmt->fetch() ?: null;

    if ($ucsExisting !== null && $ucsExisting['verification_status'] === 'rejected') {
        $pdo->prepare(
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
                 visibility = :visibility,
                 verification_status = 'pending'
             WHERE id = :id"
        )->execute([
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
            ':id'                   => $ucsExisting['id'],
        ]);
        $_SESSION['student_alumni_flash'] = ['type' => 'success', 'message' => 'Your alumni profile has been resubmitted for review.'];
    } else {
        $pdo->prepare(
            "INSERT INTO alumni_profiles
                (student_id, current_job, company, professional_field, skills,
                 bio, career_journey, linkedin_url, github_url, website_url,
                 visibility)
             VALUES
                (:student_id, :current_job, :company, :professional_field, :skills,
                 :bio, :career_journey, :linkedin_url, :github_url, :website_url,
                 :visibility)"
        )->execute([
            ':student_id'           => $ucsClean['student_id'],
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
        ]);
        $_SESSION['student_alumni_flash'] = ['type' => 'success', 'message' => 'Your alumni profile has been submitted for review.'];
    }
} catch (Throwable $e) {
    $_SESSION['student_alumni_flash'] = ['type' => 'error', 'message' => 'Unable to submit your alumni profile. Please try again.'];
}

header('Location: ' . BASE_URL . '/alumni-dashboard.php');
exit;