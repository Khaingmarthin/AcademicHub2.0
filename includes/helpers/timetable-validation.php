<?php
/**
 * Shared validation helpers for the Admin Timetables module.
 *
 * Used by both the create and update handlers so the timetable input rules
 * live in exactly one place. Returns clean, normalised values plus a list
 * of human-readable validation errors.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

require_once __DIR__ . '/ucs-upload.php';

const TIMETABLE_SEMESTERS = ['First Semester', 'Second Semester'];
const TIMETABLE_IMAGE_MAX_BYTES = 2097152; // 2 MB

/**
 * Validate and normalise timetable form input.
 *
 * @param array      $input     Raw form values (e.g. $_POST).
 * @param PDO        $pdo       Database connection used for FK + duplicate checks.
 * @param int|null   $excludeId Row id to ignore when editing an existing timetable.
 * @param string|null $existingImage Relative image path of the current row when editing.
 * @return array{clean:array, errors:array}
 */
function timetable_validate_input($input, $pdo, $excludeId = null, $existingImage = null)
{
    $errors = [];

    $classroomId = filter_var($input['classroom_id'] ?? null, FILTER_VALIDATE_INT);
    $semester    = trim((string) ($input['semester'] ?? ''));
    $title       = trim((string) ($input['title'] ?? ''));
    $status      = $input['status'] ?? 1;

    // ---- Foreign key ---------------------------------------------------
    if ($classroomId === false || $classroomId < 1) {
        $errors[] = 'A classroom must be selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM classrooms WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $classroomId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected classroom does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the classroom. Please try again.';
        }
    }

    // ---- Semester, title -----------------------------------------------
    if (!in_array($semester, TIMETABLE_SEMESTERS, true)) {
        $errors[] = 'Invalid semester selected.';
    }

    if ($title === '') {
        $errors[] = 'Title is required.';
    } elseif (mb_strlen($title) > 255) {
        $errors[] = 'Title must be 255 characters or fewer.';
    }

    // ---- Image ----------------------------------------------------------
    $ucsImage = null;
    try {
        $ucsImage = ucs_handle_upload('image', ['jpg', 'jpeg', 'png', 'gif', 'webp'], TIMETABLE_IMAGE_MAX_BYTES);
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    if ($ucsImage === null && $excludeId === null && $existingImage === null) {
        $errors[] = 'A timetable image is required.';
    }

    // ---- Duplicate (classroom_id, semester) ------------------------------
    if ($classroomId !== false && $classroomId > 0 && $semester !== '') {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT id FROM timetables
                 WHERE classroom_id = :classroom_id AND semester = :semester
                 LIMIT 1"
            );
            $ucsStmt->execute([
                ':classroom_id' => $classroomId,
                ':semester'     => $semester,
            ]);
            $ucsExistingId = $ucsStmt->fetchColumn();
            if ($ucsExistingId !== false && (int) $ucsExistingId !== (int) $excludeId) {
                $errors[] = 'A timetable already exists for this classroom and semester.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to check for duplicate timetables. Please try again.';
        }
    }

    if (!in_array($status, [0, 1, '0', '1'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'classroom_id' => $classroomId,
            'semester'     => $semester,
            'title'        => $title,
            'image'        => $ucsImage,
            'status'       => in_array($status, [1, '1'], true) ? 1 : 0,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Timetables module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function timetable_flash($type, $message)
{
    $_SESSION['timetable_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}