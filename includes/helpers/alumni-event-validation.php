<?php
/**
 * Shared validation + support helpers for the Alumni Events module.
 *
 * Alumni Events are university-organised gatherings for the Alumni & Career
 * Community (webinars, workshops, networking nights, career fairs). Events are
 * created and managed exclusively by admins; alumni and students view them on
 * the public page and the alumni dashboard.
 *
 * The upcoming / ongoing / completed display state is derived from the stored
 * start and end times; admins only ever set 'published' / 'cancelled'.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

const ALUMNI_EVENT_TYPES = ['webinar', 'workshop', 'networking', 'seminar', 'career_fair', 'social'];
const ALUMNI_EVENT_LABELS = [
    'webinar'    => 'Webinar',
    'workshop'   => 'Workshop',
    'networking' => 'Networking',
    'seminar'    => 'Seminar',
    'career_fair'=> 'Career Fair',
    'social'     => 'Social',
];
const ALUMNI_EVENT_STATUSES  = ['published', 'cancelled'];
const ALUMNI_EVENT_TITLE_MAX = 255;
const ALUMNI_EVENT_VENUE_MAX = 255;
const ALUMNI_EVENT_LINK_MAX  = 191;

/**
 * Store a flash message for the Alumni Events pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function alumni_event_flash($type, $message)
{
    $_SESSION['alumni_event_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}

/**
 * Human-readable label for an event type.
 *
 * @param string $type Event type key.
 * @return string
 */
function alumni_event_label($type)
{
    return ALUMNI_EVENT_LABELS[$type] ?? 'Networking';
}

/**
 * Derive the display state of an event from its start/end times.
 *
 * @param array $row An alumni_events row.
 * @return string One of 'upcoming', 'ongoing', 'completed'.
 */
function alumni_event_display_state($row)
{
    $ucsStart = strtotime((string) ($row['starts_at'] ?? ''));
    $ucsEnd   = !empty($row['ends_at']) ? strtotime((string) $row['ends_at']) : null;
    $ucsNow   = time();

    if ($ucsStart > $ucsNow) {
        return 'upcoming';
    }
    if ($ucsEnd !== null && $ucsEnd < $ucsNow) {
        return 'completed';
    }
    return 'ongoing';
}

/**
 * Validate and normalise an alumni event form.
 *
 * @param array $input Raw form values (e.g. $_POST).
 * @param PDO   $pdo   Database connection.
 * @return array{clean:array, errors:array}
 */
function alumni_event_validate_input($input, $pdo)
{
    $errors = [];

    $title            = trim((string) ($input['title'] ?? ''));
    $description      = trim((string) ($input['description'] ?? ''));
    $eventType        = (string) ($input['event_type'] ?? '');
    $venue            = trim((string) ($input['venue'] ?? ''));
    $startsAt         = trim((string) ($input['starts_at'] ?? ''));
    $endsAt           = trim((string) ($input['ends_at'] ?? ''));
    $registrationLink = trim((string) ($input['registration_link'] ?? ''));
    $status           = (string) ($input['status'] ?? 'published');

    if ($title === '') {
        $errors[] = 'An event title is required.';
    } elseif (mb_strlen($title) > ALUMNI_EVENT_TITLE_MAX) {
        $errors[] = 'The event title must be ' . ALUMNI_EVENT_TITLE_MAX . ' characters or fewer.';
    }

    if ($description === '') {
        $errors[] = 'A description of the event is required.';
    }

    if (!in_array($eventType, ALUMNI_EVENT_TYPES, true)) {
        $errors[] = 'Invalid event type selected.';
    }

    if (mb_strlen($venue) > ALUMNI_EVENT_VENUE_MAX) {
        $errors[] = 'The venue must be ' . ALUMNI_EVENT_VENUE_MAX . ' characters or fewer.';
    }

    $ucsStartsClean = null;
    if ($startsAt === '') {
        $errors[] = 'The event start date and time are required.';
    } else {
        $ucsStartsClean = alumni_event_parse_datetime($startsAt);
        if ($ucsStartsClean === null) {
            $errors[] = 'Please enter a valid start date and time.';
        }
    }

    $ucsEndsClean = null;
    if ($endsAt !== '') {
        $ucsEndsClean = alumni_event_parse_datetime($endsAt);
        if ($ucsEndsClean === null) {
            $errors[] = 'Please enter a valid end date and time.';
        } elseif ($ucsStartsClean !== null && strtotime($ucsEndsClean) <= strtotime($ucsStartsClean)) {
            $errors[] = 'The end date and time must be after the start.';
        }
    }

    if ($registrationLink !== '') {
        if (mb_strlen($registrationLink) > ALUMNI_EVENT_LINK_MAX) {
            $errors[] = 'The registration link must be ' . ALUMNI_EVENT_LINK_MAX . ' characters or fewer.';
        } elseif (!filter_var($registrationLink, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid registration link URL.';
        }
    }

    if (!in_array($status, ALUMNI_EVENT_STATUSES, true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'title'             => $title,
            'description'       => $description,
            'event_type'        => $eventType,
            'venue'             => $venue,
            'starts_at'         => $ucsStartsClean,
            'ends_at'           => $ucsEndsClean,
            'registration_link' => $registrationLink,
            'status'            => $status,
        ],
        'errors' => $errors,
    ];
}

/**
 * Parse a datetime-local value into a MySQL DATETIME string.
 *
 * Accepts "Y-m-d\TH:i" (HTML datetime-local) or "Y-m-d H:i[:s]".
 *
 * @param string $value Raw value.
 * @return string|null Normalised "Y-m-d H:i:s", or null when invalid.
 */
function alumni_event_parse_datetime($value)
{
    $value = trim((string) $value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2})?$/', $value, $match)) {
        $ucsNorm = str_replace('T', ' ', $value);
        if (empty($match[1])) {
            $ucsNorm .= ':00';
        }
        if (strtotime($ucsNorm) !== false) {
            return $ucsNorm;
        }
    }
    return null;
}

