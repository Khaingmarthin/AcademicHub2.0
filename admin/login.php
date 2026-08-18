<?php
/**
 * Admin Login page.
 *
 * Standalone administrator login screen. Authentication is handled by
 * actions/admin/login.php; this page only presents the form, the CSRF token
 * and any validation errors carried back in the session.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Admins who are already authenticated go straight to the dashboard.
if (admin_is_logged_in()) {
    header('Location: ' . ROOT_URL . '/admin/dashboard.php');
    exit;
}

$pageTitle = 'Admin Login';

// Flash messages left by the login handler.
$ucsLoginErrors = $_SESSION['admin_login_errors'] ?? [];
$ucsLoginEmail  = (string) ($_SESSION['admin_login_email'] ?? '');
unset($_SESSION['admin_login_errors'], $_SESSION['admin_login_email']);

// CSRF token for the login form.
$ucsCsrfToken = admin_csrf_token();
$ucsLogoUrl   = ROOT_URL . '/assets/images/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <meta name="description" content="UCSMTLA Academic Hub - Administrator sign in.">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(ROOT_URL); ?>/assets/css/style.css">
</head>
<body class="min-h-screen bg-slate-100 font-sans text-gray-900 antialiased">
    <div class="flex min-h-screen">
        <!-- Brand panel (desktop) -->
        <aside class="relative hidden flex-col justify-between overflow-hidden bg-gray-900 p-12 lg:flex lg:w-[45%] xl:w-1/2" aria-hidden="true">
            <div class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-blue-600/20 blur-3xl"></div>
            <div class="pointer-events-none absolute bottom-0 -left-24 h-72 w-72 rounded-full bg-blue-500/10 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <img src="<?php echo htmlspecialchars($ucsLogoUrl); ?>" alt="UCSMTLA Academic Hub logo" class="h-11 w-11 object-contain">
                <span class="leading-tight">
                    <span class="block text-lg font-bold tracking-tight text-white">UCSMTLA</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-400">Academic Hub</span>
                </span>
            </div>

            <div class="relative max-w-md">
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-400">Administration Portal</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight tracking-tight text-white">
                    University Administration
                </h1>
                <p class="mt-4 text-sm leading-6 text-gray-400">
                    Manage faculties, departments, courses, students, admissions and news for the UCSMTLA Academic Hub from a single secure control panel.
                </p>
            </div>

            <p class="relative text-xs text-gray-500">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(APP_NAME); ?></p>
        </aside>

        <!-- Login form panel -->
        <main class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:py-16">
            <div class="w-full max-w-md">
                <div class="rounded-2xl bg-white p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 sm:p-10">
                    <!-- Heading -->
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-900 text-blue-400" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-widest text-blue-700">Administrator</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">Admin Sign In</h1>
                        </div>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-gray-500">
                        Sign in to manage the university portal.
                    </p>

                    <!-- Validation errors -->
                    <?php if (!empty($ucsLoginErrors)): ?>
                        <div class="mt-6 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
                            <ul class="space-y-1">
                                <?php foreach ($ucsLoginErrors as $ucsLoginError): ?>
                                    <li class="text-sm font-medium text-red-700"><?php echo htmlspecialchars($ucsLoginError); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/login.php'); ?>" method="post" class="mt-8 space-y-5" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($ucsCsrfToken); ?>">

                        <div>
                            <label for="admin-login-email" class="block text-sm font-semibold text-gray-700">Email address</label>
                            <input type="email" id="admin-login-email" name="email" value="<?php echo htmlspecialchars($ucsLoginEmail); ?>" required autocomplete="username" placeholder="admin@example.com"
                                   class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="admin-login-password" class="block text-sm font-semibold text-gray-700">Password</label>
                            </div>
                            <div class="relative mt-2">
                                <input type="password" id="admin-login-password" name="password" required autocomplete="current-password" placeholder="Enter your password"
                                       class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 pr-11 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                <button type="button" id="admin-password-toggle" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-400 transition-colors hover:text-gray-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Show password" aria-pressed="false">
                                    <!-- Eye -->
                                    <svg id="admin-eye-open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <!-- Eye off -->
                                    <svg id="admin-eye-closed" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                        <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                        <path d="M6.61 6.61A13.5 13.5 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                        <line x1="2" y1="2" x2="22" y2="22"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-800 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Sign In
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </form>

                    <p class="mt-8 text-center text-sm">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="font-semibold text-gray-600 transition-colors hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            &larr; Return to University Website
                        </a>
                    </p>
                </div>

                <p class="mt-6 text-center text-xs text-gray-400">
                    Restricted area — authorized personnel only.
                </p>
            </div>
        </main>
    </div>

    <script>
        (function () {
            'use strict';
            var toggle = document.getElementById('admin-password-toggle');
            var input = document.getElementById('admin-login-password');
            var eyeOpen = document.getElementById('admin-eye-open');
            var eyeClosed = document.getElementById('admin-eye-closed');
            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                if (eyeOpen) eyeOpen.classList.toggle('hidden', show);
                if (eyeClosed) eyeClosed.classList.toggle('hidden', !show);
            });
        })();
    </script>
</body>
</html>
