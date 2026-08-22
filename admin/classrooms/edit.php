<?php
/**
 * Admin Classrooms module - edit form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/classroom-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Edit Classroom';
$pageSubtitle = 'Update a classroom record.';
$activeNav    = 'classrooms';

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT cl.id, cl.academic_year_id, cl.major_id, cl.year_level, cl.section, cl.classroom_name, cl.status,
                ay.year_name AS academic_year_name
         FROM classrooms cl
         JOIN academic_years ay ON ay.id = cl.academic_year_id
         WHERE cl.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsClassroom = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsClassroom = null;
}

if ($ucsClassroom === null || $ucsId === false || $ucsId < 1) {
    $_SESSION['classroom_flash'] = ['type' => 'error', 'message' => 'Classroom not found.'];
    header('Location: ' . ROOT_URL . '/admin/classrooms/index.php');
    exit;
}

$ucsErrors = $_SESSION['classroom_errors'] ?? [];
unset($_SESSION['classroom_errors']);

$ucsOld = $_SESSION['classroom_old'] ?? null;
unset($_SESSION['classroom_old']);

$ucsForm = [
    'academic_year_id' => $ucsOld['academic_year_id'] ?? (int) $ucsClassroom['academic_year_id'],
    'major_id'         => $ucsOld['major_id'] ?? (int) $ucsClassroom['major_id'],
    'year_level'       => $ucsOld['year_level'] ?? $ucsClassroom['year_level'],
    'section'          => $ucsOld['section'] ?? $ucsClassroom['section'],
    'classroom_name'   => $ucsOld['classroom_name'] ?? $ucsClassroom['classroom_name'],
    'status'           => $ucsOld['status'] ?? (int) $ucsClassroom['status'],
];

$ucsMajors = ucs_admin_majors($pdo);

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
            <h2 class="text-base font-semibold text-gray-900">Edit Classroom</h2>
            <p class="mt-1 text-sm text-gray-500">Update the classroom details. The academic year is managed globally.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/classroom-update.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsClassroom['id']; ?>">

            <div class="space-y-6 px-6 py-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                        <div class="mt-2 flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span class="truncate font-medium"><?php echo htmlspecialchars((string) ($ucsClassroom['academic_year_name'] ?: $ucsForm['academic_year_id'])); ?></span>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">Academic year is managed globally and cannot be changed here.</p>
                        <input type="hidden" name="academic_year_id" value="<?php echo (int) $ucsForm['academic_year_id']; ?>">
                    </div>
                    <div>
                        <label for="major_id" class="block text-sm font-medium text-gray-700">Major <span class="text-red-500">*</span></label>
                        <select id="major_id" name="major_id" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <?php foreach ($ucsMajors as $ucsMajor): ?>
                                <option value="<?php echo (int) $ucsMajor['id']; ?>" <?php echo (int) $ucsForm['major_id'] === (int) $ucsMajor['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ucsMajor['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-3">
                    <div>
                        <label for="year_level" class="block text-sm font-medium text-gray-700">Year Level <span class="text-red-500">*</span></label>
                        <select id="year_level" name="year_level" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <?php foreach (CLASSROOM_YEAR_LEVELS as $ucsLevel): ?>
                                <option value="<?php echo $ucsLevel; ?>" <?php echo $ucsForm['year_level'] === $ucsLevel ? 'selected' : ''; ?>><?php echo $ucsLevel; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="section" class="block text-sm font-medium text-gray-700">Section</label>
                        <input type="text" id="section" name="section" value="<?php echo htmlspecialchars($ucsForm['section'] === '-' ? '' : $ucsForm['section']); ?>" placeholder="e.g. A (optional)" maxlength="10"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <p class="mt-1.5 text-xs text-gray-500">Leave empty for a single section classroom.</p>
                    </div>
                    <div>
                        <label for="classroom_name" class="block text-sm font-medium text-gray-700">Classroom Name <span class="text-red-500">*</span></label>
                        <input type="text" id="classroom_name" name="classroom_name" value="<?php echo htmlspecialchars($ucsForm['classroom_name']); ?>" placeholder="e.g. First Year (A)" required maxlength="100"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
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
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
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