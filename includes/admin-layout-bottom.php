<?php
/**
 * Admin layout - bottom shell.
 *
 * Closes the main content column opened by includes/admin-layout-top.php,
 * renders the admin footer, the mobile sidebar behaviour script and the
 * admin profile dropdown script.
 */
?>
                </div>
            </main>

            <footer class="shrink-0 border-t border-slate-200 bg-white px-4 py-3 sm:px-5">
                <div class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-1 text-[11px] text-slate-400 sm:flex-row">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(APP_NAME); ?> &middot; Admin Panel</p>
                    <p>UCSMTLA University Administration</p>
                </div>
            </footer>
        </div>
    </div>

    <script>
        (function () {
            'use strict';
            var sidebar = document.getElementById('admin-sidebar');
            var toggle = document.getElementById('admin-sidebar-toggle');
            var closeBtn = document.getElementById('admin-sidebar-close');
            var backdrop = document.getElementById('admin-sidebar-backdrop');

            if (!sidebar) return;

            function setOpen(open) {
                sidebar.style.transform = open ? 'translateX(0)' : '';
                if (backdrop) backdrop.classList.toggle('hidden', !open);
                document.body.classList.toggle('overflow-hidden', open);
                if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    setOpen(sidebar.style.transform !== 'translateX(0)');
                });
            }
            if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
            if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') setOpen(false);
            });

            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.matchMedia('(max-width: 1023px)').matches) {
                        setOpen(false);
                    }
                });
            });

            // Admin profile dropdown
            var profileRoot = document.querySelector('[data-admin-profile]');
            if (profileRoot) {
                var profileToggle = profileRoot.querySelector('[data-admin-profile-toggle]');
                var profileMenu = profileRoot.querySelector('[data-admin-profile-menu]');
                var profileChevron = profileRoot.querySelector('[data-admin-profile-chevron]');

                function setProfileOpen(open) {
                    if (!profileMenu) return;
                    profileMenu.classList.toggle('invisible', !open);
                    profileMenu.classList.toggle('opacity-0', !open);
                    profileMenu.classList.toggle('opacity-100', open);
                    if (profileToggle) {
                        profileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    }
                    if (profileChevron) {
                        profileChevron.classList.toggle('rotate-180', open);
                    }
                }

                if (profileToggle) {
                    profileToggle.addEventListener('click', function (e) {
                        e.stopPropagation();
                        setProfileOpen(profileMenu.classList.contains('invisible'));
                    });
                }

                document.addEventListener('click', function (e) {
                    if (!profileRoot.contains(e.target)) {
                        setProfileOpen(false);
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') setProfileOpen(false);
                });
            }
        })();
    </script>
</body>
</html>
