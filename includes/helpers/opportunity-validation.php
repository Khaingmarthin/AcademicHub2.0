<?php
/**
 * Shared validation + support helpers for the Career Opportunities module.
 *
 * Career Opportunities is a moderated job board in the Alumni & Career
 * Community: verified alumni share real career opportunities (jobs,
 * internships, freelance work) that current students and other alumni can
 * browse publicly. Only verified alumni can post; admins can edit, hide or
 * delete any posting.
 *
 * All ownership checks, moderation rules and expiry logic are enforced
 * server-side here; hiding buttons on a page is never the only line of
 * defence.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

const OPPORTUNITY_EMPLOYMENT_TYPES = ['full_time', 'part_time', 'internship', 'contract', 'freelance'];
const OPPORTUNITY_EMPLOYMENT_LABELS = [
    'full_time'  => 'Full-Time',
    'part_time'  => 'Part-Time',
    'internship' => 'Internship',
    'contract'   => 'Contract',
    'freelance'  => 'Freelance',
];
const OPPORTUNITY_STATUSES     = ['active', 'hidden'];
const OPPORTUNITY_TITLE_MAX    = 255;
const OPPORTUNITY_COMPANY_MAX  = 255;
const OPPORTUNITY_LOCATION_MAX = 191;
const OPPORTUNITY_SALARY_MAX   = 191;

/**
 * Store a flash message for the Career Opportunities pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function opportunity_flash($type, $message)
{
    $_SESSION['opportunity_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}

/**
 * Human-readable label for an employment type.
 *
 * @param string $type Employment type key.
 * @return string
 */
function opportunity_employment_label($type)
{
    return OPPORTUNITY_EMPLOYMENT_LABELS[$type] ?? 'Full-Time';
}

/**
 * Whether a stored opportunity is currently listed publicly.
 *
 * An opportunity is public when it is 'active' and its optional deadline has
 * not passed. Expired postings drop off the public page automatically but
 * remain visible to the owner and admins.
 *
 * @param array $row A career_opportunities row.
 * @return bool
 */
function opportunity_is_public($row)
{
    if ((string) ($row['status'] ?? '') !== 'active') {
        return false;
    }
    $ucsExpires = (string) ($row['expires_at'] ?? '');
    if ($ucsExpires === '' || $ucsExpires === null) {
        return true;
    }
    return strtotime($ucsExpires) >= strtotime(date('Y-m-d'));
}

/**
 * Validate and normalise a career opportunity form.
 *
 * Shared by the alumnus's own create/edit forms and the admin editor, so the
 * input rules live in exactly one place.
 *
 * @param array $input Raw form values (e.g. $_POST).
 * @param PDO   $pdo   Database connection.
 * @return array{clean:array, errors:array}
 */
function opportunity_validate_input($input, $pdo)
{
    $errors = [];

    $title          = trim((string) ($input['title'] ?? ''));
    $company        = trim((string) ($input['company'] ?? ''));
    $location       = trim((string) ($input['location'] ?? ''));
    $employmentType = (string) ($input['employment_type'] ?? '');
    $salaryRange    = trim((string) ($input['salary_range'] ?? ''));
    $description    = trim((string) ($input['description'] ?? ''));
    $howToApply     = trim((string) ($input['how_to_apply'] ?? ''));
    $expiresAt      = trim((string) ($input['expires_at'] ?? ''));
    $status         = (string) ($input['status'] ?? 'active');

    if ($title === '') {
        $errors[] = 'A job title is required.';
    } elseif (mb_strlen($title) > OPPORTUNITY_TITLE_MAX) {
        $errors[] = 'The job title must be ' . OPPORTUNITY_TITLE_MAX . ' characters or fewer.';
    }

    if ($company === '') {
        $errors[] = 'The company or employer name is required.';
    } elseif (mb_strlen($company) > OPPORTUNITY_COMPANY_MAX) {
        $errors[] = 'The company name must be ' . OPPORTUNITY_COMPANY_MAX . ' characters or fewer.';
    }

    if (mb_strlen($location) > OPPORTUNITY_LOCATION_MAX) {
        $errors[] = 'The location must be ' . OPPORTUNITY_LOCATION_MAX . ' characters or fewer.';
    }

    if (mb_strlen($salaryRange) > OPPORTUNITY_SALARY_MAX) {
        $errors[] = 'The salary range must be ' . OPPORTUNITY_SALARY_MAX . ' characters or fewer.';
    }

    if (!in_array($employmentType, OPPORTUNITY_EMPLOYMENT_TYPES, true)) {
        $errors[] = 'Invalid employment type selected.';
    }

    if ($description === '') {
        $errors[] = 'A description of the opportunity is required.';
    }

    if ($expiresAt !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiresAt)) {
            $errors[] = 'Please enter a valid deadline date.';
        } elseif (strtotime($expiresAt) < strtotime(date('Y-m-d'))) {
            $errors[] = 'The deadline must be today or later.';
        }
    }

    if (!in_array($status, OPPORTUNITY_STATUSES, true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'title'           => $title,
            'company'         => $company,
            'location'        => $location,
            'employment_type' => $employmentType,
            'salary_range'    => $salaryRange,
            'description'     => $description,
            'how_to_apply'    => $howToApply,
            'expires_at'      => $expiresAt !== '' ? $expiresAt : null,
            'status'          => $status,
        ],
        'errors' => $errors,
    ];
}

/**
 * Load a single career opportunity with poster details.
 *
 * @param PDO $pdo Database connection.
 * @param int $id  Opportunity id.
 * @return array|null
 */
