<?php
/**
 * Admin topbar.
 * Clean, professional header with View Site button and profile dropdown.
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

$ucsCurrentTime = date('h:i:s A');
$ucsCurrentDate = date('D, d M Y');
?>
<header class="sticky top-0 z-20 flex h-[64px] shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
    <!-- Left: Mobile toggle + Page Title -->
    <div class="flex items-center gap-4">
        <button type="button" id="admin-sidebar-toggle" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-500 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700 lg:hidden focus:outline-none" aria-label="Open menu" aria-expanded="false" aria-controls="admin-sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M3 6h18M3 12h18M3 18h18"></path>
            </svg>
        </button>
        <h1 class="text-lg font-bold tracking-tight text-slate-800 sm:text-xl">UCSMTLA Academic Hub</h1>
    </div>

    <!-- Right: View Site, Badge, Time, Profile -->
    <div class="flex items-center gap-3">
        <!-- View Site Button -->
        <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" target="_blank" class="hidden sm:inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2.5 text-[12px] font-semibold text-white shadow-sm shadow-blue-600/20 transition-all duration-200 hover:from-blue-700 hover:to-indigo-700 hover:shadow-md hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline>
                <line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
            View Site
        </a>

        <!-- Divider -->
        <div class="hidden sm:block h-8 w-px bg-slate-200"></div>

        <!-- Active Academic Year -->
        <?php if ($ucsHeaderActiveYear !== null && $ucsHeaderActiveYear !== ''): ?>
            <span class="hidden lg:inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-[11px] font-medium text-emerald-700">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                <?php echo htmlspecialchars($ucsHeaderActiveYear); ?> (Active)
            </span>
        <?php endif; ?>

        <!-- Divider -->
        <div class="hidden sm:block h-8 w-px bg-slate-200"></div>

        <!-- Time -->
        <div class="hidden sm:flex flex-col items-end">
            <span class="text-xs font-semibold text-slate-700"><?php echo htmlspecialchars($ucsCurrentTime); ?></span>
            <span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($ucsCurrentDate); ?></span>
        </div>

        <!-- Divider -->
        <div class="hidden sm:block h-8 w-px bg-slate-200"></div>

        <!-- Profile -->
        <?php if ($ucsAdminUser !== null): ?>
            <div class="relative" data-admin-profile>
                <button type="button" data-admin-profile-toggle class="flex items-center gap-2.5 rounded-xl py-1.5 pl-1.5 pr-2 transition-colors duration-150 hover:bg-slate-100 focus:outline-none" aria-haspopup="true" aria-expanded="false">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-[12px] font-bold text-white" aria-hidden="true">
                        <?php echo htmlspecialchars($ucsAvatarInitial); ?>
                    </span>
                    <span class="hidden text-left md:block">
                        <span class="block text-[12px] font-semibold text-slate-700 leading-tight"><?php echo htmlspecialchars($ucsAdminName); ?></span>
                        <span class="block text-[10px] text-slate-400 leading-tight"><?php echo htmlspecialchars($ucsAdminRole); ?></span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform duration-150" data-admin-profile-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </button>
                <div data-admin-profile-menu class="invisible absolute right-0 top-full z-50 mt-2 w-52 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 opacity-0 shadow-xl shadow-slate-900/10 transition-all duration-150" role="menu">
                    <div class="border-b border-slate-100 px-3 py-2.5 mb-1">
                        <p class="truncate text-[12px] font-semibold text-slate-800"><?php echo htmlspecialchars($ucsAdminName); ?></p>
                        <p class="truncate text-[10px] text-slate-400"><?php echo htmlspecialchars($ucsAdminUser['email'] ?? ''); ?></p>
                    </div>
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/profile.php'); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-[12px] font-medium text-slate-600 transition-colors duration-150 hover:bg-slate-50 hover:text-slate-900 focus:outline-none" role="menuitem">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="8" r="5"></circle>
                            <path d="M20 21a8 8 0 1 0-16 0"></path>
                        </svg>
                        Profile
                    </a>
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/logout.php'); ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-[12px] font-medium text-red-500 transition-colors duration-150 hover:bg-red-50 hover:text-red-600 focus:outline-none" role="menuitem">
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
