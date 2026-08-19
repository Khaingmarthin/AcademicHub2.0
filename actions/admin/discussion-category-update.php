<?php
/**
 * Admin Career Discussions - Update category.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/discussions/categories/index.php';
$ucsCategoryId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

if ($ucsCategoryId === false || $ucsCategoryId < 1) {
    discussion_flash('error', 'That category could not be found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = discussion_validate_category_input($_POST, $pdo, $ucsCategoryId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['discussion_category_errors'] = $ucsErrors;
    $_SESSION['discussion_category_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/discussions/categories/edit.php?id=' . $ucsCategoryId);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "UPDATE discussion_categories
         SET name = :name, slug = :slug, description = :description,
             sort_order = :sort_order, status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':name'        => $ucsClean['name'],
        ':slug'        => $ucsClean['slug'],
        ':description' => $ucsClean['description'] !== '' ? $ucsClean['description'] : null,
        ':sort_order'  => $ucsClean['sort_order'],
        ':status'      => $ucsClean['status'],
        ':id'          => $ucsCategoryId,
    ]);

    discussion_flash('success', 'Category "' . $ucsClean['name'] . '" updated successfully.');
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to update the category. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;