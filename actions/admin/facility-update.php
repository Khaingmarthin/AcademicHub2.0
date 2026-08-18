<?php
/**
 * Admin Facilities - Update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/facility-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/facilities/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    facility_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, name FROM facilities WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    facility_flash('error', 'Facility not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = facility_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['facility_errors'] = $ucsErrors;
    $_SESSION['facility_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/facilities/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE facilities
         SET name = :name,
             image = :image,
             description = :description,
             location = :location,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':name'        => $ucsClean['name'],
        ':image'       => $ucsClean['image'],
        ':description' => $ucsClean['description'],
        ':location'    => $ucsClean['location'],
        ':status'      => $ucsClean['status'],
        ':id'          => $ucsId,
    ]);

    facility_flash('success', 'Facility "' . $ucsClean['name'] . '" updated successfully.');
} catch (Throwable $e) {
    facility_flash('error', 'Unable to update the facility. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
