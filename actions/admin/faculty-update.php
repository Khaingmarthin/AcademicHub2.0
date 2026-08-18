<?php
/**
 * Admin Faculties - Update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/faculty-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/faculties/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    faculty_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, name FROM faculties WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    faculty_flash('error', 'Faculty not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = faculty_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['faculty_errors'] = $ucsErrors;
    $_SESSION['faculty_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/faculties/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE faculties
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

    faculty_flash('success', 'Faculty "' . $ucsClean['name'] . '" updated successfully.');
} catch (Throwable $e) {
    faculty_flash('error', 'Unable to update the faculty. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
