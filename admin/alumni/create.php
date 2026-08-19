<?php
/**
 * Admin Alumni module - create profile.
 *
 * Lists officially graduated students who do not yet have an alumni profile,
 * so the admin can create one. Creating a profile activates the alumni
 * relationship for that same person; no new account is created.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';

admin_require_login();

$pageTitle    = 'Create Alumni Profile';
$pageSubtitle = 'Activate an alumni profile for an officially graduated student.';
$activeNav    = 'alumni';

$ucsFlash = $_SESSION['alumni_flash'] ?? null;
unset($_SESSION['alumni_flash']);

$ucsErrors = $_SESSION['alumni_errors'] ?? [];
unset($_SESSION['alumni_errors']);

// Graduated students without an alumni profile.
$ucsCandidates = [];
try {
    $ucsStmt = $pdo->query(
        "SELECT s.id, s.name, s.student_id AS student_code, s.roll_number,
                s.graduation_year,
                m.name AS major_name
         FROM students s
         JOIN classrooms cl ON cl.id = s.classroom_id
         JOIN majors m ON m.id = cl.major_id
         LEFT JOIN alumni_profiles ap ON ap.student_id = s.id
         WHERE s.student_status = 'graduated' AND ap.id IS NULL
         ORDER BY s.graduation_year DESC, s.name ASC"
    );
    $ucsCandidates = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsCandidates = [];
}

require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<?php if ($ucsFlash !== null): ?>
    <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100 text-red-700' : 'bg-blue-50 ring-blue-100 text-blue-700'; ?> rounded-xl px-4 py-3 ring-1" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
        <p class="flex items-start gap-2 text-sm font-medium">
            <?php if ($ucsFlash['type'] === 'error'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <path d="m9 11 3 3L22 4"></path>
                </svg>
            <?php endif; ?>
            <?php echo htmlspecialchars($ucsFlash['message']); ?>
        </p>
    </div>
<?php endif; ?>

<?php if (!empty($ucsErrors)): ?>
    <div class="rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
        <p class="text-sm font-semibold text-red-700">Please fix the following:</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
            <?php foreach ($ucsErrors as $ucsError): ?>
                <li><?php echo htmlspecialchars($ucsError); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
    <div class="flex flex-col gap-1 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Graduated Students</h2>
            <p class="mt-1 text-sm text-gray-500">Students who are officially graduated but do not yet have an alumni profile.</p>
        </div>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/index.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
            Back to Alumni
        </a>
    </div>

    <?php if (empty($ucsCandidates)): ?>
        <div class="p-10 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
                <path d="M22 10v6"></path>
                <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-800">No candidates available</h3>
            <p class="mt-2 text-sm text-gray-500">All graduated students already have an alumni profile, or no student has been marked as graduated yet.</p>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php?academic_status=graduated'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                View Graduated Students
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Student</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Major</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Graduation Year</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($ucsCandidates as $ucsCandidate): ?>
                        <?php
                        $ucsCandidateName = (string) $ucsCandidate['name'];
                        $ucsJsCandidate   = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsCandidateName);
                        ?>
                        <tr class="transition-colors hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="max-w-[13rem] truncate font-semibold text-gray-900"><?php echo htmlspecialchars($ucsCandidateName); ?></p>
                                        <p class="max-w-[13rem] truncate text-xs text-gray-500"><?php echo htmlspecialchars((string) $ucsCandidate['student_code']); ?> &middot; <?php echo htmlspecialchars((string) $ucsCandidate['roll_number']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-gray-600"><?php echo htmlspecialchars((string) $ucsCandidate['major_name']); ?></td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs font-semibold text-gray-800 ring-1 ring-gray-200"><?php echo htmlspecialchars((string) $ucsCandidate['graduation_year']); ?></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex justify-end">
                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-create.php'); ?>" class="inline-flex"
                                          onsubmit="return confirm('Create an Alumni Profile for &quot;<?php echo htmlspecialchars($ucsJsCandidate); ?>&quot;? It will start as Pending verification.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="student_id" value="<?php echo (int) $ucsCandidate['id']; ?>">
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M5 12h14"></path>
                                                <path d="M12 5v14"></path>
                                            </svg>
                                            Create Profile
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>