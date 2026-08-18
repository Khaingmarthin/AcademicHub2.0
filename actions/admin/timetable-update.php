<?php
/**
 * Admin Timetables - Update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/timetable-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/timetables/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    timetable_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, title, image FROM timetables WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    timetable_flash('error', 'Timetable not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = timetable_validate_input($_POST, $pdo, $ucsId, (string) $ucsExisting['image']);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    ucs_delete_upload($ucsClean['image'] ?? null);
    $_SESSION['timetable_errors'] = $ucsErrors;
    $_SESSION['timetable_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/timetables/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsImage = $ucsClean['image'] ?? null;
    $ucsImage = $ucsImage === null ? $ucsExisting['image'] : $ucsImage;

    $ucsStmt = $pdo->prepare(
        "UPDATE timetables
         SET classroom_id = :classroom_id,
             semester = :semester,
             title = :title,
             image = :image,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':classroom_id' => $ucsClean['classroom_id'],
        ':semester'     => $ucsClean['semester'],
        ':title'        => $ucsClean['title'],
        ':image'        => $ucsImage,
        ':status'       => $ucsClean['status'],
        ':id'           => $ucsId,
    ]);

    if ($ucsClean['image'] !== null) {
        ucs_delete_upload($ucsExisting['image'] ?? null);
    }

    timetable_flash('success', 'Timetable "' . $ucsClean['title'] . '" updated successfully.');
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        timetable_flash('error', 'A timetable already exists for this classroom and semester.');
    } else {
        timetable_flash('error', 'Unable to update the timetable. Please try again.');
    }
} catch (Throwable $e) {
    timetable_flash('error', 'Unable to update the timetable. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;