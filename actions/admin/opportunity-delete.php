<?php
/**
 * Admin Career Opportunities - delete handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

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

try {
    $ucsStmt = $pdo->prepare("DELETE FROM career_opportunities WHERE id = :id");
    $ucsStmt->execute([':id' => $ucsOpportunityId]);

    opportunity_flash('success', 'The opportunity has been deleted.');
} catch (Throwable $e) {
    opportunity_flash('error', 'Unable to delete the opportunity. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;