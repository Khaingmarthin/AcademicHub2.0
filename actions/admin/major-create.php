<?php
/**
 * Admin Majors - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/major-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/majors/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/majors/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    major_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = major_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['major_errors'] = $ucsErrors;
    $_SESSION['major_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO majors (name, short_name, degree_name, description, faculty_id, status)
         VALUES (:name, :short_name, :degree_name, :description, :faculty_id, :status)"
    );
    $ucsStmt->execute([
        ':name'        => $ucsClean['name'],
        ':short_name'  => $ucsClean['short_name'],
        ':degree_name' => $ucsClean['degree_name'],
        ':description' => $ucsClean['description'],
        ':faculty_id'  => $ucsClean['faculty_id'] ?: null,
        ':status'      => $ucsClean['status'],
    ]);

    major_flash('success', 'Major "' . $ucsClean['name'] . '" created successfully.');
} catch (Throwable $e) {
    major_flash('error', 'Unable to create the major. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;