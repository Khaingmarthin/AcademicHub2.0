<?php
/**
 * Admin Alumni Events - create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-event-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/events/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    alumni_event_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = alumni_event_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['alumni_event_errors'] = $ucsErrors;
    $_SESSION['alumni_event_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/events/create.php');
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO alumni_events
            (admin_id, title, description, event_type, venue, starts_at, ends_at,
             registration_link, status)
         VALUES
            (:admin_id, :title, :description, :event_type, :venue, :starts_at, :ends_at,
             :registration_link, :status)"
    );
    $ucsStmt->execute([
        ':admin_id'          => (int) $_SESSION['admin_id'],
        ':title'             => $ucsClean['title'],
        ':description'       => $ucsClean['description'],
        ':event_type'        => $ucsClean['event_type'],
        ':venue'             => $ucsClean['venue'],
        ':starts_at'         => $ucsClean['starts_at'],
        ':ends_at'           => $ucsClean['ends_at'],
        ':registration_link' => $ucsClean['registration_link'],
        ':status'            => $ucsClean['status'],
    ]);

    alumni_event_flash('success', 'The event has been published.');
} catch (Throwable $e) {
    alumni_event_flash('error', 'Unable to publish the event. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;