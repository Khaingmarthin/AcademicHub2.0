<?php
/**
 * Admin Majors - Update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/major-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/majors/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    major_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, name FROM majors WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    major_flash('error', 'Major not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = major_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['major_errors'] = $ucsErrors;
    $_SESSION['major_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/majors/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE majors
         SET name = :name,
             short_name = :short_name,
             degree_name = :degree_name,
             description = :description,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':name'        => $ucsClean['name'],
        ':short_name'  => $ucsClean['short_name'],
        ':degree_name' => $ucsClean['degree_name'],
        ':description' => $ucsClean['description'],
        ':status'      => $ucsClean['status'],
        ':id'          => $ucsId,
    ]);

    major_flash('success', 'Major "' . $ucsClean['name'] . '" updated successfully.');
} catch (Throwable $e) {
    major_flash('error', 'Unable to update the major. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;