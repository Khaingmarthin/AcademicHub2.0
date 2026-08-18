<?php
/**
 * Shared validation helpers for the Admin Courses module.
 *
 * Used by both the create and update handlers so the course input rules
 * live in exactly one place. Returns clean, normalised values plus a list
 * of human-readable validation errors.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

const COURSE_YEAR_LEVELS = ['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Fifth Year'];
const COURSE_SEMESTERS   = ['First Semester', 'Second Semester'];

/**
 * Validate and normalise course form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO      $pdo       Database connection used for FK + duplicate checks.
 * @param int|null $excludeId Row id to ignore when editing an existing course.
 * @return array{clean:array, errors:array}
 */
function course_validate_input($input, $pdo, $excludeId = null)
{
    $errors = [];

    $academicYearId = filter_var($input['academic_year_id'] ?? null, FILTER_VALIDATE_INT);
    $majorId        = filter_var($input['major_id'] ?? null, FILTER_VALIDATE_INT);
    $courseCode     = trim((string) ($input['course_code'] ?? ''));
    $courseName     = trim((string) ($input['course_name'] ?? ''));
    $yearLevel      = trim((string) ($input['year_level'] ?? ''));
    $semester       = trim((string) ($input['semester'] ?? ''));
    $creditHours    = trim((string) ($input['credit_hours'] ?? ''));
    $description    = trim((string) ($input['description'] ?? ''));
    $status         = $input['status'] ?? 1;

    // ---- Foreign keys --------------------------------------------------
    if ($academicYearId === false || $academicYearId < 1) {
        $errors[] = 'An academic year must be selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM academic_years WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $academicYearId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected academic year does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the academic year. Please try again.';
        }
    }

    if ($majorId === false || $majorId < 1) {
        $errors[] = 'A major must be selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM majors WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $majorId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected major does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the major. Please try again.';
        }
    }

    // ---- Course code & name -------------------------------------------
    if ($courseCode === '') {
        $errors[] = 'Course code is required.';
    } elseif (mb_strlen($courseCode) > 50) {
        $errors[] = 'Course code must be 50 characters or fewer.';
    }

    if ($courseName === '') {
        $errors[] = 'Course name is required.';
    } elseif (mb_strlen($courseName) > 255) {
        $errors[] = 'Course name must be 255 characters or fewer.';
    }

    // ---- Year level & semester ----------------------------------------
    if (!in_array($yearLevel, COURSE_YEAR_LEVELS, true)) {
        $errors[] = 'Invalid year level selected.';
    }

    if (!in_array($semester, COURSE_SEMESTERS, true)) {
        $errors[] = 'Invalid semester selected.';
    }

    // ---- Credit hours --------------------------------------------------
    $creditHoursClean = null;
    if ($creditHours !== '') {
        if (!preg_match('/^\d{1,5}$/', $creditHours)) {
            $errors[] = 'Credit hours must be a whole number.';
        } else {
            $creditHoursClean = (int) $creditHours;
            if ($creditHoursClean > 500) {
                $errors[] = 'Credit hours must be 500 or fewer.';
            }
        }
    }

    // ---- Duplicate (academic_year, major, code) -------------------------
    if ($academicYearId !== false && $academicYearId > 0 && $majorId !== false && $majorId > 0 && $courseCode !== '') {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT id FROM courses
                 WHERE academic_year_id = :ay AND major_id = :mj AND course_code = :cc
                 LIMIT 1"
            );
            $ucsStmt->execute([
                ':ay' => $academicYearId,
                ':mj' => $majorId,
                ':cc' => $courseCode,
            ]);
            $ucsExistingId = $ucsStmt->fetchColumn();
            if ($ucsExistingId !== false && (int) $ucsExistingId !== (int) $excludeId) {
                $errors[] = 'A course with this code already exists for the selected academic year and major.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to check for duplicate courses. Please try again.';
        }
    }

    if (!in_array($status, [0, 1, '0', '1'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'academic_year_id' => $academicYearId,
            'major_id'         => $majorId,
            'course_code'      => $courseCode,
            'course_name'      => $courseName,
            'year_level'       => $yearLevel,
            'semester'         => $semester,
            'credit_hours'     => $creditHoursClean,
            'description'      => $description === '' ? null : $description,
            'status'           => in_array($status, [1, '1'], true) ? 1 : 0,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Courses module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function course_flash($type, $message)
{
    $_SESSION['course_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}