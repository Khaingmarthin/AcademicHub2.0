<?php
/**
 * Admin Teacher Course Assignments - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/teacher-assignment-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/teacher-assignments/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/teacher-assignments/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    teacher_assignment_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = teacher_assignment_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['teacher_assignment_errors'] = $ucsErrors;
    $_SESSION['teacher_assignment_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO teacher_course_assignments (teacher_id, course_id, classroom_id)
         VALUES (:teacher_id, :course_id, :classroom_id)"
    );
    $ucsStmt->execute([
        ':teacher_id'   => $ucsClean['teacher_id'],
        ':course_id'    => $ucsClean['course_id'],
        ':classroom_id' => $ucsClean['classroom_id'],
    ]);

    teacher_assignment_flash('success', 'Teacher assigned successfully.');
} catch (Throwable $e) {
    teacher_assignment_flash('error', 'Unable to assign teacher. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
