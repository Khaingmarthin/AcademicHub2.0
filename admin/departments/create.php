<?php
/**
 * Admin Departments module - create form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Add Department';
$pageSubtitle = 'Create a new department.';
$activeNav    = 'faculties-departments';

$ucsErrors = $_SESSION['department_errors'] ?? [];
unset($_SESSION['department_errors']);

$ucsOld = $_SESSION['department_old'] ?? [
    'faculty_id'  => '',
    'name'        => '',
    'description' => '',
    'status'      => 1,
];
unset($_SESSION['department_old']);

$ucsFaculties = ucs_admin_faculties($pdo);

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
            <h2 class="text-base font-semibold text-gray-900">New Department</h2>
            <p class="mt-1 text-sm text-gray-500">The description is optional and may include a short overview of the department.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/department-create.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">

            <div class="space-y-6 px-6 py-6">
                <div>
                    <label for="faculty_id" class="block text-sm font-medium text-gray-700">Faculty <span class="text-red-500">*</span></label>
                    <select id="faculty_id" name="faculty_id" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Select a faculty…</option>
                        <?php foreach ($ucsFaculties as $ucsFacultyOption): ?>
                            <option value="<?php echo (int) $ucsFacultyOption['id']; ?>" <?php echo (int) ($ucsOld['faculty_id'] ?? 0) === (int) $ucsFacultyOption['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ucsFacultyOption['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1.5 text-xs text-gray-500">A department must belong to a faculty.</p>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Department Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($ucsOld['name'] ?? ''); ?>" placeholder="e.g. Student Affairs Department" required maxlength="255"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="4"
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsOld['description'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="1" <?php echo (int) ($ucsOld['status'] ?? 1) === 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo (int) ($ucsOld['status'] ?? 1) === 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <p class="mt-1.5 text-xs text-gray-500">Inactive departments are hidden from the public listing.</p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/faculties-departments/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Create Department
                </button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
