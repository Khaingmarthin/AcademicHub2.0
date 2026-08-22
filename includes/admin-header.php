<?php
/**
 * Admin topbar.
 *
 * Compact white topbar for the admin panel. Contains mobile menu toggle,
 * active academic year badge, and admin profile dropdown.
 */
$ucsAdminUser       = function_exists('admin_current_user') ? admin_current_user() : null;
$ucsAvatarInitial   = 'A';
$ucsAdminName       = '';
$ucsAdminRole       = 'Administrator';
if ($ucsAdminUser !== null) {
    $ucsAdminName = (string) $ucsAdminUser['name'];
    $ucsInitial   = strtoupper(substr(trim($ucsAdminName), 0, 1));
    $ucsAvatarInitial = $ucsInitial !== '' ? $ucsInitial : 'A';
}

$ucsHeaderActiveYear = null;
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $ucsYearStmt = $pdo->query(
            "SELECT year_name FROM academic_years
             WHERE status = 'Active'
             ORDER BY start_date DESC, id DESC
             LIMIT 1"
        );
        $ucsYearRow = $ucsYearStmt->fetch();
        $ucsHeaderActiveYear = $ucsYearRow['year_name'] ?? null;
    } catch (PDOException $e) {
        $ucsHeaderActiveYear = null;
    }
}
?>
<header class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-5">
    <!-- Mobile menu toggle -->
    <button type="button" id="admin-sidebar-toggle" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-slate-500 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700 lg:hidden focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Open menu" aria-expanded="false" aria-controls="admin-sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M3 6h18M3 12h18M3 18h18"></path>
        </svg>
    </button>

    <!-- Title + active academic year -->
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <p class="truncate text-sm font-semibold text-slate-800">
            UCSMTLA Academic Hub
        </p>
        <?php if ($ucsHeaderActiveYear !== null && $ucsHeaderActiveYear !== ''): ?>
            <span class="hidden shrink-0 items-center gap-1.5 rounded-md bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 sm:inline-flex">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <?php echo htmlspecialchars($ucsHeaderActiveYear); ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- Right actions -->
    <div class="flex shrink-0 items-center gap-2">
        <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="hidden items-center gap-1.5 rounded-md px-2.5 py-1.5 text-[13px] font-medium text-slate-500 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700 sm:inline-flex focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            View Site
        </a>

        <?php if ($ucsAdminUser !== null): ?>
            <div class="relative" data-admin-profile>
                <button type="button" data-admin-profile-toggle class="flex items-center gap-2 rounded-md py-1 pl-1 pr-2 transition-colors duration-150 hover:bg-slate-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-haspopup="true" aria-expanded="false">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-blue-600 text-[11px] font-bold text-white" aria-hidden="true">
                        <?php echo htmlspecialchars($ucsAvatarInitial); ?>
                    </span>
                    <span class="hidden max-w-[8rem] text-left md:block">
                        <span class="block truncate text-[13px] font-semibold text-slate-700"><?php echo htmlspecialchars($ucsAdminName); ?></span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 transition-transform duration-150" data-admin-profile-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </button>
                <div data-admin-profile-menu class="invisible absolute right-0 top-full z-50 mt-1.5 w-52 origin-top-right rounded-lg border border-slate-200 bg-white p-1.5 opacity-0 shadow-lg shadow-slate-900/5 transition-all duration-150" role="menu">
                    <div class="border-b border-slate-100 px-3 py-2">
                        <p class="truncate text-[13px] font-semibold text-slate-800"><?php echo htmlspecialchars($ucsAdminName); ?></p>
                        <p class="truncate text-[11px] text-slate-400"><?php echo htmlspecialchars($ucsAdminUser['email'] ?? ''); ?></p>
                    </div>
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/profile.php'); ?>" class="mt-1 flex items-center gap-2 rounded-md px-3 py-2 text-[13px] font-medium text-slate-600 transition-colors duration-150 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" role="menuitem">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="8" r="5"></circle>
                            <path d="M20 21a8 8 0 1 0-16 0"></path>
                        </svg>
                        Profile
                    </a>
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/logout.php'); ?>" class="flex items-center gap-2 rounded-md px-3 py-2 text-[13px] font-medium text-red-600 transition-colors duration-150 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600" role="menuitem">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>
