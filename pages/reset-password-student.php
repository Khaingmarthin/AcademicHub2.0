<?php
/**
 * Student Reset Password page.
 *
 * Reached via the unique link sent to the student's email. The token is
 * validated before rendering the form.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/student-auth.php';

$ucsToken    = (string) ($_GET['token'] ?? '');
$ucsValid    = false;
$ucsUserId   = false;
$ucsResetErrors  = $_SESSION['student_reset_errors'] ?? [];
$ucsResetSuccess = $_SESSION['student_reset_success'] ?? '';
unset($_SESSION['student_reset_errors'], $_SESSION['student_reset_success']);

if ($ucsToken !== '') {
    $ucsUserId = student_validate_reset_token($ucsToken);
    if ($ucsUserId !== false) {
        $ucsValid = true;
    }
}

$pageTitle = 'Reset Password';

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
            <div class="text-center">
                <img src="<?php echo htmlspecialchars($ucsLoginLogo); ?>" alt="UCSMTLA logo" class="mx-auto h-16 w-16 object-contain">
                <p class="mt-5 text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Student Portal</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Reset Password</h1>
            </div>

            <?php if ($ucsResetSuccess !== ''): ?>
                <div class="mt-6 rounded-lg bg-green-50 px-4 py-3 ring-1 ring-green-200" role="status">
                    <p class="text-sm font-medium text-green-700"><?php echo htmlspecialchars($ucsResetSuccess); ?></p>
                    <p class="mt-2 text-sm text-green-600">
                        <a href="<?php echo htmlspecialchars(ROOT_URL . '/pages/student-login.php'); ?>" class="font-semibold underline hover:text-green-800">Go to Login</a>
                    </p>
                </div>
            <?php elseif (!$ucsValid): ?>
                <div class="mt-6 rounded-lg bg-red-50 px-4 py-3 ring-1 ring-red-200" role="alert">
                    <p class="text-sm font-medium text-red-700">This password reset link is invalid or has expired.</p>
                    <p class="mt-2 text-sm text-red-600">
                        <a href="<?php echo htmlspecialchars(ROOT_URL . '/pages/forgot-password-student.php'); ?>" class="font-semibold underline hover:text-red-800">Request a new link</a>
                    </p>
                </div>
            <?php else: ?>
                <?php if (!empty($ucsResetErrors)): ?>
                    <div class="mt-6 rounded-lg bg-red-50 px-4 py-3 ring-1 ring-red-200" role="alert">
                        <ul class="space-y-1">
                            <?php foreach ($ucsResetErrors as $ucsResetError): ?>
                                <li class="text-sm font-medium text-red-700"><?php echo htmlspecialchars($ucsResetError); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars(ROOT_URL . '/actions/student/reset-password.php'); ?>" method="post" class="mt-8 space-y-5" novalidate>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($ucsToken); ?>">

                    <div>
                        <label for="student-reset-password" class="block text-sm font-semibold text-slate-700">New Password</label>
                        <input type="password" id="student-reset-password" name="password" required autocomplete="new-password" placeholder="Enter new password"
                               class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="student-reset-password-confirm" class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                        <input type="password" id="student-reset-password-confirm" name="password_confirm" required autocomplete="new-password" placeholder="Re-enter password"
                               class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Reset Password
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
