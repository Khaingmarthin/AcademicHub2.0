<?php
/**
 * Admin Students - Ungraduate handler.
 *
 * Reverses the graduation status: student_status -> 'active',
 * clears graduation_year. Used to correct mistakes.
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

$ucsStudentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);

if ($ucsStudentId === false || $ucsStudentId < 1) {
    student_flash('error', 'Invalid student selected.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    // Verify the student exists and is graduated.
    $ucsStmt = $pdo->prepare(
        "SELECT id, name, student_status FROM students WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsStudentId]);
    $ucsStudent = $ucsStmt->fetch() ?: null;

    if ($ucsStudent === null) {
        student_flash('error', 'Student not found.');
        header('Location: ' . $ucsReturnUrl);
        exit;
    }

    if ($ucsStudent['student_status'] !== 'graduated') {
        student_flash('error', 'This student is not marked as graduated.');
        header('Location: ' . $ucsReturnUrl);
        exit;
    }

    // Revert to active status.
    $ucsUpdate = $pdo->prepare(
        "UPDATE students
         SET student_status = 'active',
             graduation_year = NULL
         WHERE id = :id"
    );
    $ucsUpdate->execute([':id' => $ucsStudentId]);

    student_flash('success', 'Student "' . $ucsStudent['name'] . '" has been changed back to active student status.');
} catch (PDOException $e) {
    student_flash('error', 'Unable to update the student. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