function opportunity_get($pdo, $id)
{
    if ($id < 1) {
        return null;
    }

    try {
        $ucsStmt = $pdo->prepare(
            "SELECT o.id, o.posted_by_student_id, o.title, o.company, o.location,
                    o.employment_type, o.salary_range, o.description, o.how_to_apply,
                    o.expires_at, o.status, o.created_at, o.updated_at,
                    s.name AS poster_name,
                    (ap.id IS NOT NULL AND ap.verification_status = 'verified') AS poster_verified,
                    ap.id AS poster_alumni_profile_id,
                    ap.visibility AS poster_profile_visibility
             FROM career_opportunities o
             JOIN students s ON s.id = o.posted_by_student_id
             LEFT JOIN alumni_profiles ap
                    ON ap.student_id = s.id AND ap.verification_status = 'verified'
             WHERE o.id = :id
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $id]);
        return $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Whether the given student posted the given opportunity.
 *
 * @param PDO $pdo       Database connection.
 * @param int $id        Opportunity id.
 * @param int $studentId Student id.
 * @return bool
 */
function opportunity_owns($pdo, $id, $studentId)
{
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id FROM career_opportunities
             WHERE id = :id AND posted_by_student_id = :student_id
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $id, ':student_id' => $studentId]);
        return $ucsStmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Load the opportunities posted by a single student (owner's management view).
 *
 * @param PDO $pdo       Database connection.
 * @param int $studentId Student id.
 * @return array
 */
function opportunity_load_own($pdo, $studentId)
{
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id, title, company, location, employment_type, expires_at, status, created_at
             FROM career_opportunities
             WHERE posted_by_student_id = :student_id
             ORDER BY created_at DESC, id DESC"
        );
        $ucsStmt->execute([':student_id' => $studentId]);
        return $ucsStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Load the public (active, unexpired) opportunities with optional search and
 * employment-type filters and pagination.
 *
 * @param PDO    $pdo             Database connection.
 * @param string $query           Free-text search on title / company / location.
 * @param string $employmentType  Optional employment-type filter ('' = all).
 * @param int    $show            Per-page limit (10/25/50).
 * @param int    $page            1-based page number.
 * @return array{items:array,total:int,total_pages:int}
 */
function opportunity_load_public($pdo, $query, $employmentType, $show, $page)
{
    $ucsWhere  = ["o.status = 'active' AND (o.expires_at IS NULL OR o.expires_at >= CURDATE())"];
    $ucsParams = [];

    if ($query !== '') {
        $ucsEscaped         = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
        $ucsWhere[]         = '(o.title LIKE :q OR o.company LIKE :q OR o.location LIKE :q)';
        $ucsParams[':q']    = '%' . $ucsEscaped . '%';
    }
    if ($employmentType !== '') {
        $ucsWhere[]                = 'o.employment_type = :employment_type';
        $ucsParams[':employment_type'] = $employmentType;
    }

    $ucsWhereSql = ' WHERE ' . implode(' AND ', $ucsWhere);

    try {
        $ucsCountStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM career_opportunities o" . $ucsWhereSql
        );
        $ucsCountStmt->execute($ucsParams);
        $ucsTotal = (int) $ucsCountStmt->fetchColumn();

        $ucsTotalPages = max(1, (int) ceil($ucsTotal / $show));
        $page          = min($page, $ucsTotalPages);
        $ucsOffset     = ($page - 1) * $show;

        $ucsStmt = $pdo->prepare(
            "SELECT o.id, o.title, o.company, o.location, o.employment_type,
                    o.salary_range, o.description, o.how_to_apply, o.expires_at,
                    o.status, o.created_at,
                    s.name AS poster_name
             FROM career_opportunities o
             JOIN students s ON s.id = o.posted_by_student_id"
            . $ucsWhereSql
            . " ORDER BY o.created_at DESC, o.id DESC
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

/**
 * Load the public (active, unexpired) opportunities posted by a single student.
 *
 * Used on public alumni profiles to cross-link "Career Opportunities shared
 * by this alumnus" without revealing anything private.
 *
 * @param PDO $pdo       Database connection.
 * @param int $studentId Student id.
 * @param int $limit     Maximum number of rows.
 * @return array
 */
function opportunity_load_public_by_student($pdo, $studentId, $limit = 5)
{
    if ($studentId < 1) {
        return [];
    }

    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id, title, company, location, employment_type, expires_at, created_at
             FROM career_opportunities
             WHERE posted_by_student_id = :student_id
               AND status = 'active'
               AND (expires_at IS NULL OR expires_at >= CURDATE())
             ORDER BY created_at DESC, id DESC
             LIMIT :limit"
        );
        $ucsStmt->bindValue(':student_id', $studentId);
        $ucsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $ucsStmt->execute();
        return $ucsStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Load the most recent opportunities posted by a student for the dashboard.
 *
 * @param PDO $pdo       Database connection.
 * @param int $studentId Student id.
 * @param int $limit     Maximum number of rows.
 * @return array
 */
function opportunity_load_recent_own($pdo, $studentId, $limit = 5)
{
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id, title, company, employment_type, expires_at, status, created_at
             FROM career_opportunities
             WHERE posted_by_student_id = :student_id
             ORDER BY created_at DESC, id DESC
             LIMIT :limit"
        );
        $ucsStmt->bindValue(':student_id', $studentId);
        $ucsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $ucsStmt->execute();
        return $ucsStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}