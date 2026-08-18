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

$ucsClassrooms = ucs_admin_classrooms($pdo);

$ucsForm = [
    'student_id'   => $ucsOld['student_id'] ?? '',
    'name'         => $ucsOld['name'] ?? '',
    'email'        => $ucsOld['email'] ?? '',
    'classroom_id' => $ucsOld['classroom_id'] ?? '',
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
            <h2 class="text-base font-semibold text-gray-900">New Student</h2>
            <p class="mt-1 text-sm text-gray-500">The student will use this ID and password to sign in.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/student-create.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">

            <div class="space-y-6 px-6 py-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-gray-700">Student ID <span class="text-red-500">*</span></label>
                        <input type="text" id="student_id" name="student_id" value="<?php echo htmlspecialchars($ucsForm['student_id']); ?>" placeholder="e.g. UCSM-2025-0001" required maxlength="50"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($ucsForm['name']); ?>" placeholder="e.g. Juan Dela Cruz" required maxlength="255"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($ucsForm['email']); ?>" placeholder="student@example.com" required maxlength="191"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                        <input type="password" id="password" name="password" placeholder="At least 8 characters" required minlength="8"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="classroom_id" class="block text-sm font-medium text-gray-700">Classroom <span class="text-red-500">*</span></label>
                        <select id="classroom_id" name="classroom_id" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Select a classroom…</option>
                            <?php
                            $ucsYearLabel = null;
                            foreach ($ucsClassrooms as $ucsClassroom):
                                $ucsGroupLabel = $ucsClassroom['academic_year'] . ($ucsClassroom['academic_year_status'] === 'Active' ? ' (Active)' : '');
                                if ($ucsGroupLabel !== $ucsYearLabel):
                                    if ($ucsYearLabel !== null): ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <optgroup label="<?php echo htmlspecialchars($ucsGroupLabel); ?>">
                                    <?php $ucsYearLabel = $ucsGroupLabel;
                                endif; ?>
                                <option value="<?php echo (int) $ucsClassroom['id']; ?>" <?php echo (int) $ucsForm['classroom_id'] === (int) $ucsClassroom['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ucsClassroom['classroom_name'] . ' — ' . $ucsClassroom['major_name']); ?>
                                </option>
                            <?php endforeach;
                            if ($ucsYearLabel !== null): ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
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
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Create Student
                </button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>