<?php
/**
 * Shared validation + support helpers for the Alumni Mentorship module.
 *
 * Alumni Mentorship connects current students with verified alumni who are
 * willing to provide career guidance. Students send a short mentorship
 * request; the alumnus accepts or declines it; after acceptance only then are
 * the alumnus's chosen contact channels revealed to that student. This is NOT
 * a chat system - there is no private messaging or real-time conversation.
 *
 * All state transitions, ownership checks and privacy rules are enforced
 * server-side here; hiding buttons on a page is never the only line of
 * defence.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

const MENTORSHIP_REQUEST_STATUSES = ['pending', 'accepted', 'declined', 'completed'];
const MENTORSHIP_REPORT_STATUSES   = ['open', 'resolved', 'dismissed'];
const MENTORSHIP_REPORT_REASONS    = [
    'Inappropriate behaviour',
    'No response',
    'Harassment',
    'Spam',
    'Other',
];
const MENTORSHIP_MESSAGE_MAX       = 2000;
const MENTORSHIP_REASON_MAX        = 50;
const MENTORSHIP_DETAILS_MAX       = 1000;
const MENTORSHIP_CONTACT_EMAIL_MAX = 191;
const MENTORSHIP_AREAS_MAX         = 12;

/**
 * Store a flash message for the Alumni Mentorship pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function mentorship_flash($type, $message)
{
    $_SESSION['mentorship_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}

/**
 * Whether a student is a verified alumnus.
 *
 * @param PDO $pdo       Database connection.
 * @param int $studentId Student id.
 * @return bool
 */
function mentorship_is_verified_alumni($pdo, $studentId)
{
    if ($studentId < 1) {
        return false;
    }

    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id FROM alumni_profiles
             WHERE student_id = :student_id AND verification_status = 'verified'
             LIMIT 1"
        );
        $ucsStmt->execute([':student_id' => $studentId]);
        return $ucsStmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Load the mentorship guidance areas.
 *
 * @param PDO $pdo         Database connection.
 * @param bool $onlyActive Restrict to active areas (public-facing use).
 * @return array
 */
