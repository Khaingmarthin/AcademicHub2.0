<?php
/**
 * Admin Majors - Delete handler.
 *
 * A major that is still referenced by courses, classrooms or news targets
 * cannot be deleted (the schema uses ON DELETE RESTRICT); the administrator
 * is told to deactivate the major instead.
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
    $ucsMajor = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsMajor = null;
}

if ($ucsMajor === null || $ucsId === false || $ucsId < 1) {
    major_flash('error', 'Major not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsDependentCount = 0;
try {
    foreach (['courses', 'classrooms', 'news_targets'] as $ucsTable) {
        $ucsStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM `$ucsTable` WHERE major_id = :id"
        );
        $ucsStmt->execute([':id' => $ucsId]);
        $ucsDependentCount += (int) $ucsStmt->fetchColumn();
    }
} catch (PDOException $e) {
    $ucsDependentCount = 1;
}

if ($ucsDependentCount > 0) {
    major_flash(
        'error',
        'Major "' . $ucsMajor['name'] . '" has linked courses, classrooms or news and cannot be deleted. Deactivate it instead.'
    );
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("DELETE FROM majors WHERE id = :id")->execute([':id' => $ucsId]);
    major_flash('success', 'Major "' . $ucsMajor['name'] . '" deleted successfully.');
} catch (PDOException $e) {
    major_flash('error', 'Unable to delete the major. Please try again.');
} catch (Throwable $e) {
    major_flash('error', 'Unable to delete the major. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;