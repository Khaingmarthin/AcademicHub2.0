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

// Handle "remember me" persistent login before rendering the page.
if (!student_is_logged_in()) {
    $ucsRememberUserId = student_validate_remember_cookie();
    if ($ucsRememberUserId !== false) {
        try {
            $ucsRememberStmt = $pdo->prepare(
                "SELECT id, student_id, name, email, status
                 FROM students
                 WHERE id = :id AND status = 1
                 LIMIT 1"
            );
            $ucsRememberStmt->execute([':id' => $ucsRememberUserId]);
            $ucsRememberUser = $ucsRememberStmt->fetch() ?: null;
            if ($ucsRememberUser !== null) {
                session_regenerate_id(true);
                $_SESSION['student_id']            = (int) $ucsRememberUser['id'];
                $_SESSION['student_name']          = (string) $ucsRememberUser['name'];
                $_SESSION['student_email']         = (string) $ucsRememberUser['email'];
                $_SESSION['student_last_activity'] = time();
                header('Location: ' . BASE_URL . '/student-dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            // Fall through to normal login page.
        }
    }
}

$pageTitle = 'Login';

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
<main class="flex flex-1 items-center justify-center bg-blue-100 px-4 py-8 sm:px-6 sm:py-10">
    <div class="w-full max-w-sm">
        <div class="rounded-xl bg-white/80 backdrop-blur-sm px-6 py-8 shadow-sm ring-1 ring-blue-200 border border-blue-200 sm:px-8">
            <!-- University logo -->
            <div class="text-center">
                <img src="<?php echo htmlspecialchars($ucsLoginLogo); ?>" alt="UCSMTLA logo" class="mx-auto h-12 w-12 object-contain">
                <p class="mt-4 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-blue-600">Student Portal</p>
                <h1 id="student-login-heading" class="mt-1.5 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Login</h1>
                <p class="mt-2 text-xs leading-5 text-slate-500">
                    Access your personalized academic information.
                </p>
            </div>

            <?php if (!empty($ucsLoginErrors)): ?>
                <div class="mt-4 rounded-lg bg-red-50 px-3.5 py-2.5 ring-1 ring-red-200" role="alert">
                    <ul class="space-y-0.5">
                        <?php foreach ($ucsLoginErrors as $ucsLoginError): ?>
                            <li class="text-xs font-medium text-red-700"><?php echo htmlspecialchars($ucsLoginError); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars(ROOT_URL . '/actions/student/login.php'); ?>" method="post" class="mt-6 space-y-4" novalidate>
                <div>
                    <label for="student-login-email" class="block text-xs font-semibold text-slate-700">Email</label>
                    <input type="email" id="student-login-email" name="email" value="<?php echo htmlspecialchars($ucsLoginEmail); ?>" required autocomplete="email" placeholder="student@example.com"
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="student-login-password" class="block text-xs font-semibold text-slate-700">Password</label>
                    </div>
                    <div class="relative mt-1.5">
                        <input type="password" id="student-login-password" name="password" required autocomplete="current-password" placeholder="Enter your password"
                               class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 pr-10 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <button type="button" id="student-password-toggle" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Show password" aria-pressed="false">
                            <!-- Eye -->
                            <svg id="student-eye-open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <!-- Eye off -->
                            <svg id="student-eye-closed" xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/pages/forgot-password-student.php'); ?>" class="text-xs font-semibold text-blue-600 transition-colors hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Login
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </button>
            </form>

            <p class="mt-6 text-center text-xs">
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
