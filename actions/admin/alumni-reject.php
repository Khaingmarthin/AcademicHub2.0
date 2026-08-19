<?php
/**
 * Admin Alumni - Reject handler.
 *
 * Only an authorised admin can move a pending Alumni Profile to 'rejected'.
 * The student's academic status (student_status) is untouched.
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

$ucsResult = alumni_verification_validate_input($_POST, $pdo, 'rejected');
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    alumni_flash('error', $ucsErrors[0]);
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("UPDATE alumni_profiles SET verification_status = 'rejected' WHERE id = :id")
        ->execute([':id' => $ucsClean['id']]);
    alumni_flash('error', 'Alumni Profile verification rejected.');
} catch (Throwable $e) {
    alumni_flash('error', 'Unable to reject the Alumni Profile. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;