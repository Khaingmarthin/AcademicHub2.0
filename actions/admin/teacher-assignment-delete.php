<?php
/**
 * Admin Teacher Course Assignments - Delete handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/teacher-assignment-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/teacher-assignments/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    teacher_assignment_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare("SELECT id FROM teacher_course_assignments WHERE id = :id LIMIT 1");
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsAssignment = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsAssignment = null;
}

if ($ucsAssignment === null || $ucsId === false || $ucsId < 1) {
    teacher_assignment_flash('error', 'Assignment not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("DELETE FROM teacher_course_assignments WHERE id = :id")->execute([':id' => $ucsId]);
    teacher_assignment_flash('success', 'Teaching assignment deleted successfully.');
} catch (Throwable $e) {
    teacher_assignment_flash('error', 'Unable to delete the assignment. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
