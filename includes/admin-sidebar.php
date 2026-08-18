<?php
/**
 * Admin sidebar.
 *
 * Professional grouped navigation for the admin panel. Grouped structure is
 * defined in includes/admin-nav.php; this partial only renders it.
 *
 * Responsive behaviour:
 *  - Mobile: fixed off-canvas drawer, slid in by the topbar toggle or
 *    admin-layout-bottom.php script; closed with the X button, backdrop,
 *    Escape or by choosing a link.
 *  - Desktop (lg): sticky full-height column inside the page flex layout.
 */
require_once __DIR__ . '/admin-nav.php';
$ucsAdminNavGroups = $ucsAdminNavGroups ?? [];
$ucsActiveNav      = $activeNav ?? '';
$ucsLogoUrl        = ROOT_URL . '/assets/images/logo.png';
?>
<aside id="admin-sidebar" class="fixed left-0 top-0 z-40 h-full -translate-x-full transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
    <div class="flex h-full flex-col bg-gray-900">
        <!-- Brand -->
        <div class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-gray-800 px-5">
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/dashboard.php'); ?>" class="flex min-w-0 items-center gap-3">
                <img src="<?php echo htmlspecialchars($ucsLogoUrl); ?>" alt="UCSMTLA Academic Hub logo" class="h-9 w-9 shrink-0 object-contain">
                <span class="min-w-0 leading-tight">
                    <span class="block text-base font-bold tracking-tight text-white">UCSMTLA</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-400">Admin Panel</span>
                </span>
            </a>
            <button type="button" id="admin-sidebar-close" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-800 hover:text-white lg:hidden focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500" aria-label="Close menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-5" aria-label="Admin navigation">
            <?php foreach ($ucsAdminNavGroups as $ucsNavGroup): ?>
                <div class="mb-6">
                    <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-500">
                        <?php echo htmlspecialchars($ucsNavGroup['label']); ?>
                    </p>
                    <ul class="space-y-1">
                        <?php foreach ($ucsNavGroup['items'] as $ucsNavItem): ?>
                            <?php
                            $ucsNavIsActive = $ucsActiveNav === $ucsNavItem['key'];
                            $ucsNavLinkClass = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500';
                            if ($ucsNavIsActive) {
                                $ucsNavLinkClass .= ' bg-blue-600 text-white hover:bg-blue-500';
                            } elseif (!empty($ucsNavItem['danger'])) {
                                $ucsNavLinkClass .= ' text-red-400 hover:bg-red-500/10 hover:text-red-300';
                            } else {
                                $ucsNavLinkClass .= ' text-gray-400 hover:bg-gray-800 hover:text-white';
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
                </div>
            <?php endforeach; ?>
        </nav>

        <!-- Footer -->
        <div class="shrink-0 border-t border-gray-800 px-5 py-3 text-[11px] leading-5 text-gray-500">
            &copy; <?php echo date('Y'); ?> UCSMTLA Academic Hub
        </div>
    </div>
</aside>
