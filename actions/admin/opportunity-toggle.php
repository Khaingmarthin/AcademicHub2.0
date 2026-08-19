<?php
/**
 * Admin Career Opportunities - hide / restore handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$ucsReturnUrl   = ROOT_URL . '/admin/opportunities/index.php';
$ucsOpportunityId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$ucsTargetStatus  = (string) ($_POST['status'] ?? '');

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    opportunity_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

if ($ucsOpportunityId === false || !in_array($ucsTargetStatus, ['active', 'hidden'], true)) {
    opportunity_flash('error', 'Invalid opportunity selected.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare("UPDATE career_opportunities SET status = :status WHERE id = :id");
    $ucsStmt->execute([':status' => $ucsTargetStatus, ':id' => $ucsOpportunityId]);

    opportunity_flash('success', $ucsTargetStatus === 'active'
        ? 'The opportunity is visible publicly again.'
        : 'The opportunity has been hidden from the public page.');
} catch (Throwable $e) {
    opportunity_flash('error', 'Unable to change the opportunity visibility. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;