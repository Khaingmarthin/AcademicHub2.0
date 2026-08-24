<?php
/**
 * Admin Academic Years - Delete handler.
 *
 * Permanently removes an academic year, but only when it is safe to do so:
 * the Active year can never be deleted, and a year that still has related
 * courses or classrooms is refused (the schema uses ON DELETE RESTRICT).
 * In those cases the administrator is told to archive the year instead.
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
    academic_year_flash(
        'error',
        'Academic year ' . $ucsYear['year_name'] . ' is the Active year and cannot be deleted. Archive it or activate a different year first.'
    );
    header('Location: ' . $ucsReturnUrl);
    exit;
}

// A year with dependent records cannot be deleted (ON DELETE RESTRICT).
$ucsDependentCount = 0;
try {
    foreach (['courses', 'classrooms'] as $ucsTable) {
        $ucsStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM `$ucsTable` WHERE academic_year_id = :id"
        );
        $ucsStmt->execute([':id' => $ucsYearId]);
        $ucsDependentCount += (int) $ucsStmt->fetchColumn();
    }
} catch (PDOException $e) {
    $ucsDependentCount = 1; // Be conservative: block the delete.
}

if ($ucsDependentCount > 0) {
    academic_year_flash(
        'error',
        'Academic year ' . $ucsYear['year_name'] . ' has linked courses or classrooms and cannot be deleted. Archive it instead.'
    );
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $pdo->prepare(
        "DELETE FROM academic_years WHERE id = :id"
    )->execute([':id' => $ucsYearId]);

    academic_year_flash('success', 'Academic year ' . $ucsYear['year_name'] . ' deleted successfully.');
} catch (PDOException $e) {
    academic_year_flash(
        'error',
        'Academic year ' . $ucsYear['year_name'] . ' cannot be deleted because it still has related records. Archive it instead.'
    );
} catch (Throwable $e) {
    academic_year_flash('error', 'Unable to delete the academic year. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
