<?php
/**
 * Admin Departments - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/department-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/departments/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/departments/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    department_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = department_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['department_errors'] = $ucsErrors;
    $_SESSION['department_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO departments (faculty_id, name, description, status)
         VALUES (:faculty_id, :name, :description, :status)"
    );
    $ucsStmt->execute([
        ':faculty_id'  => $ucsClean['faculty_id'],
        ':name'        => $ucsClean['name'],
        ':description' => $ucsClean['description'],
        ':status'      => $ucsClean['status'],
    ]);

    department_flash('success', 'Department "' . $ucsClean['name'] . '" created successfully.');
} catch (Throwable $e) {
    department_flash('error', 'Unable to create the department. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
