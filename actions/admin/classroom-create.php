<?php
/**
 * Admin Classrooms - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/classroom-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/classrooms/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/classrooms/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    classroom_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = classroom_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['classroom_errors'] = $ucsErrors;
    $_SESSION['classroom_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO classrooms
            (academic_year_id, major_id, year_level, section, classroom_name, status)
         VALUES
            (:academic_year_id, :major_id, :year_level, :section, :classroom_name, :status)"
    );
    $ucsStmt->execute([
        ':academic_year_id' => $ucsClean['academic_year_id'],
        ':major_id'         => $ucsClean['major_id'],
        ':year_level'       => $ucsClean['year_level'],
        ':section'          => $ucsClean['section'],
        ':classroom_name'   => $ucsClean['classroom_name'],
        ':status'           => $ucsClean['status'],
    ]);

    classroom_flash('success', 'Classroom "' . $ucsClean['classroom_name'] . '" created successfully.');
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        classroom_flash('error', 'A classroom with this section already exists for the selected academic year, major and year level.');
    } else {
        classroom_flash('error', 'Unable to create the classroom. Please try again.');
    }
} catch (Throwable $e) {
    classroom_flash('error', 'Unable to create the classroom. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;