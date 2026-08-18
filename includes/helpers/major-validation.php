<?php
/**
 * Shared validation helpers for the Admin Majors module.
 *
 * Used by both the create and update handlers so the major input rules
 * live in exactly one place. Returns clean, normalised values plus a list
 * of human-readable validation errors.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

/**
 * Validate and normalise major form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO|null $pdo       Unused here; kept for the shared signature.
 * @param int|null $excludeId Unused here; kept for the shared signature.
 * @return array{clean:array, errors:array}
 */
function major_validate_input($input, $pdo = null, $excludeId = null)
{
    $errors = [];

    $name        = trim((string) ($input['name'] ?? ''));
    $shortName   = trim((string) ($input['short_name'] ?? ''));
    $degreeName  = trim((string) ($input['degree_name'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $status      = $input['status'] ?? 1;

    if ($name === '') {
        $errors[] = 'Major name is required.';
    } elseif (mb_strlen($name) > 255) {
        $errors[] = 'Major name must be 255 characters or fewer.';
    }

    if (mb_strlen($shortName) > 50) {
        $errors[] = 'Short name must be 50 characters or fewer.';
    }

    if (mb_strlen($degreeName) > 100) {
        $errors[] = 'Degree name must be 100 characters or fewer.';
    }

    if (!in_array($status, [0, 1, '0', '1'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'name'        => $name,
            'short_name'  => $shortName === '' ? null : $shortName,
            'degree_name' => $degreeName === '' ? null : $degreeName,
            'description' => $description === '' ? null : $description,
            'status'      => in_array($status, [1, '1'], true) ? 1 : 0,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Majors module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function major_flash($type, $message)
{
    $_SESSION['major_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}