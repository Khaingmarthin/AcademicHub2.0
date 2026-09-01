<?php
/**
 * Admin Login page.
 *
 * Modern single-card administrator login screen. Authentication is handled by
 * actions/admin/login.php; this page only presents the form, the CSRF token
 * and any validation errors carried back in the session.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Admins who are already authenticated go straight to the dashboard.
if (admin_is_logged_in()) {
    header('Location: ' . ROOT_URL . '/admin/dashboard.php');
    exit;
}

// Handle "remember me" persistent login before rendering the page.
if (!admin_is_logged_in()) {
    $ucsRememberUserId = admin_validate_remember_cookie();
    if ($ucsRememberUserId !== false) {
        try {
            $ucsRememberStmt = $pdo->prepare(
                "SELECT id, name, email, status
                 FROM admins
                 WHERE id = :id AND status = 1
                 LIMIT 1"
            );
            $ucsRememberStmt->execute([':id' => $ucsRememberUserId]);
            $ucsRememberUser = $ucsRememberStmt->fetch() ?: null;
            if ($ucsRememberUser !== null) {
                session_regenerate_id(true);
                $_SESSION['admin_id']            = (int) $ucsRememberUser['id'];
                $_SESSION['admin_name']          = (string) $ucsRememberUser['name'];
                $_SESSION['admin_email']         = (string) $ucsRememberUser['email'];
                $_SESSION['admin_last_activity'] = time();
                header('Location: ' . ROOT_URL . '/admin/dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            // Fall through to normal login page.
        }
    }
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
    <style>
        .admin-login-bg {
            background: #dbeafe;
        }
        .admin-card {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="admin-login-bg relative min-h-screen font-sans text-gray-900 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 sm:py-10">
        <div class="w-full max-w-sm">
            <div class="admin-card rounded-xl bg-white/80 px-6 py-8 shadow-sm ring-1 ring-blue-200 border border-blue-200 sm:px-8">

                <!-- Logo & Branding -->
                <div class="text-center">
                    <img src="<?php echo htmlspecialchars($ucsLogoUrl); ?>" alt="UCSMTLA Academic Hub logo" class="mx-auto h-12 w-12 object-contain">
                    <p class="mt-4 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-blue-600">Administration Portal</p>
                    <h1 class="mt-1.5 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Admin Sign In</h1>
                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        Sign in to manage the university portal.
                    </p>
                </div>

                <!-- Validation errors -->
                <?php if (!empty($ucsLoginErrors)): ?>
                    <div class="mt-4 rounded-lg bg-red-50 px-3.5 py-2.5 ring-1 ring-red-200" role="alert">
                        <ul class="space-y-0.5">
                            <?php foreach ($ucsLoginErrors as $ucsLoginError): ?>
                                <li class="text-xs font-medium text-red-700"><?php echo htmlspecialchars($ucsLoginError); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/login.php'); ?>" method="post" class="mt-6 space-y-4" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($ucsCsrfToken); ?>">

                    <div>
                        <label for="admin-login-email" class="block text-xs font-semibold text-slate-700">Email</label>
                        <input type="email" id="admin-login-email" name="email" value="<?php echo htmlspecialchars($ucsLoginEmail); ?>" required autocomplete="username" placeholder="admin@example.com"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="admin-login-password" class="block text-xs font-semibold text-slate-700">Password</label>
                        </div>
                        <div class="relative mt-1.5">
                            <input type="password" id="admin-login-password" name="password" required autocomplete="current-password" placeholder="Enter your password"
                                   class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 pr-10 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <button type="button" id="admin-password-toggle" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Show password" aria-pressed="false">
                                <!-- Eye -->
                                <svg id="admin-eye-open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <!-- Eye off -->
                                <svg id="admin-eye-closed" xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                    <path d="M6.61 6.61A13.5 13.5 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                    <line x1="2" y1="2" x2="22" y2="22"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me + Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="remember_me" value="1"
                                   class="h-3.5 w-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-xs text-slate-600">Remember me</span>
                        </label>
                        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/forgot-password.php'); ?>" class="text-xs font-semibold text-blue-600 transition-colors hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Sign In
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </form>

                <p class="mt-6 text-center text-xs">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="font-semibold text-blue-600 transition-colors hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        &larr; Return to University Website
                    </a>
                </p>
            </div>

            <p class="mt-4 text-center text-xs text-slate-500">
                Restricted area — authorized personnel only.
            </p>
            <p class="mt-1.5 text-center text-xs text-slate-500">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(APP_NAME); ?></p>
        </div>
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
