<?php
/**
 * Admin Courses - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/course-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/courses/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/courses/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    course_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = course_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['course_errors'] = $ucsErrors;
    $_SESSION['course_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO courses
            (academic_year_id, major_id, course_code, course_name, year_level, semester, credit_hours, description, status)
         VALUES
            (:academic_year_id, :major_id, :course_code, :course_name, :year_level, :semester, :credit_hours, :description, :status)"
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
    ]);

    course_flash('success', 'Course "' . $ucsClean['course_code'] . ' - ' . $ucsClean['course_name'] . '" created successfully.');
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        course_flash('error', 'A course with this code already exists for the selected academic year and major.');
    } else {
        course_flash('error', 'Unable to create the course. Please try again.');
    }
} catch (Throwable $e) {
    course_flash('error', 'Unable to create the course. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;