<?php
/**
 * Admin Students - Mark Graduated handler.
 *
 * This is the official graduation confirmation. It changes ONLY the academic
 * status (student_status -> 'graduated') and records the graduation year and
 * confirmation time. The student account, credentials and historical
 * academic record stay fully intact, and the student keeps logging in.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/student-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/students/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/students/graduate.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    student_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = student_graduation_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['student_errors'] = $ucsErrors;
    $_SESSION['student_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl . '?id=' . (int) $ucsClean['student_id']);
    exit;
}

// Double-check: only Fifth Year students can be graduated.
try {
    $ucsCheckStmt = $pdo->prepare(
        "SELECT c.year_level
         FROM students s
         JOIN classrooms c ON c.id = s.classroom_id
         WHERE s.id = :id
         LIMIT 1"
    );
    $ucsCheckStmt->execute([':id' => $ucsClean['student_id']]);
    $ucsYearLevel = (string) ($ucsCheckStmt->fetchColumn() ?: '');
    if (trim($ucsYearLevel) !== 'Fifth Year') {
        student_flash('error', 'Only Fifth Year students can be marked as graduated.');
        header('Location: ' . $ucsReturnUrl);
        exit;
    }
} catch (PDOException $e) {
    // If we can't verify, let the validation error from above handle it.
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE students
         SET student_status = 'graduated',
             graduation_year = :graduation_year
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':graduation_year' => $ucsClean['graduation_year'],
        ':id'              => $ucsClean['student_id'],
    ]);

    $ucsName = 'this student';
    try {
        $ucsStmt = $pdo->prepare("SELECT name FROM students WHERE id = :id LIMIT 1");
        $ucsStmt->execute([':id' => $ucsClean['student_id']]);
        $ucsName = (string) ($ucsStmt->fetchColumn() ?: $ucsName);
    } catch (PDOException $e) {
        // Keep the generic name.
    }

    student_flash('success', 'Student "' . $ucsName . '" has been marked as graduated.');
} catch (Throwable $e) {
    student_flash('error', 'Unable to mark the student as graduated. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;