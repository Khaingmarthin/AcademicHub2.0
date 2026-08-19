<?php
/**
 * Admin Alumni Events - publish / cancel handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/events/index.php';
$ucsEventId   = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$ucsTarget    = (string) ($_POST['status'] ?? '');

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    alumni_event_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

if ($ucsEventId === false || !in_array($ucsTarget, ['published', 'cancelled'], true)) {
    alumni_event_flash('error', 'Invalid event selected.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare("UPDATE alumni_events SET status = :status WHERE id = :id");
    $ucsStmt->execute([':status' => $ucsTarget, ':id' => $ucsEventId]);

    alumni_event_flash('success', $ucsTarget === 'published'
        ? 'The event is published again.'
        : 'The event has been marked as cancelled.');
} catch (Throwable $e) {
    alumni_event_flash('error', 'Unable to change the event status. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;