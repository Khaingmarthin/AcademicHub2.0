<?php
/**
 * Admin Courses module - edit form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/course-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Edit Course';
$pageSubtitle = 'Update a course record.';
$activeNav    = 'courses';

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT c.id, c.academic_year_id, c.major_id, c.course_code, c.course_name,
                c.year_level, c.semester, c.credit_hours, c.description, c.status,
                ay.year_name AS academic_year_name
         FROM courses c
         JOIN academic_years ay ON ay.id = c.academic_year_id
         WHERE c.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsCourse = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsCourse = null;
}

if ($ucsCourse === null || $ucsId === false || $ucsId < 1) {
    $_SESSION['course_flash'] = ['type' => 'error', 'message' => 'Course not found.'];
    header('Location: ' . ROOT_URL . '/admin/courses/index.php');
    exit;
}

$ucsErrors = $_SESSION['course_errors'] ?? [];
unset($_SESSION['course_errors']);

$ucsOld = $_SESSION['course_old'] ?? null;
unset($_SESSION['course_old']);

$ucsForm = [
    'academic_year_id' => $ucsOld['academic_year_id'] ?? (int) $ucsCourse['academic_year_id'],
    'major_id'         => $ucsOld['major_id'] ?? (int) $ucsCourse['major_id'],
    'course_code'      => $ucsOld['course_code'] ?? $ucsCourse['course_code'],
    'course_name'      => $ucsOld['course_name'] ?? $ucsCourse['course_name'],
    'year_level'       => $ucsOld['year_level'] ?? $ucsCourse['year_level'],
    'semester'         => $ucsOld['semester'] ?? ($ucsCourse['semester'] ?: 'First Semester'),
    'credit_hours'     => $ucsOld['credit_hours'] ?? ($ucsCourse['credit_hours'] ?? ''),
    'description'      => $ucsOld['description'] ?? ($ucsCourse['description'] ?? ''),
    'status'           => $ucsOld['status'] ?? (int) $ucsCourse['status'],
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
            <h2 class="text-base font-semibold text-gray-900">Edit Course</h2>
            <p class="mt-1 text-sm text-gray-500">Update the course details. The academic year is managed globally.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/course-update.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsCourse['id']; ?>">

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
                            <span class="truncate font-medium"><?php echo htmlspecialchars((string) ($ucsCourse['academic_year_name'] ?: $ucsForm['academic_year_id'])); ?></span>
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

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="course_code" class="block text-sm font-medium text-gray-700">Course Code <span class="text-red-500">*</span></label>
                        <input type="text" id="course_code" name="course_code" value="<?php echo htmlspecialchars($ucsForm['course_code']); ?>" placeholder="e.g. CST-1241" required maxlength="50"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="course_name" class="block text-sm font-medium text-gray-700">Course Name <span class="text-red-500">*</span></label>
                        <input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars($ucsForm['course_name']); ?>" placeholder="e.g. Discrete Mathematics" required maxlength="255"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-3">
                    <div>
                        <label for="year_level" class="block text-sm font-medium text-gray-700">Year Level <span class="text-red-500">*</span></label>
                        <select id="year_level" name="year_level" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <?php foreach (COURSE_YEAR_LEVELS as $ucsLevel): ?>
                                <option value="<?php echo $ucsLevel; ?>" <?php echo $ucsForm['year_level'] === $ucsLevel ? 'selected' : ''; ?>><?php echo $ucsLevel; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="semester" class="block text-sm font-medium text-gray-700">Semester <span class="text-red-500">*</span></label>
                        <select id="semester" name="semester" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <?php foreach (COURSE_SEMESTERS as $ucsSemester): ?>
                                <option value="<?php echo $ucsSemester; ?>" <?php echo $ucsForm['semester'] === $ucsSemester ? 'selected' : ''; ?>><?php echo $ucsSemester; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="credit_hours" class="block text-sm font-medium text-gray-700">Credit Hours</label>
                        <input type="number" id="credit_hours" name="credit_hours" value="<?php echo htmlspecialchars($ucsForm['credit_hours']); ?>" min="0" max="500" placeholder="e.g. 3"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="4"
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['description']); ?></textarea>
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
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/courses/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
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