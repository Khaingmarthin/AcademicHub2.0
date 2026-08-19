<?php
/**
 * Admin Alumni Events - update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-event-validation.php';

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

$ucsResult = alumni_event_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['alumni_event_errors'] = $ucsErrors;
    $_SESSION['alumni_event_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/events/edit.php?id=' . $ucsEventId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE alumni_events
         SET title = :title,
             description = :description,
             event_type = :event_type,
             venue = :venue,
             starts_at = :starts_at,
             ends_at = :ends_at,
             registration_link = :registration_link,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':title'             => $ucsClean['title'],
        ':description'       => $ucsClean['description'],
        ':event_type'        => $ucsClean['event_type'],
        ':venue'             => $ucsClean['venue'],
        ':starts_at'         => $ucsClean['starts_at'],
        ':ends_at'           => $ucsClean['ends_at'],
        ':registration_link' => $ucsClean['registration_link'],
        ':status'            => $ucsClean['status'],
        ':id'                => $ucsEventId,
    ]);

    alumni_event_flash('success', 'The event has been updated.');
} catch (Throwable $e) {
    alumni_event_flash('error', 'Unable to update the event. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;