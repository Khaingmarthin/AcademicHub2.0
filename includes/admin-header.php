<?php
/**
 * Admin topbar.
 *
 * Slim sticky app bar shown above the main content column. Contains the
 * mobile menu toggle, the current location breadcrumb, the university link
 * and the logged-in admin's identity.
 *
 * Relies on the following variables set by includes/admin-layout-top.php:
 *   - $adminBreadcrumb : breadcrumb string for the current location
 *   - $pageTitle       : current page title (fallback for the breadcrumb)
 */
$ucsAdminUser       = function_exists('admin_current_user') ? admin_current_user() : null;
$ucsBreadcrumbText  = trim((string) ($adminBreadcrumb ?? '')) !== '' ? $adminBreadcrumb : (isset($pageTitle) ? $pageTitle : 'Admin');
$ucsAvatarInitial   = 'A';
$ucsAdminName       = '';
if ($ucsAdminUser !== null) {
    $ucsAdminName = (string) $ucsAdminUser['name'];
    $ucsInitial   = strtoupper(substr(trim($ucsAdminName), 0, 1));
    $ucsAvatarInitial = $ucsInitial !== '' ? $ucsInitial : 'A';
}
?>
<header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 bg-white/95 px-4 backdrop-blur-sm sm:px-6">
    <!-- Mobile menu toggle -->
    <button type="button" id="admin-sidebar-toggle" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-gray-600 transition-colors duration-150 hover:bg-gray-100 hover:text-gray-900 lg:hidden focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Open menu" aria-expanded="false" aria-controls="admin-sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M3 6h18M3 12h18M3 18h18"></path>
        </svg>
    </button>

    <!-- Current location -->
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-gray-500"><?php echo htmlspecialchars($ucsBreadcrumbText); ?></p>
    </div>

    <!-- Right actions -->
    <div class="flex shrink-0 items-center gap-2 sm:gap-4">
        <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="hidden items-center gap-1.5 text-sm font-medium text-gray-600 transition-colors duration-150 hover:text-blue-700 sm:inline-flex focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            View Site
        </a>

        <?php if ($ucsAdminUser !== null): ?>
            <div class="flex items-center gap-2.5 rounded-full py-1 pl-1 pr-3 ring-1 ring-gray-200">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white" aria-hidden="true">
                    <?php echo htmlspecialchars($ucsAvatarInitial); ?>
                </span>
                <span class="hidden max-w-[10rem] truncate text-sm font-semibold text-gray-700 md:block"><?php echo htmlspecialchars($ucsAdminName); ?></span>
            </div>
        <?php endif; ?>
    </div>
</header>
