<?php
/**
 * Admin Admissions - Deactivate handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/admission-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/admission/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    admission_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, title FROM admissions WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsAdmission = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsAdmission = null;
}

if ($ucsAdmission === null || $ucsId === false || $ucsId < 1) {
    admission_flash('error', 'Admission not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("UPDATE admissions SET status = 0 WHERE id = :id")->execute([':id' => $ucsId]);
    admission_flash('error', 'Admission "' . $ucsAdmission['title'] . '" has been deactivated.');
} catch (Throwable $e) {
    admission_flash('error', 'Unable to deactivate the admission. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;