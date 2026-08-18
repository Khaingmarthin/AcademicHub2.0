<?php
/**
 * Admin Faculties - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/faculty-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/faculties/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/faculties/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    faculty_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = faculty_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['faculty_errors'] = $ucsErrors;
    $_SESSION['faculty_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO faculties (name, description, status)
         VALUES (:name, :description, :status)"
    );
    $ucsStmt->execute([
        ':name'        => $ucsClean['name'],
        ':description' => $ucsClean['description'],
        ':status'      => $ucsClean['status'],
    ]);

    faculty_flash('success', 'Faculty "' . $ucsClean['name'] . '" created successfully.');
} catch (Throwable $e) {
    faculty_flash('error', 'Unable to create the faculty. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
