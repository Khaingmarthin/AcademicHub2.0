<?php
/**
 * Shared validation helpers for the Admin Departments module.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

/**
 * Validate and normalise department form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO|null $pdo       Database connection for duplicate checks.
 * @param int|null $excludeId ID to exclude from duplicate name check (edit mode).
 * @return array{clean:array, errors:array}
 */
function department_validate_input($input, $pdo = null, $excludeId = null)
{
    $errors = [];

    $name        = trim((string) ($input['name'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $status      = $input['status'] ?? 1;

    if ($name === '') {
        $errors[] = 'Department name is required.';
    } elseif (mb_strlen($name) > 255) {
        $errors[] = 'Department name must be 255 characters or fewer.';
    } else {
        // Duplicate name prevention.
        if ($pdo !== null) {
            try {
                $ucsStmt = $pdo->prepare(
                    "SELECT id FROM departments WHERE name = :name LIMIT 1"
                );
                $ucsStmt->execute([':name' => $name]);
                $ucsExistingId = $ucsStmt->fetchColumn();
                if ($ucsExistingId !== false && (int) $ucsExistingId !== (int) $excludeId) {
                    $errors[] = 'A department with this name already exists.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Unable to check for duplicate departments. Please try again.';
            }
        }
    }

    if (!in_array($status, [0, 1, '0', '1'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'name'        => $name,
            'description' => $description === '' ? null : $description,
            'status'      => in_array($status, [1, '1'], true) ? 1 : 0,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Departments module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function department_flash($type, $message)
{
    $_SESSION['department_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}
