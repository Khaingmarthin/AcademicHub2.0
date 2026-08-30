<?php
/**
 * Admin Reset Password page.
 *
 * Reached via the unique link sent to the admin's email. The token is
 * validated before rendering the form.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$ucsToken    = (string) ($_GET['token'] ?? '');
$ucsValid    = false;
$ucsUserId   = false;
$ucsResetErrors  = $_SESSION['admin_reset_errors'] ?? [];
$ucsResetSuccess = $_SESSION['admin_reset_success'] ?? '';
unset($_SESSION['admin_reset_errors'], $_SESSION['admin_reset_success']);

if ($ucsToken !== '') {
    $ucsUserId = admin_validate_reset_token($ucsToken);
    if ($ucsUserId !== false) {
        $ucsValid = true;
    }
}

$pageTitle = 'Reset Password';
$ucsCsrfToken = admin_csrf_token();
$ucsLogoUrl   = ROOT_URL . '/assets/images/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <meta name="description" content="UCSMTLA Academic Hub - Administrator password reset.">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(ROOT_URL); ?>/assets/css/style.css">
    <style>
        .admin-login-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }
        .admin-login-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 20% 50%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                        radial-gradient(ellipse at 80% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
        }
        .admin-card {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
    </style>
</head>
<body class="admin-login-bg relative min-h-screen font-sans text-gray-900 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="admin-card rounded-2xl bg-white p-8 shadow-2xl shadow-black/10 ring-1 ring-slate-900/5 sm:p-10">
                <div class="text-center">
                    <img src="<?php echo htmlspecialchars($ucsLogoUrl); ?>" alt="UCSMTLA Academic Hub logo" class="mx-auto h-14 w-14 object-contain">
                    <p class="mt-5 text-xs font-semibold uppercase tracking-[0.22em] text-blue-600">Administration Portal</p>
                    <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Reset Password</h1>
                </div>

                <?php if ($ucsResetSuccess !== ''): ?>
                    <div class="mt-6 rounded-xl bg-green-50 px-4 py-3 ring-1 ring-green-100" role="status">
                        <p class="text-sm font-medium text-green-700"><?php echo htmlspecialchars($ucsResetSuccess); ?></p>
                        <p class="mt-2 text-sm text-green-600">
                            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/login.php'); ?>" class="font-semibold underline hover:text-green-800">Go to Admin Login</a>
                        </p>
                    </div>
                <?php elseif (!$ucsValid): ?>
                    <div class="mt-6 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
                        <p class="text-sm font-medium text-red-700">This password reset link is invalid or has expired.</p>
                        <p class="mt-2 text-sm text-red-600">
                            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/forgot-password.php'); ?>" class="font-semibold underline hover:text-red-800">Request a new link</a>
                        </p>
                    </div>
                <?php else: ?>
                    <?php if (!empty($ucsResetErrors)): ?>
                        <div class="mt-6 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
                            <ul class="space-y-1">
                                <?php foreach ($ucsResetErrors as $ucsResetError): ?>
                                    <li class="text-sm font-medium text-red-700"><?php echo htmlspecialchars($ucsResetError); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/reset-password.php'); ?>" method="post" class="mt-8 space-y-5" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($ucsCsrfToken); ?>">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($ucsToken); ?>">

                        <div>
                            <label for="admin-reset-password" class="block text-sm font-semibold text-gray-700">New Password</label>
                            <input type="password" id="admin-reset-password" name="password" required autocomplete="new-password" placeholder="Enter new password"
                                   class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>

                        <div>
                            <label for="admin-reset-password-confirm" class="block text-sm font-semibold text-gray-700">Confirm Password</label>
                            <input type="password" id="admin-reset-password-confirm" name="password_confirm" required autocomplete="new-password" placeholder="Re-enter password"
                                   class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-800 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Reset Password
                        </button>
                    </form>
                <?php endif; ?>

                <p class="mt-8 text-center text-sm">
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/login.php'); ?>" class="font-semibold text-gray-600 transition-colors hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        &larr; Back to Login
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
