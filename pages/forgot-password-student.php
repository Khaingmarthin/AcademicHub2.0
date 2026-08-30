<?php
/**
 * Student Forgot Password page.
 *
 * Allows a student to request a password-reset link by entering their
 * registered email address. On success a generic confirmation message is
 * shown regardless of whether the email exists in the database.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/student-auth.php';

// Already-logged-in students skip this page.
if (student_is_logged_in()) {
    header('Location: ' . BASE_URL . '/student-dashboard.php');
    exit;
}

$pageTitle = 'Reset Password';

$ucsResetSuccess = $_SESSION['student_forgot_success'] ?? '';
$ucsResetErrors  = $_SESSION['student_forgot_errors'] ?? [];
unset($_SESSION['student_forgot_success'], $_SESSION['student_forgot_errors']);

// University logo for the card.
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
            <div class="text-center">
                <img src="<?php echo htmlspecialchars($ucsLoginLogo); ?>" alt="UCSMTLA logo" class="mx-auto h-16 w-16 object-contain">
                <p class="mt-5 text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Student Portal</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Forgot Password</h1>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Enter your registered email address and we'll send you a link to reset your password.
                </p>
            </div>

            <?php if (!empty($ucsResetErrors)): ?>
                <div class="mt-6 rounded-lg bg-red-50 px-4 py-3 ring-1 ring-red-200" role="alert">
                    <ul class="space-y-1">
                        <?php foreach ($ucsResetErrors as $ucsResetError): ?>
                            <li class="text-sm font-medium text-red-700"><?php echo htmlspecialchars($ucsResetError); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($ucsResetSuccess !== ''): ?>
                <div class="mt-6 rounded-lg bg-green-50 px-4 py-3 ring-1 ring-green-200" role="status">
                    <p class="text-sm font-medium text-green-700"><?php echo htmlspecialchars($ucsResetSuccess); ?></p>
                </div>
            <?php else: ?>
                <form action="<?php echo htmlspecialchars(ROOT_URL . '/actions/student/forgot-password.php'); ?>" method="post" class="mt-8 space-y-5" novalidate>
                    <div>
                        <label for="student-forgot-email" class="block text-sm font-semibold text-slate-700">Email address</label>
                        <input type="email" id="student-forgot-email" name="email" required autocomplete="email" placeholder="student@example.com"
                               class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Send Reset Link
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </form>
            <?php endif; ?>

            <p class="mt-8 text-center text-sm">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/pages/student-login.php'); ?>" class="font-semibold text-blue-600 transition-colors hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    &larr; Back to Login
                </a>
            </p>
        </div>
    </div>
</main>

<?php
require_once '../includes/footer.php';
?>
