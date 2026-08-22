<?php
/**
 * Admin Academic Years module - list.
 *
 * Lists all academic years with search, status badges and per-row actions
 * (edit, activate, archive, delete). The Active year comes from the database
 * and is never hard-coded.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$pageTitle    = 'Academic Years';
$pageSubtitle = 'Manage academic years and their lifecycle.';
$activeNav    = 'academic-years';

// Flash message left by an action handler.
$ucsFlash = $_SESSION['academic_year_flash'] ?? null;
unset($_SESSION['academic_year_flash']);

// Search term.
$ucsQuery = trim((string) ($_GET['q'] ?? ''));

// Load the academic years (optionally filtered by the search term).
$ucsYears = [];
try {
    if ($ucsQuery !== '') {
        // Escape LIKE wildcards so user input is matched literally.
        $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
        $ucsStmt = $pdo->prepare(
            "SELECT id, year_name, start_date, end_date, status, created_at
             FROM academic_years
             WHERE year_name LIKE :query
             ORDER BY start_date DESC, id DESC"
        );
        $ucsStmt->execute([':query' => '%' . $ucsEscaped . '%']);
    } else {
        $ucsStmt = $pdo->query(
            "SELECT id, year_name, start_date, end_date, status, created_at
             FROM academic_years
             ORDER BY start_date DESC, id DESC"
        );
    }
    $ucsYears = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsYears = [];
}

$ucsFmtDate = function ($value) {
    if (empty($value)) {
        return '—';
    }
    $ts = strtotime((string) $value);
    return $ts !== false ? date('j M Y', $ts) : htmlspecialchars((string) $value);
};

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
    <!-- Card header: title, search, add button -->
    <div class="flex flex-col gap-4 border-b border-gray-100 p-6">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Academic Year List</h2>
            <p class="mt-1 text-sm text-gray-500">Only one academic year can be Active at a time. Activating a year automatically archives the previous Active one.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="relative" role="search">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search academic years…" aria-label="Search academic years"
                       class="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-64">
            </form>

            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14"></path>
                    <path d="M12 5v14"></path>
                </svg>
                Add Academic Year
            </a>
        </div>
    </div>

    <?php if (empty($ucsYears)): ?>
        <div class="p-10 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-800">
                <?php echo $ucsQuery !== '' ? 'No matching academic years' : 'No academic years yet'; ?>
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                <?php echo $ucsQuery !== '' ? 'Try a different search term.' : 'Add your first academic year to get started.'; ?>
            </p>
            <?php if ($ucsQuery !== ''): ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Clear search
                </a>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="M12 5v14"></path>
                    </svg>
                    Add Academic Year
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="w-full">
            <table class="w-full table-fixed text-left text-[13px] text-gray-600">
                <thead class="border-b border-gray-100 bg-gray-50/50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="w-[40%] px-4 py-3">Academic Year</th>
                        <th scope="col" class="w-[20%] px-4 py-3">Status</th>
                        <th scope="col" class="w-[22%] px-4 py-3">Created At</th>
                        <th scope="col" class="w-[18%] px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($ucsYears as $ucsYear): ?>
                        <?php
                        $ucsYearName = (string) $ucsYear['year_name'];
                        $ucsJsName   = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsYearName);
                        ?>
                        <tr class="transition-colors hover:bg-gray-50/50">
                            <td class="px-4 py-3 truncate">
                                <div class="flex items-center gap-3">
                                    <span class="hidden sm:inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                    </span>
                                    <div class="min-w-0 flex-1 truncate">
                                        <p class="truncate font-semibold text-gray-900"><?php echo htmlspecialchars($ucsYearName); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($ucsYear['status'] === 'Active'): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                        Active
                                    </span>
                                <?php elseif ($ucsYear['status'] === 'Preparation'): ?>
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                        Preparation
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">
                                        Archived
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 truncate text-gray-600"><?php echo $ucsFmtDate($ucsYear['created_at']); ?></td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/edit.php?id=' . (int) $ucsYear['id']); ?>"
                                       class="text-[12px] font-semibold text-amber-600 hover:text-amber-700 transition-colors">Edit</a>

                                    <?php if ($ucsYear['status'] !== 'Active'): ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/academic-year-activate.php'); ?>" class="inline m-0">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsYear['id']; ?>">
                                            <button type="submit" class="text-[12px] font-semibold text-blue-600 hover:text-blue-700 transition-colors">Activate</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($ucsYear['status'] !== 'Archived'): ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/academic-year-archive.php'); ?>" class="inline m-0"
                                              onsubmit="return confirm('Archive academic year &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsYear['id']; ?>">
                                            <button type="submit" class="text-[12px] font-semibold text-slate-500 hover:text-slate-700 transition-colors">Archive</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/academic-year-delete.php'); ?>" class="inline m-0"
                                          onsubmit="return confirm('Delete academic year &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsYear['id']; ?>">
                                        <button type="submit" class="text-[12px] font-semibold text-red-500 hover:text-red-600 transition-colors">Delete</button>
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
