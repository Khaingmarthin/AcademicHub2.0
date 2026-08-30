<?php
/**
 * Admin Forgot Password page.
 *
 * Allows an admin to request a password-reset link by entering their
 * registered email address. On success a generic confirmation message is
 * shown regardless of whether the email exists in the database.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Already-logged-in admins skip this page.
if (admin_is_logged_in()) {
    header('Location: ' . ROOT_URL . '/admin/dashboard.php');
    exit;
}

$pageTitle = 'Reset Password';

$ucsResetSuccess = $_SESSION['admin_forgot_success'] ?? '';
$ucsResetErrors  = $_SESSION['admin_forgot_errors'] ?? [];
unset($_SESSION['admin_forgot_success'], $_SESSION['admin_forgot_errors']);

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
                    <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Forgot Password</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-500">
                        Enter your registered email address and we'll send you a link to reset your password.
                    </p>
                </div>

                <?php if (!empty($ucsResetErrors)): ?>
                    <div class="mt-6 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
                        <ul class="space-y-1">
                            <?php foreach ($ucsResetErrors as $ucsResetError): ?>
                                <li class="text-sm font-medium text-red-700"><?php echo htmlspecialchars($ucsResetError); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($ucsResetSuccess !== ''): ?>
                    <div class="mt-6 rounded-xl bg-green-50 px-4 py-3 ring-1 ring-green-100" role="status">
                        <p class="text-sm font-medium text-green-700"><?php echo htmlspecialchars($ucsResetSuccess); ?></p>
                    </div>
                <?php else: ?>
                    <form action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/forgot-password.php'); ?>" method="post" class="mt-8 space-y-5" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($ucsCsrfToken); ?>">

                        <div>
                            <label for="admin-forgot-email" class="block text-sm font-semibold text-gray-700">Email address</label>
                            <input type="email" id="admin-forgot-email" name="email" required autocomplete="username" placeholder="admin@example.com"
                                   class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-800 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Send Reset Link
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
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
