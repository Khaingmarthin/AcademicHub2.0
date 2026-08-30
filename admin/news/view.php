<?php
/**
 * Admin News module - view article.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT n.id, n.title, n.slug, n.content, n.cover_image, n.published_at, n.status, c.name AS category_name
         FROM news n
         LEFT JOIN categories c ON c.id = n.category_id
         WHERE n.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsNews = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsNews = null;
}

if ($ucsNews === null || $ucsId === false || $ucsId < 1) {
    $_SESSION['news_flash'] = ['type' => 'error', 'message' => 'News article not found.'];
    header('Location: ' . ROOT_URL . '/admin/news/index.php');
    exit;
}

$pageTitle    = 'View News';
$pageSubtitle = 'Preview details of the news article.';
$activeNav    = 'news';

$ucsCoverImage = !empty($ucsNews['cover_image']) ? ROOT_URL . '/assets/' . ltrim($ucsNews['cover_image'], '/') : null;
$ucsStatus     = $ucsNews['status'];
$ucsStatusColors = [
    'Draft'     => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200',
    'Published' => 'bg-green-50 text-green-700 ring-1 ring-green-200',
    'Expired'   => 'bg-red-50 text-red-700 ring-1 ring-red-200',
];

// Fetch gallery images.
$ucsGalleryImages = [];
try {
    $ucsStmt = $pdo->prepare(
        "SELECT image_path FROM news_images WHERE news_id = :news_id ORDER BY sort_order ASC"
    );
    $ucsStmt->execute([':news_id' => $ucsId]);
    $ucsGalleryImages = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsGalleryImages = [];
}

require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>

<div class="mx-auto max-w-4xl">
    <div class="mb-4 flex items-center justify-between">
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-blue-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Back to News
        </a>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/edit.php?id=' . (int) $ucsNews['id']); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                <path d="m15 5 4 4"></path>
            </svg>
            Edit Article
        </a>
    </div>

    <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <?php if ($ucsCoverImage): ?>
            <div class="h-64 w-full bg-gray-100 sm:h-80 lg:h-96">
                <img src="<?php echo htmlspecialchars($ucsCoverImage); ?>" alt="Cover" class="h-full w-full object-cover">
            </div>
        <?php endif; ?>

        <div class="p-6 sm:p-8 lg:p-10">
            <div class="mb-6 flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-blue-700">
                    <?php echo htmlspecialchars($ucsNews['category_name'] ?? 'Uncategorized'); ?>
                </span>
                <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide <?php echo $ucsStatusColors[$ucsStatus] ?? 'bg-gray-100 text-gray-500'; ?>">
                    <?php echo htmlspecialchars($ucsStatus); ?>
                </span>
                <?php if (!empty($ucsNews['published_at'])): ?>
                    <span class="text-sm font-medium text-gray-500">
                        Published: <?php echo htmlspecialchars(date('F j, Y g:i A', strtotime($ucsNews['published_at']))); ?>
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="mb-6 text-2xl font-extrabold text-gray-900 sm:text-3xl lg:text-4xl">
                <?php echo htmlspecialchars($ucsNews['title']); ?>
            </h1>

            <div class="prose prose-blue max-w-none text-gray-700">
                <?php echo nl2br(htmlspecialchars($ucsNews['content'])); ?>
            </div>

            <?php if (!empty($ucsGalleryImages)): ?>
                <div class="mt-8 border-t border-gray-100 pt-8">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Gallery Images</h2>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                        <?php foreach ($ucsGalleryImages as $ucsGalImg): ?>
                            <?php
                            $ucsGalUrl = ROOT_URL . '/assets/' . ltrim($ucsGalImg['image_path'], '/');
                            ?>
                            <div class="overflow-hidden rounded-xl ring-1 ring-gray-200">
                                <img src="<?php echo htmlspecialchars($ucsGalUrl); ?>" alt="" class="h-32 w-full object-cover sm:h-40">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </article>
</div>

<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
