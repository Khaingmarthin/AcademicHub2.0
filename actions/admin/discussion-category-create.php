<?php
/**
 * Admin Career Discussions - Create category.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/discussions/categories/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/discussions/categories/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    discussion_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = discussion_validate_category_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    $_SESSION['discussion_category_errors'] = $ucsErrors;
    $_SESSION['discussion_category_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsStmt = $pdo->prepare(
        "INSERT INTO discussion_categories (name, slug, description, sort_order, status)
         VALUES (:name, :slug, :description, :sort_order, :status)"
    );
    $ucsStmt->execute([
        ':name'        => $ucsClean['name'],
        ':slug'        => $ucsClean['slug'],
        ':description' => $ucsClean['description'] !== '' ? $ucsClean['description'] : null,
        ':sort_order'  => $ucsClean['sort_order'],
        ':status'      => $ucsClean['status'],
    ]);

    discussion_flash('success', 'Category "' . $ucsClean['name'] . '" created successfully.');
} catch (Throwable $e) {
    discussion_flash('error', 'Unable to create the category. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;