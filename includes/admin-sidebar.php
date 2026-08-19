<?php
/**
 * Admin sidebar.
 *
 * Blue gradient sidebar for the admin panel. The flat navigation list is
 * defined in includes/admin-nav.php; this partial only renders it.
 *
 * Responsive behaviour:
 *  - Mobile: fixed off-canvas drawer, slid in by the topbar toggle or
 *    admin-layout-bottom.php script; closed with the X button, backdrop,
 *    Escape or by choosing a link.
 *  - Desktop (lg): sticky full-height column inside the page flex layout.
 */
require_once __DIR__ . '/admin-nav.php';
$ucsAdminNavItems = $ucsAdminNavItems ?? [];
$ucsActiveNav      = $activeNav ?? '';
$ucsLogoUrl        = ROOT_URL . '/assets/images/logo.png';
?>
<aside id="admin-sidebar" class="fixed left-0 top-0 z-40 h-full w-64 -translate-x-full transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:translate-x-0">
    <div class="flex h-full flex-col bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800">
        <!-- Brand -->
        <div class="flex h-16 shrink-0 items-center justify-between gap-3 px-5">
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/dashboard.php'); ?>" class="flex min-w-0 items-center gap-3">
                <img src="<?php echo htmlspecialchars($ucsLogoUrl); ?>" alt="UCSMTLA Academic Hub logo" class="h-10 w-10 shrink-0 object-contain">
                <span class="min-w-0 leading-tight">
                    <span class="block text-base font-extrabold tracking-tight text-white">UCSMTLA</span>
                    <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-blue-300">Admin Panel</span>
                </span>
            </a>
            <button type="button" id="admin-sidebar-close" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-blue-200 transition-colors hover:bg-white/20 hover:text-white lg:hidden focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" aria-label="Close menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Admin navigation">
            <ul class="space-y-1">
                <?php foreach ($ucsAdminNavItems as $ucsNavItem): ?>
                    <?php
                    $ucsNavIsActive = $ucsActiveNav === $ucsNavItem['key'];
                    $ucsNavLinkClass = 'flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white';
                    if ($ucsNavIsActive) {
                        $ucsNavLinkClass .= ' bg-white text-blue-700 shadow-sm hover:bg-blue-50';
                    } else {
                        $ucsNavLinkClass .= ' text-white hover:bg-white/20';
                    }
                    ?>
                    <li>
                        <a href="<?php echo htmlspecialchars(ROOT_URL . $ucsNavItem['url']); ?>" <?php echo $ucsNavIsActive ? 'aria-current="page"' : ''; ?> class="<?php echo $ucsNavLinkClass; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <?php echo $ucsNavItem['icon']; ?>
                            </svg>
                            <?php echo htmlspecialchars($ucsNavItem['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <!-- Footer -->
        <div class="shrink-0 px-5 py-3 text-[11px] leading-5 text-blue-200">
            &copy; <?php echo date('Y'); ?> UCSMTLA Academic Hub
        </div>
    </div>
</aside>