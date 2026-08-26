<?php
/**
 * Shared validation helpers for the Admin Teachers module.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

/**
 * Validate and normalise teacher form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO|null $pdo       Database connection for duplicate/ FK checks.
 * @param int|null $excludeId ID to exclude from duplicate name check (edit mode).
 * @return array{clean:array, errors:array}
 */
function teacher_validate_input($input, $pdo = null, $excludeId = null)
{
    $errors = [];

    $teacherId    = trim((string) ($input['teacher_id'] ?? ''));
    $name         = trim((string) ($input['name'] ?? ''));
    $email        = trim((string) ($input['email'] ?? ''));
    $phone        = trim((string) ($input['phone'] ?? ''));
    $facultyId    = filter_var($input['faculty_id'] ?? null, FILTER_VALIDATE_INT);
    $departmentId = filter_var($input['department_id'] ?? null, FILTER_VALIDATE_INT);
    $specialization = trim((string) ($input['specialization'] ?? ''));
    $status       = $input['status'] ?? 1;

    // teacher_id
    if ($teacherId === '') {
        $errors[] = 'Teacher ID is required.';
    } elseif (mb_strlen($teacherId) > 50) {
        $errors[] = 'Teacher ID must be 50 characters or fewer.';
    } elseif ($pdo !== null) {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM teachers WHERE teacher_id = :tid LIMIT 1");
            $ucsStmt->execute([':tid' => $teacherId]);
            $existingId = $ucsStmt->fetchColumn();
            if ($existingId !== false && (int) $existingId !== (int) $excludeId) {
                $errors[] = 'A teacher with this Teacher ID already exists.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to check for duplicate Teacher ID. Please try again.';
        }
    }

    // Name
    if ($name === '') {
        $errors[] = 'Teacher name is required.';
    } elseif (mb_strlen($name) > 255) {
        $errors[] = 'Teacher name must be 255 characters or fewer.';
    }

    // Email (optional but validate format if provided)
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Must belong to either a faculty OR a department (not both unless dual affiliation is supported)
    $hasFaculty    = ($facultyId !== false && $facultyId > 0);
    $hasDepartment = ($departmentId !== false && $departmentId > 0);

    if (!$hasFaculty && !$hasDepartment) {
        $errors[] = 'A teacher must belong to either a faculty or an academic department.';
    } elseif ($hasFaculty && $hasDepartment) {
        $errors[] = 'A teacher cannot be assigned to both a faculty and a department. Please choose one.';
    }

    // Validate faculty FK
    if ($hasFaculty && $pdo !== null) {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM faculties WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $facultyId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected faculty does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the faculty. Please try again.';
        }
    }

    // Validate department FK
    if ($hasDepartment && $pdo !== null) {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM departments WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $departmentId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected department does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the department. Please try again.';
        }
    }

    if (!in_array($status, [0, 1, '0', '1'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'teacher_id'     => $teacherId,
            'name'           => $name,
            'email'          => $email === '' ? null : $email,
            'phone'          => $phone === '' ? null : $phone,
            'faculty_id'     => $hasFaculty ? $facultyId : null,
            'department_id'  => $hasDepartment ? $departmentId : null,
            'specialization' => $specialization === '' ? null : $specialization,
            'status'         => in_array($status, [1, '1'], true) ? 1 : 0,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Teachers module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function teacher_flash($type, $message)
{
    $_SESSION['teacher_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}
