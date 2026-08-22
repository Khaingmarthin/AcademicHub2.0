<?php
/**
 * Public News Details page — advanced editorial article layout.
 *
 * Professional university article page with strong typography,
 * comfortable reading width, and institutional publication feel.
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

// Related articles (same category, excluding current).
$ucsRelated = [];
if ($ucsArticle !== null && isset($pdo)) {
    try {
        $ucsRelatedStmt = $pdo->prepare(
            "SELECT n.title, n.slug, n.published_at, c.name AS category
             FROM news n
             LEFT JOIN categories c ON c.id = n.category_id
             WHERE n.status = 'Published'
               AND (n.published_at IS NULL OR n.published_at <= NOW())
               AND n.id != :current_id
               AND n.category_id = (SELECT category_id FROM news WHERE id = :current_id2)
             ORDER BY n.published_at DESC
             LIMIT 4"
        );
        $ucsRelatedStmt->execute([':current_id' => $ucsArticle['id'], ':current_id2' => $ucsArticle['id']]);
        $ucsRelated = $ucsRelatedStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        $ucsRelated = [];
    }
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

        <!-- Article header -->
        <section class="border-b border-slate-200 bg-white" aria-labelledby="news-details-heading">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">

                <!-- Back navigation -->
                <nav class="mb-8 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="transition-colors hover:text-slate-600">News</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Article</li>
                    </ol>
                </nav>

                <!-- Category -->
                <div class="mt-8">
                    <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-blue-600">
                        <?php echo htmlspecialchars($ucsArticle['category'] ?? 'News'); ?>
                    </span>
                </div>

                <!-- Title -->
                <h1 id="news-details-heading" class="mt-3 scroll-mt-24 text-2xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-3xl lg:text-[2.25rem] lg:leading-[1.15]">
                    <?php echo htmlspecialchars($ucsArticle['title']); ?>
                </h1>

                <!-- Publication metadata -->
                <div class="mt-5 flex items-center gap-4 border-t border-slate-200 pt-5">
                    <?php if ($ucsArticleDate !== null): ?>
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <time class="text-sm text-slate-600" datetime="<?php echo htmlspecialchars($ucsArticleDate); ?>">
                                <?php echo htmlspecialchars(date('F j, Y', strtotime($ucsArticleDate))); ?>
                            </time>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Cover image (full-width) -->
        <?php if ($ucsHasCover): ?>
            <section class="bg-white" aria-hidden="true">
                <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars($ucsArticle['title']); ?>" class="w-full rounded-lg object-cover" style="max-height: 32rem;">
                </div>
            </section>
        <?php endif; ?>

        <!-- Article content — reading width -->
        <section class="bg-white py-10 sm:py-14" aria-labelledby="news-article-heading">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

                <article>
                    <h2 id="news-article-heading" class="sr-only">Article Content</h2>

                    <?php if (trim((string) ($ucsArticle['content'] ?? '')) !== ''): ?>
                        <div class="text-[0.9375rem] leading-[1.85] text-slate-700 [&_p]:mb-5">
                            <?php echo nl2br(htmlspecialchars($ucsArticle['content'])); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-[0.9375rem] leading-[1.85] text-slate-400 italic">The full article content is not available yet.</p>
                    <?php endif; ?>
                </article>

                <!-- Article footer actions -->
                <div class="mt-12 flex items-center justify-between border-t border-slate-200 pt-8">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"></path>
                        </svg>
                        Back to News
                    </a>
                </div>

                <!-- Related announcements -->
                <?php if (count($ucsRelated) > 0): ?>
                    <div class="mt-12 border-t border-slate-200 pt-10">
                        <h3 class="text-lg font-bold tracking-tight text-slate-900">Related Announcements</h3>
                        <div class="mt-6 divide-y divide-slate-200">
                            <?php foreach ($ucsRelated as $ucsRelatedItem): ?>
                                <?php
                                $ucsRelatedUrl = BASE_URL . '/news-details.php?slug=' . urlencode($ucsRelatedItem['slug']);
                                $ucsRelatedDate = !empty($ucsRelatedItem['published_at'])
                                    ? $ucsRelatedItem['published_at']
                                    : null;
                                ?>
                                <article class="py-4 first:pt-0 last:pb-0">
                                    <a href="<?php echo htmlspecialchars($ucsRelatedUrl); ?>" class="group flex items-start gap-4">
                                        <!-- Date -->
                                        <div class="hidden w-12 shrink-0 text-right sm:block">
                                            <?php if ($ucsRelatedDate !== null): ?>
                                                <div class="text-[0.625rem] font-semibold uppercase tracking-wide text-slate-400"><?php echo htmlspecialchars(date('M', strtotime($ucsRelatedDate))); ?></div>
                                                <div class="text-lg font-bold leading-none text-slate-900"><?php echo htmlspecialchars(date('j', strtotime($ucsRelatedDate))); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Content -->
                                        <div class="flex-1">
                                            <span class="text-[0.625rem] font-semibold uppercase tracking-wider text-blue-600">
                                                <?php echo htmlspecialchars($ucsRelatedItem['category'] ?? 'News'); ?>
                                            </span>
                                            <h4 class="mt-1 text-base font-semibold tracking-tight text-slate-900 transition-colors duration-150 group-hover:text-blue-700">
                                                <?php echo htmlspecialchars($ucsRelatedItem['title']); ?>
                                            </h4>
                                        </div>
                                        <!-- Arrow -->
                                        <div class="mt-1 shrink-0 text-slate-300 transition-colors duration-150 group-hover:text-blue-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    <?php else: ?>
        <!-- Not found -->
        <section class="bg-white py-20 sm:py-24" aria-labelledby="news-not-found-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-blue-600">News &amp; Updates</span>
                    <h1 id="news-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl">Article Not Found</h1>
                    <p class="mt-4 text-[0.9375rem] leading-[1.85] text-slate-500">
                        The requested article could not be found or is no longer available.
                    </p>
                    <div class="mt-8">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700">
                            Browse News
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>
