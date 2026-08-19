<?php
/**
 * Admin News module - list.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$pageTitle    = 'News';
$pageSubtitle = 'Manage news articles and their audience targeting.';
$activeNav    = 'news';

$ucsFlash = $_SESSION['news_flash'] ?? null;
unset($_SESSION['news_flash']);

// --- Filters -----------------------------------------------------------------
$ucsQuery      = trim((string) ($_GET['q'] ?? ''));
$ucsCategoryId = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT);
if ($ucsCategoryId === false) {
    $ucsCategoryId = null;
}
$ucsStatus   = in_array((string) ($_GET['status'] ?? ''), ['Draft', 'Published', 'Expired'], true) ? (string) $_GET['status'] : '';
$ucsFromDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['from_date'] ?? '')) ? (string) $_GET['from_date'] : '';
$ucsToDate   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['to_date'] ?? '')) ? (string) $_GET['to_date'] : '';
$ucsSort     = (string) ($_GET['sort'] ?? '') === 'oldest' ? 'oldest' : 'newest';
$ucsShow     = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage     = max(1, (int) ($_GET['page'] ?? 1));

$ucsWhere  = [];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped   = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[]   = '(n.title LIKE :q OR c.name LIKE :q)';
    $ucsParams[':q'] = '%' . $ucsEscaped . '%';
}
if ($ucsCategoryId !== null && $ucsCategoryId > 0) {
    $ucsWhere[]   = 'n.category_id = :category_id';
    $ucsParams[':category_id'] = $ucsCategoryId;
}
if ($ucsStatus !== '') {
    $ucsWhere[]   = 'n.status = :status';
    $ucsParams[':status'] = $ucsStatus;
}
if ($ucsFromDate !== '') {
    $ucsWhere[]   = 'n.published_at >= :from_date';
    $ucsParams[':from_date'] = $ucsFromDate . ' 00:00:00';
}
if ($ucsToDate !== '') {
    $ucsWhere[]   = 'n.published_at <= :to_date';
    $ucsParams[':to_date'] = $ucsToDate . ' 23:59:59';
}

$ucsWhereSql = count($ucsWhere) > 0 ? ' WHERE ' . implode(' AND ', $ucsWhere) : '';

$ucsNewsItems  = [];
$ucsTotal      = 0;
$ucsTotalPages = 1;
try {
    $ucsCountStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM news n
         JOIN categories c ON c.id = n.category_id
         JOIN admins a ON a.id = n.admin_id"
        . $ucsWhereSql
    );
    $ucsCountStmt->execute($ucsParams);
    $ucsTotal = (int) $ucsCountStmt->fetchColumn();

    $ucsTotalPages = max(1, (int) ceil($ucsTotal / $ucsShow));
    $ucsPage       = min($ucsPage, $ucsTotalPages);
    $ucsOffset     = ($ucsPage - 1) * $ucsShow;
    $ucsOrderBy    = $ucsSort === 'oldest' ? 'n.created_at ASC' : 'n.created_at DESC';

    $ucsStmt = $pdo->prepare(
        "SELECT n.id, n.title, n.slug, n.content, n.cover_image, n.published_at, n.status, n.created_at,
                c.name AS category_name, a.name AS admin_name
         FROM news n
         JOIN categories c ON c.id = n.category_id
         JOIN admins a ON a.id = n.admin_id"
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
    $ucsNewsItems = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsNewsItems  = [];
    $ucsTotal      = 0;
    $ucsTotalPages = 1;
}

$ucsCategories = [];
try {
    $ucsCategories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $ucsCategories = [];
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

<!-- Search + Add (on page background) -->
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>" class="flex w-full max-w-xl items-stretch" role="search">
        <div class="relative flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search news…" aria-label="Search news"
                   class="h-full w-full rounded-l-xl border border-r-0 border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>
        <button type="submit"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-r-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            Search
        </button>
    </form>

    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14"></path>
            <path d="M12 5v14"></path>
        </svg>
        Add News
    </a>
</div>

<!-- Advanced filters -->
<div class="mb-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>">
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
                    <?php foreach (['Draft', 'Published', 'Expired'] as $ucsOptionStatus): ?>
                        <option value="<?php echo $ucsOptionStatus; ?>" <?php echo $ucsStatus === $ucsOptionStatus ? 'selected' : ''; ?>>
                            <?php echo $ucsOptionStatus; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="from_date" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">From Date</label>
                <input type="date" id="from_date" name="from_date" value="<?php echo htmlspecialchars($ucsFromDate); ?>"
                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <div>
                <label for="to_date" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">To Date</label>
                <input type="date" id="to_date" name="to_date" value="<?php echo htmlspecialchars($ucsToDate); ?>"
                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-3">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <label for="sort" class="font-semibold text-gray-500">Sort:</label>
                    <select id="sort" name="sort"
                            class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="newest" <?php echo $ucsSort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $ucsSort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <label for="show" class="font-semibold text-gray-500">Show:</label>
                    <select id="show" name="show"
                            class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <?php foreach ([10, 25, 50] as $ucsShowOption): ?>
                            <option value="<?php echo $ucsShowOption; ?>" <?php echo $ucsShow === $ucsShowOption ? 'selected' : ''; ?>>
                                <?php echo $ucsShowOption; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span>entries</span>
                </div>
            </div>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>"
               class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <path d="M3 3v5h5"></path>
                </svg>
                Reset Filters
            </a>
        </div>
    </form>
</div>

<?php if (empty($ucsNewsItems)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
            <path d="M18 14h-8"></path>
            <path d="M15 18h-5"></path>
            <path d="M10 6h8v4h-8V6Z"></path>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">
            <?php echo $ucsQuery !== '' || $ucsCategoryId !== null || $ucsStatus !== '' || $ucsFromDate !== '' || $ucsToDate !== '' ? 'No matching news' : 'No news articles yet'; ?>
        </h3>
        <p class="mt-2 text-sm text-gray-500">
            <?php echo $ucsQuery !== '' || $ucsCategoryId !== null || $ucsStatus !== '' || $ucsFromDate !== '' || $ucsToDate !== '' ? 'Try adjusting or resetting your filters.' : 'Add your first news article to get started.'; ?>
        </p>
        <?php if ($ucsQuery !== '' || $ucsCategoryId !== null || $ucsStatus !== '' || $ucsFromDate !== '' || $ucsToDate !== ''): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Clear Filters
            </a>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Add News
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="mb-4 text-sm text-gray-600">
        Showing <span class="font-semibold text-gray-900"><?php echo $ucsTotal; ?></span>
        <?php echo $ucsTotal === 1 ? 'news article' : 'news articles'; ?>
        <?php if ($ucsTotalPages > 1): ?>
            &middot; Page <span class="font-semibold text-gray-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-gray-900"><?php echo $ucsTotalPages; ?></span>
        <?php endif; ?>
    </p>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($ucsNewsItems as $ucsNews): ?>
            <?php
            $ucsNewsTitle   = (string) $ucsNews['title'];
            $ucsJsName      = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsNewsTitle);
            $ucsNewsStatus  = (string) $ucsNews['status'];
            $ucsExcerpt     = trim((string) strip_tags((string) $ucsNews['content']));
            if (strlen($ucsExcerpt) > 160) {
                $ucsExcerpt = substr($ucsExcerpt, 0, 160) . '…';
            }
            $ucsCover       = (string) ($ucsNews['cover_image'] ?? '');
            $ucsCoverName   = $ucsCover !== '' ? basename($ucsCover) : '';
            $ucsPublishedAt = !empty($ucsNews['published_at']) ? (string) $ucsNews['published_at'] : '';
            $ucsArticleUrl  = BASE_URL . '/news-details.php?slug=' . urlencode((string) $ucsNews['slug']);
            ?>
            <article class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <div class="flex items-center justify-between gap-3 px-5 pt-5">
                    <span class="inline-flex max-w-[60%] items-center truncate rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-blue-700">
                        <?php echo htmlspecialchars((string) $ucsNews['category_name']); ?>
                    </span>
                    <?php if ($ucsNewsStatus === 'Published'): ?>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">Published</span>
                    <?php elseif ($ucsNewsStatus === 'Expired'): ?>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">Expired</span>
                    <?php else: ?>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Draft</span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-1 flex-col px-5 pt-3">
                    <h3 class="text-base font-bold leading-snug text-gray-900"><?php echo htmlspecialchars($ucsNewsTitle); ?></h3>
                    <?php if ($ucsExcerpt !== ''): ?>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500"><?php echo htmlspecialchars($ucsExcerpt); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($ucsCover !== ''): ?>
                    <div class="px-5 pt-4">
                        <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3 ring-1 ring-gray-100">
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200" aria-hidden="true">
                                <?php if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $ucsCoverName)): ?>
                                    <img src="<?php echo htmlspecialchars(ROOT_URL . '/assets/' . ltrim($ucsCover, '/')); ?>" alt="" class="h-full w-full object-cover">
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                    </svg>
                                <?php endif; ?>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-gray-700"><?php echo htmlspecialchars($ucsCoverName); ?></p>
                                <p class="text-[11px] text-gray-400">Attachment</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 pt-4 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <?php echo $ucsPublishedAt !== '' ? date('j M Y g:i A', strtotime($ucsPublishedAt)) : 'Not published'; ?>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <?php echo htmlspecialchars((string) $ucsNews['admin_name']); ?>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        —
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        —
                    </span>
                </div>

                <div class="mt-4 flex items-center gap-1 border-t border-gray-100 px-5 py-3">
                    <a href="<?php echo htmlspecialchars($ucsArticleUrl); ?>" target="_blank" rel="noopener" title="View <?php echo htmlspecialchars($ucsNewsTitle); ?>"
                       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:bg-blue-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        View
                    </a>
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/edit.php?id=' . (int) $ucsNews['id']); ?>" title="Edit <?php echo htmlspecialchars($ucsNewsTitle); ?>"
                       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-amber-600 transition-colors duration-150 hover:bg-amber-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                            <path d="m15 5 4 4"></path>
                        </svg>
                        Edit
                    </a>
                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/news-delete.php'); ?>" class="inline-flex"
                          onsubmit="return confirm('Delete news article &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? This cannot be undone.');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $ucsNews['id']; ?>">
                        <button type="submit" title="Delete <?php echo htmlspecialchars($ucsNewsTitle); ?>"
                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-red-600 transition-colors duration-150 hover:bg-red-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
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
        if ($ucsQuery !== '')       { $ucsFilterParams['q'] = $ucsQuery; }
        if ($ucsCategoryId !== null) { $ucsFilterParams['category_id'] = $ucsCategoryId; }
        if ($ucsStatus !== '')       { $ucsFilterParams['status'] = $ucsStatus; }
        if ($ucsFromDate !== '')     { $ucsFilterParams['from_date'] = $ucsFromDate; }
        if ($ucsToDate !== '')       { $ucsFilterParams['to_date'] = $ucsToDate; }
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

        $ucsPrevUrl = ROOT_URL . '/admin/news/index.php';
        if ($ucsPage > 1) {
            $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
        }
        $ucsNextUrl = ROOT_URL . '/admin/news/index.php';
        if ($ucsPage < $ucsTotalPages) {
            $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
        }
        $ucsPageLinkUrl = ROOT_URL . '/admin/news/index.php?';
        ?>
        <nav class="mt-6 flex flex-wrap items-center justify-between gap-3" aria-label="News pagination">
            <a href="<?php echo htmlspecialchars($ucsPrevUrl); ?>"
               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?> focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
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
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                <?php echo $ucsLinkPage; ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <a href="<?php echo htmlspecialchars($ucsNextUrl); ?>"
               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage >= $ucsTotalPages ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?> focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </a>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>