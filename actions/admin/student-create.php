<?php
/**
 * Admin Students - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/student-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/students/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/students/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    student_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = student_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['student_errors'] = $ucsErrors;
    $_SESSION['student_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO students
            (student_id, roll_number, name, email, password, classroom_id, status)
         VALUES
            (:student_id, :roll_number, :name, :email, :password, :classroom_id, :status)"
    );
    $ucsStmt->execute([
        ':student_id'   => $ucsClean['student_id'],
        ':roll_number'  => $ucsClean['roll_number'],
        ':name'         => $ucsClean['name'],
        ':email'        => $ucsClean['email'],
        ':password'     => password_hash($ucsClean['password'], PASSWORD_DEFAULT),
        ':classroom_id' => $ucsClean['classroom_id'],
        ':status'       => $ucsClean['status'],
    ]);

    student_flash('success', 'Student "' . $ucsClean['name'] . '" created successfully.');
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        student_flash('error', 'A student with this Student ID, Roll Number or email address already exists.');
    } else {
        student_flash('error', 'Unable to create the student. Please try again.');
    }
} catch (Throwable $e) {
    student_flash('error', 'Unable to create the student. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;