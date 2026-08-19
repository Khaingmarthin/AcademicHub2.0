<?php
/**
 * Alumni Mentorship - Mark a mentorship as completed.
 *
 * Either the requester (student) or the alumnus can mark an accepted
 * mentorship as completed once it has run its course.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/student-auth.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';

student_require_login();

$ucsUser      = student_current_user();
$ucsRequestId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

$ucsOwnProfile = null;
if ($ucsRequestId !== false && $ucsRequestId > 0) {
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT alumni_profile_id FROM mentorship_requests WHERE id = :id LIMIT 1"
        );
        $ucsStmt->execute([':id' => $ucsRequestId]);
        $ucsAlumniProfileId = $ucsStmt->fetchColumn();
        if ($ucsAlumniProfileId !== false) {
            $ucsStmt = $pdo->prepare(
                "SELECT id FROM alumni_profiles
                 WHERE id = :profile_id AND student_id = :student_id
                 LIMIT 1"
            );
            $ucsStmt->execute([
                ':profile_id'  => $ucsAlumniProfileId,
                ':student_id'  => (int) $ucsUser['id'],
            ]);
            if ($ucsStmt->fetchColumn() !== false) {
                $ucsOwnProfile = true;
            }
        }
    } catch (PDOException $e) {
        // Fall through; redirect target chosen below.
    }
}

$ucsRedirect = $ucsOwnProfile === true
    ? BASE_URL . '/mentorship-inbox.php'
    : BASE_URL . '/my-mentorship.php';

if (!student_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    mentorship_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsRedirect);
    exit;
}

if ($ucsRequestId === false || $ucsRequestId < 1) {
    mentorship_flash('error', 'Invalid mentorship request selected.');
    header('Location: ' . $ucsRedirect);
    exit;
}

// Only a party to the request may complete it, and only once accepted.
if (!mentorship_request_involves($pdo, $ucsRequestId, (int) $ucsUser['id'])) {
    mentorship_flash('error', 'You are not a part of this mentorship request.');
    header('Location: ' . $ucsRedirect);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "SELECT status FROM mentorship_requests WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsRequestId]);
    $ucsStatus = $ucsStmt->fetchColumn();
} catch (PDOException $e) {
    $ucsStatus = false;
}

if ($ucsStatus !== 'accepted') {
    mentorship_flash('error', 'Only accepted mentorships can be marked as completed.');
    header('Location: ' . $ucsRedirect);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE mentorship_requests
         SET status = 'completed', completed_at = NOW()
         WHERE id = :id AND status = 'accepted'"
    );
    $ucsStmt->execute([':id' => $ucsRequestId]);
} catch (PDOException $e) {
    mentorship_flash('error', 'Unable to update the mentorship request. Please try again.');
    header('Location: ' . $ucsRedirect);
    exit;
}

mentorship_flash('success', 'This mentorship has been marked as completed. Thank you!');
header('Location: ' . $ucsRedirect);
exit;