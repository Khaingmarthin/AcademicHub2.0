<?php
/**
 * Admin Academic Years - Update handler.
 *
 * Validates the edited academic year and updates it. When the year is changed
 * to Active, the previously active academic year is archived inside the same
 * transaction so there is always exactly one Active academic year.
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
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsYearId === false || $ucsYearId < 1) {
    academic_year_flash('error', 'Academic year not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = academic_year_validate_input($_POST, $pdo, $ucsYearId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['academic_year_errors'] = $ucsErrors;
    $_SESSION['academic_year_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/academic-years/edit.php?id=' . $ucsYearId);
    exit;
}

try {
    $ucsWasActive      = $ucsExisting['status'] === 'Active';
    $ucsBecomingActive = $ucsClean['status'] === 'Active';

    if ($ucsBecomingActive && !$ucsWasActive) {
        // Activating a previously non-active year: archive any other Active year.
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE academic_years
                 SET status = 'Archived'
                 WHERE status = 'Active' AND id != :id"
            )->execute([':id' => $ucsYearId]);

            $ucsStmt = $pdo->prepare(
                "UPDATE academic_years
                 SET year_name = :year_name,
                     start_date = :start_date,
                     end_date = :end_date,
                     status = 'Active'
                 WHERE id = :id"
            );
            $ucsStmt->execute([
                ':year_name'  => $ucsClean['year_name'],
                ':start_date' => $ucsClean['start_date'],
                ':end_date'   => $ucsClean['end_date'],
                ':id'         => $ucsYearId,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    } else {
        $ucsStmt = $pdo->prepare(
            "UPDATE academic_years
             SET year_name = :year_name,
                 start_date = :start_date,
                 end_date = :end_date,
                 status = :status
             WHERE id = :id"
        );
        $ucsStmt->execute([
            ':year_name'  => $ucsClean['year_name'],
            ':start_date' => $ucsClean['start_date'],
            ':end_date'   => $ucsClean['end_date'],
            ':status'     => $ucsClean['status'],
            ':id'         => $ucsYearId,
        ]);
    }

    academic_year_flash('success', 'Academic year ' . $ucsClean['year_name'] . ' updated successfully.');
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        academic_year_flash('error', 'An academic year with this label already exists.');
    } else {
        academic_year_flash('error', 'Unable to update the academic year. Please try again.');
    }
} catch (Throwable $e) {
    academic_year_flash('error', 'Unable to update the academic year. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
