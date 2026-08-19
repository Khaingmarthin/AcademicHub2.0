<?php
/**
 * Alumni Mentorship - Respond to a mentorship request (alumnus side).
 *
 * The alumnus who owns the target profile may accept or decline a pending
 * request. A suspended mentor cannot accept new requests.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';

student_require_login();

$ucsProfile = alumni_current_profile($pdo);
if ($ucsProfile === null) {
    header('Location: ' . BASE_URL . '/alumni.php');
    exit;
}
$ucsProfileId = (int) $ucsProfile['id'];

$ucsRequestId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$ucsAction    = (string) ($_POST['action'] ?? '');

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    mentorship_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . BASE_URL . '/mentorship-inbox.php');
    exit;
}

if ($ucsRequestId === false || $ucsRequestId < 1) {
    mentorship_flash('error', 'Invalid mentorship request selected.');
    header('Location: ' . BASE_URL . '/mentorship-inbox.php');
    exit;
}

if (!in_array($ucsAction, ['accept', 'decline'], true)) {
    mentorship_flash('error', 'Invalid action selected.');
    header('Location: ' . BASE_URL . '/mentorship-inbox.php');
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, alumni_profile_id, status
         FROM mentorship_requests
         WHERE id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsRequestId]);
    $ucsRequest = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsRequest = null;
}

if ($ucsRequest === null
    || (int) $ucsRequest['alumni_profile_id'] !== $ucsProfileId
    || $ucsRequest['status'] !== 'pending') {
    mentorship_flash('error', 'This mentorship request is no longer available to respond to.');
    header('Location: ' . BASE_URL . '/mentorship-inbox.php');
    exit;
}

if ($ucsAction === 'accept' && (int) $ucsProfile['mentorship_suspended'] === 1) {
    mentorship_flash('error', 'Mentorship has been disabled for your account by an administrator.');
    header('Location: ' . BASE_URL . '/mentorship-inbox.php');
    exit;
}

$ucsNewStatus = $ucsAction === 'accept' ? 'accepted' : 'declined';

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE mentorship_requests
         SET status = :status, responded_at = NOW()
         WHERE id = :id AND status = 'pending'"
    );
    $ucsStmt->execute([
        ':status' => $ucsNewStatus,
        ':id'     => $ucsRequestId,
    ]);
} catch (PDOException $e) {
    mentorship_flash('error', 'Unable to update the mentorship request. Please try again.');
    header('Location: ' . BASE_URL . '/mentorship-inbox.php');
    exit;
}

mentorship_flash(
    'success',
    $ucsAction === 'accept'
        ? 'You accepted this mentorship request. The student can now see your shared contact details.'
        : 'You declined this mentorship request.'
);
header('Location: ' . BASE_URL . '/mentorship-inbox.php');
exit;