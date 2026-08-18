<?php
/**
 * Admin Academic Years - Archive handler.
 *
 * Moves the selected academic year to the Archived state (the deactivated,
 * kept-for-reference state in the schema). Archiving the currently Active
 * year is allowed but leaves the system without an Active year until another
 * one is activated.
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

if ($ucsYear['status'] === 'Archived') {
    academic_year_flash('success', 'Academic year ' . $ucsYear['year_name'] . ' is already archived.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare(
        "UPDATE academic_years
         SET status = 'Archived'
         WHERE id = :id"
    )->execute([':id' => $ucsYearId]);

    if ($ucsYear['status'] === 'Active') {
        academic_year_flash(
            'success',
            'Academic year ' . $ucsYear['year_name'] . ' has been archived. There is no longer an Active academic year — activate another one to continue.'
        );
    } else {
        academic_year_flash('success', 'Academic year ' . $ucsYear['year_name'] . ' has been archived.');
    }
} catch (Throwable $e) {
    academic_year_flash('error', 'Unable to archive the academic year. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
