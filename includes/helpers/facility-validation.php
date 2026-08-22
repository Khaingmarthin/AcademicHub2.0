<?php
/**
 * Shared validation helpers for the Admin Facilities module.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

require_once __DIR__ . '/ucs-upload.php';

const FACILITY_IMAGE_MAX_BYTES = 5242880; // 5 MB

/**
 * Validate and normalise facility form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO|null $pdo       Unused here; kept for the shared signature.
 * @param int|null $excludeId Unused here; kept for the shared signature.
 * @return array{clean:array, errors:array}
 */
function facility_validate_input($input, $pdo = null, $excludeId = null)
{
    $errors = [];

    $name        = trim((string) ($input['name'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $location    = trim((string) ($input['location'] ?? ''));
    $status      = $input['status'] ?? 1;

    if ($name === '') {
        $errors[] = 'Facility name is required.';
    } elseif (mb_strlen($name) > 255) {
        $errors[] = 'Facility name must be 255 characters or fewer.';
    }

    if (mb_strlen($location) > 255) {
        $errors[] = 'Location must be 255 characters or fewer.';
    }

    if (!in_array($status, [0, 1, '0', '1'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    // ---- Image upload (optional) ----------------------------------------
    $ucsImage = null;
    try {
        $ucsImage = ucs_handle_upload('image', ['jpg', 'jpeg', 'png', 'gif', 'webp'], FACILITY_IMAGE_MAX_BYTES);
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    return [
        'clean' => [
            'name'        => $name,
            'image'       => $ucsImage,
            'description' => $description === '' ? null : $description,
            'location'    => $location === '' ? null : $location,
            'status'      => in_array($status, [1, '1'], true) ? 1 : 0,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Facilities module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function facility_flash($type, $message)
{
    $_SESSION['facility_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}
