<?php
/**
 * Admin Facilities - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/facility-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/facilities/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/facilities/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    facility_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = facility_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['facility_errors'] = $ucsErrors;
    $_SESSION['facility_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO facilities (name, image, description, location, status)
         VALUES (:name, :image, :description, :location, :status)"
    );
    $ucsStmt->execute([
        ':name'        => $ucsClean['name'],
        ':image'       => $ucsClean['image'],
        ':description' => $ucsClean['description'],
        ':location'    => $ucsClean['location'],
        ':status'      => $ucsClean['status'],
    ]);

    facility_flash('success', 'Facility "' . $ucsClean['name'] . '" created successfully.');
} catch (Throwable $e) {
    facility_flash('error', 'Unable to create the facility. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
