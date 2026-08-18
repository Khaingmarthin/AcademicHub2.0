<?php
/**
 * Admin Courses - Update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/course-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/courses/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    course_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, course_code, course_name FROM courses WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    course_flash('error', 'Course not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = course_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['course_errors'] = $ucsErrors;
    $_SESSION['course_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/courses/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE courses
         SET academic_year_id = :academic_year_id,
             major_id = :major_id,
             course_code = :course_code,
             course_name = :course_name,
             year_level = :year_level,
             semester = :semester,
             credit_hours = :credit_hours,
             description = :description,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':academic_year_id' => $ucsClean['academic_year_id'],
        ':major_id'         => $ucsClean['major_id'],
        ':course_code'      => $ucsClean['course_code'],
        ':course_name'      => $ucsClean['course_name'],
        ':year_level'       => $ucsClean['year_level'],
        ':semester'         => $ucsClean['semester'],
        ':credit_hours'     => $ucsClean['credit_hours'],
        ':description'      => $ucsClean['description'],
        ':status'           => $ucsClean['status'],
        ':id'               => $ucsId,
    ]);

    course_flash('success', 'Course "' . $ucsClean['course_code'] . ' - ' . $ucsClean['course_name'] . '" updated successfully.');
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        course_flash('error', 'A course with this code already exists for the selected academic year and major.');
    } else {
        course_flash('error', 'Unable to update the course. Please try again.');
    }
} catch (Throwable $e) {
    course_flash('error', 'Unable to update the course. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;