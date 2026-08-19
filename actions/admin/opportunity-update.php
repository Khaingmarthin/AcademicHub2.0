<?php
/**
 * Admin Career Opportunities - update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/opportunity-validation.php';

admin_require_login();

$ucsReturnUrl   = ROOT_URL . '/admin/opportunities/index.php';
$ucsOpportunityId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    opportunity_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

if ($ucsOpportunityId === false) {
    opportunity_flash('error', 'Invalid opportunity selected.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = opportunity_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['opportunity_errors'] = $ucsErrors;
    $_SESSION['opportunity_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/opportunities/edit.php?id=' . $ucsOpportunityId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE career_opportunities
         SET title = :title,
             company = :company,
             location = :location,
             employment_type = :employment_type,
             salary_range = :salary_range,
             description = :description,
             how_to_apply = :how_to_apply,
             expires_at = :expires_at,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':title'           => $ucsClean['title'],
        ':company'         => $ucsClean['company'],
        ':location'        => $ucsClean['location'],
        ':employment_type' => $ucsClean['employment_type'],
        ':salary_range'    => $ucsClean['salary_range'],
        ':description'     => $ucsClean['description'],
        ':how_to_apply'    => $ucsClean['how_to_apply'],
        ':expires_at'      => $ucsClean['expires_at'],
        ':status'          => $ucsClean['status'],
        ':id'              => $ucsOpportunityId,
    ]);

    opportunity_flash('success', 'The opportunity has been updated.');
} catch (Throwable $e) {
    opportunity_flash('error', 'Unable to update the opportunity. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;