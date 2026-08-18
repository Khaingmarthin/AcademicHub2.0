<?php
/**
 * Admin Admissions - Update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/admission-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/admission/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    admission_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, title, document_path FROM admissions WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    admission_flash('error', 'Admission not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = admission_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    ucs_delete_upload($ucsClean['document_path'] ?? null);
    $_SESSION['admission_errors'] = $ucsErrors;
    $_SESSION['admission_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/admission/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsRemoveDocument = isset($_POST['remove_document']) && (string) $_POST['remove_document'] === '1';

    $ucsDocumentTitle = $ucsExisting['document_path'] !== null && !$ucsRemoveDocument
        ? ($ucsClean['document_title'] !== null ? $ucsClean['document_title'] : $ucsExisting['document_title'])
        : null;
    $ucsDocumentPath  = $ucsClean['document_path'] !== null
        ? $ucsClean['document_path']
        : ($ucsRemoveDocument ? null : $ucsExisting['document_path']);
    $ucsDocumentType  = $ucsClean['document_path'] !== null
        ? $ucsClean['document_type']
        : ($ucsRemoveDocument ? null : $ucsExisting['document_type']);

    $ucsStmt = $pdo->prepare(
        "UPDATE admissions
         SET academic_year_id = :academic_year_id,
             title = :title,
             description = :description,
             requirements = :requirements,
             important_dates = :important_dates,
             application_info = :application_info,
             document_title = :document_title,
             document_path = :document_path,
             document_type = :document_type,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':academic_year_id' => $ucsClean['academic_year_id'],
        ':title'            => $ucsClean['title'],
        ':description'      => $ucsClean['description'] !== '' ? $ucsClean['description'] : null,
        ':requirements'     => $ucsClean['requirements'] !== '' ? $ucsClean['requirements'] : null,
        ':important_dates'  => $ucsClean['important_dates'] !== '' ? $ucsClean['important_dates'] : null,
        ':application_info' => $ucsClean['application_info'] !== '' ? $ucsClean['application_info'] : null,
        ':document_title'   => $ucsDocumentTitle,
        ':document_path'    => $ucsDocumentPath,
        ':document_type'    => $ucsDocumentType,
        ':status'           => $ucsClean['status'],
        ':id'               => $ucsId,
    ]);

    if ($ucsClean['document_path'] !== null || $ucsRemoveDocument) {
        ucs_delete_upload($ucsExisting['document_path'] ?? null);
    }

    admission_flash('success', 'Admission "' . $ucsClean['title'] . '" updated successfully.');
} catch (PDOException $e) {
    admission_flash('error', 'Unable to update the admission. Please try again.');
} catch (Throwable $e) {
    admission_flash('error', 'Unable to update the admission. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;