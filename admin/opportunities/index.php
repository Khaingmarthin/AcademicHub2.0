<?php
/**
 * Admin Career Opportunities module - list.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/opportunity-validation.php';

admin_require_login();

$pageTitle    = 'Career Opportunities';
$pageSubtitle = 'Moderate career opportunities shared by verified alumni.';
$activeNav    = 'opportunities';

$ucsFlash = $_SESSION['opportunity_flash'] ?? null;
unset($_SESSION['opportunity_flash']);

// --- Filters -----------------------------------------------------------------
$ucsQuery      = trim((string) ($_GET['q'] ?? ''));
$ucsEmployment = in_array((string) ($_GET['type'] ?? ''), OPPORTUNITY_EMPLOYMENT_TYPES, true) ? (string) $_GET['type'] : '';
$ucsStatus     = in_array((string) ($_GET['status'] ?? ''), ['active', 'hidden', 'expired'], true) ? (string) $_GET['status'] : '';
$ucsSort       = (string) ($_GET['sort'] ?? '') === 'oldest' ? 'oldest' : 'newest';
$ucsShow       = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage       = max(1, (int) ($_GET['page'] ?? 1));

$ucsWhere  = [];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped    = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[]    = '(o.title LIKE :q OR o.company LIKE :q OR o.location LIKE :q OR s.name LIKE :q)';
    $ucsParams[':q'] = '%' . $ucsEscaped . '%';
}
if ($ucsEmployment !== '') {
    $ucsWhere[]               = 'o.employment_type = :employment_type';
    $ucsParams[':employment_type'] = $ucsEmployment;
}
if ($ucsStatus === 'expired') {
    $ucsWhere[] = "o.status = 'active' AND o.expires_at IS NOT NULL AND o.expires_at < CURDATE()";
} elseif ($ucsStatus !== '') {
    $ucsWhere[] = 'o.status = :status';
    $ucsParams[':status'] = $ucsStatus;
}

$ucsWhereSql = count($ucsWhere) > 0 ? ' WHERE ' . implode(' AND ', $ucsWhere) : '';

$ucsOpportunities = [];
$ucsTotal         = 0;
$ucsTotalPages    = 1;
try {
    $ucsCountStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM career_opportunities o
         JOIN students s ON s.id = o.posted_by_student_id"
        . $ucsWhereSql
    );
    $ucsCountStmt->execute($ucsParams);
    $ucsTotal = (int) $ucsCountStmt->fetchColumn();

    $ucsTotalPages = max(1, (int) ceil($ucsTotal / $ucsShow));
    $ucsPage       = min($ucsPage, $ucsTotalPages);
    $ucsOffset     = ($ucsPage - 1) * $ucsShow;
    $ucsOrderBy    = $ucsSort === 'oldest' ? 'o.created_at ASC' : 'o.created_at DESC';

    $ucsStmt = $pdo->prepare(
        "SELECT o.id, o.title, o.company, o.location, o.employment_type,
                o.expires_at, o.status, o.created_at,
                s.name AS poster_name
         FROM career_opportunities o
         JOIN students s ON s.id = o.posted_by_student_id"
        . $ucsWhereSql
        . " ORDER BY $ucsOrderBy
         LIMIT :limit OFFSET :offset"
    );
    foreach ($ucsParams as $ucsKey => $ucsVal) {
        $ucsStmt->bindValue($ucsKey, $ucsVal);
    }
    $ucsStmt->bindValue(':limit', $ucsShow, PDO::PARAM_INT);
    $ucsStmt->bindValue(':offset', $ucsOffset, PDO::PARAM_INT);
    $ucsStmt->execute();
    $ucsOpportunities = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsOpportunities = [];
    $ucsTotal         = 0;
    $ucsTotalPages    = 1;
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

<!-- Advanced filters -->
<div class="mb-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/opportunities/index.php'); ?>">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search title, company, location, poster…" aria-label="Search opportunities"
                       class="w-full rounded-l-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <button type="submit"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                Search
            </button>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <label for="type" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Type</label>
                <select id="type" name="type"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Types</option>
                    <?php foreach (OPPORTUNITY_EMPLOYMENT_TYPES as $ucsType): ?>
                        <option value="<?php echo $ucsType; ?>" <?php echo $ucsEmployment === $ucsType ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(opportunity_employment_label($ucsType)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                <select id="status" name="status"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $ucsStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="expired" <?php echo $ucsStatus === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="hidden" <?php echo $ucsStatus === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                </select>
            </div>
            <div class="flex items-end gap-3">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <label for="sort" class="font-semibold text-gray-500">Sort:</label>
                    <select id="sort" name="sort"
                            class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="newest" <?php echo $ucsSort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $ucsSort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/opportunities/index.php'); ?>"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<?php if (empty($ucsOpportunities)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">
            <?php echo $ucsQuery !== '' || $ucsEmployment !== '' || $ucsStatus !== '' ? 'No matching opportunities' : 'No career opportunities yet'; ?>
        </h3>
        <p class="mt-2 text-sm text-gray-500">
            <?php echo $ucsQuery !== '' || $ucsEmployment !== '' || $ucsStatus !== '' ? 'Try adjusting or resetting your filters.' : 'Opportunities appear here as verified alumni share them.'; ?>
        </p>
        <?php if ($ucsQuery !== '' || $ucsEmployment !== '' || $ucsStatus !== ''): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/opportunities/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">Clear Filters</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="mb-4 text-sm text-gray-600">
        Showing <span class="font-semibold text-gray-900"><?php echo $ucsTotal; ?></span>
        <?php echo $ucsTotal === 1 ? 'opportunity' : 'opportunities'; ?>
        <?php if ($ucsTotalPages > 1): ?>
            &middot; Page <span class="font-semibold text-gray-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-gray-900"><?php echo $ucsTotalPages; ?></span>
        <?php endif; ?>
    </p>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Opportunity</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Posted By</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Type</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Deadline</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($ucsOpportunities as $ucsOpportunity): ?>
                        <?php
                        $ucsPublic = opportunity_is_public($ucsOpportunity);
                        $ucsTitle  = (string) $ucsOpportunity['title'];
                        $ucsJsName = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsTitle);
                        ?>
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($ucsTitle); ?></p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    <?php echo htmlspecialchars((string) $ucsOpportunity['company']); ?>
                                    <?php if (!empty($ucsOpportunity['location'])): ?>
                                        &middot; <?php echo htmlspecialchars((string) $ucsOpportunity['location']); ?>
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600"><?php echo htmlspecialchars((string) $ucsOpportunity['poster_name']); ?></td>
                            <td class="px-5 py-4 text-sm text-gray-600"><?php echo htmlspecialchars(opportunity_employment_label((string) $ucsOpportunity['employment_type'])); ?></td>
                            <td class="px-5 py-4 text-sm text-gray-600"><?php echo htmlspecialchars(!empty($ucsOpportunity['expires_at']) ? date('M j, Y', strtotime((string) $ucsOpportunity['expires_at'])) : 'Open'); ?></td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 <?php echo $ucsPublic ? 'bg-green-50 text-green-700 ring-green-100' : ((string) $ucsOpportunity['status'] === 'hidden' ? 'bg-gray-100 text-gray-500 ring-gray-200' : 'bg-amber-50 text-amber-700 ring-amber-100'); ?>">
                                    <?php echo $ucsPublic ? 'Active' : (ucfirst((string) $ucsOpportunity['status']) === 'Hidden' ? 'Hidden' : 'Expired'); ?>
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/opportunities/edit.php?id=' . (int) $ucsOpportunity['id']); ?>" title="Edit"
                                       class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-amber-600 transition-colors duration-150 hover:bg-amber-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                            <path d="m15 5 4 4"></path>
                                        </svg>
                                        Edit
                                    </a>
                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/opportunity-toggle.php'); ?>" class="inline-flex">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsOpportunity['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo (string) $ucsOpportunity['status'] === 'hidden' ? 'active' : 'hidden'; ?>">
                                        <button type="submit" title="<?php echo (string) $ucsOpportunity['status'] === 'hidden' ? 'Restore' : 'Hide'; ?>"
                                                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-semibold <?php echo (string) $ucsOpportunity['status'] === 'hidden' ? 'text-blue-600 hover:bg-blue-50' : 'text-gray-600 hover:bg-gray-100'; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            <?php echo (string) $ucsOpportunity['status'] === 'hidden' ? 'Restore' : 'Hide'; ?>
                                        </button>
                                    </form>
                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/opportunity-delete.php'); ?>" class="inline-flex"
                                          onsubmit="return confirm('Delete this opportunity permanently? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsOpportunity['id']; ?>">
                                        <button type="submit" title="Delete"
                                                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-red-600 transition-colors duration-150 hover:bg-red-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18"></path>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
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
    </div>

    <?php if ($ucsTotalPages > 1): ?>
        <?php
        $ucsFilterParams = [];
        if ($ucsQuery !== '')      { $ucsFilterParams['q'] = $ucsQuery; }
        if ($ucsEmployment !== '') { $ucsFilterParams['type'] = $ucsEmployment; }
        if ($ucsStatus !== '')     { $ucsFilterParams['status'] = $ucsStatus; }
        $ucsFilterParams['sort'] = $ucsSort;
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

        $ucsPrevUrl = ROOT_URL . '/admin/opportunities/index.php';
        if ($ucsPage > 1) {
            $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
        }
        $ucsNextUrl = ROOT_URL . '/admin/opportunities/index.php';
        if ($ucsPage < $ucsTotalPages) {
            $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
        }
        $ucsPageLinkUrl = ROOT_URL . '/admin/opportunities/index.php?';
        ?>
        <nav class="mt-6 flex flex-wrap items-center justify-between gap-3" aria-label="Opportunities pagination">
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