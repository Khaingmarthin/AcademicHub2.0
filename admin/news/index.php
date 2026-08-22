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

<!-- Search + Filters toolbar -->
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>" role="search" class="flex flex-col gap-5">
        
        <!-- Top Row: Search & Actions -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Search Group & Add Button -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full lg:w-auto">
                <!-- Search Input -->
                <div class="relative w-full sm:w-64 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                    <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search news..." aria-label="Search news"
                           class="block w-full rounded-lg border border-slate-300 bg-slate-50 py-2 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <!-- Search Button -->
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-1 shrink-0">
                    Search
                </button>

                <!-- Add News Button -->
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/create.php'); ?>" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                    Add News
                </a>
            </div>

            <!-- Clear Filters -->
            <?php if ($ucsQuery !== '' || $ucsCategoryId !== null || $ucsStatus !== '' || $ucsFromDate !== '' || $ucsToDate !== ''): ?>
                <div class="flex shrink-0">
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>" class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-1">
                        Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="h-px w-full bg-slate-100"></div>

        <!-- Bottom Row: Filters Grid -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div class="xl:col-span-1">
                <label for="category_id" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Category</label>
                <select id="category_id" name="category_id" onchange="this.form.submit()" aria-label="Filter by category"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    <?php foreach ($ucsCategories as $ucsCategory): ?>
                        <option value="<?php echo (int) $ucsCategory['id']; ?>" <?php echo $ucsCategoryId !== null && (int) $ucsCategoryId === (int) $ucsCategory['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ucsCategory['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xl:col-span-1">
                <label for="status" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Status</label>
                <select id="status" name="status" onchange="this.form.submit()" aria-label="Filter by status"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <?php foreach (['Draft', 'Published', 'Expired'] as $ucsOptionStatus): ?>
                        <option value="<?php echo $ucsOptionStatus; ?>" <?php echo $ucsStatus === $ucsOptionStatus ? 'selected' : ''; ?>>
                            <?php echo $ucsOptionStatus; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xl:col-span-1">
                <label for="from_date" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">From Date</label>
                <input type="date" id="from_date" name="from_date" value="<?php echo htmlspecialchars($ucsFromDate); ?>" onchange="this.form.submit()"
                       class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="xl:col-span-1">
                <label for="to_date" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">To Date</label>
                <input type="date" id="to_date" name="to_date" value="<?php echo htmlspecialchars($ucsToDate); ?>" onchange="this.form.submit()"
                       class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            
            <div class="xl:col-span-1">
                <label for="sort" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Sort By</label>
                <select id="sort" name="sort" onchange="this.form.submit()"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="newest" <?php echo $ucsSort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $ucsSort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                </select>
            </div>
            
            <div class="xl:col-span-1">
                <label for="show" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Entries</label>
                <select id="show" name="show" onchange="this.form.submit()"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <?php foreach ([10, 25, 50] as $ucsShowOption): ?>
                        <option value="<?php echo $ucsShowOption; ?>" <?php echo $ucsShow === $ucsShowOption ? 'selected' : ''; ?>><?php echo $ucsShowOption; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
</div>

<?php if (empty($ucsNewsItems)): ?>
    <div class="mt-6 flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
                <path d="M18 14h-8"></path>
                <path d="M15 18h-5"></path>
                <path d="M10 6h8v4h-8V6Z"></path>
            </svg>
        </div>
        <h3 class="mt-4 text-lg font-semibold text-slate-900">
            <?php echo $ucsQuery !== '' || $ucsCategoryId !== null || $ucsStatus !== '' || $ucsFromDate !== '' || $ucsToDate !== '' ? 'No matching news' : 'No news articles yet'; ?>
        </h3>
        <p class="mt-2 text-sm text-slate-500">
            <?php echo $ucsQuery !== '' || $ucsCategoryId !== null || $ucsStatus !== '' || $ucsFromDate !== '' || $ucsToDate !== '' ? 'Try adjusting or resetting your filters.' : 'Add your first news article to get started.'; ?>
        </p>
        <?php if ($ucsQuery !== '' || $ucsCategoryId !== null || $ucsStatus !== '' || $ucsFromDate !== '' || $ucsToDate !== ''): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                Clear Filters
            </a>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/create.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Add News
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm font-medium text-slate-600">
            Showing <span class="font-semibold text-slate-900"><?php echo $ucsTotal; ?></span>
            <?php echo $ucsTotal === 1 ? 'news article' : 'news articles'; ?>
            <?php if ($ucsTotalPages > 1): ?>
                &middot; Page <span class="font-semibold text-slate-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-slate-900"><?php echo $ucsTotalPages; ?></span>
            <?php endif; ?>
        </p>
    </div>

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
            <article class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:border-blue-300">
                <!-- Top: Badges -->
                <div class="mb-4 flex items-start justify-between gap-2">
                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                        <?php echo htmlspecialchars((string) $ucsNews['category_name']); ?>
                    </span>
                    <?php if ($ucsNewsStatus === 'Published'): ?>
                        <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20">Published</span>
                    <?php elseif ($ucsNewsStatus === 'Expired'): ?>
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Expired</span>
                    <?php else: ?>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">Draft</span>
                    <?php endif; ?>
                </div>

                <!-- Main: Info -->
                <div class="mb-5 flex-1">
                    <h3 class="text-lg font-bold tracking-tight text-slate-900 leading-snug"><?php echo htmlspecialchars($ucsNewsTitle); ?></h3>
                    <?php if ($ucsExcerpt !== ''): ?>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500"><?php echo htmlspecialchars($ucsExcerpt); ?></p>
                    <?php endif; ?>
                    
                    <div class="mt-4 flex flex-col gap-2 text-sm">
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-500">Published</span>
                            <span class="font-medium text-slate-900 text-right">
                                <?php echo $ucsPublishedAt !== '' ? date('M j, Y g:i A', strtotime($ucsPublishedAt)) : '—'; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-500">Author</span>
                            <span class="font-medium text-slate-900 text-right"><?php echo htmlspecialchars((string) $ucsNews['admin_name']); ?></span>
                        </div>
                        <?php if ($ucsCover !== ''): ?>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-500">Attachment</span>
                            <span class="font-medium text-slate-900 text-right truncate max-w-[150px]"><?php echo htmlspecialchars($ucsCoverName); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bottom: Actions -->
                <div class="mt-auto flex flex-col gap-2 border-t border-slate-100 pt-4">
                    <div class="grid grid-cols-2 gap-2">
                        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/view.php?id=' . (int) $ucsNews['id']); ?>" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-sky-50 px-2 py-2 text-[11px] font-semibold text-sky-600 transition-colors hover:bg-sky-100 hover:text-sky-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            View
                        </a>
                        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/edit.php?id=' . (int) $ucsNews['id']); ?>" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-amber-50 px-2 py-2 text-[11px] font-semibold text-amber-600 transition-colors hover:bg-amber-100 hover:text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path><path d="m15 5 4 4"></path></svg>
                            Edit
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-2">
                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/news-delete.php'); ?>" class="flex" onsubmit="return confirm('Delete news article &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? This cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $ucsNews['id']; ?>">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-rose-50 px-2 py-2 text-[11px] font-semibold text-rose-600 transition-colors hover:bg-rose-100 hover:text-rose-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                Delete
                            </button>
                        </form>
                    </div>
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