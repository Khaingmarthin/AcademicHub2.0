<?php
/**
 * Admin Teachers - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/teacher-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/teachers/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/teachers/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    teacher_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = teacher_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['teacher_errors'] = $ucsErrors;
    $_SESSION['teacher_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO teachers (teacher_id, name, email, phone, faculty_id, department_id, specialization, status)
         VALUES (:teacher_id, :name, :email, :phone, :faculty_id, :department_id, :specialization, :status)"
    );
    $ucsStmt->execute([
        ':teacher_id'     => $ucsClean['teacher_id'],
        ':name'           => $ucsClean['name'],
        ':email'          => $ucsClean['email'],
        ':phone'          => $ucsClean['phone'],
        ':faculty_id'     => $ucsClean['faculty_id'],
        ':department_id'  => $ucsClean['department_id'],
        ':specialization' => $ucsClean['specialization'],
        ':status'         => $ucsClean['status'],
    ]);

    teacher_flash('success', 'Teacher "' . $ucsClean['name'] . '" created successfully.');
} catch (Throwable $e) {
    teacher_flash('error', 'Unable to create the teacher. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
