<?php
/**
 * Admin Academic Years - Activate handler.
 *
 * Makes the selected academic year Active and archives the previously active
 * one inside a single transaction, guaranteeing only one Active year exists.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/academic-year-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/academic-years/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    academic_year_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsYearId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, year_name, status
         FROM academic_years
         WHERE id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsYearId]);
    $ucsYear = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsYear = null;
}

if ($ucsYear === null || $ucsYearId === false || $ucsYearId < 1) {
    academic_year_flash('error', 'Academic year not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

if ($ucsYear['status'] === 'Active') {
    academic_year_flash('success', 'Academic year ' . $ucsYear['year_name'] . ' is already Active.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare(
        "UPDATE academic_years
         SET status = 'Archived'
         WHERE status = 'Active' AND id != :id"
    )->execute([':id' => $ucsYearId]);

    $pdo->prepare(
        "UPDATE academic_years
         SET status = 'Active'
         WHERE id = :id"
    )->execute([':id' => $ucsYearId]);

    $pdo->commit();

    academic_year_flash(
        'success',
        'Academic year ' . $ucsYear['year_name'] . ' is now the Active academic year.'
    );
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    academic_year_flash('error', 'Unable to activate the academic year. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
