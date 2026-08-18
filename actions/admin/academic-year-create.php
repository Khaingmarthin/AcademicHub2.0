<?php
/**
 * Admin Academic Years - Create handler.
 *
 * Validates the submitted academic year and inserts it. When the new year is
 * set to Active, the previously active academic year is archived inside the
 * same transaction so there is always exactly one Active academic year.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/academic-year-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/academic-years/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/academic-years/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    academic_year_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = academic_year_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['academic_year_errors'] = $ucsErrors;
    $_SESSION['academic_year_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsYearName  = $ucsClean['year_name'];
    $ucsStartDate = $ucsClean['start_date'];
    $ucsEndDate   = $ucsClean['end_date'];

    if ($ucsClean['status'] === 'Active') {
        // Archive any current Active year first so the one-active rule holds.
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE academic_years
                 SET status = 'Archived'
                 WHERE status = 'Active'"
            )->execute();

            $ucsStmt = $pdo->prepare(
                "INSERT INTO academic_years (year_name, start_date, end_date, status)
                 VALUES (:year_name, :start_date, :end_date, 'Active')"
            );
            $ucsStmt->execute([
                ':year_name'  => $ucsYearName,
                ':start_date' => $ucsStartDate,
                ':end_date'   => $ucsEndDate,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    } else {
        $ucsStmt = $pdo->prepare(
            "INSERT INTO academic_years (year_name, start_date, end_date, status)
             VALUES (:year_name, :start_date, :end_date, :status)"
        );
        $ucsStmt->execute([
            ':year_name'  => $ucsYearName,
            ':start_date' => $ucsStartDate,
            ':end_date'   => $ucsEndDate,
            ':status'     => $ucsClean['status'],
        ]);
    }

    academic_year_flash('success', 'Academic year ' . $ucsYearName . ' created successfully.');
} catch (PDOException $e) {
    // A unique-constraint failure means the label was inserted by someone else
    // between the pre-check and the insert.
    if ((string) $e->getCode() === '23000') {
        academic_year_flash('error', 'An academic year with this label already exists.');
    } else {
        academic_year_flash('error', 'Unable to create the academic year. Please try again.');
    }
} catch (Throwable $e) {
    academic_year_flash('error', 'Unable to create the academic year. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;
