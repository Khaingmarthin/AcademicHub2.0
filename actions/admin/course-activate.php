<?php
/**
 * Admin Courses - Activate handler.
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
    $ucsCourse = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsCourse = null;
}

if ($ucsCourse === null || $ucsId === false || $ucsId < 1) {
    course_flash('error', 'Course not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare("UPDATE courses SET status = 1 WHERE id = :id")->execute([':id' => $ucsId]);
    course_flash('success', 'Course "' . $ucsCourse['course_code'] . ' - ' . $ucsCourse['course_name'] . '" is now Active.');
} catch (Throwable $e) {
    course_flash('error', 'Unable to activate the course. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;