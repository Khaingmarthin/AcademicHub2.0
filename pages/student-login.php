<?php
/**
 * Public Student Login page.
 *
 * Renders a clean, professional login form. Authentication is handled by
 * actions/student/login.php; this page only presents the form and any
 * validation errors carried back in the session.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/student-auth.php';

// Students who are already authenticated go straight to the dashboard.
if (student_is_logged_in()) {
    header('Location: ' . BASE_URL . '/student-dashboard.php');
    exit;
}

$pageTitle = 'Student Login';

// Flash messages left by the login handler.
$ucsLoginErrors = $_SESSION['student_login_errors'] ?? [];
$ucsLoginEmail  = (string) ($_SESSION['student_login_email'] ?? '');
unset($_SESSION['student_login_errors'], $_SESSION['student_login_email']);

// University logo for the login card.
$ucsLoginLogo = 'images/logo.png';
try {
    $ucsLoginStmt = $pdo->query(
        "SELECT logo FROM university_profile ORDER BY id ASC LIMIT 1"
    );
    $ucsLoginRow = $ucsLoginStmt->fetch() ?: null;
    $ucsLoginLogo = $ucsLoginRow['logo'] ?? 'images/logo.png';
} catch (PDOException $e) {
    $ucsLoginLogo = 'images/logo.png';
}

if (!preg_match('~^https?://~i', $ucsLoginLogo)) {
    $ucsLoginLogo = ROOT_URL . '/assets/' . ltrim($ucsLoginLogo, '/');
}

require_once '../includes/header.php';
?>
<main class="flex flex-1 items-center justify-center bg-slate-50 px-4 py-16 sm:px-6 sm:py-20">
    <div class="w-full max-w-md">
        <div class="rounded-lg bg-white px-6 py-10 ring-1 ring-slate-200 sm:px-10">
            <!-- University logo -->
            <div class="text-center">
                <img src="<?php echo htmlspecialchars($ucsLoginLogo); ?>" alt="UCSMTLA logo" class="mx-auto h-16 w-16 object-contain">
                <p class="mt-5 text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Student Portal</p>
                <h1 id="student-login-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Student Login</h1>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Access your personalized academic information.
                </p>
            </div>

            <?php if (!empty($ucsLoginErrors)): ?>
                <div class="mt-6 rounded-lg bg-red-50 px-4 py-3 ring-1 ring-red-200" role="alert">
                    <ul class="space-y-1">
                        <?php foreach ($ucsLoginErrors as $ucsLoginError): ?>
                            <li class="text-sm font-medium text-red-700"><?php echo htmlspecialchars($ucsLoginError); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars(ROOT_URL . '/actions/student/login.php'); ?>" method="post" class="mt-8 space-y-5" novalidate>
                <div>
                    <label for="student-login-email" class="block text-sm font-semibold text-slate-700">Email</label>
                    <input type="email" id="student-login-email" name="email" value="<?php echo htmlspecialchars($ucsLoginEmail); ?>" required autocomplete="email" placeholder="student@example.com"
                           class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="student-login-password" class="block text-sm font-semibold text-slate-700">Password</label>
                    </div>
                    <div class="relative mt-2">
                        <input type="password" id="student-login-password" name="password" required autocomplete="current-password" placeholder="Enter your password"
                               class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 pr-11 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <button type="button" id="student-password-toggle" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Show password" aria-pressed="false">
                            <!-- Eye -->
                            <svg id="student-eye-open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <!-- Eye off -->
                            <svg id="student-eye-closed" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                <path d="M6.61 6.61A13.5 13.5 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                <line x1="2" y1="2" x2="22" y2="22"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Login
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </button>
            </form>

            <p class="mt-8 text-center text-sm">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="font-semibold text-blue-600 transition-colors hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Return to University Website
                </a>
            </p>
        </div>
    </div>
</main>

<script>
    (function () {
        'use strict';
        var toggle = document.getElementById('student-password-toggle');
        var input = document.getElementById('student-login-password');
        var eyeOpen = document.getElementById('student-eye-open');
        var eyeClosed = document.getElementById('student-eye-closed');
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

<?php
require_once '../includes/footer.php';
?>
