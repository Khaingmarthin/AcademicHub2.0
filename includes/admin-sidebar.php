<?php
/**
 * Admin sidebar.
 * Clean, professional, modern design with visible active state.
 */
require_once __DIR__ . '/admin-nav.php';
$ucsAdminNavItems = $ucsAdminNavItems ?? [];
$ucsActiveNav      = $activeNav ?? '';
$ucsLogoUrl        = ROOT_URL . '/assets/images/logo.png';

$ucsMainNav   = [];
$ucsLogoutNav = null;
foreach ($ucsAdminNavItems as $ucsNavItem) {
    if (($ucsNavItem['key'] ?? '') === 'logout') {
        $ucsLogoutNav = $ucsNavItem;
    } else {
        $ucsMainNav[] = $ucsNavItem;
    }
}
?>
<aside id="admin-sidebar" class="fixed left-0 top-0 z-40 h-full w-[240px] -translate-x-full transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:w-[240px] lg:shrink-0 lg:translate-x-0">
    <div class="flex h-full flex-col bg-white border-r border-slate-200/80">
        <!-- Brand Area -->
        <div class="flex h-[64px] shrink-0 items-center justify-center gap-3 px-5 border-b border-slate-100">
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/dashboard.php'); ?>" class="flex items-center gap-3 min-w-0 transition-opacity hover:opacity-80">
                <img src="<?php echo htmlspecialchars($ucsLogoUrl); ?>" alt="" class="h-10 w-10 shrink-0 object-contain rounded-lg" aria-hidden="true">
                <span class="min-w-0 flex flex-col justify-center">
                    <span class="block text-[15px] font-bold tracking-wide text-slate-800 leading-tight">UCSMTLA</span>
                    <span class="block text-[9px] font-semibold uppercase tracking-[0.15em] text-slate-400 mt-0.5">Admin Panel</span>
                </span>
            </a>
            <button type="button" id="admin-sidebar-close" class="absolute right-3 top-1/2 -translate-y-1/2 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 lg:hidden focus:outline-none" aria-label="Close menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Main Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 [&::-webkit-scrollbar]:hidden" style="scrollbar-width: none; -ms-overflow-style: none;" aria-label="Admin navigation">
            <ul class="space-y-1">
                <?php foreach ($ucsMainNav as $ucsNavItem): ?>
                    <?php
                    $ucsNavIsActive = $ucsActiveNav === $ucsNavItem['key'];
                    ?>
                    <li>
                        <a href="<?php echo htmlspecialchars(ROOT_URL . $ucsNavItem['url']); ?>" <?php echo $ucsNavIsActive ? 'aria-current="page"' : ''; ?> class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-[14px] font-medium transition-all duration-150 focus:outline-none <?php echo $ucsNavIsActive ? $ucsNavItem['color']['activeBg'] . ' ' . $ucsNavItem['color']['activeText'] . ' shadow-sm ring-1 ' . $ucsNavItem['color']['activeRing'] : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg <?php echo $ucsNavIsActive ? $ucsNavItem['color']['activeBg'] . ' ' . $ucsNavItem['color']['activeText'] : $ucsNavItem['color']['iconBg'] . ' ' . $ucsNavItem['color']['iconText']; ?> transition-colors duration-150" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[16px] w-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <?php echo $ucsNavItem['icon']; ?>
                                </svg>
                            </span>
                            <span class="truncate"><?php echo htmlspecialchars($ucsNavItem['label']); ?></span>
                            <?php if ($ucsNavIsActive): ?>
                                <span class="ml-auto h-1.5 w-1.5 rounded-full <?php echo $ucsNavItem['color']['activeDot']; ?>" aria-hidden="true"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <!-- Logout Area -->
        <?php if ($ucsLogoutNav !== null): ?>
            <div class="shrink-0 px-3 py-3 mt-auto border-t border-slate-100">
                <a href="<?php echo htmlspecialchars(ROOT_URL . $ucsLogoutNav['url']); ?>" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-[14px] font-medium text-slate-500 transition-all duration-150 hover:bg-red-50 hover:text-red-600 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0 text-slate-400 group-hover:text-red-500 transition-colors duration-150" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span class="truncate"><?php echo htmlspecialchars($ucsLogoutNav['label']); ?></span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</aside>
