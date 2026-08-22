<?php
/**
 * Admin sidebar.
 *
 * Professional, modern university admin sidebar.
 * Uses a solid blue background with clean navigation and active pill state.
 *
 * Responsive behaviour:
 *  - Mobile: fixed off-canvas drawer
 *  - Desktop (lg): sticky full-height column
 */
require_once __DIR__ . '/admin-nav.php';
$ucsAdminNavItems = $ucsAdminNavItems ?? [];
$ucsActiveNav      = $activeNav ?? '';
$ucsLogoUrl        = ROOT_URL . '/assets/images/logo.png';

// Separate logout from main nav items.
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
    <div class="flex h-full flex-col bg-[#2563EB]">
        <!-- Brand Area -->
        <div class="flex h-[76px] shrink-0 items-center justify-between px-5 mb-2">
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/dashboard.php'); ?>" class="flex items-center gap-3 min-w-0 transition-opacity hover:opacity-90">
                <img src="<?php echo htmlspecialchars($ucsLogoUrl); ?>" alt="" class="h-10 w-10 shrink-0 object-contain rounded-md bg-white/10 p-1.5" aria-hidden="true">
                <span class="min-w-0 flex flex-col justify-center">
                    <span class="block text-[15px] font-bold tracking-wide text-white leading-tight">UCSMTLA</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-[0.2em] text-blue-200 mt-0.5">Admin Panel</span>
                </span>
            </a>
            <button type="button" id="admin-sidebar-close" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-blue-200 transition-colors hover:bg-white/15 hover:text-white lg:hidden focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" aria-label="Close menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Main Navigation -->
        <nav class="flex-1 overflow-y-auto px-4 py-2 [&::-webkit-scrollbar]:hidden" style="scrollbar-width: none; -ms-overflow-style: none;" aria-label="Admin navigation">
            <ul class="space-y-1.5">
                <?php foreach ($ucsMainNav as $ucsNavItem): ?>
                    <?php
                    $ucsNavIsActive = $ucsActiveNav === $ucsNavItem['key'];
                    $ucsNavLinkClass = 'group flex items-center gap-3.5 rounded-lg px-3 py-2.5 text-[14px] font-medium transition-all duration-200 focus:outline-none';
                    if ($ucsNavIsActive) {
                        $ucsNavLinkClass .= ' bg-white text-[#2563EB] shadow-sm';
                    } else {
                        $ucsNavLinkClass .= ' text-blue-50/90 hover:bg-white/15 hover:text-white';
                    }
                    ?>
                    <li>
                        <a href="<?php echo htmlspecialchars(ROOT_URL . $ucsNavItem['url']); ?>" <?php echo $ucsNavIsActive ? 'aria-current="page"' : ''; ?> class="<?php echo $ucsNavLinkClass; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0 <?php echo $ucsNavIsActive ? 'text-[#2563EB]' : 'text-blue-200/90 group-hover:text-white'; ?> transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <?php echo $ucsNavItem['icon']; ?>
                            </svg>
                            <span class="truncate"><?php echo htmlspecialchars($ucsNavItem['label']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <!-- Logout Area -->
        <?php if ($ucsLogoutNav !== null): ?>
            <div class="shrink-0 px-4 py-5 mt-auto border-t border-white/10">
                <a href="<?php echo htmlspecialchars(ROOT_URL . $ucsLogoutNav['url']); ?>" class="group flex items-center justify-center gap-2.5 rounded-xl px-4 py-2.5 text-[14px] font-semibold text-red-400 bg-red-400/10 border border-red-400/20 transition-all duration-200 hover:bg-red-500 hover:text-white hover:shadow-lg hover:shadow-red-500/20 hover:border-red-500 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 transition-transform duration-200 group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <?php echo $ucsLogoutNav['icon']; ?>
                    </svg>
                    <span class="truncate"><?php echo htmlspecialchars($ucsLogoutNav['label']); ?></span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</aside>
