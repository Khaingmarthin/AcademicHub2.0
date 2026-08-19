<?php
/**
 * Admin Alumni Mentorship module - list / moderation of requests.
 *
 * Shows every mentorship request (all statuses) so admins can review who is
 * mentoring whom. Mentors with suspended mentorship access are flagged, and
 * open reports are surfaced via a shortcut to the reports page.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';

admin_require_login();

$pageTitle    = 'Mentorship Requests';
$pageSubtitle = 'Review mentorship requests between students and alumni.';
$activeNav    = 'mentorship';

$ucsFlash = $_SESSION['mentorship_flash'] ?? null;
unset($_SESSION['mentorship_flash']);

// --- Filters -----------------------------------------------------------------
$ucsQuery = trim((string) ($_GET['q'] ?? ''));
$ucsStatus = in_array((string) ($_GET['status'] ?? ''), MENTORSHIP_REQUEST_STATUSES, true)
    ? (string) $_GET['status']
    : '';
$ucsShow = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage = max(1, (int) ($_GET['page'] ?? 1));

$ucsWhere  = [];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[] = '(s.name LIKE :q OR ms.name LIKE :q OR mr.message LIKE :q)';
    $ucsParams[':q'] = '%' . $ucsEscaped . '%';
}
if ($ucsStatus !== '') {
    $ucsWhere[]   = 'mr.status = :status';
    $ucsParams[':status'] = $ucsStatus;
}

$ucsWhereSql = count($ucsWhere) > 0 ? ' WHERE ' . implode(' AND ', $ucsWhere) : '';

$ucsRequests  = [];
$ucsTotal     = 0;
$ucsTotalPages = 1;
try {
    $ucsCountStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM mentorship_requests mr
         JOIN students s  ON s.id = mr.student_id
         JOIN alumni_profiles ap ON ap.id = mr.alumni_profile_id
         JOIN students ms ON ms.id = ap.student_id"
        . $ucsWhereSql
    );
    $ucsCountStmt->execute($ucsParams);
    $ucsTotal = (int) $ucsCountStmt->fetchColumn();

    $ucsTotalPages = max(1, (int) ceil($ucsTotal / $ucsShow));
    $ucsPage       = min($ucsPage, $ucsTotalPages);
    $ucsOffset     = ($ucsPage - 1) * $ucsShow;

    $ucsStmt = $pdo->prepare(
        "SELECT mr.id, mr.message, mr.status, mr.created_at,
                s.name AS student_name,
                ms.name AS mentor_name,
                ap.id AS alumni_profile_id, ap.mentorship_suspended
         FROM mentorship_requests mr
         JOIN students s  ON s.id = mr.student_id
         JOIN alumni_profiles ap ON ap.id = mr.alumni_profile_id
         JOIN students ms ON ms.id = ap.student_id"
        . $ucsWhereSql
        . " ORDER BY mr.created_at DESC, mr.id DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($ucsParams as $ucsKey => $ucsVal) {
        $ucsStmt->bindValue($ucsKey, $ucsVal);
    }
    $ucsStmt->bindValue(':limit', $ucsShow, PDO::PARAM_INT);
    $ucsStmt->bindValue(':offset', $ucsOffset, PDO::PARAM_INT);
    $ucsStmt->execute();
    $ucsRequests = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsRequests  = [];
    $ucsTotal     = 0;
    $ucsTotalPages = 1;
}

$ucsHasActiveFilters = $ucsQuery !== '' || $ucsStatus !== '';

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
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/index.php'); ?>" class="flex w-full max-w-xl items-stretch" role="search">
        <div class="relative flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search students, mentors, messages…" aria-label="Search mentorship requests"
                   class="h-full w-full rounded-l-xl border border-r-0 border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>
        <button type="submit"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-r-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            Search
        </button>
    </form>

    <div class="flex shrink-0 items-center gap-2">
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/mentors.php'); ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
            Mentors
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

<!-- Advanced filters -->
<div class="mb-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/index.php'); ?>">
        <input type="hidden" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>">

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                <select id="status" name="status"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $ucsStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="accepted" <?php echo $ucsStatus === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                    <option value="declined" <?php echo $ucsStatus === 'declined' ? 'selected' : ''; ?>>Declined</option>
                    <option value="completed" <?php echo $ucsStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div>
                <label for="show" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Show</label>
                <select id="show" name="show"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <?php foreach ([10, 25, 50] as $ucsShowOption): ?>
                        <option value="<?php echo $ucsShowOption; ?>" <?php echo $ucsShow === $ucsShowOption ? 'selected' : ''; ?>>
                            <?php echo $ucsShowOption; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <span class="font-semibold text-gray-500">Sort:</span>
                <span class="text-gray-700">Newest first</span>
            </div>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/index.php'); ?>"
               class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                Reset Filters
            </a>
        </div>
    </form>
</div>

<?php if (empty($ucsRequests)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">
            <?php echo $ucsHasActiveFilters ? 'No matching mentorship requests' : 'No mentorship requests yet'; ?>
        </h3>
        <p class="mt-2 text-sm text-gray-500">
            <?php echo $ucsHasActiveFilters ? 'Try adjusting or resetting your filters.' : 'Requests appear here when students ask verified alumni for mentorship.'; ?>
        </p>
        <?php if ($ucsHasActiveFilters): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                Clear Filters
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="mb-4 text-sm text-gray-600">
        Showing <span class="font-semibold text-gray-900"><?php echo $ucsTotal; ?></span>
        <?php echo $ucsTotal === 1 ? 'request' : 'requests'; ?>
        <?php if ($ucsTotalPages > 1): ?>
            &middot; Page <span class="font-semibold text-gray-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-gray-900"><?php echo $ucsTotalPages; ?></span>
        <?php endif; ?>
    </p>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-5 py-3.5">Request</th>
                    <th class="px-4 py-3.5">Student</th>
                    <th class="px-4 py-3.5">Mentor</th>
                    <th class="px-4 py-3.5">Status</th>
                    <th class="px-4 py-3.5">Created</th>
                    <th class="px-4 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($ucsRequests as $ucsRequest): ?>
                    <?php
                    $ucsStatus = (string) $ucsRequest['status'];
                    $ucsExcerpt = trim((string) strip_tags((string) $ucsRequest['message']));
                    if (mb_strlen($ucsExcerpt) > 90) {
                        $ucsExcerpt = mb_substr($ucsExcerpt, 0, 90) . '…';
                    }
                    $ucsMentorUrl = BASE_URL . '/alumni-details.php?id=' . (int) $ucsRequest['alumni_profile_id'];
                    ?>
                    <tr class="align-top transition-colors hover:bg-gray-50/60">
                        <td class="max-w-xs px-5 py-4">
                            <p class="font-semibold text-gray-900">#<?php echo (int) $ucsRequest['id']; ?></p>
                            <?php if ($ucsExcerpt !== ''): ?>
                                <p class="mt-1 text-xs leading-5 text-gray-500"><?php echo htmlspecialchars($ucsExcerpt); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-700"><?php echo htmlspecialchars((string) $ucsRequest['student_name']); ?></p>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-700"><?php echo htmlspecialchars((string) $ucsRequest['mentor_name']); ?></p>
                            <?php if ((int) $ucsRequest['mentorship_suspended'] === 1): ?>
                                <span class="mt-1 inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700 ring-1 ring-red-100">Suspended</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <?php if ($ucsStatus === 'pending'): ?>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">Pending</span>
                            <?php elseif ($ucsStatus === 'accepted'): ?>
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">Accepted</span>
                            <?php elseif ($ucsStatus === 'declined'): ?>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Declined</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-blue-700">Completed</span>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-xs text-gray-500">
                            <?php echo htmlspecialchars(date('M j, Y', strtotime((string) $ucsRequest['created_at']))); ?>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap items-center justify-end gap-1">
                                <a href="<?php echo htmlspecialchars($ucsMentorUrl); ?>" target="_blank" rel="noopener" title="View mentor profile"
                                   class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-blue-600 transition-colors duration-150 hover:bg-blue-50">
                                    View Mentor
                                </a>
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
        if ($ucsQuery !== '')   { $ucsFilterParams['q'] = $ucsQuery; }
        if ($ucsStatus !== '')  { $ucsFilterParams['status'] = $ucsStatus; }
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

        $ucsPrevUrl = ROOT_URL . '/admin/mentorship/index.php';
        if ($ucsPage > 1) {
            $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
        }
        $ucsNextUrl = ROOT_URL . '/admin/mentorship/index.php';
        if ($ucsPage < $ucsTotalPages) {
            $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
        }
        $ucsPageLinkUrl = ROOT_URL . '/admin/mentorship/index.php?';
        ?>
        <nav class="mt-6 flex flex-wrap items-center justify-between gap-3" aria-label="Mentorship requests pagination">
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