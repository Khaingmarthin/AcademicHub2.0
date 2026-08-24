<?php
/**
 * Admin Students module - create form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/student-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Add Student';
$pageSubtitle = 'Create a new student account.';
$activeNav    = 'students';

$ucsErrors = $_SESSION['student_errors'] ?? [];
unset($_SESSION['student_errors']);

$ucsOld = $_SESSION['student_old'] ?? null;
unset($_SESSION['student_old']);

// Students belong to the active academic year; classrooms are loaded from
// that year only and the year itself is never selected manually.
$ucsActiveYear     = ucs_admin_active_academic_year($pdo);
$ucsActiveYearId   = $ucsActiveYear !== null ? (int) $ucsActiveYear['id'] : 0;
$ucsActiveYearName = $ucsActiveYear !== null ? (string) $ucsActiveYear['year_name'] : '';

$ucsClassrooms = ucs_admin_active_year_classrooms($pdo);

$ucsForm = [
    'student_id'          => $ucsOld['student_id'] ?? '',
    'roll_number'         => $ucsOld['roll_number'] ?? '',
    'name'                => $ucsOld['name'] ?? '',
    'email'               => $ucsOld['email'] ?? '',
    'classroom_id'        => $ucsOld['classroom_id'] ?? '',
    'status'              => $ucsOld['status'] ?? 'active',
    'email_notifications' => $ucsOld['email_notifications'] ?? '1',
];

$hidePageHeader = true;
require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<!-- Page Header -->
<div class="mb-6 flex flex-col gap-1">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?php echo htmlspecialchars($pageTitle); ?></h1>
    <p class="text-sm text-slate-500"><?php echo htmlspecialchars($pageSubtitle); ?></p>
</div>
<?php if (!empty($ucsErrors)): ?>
    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3" role="alert">
        <p class="text-sm font-semibold text-red-700">Please fix the following:</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
            <?php foreach ($ucsErrors as $ucsError): ?>
                <li><?php echo htmlspecialchars($ucsError); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="mx-auto max-w-2xl">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">New Student</h2>
            <p class="mt-1 text-sm text-slate-500">The student will use this ID, roll number and password to sign in. The student is automatically assigned to the active academic year.</p>
        </div>

        <?php if ($ucsActiveYear === null): ?>
            <div class="px-6 py-10 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">No active academic year</h3>
                <p class="mt-2 text-sm text-slate-500">Students are created under the active academic year. Activate an academic year first.</p>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Manage Academic Years
                </a>
            </div>
        <?php else: ?>
        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/student-create.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">

            <div class="space-y-6 px-6 py-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Academic Year</label>
                    <div class="mt-2 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span class="truncate font-medium"><?php echo htmlspecialchars($ucsActiveYearName); ?></span>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-700 ring-1 ring-blue-100">Active</span>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Automatically inherited from the active academic year.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-slate-700">Student ID <span class="text-red-500">*</span></label>
                        <input type="text" id="student_id" name="student_id" value="<?php echo htmlspecialchars($ucsForm['student_id']); ?>" placeholder="e.g. UCSM-2025-0001" required maxlength="50"
                               class="mt-2 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="roll_number" class="block text-sm font-medium text-slate-700">Roll Number <span class="text-red-500">*</span></label>
                        <input type="text" id="roll_number" name="roll_number" value="<?php echo htmlspecialchars($ucsForm['roll_number']); ?>" placeholder="e.g. R-1001" required maxlength="50"
                               class="mt-2 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($ucsForm['name']); ?>" placeholder="e.g. Juan Dela Cruz" required maxlength="255"
                           class="mt-2 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($ucsForm['email']); ?>" placeholder="student@example.com" required maxlength="191"
                           class="mt-2 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Password <span class="text-red-500">*</span></label>
                        <input type="password" id="password" name="password" placeholder="At least 8 characters" required minlength="8"
                               class="mt-2 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="classroom_id" class="block text-sm font-medium text-slate-700">Classroom / Section <span class="text-red-500">*</span></label>
                        <select id="classroom_id" name="classroom_id" required
                                class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Select a classroom…</option>
                            <?php foreach ($ucsClassrooms as $ucsClassroom): ?>
                                <option value="<?php echo (int) $ucsClassroom['id']; ?>" <?php echo (int) $ucsForm['classroom_id'] === (int) $ucsClassroom['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ucsClassroom['classroom_name'] . ' — ' . $ucsClassroom['major_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                    <select id="status" name="status" required
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="active" <?php echo ($ucsForm['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="graduated" <?php echo ($ucsForm['status'] ?? '') === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                    </select>
                </div>

                <div>
                    <label for="email_notifications" class="block text-sm font-medium text-slate-700">Email Notifications</label>
                    <select id="email_notifications" name="email_notifications" required
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="1" <?php echo (int) ($ucsForm['email_notifications'] ?? 1) === 1 ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo (int) ($ucsForm['email_notifications'] ?? 1) === 0 ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">Whether this student receives email notifications about news and announcements.</p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Create Student
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