/**
 * Format a MySQL DATETIME for an HTML datetime-local input.
 *
 * @param string $value A "Y-m-d H:i:s" (or "Y-m-d H:i") value.
 * @return string Value in "Y-m-d\TH:i" form, or '' when unparseable.
 */
function alumni_event_format_local_input($value)
{
    $ucsTs = strtotime((string) $value);
    return $ucsTs !== false ? date('Y-m-d\TH:i', $ucsTs) : '';
}

/**
 * Load the next upcoming/ongoing published events for the dashboard.
 *
 * @param PDO $pdo   Database connection.
 * @param int $limit Maximum number of rows.
 * @return array
 */
function alumni_event_load_upcoming($pdo, $limit = 3)
{
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id, title, description, event_type, venue, starts_at, ends_at
             FROM alumni_events
             WHERE status = 'published'
               AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY starts_at ASC, id ASC
             LIMIT :limit"
        );
        $ucsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $ucsStmt->execute();
        return $ucsStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Load published, not-yet-completed events with optional filters + pagination.
 *
 * @param PDO    $pdo      Database connection.
 * @param string $query    Free-text search on title / venue.
 * @param string $eventType Optional event-type filter ('' = all).
 * @param int    $show     Per-page limit (10/25/50).
 * @param int    $page     1-based page number.
 * @return array{items:array,total:int,total_pages:int}
 */
function alumni_event_load_public($pdo, $query, $eventType, $show, $page)
{
    $ucsWhere  = ["e.status = 'published' AND (e.ends_at IS NULL OR e.ends_at >= NOW())"];
    $ucsParams = [];

    if ($query !== '') {
        $ucsEscaped      = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
        $ucsWhere[]      = '(e.title LIKE :q OR e.venue LIKE :q)';
        $ucsParams[':q'] = '%' . $ucsEscaped . '%';
    }
    if ($eventType !== '') {
        $ucsWhere[]                = 'e.event_type = :event_type';
        $ucsParams[':event_type']  = $eventType;
    }

    $ucsWhereSql = ' WHERE ' . implode(' AND ', $ucsWhere);

    try {
        $ucsCountStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM alumni_events e" . $ucsWhereSql
        );
        $ucsCountStmt->execute($ucsParams);
        $ucsTotal = (int) $ucsCountStmt->fetchColumn();

        $ucsTotalPages = max(1, (int) ceil($ucsTotal / $show));
        $page          = min($page, $ucsTotalPages);
        $ucsOffset     = ($page - 1) * $show;

        $ucsStmt = $pdo->prepare(
            "SELECT e.id, e.title, e.description, e.event_type, e.venue,
                    e.starts_at, e.ends_at, e.registration_link, e.status
             FROM alumni_events e"
            . $ucsWhereSql
            . " ORDER BY e.starts_at ASC, e.id ASC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($ucsParams as $ucsKey => $ucsVal) {
            $ucsStmt->bindValue($ucsKey, $ucsVal);
        }
        $ucsStmt->bindValue(':limit', $show, PDO::PARAM_INT);
        $ucsStmt->bindValue(':offset', $ucsOffset, PDO::PARAM_INT);
        $ucsStmt->execute();
        return [
            'items'       => $ucsStmt->fetchAll() ?: [],
            'total'       => $ucsTotal,
            'total_pages' => $ucsTotalPages,
        ];
    } catch (PDOException $e) {
        return ['items' => [], 'total' => 0, 'total_pages' => 1];
    }
}