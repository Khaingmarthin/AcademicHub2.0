<?php
/**
 * Admin Timetables - Delete handler.
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
    $pdo->prepare("DELETE FROM timetables WHERE id = :id")->execute([':id' => $ucsId]);

    $ucsImage = (string) $ucsTimetable['image'];
    if ($ucsImage !== '' && strpos($ucsImage, 'uploads/') === 0) {
        $ucsFilePath = UPLOAD_DIR . substr($ucsImage, strlen('uploads/'));
        if (is_file($ucsFilePath)) {
            @unlink($ucsFilePath);
        }
    }

    timetable_flash('success', 'Timetable "' . $ucsTimetable['title'] . '" deleted successfully.');
} catch (Throwable $e) {
    timetable_flash('error', 'Unable to delete the timetable. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;