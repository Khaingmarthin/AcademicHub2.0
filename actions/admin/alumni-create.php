<?php
/**
 * Admin Alumni - Create Alumni Profile handler.
 *
 * Creates an alumni profile for an officially graduated student. The profile
 * starts with verification_status 'pending'; only an authorised admin can
 * later verify or reject it. The student's own account is never replaced.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/alumni/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/alumni/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    alumni_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = alumni_create_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['alumni_errors'] = $ucsErrors;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO alumni_profiles (student_id)
         VALUES (:student_id)"
    );
    $ucsStmt->execute([':student_id' => $ucsClean['student_id']]);

    $ucsName = 'this student';
    try {
        $ucsStmt = $pdo->prepare("SELECT name FROM students WHERE id = :id LIMIT 1");
        $ucsStmt->execute([':id' => $ucsClean['student_id']]);
        $ucsName = (string) ($ucsStmt->fetchColumn() ?: $ucsName);
    } catch (PDOException $e) {
        // Keep the generic name.
    }

    alumni_flash('success', 'Alumni Profile created for "' . $ucsName . '". It is now pending verification.');
} catch (Throwable $e) {
    if ((string) $e->getCode() === '23000') {
        alumni_flash('error', 'This student already has an Alumni Profile.');
    } else {
        alumni_flash('error', 'Unable to create the Alumni Profile. Please try again.');
    }
}

header('Location: ' . $ucsReturnUrl);
exit;