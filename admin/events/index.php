<?php
/**
 * Admin Alumni Events module - list.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-event-validation.php';

admin_require_login();

$pageTitle    = 'Alumni Events';
$pageSubtitle = 'Manage events for the Alumni & Career Community.';
$activeNav    = 'alumni-events';

$ucsFlash = $_SESSION['alumni_event_flash'] ?? null;
unset($_SESSION['alumni_event_flash']);

// --- Filters -----------------------------------------------------------------
$ucsQuery    = trim((string) ($_GET['q'] ?? ''));
$ucsType     = in_array((string) ($_GET['type'] ?? ''), ALUMNI_EVENT_TYPES, true) ? (string) $_GET['type'] : '';
$ucsStatus   = in_array((string) ($_GET['status'] ?? ''), ['published', 'cancelled'], true) ? (string) $_GET['status'] : '';
$ucsSort     = (string) ($_GET['sort'] ?? '') === 'oldest' ? 'oldest' : 'newest';
$ucsShow     = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage     = max(1, (int) ($_GET['page'] ?? 1));

$ucsWhere  = [];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped    = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[]    = '(e.title LIKE :q OR e.venue LIKE :q)';
    $ucsParams[':q'] = '%' . $ucsEscaped . '%';
}
if ($ucsType !== '') {
    $ucsWhere[]            = 'e.event_type = :event_type';
    $ucsParams[':event_type'] = $ucsType;
}
if ($ucsStatus !== '') {
    $ucsWhere[]     = 'e.status = :status';
    $ucsParams[':status'] = $ucsStatus;
}

$ucsWhereSql = count($ucsWhere) > 0 ? ' WHERE ' . implode(' AND ', $ucsWhere) : '';

$ucsEvents      = [];
$ucsTotal       = 0;
$ucsTotalPages  = 1;
try {
    $ucsCountStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM alumni_events e
         JOIN admins a ON a.id = e.admin_id"
        . $ucsWhereSql
    );
    $ucsCountStmt->execute($ucsParams);
    $ucsTotal = (int) $ucsCountStmt->fetchColumn();

    $ucsTotalPages = max(1, (int) ceil($ucsTotal / $ucsShow));
    $ucsPage       = min($ucsPage, $ucsTotalPages);
    $ucsOffset     = ($ucsPage - 1) * $ucsShow;
    $ucsOrderBy    = $ucsSort === 'oldest' ? 'e.starts_at ASC' : 'e.starts_at DESC';

    $ucsStmt = $pdo->prepare(
        "SELECT e.id, e.title, e.event_type, e.venue, e.starts_at, e.ends_at,
                e.status, e.registration_link, a.name AS admin_name
         FROM alumni_events e
         JOIN admins a ON a.id = e.admin_id"
        . $ucsWhereSql
        . " ORDER BY $ucsOrderBy, e.id DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($ucsParams as $ucsKey => $ucsVal) {
        $ucsStmt->bindValue($ucsKey, $ucsVal);
    }
    $ucsStmt->bindValue(':limit', $ucsShow, PDO::PARAM_INT);
    $ucsStmt->bindValue(':offset', $ucsOffset, PDO::PARAM_INT);
    $ucsStmt->execute();
    $ucsEvents = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsEvents     = [];
    $ucsTotal      = 0;
    $ucsTotalPages = 1;
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

<!-- Search + Add -->
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/events/index.php'); ?>" class="flex w-full max-w-xl items-stretch" role="search">
        <div class="relative flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search events…" aria-label="Search alumni events"
                   class="h-full w-full rounded-l-xl border border-r-0 border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>
        <button type="submit"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-r-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
            Search
        </button>
    </form>

    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/events/create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14"></path>
            <path d="M12 5v14"></path>
        </svg>
        Add Event
    </a>
</div>

<!-- Advanced filters -->
<div class="mb-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/events/index.php'); ?>">
        <input type="hidden" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="type" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Type</label>
                <select id="type" name="type"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Types</option>
                    <?php foreach (ALUMNI_EVENT_TYPES as $ucsTypeOption): ?>
                        <option value="<?php echo $ucsTypeOption; ?>" <?php echo $ucsType === $ucsTypeOption ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(alumni_event_label($ucsTypeOption)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                <select id="status" name="status"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Statuses</option>
                    <option value="published" <?php echo $ucsStatus === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="cancelled" <?php echo $ucsStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="flex items-end gap-3">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <label for="sort" class="font-semibold text-gray-500">Sort:</label>
                    <select id="sort" name="sort"
                            class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="newest" <?php echo $ucsSort === 'newest' ? 'selected' : ''; ?>>Soonest First</option>
                        <option value="oldest" <?php echo $ucsSort === 'oldest' ? 'selected' : ''; ?>>Furthest First</option>
                    </select>
                </div>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/events/index.php'); ?>"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                    Reset Filters
                </a>
            </div>
        </div>
    </form>
</div>

<?php if (empty($ucsEvents)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M8 2v4M16 2v4M3 10h18"></path>
            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">
            <?php echo $ucsQuery !== '' || $ucsType !== '' || $ucsStatus !== '' ? 'No matching events' : 'No alumni events yet'; ?>
        </h3>
        <p class="mt-2 text-sm text-gray-500">
            <?php echo $ucsQuery !== '' || $ucsType !== '' || $ucsStatus !== '' ? 'Try adjusting or resetting your filters.' : 'Add your first alumni or career event to get started.'; ?>
        </p>
        <?php if ($ucsQuery !== '' || $ucsType !== '' || $ucsStatus !== ''): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/events/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">Clear Filters</a>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/events/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">Add Event</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="mb-4 text-sm text-gray-600">
        Showing <span class="font-semibold text-gray-900"><?php echo $ucsTotal; ?></span>
        <?php echo $ucsTotal === 1 ? 'event' : 'events'; ?>
        <?php if ($ucsTotalPages > 1): ?>
            &middot; Page <span class="font-semibold text-gray-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-gray-900"><?php echo $ucsTotalPages; ?></span>
        <?php endif; ?>
    </p>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($ucsEvents as $ucsEvent): ?>
            <?php
            $ucsState   = alumni_event_display_state($ucsEvent);
            $ucsTitle   = (string) $ucsEvent['title'];
            $ucsJsName  = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsTitle);
            ?>
            <article class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <div class="flex items-center justify-between gap-3 px-5 pt-5">
                    <span class="inline-flex max-w-[60%] items-center truncate rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-blue-700">
                        <?php echo htmlspecialchars(alumni_event_label((string) $ucsEvent['event_type'])); ?>
                    </span>
                    <?php if ((string) $ucsEvent['status'] === 'cancelled'): ?>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-red-700">Cancelled</span>
                    <?php elseif ($ucsState === 'completed'): ?>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Completed</span>
                    <?php else: ?>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">Published</span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-1 flex-col px-5 pt-3">
                    <h3 class="text-base font-bold leading-snug text-gray-900"><?php echo htmlspecialchars($ucsTitle); ?></h3>
                    <div class="mt-3 space-y-1.5 text-xs text-gray-500">
                        <p class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8 2v4M16 2v4M3 10h18"></path>
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            </svg>
                            <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string) $ucsEvent['starts_at']))); ?>
                        </p>
                        <?php if (!empty($ucsEvent['venue'])): ?>
                            <p class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <?php echo htmlspecialchars((string) $ucsEvent['venue']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 px-5 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <?php echo htmlspecialchars((string) $ucsEvent['admin_name']); ?>
                    </span>
                </div>

                <div class="mt-4 flex items-center gap-1 border-t border-gray-100 px-5 py-3">
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/events/edit.php?id=' . (int) $ucsEvent['id']); ?>" title="Edit"
                       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-amber-600 transition-colors duration-150 hover:bg-amber-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                            <path d="m15 5 4 4"></path>
                        </svg>
                        Edit
                    </a>
                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/event-toggle.php'); ?>" class="inline-flex">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $ucsEvent['id']; ?>">
                        <input type="hidden" name="status" value="<?php echo (string) $ucsEvent['status'] === 'published' ? 'cancelled' : 'published'; ?>">
                        <button type="submit" title="<?php echo (string) $ucsEvent['status'] === 'published' ? 'Cancel' : 'Publish'; ?>"
                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold <?php echo (string) $ucsEvent['status'] === 'published' ? 'text-red-600 hover:bg-red-50' : 'text-blue-600 hover:bg-blue-50'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8 2v4M16 2v4M3 10h18"></path>
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            </svg>
                            <?php echo (string) $ucsEvent['status'] === 'published' ? 'Cancel' : 'Publish'; ?>
                        </button>
                    </form>
                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/event-delete.php'); ?>" class="ml-auto inline-flex"
                          onsubmit="return confirm('Delete this event permanently? This cannot be undone.');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $ucsEvent['id']; ?>">
                        <button type="submit" title="Delete"
                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-red-600 transition-colors duration-150 hover:bg-red-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18"></path>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            Delete
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($ucsTotalPages > 1): ?>
        <?php
        $ucsFilterParams = [];
        if ($ucsQuery !== '')    { $ucsFilterParams['q'] = $ucsQuery; }
        if ($ucsType !== '')     { $ucsFilterParams['type'] = $ucsType; }
        if ($ucsStatus !== '')   { $ucsFilterParams['status'] = $ucsStatus; }
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

        $ucsPrevUrl = ROOT_URL . '/admin/events/index.php';
        if ($ucsPage > 1) {
            $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
        }
        $ucsNextUrl = ROOT_URL . '/admin/events/index.php';
        if ($ucsPage < $ucsTotalPages) {
            $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
        }
        $ucsPageLinkUrl = ROOT_URL . '/admin/events/index.php?';
        ?>
        <nav class="mt-6 flex flex-wrap items-center justify-between gap-3" aria-label="Events pagination">
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