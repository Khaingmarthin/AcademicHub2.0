<?php
/**
 * Admin Facilities - Delete handler.
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
    $ucsFacility = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsFacility = null;
}

if ($ucsFacility === null || $ucsId === false || $ucsId < 1) {
    facility_flash('error', 'Facility not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("DELETE FROM facilities WHERE id = :id")->execute([':id' => $ucsId]);
    facility_flash('success', 'Facility "' . $ucsFacility['name'] . '" deleted successfully.');
} catch (Throwable $e) {
    facility_flash('error', 'Unable to delete the facility. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
