<?php
/**
 * Shared validation helpers for the Admin Students module.
 *
 * Used by both the create and update handlers so the student input rules
 * live in exactly one place. Returns clean, normalised values plus a list
 * of human-readable validation errors.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

/**
 * Validate and normalise student form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO      $pdo       Database connection used for FK + duplicate checks.
 * @param int|null $excludeId Row id to ignore when editing an existing student.
 * @return array{clean:array, errors:array}
 */
function student_validate_input($input, $pdo, $excludeId = null)
{
    $errors = [];

    $studentId    = trim((string) ($input['student_id'] ?? ''));
    $name         = trim((string) ($input['name'] ?? ''));
    $email        = strtolower(trim((string) ($input['email'] ?? '')));
    $password     = (string) ($input['password'] ?? '');
    $classroomId  = filter_var($input['classroom_id'] ?? null, FILTER_VALIDATE_INT);
    $status       = $input['status'] ?? 1;

    // ---- Student ID ------------------------------------------------------
    if ($studentId === '') {
        $errors[] = 'Student ID is required.';
    } elseif (mb_strlen($studentId) > 50) {
        $errors[] = 'Student ID must be 50 characters or fewer.';
    } else {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM students WHERE student_id = :sid LIMIT 1");
            $ucsStmt->execute([':sid' => $studentId]);
            $ucsExistingId = $ucsStmt->fetchColumn();
            if ($ucsExistingId !== false && (int) $ucsExistingId !== (int) $excludeId) {
                $errors[] = 'This Student ID is already in use.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to check the Student ID. Please try again.';
        }
    }

    // ---- Name ------------------------------------------------------------
    if ($name === '') {
        $errors[] = 'Name is required.';
    } elseif (mb_strlen($name) > 255) {
        $errors[] = 'Name must be 255 characters or fewer.';
    }

    // ---- Email -----------------------------------------------------------
    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 191) {
        $errors[] = 'Email address must be 191 characters or fewer.';
    } else {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM students WHERE email = :email LIMIT 1");
            $ucsStmt->execute([':email' => $email]);
            $ucsExistingId = $ucsStmt->fetchColumn();
            if ($ucsExistingId !== false && (int) $ucsExistingId !== (int) $excludeId) {
                $errors[] = 'This email address is already in use.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to check the email address. Please try again.';
        }
    }

    // ---- Password --------------------------------------------------------
    if ($excludeId === null) {
        if ($password === '') {
            $errors[] = 'A password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
    } elseif ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    // ---- Classroom -------------------------------------------------------
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

    if (!in_array($status, [0, 1, '0', '1'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'student_id'   => $studentId,
            'name'         => $name,
            'email'        => $email,
            'password'     => $password,
            'classroom_id' => $classroomId,
            'status'       => in_array($status, [1, '1'], true) ? 1 : 0,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Students module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function student_flash($type, $message)
{
    $_SESSION['student_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}