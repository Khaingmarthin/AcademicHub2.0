<?php
/**
 * Alumni self-management - delete career opportunity handler.
 *
 * Only the authenticated, verified alumnus who posted the opportunity may
 * delete it. Guards: student login + verified alumni profile + ownership +
 * CSRF verification.
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

$ucsStudentId   = (int) $ucsProfile['student_id'];
$ucsReturnUrl   = BASE_URL . '/my-opportunities.php';
$ucsOpportunityId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    opportunity_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

if ($ucsOpportunityId === false || !opportunity_owns($pdo, $ucsOpportunityId, $ucsStudentId)) {
    opportunity_flash('error', 'This opportunity no longer exists.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "DELETE FROM career_opportunities
         WHERE id = :id AND posted_by_student_id = :student_id"
    );
    $ucsStmt->execute([':id' => $ucsOpportunityId, ':student_id' => $ucsStudentId]);

    opportunity_flash('success', 'The opportunity has been deleted.');
} catch (Throwable $e) {
    opportunity_flash('error', 'Unable to delete the opportunity. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;