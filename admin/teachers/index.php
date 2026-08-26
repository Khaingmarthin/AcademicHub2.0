<?php
/**
 * Admin Teachers module - list view.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$pageTitle    = 'Teachers';
$pageSubtitle = 'Manage university teachers.';
$activeNav    = 'teachers';

// Flash message.
$ucsFlash = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);

// Load teachers with faculty/department names.
$ucsTeachers = [];
try {
    $ucsStmt = $pdo->query(
        "SELECT t.id, t.teacher_id, t.name, t.email, t.phone, t.specialization, t.status,
                f.name AS faculty_name, d.name AS department_name
         FROM teachers t
         LEFT JOIN faculties f ON f.id = t.faculty_id
         LEFT JOIN departments d ON d.id = t.department_id
         ORDER BY t.name ASC"
    );
    $ucsTeachers = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsTeachers = [];
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
        <p class="text-sm text-gray-500"><?php echo count($ucsTeachers); ?> teachers</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/teachers/create.php'); ?>"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14"></path><path d="M12 5v14"></path>
            </svg>
            Add Teacher
        </a>
    </div>
</div>

<div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
    <?php if (empty($ucsTeachers)): ?>
        <div class="p-10 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-800">No teachers yet</h3>
            <p class="mt-2 text-sm text-gray-500">Get started by adding your first teacher.</p>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/teachers/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
                Add Teacher
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Teacher ID</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Belongs To</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Specialization</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php foreach ($ucsTeachers as $ucsTeacher): ?>
                        <?php $ucsIsActive = (int) $ucsTeacher['status'] === 1; ?>
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700"><?php echo htmlspecialchars($ucsTeacher['teacher_id']); ?></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($ucsTeacher['name']); ?></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($ucsTeacher['email'] ?? '—'); ?></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <?php if (!empty($ucsTeacher['faculty_name'])): ?>
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-200"><?php echo htmlspecialchars($ucsTeacher['faculty_name']); ?></span>
                                <?php elseif (!empty($ucsTeacher['department_name'])): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200"><?php echo htmlspecialchars($ucsTeacher['department_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($ucsTeacher['specialization'] ?? '—'); ?></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide <?php echo $ucsIsActive ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200'; ?>">
                                    <?php echo $ucsIsActive ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/teachers/edit.php?id=' . (int) $ucsTeacher['id']); ?>"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none">
                                        Edit
                                    </a>
                                    <?php if ($ucsIsActive): ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/teacher-deactivate.php'); ?>" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsTeacher['id']; ?>">
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-amber-700 transition-colors duration-150 hover:bg-amber-50 focus:outline-none">
                                                Deactivate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/teacher-activate.php'); ?>" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsTeacher['id']; ?>">
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-green-700 transition-colors duration-150 hover:bg-green-50 focus:outline-none">
                                                Activate
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
