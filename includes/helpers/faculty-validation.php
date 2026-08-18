<?php
/**
 * Shared validation helpers for the Admin Faculties module.
 *
 * Used by both the create and update handlers so the faculty input rules
 * live in exactly one place. Returns clean, normalised values plus a list
 * of human-readable validation errors.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

/**
 * Validate and normalise faculty form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO|null $pdo       Database connection (unused here; kept for the shared signature).
 * @param int|null $excludeId Unused here; kept for the shared signature.
 * @return array{clean:array, errors:array}
 */
function faculty_validate_input($input, $pdo = null, $excludeId = null)
{
    $errors = [];

    $name        = trim((string) ($input['name'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $status      = $input['status'] ?? 1;

    if ($name === '') {
        $errors[] = 'Faculty name is required.';
    } elseif (mb_strlen($name) > 255) {
        $errors[] = 'Faculty name must be 255 characters or fewer.';
    } else {
        // Duplicate name prevention.
        if ($pdo !== null) {
            try {
                $ucsStmt = $pdo->prepare(
                    "SELECT id FROM faculties WHERE name = :name LIMIT 1"
                );
                $ucsStmt->execute([':name' => $name]);
                $ucsExistingId = $ucsStmt->fetchColumn();
                if ($ucsExistingId !== false && (int) $ucsExistingId !== (int) $excludeId) {
                    $errors[] = 'A faculty with this name already exists.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Unable to check for duplicate faculties. Please try again.';
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
 * Store a flash message for the Faculties module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function faculty_flash($type, $message)
{
    $_SESSION['faculty_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}
