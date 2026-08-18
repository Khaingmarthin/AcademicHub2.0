<?php
/**
 * Admin Students - Delete handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/student-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/students/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    student_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, name FROM students WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsStudent = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsStudent = null;
}

if ($ucsStudent === null || $ucsId === false || $ucsId < 1) {
    student_flash('error', 'Student not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("DELETE FROM students WHERE id = :id")->execute([':id' => $ucsId]);
    student_flash('success', 'Student "' . $ucsStudent['name'] . '" deleted successfully.');
} catch (Throwable $e) {
    student_flash('error', 'Unable to delete the student. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;