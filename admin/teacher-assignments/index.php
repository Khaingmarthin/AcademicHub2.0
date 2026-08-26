<?php
/**
 * Admin Teacher Course Assignments - list view.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$pageTitle    = 'Assign Teacher';
$pageSubtitle = 'Manage teaching assignments for courses and classrooms.';
$activeNav    = 'teacher-assignments';

// Flash message.
$ucsFlash = $_SESSION['teacher_assignment_flash'] ?? null;
unset($_SESSION['teacher_assignment_flash']);

// Load assignments with teacher, course, classroom details.
$ucsAssignments = [];
try {
    $ucsStmt = $pdo->query(
        "SELECT tca.id, t.name AS teacher_name, t.teacher_id,
                c.course_code, c.course_name,
                cl.classroom_name, cl.year_level, cl.section,
                m.name AS major_name,
                ay.year_name AS academic_year
         FROM teacher_course_assignments tca
         JOIN teachers t ON t.id = tca.teacher_id
         JOIN courses c ON c.id = tca.course_id
         JOIN classrooms cl ON cl.id = tca.classroom_id
         JOIN academic_years ay ON ay.id = cl.academic_year_id
         LEFT JOIN majors m ON m.id = cl.major_id
         ORDER BY ay.start_date DESC, t.name ASC, c.course_code ASC"
    );
    $ucsAssignments = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsAssignments = [];
}

require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<?php if ($ucsFlash !== null): ?>
    <div class="rounded-xl <?php echo $ucsFlash['type'] === 'success' ? 'bg-green-50 ring-1 ring-green-200 text-green-700' : 'bg-red-50 ring-1 ring-red-100 text-red-700'; ?> px-4 py-3" role="status">
        <p class="text-sm font-semibold"><?php echo htmlspecialchars($ucsFlash['message']); ?></p>
    </div>
<?php endif; ?>

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="text-sm text-gray-500"><?php echo count($ucsAssignments); ?> assignments</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/teacher-assignments/create.php'); ?>"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14"></path><path d="M12 5v14"></path>
            </svg>
            Assign Teacher
        </a>
    </div>
</div>

<div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
    <?php if (empty($ucsAssignments)): ?>
        <div class="p-10 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-800">No assignments yet</h3>
            <p class="mt-2 text-sm text-gray-500">Get started by assigning your first teacher.</p>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/teacher-assignments/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
                Assign Teacher
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Teacher</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Course</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Classroom</th>
                        <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php foreach ($ucsAssignments as $ucsAssignment): ?>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($ucsAssignment['teacher_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($ucsAssignment['teacher_id']); ?></div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="text-sm font-bold text-blue-600"><?php echo htmlspecialchars($ucsAssignment['course_code']); ?></div>
                                <div class="text-xs text-gray-600"><?php echo htmlspecialchars($ucsAssignment['course_name']); ?></div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($ucsAssignment['classroom_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($ucsAssignment['year_level']); ?><?php echo $ucsAssignment['section'] ? ' - ' . htmlspecialchars($ucsAssignment['section']) : ''; ?></div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/teacher-assignments/edit.php?id=' . (int) $ucsAssignment['id']); ?>"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none">
                                        Edit
                                    </a>
                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/teacher-assignment-delete.php'); ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsAssignment['id']; ?>">
                                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 transition-colors duration-150 hover:bg-red-50 focus:outline-none">
                                            Delete
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
