<?php
/**
 * Admin Admissions module - list.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$pageTitle    = 'Admissions';
$pageSubtitle = 'Manage admission announcements and documents.';
$activeNav    = 'admission';

$ucsFlash = $_SESSION['admission_flash'] ?? null;
unset($_SESSION['admission_flash']);

$ucsQuery = trim((string) ($_GET['q'] ?? ''));

$ucsAdmissions = [];
try {
    if ($ucsQuery !== '') {
        $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
        $ucsStmt = $pdo->prepare(
            "SELECT ad.id, ad.title, ad.document_title, ad.document_type, ad.status, ad.created_at,
                    ay.year_name AS academic_year
             FROM admissions ad
             JOIN academic_years ay ON ay.id = ad.academic_year_id
             WHERE ad.title LIKE :query OR ay.year_name LIKE :query
             ORDER BY ad.created_at DESC"
        );
        $ucsStmt->execute([':query' => '%' . $ucsEscaped . '%']);
    } else {
        $ucsStmt = $pdo->query(
            "SELECT ad.id, ad.title, ad.document_title, ad.document_type, ad.status, ad.created_at,
                    ay.year_name AS academic_year
             FROM admissions ad
             JOIN academic_years ay ON ay.id = ad.academic_year_id
             ORDER BY ad.created_at DESC"
        );
    }
    $ucsAdmissions = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsAdmissions = [];
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
            <h2 class="text-base font-semibold text-gray-900">Admission List</h2>
            <p class="mt-1 text-sm text-gray-500">Announcements shown on the admission page with optional document downloads.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/admission/index.php'); ?>" class="relative" role="search">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search admissions…" aria-label="Search admissions"
                       class="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-64">
            </form>

            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/admission/create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14"></path>
                    <path d="M12 5v14"></path>
                </svg>
                Add Admission
            </a>
        </div>
    </div>

    <?php if (empty($ucsAdmissions)): ?>
        <div class="p-10 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2v20"></path>
                <path d="m17 5 4 4-4 4"></path>
                <path d="m7 19-4-4 4-4"></path>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-800">
                <?php echo $ucsQuery !== '' ? 'No matching admissions' : 'No admissions yet'; ?>
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                <?php echo $ucsQuery !== '' ? 'Try a different search term.' : 'Add your first admission announcement to get started.'; ?>
            </p>
            <?php if ($ucsQuery !== ''): ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/admission/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Clear search
                </a>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/admission/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Add Admission
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="w-full">
            <table class="w-full table-fixed text-left text-[13px] text-gray-600">
                <thead class="border-b border-gray-100 bg-gray-50/50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="w-[35%] px-4 py-3">Admission</th>
                        <th scope="col" class="w-[15%] px-4 py-3">Academic Year</th>
                        <th scope="col" class="w-1/4 px-4 py-3">Document</th>
                        <th scope="col" class="w-[10%] px-4 py-3">Status</th>
                        <th scope="col" class="w-[15%] px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($ucsAdmissions as $ucsAdmission): ?>
                        <?php
                        $ucsAdmissionTitle = (string) $ucsAdmission['title'];
                        $ucsJsName         = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsAdmissionTitle);
                        ?>
                        <tr class="transition-colors hover:bg-gray-50/50">
                            <td class="px-4 py-3 truncate">
                                <div class="flex items-center gap-2">
                                    <span class="hidden sm:inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 2v20"></path>
                                            <path d="m17 5 4 4-4 4"></path>
                                            <path d="m7 19-4-4 4-4"></path>
                                        </svg>
                                    </span>
                                    <div class="min-w-0 flex-1 truncate">
                                        <p class="truncate font-semibold text-gray-900"><?php echo htmlspecialchars($ucsAdmissionTitle); ?></p>
                                        <p class="truncate text-[11px] text-gray-500">Added <?php echo date('j M Y', strtotime((string) $ucsAdmission['created_at'])); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 truncate text-gray-600"><?php echo htmlspecialchars((string) $ucsAdmission['academic_year']); ?></td>
                            <td class="px-4 py-3 truncate text-gray-600">
                                <?php if (!empty($ucsAdmission['document_title'])): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 truncate max-w-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <path d="M7 10l5 5 5-5"></path>
                                            <path d="M12 15V3"></path>
                                        </svg>
                                        <span class="truncate"><?php echo htmlspecialchars($ucsAdmission['document_title']); ?></span>
                                        <span class="ml-1 shrink-0 rounded bg-white px-1 text-[9px] font-semibold text-gray-400"><?php echo htmlspecialchars((string) $ucsAdmission['document_type']); ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-[11px] text-gray-400">No document</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ((int) $ucsAdmission['status'] === 1): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/admission/edit.php?id=' . (int) $ucsAdmission['id']); ?>" title="Edit <?php echo htmlspecialchars($ucsAdmissionTitle); ?>"
                                       class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-600 transition-colors hover:bg-amber-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                            <path d="m15 5 4 4"></path>
                                        </svg>
                                        Edit
                                    </a>

                                    <?php if ((int) $ucsAdmission['status'] !== 1): ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/admission-activate.php'); ?>" class="inline-flex">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsAdmission['id']; ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-600 transition-colors hover:bg-blue-100">
                                                Activate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/admission-deactivate.php'); ?>" class="inline-flex"
                                              onsubmit="return confirm('Deactivate admission &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsAdmission['id']; ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600 transition-colors hover:bg-slate-200">
                                                Deactivate
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/admission-delete.php'); ?>" class="inline-flex"
                                          onsubmit="return confirm('Delete admission &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsAdmission['id']; ?>">
                                        <button type="submit" title="Delete <?php echo htmlspecialchars($ucsAdmissionTitle); ?>"
                                                class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-500 transition-colors hover:bg-red-100 hover:text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18"></path>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                            Del
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