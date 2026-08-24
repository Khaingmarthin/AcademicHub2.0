<?php
/**
 * Public News & Updates page — advanced editorial listing.
 *
 * University news portal with featured story, structured news feed,
 * category navigation, and integrated search. Editorial layout inspired
 * by institutional university websites.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';

$pageTitle = 'News & Updates';

$ucsHeroMedia = 'images/front_view.jpg';

try {
    $ucsProfileStmt = $pdo->query(
        "SELECT short_name, hero_media
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
    $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';
} catch (PDOException $e) {
    // Keep the default hero media.
}

$ucsFilterCategories = ['News', 'Announcement', 'Event', 'Academic'];

$ucsCategoryFilter = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
if (!in_array($ucsCategoryFilter, $ucsFilterCategories, true)) {
    $ucsCategoryFilter = '';
}

$ucsSearchQuery = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

$ucsNewsItems = [];

try {
    $ucsSql = "SELECT n.id, n.title, n.slug, n.content, n.cover_image,
                      n.published_at, n.created_at, c.name AS category
               FROM news n
               LEFT JOIN categories c ON c.id = n.category_id
               WHERE n.status = 'Published'
                 AND (n.published_at IS NULL OR n.published_at <= NOW())";

    $ucsParams = [];
    if ($ucsCategoryFilter !== '') {
        $ucsSql    .= " AND c.name = :category";
        $ucsParams[':category'] = $ucsCategoryFilter;
    }
    if ($ucsSearchQuery !== '') {
        $ucsSql .= " AND (n.title LIKE :search OR n.content LIKE :search)";
        $ucsParams[':search'] = '%' . $ucsSearchQuery . '%';
    }

    $ucsSql .= " ORDER BY n.created_at DESC, n.id DESC";

    $ucsStmt = $pdo->prepare($ucsSql);
    $ucsStmt->execute($ucsParams);
    $ucsNewsItems = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsNewsItems = [];
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

$ucsFeatured = $ucsNewsItems[0] ?? null;
$ucsListing  = array_slice($ucsNewsItems, 1);

require_once '../includes/header.php';
?>
<main class="flex-1">

    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="news-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">News & Updates</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">University News</p>
                <h1 id="news-page-heading" class="mt-3 scroll-mt-24 text-[2rem] font-extrabold tracking-[-0.025em] text-slate-900 sm:text-[2.5rem] lg:text-[3rem] leading-[1.1]">News &amp; Announcements</h1>
                <p class="mt-4 max-w-2xl text-[0.9375rem] leading-[1.85] text-slate-600">
                    Stay informed about the latest developments, events, and academic updates at UCSMTLA.
                </p>
            </div>
        </div>
    </section>

    <!-- Category navigation + search -->
    <section class="border-y border-slate-200 bg-white" aria-label="Filter and search">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 py-3 sm:flex-row sm:items-center sm:justify-between">

                <!-- Category tabs -->
                <nav class="flex items-center gap-1 overflow-x-auto" role="group" aria-label="Filter by category">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="shrink-0 border-b-2 px-3 py-2 text-sm font-medium transition-colors duration-150 <?php echo $ucsCategoryFilter === '' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'; ?>">
                        All
                    </a>
                    <?php foreach ($ucsFilterCategories as $ucsFilterCategory): ?>
                        <?php $ucsIsActive = ($ucsCategoryFilter === $ucsFilterCategory); ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php?category=' . urlencode($ucsFilterCategory)); ?>" class="shrink-0 border-b-2 px-3 py-2 text-sm font-medium transition-colors duration-150 <?php echo $ucsIsActive ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'; ?>">
                            <?php echo htmlspecialchars($ucsFilterCategory); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Search -->
                <form action="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" method="get" class="shrink-0">
                    <?php if ($ucsCategoryFilter !== ''): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($ucsCategoryFilter); ?>">
                    <?php endif; ?>
                    <div class="flex items-center gap-2 border border-slate-200 bg-white px-3 py-1.5 transition-colors focus-within:border-blue-400 focus-within:ring-1 focus-within:ring-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="search" name="q" value="<?php echo htmlspecialchars($ucsSearchQuery); ?>" placeholder="Search articles..." class="w-32 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none sm:w-48">
                    </div>
                </form>

            </div>
        </div>
    </section>

    <!-- News content -->
    <section class="bg-white py-10 sm:py-14" aria-labelledby="news-listing-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="news-listing-heading" class="sr-only">Articles</h2>

            <?php if ($ucsFeatured !== null): ?>
                <?php
                $ucsFeaturedUrl = BASE_URL . '/news-details.php?slug=' . urlencode($ucsFeatured['slug']);
                $ucsHasCover = false;
                $ucsCoverUrl = '';
                if (!empty($ucsFeatured['cover_image'])) {
                    $ucsCoverFile = dirname(__DIR__) . '/assets/' . ltrim($ucsFeatured['cover_image'], '/');
                    $ucsHasCover  = is_file($ucsCoverFile);
                    if ($ucsHasCover) {
                        $ucsCoverUrl = ROOT_URL . '/assets/' . ltrim($ucsFeatured['cover_image'], '/');
                    }
                }
                $ucsPreview = ucs_short_summary((string) ($ucsFeatured['content'] ?? ''), 300);
                $ucsNewsDate = !empty($ucsFeatured['published_at'])
                    ? $ucsFeatured['published_at']
                    : ($ucsFeatured['created_at'] ?? null);
                ?>

                <!-- Featured announcement -->
                <article class="border-b border-slate-200 pb-10">
                    <a href="<?php echo htmlspecialchars($ucsFeaturedUrl); ?>" class="group block">
                        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-10">

                            <!-- Text — takes 7 columns -->
                            <div class="flex flex-col justify-center <?php echo $ucsHasCover ? 'lg:col-span-7' : 'lg:col-span-12'; ?>">
                                <div class="flex items-center gap-3">
                                    <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-blue-600">
                                        <?php echo htmlspecialchars($ucsFeatured['category'] ?? 'News'); ?>
                                    </span>
                                    <?php if ($ucsNewsDate !== null): ?>
                                        <span class="text-gray-300" aria-hidden="true">|</span>
                                        <time class="text-[0.6875rem] text-slate-400" datetime="<?php echo htmlspecialchars($ucsNewsDate); ?>">
                                            <?php echo htmlspecialchars(date('F j, Y', strtotime($ucsNewsDate))); ?>
                                        </time>
                                    <?php endif; ?>
                                </div>
                                <h3 class="mt-3 text-2xl font-extrabold tracking-[-0.02em] text-slate-900 transition-colors duration-150 group-hover:text-blue-700 sm:text-3xl lg:text-[2rem] leading-[1.15]">
                                    <?php echo htmlspecialchars($ucsFeatured['title']); ?>
                                </h3>
                                <?php if ($ucsPreview !== ''): ?>
                                    <p class="mt-3 max-w-xl text-[0.9375rem] leading-[1.85] text-slate-500">
                                        <?php echo htmlspecialchars($ucsPreview); ?>
                                    </p>
                                <?php endif; ?>
                                <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 group-hover:text-blue-700">
                                    Read announcement
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </span>
                            </div>

                            <!-- Image — takes 5 columns -->
                            <?php if ($ucsHasCover): ?>
                                <div class="lg:col-span-5">
                                    <div class="overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars($ucsFeatured['title']); ?>" class="aspect-[16/10] w-full object-cover transition-transform duration-500 group-hover:scale-[1.02]">
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </a>
                </article>
            <?php endif; ?>

            <?php if (count($ucsListing) > 0): ?>
                <!-- Announcement feed — structured editorial list -->
                <div>
                    <?php foreach ($ucsListing as $ucsNewsItem): ?>
                        <?php
                        $ucsArticleUrl = BASE_URL . '/news-details.php?slug=' . urlencode($ucsNewsItem['slug']);
                        $ucsPreview = ucs_short_summary((string) ($ucsNewsItem['content'] ?? ''), 160);
                        $ucsNewsDate = !empty($ucsNewsItem['published_at'])
                            ? $ucsNewsItem['published_at']
                            : ($ucsNewsItem['created_at'] ?? null);
                        ?>
                        <article class="border-b border-slate-200 py-6 first:pt-0 last:border-b-0 last:pb-0">
                            <a href="<?php echo htmlspecialchars($ucsArticleUrl); ?>" class="group flex items-start gap-6">
                                <!-- Date column -->
                                <div class="hidden w-16 shrink-0 text-right sm:block">
                                    <?php if ($ucsNewsDate !== null): ?>
                                        <div class="text-[0.6875rem] font-semibold uppercase tracking-wide text-slate-400"><?php echo htmlspecialchars(date('M', strtotime($ucsNewsDate))); ?></div>
                                        <div class="text-2xl font-bold leading-none text-slate-900"><?php echo htmlspecialchars(date('j', strtotime($ucsNewsDate))); ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <?php if ($ucsNewsDate !== null): ?>
                                            <time class="text-[0.6875rem] text-slate-400 sm:hidden" datetime="<?php echo htmlspecialchars($ucsNewsDate); ?>">
                                                <?php echo htmlspecialchars(date('M j, Y', strtotime($ucsNewsDate))); ?>
                                            </time>
                                        <?php endif; ?>
                                        <span class="text-[0.6875rem] font-semibold uppercase tracking-wider text-blue-600">
                                            <?php echo htmlspecialchars($ucsNewsItem['category'] ?? 'News'); ?>
                                        </span>
                                    </div>
                                    <h3 class="mt-1.5 text-lg font-bold tracking-tight text-slate-900 transition-colors duration-150 group-hover:text-blue-700">
                                        <?php echo htmlspecialchars($ucsNewsItem['title']); ?>
                                    </h3>
                                    <?php if ($ucsPreview !== ''): ?>
                                        <p class="mt-1.5 text-sm leading-[1.8] text-slate-500 line-clamp-2">
                                            <?php echo htmlspecialchars($ucsPreview); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Arrow -->
                                <div class="mt-2 shrink-0 text-slate-300 transition-colors duration-150 group-hover:text-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($ucsFeatured === null && count($ucsListing) === 0): ?>
                <!-- Empty state -->
                <div class="py-20 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
                            <path d="M18 14h-8M15 18h-5M10 6h8v4h-8V6z"></path>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-slate-900">No articles found</h3>
                    <p class="mt-2 text-sm text-slate-500">Please check back soon for the latest news and updates.</p>
                    <?php if ($ucsCategoryFilter !== '' || $ucsSearchQuery !== ''): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700">
                            View all articles
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>
