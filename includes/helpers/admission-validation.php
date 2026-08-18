<?php
/**
 * Shared validation helpers for the Admin Admissions module.
 *
 * Used by both the create and update handlers so the admission input rules
 * live in exactly one place. Returns clean, normalised values plus a list
 * of human-readable validation errors.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

require_once __DIR__ . '/ucs-upload.php';

const ADMISSION_DOCUMENT_MAX_BYTES = 5242880; // 5 MB

/**
 * Validate and normalise admission form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO      $pdo       Database connection used for FK checks.
 * @return array{clean:array, errors:array}
 */
function admission_validate_input($input, $pdo)
{
    $errors = [];

    $academicYearId = filter_var($input['academic_year_id'] ?? null, FILTER_VALIDATE_INT);
    $title          = trim((string) ($input['title'] ?? ''));
    $description    = trim((string) ($input['description'] ?? ''));
    $requirements   = trim((string) ($input['requirements'] ?? ''));
    $importantDates = trim((string) ($input['important_dates'] ?? ''));
    $applicationInfo = trim((string) ($input['application_info'] ?? ''));
    $status         = $input['status'] ?? 1;

    // ---- Foreign key ---------------------------------------------------
    if ($academicYearId === false || $academicYearId < 1) {
        $errors[] = 'An academic year must be selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM academic_years WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $academicYearId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected academic year does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the academic year. Please try again.';
        }
    }

    // ---- Title ----------------------------------------------------------
    if ($title === '') {
        $errors[] = 'Title is required.';
    } elseif (mb_strlen($title) > 255) {
        $errors[] = 'Title must be 255 characters or fewer.';
    }

    // ---- Optional document ----------------------------------------------
    $ucsDocument = [
        'document_title' => null,
        'document_path'  => null,
        'document_type'  => null,
    ];

    if (isset($_FILES['document']) && is_array($_FILES['document'])) {
        $ucsHasFile = isset($_FILES['document']['error'])
            && (int) $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE;
    } else {
        $ucsHasFile = false;
    }

    if ($ucsHasFile) {
        $ucsDocumentTitle = trim((string) ($input['document_title'] ?? ''));
        if ($ucsDocumentTitle === '') {
            $errors[] = 'A document title is required when uploading a document.';
        } elseif (mb_strlen($ucsDocumentTitle) > 255) {
            $errors[] = 'Document title must be 255 characters or fewer.';
        }

        try {
            $ucsDocumentPath = ucs_handle_upload('document', ['pdf', 'doc', 'docx'], ADMISSION_DOCUMENT_MAX_BYTES);
        } catch (RuntimeException $e) {
            $ucsDocumentPath = null;
            $errors[] = $e->getMessage();
        }

        if ($ucsDocumentPath !== null) {
            $ucsDocument = [
                'document_title' => $ucsDocumentTitle,
                'document_path'  => $ucsDocumentPath,
                'document_type'  => strtoupper(pathinfo($ucsDocumentPath, PATHINFO_EXTENSION)),
            ];
        }
    }

    if (!in_array($status, [0, 1, '0', '1'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'academic_year_id' => $academicYearId,
            'title'            => $title,
            'description'      => $description,
            'requirements'     => $requirements,
            'important_dates'  => $importantDates,
            'application_info' => $applicationInfo,
            'status'           => in_array($status, [1, '1'], true) ? 1 : 0,
            'document_title'   => $ucsDocument['document_title'],
            'document_path'    => $ucsDocument['document_path'],
            'document_type'    => $ucsDocument['document_type'],
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the Admissions module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function admission_flash($type, $message)
{
    $_SESSION['admission_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}