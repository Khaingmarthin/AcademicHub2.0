<?php
/**
 * Shared validation helpers for the Admin Academic Years module.
 *
 * Used by both the create and update handlers so the academic year input
 * rules live in exactly one place. Returns clean, normalised values plus a
 * list of human-readable validation errors.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

/**
 * Normalise a submitted academic year label.
 *
 * Accepts common separators ("2025-2026", "2025 - 2026", "2025–2026" ...)
 * and returns a single canonical "YYYY-YYYY" form.
 *
 * @param string $value Raw submitted label.
 * @return string Normalised label.
 */
function academic_year_normalize_label($value)
{
    $value = trim((string) $value);
    $value = preg_replace('/\s*[-–—]\s*/u', '-', $value);
    return trim($value);
}

/**
 * Validate and normalise academic year form input.
 *
 * Checks the year label format, optional start/end dates, the status enum
 * and uniqueness of the label (against the whole table, or excluding a row
 * when editing). All checks are performed before any write happens.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO      $pdo       Database connection used for the duplicate check.
 * @param int|null $excludeId Row id to ignore when editing an existing year.
 * @return array{clean:array, errors:array}
 */
function academic_year_validate_input($input, $pdo, $excludeId = null)
{
    $errors = [];

    $yearName  = academic_year_normalize_label($input['year_name'] ?? '');
    $startDate = trim((string) ($input['start_date'] ?? ''));
    $endDate   = trim((string) ($input['end_date'] ?? ''));
    $status    = trim((string) ($input['status'] ?? ''));

    // ---- Year label ----------------------------------------------------
    if ($yearName === '') {
        $errors[] = 'Academic year label is required.';
    } elseif (!preg_match('/^\d{4}-\d{4}$/', $yearName)) {
        $errors[] = 'Invalid academic year label. Use the format YYYY-YYYY, for example 2025-2026.';
    } elseif (strlen($yearName) > 20) {
        $errors[] = 'Academic year label must be 20 characters or fewer.';
    } else {
        $ucsLabelParts = explode('-', $yearName);
        if ((int) $ucsLabelParts[1] <= (int) $ucsLabelParts[0]) {
            $errors[] = 'The end year must be later than the start year, for example 2025-2026.';
        }
    }

    // ---- Start / end dates --------------------------------------------
    $ucsDatesOk = true;
    foreach (['start_date' => $startDate, 'end_date' => $endDate] as $ucsKey => $ucsValue) {
        if ($ucsValue === '') {
            continue;
        }
        $ucsParts = explode('-', $ucsValue);
        if (count($ucsParts) !== 3
            || !checkdate((int) $ucsParts[1], (int) $ucsParts[2], (int) $ucsParts[0])) {
            $errors[] = 'Invalid ' . str_replace('_', ' ', $ucsKey) . '. Use the format YYYY-MM-DD.';
            $ucsDatesOk = false;
        }
    }
    if ($ucsDatesOk && $startDate !== '' && $endDate !== '' && $startDate > $endDate) {
        $errors[] = 'The start date must be on or before the end date.';
    }

    // ---- Status --------------------------------------------------------
    if (!in_array($status, ['Preparation', 'Active', 'Archived'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    // ---- Duplicate label ----------------------------------------------
    if ($yearName !== '' && $pdo !== null) {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT id FROM academic_years WHERE year_name = :year_name LIMIT 1"
            );
            $ucsStmt->execute([':year_name' => $yearName]);
            $ucsExistingId = $ucsStmt->fetchColumn();
            if ($ucsExistingId !== false && (int) $ucsExistingId !== (int) $excludeId) {
                $errors[] = 'An academic year with this label already exists.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to check for duplicate academic years. Please try again.';
        }
    }

    return [
        'clean' => [
            'year_name'  => $yearName,
            'start_date' => $startDate === '' ? null : $startDate,
            'end_date'   => $endDate === '' ? null : $endDate,
            'status'     => $status,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Academic Years module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function academic_year_flash($type, $message)
{
    $_SESSION['academic_year_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}
