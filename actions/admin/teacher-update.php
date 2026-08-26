<?php
/**
 * Admin Teachers - Update handler.
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
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    teacher_flash('error', 'Teacher not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = teacher_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['teacher_errors'] = $ucsErrors;
    $_SESSION['teacher_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/teachers/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE teachers
         SET teacher_id = :teacher_id,
             name = :name,
             email = :email,
             phone = :phone,
             faculty_id = :faculty_id,
             department_id = :department_id,
             specialization = :specialization,
             status = :status
         WHERE id = :id"
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
        ':id'             => $ucsId,
    ]);

    teacher_flash('success', 'Teacher "' . $ucsClean['name'] . '" updated successfully.');
} catch (Throwable $e) {
    teacher_flash('error', 'Unable to update the teacher. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
