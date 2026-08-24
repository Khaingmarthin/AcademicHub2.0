<?php
/**
 * Admin Profile.
 *
 * Shows the logged-in admin's account details.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

admin_require_login();

$pageTitle    = 'Profile';
$pageSubtitle = 'Your admin account details.';
$activeNav    = 'dashboard';

$ucsAdmin = admin_current_user();
$ucsAdminName  = $ucsAdmin['name'] ?? '';
$ucsAdminEmail = $ucsAdmin['email'] ?? '';
$ucsAdminStatus = ($ucsAdmin['status'] ?? 0) ? 'Active' : 'Inactive';
$ucsAdminCreatedAt = !empty($ucsAdmin['created_at']) ? date('d M Y', strtotime((string) $ucsAdmin['created_at'])) : '-';
$ucsAdminUpdatedAt = !empty($ucsAdmin['updated_at']) ? date('d M Y', strtotime((string) $ucsAdmin['updated_at'])) : '-';

$ucsAvatarInitial = strtoupper(substr(trim($ucsAdminName), 0, 1)) ?: 'A';

require_once __DIR__ . '/../includes/admin-layout-top.php';
?>

<div class="mx-auto max-w-2xl">
    <!-- Profile Card -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200/60 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-8 text-center">
            <span class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-white/20 text-2xl font-bold text-white ring-2 ring-white/30" aria-hidden="true">
                <?php echo htmlspecialchars($ucsAvatarInitial); ?>
            </span>
            <h2 class="mt-4 text-xl font-bold text-white"><?php echo htmlspecialchars($ucsAdminName); ?></h2>
            <p class="mt-1 text-sm text-blue-100">System Administrator</p>
        </div>

        <!-- Details -->
        <div class="divide-y divide-slate-100">
            <div class="grid grid-cols-1 sm:grid-cols-2">
                <div class="px-6 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Full Name</p>
                    <p class="mt-1 text-sm font-medium text-slate-800"><?php echo htmlspecialchars($ucsAdminName); ?></p>
                </div>
                <div class="px-6 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Email Address</p>
                    <p class="mt-1 text-sm font-medium text-slate-800"><?php echo htmlspecialchars($ucsAdminEmail); ?></p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2">
                <div class="px-6 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Role</p>
                    <p class="mt-1 text-sm font-medium text-slate-800">System Administrator</p>
                </div>
                <div class="px-6 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</p>
                    <p class="mt-1">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo $ucsAdmin['status'] ?? 0 ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-red-50 text-red-700 ring-1 ring-red-200'; ?>">
                            <span class="inline-block h-1.5 w-1.5 rounded-full <?php echo $ucsAdmin['status'] ?? 0 ? 'bg-emerald-500' : 'bg-red-500'; ?>"></span>
                            <?php echo htmlspecialchars($ucsAdminStatus); ?>
                        </span>
                    </p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2">
                <div class="px-6 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Created</p>
                    <p class="mt-1 text-sm font-medium text-slate-800"><?php echo htmlspecialchars($ucsAdminCreatedAt); ?></p>
                </div>
                <div class="px-6 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Last Updated</p>
                    <p class="mt-1 text-sm font-medium text-slate-800"><?php echo htmlspecialchars($ucsAdminUpdatedAt); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