function mentorship_load_areas($pdo, $onlyActive = false)
{
    $ucsSql = "SELECT id, name, slug, description, sort_order, status
               FROM mentorship_areas";
    if ($onlyActive) {
        $ucsSql .= " WHERE status = 'active'";
    }
    $ucsSql .= " ORDER BY sort_order ASC, name ASC";

    try {
        return $pdo->query($ucsSql)->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Load the mentorship area ids selected on an alumni profile.
 *
 * @param PDO $pdo      Database connection.
 * @param int $profileId Alumni profile id.
 * @return array List of area ids.
 */
function mentorship_load_profile_area_ids($pdo, $profileId)
{
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT mentorship_area_id
             FROM alumni_mentorship_areas
             WHERE alumni_profile_id = :profile_id
             ORDER BY mentorship_area_id ASC"
        );
        $ucsStmt->execute([':profile_id' => $profileId]);
        return array_map('intval', $ucsStmt->fetchAll(PDO::FETCH_COLUMN)) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Load the mentorship areas selected on an alumni profile (with details).
 *
 * @param PDO $pdo       Database connection.
 * @param int $profileId Alumni profile id.
 * @return array
 */
function mentorship_load_profile_areas($pdo, $profileId)
{
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT a.id, a.name, a.slug
             FROM alumni_mentorship_areas ama
             JOIN mentorship_areas a ON a.id = ama.mentorship_area_id
             WHERE ama.alumni_profile_id = :profile_id AND a.status = 'active'
             ORDER BY a.sort_order ASC, a.name ASC"
        );
        $ucsStmt->execute([':profile_id' => $profileId]);
        return $ucsStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Load a public, verified, non-suspended mentor profile for display.
 *
 * Only publicly visible verified profiles can be requested, which keeps
 * private alumni profiles from ever being contacted.
 *
 * @param PDO $pdo      Database connection.
 * @param int $profileId Alumni profile id.
 * @return array|null
 */
function mentorship_mentor_profile($pdo, $profileId)
{
    if ($profileId < 1) {
        return null;
    }

    try {
        $ucsStmt = $pdo->prepare(
            "SELECT ap.id, ap.student_id, ap.current_job, ap.company,
                    ap.profile_photo, ap.mentorship_available,
                    ap.mentorship_suspended,
                    s.name AS student_name, s.graduation_year
             FROM alumni_profiles ap
             JOIN students s ON s.id = ap.student_id
             WHERE ap.id = :id
               AND ap.verification_status = 'verified'
               AND ap.visibility = 'public'
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $profileId]);
        $ucsProfile = $ucsStmt->fetch() ?: null;
        return $ucsProfile;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Return the active (pending or accepted) mentorship request between a
 * student and an alumni profile, or null.
 *
 * A student may have at most one pending or accepted request with the same
 * alumnus; declined and completed requests are considered closed.
 *
 * @param PDO $pdo       Database connection.
 * @param int $studentId Requester student id.
 * @param int $profileId Alumni profile id.
 * @return array|null
 */
function mentorship_active_request($pdo, $studentId, $profileId)
{
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id, status
             FROM mentorship_requests
             WHERE student_id = :student_id
               AND alumni_profile_id = :profile_id
               AND status IN ('pending', 'accepted')
             LIMIT 1"
        );
        $ucsStmt->execute([
            ':student_id' => $studentId,
            ':profile_id' => $profileId,
        ]);
        $ucsRequest = $ucsStmt->fetch() ?: null;
        return $ucsRequest;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Validate and normalise a new mentorship request.
 *
 * @param array $input    Raw form values (e.g. $_POST).
 * @param PDO   $pdo      Database connection.
 * @param int   $viewerId The authenticated student id.
 * @return array{clean:array, errors:array}
 */
function mentorship_validate_request_input($input, $pdo, $viewerId)
{
    $errors     = [];
    $profileId  = filter_var($input['alumni_profile_id'] ?? null, FILTER_VALIDATE_INT);
    $message    = trim((string) ($input['message'] ?? ''));

    if ($profileId === false || $profileId < 1) {
        $errors[] = 'Invalid mentor selected.';
    } else {
        $ucsProfile = mentorship_mentor_profile($pdo, $profileId);
        if ($ucsProfile === null) {
            $errors[] = 'This mentor is no longer available for mentorship.';
        } elseif ((int) $ucsProfile['student_id'] === (int) $viewerId) {
            $errors[] = 'You cannot request mentorship from yourself.';
        } elseif ((int) $ucsProfile['mentorship_suspended'] === 1) {
            $errors[] = 'This mentor is currently unavailable.';
        } elseif ((int) $ucsProfile['mentorship_available'] !== 1) {
            $errors[] = 'This alumnus is not currently offering mentorship.';
        } else {
            $ucsActive = mentorship_active_request($pdo, $viewerId, $profileId);
            if ($ucsActive !== null) {
                $errors[] = $ucsActive['status'] === 'accepted'
                    ? 'You already have an accepted mentorship request with this alumnus.'
                    : 'You already have a pending mentorship request with this alumnus.';
            }
        }
    }

    if ($message === '') {
        $errors[] = 'Please write a short message to the mentor.';
    } elseif (mb_strlen($message) > MENTORSHIP_MESSAGE_MAX) {
        $errors[] = 'Your message must be ' . MENTORSHIP_MESSAGE_MAX . ' characters or fewer.';
    }

    return [
        'clean' => [
            'alumni_profile_id' => $profileId,
            'message'           => $message,
        ],
        'errors' => $errors,
    ];
}

/**
 * Validate and normalise the mentorship-specific profile settings.
 *
 * Used by the alumnus's own profile editor: the optional contact email shared
 * with accepted students and the selected mentorship areas. Kept separate
 * from alumni_update_validate_input() because that validator is also used by
 * the admin profile editor, which must never reset these self-managed fields.
 *
 * @param array $input Raw form values (e.g. $_POST).
 * @param PDO   $pdo   Database connection.
 * @return array{clean:array, errors:array}
 */
function mentorship_validate_profile_mentorship_input($input, $pdo)
{
    $errors        = [];
    $contactEmail  = trim((string) ($input['mentorship_contact_email'] ?? ''));
    $rawAreas      = $input['mentorship_areas'] ?? [];
    $areaIds       = [];

    if (!is_array($rawAreas)) {
        $rawAreas = [];
    }
    foreach ($rawAreas as $ucsArea) {
        $ucsArea = filter_var($ucsArea, FILTER_VALIDATE_INT);
        if ($ucsArea !== false && $ucsArea > 0) {
            $areaIds[(int) $ucsArea] = (int) $ucsArea;
        }
    }
    $areaIds = array_values(array_slice($areaIds, 0, MENTORSHIP_AREAS_MAX));

    if (count($areaIds) > 0) {
        try {
            $ucsInClause = implode(',', array_fill(0, count($areaIds), '?'));
            $ucsStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM mentorship_areas
                 WHERE id IN ({$ucsInClause}) AND status = 'active'"
            );
            $ucsStmt->execute($areaIds);
            if ((int) $ucsStmt->fetchColumn() !== count($areaIds)) {
                $errors[] = 'One or more selected mentorship areas are invalid.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the mentorship areas. Please try again.';
        }
    }

    if ($contactEmail !== '') {
        if (mb_strlen($contactEmail) > MENTORSHIP_CONTACT_EMAIL_MAX) {
            $errors[] = 'The mentorship contact email must be ' . MENTORSHIP_CONTACT_EMAIL_MAX . ' characters or fewer.';
        } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid mentorship contact email.';
        }
    }

    return [
        'clean' => [
            'mentorship_contact_email' => $contactEmail,
            'mentorship_areas'         => $areaIds,
        ],
        'errors' => $errors,
    ];
}

/**
 * Validate a report of a mentorship request.
 *
 * Only the student who sent the request may report it.
 *
 * @param array $input    Raw form values (e.g. $_POST).
 * @param PDO   $pdo      Database connection.
 * @param int   $viewerId The authenticated student id.
 * @return array{clean:array, errors:array}
 */
function mentorship_validate_report_input($input, $pdo, $viewerId)
{
    $errors     = [];
    $requestId  = filter_var($input['request_id'] ?? null, FILTER_VALIDATE_INT);
    $reason     = trim((string) ($input['reason'] ?? ''));
    $details    = trim((string) ($input['details'] ?? ''));

    if ($requestId === false || $requestId < 1) {
        $errors[] = 'Invalid mentorship request selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT id, student_id, alumni_profile_id, status
                 FROM mentorship_requests
                 WHERE id = :id
                 LIMIT 1"
            );
            $ucsStmt->execute([':id' => $requestId]);
            $ucsRequest = $ucsStmt->fetch() ?: null;

            if ($ucsRequest === null) {
                $errors[] = 'This mentorship request no longer exists.';
            } elseif ((int) $ucsRequest['student_id'] !== (int) $viewerId) {
                $errors[] = 'You can only report your own mentorship requests.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the mentorship request. Please try again.';
        }
    }

    if ($reason === '' || mb_strlen($reason) > MENTORSHIP_REASON_MAX) {
        $errors[] = 'Please choose a reason.';
    }

    if (mb_strlen($details) > MENTORSHIP_DETAILS_MAX) {
        $errors[] = 'The additional details must be ' . MENTORSHIP_DETAILS_MAX . ' characters or fewer.';
    }

    return [
        'clean' => [
            'request_id' => $requestId,
            'reason'     => $reason,
            'details'    => $details,
        ],
        'errors' => $errors,
    ];
}

/**
 * Whether the given student is a party to the given mentorship request.
 *
 * The requester (student_id) is always a party. The alumnus is a party when
 * the student owns the alumni profile the request targets.
 *
 * @param PDO $pdo       Database connection.
 * @param int $requestId Mentorship request id.
 * @param int $studentId Student id.
 * @return bool
 */
function mentorship_request_involves($pdo, $requestId, $studentId)
{
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT mr.id
             FROM mentorship_requests mr
             JOIN alumni_profiles ap ON ap.id = mr.alumni_profile_id
             WHERE mr.id = :id
               AND (mr.student_id = :student_id OR ap.student_id = :student_id)
             LIMIT 1"
        );
        $ucsStmt->execute([
            ':id'         => $requestId,
            ':student_id' => $studentId,
        ]);
        return $ucsStmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Load the contact channels an alumnus opts to share after accepting a
 * mentorship request. Never called on the public profile: this data is only
 * revealed to the accepted student (and to admins for moderation).
 *
 * @param PDO $pdo       Database connection.
 * @param int $profileId Alumni profile id.
 * @return array List of ['type','label','value'] contact channels.
 */
function mentorship_reveal_contact($pdo, $profileId)
{
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT mentorship_contact_email, linkedin_url, github_url, website_url
             FROM alumni_profiles
             WHERE id = :id
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $profileId]);
        $ucsRow = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsRow = null;
    }

    if ($ucsRow === null) {
        return [];
    }

    $ucsChannels = [];
    if (!empty($ucsRow['mentorship_contact_email'])) {
        $ucsChannels[] = ['email', 'Email', (string) $ucsRow['mentorship_contact_email']];
    }
    if (!empty($ucsRow['linkedin_url'])) {
        $ucsChannels[] = ['linkedin', 'LinkedIn', (string) $ucsRow['linkedin_url']];
    }
    if (!empty($ucsRow['github_url'])) {
        $ucsChannels[] = ['github', 'GitHub', (string) $ucsRow['github_url']];
    }
    if (!empty($ucsRow['website_url'])) {
        $ucsChannels[] = ['website', 'Website', (string) $ucsRow['website_url']];
    }

    return $ucsChannels;
}

/**
 * Count open mentorship reports.
 *
 * @param PDO $pdo Database connection.
 * @return int
 */
function mentorship_open_report_count($pdo)
{
    try {
        return (int) $pdo->query(
            "SELECT COUNT(*) FROM mentorship_reports WHERE status = 'open'"
        )->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}