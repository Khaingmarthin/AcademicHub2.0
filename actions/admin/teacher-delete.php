<?php
/**
 * Admin Teachers - Delete handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/teacher-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/teachers/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    teacher_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare("SELECT id, name FROM teachers WHERE id = :id LIMIT 1");
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsTeacher = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsTeacher = null;
}

if ($ucsTeacher === null || $ucsId === false || $ucsId < 1) {
    teacher_flash('error', 'Teacher not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("DELETE FROM teachers WHERE id = :id")->execute([':id' => $ucsId]);
    teacher_flash('success', 'Teacher "' . $ucsTeacher['name'] . '" deleted successfully.');
} catch (Throwable $e) {
    teacher_flash('error', 'Unable to delete the teacher. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
