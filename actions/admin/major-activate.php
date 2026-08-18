<?php
/**
 * Admin Majors - Activate handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/major-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/majors/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    major_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, name FROM majors WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsMajor = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsMajor = null;
}

if ($ucsMajor === null || $ucsId === false || $ucsId < 1) {
    major_flash('error', 'Major not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("UPDATE majors SET status = 1 WHERE id = :id")->execute([':id' => $ucsId]);
    major_flash('success', 'Major "' . $ucsMajor['name'] . '" is now Active.');
} catch (Throwable $e) {
    major_flash('error', 'Unable to activate the major. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;