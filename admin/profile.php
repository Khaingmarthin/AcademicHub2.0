<?php
/**
 * Admin Profile (placeholder).
 *
 * Lets the logged-in admin view and later edit their own account details.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

admin_require_login();

$pageTitle    = 'Profile';
$pageSubtitle = 'Manage your admin account details.';
$activeNav    = 'dashboard';

require_once __DIR__ . '/../includes/admin-layout-top.php';
?>
<div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="8" r="5"></circle>
        <path d="M20 21a8 8 0 1 0-16 0"></path>
    </svg>
    <h3 class="mt-4 text-lg font-semibold text-gray-800">Profile</h3>
    <p class="mt-2 text-sm text-gray-500">
        Admin profile settings will appear here.
    </p>
</div>
<?php require_once __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
