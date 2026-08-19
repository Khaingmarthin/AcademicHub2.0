<?php
/**
 * Admin Career Discussions - Delete category (only when unused).
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/discussions/categories/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsCategoryId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($ucsCategoryId === false || $ucsCategoryId < 1) {
    discussion_flash('error', 'That category could not be found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare("SELECT name FROM discussion_categories WHERE id = :id LIMIT 1");
    $ucsStmt->execute([':id' => $ucsCategoryId]);
    $ucsCategoryName = $ucsStmt->fetchColumn();

    if ($ucsCategoryName === false) {
        discussion_flash('error', 'That category could not be found.');
    } else {
        $ucsStmt = $pdo->prepare("SELECT COUNT(*) FROM discussions WHERE category_id = :id");
        $ucsStmt->execute([':id' => $ucsCategoryId]);
        if ((int) $ucsStmt->fetchColumn() > 0) {
            discussion_flash('error', 'This category is in use and cannot be deleted.');
        } else {
            $ucsStmt = $pdo->prepare("DELETE FROM discussion_categories WHERE id = :id");
            $ucsStmt->execute([':id' => $ucsCategoryId]);
            discussion_flash('success', 'Category "' . $ucsCategoryName . '" has been deleted.');
        }
    }
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to delete the category. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;