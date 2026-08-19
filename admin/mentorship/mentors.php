<?php
/**
 * Admin Alumni Mentorship module - mentor management.
 *
 * Lists verified alumni who participate in mentorship (are available,
 * suspended, or have selected mentorship areas) so admins can suspend or
 * restore an individual mentor's mentorship access.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';

admin_require_login();

$pageTitle    = 'Mentorship Mentors';
$pageSubtitle = 'Manage alumni mentorship access and availability.';
$activeNav    = 'mentorship';

$ucsFlash = $_SESSION['mentorship_flash'] ?? null;
unset($_SESSION['mentorship_flash']);

// --- Filters -----------------------------------------------------------------
$ucsQuery = trim((string) ($_GET['q'] ?? ''));
$ucsShow  = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage  = max(1, (int) ($_GET['page'] ?? 1));

$ucsWhere  = ['(ap.mentorship_available = 1 OR ap.mentorship_suspended = 1 OR EXISTS (
                  SELECT 1 FROM alumni_mentorship_areas ama WHERE ama.alumni_profile_id = ap.id
              ))'];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[] = '(s.name LIKE :q OR ap.current_job LIKE :q OR ap.company LIKE :q)';
    $ucsParams[':q'] = '%' . $ucsEscaped . '%';
}

$ucsWhereSql = ' WHERE ' . implode(' AND ', $ucsWhere);

$ucsMentors  = [];
$ucsTotal     = 0;
$ucsTotalPages = 1;
try {
    $ucsCountStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id"
        . $ucsWhereSql
    );
    $ucsCountStmt->execute($ucsParams);
    $ucsTotal = (int) $ucsCountStmt->fetchColumn();

    $ucsTotalPages = max(1, (int) ceil($ucsTotal / $ucsShow));
    $ucsPage       = min($ucsPage, $ucsTotalPages);
    $ucsOffset     = ($ucsPage - 1) * $ucsShow;

    $ucsStmt = $pdo->prepare(
        "SELECT ap.id, ap.current_job, ap.company, ap.mentorship_available,
                ap.mentorship_suspended,
                s.name AS student_name,
                (SELECT COUNT(*) FROM mentorship_requests mr
                  WHERE mr.alumni_profile_id = ap.id) AS request_count,
                (SELECT GROUP_CONCAT(a.name ORDER BY a.sort_order ASC SEPARATOR ', ')
                   FROM alumni_mentorship_areas ama
                   JOIN mentorship_areas a ON a.id = ama.mentorship_area_id
                  WHERE ama.alumni_profile_id = ap.id AND a.status = 'active') AS areas
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id"
        . $ucsWhereSql
        . " ORDER BY ap.mentorship_suspended ASC, ap.mentorship_available DESC, s.name ASC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($ucsParams as $ucsKey => $ucsVal) {
        $ucsStmt->bindValue($ucsKey, $ucsVal);
    }
    $ucsStmt->bindValue(':limit', $ucsShow, PDO::PARAM_INT);
    $ucsStmt->bindValue(':offset', $ucsOffset, PDO::PARAM_INT);
    $ucsStmt->execute();
    $ucsMentors = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsMentors   = [];
    $ucsTotal     = 0;
    $ucsTotalPages = 1;
}

$ucsHasActiveFilters = $ucsQuery !== '';

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

<!-- Search + shortcuts -->
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/mentors.php'); ?>" class="flex w-full max-w-xl items-stretch" role="search">
        <div class="relative flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search mentors…" aria-label="Search mentors"
                   class="h-full w-full rounded-l-xl border border-r-0 border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>
        <button type="submit"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-r-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            Search
        </button>
    </form>

    <div class="flex shrink-0 items-center gap-2">
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/index.php'); ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
            Requests
        </a>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/reports.php'); ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
            Reports
            <?php if (mentorship_open_report_count($pdo) > 0): ?>
                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-[11px] font-bold text-white"><?php echo mentorship_open_report_count($pdo); ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<div class="mb-4 flex items-center gap-2 text-sm text-gray-600">
    <span class="font-semibold text-gray-500">Sort:</span>
    <span class="text-gray-700">Active mentors first, then suspended</span>
</div>

<?php if (empty($ucsMentors)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
            <path d="M22 10v6"></path>
            <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">
            <?php echo $ucsHasActiveFilters ? 'No matching mentors' : 'No mentors yet'; ?>
        </h3>
        <p class="mt-2 text-sm text-gray-500">
            <?php echo $ucsHasActiveFilters ? 'Try adjusting your search.' : 'Alumni appear here once they select mentorship areas or turn on availability.'; ?>
        </p>
    </div>
<?php else: ?>
    <p class="mb-4 text-sm text-gray-600">
        Showing <span class="font-semibold text-gray-900"><?php echo $ucsTotal; ?></span>
        <?php echo $ucsTotal === 1 ? 'mentor' : 'mentors'; ?>
        <?php if ($ucsTotalPages > 1): ?>
            &middot; Page <span class="font-semibold text-gray-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-gray-900"><?php echo $ucsTotalPages; ?></span>
        <?php endif; ?>
    </p>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-5 py-3.5">Alumnus</th>
                    <th class="px-4 py-3.5">Mentorship Areas</th>
                    <th class="px-4 py-3.5 text-center">Requests</th>
                    <th class="px-4 py-3.5">Status</th>
                    <th class="px-4 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($ucsMentors as $ucsMentor): ?>
                    <?php
                    $ucsSuspended   = (int) $ucsMentor['mentorship_suspended'] === 1;
                    $ucsAvailable   = (int) $ucsMentor['mentorship_available'] === 1;
                    $ucsJob         = trim((string) ($ucsMentor['current_job'] ?? '') . ($ucsMentor['company'] !== '' && $ucsMentor['current_job'] !== '' ? ' · ' : '') . (string) ($ucsMentor['company'] ?? ''));
                    $ucsMentorUrl   = BASE_URL . '/alumni-details.php?id=' . (int) $ucsMentor['id'];
                    $ucsJsName      = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $ucsMentor['student_name']);
                    ?>
                    <tr class="align-top transition-colors hover:bg-gray-50/60">
                        <td class="max-w-xs px-5 py-4">
                            <a href="<?php echo htmlspecialchars($ucsMentorUrl); ?>" target="_blank" rel="noopener"
                               class="font-semibold text-gray-900 transition-colors duration-150 hover:text-blue-700">
                                <?php echo htmlspecialchars((string) $ucsMentor['student_name']); ?>
                            </a>
                            <?php if ($ucsJob !== ''): ?>
                                <p class="mt-1 truncate text-xs leading-5 text-gray-500"><?php echo htmlspecialchars($ucsJob); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="max-w-[280px] px-4 py-4">
                            <?php if (!empty($ucsMentor['areas'])): ?>
                                <p class="text-xs leading-5 text-gray-600"><?php echo htmlspecialchars((string) $ucsMentor['areas']); ?></p>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">No areas selected</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-700"><?php echo (int) $ucsMentor['request_count']; ?></span>
                        </td>
                        <td class="px-4 py-4">
                            <?php if ($ucsSuspended): ?>
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-red-700">Suspended</span>
                            <?php elseif ($ucsAvailable): ?>
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">Available</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Off</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap items-center justify-end gap-1">
                                <a href="<?php echo htmlspecialchars($ucsMentorUrl); ?>" target="_blank" rel="noopener" title="View profile"
                                   class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-blue-600 transition-colors duration-150 hover:bg-blue-50">
                                    View
                                </a>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/mentorship-suspend.php'); ?>" class="inline-flex"
                                      <?php if (!$ucsSuspended): ?>onsubmit="return confirm('Suspend mentorship for &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? Pending requests will be declined and open reports resolved.');"<?php endif; ?>>
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsMentor['id']; ?>">
                                    <input type="hidden" name="suspended" value="<?php echo $ucsSuspended ? '0' : '1'; ?>">
                                    <button type="submit" title="<?php echo $ucsSuspended ? 'Restore mentorship access' : 'Suspend mentorship access'; ?>"
                                            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold <?php echo $ucsSuspended ? 'text-emerald-600 transition-colors duration-150 hover:bg-emerald-50' : 'text-red-600 transition-colors duration-150 hover:bg-red-50'; ?>">
                                        <?php echo $ucsSuspended ? 'Restore' : 'Suspend'; ?>
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
        if ($ucsQuery !== '')  { $ucsFilterParams['q'] = $ucsQuery; }
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

        $ucsPrevUrl = ROOT_URL . '/admin/mentorship/mentors.php';
        if ($ucsPage > 1) {
            $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
        }
        $ucsNextUrl = ROOT_URL . '/admin/mentorship/mentors.php';
        if ($ucsPage < $ucsTotalPages) {
            $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
        }
        $ucsPageLinkUrl = ROOT_URL . '/admin/mentorship/mentors.php?';
        ?>
        <nav class="mt-6 flex flex-wrap items-center justify-between gap-3" aria-label="Mentors pagination">
            <a href="<?php echo htmlspecialchars($ucsPrevUrl); ?>"
               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?> focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
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
               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage >= $ucsTotalPages ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?> focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Next
            </a>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>