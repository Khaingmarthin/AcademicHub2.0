<?php
/**
 * Admin Teachers module - edit form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Edit Teacher';
$pageSubtitle = 'Update a teacher record.';
$activeNav    = 'teachers';

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, teacher_id, name, email, phone, faculty_id, department_id, specialization, status
         FROM teachers WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsTeacher = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsTeacher = null;
}

if ($ucsTeacher === null || $ucsId === false || $ucsId < 1) {
    $_SESSION['teacher_flash'] = ['type' => 'error', 'message' => 'Teacher not found.'];
    header('Location: ' . ROOT_URL . '/admin/teachers/index.php');
    exit;
}

$ucsErrors = $_SESSION['teacher_errors'] ?? [];
unset($_SESSION['teacher_errors']);

$ucsOld = $_SESSION['teacher_old'] ?? null;
unset($_SESSION['teacher_old']);

$ucsForm = [
    'teacher_id'     => $ucsOld['teacher_id'] ?? $ucsTeacher['teacher_id'],
    'name'           => $ucsOld['name'] ?? $ucsTeacher['name'],
    'email'          => $ucsOld['email'] ?? ($ucsTeacher['email'] ?? ''),
    'phone'          => $ucsOld['phone'] ?? ($ucsTeacher['phone'] ?? ''),
    'faculty_id'     => $ucsOld['faculty_id'] ?? ($ucsTeacher['faculty_id'] ?? ''),
    'department_id'  => $ucsOld['department_id'] ?? ($ucsTeacher['department_id'] ?? ''),
    'specialization' => $ucsOld['specialization'] ?? ($ucsTeacher['specialization'] ?? ''),
    'status'         => $ucsOld['status'] ?? (int) $ucsTeacher['status'],
];

$ucsFaculties    = ucs_admin_faculties($pdo);
$ucsDepartments  = ucs_admin_academic_departments($pdo);

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
            <h2 class="text-base font-semibold text-gray-900">Edit Teacher</h2>
            <p class="mt-1 text-sm text-gray-500">A teacher must belong to either a faculty or an academic department (not both).</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/teacher-update.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsTeacher['id']; ?>">

            <div class="space-y-6 px-6 py-6">
                <div>
                    <label for="teacher_id" class="block text-sm font-medium text-gray-700">Teacher ID <span class="text-red-500">*</span></label>
                    <input type="text" id="teacher_id" name="teacher_id" value="<?php echo htmlspecialchars($ucsForm['teacher_id']); ?>" placeholder="e.g. T-001" required maxlength="50"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($ucsForm['name']); ?>" placeholder="e.g. Dr. Aung Aung" required maxlength="255"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($ucsForm['email']); ?>" placeholder="teacher@ucsmtla.edu.mm"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($ucsForm['phone']); ?>" placeholder="09-123 456 789"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div>
                    <label for="specialization" class="block text-sm font-medium text-gray-700">Specialization</label>
                    <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($ucsForm['specialization']); ?>" placeholder="e.g. Artificial Intelligence"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="faculty_id" class="block text-sm font-medium text-gray-700">Faculty</label>
                        <select id="faculty_id" name="faculty_id"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Select a faculty…</option>
                            <?php foreach ($ucsFaculties as $ucsFacultyOption): ?>
                                <option value="<?php echo (int) $ucsFacultyOption['id']; ?>" <?php echo (int) $ucsForm['faculty_id'] === (int) $ucsFacultyOption['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ucsFacultyOption['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="mt-1.5 text-xs text-gray-500">Choose either faculty or department, not both.</p>
                    </div>
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700">Academic Department</label>
                        <select id="department_id" name="department_id"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Select a department…</option>
                            <?php foreach ($ucsDepartments as $ucsDeptOption): ?>
                                <option value="<?php echo (int) $ucsDeptOption['id']; ?>" <?php echo (int) $ucsForm['department_id'] === (int) $ucsDeptOption['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ucsDeptOption['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <script>
                    (function () {
                        var facultySelect = document.getElementById('faculty_id');
                        var departmentSelect = document.getElementById('department_id');
                        if (!facultySelect || !departmentSelect) return;

                        function toggleFields() {
                            var facultyVal = facultySelect.value;
                            var deptVal = departmentSelect.value;
                            departmentSelect.disabled = facultyVal !== '';
                            facultySelect.disabled = deptVal !== '';
                        }

                        facultySelect.addEventListener('change', function () {
                            if (facultySelect.value !== '') {
                                departmentSelect.value = '';
                            }
                            toggleFields();
                        });

                        departmentSelect.addEventListener('change', function () {
                            if (departmentSelect.value !== '') {
                                facultySelect.value = '';
                            }
                            toggleFields();
                        });

                        toggleFields();
                    })();
                </script>

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
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/teachers/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
