<?php
/**
 * Admin Classrooms module - list.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$pageTitle    = 'Classrooms';
$pageSubtitle = 'Manage classrooms (sections) per year and major.';
$activeNav    = 'classrooms';

$ucsFlash = $_SESSION['classroom_flash'] ?? null;
unset($_SESSION['classroom_flash']);

$ucsQuery = trim((string) ($_GET['q'] ?? ''));

$ucsClassrooms = [];
try {
    if ($ucsQuery !== '') {
        $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
        $ucsStmt = $pdo->prepare(
            "SELECT cl.id, cl.classroom_name, cl.year_level, cl.section, cl.status, cl.created_at,
                    ay.year_name AS academic_year, m.name AS major_name
             FROM classrooms cl
             JOIN academic_years ay ON ay.id = cl.academic_year_id
             JOIN majors m ON m.id = cl.major_id
             WHERE cl.classroom_name LIKE :query OR m.name LIKE :query OR cl.section LIKE :query
             ORDER BY cl.year_level ASC, cl.section ASC"
        );
        $ucsStmt->execute([':query' => '%' . $ucsEscaped . '%']);
    } else {
        $ucsStmt = $pdo->query(
            "SELECT cl.id, cl.classroom_name, cl.year_level, cl.section, cl.status, cl.created_at,
                    ay.year_name AS academic_year, m.name AS major_name
             FROM classrooms cl
             JOIN academic_years ay ON ay.id = cl.academic_year_id
             JOIN majors m ON m.id = cl.major_id
             ORDER BY cl.year_level ASC, cl.section ASC"
        );
    }
    $ucsClassrooms = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsClassrooms = [];
}

require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<?php if ($ucsFlash !== null): ?>
    <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100 text-red-700' : 'bg-blue-50 ring-blue-100 text-blue-700'; ?> rounded-xl px-4 py-3 ring-1" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
        <p class="flex items-start gap-2 text-sm font-medium">
            <?php if ($ucsFlash['type'] === 'error'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <path d="m9 11 3 3L22 4"></path>
                </svg>
            <?php endif; ?>
            <?php echo htmlspecialchars($ucsFlash['message']); ?>
        </p>
    </div>
<?php endif; ?>

<div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
    <div class="flex flex-col gap-4 border-b border-gray-100 p-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Classroom List</h2>
            <p class="mt-1 text-sm text-gray-500">Classrooms group students by academic year, major, year level and section.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/index.php'); ?>" class="relative" role="search">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search classrooms…" aria-label="Search classrooms"
                       class="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-64">
            </form>

            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14"></path>
                    <path d="M12 5v14"></path>
                </svg>
                Add Classroom
            </a>
        </div>
    </div>

    <?php if (empty($ucsClassrooms)): ?>
        <div class="p-10 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 9 12 5 2 9l10 4 10-4z"></path>
                <path d="M6 11.5V15c0 1.66 2.69 3 6 3s6-1.34 6-3v-3.5"></path>
                <path d="M2 9v5"></path>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-800">
                <?php echo $ucsQuery !== '' ? 'No matching classrooms' : 'No classrooms yet'; ?>
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                <?php echo $ucsQuery !== '' ? 'Try a different search term.' : 'Add your first classroom to get started.'; ?>
            </p>
            <?php if ($ucsQuery !== ''): ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Clear search
                </a>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Add Classroom
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Classroom</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Academic Year</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Major</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Year / Section</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($ucsClassrooms as $ucsClassroom): ?>
                        <?php
                        $ucsClassroomName = (string) $ucsClassroom['classroom_name'];
                        $ucsJsName        = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsClassroomName);
                        ?>
                        <tr class="transition-colors hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 9 12 5 2 9l10 4 10-4z"></path>
                                            <path d="M6 11.5V15c0 1.66 2.69 3 6 3s6-1.34 6-3v-3.5"></path>
                                            <path d="M2 9v5"></path>
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($ucsClassroomName); ?></p>
                                        <p class="text-xs text-gray-500">Added <?php echo date('j M Y', strtotime((string) $ucsClassroom['created_at'])); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-gray-600"><?php echo htmlspecialchars((string) $ucsClassroom['academic_year']); ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-gray-600"><?php echo htmlspecialchars((string) $ucsClassroom['major_name']); ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-gray-600">
                                <span class="block"><?php echo htmlspecialchars((string) $ucsClassroom['year_level']); ?></span>
                                <span class="block text-xs text-gray-400">Section <?php echo htmlspecialchars((string) ($ucsClassroom['section'] ?: '—')); ?></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <?php if ((int) $ucsClassroom['status'] === 1): ?>
                                    <span class="inline-flex items-center rounded-full bg-blue-600 px-2.5 py-0.5 text-xs font-semibold text-white">Active</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-500">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/edit.php?id=' . (int) $ucsClassroom['id']); ?>" title="Edit <?php echo htmlspecialchars($ucsClassroomName); ?>" aria-label="Edit <?php echo htmlspecialchars($ucsClassroomName); ?>"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                            <path d="m15 5 4 4"></path>
                                        </svg>
                                    </a>

                                    <?php if ((int) $ucsClassroom['status'] !== 1): ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/classroom-activate.php'); ?>" class="inline-flex">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsClassroom['id']; ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                                Activate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/classroom-deactivate.php'); ?>" class="inline-flex"
                                              onsubmit="return confirm('Deactivate classroom &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsClassroom['id']; ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                                                Deactivate
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/classroom-delete.php'); ?>" class="inline-flex"
                                          onsubmit="return confirm('Delete classroom &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? Its timetables will also be removed. This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsClassroom['id']; ?>">
                                        <button type="submit" title="Delete <?php echo htmlspecialchars($ucsClassroomName); ?>" aria-label="Delete <?php echo htmlspecialchars($ucsClassroomName); ?>"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition-colors duration-150 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18"></path>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
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