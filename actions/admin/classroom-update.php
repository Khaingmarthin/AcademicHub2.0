<?php
/**
 * Admin Classrooms - Update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/classroom-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/classrooms/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    classroom_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, classroom_name FROM classrooms WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    classroom_flash('error', 'Classroom not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = classroom_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['classroom_errors'] = $ucsErrors;
    $_SESSION['classroom_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/classrooms/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE classrooms
         SET academic_year_id = :academic_year_id,
             major_id = :major_id,
             year_level = :year_level,
             section = :section,
             classroom_name = :classroom_name,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':academic_year_id' => $ucsClean['academic_year_id'],
        ':major_id'         => $ucsClean['major_id'],
        ':year_level'       => $ucsClean['year_level'],
        ':section'          => $ucsClean['section'],
        ':classroom_name'   => $ucsClean['classroom_name'],
        ':status'           => $ucsClean['status'],
        ':id'               => $ucsId,
    ]);

    classroom_flash('success', 'Classroom "' . $ucsClean['classroom_name'] . '" updated successfully.');
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        classroom_flash('error', 'A classroom with this section already exists for the selected academic year, major and year level.');
    } else {
        classroom_flash('error', 'Unable to update the classroom. Please try again.');
    }
} catch (Throwable $e) {
    classroom_flash('error', 'Unable to update the classroom. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;