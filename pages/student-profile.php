<?php
/**
 * Student Profile (protected).
 *
 * Shows the authenticated student's full profile from the students table,
 * joined with their classroom, major and academic year.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/student-validation.php';

student_require_login();

$pageTitle = 'My Profile';

$ucsFlash = $_SESSION['student_flash'] ?? null;
unset($_SESSION['student_flash']);

$ucsStudent = student_current_user();
$ucsDetails = null;

try {
    $ucsStmt = $pdo->prepare(
        "SELECT s.student_id, s.name, s.email, s.status, s.email_notifications, s.created_at,
                c.classroom_name, c.year_level, c.section,
                m.name AS major_name,
                ay.year_name AS academic_year
         FROM students s
         JOIN classrooms c ON c.id = s.classroom_id
         LEFT JOIN majors m ON m.id = c.major_id
         JOIN academic_years ay ON ay.id = c.academic_year_id
         WHERE s.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsStudent['id']]);
    $ucsDetails = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsDetails = null;
}

$ucsAccountStatus = ($ucsDetails !== null && (int) $ucsDetails['status'] === 1) ? 'Active' : 'Inactive';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-1 bg-slate-50">
    <section class="py-12 sm:py-16" aria-labelledby="student-profile-heading">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <!-- Heading -->
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Student Portal</p>
                    <h1 id="student-profile-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">My Profile</h1>
                </div>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            <?php if ($ucsFlash !== null): ?>
                <div class="mt-6 <?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100 text-red-700' : 'bg-emerald-50 ring-emerald-100 text-emerald-700'; ?> rounded-xl px-4 py-3 ring-1" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($ucsFlash['message']); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($ucsDetails !== null): ?>
                <!-- Identity -->
                <div class="mt-8 rounded-lg border border-slate-200 bg-white px-6 py-8 sm:px-8">
                    <div class="flex flex-col items-center gap-5 sm:flex-row sm:items-center">
                        <span class="inline-flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-2xl font-bold text-white" aria-hidden="true">
                            <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsDetails['name']), 0, 1))); ?>
                        </span>
                        <div class="text-center sm:text-left">
                            <h2 class="text-xl font-bold tracking-tight text-slate-900"><?php echo htmlspecialchars($ucsDetails['name']); ?></h2>
                            <p class="mt-1 text-sm text-slate-500"><?php echo htmlspecialchars($ucsDetails['email']); ?></p>
                            <span class="mt-3 inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700 ring-1 ring-blue-200">
                                <?php echo htmlspecialchars($ucsAccountStatus); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="mt-6 rounded-lg border border-slate-200 bg-white px-6 py-8 sm:px-8">
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Academic Information</h2>
                    <dl class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Student ID</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['student_id']); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Classroom</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['classroom_name']); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Year Level</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['year_level']); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Section</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['section'] !== '' && $ucsDetails['section'] !== null ? $ucsDetails['section'] : '\u2014'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Major</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['major_name']); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Academic Year</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['academic_year']); ?></dd>
                        </div>
                    </dl>
                </div>

                <?php if ($ucsAccountStatus === 'Active'): ?>
                    <!-- Notification Preferences -->
                    <div class="mt-6 rounded-lg border border-slate-200 bg-white px-6 py-8 sm:px-8">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Notification Preferences</h2>
                        <p class="mt-2 text-sm text-slate-500">Choose whether you receive email notifications about news and announcements.</p>
                        <form method="POST" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/student/update-notification.php'); ?>" class="mt-5">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                            <div class="flex items-center gap-4">
                                <label for="email_notifications" class="text-sm font-medium text-slate-700">Email Notifications</label>
                                <select id="email_notifications" name="email_notifications" required
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <option value="1" <?php echo (int) ($ucsDetails['email_notifications'] ?? 1) === 1 ? 'selected' : ''; ?>>Enabled</option>
                                    <option value="0" <?php echo (int) ($ucsDetails['email_notifications'] ?? 1) === 0 ? 'selected' : ''; ?>>Disabled</option>
                                </select>
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="mt-8 rounded-lg border border-slate-200 bg-white px-6 py-10 text-center sm:px-8">
                    <p class="text-base text-slate-600">Your profile details could not be loaded at this time. Please try again later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
