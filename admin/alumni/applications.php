<?php
/**
 * Admin Alumni module - pending applications.
 *
 * Lists all alumni profiles with verification_status 'pending' so admins
 * can review, verify or reject each application. Provides the same search
 * and filter capabilities as the main alumni index, scoped to pending only.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';

admin_require_login();

$pageTitle    = 'Alumni Applications';
$pageSubtitle = 'Review and manage pending alumni profile applications.';
$activeNav    = 'alumni';

$ucsFlash = $_SESSION['alumni_flash'] ?? null;
unset($_SESSION['alumni_flash']);

// --- Filters ----------------------------------------------------------------
$ucsQuery          = trim((string) ($_GET['q'] ?? ''));
$ucsGraduationYear = filter_var($_GET['graduation_year'] ?? '', FILTER_VALIDATE_INT);
if ($ucsGraduationYear === false) { $ucsGraduationYear = 0; }
$ucsMajorId        = filter_var($_GET['major'] ?? '', FILTER_VALIDATE_INT);
if ($ucsMajorId === false) { $ucsMajorId = 0; }
$ucsShow           = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage           = max(1, (int) ($_GET['page'] ?? 1));

// --- Filter option lists ----------------------------------------------------
$ucsYearOptions  = [];
$ucsMajorOptions = [];

try {
    $ucsYearStmt = $pdo->query(
        "SELECT DISTINCT s.graduation_year
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         WHERE ap.verification_status = 'pending' AND s.graduation_year IS NOT NULL
         ORDER BY s.graduation_year DESC"
    );
    $ucsYearOptions = $ucsYearStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsYearOptions = [];
}

try {
    $ucsMajorStmt = $pdo->query(
        "SELECT DISTINCT m.id, m.name
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         LEFT JOIN classrooms cl ON cl.id = s.classroom_id
         LEFT JOIN majors m ON m.id = cl.major_id
         WHERE ap.verification_status = 'pending' AND m.id IS NOT NULL
         ORDER BY m.name ASC"
    );
    $ucsMajorOptions = $ucsMajorStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsMajorOptions = [];
}

// --- Query ------------------------------------------------------------------
$ucsWhere  = ["ap.verification_status = 'pending'"];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[] = '(s.name LIKE :q OR s.roll_number LIKE :q OR ap.current_job LIKE :q OR ap.company LIKE :q)';
    $ucsParams[':q'] = '%' . $ucsEscaped . '%';
}
if ($ucsGraduationYear > 0) {
    $ucsWhere[]           = 's.graduation_year = :grad_year';
    $ucsParams[':grad_year'] = $ucsGraduationYear;
}
if ($ucsMajorId > 0) {
    $ucsWhere[]      = 'cl.major_id = :major_id';
    $ucsParams[':major_id'] = $ucsMajorId;
}

$ucsWhereSql = ' WHERE ' . implode(' AND ', $ucsWhere);

$ucsProfiles     = [];
$ucsTotal        = 0;
$ucsTotalPages   = 1;

try {
    $ucsCountStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         LEFT JOIN classrooms cl ON cl.id = s.classroom_id"
        . $ucsWhereSql
    );
    $ucsCountStmt->execute($ucsParams);
    $ucsTotal = (int) $ucsCountStmt->fetchColumn();

    $ucsTotalPages = max(1, (int) ceil($ucsTotal / $ucsShow));
    $ucsPage       = min($ucsPage, $ucsTotalPages);
    $ucsOffset     = ($ucsPage - 1) * $ucsShow;

    $ucsStmt = $pdo->prepare(
        "SELECT ap.id, ap.current_job, ap.company, ap.professional_field,
                ap.verification_status, ap.created_at,
                s.id AS student_id, s.name AS student_name,
                s.student_id AS student_code, s.roll_number,
                s.graduation_year, s.email,
                m.name AS major_name, cl.classroom_name
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         LEFT JOIN classrooms cl ON cl.id = s.classroom_id
         LEFT JOIN majors m ON m.id = cl.major_id"
        . $ucsWhereSql
        . " ORDER BY ap.created_at ASC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($ucsParams as $ucsKey => $ucsVal) {
        $ucsStmt->bindValue($ucsKey, $ucsVal);
    }
    $ucsStmt->bindValue(':limit', $ucsShow, PDO::PARAM_INT);
    $ucsStmt->bindValue(':offset', $ucsOffset, PDO::PARAM_INT);
    $ucsStmt->execute();
    $ucsProfiles = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsProfiles   = [];
    $ucsTotal      = 0;
    $ucsTotalPages = 1;
}

$ucsHasFilters = $ucsQuery !== '' || $ucsGraduationYear > 0 || $ucsMajorId > 0;
$ucsResultLabel = 'Showing ' . $ucsTotal . ' pending application' . ($ucsTotal !== 1 ? 's' : '');
if ($ucsTotalPages > 1) {
    $ucsResultLabel .= ' · Page ' . $ucsPage . ' of ' . $ucsTotalPages;
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

<!-- Back link + header -->
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/index.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-blue-600 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 18-6-6 6-6"></path>
        </svg>
        Back to Alumni
    </a>
</div>

<!-- Search + filters -->
<div class="mb-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/applications.php'); ?>">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="q" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Search</label>
                <div class="relative mt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="search" id="q" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search by name, roll number, job…"
                           class="h-full w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
            </div>
            <div>
                <label for="graduation_year" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Graduation Year</label>
                <select id="graduation_year" name="graduation_year"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Years</option>
                    <?php foreach ($ucsYearOptions as $ucsYear): ?>
                        <option value="<?php echo (int) $ucsYear['graduation_year']; ?>" <?php echo $ucsGraduationYear === (int) $ucsYear['graduation_year'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $ucsYear['graduation_year']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="major" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Major</label>
                <select id="major" name="major"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Majors</option>
                    <?php foreach ($ucsMajorOptions as $ucsMajor): ?>
                        <option value="<?php echo (int) $ucsMajor['id']; ?>" <?php echo $ucsMajorId === (int) $ucsMajor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $ucsMajor['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                    <?php echo $ucsTotal; ?> pending
                </span>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($ucsHasFilters): ?>
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/applications.php'); ?>"
                       class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                        Reset Filters
                    </a>
                <?php endif; ?>
                <button type="submit"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
                    Search
                </button>
            </div>
        </div>
    </form>
</div>

<?php if (empty($ucsProfiles)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">
            <?php echo $ucsHasFilters ? 'No matching applications' : 'No pending applications'; ?>
        </h3>
        <p class="mt-2 text-sm text-gray-500">
            <?php echo $ucsHasFilters ? 'Try adjusting your search or filter criteria.' : 'All alumni applications have been reviewed.'; ?>
        </p>
        <?php if ($ucsHasFilters): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/applications.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                Clear Filters
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="mb-4 text-sm text-gray-600"><?php echo htmlspecialchars($ucsResultLabel); ?></p>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-5 py-3.5">Applicant</th>
                    <th class="px-4 py-3.5">Major</th>
                    <th class="px-4 py-3.5">Class of</th>
                    <th class="px-4 py-3.5">Occupation</th>
                    <th class="px-4 py-3.5">Submitted</th>
                    <th class="px-4 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($ucsProfiles as $ucsProfile): ?>
                    <?php
                    $ucsProfileName = (string) $ucsProfile['student_name'];
                    $ucsJsName      = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsProfileName);
                    ?>
                    <tr class="align-top transition-colors hover:bg-gray-50/60">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 ring-1 ring-amber-100" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900"><?php echo htmlspecialchars($ucsProfileName); ?></p>
                                    <p class="truncate text-[11px] text-gray-500"><?php echo htmlspecialchars((string) $ucsProfile['roll_number']); ?> &middot; <?php echo htmlspecialchars((string) $ucsProfile['email']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 truncate">
                            <?php echo htmlspecialchars((string) $ucsProfile['major_name']); ?>
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex rounded bg-gray-100 px-2 py-0.5 font-mono text-xs font-semibold text-gray-800 ring-1 ring-gray-200"><?php echo htmlspecialchars((string) $ucsProfile['graduation_year']); ?></span>
                        </td>
                        <td class="px-4 py-4 truncate">
                            <?php if (!empty($ucsProfile['current_job']) || !empty($ucsProfile['company'])): ?>
                                <p class="truncate font-medium text-gray-800"><?php echo htmlspecialchars((string) $ucsProfile['current_job']); ?></p>
                                <p class="truncate text-[11px] text-gray-500"><?php echo htmlspecialchars((string) $ucsProfile['company']); ?></p>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-xs text-gray-500">
                            <?php echo htmlspecialchars(date('M j, Y', strtotime((string) $ucsProfile['created_at']))); ?>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/view.php?id=' . (int) $ucsProfile['id']); ?>" title="Review <?php echo htmlspecialchars($ucsProfileName); ?>"
                                   class="inline-flex h-7 w-7 items-center justify-center rounded bg-blue-50 text-blue-600 transition-colors hover:bg-blue-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-verify.php'); ?>" class="inline-flex">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsProfile['id']; ?>">
                                    <button type="submit" title="Verify <?php echo htmlspecialchars($ucsProfileName); ?>"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded bg-emerald-50 text-emerald-600 transition-colors hover:bg-emerald-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </button>
                                </form>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-reject.php'); ?>" class="inline-flex"
                                      onsubmit="return confirm('Reject application from &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsProfile['id']; ?>">
                                    <button type="submit" title="Reject <?php echo htmlspecialchars($ucsProfileName); ?>"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded bg-red-50 text-red-600 transition-colors hover:bg-red-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($ucsTotalPages > 1): ?>
        <?php
        $ucsFilterParams = [];
        if ($ucsQuery !== '')          { $ucsFilterParams['q'] = $ucsQuery; }
        if ($ucsGraduationYear > 0)    { $ucsFilterParams['graduation_year'] = $ucsGraduationYear; }
        if ($ucsMajorId > 0)           { $ucsFilterParams['major'] = $ucsMajorId; }
        $ucsFilterParams['show'] = $ucsShow;

        $ucsPageLinks = [];
        for ($ucsLinkPage = 1; $ucsLinkPage <= $ucsTotalPages; $ucsLinkPage++) {
            if ($ucsTotalPages > 7 && $ucsLinkPage > 1 && $ucsLinkPage < $ucsTotalPages && abs($ucsLinkPage - $ucsPage) > 2) {
                if (!in_array('ellipsis', $ucsPageLinks, true) && end($ucsPageLinks) !== 'ellipsis') {
                    $ucsPageLinks[] = 'ellipsis';
                }
                continue;
            }
            $ucsPageLinks[] = $ucsLinkPage;
        }

        $ucsPrevUrl = ROOT_URL . '/admin/alumni/applications.php';
        if ($ucsPage > 1) {
            $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
        }
        $ucsNextUrl = ROOT_URL . '/admin/alumni/applications.php';
        if ($ucsPage < $ucsTotalPages) {
            $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
        }
        $ucsPageLinkUrl = ROOT_URL . '/admin/alumni/applications.php?';
        ?>
        <nav class="mt-6 flex flex-wrap items-center justify-between gap-3" aria-label="Applications pagination">
            <a href="<?php echo htmlspecialchars($ucsPrevUrl); ?>"
               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?>">
                Previous
            </a>
            <div class="flex flex-wrap items-center gap-1">
                <?php foreach ($ucsPageLinks as $ucsLinkPage): ?>
                    <?php if ($ucsLinkPage === 'ellipsis'): ?>
                        <span class="px-1.5 text-sm text-gray-400">…</span>
                    <?php else: ?>
                        <?php if ($ucsLinkPage === $ucsPage): ?>
                            <span aria-current="page" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold text-white"><?php echo $ucsLinkPage; ?></span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($ucsPageLinkUrl . http_build_query(array_merge(['page' => $ucsLinkPage], $ucsFilterParams))); ?>"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50"><?php echo $ucsLinkPage; ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <a href="<?php echo htmlspecialchars($ucsNextUrl); ?>"
               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage >= $ucsTotalPages ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?>">
                Next
            </a>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
