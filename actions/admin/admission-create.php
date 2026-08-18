<?php
/**
 * Admin Admissions - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/admission-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/admission/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/admission/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    admission_flash('error', 'Your session has expired. Please try again.');
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
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO admissions
            (academic_year_id, title, description, requirements, important_dates,
             application_info, document_title, document_path, document_type, status)
         VALUES
            (:academic_year_id, :title, :description, :requirements, :important_dates,
             :application_info, :document_title, :document_path, :document_type, :status)"
    );
    $ucsStmt->execute([
        ':academic_year_id' => $ucsClean['academic_year_id'],
        ':title'            => $ucsClean['title'],
        ':description'      => $ucsClean['description'] !== '' ? $ucsClean['description'] : null,
        ':requirements'     => $ucsClean['requirements'] !== '' ? $ucsClean['requirements'] : null,
        ':important_dates'  => $ucsClean['important_dates'] !== '' ? $ucsClean['important_dates'] : null,
        ':application_info' => $ucsClean['application_info'] !== '' ? $ucsClean['application_info'] : null,
        ':document_title'   => $ucsClean['document_title'],
        ':document_path'    => $ucsClean['document_path'],
        ':document_type'    => $ucsClean['document_type'],
        ':status'           => $ucsClean['status'],
    ]);

    admission_flash('success', 'Admission "' . $ucsClean['title'] . '" created successfully.');
} catch (PDOException $e) {
    admission_flash('error', 'Unable to create the admission. Please try again.');
} catch (Throwable $e) {
    admission_flash('error', 'Unable to create the admission. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;