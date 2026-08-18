<?php
/**
 * Admin layout - top shell.
 *
 * Opens the document, renders the sidebar + topbar and the main content
 * column with a page header. The page file must:
 *
 *   1. require config/app.php + includes/auth.php and call admin_require_login();
 *   2. set $pageTitle (and optionally $pageSubtitle and $activeNav);
 *   3. require_once this file;
 *   4. output the page content;
 *   5. require_once includes/admin-layout-bottom.php.
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin-nav.php';

$ucsAdminNavGroups = $ucsAdminNavGroups ?? [];
$ucsActiveNav      = $activeNav ?? '';
$pageTitle         = (string) ($pageTitle ?? 'Admin');

// Build the breadcrumb ("Admin / <Group>") from the active nav item.
$adminBreadcrumb    = 'Admin';
$ucsAdminCurrentLabel = '';
foreach ($ucsAdminNavGroups as $ucsNavGroup) {
    foreach ($ucsNavGroup['items'] as $ucsNavItem) {
        if ($ucsNavItem['key'] === $ucsActiveNav) {
            $adminBreadcrumb = 'Admin / ' . $ucsNavGroup['label'];
            $ucsAdminCurrentLabel = $ucsNavItem['label'];
            break 2;
        }
    }
}

$pageSubtitle = (string) ($pageSubtitle ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(ROOT_URL); ?>/assets/css/style.css">
</head>
<body class="min-h-screen bg-gray-100 font-sans text-gray-900 antialiased">
    <!-- Mobile drawer backdrop -->
    <div id="admin-sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-gray-900/50 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

    <div class="min-h-screen lg:flex">
        <?php include __DIR__ . '/admin-sidebar.php'; ?>

        <div class="flex min-w-0 flex-1 flex-col">
            <?php include __DIR__ . '/admin-header.php'; ?>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <div class="mx-auto w-full max-w-7xl">
                    <!-- Page header -->
                    <header class="mb-6">
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                            <?php echo htmlspecialchars($pageTitle); ?>
                        </h1>
                        <?php if ($pageSubtitle !== ''): ?>
                            <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($pageSubtitle); ?></p>
                        <?php endif; ?>
                    </header>
