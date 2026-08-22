<?php
/**
 * Admin Faculties & Departments module - list view.
 *
 * Displays all faculties with their departments grouped beneath each faculty.
 * Allows quick-create buttons for both faculties and departments.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$pageTitle    = 'Faculties & Departments';
$pageSubtitle = 'Manage faculties and their departments.';
$activeNav    = 'faculties-departments';

// Flash message.
$ucsFlash = $_SESSION['faculty_flash'] ?? $_SESSION['department_flash'] ?? null;
unset($_SESSION['faculty_flash'], $_SESSION['department_flash']);

// Load faculties with departments grouped beneath.
$ucsFaculties = [];
try {
    $ucsFacultyStmt = $pdo->query(
        "SELECT id, name, description, status
         FROM faculties
         ORDER BY name ASC"
    );
    $ucsFaculties = $ucsFacultyStmt->fetchAll();
} catch (PDOException $e) {
    $ucsFaculties = [];
}

// Load all departments keyed by faculty_id.
$ucsDepartmentsByFaculty = [];
try {
    $ucsDeptStmt = $pdo->query(
        "SELECT d.id, d.faculty_id, d.name, d.description, d.status
         FROM departments d
         ORDER BY d.name ASC"
    );
    foreach ($ucsDeptStmt->fetchAll() as $ucsDept) {
        $ucsDepartmentsByFaculty[(int) $ucsDept['faculty_id']][] = $ucsDept;
    }
} catch (PDOException $e) {
    $ucsDepartmentsByFaculty = [];
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
        <p class="text-sm text-gray-500"><?php echo count($ucsFaculties); ?> faculty/faculties &middot; <?php echo array_sum(array_map('count', $ucsDepartmentsByFaculty)); ?> department(s)</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/faculties/create.php'); ?>"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14"></path><path d="M12 5v14"></path>
            </svg>
            Add Faculty
        </a>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/departments/create.php'); ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14"></path><path d="M12 5v14"></path>
            </svg>
            Add Department
        </a>
    </div>
</div>

<?php if (empty($ucsFaculties)): ?>
    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 22v-4a2 2 0 1 0-4 0v4"></path><path d="m18 10 3.447 1.724a1 1 0 0 1 .553.894V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7.382a1 1 0 0 1 .553-.894L6 10"></path><path d="M18 5v17"></path><path d="m4 6 8-4 8 4"></path><path d="M6 5v17"></path><circle cx="12" cy="9" r="2"></circle>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">No faculties yet</h3>
        <p class="mt-2 text-sm text-gray-500">Get started by creating your first faculty.</p>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/faculties/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            Create Faculty
        </a>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($ucsFaculties as $ucsFaculty): ?>
            <?php
            $ucsFacultyId       = (int) $ucsFaculty['id'];
            $ucsFacultyDepts    = $ucsDepartmentsByFaculty[$ucsFacultyId] ?? [];
            $ucsFacultyIsActive = (int) $ucsFaculty['status'] === 1;
            ?>
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
                <!-- Faculty header -->
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="truncate text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($ucsFaculty['name']); ?></h3>
                            <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide <?php echo $ucsFacultyIsActive ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200'; ?>">
                                <?php echo $ucsFacultyIsActive ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        <?php if (!empty($ucsFaculty['description'])): ?>
                            <p class="mt-0.5 truncate text-xs text-gray-500"><?php echo htmlspecialchars(mb_strimwidth($ucsFaculty['description'], 0, 120, '…')); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/faculties/edit.php?id=' . $ucsFacultyId); ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                            Edit
                        </a>
                    </div>
                </div>

                <!-- Departments -->
                <?php if (empty($ucsFacultyDepts)): ?>
                    <div class="px-5 py-4 text-center text-xs text-gray-400">
                        No departments yet. <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/departments/create.php'); ?>" class="font-semibold text-blue-600 hover:underline">Add one</a>.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($ucsFacultyDepts as $ucsDept): ?>
                            <?php $ucsDeptIsActive = (int) $ucsDept['status'] === 1; ?>
                            <div class="flex items-center justify-between gap-3 px-5 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-800"><?php echo htmlspecialchars($ucsDept['name']); ?></p>
                                    <?php if (!empty($ucsDept['description'])): ?>
                                        <p class="mt-0.5 truncate text-xs text-gray-400"><?php echo htmlspecialchars(mb_strimwidth($ucsDept['description'], 0, 100, '…')); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide <?php echo $ucsDeptIsActive ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200'; ?>">
                                        <?php echo $ucsDeptIsActive ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/departments/edit.php?id=' . (int) $ucsDept['id']); ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
