<?php
/**
 * Homepage Latest Announcements section.
 *
 * Modern card-based layout with cover images and engaging visual design.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../../config/database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}

$ucsAnnouncements = [];

if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT n.id, n.title, n.slug, n.content, n.cover_image, n.published_at, n.created_at, c.name AS category
             FROM news n
             LEFT JOIN categories c ON c.id = n.category_id
             WHERE n.status = 'Published'
               AND (n.published_at IS NULL OR n.published_at <= NOW())
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT 4"
        );
        $ucsAnnouncements = $ucsStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        $ucsAnnouncements = [];
    }
}

if (function_exists('mb_strlen')) {
    $ucsExcerpt = function ($ucsText, $ucsMax) {
        $ucsText = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $ucsText)));
        if (mb_strlen($ucsText) <= $ucsMax) {
            return $ucsText;
        }
        return rtrim(mb_substr($ucsText, 0, $ucsMax), " \t\n\r.,;:!?") . '…';
    };
} else {
    $ucsExcerpt = function ($ucsText, $ucsMax) {
        $ucsText = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $ucsText)));
        if (strlen($ucsText) <= $ucsMax) {
            return $ucsText;
        }
        return rtrim(substr($ucsText, 0, $ucsMax), " \t\n\r.,;:!?") . '…';
    };
}

$ucsFeatured = $ucsAnnouncements[0] ?? null;
$ucsSupporting = array_slice($ucsAnnouncements, 1, 3);

function ucsGetCoverUrl($coverImage) {
    if (empty($coverImage)) {
        return '';
    }
    if (preg_match('~^https?://~i', $coverImage)) {
        return $coverImage;
    }
    return ROOT_URL . '/assets/' . ltrim($coverImage, '/');
}
?>
<section class="bg-gradient-to-b from-slate-50 to-white py-20 sm:py-24" aria-labelledby="announcements-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- Section header -->
        <div class="text-center max-w-2xl mx-auto">
            <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                News &amp; Updates
            </span>
            <h2 id="announcements-heading" class="mt-5 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-slate-900 sm:text-4xl">Latest Announcements</h2>
            <p class="mt-3 text-base text-slate-500">Stay informed with the latest news and updates from UCSMTLA</p>
        </div>

        <?php if ($ucsFeatured !== null): ?>
            <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-2">

                <!-- Featured announcement with image -->
                <article class="group relative overflow-hidden rounded-3xl bg-white shadow-sm border border-slate-200 transition-all duration-300 hover:shadow-xl hover:border-slate-300">
                    <div class="aspect-[16/10] bg-gradient-to-br from-blue-500 to-blue-700 overflow-hidden">
                        <?php $ucsCoverUrl = ucsGetCoverUrl($ucsFeatured['cover_image'] ?? ''); ?>
                        <?php if ($ucsCoverUrl): ?>
                            <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars($ucsFeatured['title']); ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        <?php else: ?>
                            <div class="flex h-full items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                <?php echo htmlspecialchars($ucsFeatured['category'] ?? 'Announcement'); ?>
                            </span>
                            <?php if (!empty($ucsFeatured['published_at'])): ?>
                                <time class="text-xs text-slate-400" datetime="<?php echo htmlspecialchars($ucsFeatured['published_at']); ?>">
                                    <?php echo htmlspecialchars(date('M j, Y', strtotime($ucsFeatured['published_at']))); ?>
                                </time>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                            <?php echo htmlspecialchars($ucsFeatured['title']); ?>
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            <?php echo htmlspecialchars($ucsExcerpt($ucsFeatured['content'], 200)); ?>
                        </p>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/news-details.php?slug=' . urlencode($ucsFeatured['slug'])); ?>" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-blue-700 hover:shadow-md">
                            Read More
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </article>

                <!-- Supporting announcements grid -->
                <div class="grid gap-4">
                    <?php foreach ($ucsSupporting as $ucsAnnouncement): ?>
                        <article class="group flex gap-5 rounded-2xl border border-slate-200 bg-white p-5 transition-all duration-200 hover:shadow-lg hover:border-slate-300">
                            <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-slate-100 to-slate-200">
                                <?php $ucsCoverUrl = ucsGetCoverUrl($ucsAnnouncement['cover_image'] ?? ''); ?>
                                <?php if ($ucsCoverUrl): ?>
                                    <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars($ucsAnnouncement['title']); ?>" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110">
                                <?php else: ?>
                                    <div class="flex h-full items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[0.625rem] font-semibold uppercase tracking-wider text-slate-600">
                                        <?php echo htmlspecialchars($ucsAnnouncement['category'] ?? 'Announcement'); ?>
                                    </span>
                                    <?php if (!empty($ucsAnnouncement['published_at'])): ?>
                                        <time class="text-[0.625rem] text-slate-400" datetime="<?php echo htmlspecialchars($ucsAnnouncement['published_at']); ?>">
                                            <?php echo htmlspecialchars(date('M j', strtotime($ucsAnnouncement['published_at']))); ?>
                                        </time>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-sm font-semibold leading-snug text-slate-900 line-clamp-2">
                                    <?php echo htmlspecialchars($ucsAnnouncement['title']); ?>
                                </h3>
                                <p class="mt-1.5 text-xs leading-5 text-slate-500 line-clamp-2">
                                    <?php echo htmlspecialchars($ucsExcerpt($ucsAnnouncement['content'], 100)); ?>
                                </p>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/news-details.php?slug=' . urlencode($ucsAnnouncement['slug'])); ?>" class="mt-2.5 inline-flex items-center gap-1 text-xs font-semibold text-blue-600 transition-colors hover:text-blue-700">
                                    Read more
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- View all button -->
            <div class="mt-10 text-center">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-all duration-200 hover:border-blue-600 hover:text-blue-600 hover:shadow-md">
                    View All Announcements
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                </a>
            </div>
        <?php else: ?>
            <div class="mt-12 rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <p class="mt-4 text-sm text-slate-500">No announcements right now. Please check back soon.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
