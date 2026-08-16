<?php
/**
 * Public News & Updates page.
 *
 * Lists published news articles from the news table joined with their
 * category. Only published items whose publish date has passed are shown,
 * matching the existing publishing logic. A simple category filter (server
 * side) lets visitors narrow the list; the Admission category is not exposed
 * here because it is not part of the public news listing. Article content is
 * never shown in full — only a short preview built from the stored text.
 */
require_once '../config/app.php';
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/ucs-listing-helpers.php';

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

// Filter categories shown in the listing. Admission is excluded because no
// admission content is surfaced through this public news module.
$ucsFilterCategories = ['News', 'Announcement', 'Event', 'Academic'];

$ucsCategoryFilter = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
if (!in_array($ucsCategoryFilter, $ucsFilterCategories, true)) {
    $ucsCategoryFilter = '';
}

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

    $ucsSql .= " ORDER BY n.published_at DESC, n.id DESC";

    $ucsStmt = $pdo->prepare($ucsSql);
    $ucsStmt->execute($ucsParams);
    $ucsNewsItems = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsNewsItems = [];
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = BASE_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="news-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">UCSMTLA</p>
                <h1 id="news-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">News &amp; Updates</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Stay informed about the latest news, activities and developments at UCSMTLA.
                </p>
            </div>
        </div>
    </section>

    <!-- News listing -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="news-listing-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-6 lg:flex-row lg:items-end">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Latest News</p>
                    <h2 id="news-listing-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Latest Articles</h2>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-gray-600">
                        Announcements, events and academic updates from across the university.
                    </p>
                </div>
            </div>

            <!-- Category filter -->
            <div class="mt-8 flex flex-wrap justify-center gap-2 sm:gap-3 lg:justify-start" role="group" aria-label="Filter news by category">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold ring-1 transition-all duration-200 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 <?php echo $ucsCategoryFilter === '' ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/20 ring-blue-600' : 'bg-white text-gray-700 ring-gray-200 hover:bg-blue-50 hover:text-blue-700 hover:ring-blue-200'; ?>">
                    All
                </a>
                <?php foreach ($ucsFilterCategories as $ucsFilterCategory): ?>
                    <?php $ucsIsActive = ($ucsCategoryFilter === $ucsFilterCategory); ?>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php?category=' . urlencode($ucsFilterCategory)); ?>" class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold ring-1 transition-all duration-200 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 <?php echo $ucsIsActive ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/20 ring-blue-600' : 'bg-white text-gray-700 ring-gray-200 hover:bg-blue-50 hover:text-blue-700 hover:ring-blue-200'; ?>">
                        <?php echo htmlspecialchars($ucsFilterCategory); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (count($ucsNewsItems) > 0): ?>
                <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                    <?php foreach ($ucsNewsItems as $ucsNewsItem): ?>
                        <?php
                        $ucsArticleUrl = BASE_URL . '/news-details.php?slug=' . urlencode($ucsNewsItem['slug']);

                        $ucsHasCover = false;
                        $ucsCoverUrl = '';
                        if (!empty($ucsNewsItem['cover_image'])) {
                            $ucsCoverFile = __DIR__ . '/assets/' . ltrim($ucsNewsItem['cover_image'], '/');
                            $ucsHasCover  = is_file($ucsCoverFile);
                            if ($ucsHasCover) {
                                $ucsCoverUrl = BASE_URL . '/assets/' . ltrim($ucsNewsItem['cover_image'], '/');
                            }
                        }

                        $ucsPreview = ucs_short_summary((string) ($ucsNewsItem['content'] ?? ''), 200);

                        $ucsNewsDate = !empty($ucsNewsItem['published_at'])
                            ? $ucsNewsItem['published_at']
                            : ($ucsNewsItem['created_at'] ?? null);
                        ?>
                        <article class="ucs-reveal group flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-900/10">
                            <a href="<?php echo htmlspecialchars($ucsArticleUrl); ?>" class="relative block aspect-[16/10] shrink-0 overflow-hidden bg-slate-100">
                                <?php if ($ucsHasCover): ?>
                                    <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars($ucsNewsItem['title']); ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                <?php else: ?>
                                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-50" aria-hidden="true"></div>
                                    <div class="absolute -top-8 -right-8 h-28 w-28 rounded-full bg-blue-100/60" aria-hidden="true"></div>
                                    <div class="absolute -bottom-10 -left-10 h-28 w-28 rounded-full bg-indigo-100/60" aria-hidden="true"></div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-xl bg-white text-blue-600 shadow-md ring-1 ring-gray-100 transition-transform duration-200 group-hover:scale-110">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
                                                <path d="M18 14h-8M15 18h-5M10 6h8v4h-8V6z"></path>
                                            </svg>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </a>

                            <div class="flex flex-1 flex-col p-5 sm:p-6">
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                        <?php echo htmlspecialchars($ucsNewsItem['category'] ?? 'News'); ?>
                                    </span>
                                    <?php if ($ucsNewsDate !== null): ?>
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M8 2v4M16 2v4M3 10h18"></path>
                                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                            </svg>
                                            <?php echo htmlspecialchars(date('M j, Y', strtotime($ucsNewsDate))); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="mt-3 text-lg font-bold leading-snug tracking-tight text-gray-900">
                                    <a href="<?php echo htmlspecialchars($ucsArticleUrl); ?>" class="transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        <?php echo htmlspecialchars($ucsNewsItem['title']); ?>
                                    </a>
                                </h3>

                                <?php if ($ucsPreview !== ''): ?>
                                    <p class="mt-2 flex-1 text-sm leading-6 text-gray-600">
                                        <?php echo htmlspecialchars($ucsPreview); ?>
                                    </p>
                                <?php endif; ?>

                                <a href="<?php echo htmlspecialchars($ucsArticleUrl); ?>" class="mt-4 inline-flex items-center gap-1.5 self-start text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    Read More
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mt-12 text-center text-sm text-gray-500">No articles found in this category. Please check back soon.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
    (function () {
        'use strict';
        document.documentElement.classList.add('ucs-js');

        var els = document.querySelectorAll('.ucs-reveal');
        if (!('IntersectionObserver' in window)) {
            els.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        els.forEach(function (el) { observer.observe(el); });
    })();
</script>

<?php
require_once '../includes/footer.php';
?>