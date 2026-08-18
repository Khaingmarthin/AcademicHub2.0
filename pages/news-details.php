<?php
/**
 * Public News Details page.
 *
 * Shows the full content of a single published news article selected by slug.
 * The record is read from the news table joined with its category — nothing
 * is invented or hard-coded.
 */
require_once '../config/app.php';
require_once '../config/database.php';

$pageTitle = 'News Details';

$ucsSlug      = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$ucsArticle   = null;
$ucsHeroMedia = 'images/front_view.jpg';

if ($ucsSlug !== '') {
    try {
        $ucsProfileStmt = $pdo->query(
            "SELECT hero_media
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
        $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';

        $ucsStmt = $pdo->prepare(
            "SELECT n.id, n.title, n.slug, n.content, n.cover_image,
                    n.published_at, n.created_at, c.name AS category
             FROM news n
             LEFT JOIN categories c ON c.id = n.category_id
             WHERE n.slug = :slug
               AND n.status = 'Published'
               AND (n.published_at IS NULL OR n.published_at <= NOW())
             LIMIT 1"
        );
        $ucsStmt->execute([':slug' => $ucsSlug]);
        $ucsArticle = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsArticle = null;
    }
}

if ($ucsArticle !== null) {
    $pageTitle = $ucsArticle['title'] ?? 'News Details';
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <?php if ($ucsArticle !== null): ?>
        <?php
        $ucsHasCover = false;
        $ucsCoverUrl = '';
        if (!empty($ucsArticle['cover_image'])) {
            $ucsCoverFile = dirname(__DIR__) . '/assets/' . ltrim($ucsArticle['cover_image'], '/');
            $ucsHasCover  = is_file($ucsCoverFile);
            if ($ucsHasCover) {
                $ucsCoverUrl = ROOT_URL . '/assets/' . ltrim($ucsArticle['cover_image'], '/');
            }
        }

        $ucsArticleDate = !empty($ucsArticle['published_at'])
            ? $ucsArticle['published_at']
            : ($ucsArticle['created_at'] ?? null);
        ?>

        <!-- Page hero -->
        <section class="relative overflow-hidden bg-gray-900" aria-labelledby="news-details-heading">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
            <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">
                    <?php echo htmlspecialchars($ucsArticle['category'] ?? 'News'); ?>
                </p>
                <h1 id="news-details-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    <?php echo htmlspecialchars($ucsArticle['title']); ?>
                </h1>
                <?php if ($ucsArticleDate !== null): ?>
                    <p class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                        <?php echo htmlspecialchars(date('F j, Y', strtotime($ucsArticleDate))); ?>
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Article content -->
        <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="news-article-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <article class="overflow-hidden rounded-3xl bg-white shadow-lg shadow-gray-900/5 ring-1 ring-gray-100">
                    <?php if ($ucsHasCover): ?>
                        <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars($ucsArticle['title']); ?>" class="aspect-[21/9] w-full object-cover">
                    <?php else: ?>
                        <div class="flex aspect-[21/9] w-full items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-50">
                            <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-md ring-1 ring-gray-100" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
                                    <path d="M18 14h-8M15 18h-5M10 6h8v4h-8V6z"></path>
                                </svg>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="p-6 sm:p-10">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                <?php echo htmlspecialchars($ucsArticle['category'] ?? 'News'); ?>
                            </span>
                            <?php if ($ucsArticleDate !== null): ?>
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M8 2v4M16 2v4M3 10h18"></path>
                                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    </svg>
                                    <?php echo htmlspecialchars(date('F j, Y', strtotime($ucsArticleDate))); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h2 id="news-article-heading" class="mt-5 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                            <?php echo htmlspecialchars($ucsArticle['title']); ?>
                        </h2>

                        <?php if (trim((string) ($ucsArticle['content'] ?? '')) !== ''): ?>
                            <div class="mt-6 space-y-5 text-base leading-7 text-gray-600 sm:text-lg sm:leading-8">
                                <?php echo nl2br(htmlspecialchars($ucsArticle['content'])); ?>
                            </div>
                        <?php else: ?>
                            <p class="mt-6 text-base leading-7 text-gray-600">The full article content is not available yet.</p>
                        <?php endif; ?>
                    </div>
                </article>

                <div class="mt-8 text-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="group inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"></path>
                        </svg>
                        Back to News
                    </a>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Not found -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="news-not-found-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">News &amp; Updates</p>
                <h1 id="news-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Article Not Found</h1>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    The requested article could not be found or is no longer available.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Browse News
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>