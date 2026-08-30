<?php
/**
 * Mark all in-app notifications as read for the current student.
 *
 * Returns JSON response for AJAX calls.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/notification-helper.php';

header('Content-Type: application/json');

if (!student_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$ucsStudent = student_current_user();

ucs_mark_notifications_read($pdo, $ucsStudent['id']);

echo json_encode(['success' => true]);
exit;
