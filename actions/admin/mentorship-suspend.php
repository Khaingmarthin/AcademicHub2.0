<?php
/**
 * Admin Alumni Mentorship - Suspend / restore a mentor's mentorship access.
 *
 * Suspending a mentor:
 *   - sets mentorship_suspended = 1 and mentorship_available = 0
 *   - auto-declines all of that mentor's pending requests
 *   - auto-resolves any open reports against that mentor's requests
 *
 * Restoring clears the suspension so the alumnus can manage availability
 * again.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/mentorship/mentors.php';
if (isset($_POST['from_reports']) && (int) $_POST['from_reports'] === 1) {
    $ucsReturnUrl = ROOT_URL . '/admin/mentorship/reports.php';
}

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    mentorship_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsProfileId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$ucsSuspend   = isset($_POST['suspended']) && in_array($_POST['suspended'], [1, '1'], true);

if ($ucsProfileId === false || $ucsProfileId < 1) {
    mentorship_flash('error', 'That alumni profile could not be found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "SELECT ap.id, ap.mentorship_suspended, s.name AS student_name
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         WHERE ap.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsProfileId]);
    $ucsProfile = $ucsStmt->fetch() ?: null;

    if ($ucsProfile === null) {
        mentorship_flash('error', 'That alumni profile could not be found.');
        header('Location: ' . $ucsReturnUrl);
        exit;
    }

    if ($ucsSuspend && (int) $ucsProfile['mentorship_suspended'] !== 1) {
        // Suspend: disable availability and clean up pending requests/reports.
        $pdo->beginTransaction();

        $ucsStmt = $pdo->prepare(
            "UPDATE alumni_profiles
             SET mentorship_suspended = 1, mentorship_available = 0
             WHERE id = :id"
        );
        $ucsStmt->execute([':id' => $ucsProfileId]);

        $ucsStmt = $pdo->prepare(
            "UPDATE mentorship_requests
             SET status = 'declined', responded_at = NOW()
             WHERE alumni_profile_id = :profile_id AND status = 'pending'"
        );
        $ucsStmt->execute([':profile_id' => $ucsProfileId]);

        $ucsStmt = $pdo->prepare(
            "UPDATE mentorship_reports r
             JOIN mentorship_requests mr ON mr.id = r.request_id
             SET r.status = 'resolved',
                 r.resolved_by_admin_id = :admin_id,
                 r.resolved_at = NOW()
             WHERE mr.alumni_profile_id = :profile_id AND r.status = 'open'"
        );
        $ucsStmt->execute([
            ':admin_id'    => (int) $_SESSION['admin_id'],
            ':profile_id'  => $ucsProfileId,
        ]);

        $pdo->commit();

        mentorship_flash('success', 'Mentorship for "' . $ucsProfile['student_name'] . '" has been suspended. Pending requests and open reports were resolved.');
    } elseif (!$ucsSuspend && (int) $ucsProfile['mentorship_suspended'] === 1) {
        $ucsStmt = $pdo->prepare(
            "UPDATE alumni_profiles SET mentorship_suspended = 0 WHERE id = :id"
        );
        $ucsStmt->execute([':id' => $ucsProfileId]);

        mentorship_flash('success', 'Mentorship access for "' . $ucsProfile['student_name'] . '" has been restored.');
    } else {
        mentorship_flash('error', 'No change was made to this mentor.');
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    mentorship_flash('error', 'Unable to update this mentor. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;