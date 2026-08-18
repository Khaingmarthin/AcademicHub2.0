<?php
/**
 * Shared validation helpers for the Admin Classrooms module.
 *
 * Used by both the create and update handlers so the classroom input rules
 * live in exactly one place. Returns clean, normalised values plus a list
 * of human-readable validation errors.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

const CLASSROOM_YEAR_LEVELS = ['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Fifth Year'];

/**
 * Validate and normalise classroom form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO      $pdo       Database connection used for FK + duplicate checks.
 * @param int|null $excludeId Row id to ignore when editing an existing classroom.
 * @return array{clean:array, errors:array}
 */
function classroom_validate_input($input, $pdo, $excludeId = null)
{
    $errors = [];

    $academicYearId = filter_var($input['academic_year_id'] ?? null, FILTER_VALIDATE_INT);
    $majorId        = filter_var($input['major_id'] ?? null, FILTER_VALIDATE_INT);
    $yearLevel      = trim((string) ($input['year_level'] ?? ''));
    $section        = trim((string) ($input['section'] ?? ''));
    $classroomName  = trim((string) ($input['classroom_name'] ?? ''));
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

    // ---- Year level, section & name -----------------------------------
    if (!in_array($yearLevel, CLASSROOM_YEAR_LEVELS, true)) {
        $errors[] = 'Invalid year level selected.';
    }

    if ($section === '') {
        $errors[] = 'Section is required.';
    } elseif (mb_strlen($section) > 10) {
        $errors[] = 'Section must be 10 characters or fewer.';
    }

    if ($classroomName === '') {
        $errors[] = 'Classroom name is required.';
    } elseif (mb_strlen($classroomName) > 100) {
        $errors[] = 'Classroom name must be 100 characters or fewer.';
    }

    // ---- Duplicate (academic_year, major, year_level, section) ----------
    if ($academicYearId !== false && $academicYearId > 0
        && $majorId !== false && $majorId > 0
        && $yearLevel !== '' && $section !== '') {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT id FROM classrooms
                 WHERE academic_year_id = :ay AND major_id = :mj
                   AND year_level = :yl AND section = :sec
                 LIMIT 1"
            );
            $ucsStmt->execute([
                ':ay'  => $academicYearId,
                ':mj'  => $majorId,
                ':yl'  => $yearLevel,
                ':sec' => $section,
            ]);
            $ucsExistingId = $ucsStmt->fetchColumn();
            if ($ucsExistingId !== false && (int) $ucsExistingId !== (int) $excludeId) {
                $errors[] = 'A classroom with this section already exists for the selected academic year, major and year level.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to check for duplicate classrooms. Please try again.';
        }
    }

    if (!in_array($status, [0, 1, '0', '1'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'academic_year_id' => $academicYearId,
            'major_id'         => $majorId,
            'year_level'       => $yearLevel,
            'section'          => $section,
            'classroom_name'   => $classroomName,
            'status'           => in_array($status, [1, '1'], true) ? 1 : 0,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Classrooms module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function classroom_flash($type, $message)
{
    $_SESSION['classroom_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}