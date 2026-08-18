<?php
/**
 * Admin Timetables - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/timetable-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/timetables/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/timetables/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    timetable_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = timetable_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    ucs_delete_upload($ucsClean['image'] ?? null);
    $_SESSION['timetable_errors'] = $ucsErrors;
    $_SESSION['timetable_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO timetables
            (classroom_id, semester, title, image, status)
         VALUES
            (:classroom_id, :semester, :title, :image, :status)"
    );
    $ucsStmt->execute([
        ':classroom_id' => $ucsClean['classroom_id'],
        ':semester'     => $ucsClean['semester'],
        ':title'        => $ucsClean['title'],
        ':image'        => $ucsClean['image'],
        ':status'       => $ucsClean['status'],
    ]);

    timetable_flash('success', 'Timetable "' . $ucsClean['title'] . '" created successfully.');
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        timetable_flash('error', 'A timetable already exists for this classroom and semester.');
    } else {
        timetable_flash('error', 'Unable to create the timetable. Please try again.');
    }
} catch (Throwable $e) {
    timetable_flash('error', 'Unable to create the timetable. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;