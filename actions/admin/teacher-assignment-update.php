<?php
/**
 * Admin Teacher Course Assignments - Update handler.
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
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    teacher_assignment_flash('error', 'Assignment not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = teacher_assignment_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['teacher_assignment_errors'] = $ucsErrors;
    $_SESSION['teacher_assignment_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/teacher-assignments/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE teacher_course_assignments
         SET teacher_id = :teacher_id,
             course_id = :course_id,
             classroom_id = :classroom_id
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':teacher_id'   => $ucsClean['teacher_id'],
        ':course_id'    => $ucsClean['course_id'],
        ':classroom_id' => $ucsClean['classroom_id'],
        ':id'           => $ucsId,
    ]);

    teacher_assignment_flash('success', 'Assignment updated successfully.');
} catch (Throwable $e) {
    teacher_assignment_flash('error', 'Unable to update the assignment. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
