<?php
/**
 * Admin Teacher Course Assignments - edit form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Edit Assignment';
$pageSubtitle = 'Update a teaching assignment record.';
$activeNav    = 'teacher-assignments';

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, teacher_id, course_id, classroom_id
         FROM teacher_course_assignments WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsAssignment = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsAssignment = null;
}

if ($ucsAssignment === null || $ucsId === false || $ucsId < 1) {
    $_SESSION['teacher_assignment_flash'] = ['type' => 'error', 'message' => 'Assignment not found.'];
    header('Location: ' . ROOT_URL . '/admin/teacher-assignments/index.php');
    exit;
}

$ucsErrors = $_SESSION['teacher_assignment_errors'] ?? [];
unset($_SESSION['teacher_assignment_errors']);

$ucsOld = $_SESSION['teacher_assignment_old'] ?? null;
unset($_SESSION['teacher_assignment_old']);

$ucsForm = [
    'teacher_id'   => $ucsOld['teacher_id'] ?? $ucsAssignment['teacher_id'],
    'course_id'    => $ucsOld['course_id'] ?? $ucsAssignment['course_id'],
    'classroom_id' => $ucsOld['classroom_id'] ?? $ucsAssignment['classroom_id'],
];

$ucsTeachers   = ucs_admin_teachers($pdo);
$ucsCourses    = ucs_admin_active_year_courses($pdo);
$ucsClassrooms = ucs_admin_classrooms($pdo);

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
            <h2 class="text-base font-semibold text-gray-900">Edit Assignment</h2>
            <p class="mt-1 text-sm text-gray-500">Update the teacher, course, or classroom for this assignment.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/teacher-assignment-update.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsAssignment['id']; ?>">

            <div class="space-y-6 px-6 py-6">
                <div>
                    <label for="teacher_id" class="block text-sm font-medium text-gray-700">Teacher <span class="text-red-500">*</span></label>
                    <select id="teacher_id" name="teacher_id" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Select a teacher…</option>
                        <?php foreach ($ucsTeachers as $ucsTeacherOption): ?>
                            <option value="<?php echo (int) $ucsTeacherOption['id']; ?>" <?php echo (int) $ucsForm['teacher_id'] === (int) $ucsTeacherOption['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ucsTeacherOption['name'] . ' (' . $ucsTeacherOption['teacher_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="course_id" class="block text-sm font-medium text-gray-700">Course <span class="text-red-500">*</span></label>
                    <select id="course_id" name="course_id" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Select a course…</option>
                        <?php foreach ($ucsCourses as $ucsCourseOption): ?>
                            <option value="<?php echo (int) $ucsCourseOption['id']; ?>" data-year-level="<?php echo htmlspecialchars($ucsCourseOption['year_level']); ?>" <?php echo (int) $ucsForm['course_id'] === (int) $ucsCourseOption['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ucsCourseOption['course_code'] . ' - ' . $ucsCourseOption['course_name'] . ' (' . $ucsCourseOption['year_level'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="classroom_id" class="block text-sm font-medium text-gray-700">Classroom <span class="text-red-500">*</span></label>
                    <select id="classroom_id" name="classroom_id" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Select a classroom…</option>
                        <?php foreach ($ucsClassrooms as $ucsClassroomOption): ?>
                            <option value="<?php echo (int) $ucsClassroomOption['id']; ?>" data-year-level="<?php echo htmlspecialchars($ucsClassroomOption['year_level']); ?>" <?php echo (int) $ucsForm['classroom_id'] === (int) $ucsClassroomOption['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ucsClassroomOption['classroom_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/teacher-assignments/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
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
<script>
(function () {
    var courseSelect = document.getElementById('course_id');
    var classroomSelect = document.getElementById('classroom_id');
    if (!courseSelect || !classroomSelect) return;

    var defaultClassroomHtml = classroomSelect.innerHTML;

    function filterClassrooms() {
        var selectedCourse = courseSelect.options[courseSelect.selectedIndex];
        var yearLevel = selectedCourse ? selectedCourse.getAttribute('data-year-level') : '';
        var currentVal = classroomSelect.value;

        classroomSelect.innerHTML = defaultClassroomHtml;

        var options = classroomSelect.querySelectorAll('option[data-year-level]');
        options.forEach(function (opt) {
            if (yearLevel && opt.getAttribute('data-year-level') !== yearLevel) {
                opt.remove();
            }
        });

        var firstOption = classroomSelect.querySelector('option[value=""]');
        if (firstOption) {
            firstOption.textContent = yearLevel ? 'Select a ' + yearLevel.toLowerCase() + ' classroom…' : 'Select a classroom…';
        }

        if (currentVal) {
            var stillExists = classroomSelect.querySelector('option[value="' + currentVal + '"]');
            if (stillExists) {
                classroomSelect.value = currentVal;
            } else {
                classroomSelect.value = '';
            }
        }
    }

    courseSelect.addEventListener('change', filterClassrooms);

    if (courseSelect.value) {
        filterClassrooms();
    }
})();
</script>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
