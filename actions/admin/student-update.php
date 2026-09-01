<?php
/**
 * Admin Students - Update handler.
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
        "SELECT id, name, status FROM students WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    student_flash('error', 'Student not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = student_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

// Extra guard: prevent graduation of non-fifth-year students via edit form.
if ($ucsClean['status'] === 'graduated' && $ucsClean['classroom_id'] > 0) {
    try {
        $ucsYrStmt = $pdo->prepare("SELECT c.year_level FROM classrooms c WHERE c.id = :id LIMIT 1");
        $ucsYrStmt->execute([':id' => $ucsClean['classroom_id']]);
        $ucsYearLevel = (string) ($ucsYrStmt->fetchColumn() ?: '');
        if (trim($ucsYearLevel) !== 'Fifth Year') {
            $ucsErrors[] = 'Only Fifth Year students can be marked as graduated.';
        }
    } catch (PDOException $e) {
        // Let validation handle it.
    }
}

if (!empty($ucsErrors)) {
    $_SESSION['student_errors'] = $ucsErrors;
    $_SESSION['student_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/students/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsIsGraduated = $ucsClean['status'] === 'graduated';
    $ucsPreservedStatus = (int) $ucsExisting['status'];
    if ($ucsClean['password'] !== '') {
        $ucsStmt = $pdo->prepare(
            "UPDATE students
             SET student_id = :student_id,
                 roll_number = :roll_number,
                 name = :name,
                 email = :email,
                 password = :password,
                 classroom_id = :classroom_id,
                 status = :status,
                 email_notifications = :email_notifications,
                 student_status = :student_status
             WHERE id = :id"
        );
        $ucsStmt->execute([
            ':student_id'           => $ucsClean['student_id'],
            ':roll_number'          => $ucsClean['roll_number'],
            ':name'                 => $ucsClean['name'],
            ':email'                => $ucsClean['email'],
            ':password'             => password_hash($ucsClean['password'], PASSWORD_DEFAULT),
            ':classroom_id'         => $ucsClean['classroom_id'],
            ':status'               => $ucsPreservedStatus,
            ':email_notifications'  => $ucsClean['email_notifications'],
            ':student_status'       => $ucsIsGraduated ? 'graduated' : 'active',
            ':id'                   => $ucsId,
        ]);
    } else {
        $ucsStmt = $pdo->prepare(
            "UPDATE students
             SET student_id = :student_id,
                 roll_number = :roll_number,
                 name = :name,
                 email = :email,
                 classroom_id = :classroom_id,
                 status = :status,
                 email_notifications = :email_notifications,
                 student_status = :student_status
             WHERE id = :id"
        );
        $ucsStmt->execute([
            ':student_id'           => $ucsClean['student_id'],
            ':roll_number'          => $ucsClean['roll_number'],
            ':name'                 => $ucsClean['name'],
            ':email'                => $ucsClean['email'],
            ':classroom_id'         => $ucsClean['classroom_id'],
            ':status'               => $ucsPreservedStatus,
            ':email_notifications'  => $ucsClean['email_notifications'],
            ':student_status'       => $ucsIsGraduated ? 'graduated' : 'active',
            ':id'                   => $ucsId,
        ]);
    }

    student_flash('success', 'Student "' . $ucsClean['name'] . '" updated successfully.');
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        student_flash('error', 'A student with this Student ID, Roll Number or email address already exists.');
    } else {
        student_flash('error', 'Unable to update the student. Please try again.');
    }
} catch (Throwable $e) {
    student_flash('error', 'Unable to update the student. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;