<?php
/**
 * Admin Faculties & Departments module - list view.
 *
 * Displays 3 parts:
 *   Part 1 — Four Faculties
 *   Part 2 — Three academic departments (ITSM, Natural Language, Natural Science)
 *   Part 3 — Four administration departments (Library, Finance, Administration, Student Affairs)
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

// Load faculties.
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
$ucsAllDepartments = [];
try {
    $ucsDeptStmt = $pdo->query(
        "SELECT d.id, d.faculty_id, d.name, d.description, d.status
         FROM departments d
         ORDER BY d.name ASC"
    );
    $ucsAllDepartments = $ucsDeptStmt->fetchAll();
    foreach ($ucsAllDepartments as $ucsDept) {
        $ucsDepartmentsByFaculty[(int) $ucsDept['faculty_id']][] = $ucsDept;
    }
} catch (PDOException $e) {
    $ucsAllDepartments = [];
}

// Part 2: Academic departments — these 3 specific departments
$ucsAcademicDeptNames = [
    'Information Technology and Systems Management (ITSM) Department',
    'Department of Natural Language (Myanmar and English)',
    'Department of Natural Science (Physics)',
];

// Part 3: Administration departments — these 4
$ucsAdminDeptNames = [
    'Library',
    'Finance Department',
    'Administration Department',
    'Student Affairs Department',
];

// Separate academic and admin departments from the loaded data.
$ucsAcademicDepts = [];
$ucsAdminDepts    = [];
foreach ($ucsAllDepartments as $ucsDept) {
    $ucsDeptName = (string) $ucsDept['name'];
    if (in_array($ucsDeptName, $ucsAcademicDeptNames, true)) {
        $ucsAcademicDepts[] = $ucsDept;
    } elseif (in_array($ucsDeptName, $ucsAdminDeptNames, true)) {
        $ucsAdminDepts[] = $ucsDept;
    }
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
        <p class="text-sm text-gray-500"><?php echo count($ucsFaculties); ?> faculties &middot; <?php echo count($ucsAcademicDepts); ?> academic departments &middot; <?php echo count($ucsAdminDepts); ?> admin departments</p>
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

<!-- Faculties Section -->
<section class="mt-6" aria-labelledby="faculties-heading">
    <div class="flex items-center gap-3 mb-4">
        <div class="h-8 w-1 rounded-full bg-blue-600" aria-hidden="true"></div>
        <h2 id="faculties-heading" class="text-lg font-bold text-slate-800">Faculties</h2>
        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-600 ring-1 ring-blue-200"><?php echo count($ucsFaculties); ?></span>
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
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($ucsFaculties as $ucsFaculty): ?>
                <?php
                $ucsFacultyId       = (int) $ucsFaculty['id'];
                $ucsFacultyDepts    = $ucsDepartmentsByFaculty[$ucsFacultyId] ?? [];
                $ucsFacultyIsActive = (int) $ucsFaculty['status'] === 1;
                ?>
                <div class="group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500" aria-hidden="true"></div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="truncate text-sm font-bold text-gray-900"><?php echo htmlspecialchars($ucsFaculty['name']); ?></h3>
                                    <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide <?php echo $ucsFacultyIsActive ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200'; ?>">
                                        <?php echo $ucsFacultyIsActive ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <?php if (!empty($ucsFaculty['description'])): ?>
                                    <p class="mt-1 truncate text-xs text-gray-500"><?php echo htmlspecialchars(mb_strimwidth($ucsFaculty['description'], 0, 100, '…')); ?></p>
                                <?php endif; ?>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                                        <?php echo count($ucsFacultyDepts); ?> departments
                                    </span>
                                </div>
                            </div>
                            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/faculties/edit.php?id=' . $ucsFacultyId); ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none">
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Academic Departments Section (Part 2) -->
<section class="mt-8" aria-labelledby="academic-depts-heading">
    <div class="flex items-center gap-3 mb-4">
        <div class="h-8 w-1 rounded-full bg-emerald-600" aria-hidden="true"></div>
        <h2 id="academic-depts-heading" class="text-lg font-bold text-slate-800">Academic Departments</h2>
        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 ring-1 ring-emerald-200"><?php echo count($ucsAcademicDepts); ?> departments</span>
    </div>

    <?php if (empty($ucsAcademicDepts)): ?>
        <p class="text-sm text-gray-400 italic">No academic departments found.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($ucsAcademicDepts as $ucsDept): ?>
                <?php
                $ucsDeptIsActive = (int) $ucsDept['status'] === 1;
                $ucsFacultyName  = '';
                foreach ($ucsFaculties as $ucsF) {
                    if ((int) $ucsF['id'] === (int) $ucsDept['faculty_id']) {
                        $ucsFacultyName = (string) $ucsF['name'];
                        break;
                    }
                }
                ?>
                <div class="group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500" aria-hidden="true"></div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($ucsDept['name']); ?></h3>
                                    <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide <?php echo $ucsDeptIsActive ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200'; ?>">
                                        <?php echo $ucsDeptIsActive ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <?php if ($ucsFacultyName !== ''): ?>
                                    <p class="mt-1 text-[11px] text-emerald-600 font-medium"><?php echo htmlspecialchars($ucsFacultyName); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($ucsDept['description'])): ?>
                                    <p class="mt-1.5 text-xs text-gray-500 line-clamp-2"><?php echo htmlspecialchars(mb_strimwidth($ucsDept['description'], 0, 120, '…')); ?></p>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/departments/edit.php?id=' . (int) $ucsDept['id']); ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none">
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Administration Departments Section (Part 3) -->
<section class="mt-8" aria-labelledby="admin-depts-heading">
    <div class="flex items-center gap-3 mb-4">
        <div class="h-8 w-1 rounded-full bg-violet-600" aria-hidden="true"></div>
        <h2 id="admin-depts-heading" class="text-lg font-bold text-slate-800">Administration Departments</h2>
        <span class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-semibold text-violet-600 ring-1 ring-violet-200"><?php echo count($ucsAdminDepts); ?> departments</span>
    </div>

    <?php if (empty($ucsAdminDepts)): ?>
        <p class="text-sm text-gray-400 italic">No administration departments found.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <?php
            $ucsAdminColors = [
                'Student Affairs Department' => ['gradient' => 'from-blue-500 to-blue-600', 'borderColor' => '#3b82f6'],
                'Library'                    => ['gradient' => 'from-emerald-500 to-emerald-600', 'borderColor' => '#10b981'],
                'Administration Department'  => ['gradient' => 'from-amber-500 to-amber-600', 'borderColor' => '#f59e0b'],
                'Finance Department'         => ['gradient' => 'from-violet-500 to-violet-600', 'borderColor' => '#8b5cf6'],
            ];
            $ucsAdminIcons = [
                'Student Affairs Department' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
                'Library'                    => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>',
                'Administration Department'  => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>',
                'Finance Department'         => '<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>',
            ];
            foreach ($ucsAdminDepts as $ucsDept):
                $ucsDeptName = (string) $ucsDept['name'];
                $ucsColor = $ucsAdminColors[$ucsDeptName] ?? ['gradient' => 'from-gray-500 to-gray-600', 'borderColor' => '#6b7280'];
                $ucsIcon  = $ucsAdminIcons[$ucsDeptName] ?? '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>';
                $ucsDeptIsActive = (int) $ucsDept['status'] === 1;
            ?>
                <div class="group relative overflow-hidden rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md" style="border-left: 4px solid <?php echo $ucsColor['borderColor']; ?>">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br <?php echo $ucsColor['gradient']; ?> text-white shadow-md transition-all duration-200 group-hover:scale-110" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <?php echo $ucsIcon; ?>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($ucsDept['name']); ?></h3>
                                    <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide <?php echo $ucsDeptIsActive ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200'; ?>">
                                        <?php echo $ucsDeptIsActive ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <p class="mt-0.5 text-[11px] text-slate-400">Administration Department</p>
                            </div>
                        </div>
                        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/departments/edit.php?id=' . (int) $ucsDept['id']); ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none">
                            Edit
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
