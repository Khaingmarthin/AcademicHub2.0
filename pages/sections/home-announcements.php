<?php
/**
 * Homepage Latest Announcements section.
 *
 * Surfaces the existing announcement/news system on the homepage using the
 * existing publishing and status logic (only published items whose publish
 * date has passed). One item is featured prominently and the next three are
 * shown as smaller supporting cards. The "View All Announcements" link
 * points to the existing News page.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

// Reuse the existing database connection if the page already connected.
if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../../includes/database.php';
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

// Build a clean single-line excerpt from announcement content.
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

// First item is featured; the rest are supporting cards.
$ucsFeatured = $ucsAnnouncements[0] ?? null;
$ucsSupporting = array_slice($ucsAnnouncements, 1, 3);
?>
<section class="bg-white py-16 sm:py-20" aria-labelledby="announcements-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">News &amp; Updates</p>
            <h2 id="announcements-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Latest News</h2>
        </div>

        <?php if ($ucsFeatured !== null): ?>
            <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-5 lg:gap-8">

                <!-- Featured announcement -->
                <article class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 lg:col-span-3">
                    <div class="h-1.5 w-full bg-blue-600" aria-hidden="true"></div>
                    <div class="flex flex-1 flex-col p-6 sm:p-8">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                <?php echo htmlspecialchars($ucsFeatured['category'] ?? 'Announcement'); ?>
                            </span>
                            <?php if (!empty($ucsFeatured['published_at'])): ?>
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M8 2v4M16 2v4M3 10h18"></path>
                                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    </svg>
                                    <?php echo htmlspecialchars(date('F j, Y', strtotime($ucsFeatured['published_at']))); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="mt-5 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                            <?php echo htmlspecialchars($ucsFeatured['title']); ?>
                        </h3>
                        <p class="mt-3 flex-1 text-sm leading-6 text-gray-600 sm:text-base sm:leading-7">
                            <?php echo htmlspecialchars($ucsExcerpt($ucsFeatured['content'], 220)); ?>
                        </p>

                        <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="mt-6 inline-flex items-center gap-2 self-start rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-md hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Read More
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </article>

                <!-- Supporting announcements -->
                <div class="flex flex-col gap-6 lg:col-span-2">
                    <?php foreach ($ucsSupporting as $ucsAnnouncement): ?>
                        <article class="group flex flex-1 flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                    <?php echo htmlspecialchars($ucsAnnouncement['category'] ?? 'Announcement'); ?>
                                </span>
                                <?php if (!empty($ucsAnnouncement['published_at'])): ?>
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                        </svg>
                                        <?php echo htmlspecialchars(date('M j, Y', strtotime($ucsAnnouncement['published_at']))); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="mt-3 text-base font-bold tracking-tight text-gray-900">
                                <?php echo htmlspecialchars($ucsAnnouncement['title']); ?>
                            </h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-gray-600 line-clamp-2">
                                <?php echo htmlspecialchars($ucsExcerpt($ucsAnnouncement['content'], 160)); ?>
                            </p>

                            <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="mt-4 inline-flex items-center gap-1.5 self-start text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                Read More
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-10 text-center">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="group inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    View All Announcements
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        <?php else: ?>
            <p class="mt-12 text-center text-sm text-gray-500">No announcements right now. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>