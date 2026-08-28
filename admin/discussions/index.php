<?php
/**
 * Admin Career Discussions module - list / moderation.
 *
 * Lists every discussion (including hidden ones) so admins can moderate the
 * community: pin important discussions, close or reopen them, hide or
 * restore them, and delete them.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

admin_require_login();

$pageTitle    = 'Discussions';
$pageSubtitle = 'Moderate the career community discussions.';
$activeNav    = 'discussions';

$ucsFlash = $_SESSION['discussion_flash'] ?? null;
unset($_SESSION['discussion_flash']);

$ucsCategories = discussion_load_categories($pdo);

// --- Filters -----------------------------------------------------------------
$ucsQuery       = trim((string) ($_GET['q'] ?? ''));
$ucsCategoryId  = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT);
if ($ucsCategoryId === false) {
    $ucsCategoryId = null;
}
$ucsStatus  = in_array((string) ($_GET['status'] ?? ''), DISCUSSION_STATUSES, true) ? (string) $_GET['status'] : '';
$ucsPinned  = (string) ($_GET['pinned'] ?? '') === '1' ? '1' : '';
$ucsShow    = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage    = max(1, (int) ($_GET['page'] ?? 1));

$ucsWhere  = [];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[] = '(d.title LIKE :q OR d.content LIKE :q OR s.name LIKE :q)';
    $ucsParams[':q'] = '%' . $ucsEscaped . '%';
}
if ($ucsCategoryId !== null && $ucsCategoryId > 0) {
    $ucsWhere[]   = 'd.category_id = :category_id';
    $ucsParams[':category_id'] = $ucsCategoryId;
}
if ($ucsStatus !== '') {
    $ucsWhere[]   = 'd.status = :status';
    $ucsParams[':status'] = $ucsStatus;
}
if ($ucsPinned !== '') {
    $ucsWhere[]   = 'd.is_pinned = 1';
}

$ucsWhereSql = count($ucsWhere) > 0 ? ' WHERE ' . implode(' AND ', $ucsWhere) : '';

$ucsDiscussions  = [];
$ucsTotal        = 0;
$ucsTotalPages   = 1;
try {
    $ucsCountStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM discussions d
         JOIN discussion_categories c ON c.id = d.category_id
         JOIN students s ON s.id = d.author_student_id"
        . $ucsWhereSql
    );
    $ucsCountStmt->execute($ucsParams);
    $ucsTotal = (int) $ucsCountStmt->fetchColumn();

    $ucsTotalPages = max(1, (int) ceil($ucsTotal / $ucsShow));
    $ucsPage       = min($ucsPage, $ucsTotalPages);
    $ucsOffset     = ($ucsPage - 1) * $ucsShow;

    $ucsStmt = $pdo->prepare(
        "SELECT d.id, d.title, d.content, d.category_id, d.status,
                d.is_pinned, d.created_at,
                c.name AS category_name,
                s.name AS author_name,
                (ap.id IS NOT NULL) AS is_alumni,
                (SELECT COUNT(*) FROM discussion_replies r
                  WHERE r.discussion_id = d.id AND r.status = 'visible') AS reply_count
         FROM discussions d
         JOIN discussion_categories c ON c.id = d.category_id
         JOIN students s ON s.id = d.author_student_id
         LEFT JOIN alumni_profiles ap
                ON ap.student_id = s.id AND ap.verification_status = 'verified'"
        . $ucsWhereSql
        . " ORDER BY d.is_pinned DESC, d.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($ucsParams as $ucsKey => $ucsVal) {
        $ucsStmt->bindValue($ucsKey, $ucsVal);
    }
    $ucsStmt->bindValue(':limit', $ucsShow, PDO::PARAM_INT);
    $ucsStmt->bindValue(':offset', $ucsOffset, PDO::PARAM_INT);
    $ucsStmt->execute();
    $ucsDiscussions = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsDiscussions = [];
    $ucsTotal       = 0;
    $ucsTotalPages  = 1;
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

<!-- Search + shortcuts -->
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/index.php'); ?>" class="flex w-full max-w-xl items-stretch" role="search">
        <div class="relative flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search discussions…" aria-label="Search discussions"
                   class="h-full w-full rounded-l-xl border border-r-0 border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>
        <button type="submit"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-r-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            Search
        </button>
    </form>

    <div class="flex shrink-0 items-center gap-2">
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/categories/index.php'); ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
            Categories
        </a>
    </div>
</div>

<!-- Advanced filters -->
<div class="mb-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/index.php'); ?>">
        <input type="hidden" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>">

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="category_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Category</label>
                <select id="category_id" name="category_id"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Categories</option>
                    <?php foreach ($ucsCategories as $ucsCategory): ?>
                        <option value="<?php echo (int) $ucsCategory['id']; ?>" <?php echo $ucsCategoryId !== null && (int) $ucsCategoryId === (int) $ucsCategory['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ucsCategory['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                <select id="status" name="status"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Statuses</option>
                    <option value="open" <?php echo $ucsStatus === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="closed" <?php echo $ucsStatus === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    <option value="hidden" <?php echo $ucsStatus === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                </select>
            </div>
            <div>
                <label for="pinned" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Pinned</label>
                <select id="pinned" name="pinned"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All</option>
                    <option value="1" <?php echo $ucsPinned === '1' ? 'selected' : ''; ?>>Pinned Only</option>
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
                <span class="text-gray-700">Pinned first, then newest</span>
            </div>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/index.php'); ?>"
               class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                Reset Filters
            </a>
        </div>
    </form>
</div>

<?php if (empty($ucsDiscussions)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">
            <?php echo $ucsQuery !== '' || $ucsCategoryId !== null || $ucsStatus !== '' || $ucsPinned !== '' ? 'No matching discussions' : 'No discussions yet'; ?>
        </h3>
        <p class="mt-2 text-sm text-gray-500">
            <?php echo $ucsQuery !== '' || $ucsCategoryId !== null || $ucsStatus !== '' || $ucsPinned !== '' ? 'Try adjusting or resetting your filters.' : 'Discussions appear here when students and alumni ask career questions.'; ?>
        </p>
        <?php if ($ucsQuery !== '' || $ucsCategoryId !== null || $ucsStatus !== '' || $ucsPinned !== ''): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                Clear Filters
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="mb-4 text-sm text-gray-600">
        Showing <span class="font-semibold text-gray-900"><?php echo $ucsTotal; ?></span>
        <?php echo $ucsTotal === 1 ? 'discussion' : 'discussions'; ?>
        <?php if ($ucsTotalPages > 1): ?>
            &middot; Page <span class="font-semibold text-gray-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-gray-900"><?php echo $ucsTotalPages; ?></span>
        <?php endif; ?>
    </p>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-5 py-3.5">Discussion</th>
                    <th class="px-4 py-3.5">Category</th>
                    <th class="px-4 py-3.5">Author</th>
                    <th class="px-4 py-3.5 text-center">Replies</th>
                    <th class="px-4 py-3.5">Status</th>
                    <th class="px-4 py-3.5">Created</th>
                    <th class="px-4 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($ucsDiscussions as $ucsDiscussion): ?>
                    <?php
                    $ucsDiscussionTitle = (string) $ucsDiscussion['title'];
                    $ucsJsName          = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsDiscussionTitle);
                    $ucsDiscussionStatus = (string) $ucsDiscussion['status'];
                    $ucsExcerpt         = trim((string) strip_tags((string) $ucsDiscussion['content']));
                    if (strlen($ucsExcerpt) > 90) {
                        $ucsExcerpt = substr($ucsExcerpt, 0, 90) . '…';
                    }
                    $ucsAdminViewUrl = ROOT_URL . '/admin/discussions/view.php?id=' . (int) $ucsDiscussion['id'];
                    ?>
                    <tr class="align-top transition-colors hover:bg-gray-50/60">
                        <td class="max-w-xs px-5 py-4">
                            <div class="flex items-start gap-1.5">
                                <?php if ((int) $ucsDiscussion['is_pinned'] === 1): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 17v5M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1z"></path>
                                    </svg>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars($ucsAdminViewUrl); ?>"
                                   class="font-semibold text-gray-900 transition-colors duration-150 hover:text-blue-700">
                                    <?php echo htmlspecialchars($ucsDiscussionTitle); ?>
                                </a>
                            </div>
                            <?php if ($ucsExcerpt !== ''): ?>
                                <p class="mt-1 text-xs leading-5 text-gray-500"><?php echo htmlspecialchars($ucsExcerpt); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                <?php echo htmlspecialchars($ucsDiscussion['category_name']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-700"><?php echo htmlspecialchars((string) $ucsDiscussion['author_name']); ?></p>
                            <?php if ((int) $ucsDiscussion['is_alumni'] === 1): ?>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">Alumni</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-700"><?php echo (int) $ucsDiscussion['reply_count']; ?></span>
                        </td>
                        <td class="px-4 py-4">
                            <?php if ($ucsDiscussionStatus === 'open'): ?>
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">Open</span>
                            <?php elseif ($ucsDiscussionStatus === 'closed'): ?>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Closed</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-xs text-gray-500">
                            <?php echo htmlspecialchars(date('M j, Y', strtotime((string) $ucsDiscussion['created_at']))); ?>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap items-center justify-end gap-1">
                                <a href="<?php echo htmlspecialchars($ucsAdminViewUrl); ?>" title="View <?php echo htmlspecialchars($ucsDiscussionTitle); ?>"
                                   class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-blue-600 transition-colors duration-150 hover:bg-blue-50">
                                    View
                                </a>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-pin.php'); ?>" class="inline-flex">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                                    <input type="hidden" name="pinned" value="<?php echo (int) $ucsDiscussion['is_pinned'] === 1 ? '0' : '1'; ?>">
                                    <button type="submit" title="<?php echo (int) $ucsDiscussion['is_pinned'] === 1 ? 'Unpin' : 'Pin'; ?> discussion"
                                            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-indigo-600 transition-colors duration-150 hover:bg-indigo-50">
                                        <?php echo (int) $ucsDiscussion['is_pinned'] === 1 ? 'Unpin' : 'Pin'; ?>
                                    </button>
                                </form>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-status.php'); ?>" class="inline-flex">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                                    <?php if ($ucsDiscussionStatus === 'open'): ?>
                                        <input type="hidden" name="action" value="close">
                                        <button type="submit" title="Close this discussion"
                                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-gray-600 transition-colors duration-150 hover:bg-gray-100">Close</button>
                                    <?php elseif ($ucsDiscussionStatus === 'closed'): ?>
                                        <input type="hidden" name="action" value="open">
                                        <button type="submit" title="Reopen this discussion"
                                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-emerald-600 transition-colors duration-150 hover:bg-emerald-50">Reopen</button>
                                    <?php endif; ?>
                                </form>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-status.php'); ?>" class="inline-flex">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                                    <?php if ($ucsDiscussionStatus === 'hidden'): ?>
                                        <input type="hidden" name="action" value="open">
                                        <button type="submit" title="Restore this discussion"
                                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-emerald-600 transition-colors duration-150 hover:bg-emerald-50">Restore</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="hidden">
                                        <button type="submit" title="Hide this discussion from the public site"
                                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-amber-600 transition-colors duration-150 hover:bg-amber-50">Hide</button>
                                    <?php endif; ?>
                                </form>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-delete.php'); ?>" class="inline-flex"
                                      onsubmit="return confirm('Delete discussion &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot; and all of its replies? This cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                                    <button type="submit" title="Delete discussion"
                                            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-red-600 transition-colors duration-150 hover:bg-red-50">Delete</button>
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
        if ($ucsQuery !== '')       { $ucsFilterParams['q'] = $ucsQuery; }
        if ($ucsCategoryId !== null) { $ucsFilterParams['category_id'] = $ucsCategoryId; }
        if ($ucsStatus !== '')       { $ucsFilterParams['status'] = $ucsStatus; }
        if ($ucsPinned !== '')       { $ucsFilterParams['pinned'] = $ucsPinned; }
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

        $ucsPrevUrl = ROOT_URL . '/admin/discussions/index.php';
        if ($ucsPage > 1) {
            $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
        }
        $ucsNextUrl = ROOT_URL . '/admin/discussions/index.php';
        if ($ucsPage < $ucsTotalPages) {
            $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
        }
        $ucsPageLinkUrl = ROOT_URL . '/admin/discussions/index.php?';
        ?>
        <nav class="mt-6 flex flex-wrap items-center justify-between gap-3" aria-label="Discussions pagination">
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