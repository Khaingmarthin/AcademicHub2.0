<?php
/**
 * Homepage Latest Announcements section.
 *
 * Editorial news section with featured announcement and supporting list.
 * Clean typography, clear hierarchy, institutional university style.
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
            "SELECT n.title, n.slug, n.content, n.published_at, c.name AS category
             FROM news n
             LEFT JOIN categories c ON c.id = n.category_id
             WHERE n.status = 'Published'
               AND (n.published_at IS NULL OR n.published_at <= NOW())
             ORDER BY n.published_at DESC, n.id DESC
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
?>
<section class="border-t border-gray-200 bg-gray-50 py-20 sm:py-24" aria-labelledby="announcements-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- Section header -->
        <div class="flex items-end justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <span class="h-px w-8 bg-blue-600" aria-hidden="true"></span>
                    <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-blue-600">News &amp; Updates</span>
                </div>
                <h2 id="announcements-heading" class="mt-4 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-gray-900 sm:text-4xl">Latest Announcements</h2>
            </div>
            <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="hidden items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 sm:inline-flex">
                View all
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>

        <?php if ($ucsFeatured !== null): ?>
            <div class="mt-10 grid grid-cols-1 gap-10 lg:grid-cols-5 lg:gap-12">

                <!-- Featured announcement -->
                <article class="lg:col-span-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                            <?php echo htmlspecialchars($ucsFeatured['category'] ?? 'Announcement'); ?>
                        </span>
                        <?php if (!empty($ucsFeatured['published_at'])): ?>
                            <time class="text-xs text-gray-500" datetime="<?php echo htmlspecialchars($ucsFeatured['published_at']); ?>">
                                <?php echo htmlspecialchars(date('F j, Y', strtotime($ucsFeatured['published_at']))); ?>
                            </time>
                        <?php endif; ?>
                    </div>
                    <h3 class="mt-4 text-2xl font-bold tracking-[-0.01em] text-gray-900 sm:text-3xl">
                        <?php echo htmlspecialchars($ucsFeatured['title']); ?>
                    </h3>
                    <p class="mt-3 text-base leading-7 text-gray-600">
                        <?php echo htmlspecialchars($ucsExcerpt($ucsFeatured['content'], 280)); ?>
                    </p>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Read full announcement
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </article>

                <!-- Supporting announcements -->
                <div class="lg:col-span-2">
                    <div class="divide-y divide-gray-200 border-t border-gray-200 lg:border-t-0">
                        <?php foreach ($ucsSupporting as $ucsAnnouncement): ?>
                            <article class="py-5 first:pt-0 lg:first:pt-0">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-[0.6875rem] font-medium text-gray-600">
                                        <?php echo htmlspecialchars($ucsAnnouncement['category'] ?? 'Announcement'); ?>
                                    </span>
                                    <?php if (!empty($ucsAnnouncement['published_at'])): ?>
                                        <time class="text-xs text-gray-400" datetime="<?php echo htmlspecialchars($ucsAnnouncement['published_at']); ?>">
                                            <?php echo htmlspecialchars(date('M j, Y', strtotime($ucsAnnouncement['published_at']))); ?>
                                        </time>
                                    <?php endif; ?>
                                </div>
                                <h3 class="mt-2 text-base font-semibold tracking-tight text-gray-900">
                                    <?php echo htmlspecialchars($ucsAnnouncement['title']); ?>
                                </h3>
                                <p class="mt-1 text-sm leading-6 text-gray-500 line-clamp-2">
                                    <?php echo htmlspecialchars($ucsExcerpt($ucsAnnouncement['content'], 140)); ?>
                                </p>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700">
                                    Read more
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Mobile view all link -->
            <div class="mt-8 text-center sm:hidden">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700">
                    View all announcements
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        <?php else: ?>
            <p class="mt-12 text-center text-sm text-gray-500">No announcements right now. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>
