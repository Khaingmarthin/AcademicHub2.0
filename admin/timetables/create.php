<?php
/**
 * Admin Timetables module - create form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/timetable-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Add Timetable';
$pageSubtitle = 'Upload a timetable image for a classroom and semester.';
$activeNav    = 'timetables';

$ucsErrors = $_SESSION['timetable_errors'] ?? [];
unset($_SESSION['timetable_errors']);

$ucsOld = $_SESSION['timetable_old'] ?? null;
unset($_SESSION['timetable_old']);

// Timetables are scoped to the active academic year; classrooms are loaded
// from that year only and the year itself is never selected manually.
$ucsActiveYear     = ucs_admin_active_academic_year($pdo);
$ucsActiveYearId   = $ucsActiveYear !== null ? (int) $ucsActiveYear['id'] : 0;
$ucsActiveYearName = $ucsActiveYear !== null ? (string) $ucsActiveYear['year_name'] : '';

$ucsClassrooms = ucs_admin_active_year_classrooms($pdo);

$ucsForm = [
    'classroom_id' => $ucsOld['classroom_id'] ?? '',
    'semester'     => $ucsOld['semester'] ?? 'First Semester',
    'title'        => $ucsOld['title'] ?? '',
    'status'       => $ucsOld['status'] ?? 1,
];

require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
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

<div class="mx-auto max-w-2xl">
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-6 py-5">
            <h2 class="text-base font-semibold text-gray-900">New Timetable</h2>
            <p class="mt-1 text-sm text-gray-500">Each classroom may have one timetable per semester. The timetable is automatically assigned to the active academic year.</p>
        </div>

        <?php if ($ucsActiveYear === null): ?>
            <div class="px-6 py-10 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-800">No active academic year</h3>
                <p class="mt-2 text-sm text-gray-500">Timetables are created under the active academic year. Activate an academic year first.</p>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Manage Academic Years
                </a>
            </div>
        <?php else: ?>
        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/timetable-create.php'); ?>" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">

            <div class="space-y-6 px-6 py-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                    <div class="mt-2 flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span class="truncate font-medium"><?php echo htmlspecialchars($ucsActiveYearName); ?></span>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-700 ring-1 ring-blue-100">Active</span>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500">Automatically inherited from the active academic year.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="classroom_id" class="block text-sm font-medium text-gray-700">Classroom / Section <span class="text-red-500">*</span></label>
                        <select id="classroom_id" name="classroom_id" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Select a classroom…</option>
                            <?php foreach ($ucsClassrooms as $ucsClassroom): ?>
                                <option value="<?php echo (int) $ucsClassroom['id']; ?>" <?php echo (int) $ucsForm['classroom_id'] === (int) $ucsClassroom['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ucsClassroom['classroom_name'] . ' — ' . $ucsClassroom['major_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="semester" class="block text-sm font-medium text-gray-700">Semester <span class="text-red-500">*</span></label>
                        <select id="semester" name="semester" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <?php foreach (TIMETABLE_SEMESTERS as $ucsSemester): ?>
                                <option value="<?php echo $ucsSemester; ?>" <?php echo $ucsForm['semester'] === $ucsSemester ? 'selected' : ''; ?>><?php echo $ucsSemester; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ucsForm['title']); ?>" placeholder="e.g. First Year (A) — First Semester" required maxlength="255"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">Timetable Image <span class="text-red-500">*</span></label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp" required
                           class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 transition-colors hover:file:bg-blue-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <p class="mt-2 text-xs text-gray-500">JPG, PNG, GIF or WebP. Maximum size 2 MB.</p>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="1" <?php echo (int) $ucsForm['status'] === 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo (int) $ucsForm['status'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Create Timetable
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>