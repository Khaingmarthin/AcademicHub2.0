<?php
/**
 * Admin Classrooms - Delete handler.
 *
 * A classroom with enrolled students cannot be deleted (ON DELETE RESTRICT
 * in the schema). Its timetables are removed first to honour the schema's
 * ON DELETE CASCADE behaviour.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/classroom-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/classrooms/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    classroom_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, classroom_name FROM classrooms WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsClassroom = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsClassroom = null;
}

if ($ucsClassroom === null || $ucsId === false || $ucsId < 1) {
    classroom_flash('error', 'Classroom not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE classroom_id = :id");
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsStudentCount = (int) $ucsStmt->fetchColumn();
} catch (PDOException $e) {
    $ucsStudentCount = 1;
}

if ($ucsStudentCount > 0) {
    classroom_flash(
        'error',
        'Classroom "' . $ucsClassroom['classroom_name'] . '" still has enrolled students and cannot be deleted. Deactivate it instead.'
    );
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM timetables WHERE classroom_id = :id")->execute([':id' => $ucsId]);
    $pdo->prepare("DELETE FROM classrooms WHERE id = :id")->execute([':id' => $ucsId]);
    $pdo->commit();

    classroom_flash('success', 'Classroom "' . $ucsClassroom['classroom_name'] . '" deleted successfully.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    classroom_flash('error', 'Unable to delete the classroom. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;