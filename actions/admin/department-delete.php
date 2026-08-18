<?php
/**
 * Admin Departments - Delete handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/department-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/departments/index.php';

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
    $ucsDepartment = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsDepartment = null;
}

if ($ucsDepartment === null || $ucsId === false || $ucsId < 1) {
    department_flash('error', 'Department not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("DELETE FROM departments WHERE id = :id")->execute([':id' => $ucsId]);
    department_flash('success', 'Department "' . $ucsDepartment['name'] . '" deleted successfully.');
} catch (Throwable $e) {
    department_flash('error', 'Unable to delete the department. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
