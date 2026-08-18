<?php
/**
 * Admin Timetables - Deactivate handler.
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
        "SELECT id, title FROM timetables WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsTimetable = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsTimetable = null;
}

if ($ucsTimetable === null || $ucsId === false || $ucsId < 1) {
    timetable_flash('error', 'Timetable not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("UPDATE timetables SET status = 0 WHERE id = :id")->execute([':id' => $ucsId]);
    timetable_flash('error', 'Timetable "' . $ucsTimetable['title'] . '" has been deactivated.');
} catch (Throwable $e) {
    timetable_flash('error', 'Unable to deactivate the timetable. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;