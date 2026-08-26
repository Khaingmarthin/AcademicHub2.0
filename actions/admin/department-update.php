<?php
/**
 * Admin Departments - Update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/department-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/faculties-departments/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    department_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, name FROM departments WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    department_flash('error', 'Department not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = department_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['department_errors'] = $ucsErrors;
    $_SESSION['department_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/departments/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE departments
         SET name = :name,
             description = :description,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':name'        => $ucsClean['name'],
        ':description' => $ucsClean['description'],
        ':status'      => $ucsClean['status'],
        ':id'          => $ucsId,
    ]);

    department_flash('success', 'Department "' . $ucsClean['name'] . '" updated successfully.');
} catch (Throwable $e) {
    department_flash('error', 'Unable to update the department. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
