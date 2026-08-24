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

$ucsAdminNavItems = $ucsAdminNavItems ?? [];
$ucsActiveNav      = $activeNav ?? '';
$pageTitle         = (string) ($pageTitle ?? 'Admin');

// Build the breadcrumb ("Admin / <Item>") from the active nav item.
$adminBreadcrumb      = 'Admin';
$ucsAdminCurrentLabel = '';
foreach ($ucsAdminNavItems as $ucsNavItem) {
    if ($ucsNavItem['key'] === $ucsActiveNav) {
        $ucsAdminCurrentLabel = $ucsNavItem['label'];
        break;
    }
}
if ($ucsAdminCurrentLabel !== '') {
    $adminBreadcrumb = 'Admin / ' . $ucsAdminCurrentLabel;
}

$pageSubtitle = (string) ($pageSubtitle ?? '');
$hidePageHeader = (bool) ($hidePageHeader ?? false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(ROOT_URL); ?>/assets/css/style.css">
</head>
<body class="min-h-screen bg-[#f5f6fa] font-sans text-slate-900 antialiased">
    <!-- Mobile drawer backdrop -->
    <div id="admin-sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

    <div class="min-h-screen lg:flex">
        <?php include __DIR__ . '/admin-sidebar.php'; ?>

        <div class="flex min-w-0 flex-1 flex-col" style="overflow-x: clip;">
            <?php include __DIR__ . '/admin-header.php'; ?>

            <main class="relative flex-1 p-4 sm:p-5 lg:p-6" style="z-index: 0;">
                <div class="mx-auto w-full max-w-7xl">
                    <?php if (!$hidePageHeader): ?>
                    <!-- Page header -->
                    <header class="mb-5">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400"><?php echo htmlspecialchars($adminBreadcrumb); ?></p>
                        <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                            <?php echo htmlspecialchars($pageTitle); ?>
                        </h1>
                        <?php if ($pageSubtitle !== ''): ?>
                            <p class="mt-0.5 text-sm text-slate-500"><?php echo htmlspecialchars($pageSubtitle); ?></p>
                        <?php endif; ?>
                    </header>
                    <?php endif; ?>
