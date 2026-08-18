<?php
/**
 * Admin layout - bottom shell.
 *
 * Closes the main content column opened by includes/admin-layout-top.php,
 * renders the admin footer and the mobile sidebar behaviour script.
 */
?>
                </div>
            </main>

            <footer class="shrink-0 border-t border-gray-200 bg-white px-4 py-4 sm:px-6">
                <div class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-2 text-xs text-gray-500 sm:flex-row">
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
        })();
    </script>
</body>
</html>
