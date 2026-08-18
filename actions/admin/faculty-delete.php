<?php
/**
 * Admin Faculties - Delete handler.
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
    $ucsFaculty = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsFaculty = null;
}

if ($ucsFaculty === null || $ucsId === false || $ucsId < 1) {
    faculty_flash('error', 'Faculty not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("DELETE FROM faculties WHERE id = :id")->execute([':id' => $ucsId]);
    faculty_flash('success', 'Faculty "' . $ucsFaculty['name'] . '" deleted successfully.');
} catch (PDOException $e) {
    faculty_flash('error', 'Unable to delete the faculty. Please try again.');
} catch (Throwable $e) {
    faculty_flash('error', 'Unable to delete the faculty. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
