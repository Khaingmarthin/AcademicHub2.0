<?php
/**
 * Shared file upload helper for the admin modules.
 *
 * Handles an optional file upload from a form, validates its extension and
 * size, moves it into the project uploads folder and returns the stored
 * path ("uploads/<name>") relative to the assets directory so public pages
 * can resolve it with ROOT_URL . '/assets/' . ltrim($path, '/').
 *
 * Returns null when no file was submitted; throws \RuntimeException when a
 * file was submitted but is invalid.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

/**
 * Process a single uploaded file field.
 *
 * @param string $field       The name of the file input (e.g. 'image').
 * @param array  $allowedExts Lower-case extensions that are accepted.
 * @param int    $maxBytes    Maximum accepted file size.
 * @return string|null Stored relative path ("uploads/<name>") or null.
 * @throws RuntimeException When an invalid file is submitted.
 */
function ucs_handle_upload($field, array $allowedExts, $maxBytes)
{
    if (!isset($_FILES[$field])) {
        return null;
    }

    $ucsFile = $_FILES[$field];

    if (!is_array($ucsFile) || !isset($ucsFile['error'])) {
        return null;
    }

    if ((int) $ucsFile['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int) $ucsFile['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file could not be uploaded. Please try again.');
    }

    if ((int) $ucsFile['size'] <= 0) {
        throw new RuntimeException('The uploaded file is empty.');
    }

    if ((int) $ucsFile['size'] > $maxBytes) {
        throw new RuntimeException('The uploaded file is too large. Maximum allowed size is ' . round($maxBytes / 1024) . ' KB.');
    }

    $ucsOriginalName = (string) ($ucsFile['name'] ?? '');
    $ucsExt          = strtolower(pathinfo($ucsOriginalName, PATHINFO_EXTENSION));

    if ($ucsExt === '' || !in_array($ucsExt, $allowedExts, true)) {
        throw new RuntimeException('Invalid file type. Allowed types: ' . strtoupper(implode(', ', $allowedExts)) . '.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('The uploads folder is not available.');
    }

    $ucsFilename = date('YmdHis') . '-' . substr(bin2hex(random_bytes(6)), 0, 12) . '.' . $ucsExt;
    $ucsTarget   = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $ucsFilename;

    if (!move_uploaded_file((string) $ucsFile['tmp_name'], $ucsTarget)) {
        throw new RuntimeException('The uploaded file could not be saved. Please try again.');
    }

    return 'uploads/' . $ucsFilename;
}

/**
 * Process multiple files from a single file input field (name="field[]").
 *
 * Returns an array of successfully uploaded relative paths. Skips empty
 * slots (no file selected) but throws on invalid files.
 *
 * @param string $field       The name of the file input (must use [] suffix).
 * @param array  $allowedExts Lower-case extensions that are accepted.
 * @param int    $maxBytes    Maximum accepted file size per file.
 * @param int    $maxFiles    Maximum number of files accepted (0 = unlimited).
 * @return array List of stored relative paths ("uploads/<name>").
 * @throws RuntimeException When an invalid file is submitted.
 */
function ucs_handle_multi_upload($field, array $allowedExts, $maxBytes, $maxFiles = 0)
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['error'])) {
        return [];
    }

    $ucsErrors = $_FILES[$field]['error'];
    $ucsCount  = count($ucsErrors);
    $results   = [];

    for ($i = 0; $i < $ucsCount; $i++) {
        if ((int) $ucsErrors[$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($maxFiles > 0 && count($results) >= $maxFiles) {
            throw new RuntimeException('You can upload a maximum of ' . $maxFiles . ' images.');
        }

        if ((int) $ucsErrors[$i] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('One of the files could not be uploaded. Please try again.');
        }

        if ((int) $_FILES[$field]['size'][$i] <= 0) {
            throw new RuntimeException('One of the uploaded files is empty.');
        }

        if ((int) $_FILES[$field]['size'][$i] > $maxBytes) {
            throw new RuntimeException('One of the files exceeds the maximum size of ' . round($maxBytes / 1024 / 1024, 1) . ' MB.');
        }

        $ucsOriginalName = (string) ($_FILES[$field]['name'][$i] ?? '');
        $ucsExt = strtolower(pathinfo($ucsOriginalName, PATHINFO_EXTENSION));

        if ($ucsExt === '' || !in_array($ucsExt, $allowedExts, true)) {
            throw new RuntimeException('Invalid file type for "' . $ucsOriginalName . '". Allowed: ' . strtoupper(implode(', ', $allowedExts)) . '.');
        }

        if (!is_dir(UPLOAD_DIR)) {
            throw new RuntimeException('The uploads folder is not available.');
        }

        $ucsFilename = date('YmdHis') . '-' . substr(bin2hex(random_bytes(6)), 0, 12) . '.' . $ucsExt;
        $ucsTarget   = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $ucsFilename;

        if (!move_uploaded_file((string) $_FILES[$field]['tmp_name'][$i], $ucsTarget)) {
            throw new RuntimeException('The file "' . $ucsOriginalName . '" could not be saved.');
        }

        $results[] = 'uploads/' . $ucsFilename;
    }

    return $results;
}

/**
 * Delete a stored upload ("uploads/<name>") if it exists.
 *
 * @param string|null $path Relative path to the file (or null).
 * @return void
 */
function ucs_delete_upload($path)
{
    if ($path === null || $path === '') {
        return;
    }
    $ucsPath = (string) $path;
    if (strpos($ucsPath, 'uploads/') !== 0) {
        return;
    }
    $ucsFile = UPLOAD_DIR . substr($ucsPath, strlen('uploads/'));
    if (is_file($ucsFile)) {
        @unlink($ucsFile);
    }
}