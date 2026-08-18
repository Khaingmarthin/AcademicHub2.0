<?php
/**
 * Admin Classrooms - Deactivate handler.
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
    $ucsClassroom = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsClassroom = null;
}

if ($ucsClassroom === null || $ucsId === false || $ucsId < 1) {
    classroom_flash('error', 'Classroom not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("UPDATE classrooms SET status = 0 WHERE id = :id")->execute([':id' => $ucsId]);
    classroom_flash('success', 'Classroom "' . $ucsClassroom['classroom_name'] . '" has been deactivated.');
} catch (Throwable $e) {
    classroom_flash('error', 'Unable to deactivate the classroom. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;