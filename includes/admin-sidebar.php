<?php
/**
 * Admin sidebar.
 *
 * Compact professional sidebar for the admin panel. Uses a restrained blue
 * background with clean navigation. The nav list is defined in admin-nav.php.
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
<aside id="admin-sidebar" class="fixed left-0 top-0 z-40 h-full w-60 -translate-x-full transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:w-60 lg:shrink-0 lg:translate-x-0">
    <div class="flex h-full flex-col bg-blue-700">
        <!-- Brand -->
        <div class="flex h-14 shrink-0 items-center gap-2.5 px-4">
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/dashboard.php'); ?>" class="flex min-w-0 items-center gap-2.5">
                <img src="<?php echo htmlspecialchars($ucsLogoUrl); ?>" alt="" class="h-8 w-8 shrink-0 object-contain" aria-hidden="true">
                <span class="min-w-0 leading-tight">
                    <span class="block text-[13px] font-bold tracking-tight text-white">UCSMTLA</span>
                    <span class="block text-[9px] font-semibold uppercase tracking-[0.18em] text-blue-200">Admin Panel</span>
                </span>
            </a>
            <button type="button" id="admin-sidebar-close" class="ml-auto inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-blue-200 transition-colors hover:bg-white/15 hover:text-white lg:hidden focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" aria-label="Close menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-2.5 py-3" aria-label="Admin navigation">
            <ul class="space-y-0.5">
                <?php foreach ($ucsMainNav as $ucsNavItem): ?>
                    <?php
                    $ucsNavIsActive = $ucsActiveNav === $ucsNavItem['key'];
                    $ucsNavLinkClass = 'flex items-center gap-2.5 rounded-md px-3 py-2 text-[13px] font-medium transition-colors duration-150 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white';
                    if ($ucsNavIsActive) {
                        $ucsNavLinkClass .= ' bg-white/15 text-white';
                    } else {
                        $ucsNavLinkClass .= ' text-blue-100 hover:bg-white/10 hover:text-white';
                    }
                    ?>
                    <li>
                        <a href="<?php echo htmlspecialchars(ROOT_URL . $ucsNavItem['url']); ?>" <?php echo $ucsNavIsActive ? 'aria-current="page"' : ''; ?> class="<?php echo $ucsNavLinkClass; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <?php echo $ucsNavItem['icon']; ?>
                            </svg>
                            <?php echo htmlspecialchars($ucsNavItem['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <!-- Logout (separated at bottom) -->
        <?php if ($ucsLogoutNav !== null): ?>
            <div class="shrink-0 border-t border-white/15 px-2.5 py-2">
                <a href="<?php echo htmlspecialchars(ROOT_URL . $ucsLogoutNav['url']); ?>" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-[13px] font-medium text-blue-200 transition-colors duration-150 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <?php echo $ucsLogoutNav['icon']; ?>
                    </svg>
                    <?php echo htmlspecialchars($ucsLogoutNav['label']); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</aside>
