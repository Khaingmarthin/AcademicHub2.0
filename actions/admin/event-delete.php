<?php
/**
 * Admin Alumni Events - delete handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/events/index.php';
$ucsEventId   = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    alumni_event_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

if ($ucsEventId === false) {
    alumni_event_flash('error', 'Invalid event selected.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare("DELETE FROM alumni_events WHERE id = :id");
    $ucsStmt->execute([':id' => $ucsEventId]);

    alumni_event_flash('success', 'The event has been deleted.');
} catch (Throwable $e) {
    alumni_event_flash('error', 'Unable to delete the event. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;