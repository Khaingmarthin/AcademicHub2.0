<?php
/**
 * Alumni self-management - create career opportunity handler.
 *
 * Only an authenticated, verified alumnus may post a career opportunity.
 * Guards: student login + verified alumni profile + CSRF verification.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';
require_once __DIR__ . '/../../includes/helpers/opportunity-validation.php';

student_require_login();

$ucsProfile = alumni_current_profile($pdo);
if ($ucsProfile === null) {
    header('Location: ' . BASE_URL . '/alumni.php');
    exit;
}

$ucsStudentId = (int) $ucsProfile['student_id'];
$ucsReturnUrl = BASE_URL . '/my-opportunities.php';
$ucsFormUrl   = BASE_URL . '/opportunity-create.php';

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    opportunity_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = opportunity_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['opportunity_errors'] = $ucsErrors;
    $_SESSION['opportunity_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO career_opportunities
            (posted_by_student_id, title, company, location, employment_type,
             salary_range, description, how_to_apply, expires_at, status)
         VALUES
            (:posted_by_student_id, :title, :company, :location, :employment_type,
             :salary_range, :description, :how_to_apply, :expires_at, 'active')"
    );
    $ucsStmt->execute([
        ':posted_by_student_id' => $ucsStudentId,
        ':title'                => $ucsClean['title'],
        ':company'              => $ucsClean['company'],
        ':location'             => $ucsClean['location'],
        ':employment_type'      => $ucsClean['employment_type'],
        ':salary_range'         => $ucsClean['salary_range'],
        ':description'          => $ucsClean['description'],
        ':how_to_apply'         => $ucsClean['how_to_apply'],
        ':expires_at'           => $ucsClean['expires_at'],
    ]);

    opportunity_flash('success', 'Your career opportunity "' . $ucsClean['title'] . '" is now live.');
} catch (Throwable $e) {
    opportunity_flash('error', 'Unable to publish the opportunity. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;