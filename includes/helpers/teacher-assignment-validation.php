<?php
/**
 * Shared validation helpers for the Admin Teacher Course Assignments module.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

/**
 * Validate and normalise teacher course assignment form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO|null $pdo       Database connection for FK checks.
 * @param int|null $excludeId ID to exclude from duplicate check (edit mode).
 * @return array{clean:array, errors:array}
 */
function teacher_assignment_validate_input($input, $pdo = null, $excludeId = null)
{
    $errors = [];

    $teacherId   = filter_var($input['teacher_id'] ?? null, FILTER_VALIDATE_INT);
    $courseId    = filter_var($input['course_id'] ?? null, FILTER_VALIDATE_INT);
    $classroomId = filter_var($input['classroom_id'] ?? null, FILTER_VALIDATE_INT);

    // teacher
    if ($teacherId === false || $teacherId < 1) {
        $errors[] = 'A teacher must be selected.';
    } elseif ($pdo !== null) {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM teachers WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $teacherId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected teacher does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the teacher. Please try again.';
        }
    }

    // course
    if ($courseId === false || $courseId < 1) {
        $errors[] = 'A course must be selected.';
    } elseif ($pdo !== null) {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM courses WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $courseId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected course does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the course. Please try again.';
        }
    }

    // classroom
    if ($classroomId === false || $classroomId < 1) {
        $errors[] = 'A classroom must be selected.';
    } elseif ($pdo !== null) {
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

    // Validate course and classroom belong to the same academic year
    if (($courseId !== false && $courseId > 0) && ($classroomId !== false && $classroomId > 0) && $pdo !== null) {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT c.academic_year_id AS course_year, cl.academic_year_id AS classroom_year
                 FROM courses c, classrooms cl
                 WHERE c.id = :course_id AND cl.id = :classroom_id LIMIT 1"
            );
            $ucsStmt->execute([':course_id' => $courseId, ':classroom_id' => $classroomId]);
            $ucsRow = $ucsStmt->fetch();
            if ($ucsRow && (int) $ucsRow['course_year'] !== (int) $ucsRow['classroom_year']) {
                $errors[] = 'The course and classroom must belong to the same academic year.';
            }
        } catch (PDOException $e) {
            // Skip this check on error
        }
    }

    // Validate classroom major/year_level compatible with course
    if (($courseId !== false && $courseId > 0) && ($classroomId !== false && $classroomId > 0) && $pdo !== null) {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT c.major_id AS course_major, c.year_level AS course_year_level,
                        cl.major_id AS classroom_major, cl.year_level AS classroom_year_level
                 FROM courses c, classrooms cl
                 WHERE c.id = :course_id AND cl.id = :classroom_id LIMIT 1"
            );
            $ucsStmt->execute([':course_id' => $courseId, ':classroom_id' => $classroomId]);
            $ucsRow = $ucsStmt->fetch();
            if ($ucsRow) {
                $courseMajor = $ucsRow['course_major'] ? (int) $ucsRow['course_major'] : null;
                $classroomMajor = $ucsRow['classroom_major'] ? (int) $ucsRow['classroom_major'] : null;
                if ($courseMajor !== null && $classroomMajor !== null && $courseMajor !== $classroomMajor) {
                    $errors[] = 'The course major and classroom major are not compatible.';
                }
                if ((string) $ucsRow['course_year_level'] !== (string) $ucsRow['classroom_year_level']) {
                    $errors[] = 'The course year level and classroom year level are not compatible.';
                }
            }
        } catch (PDOException $e) {
            // Skip this check on error
        }
    }

    // Duplicate check
    if ($teacherId > 0 && $courseId > 0 && $classroomId > 0 && $pdo !== null) {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT id FROM teacher_course_assignments
                 WHERE teacher_id = :teacher_id
                   AND course_id = :course_id
                   AND classroom_id = :classroom_id
                 LIMIT 1"
            );
            $ucsStmt->execute([
                ':teacher_id'   => $teacherId,
                ':course_id'    => $courseId,
                ':classroom_id' => $classroomId,
            ]);
            $existingId = $ucsStmt->fetchColumn();
            if ($existingId !== false && (int) $existingId !== (int) $excludeId) {
                $errors[] = 'This teacher-course-classroom assignment already exists.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to check for duplicate assignments. Please try again.';
        }
    }

    return [
        'clean' => [
            'teacher_id'   => ($teacherId !== false && $teacherId > 0) ? $teacherId : null,
            'course_id'    => ($courseId !== false && $courseId > 0) ? $courseId : null,
            'classroom_id' => ($classroomId !== false && $classroomId > 0) ? $classroomId : null,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Teacher Assignment module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function teacher_assignment_flash($type, $message)
{
    $_SESSION['teacher_assignment_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}
