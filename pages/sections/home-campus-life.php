<?php
/**
 * Homepage Campus Life & Facilities section.
 *
 * Clean editorial layout with facility images and text.
 * Institutional university style with clear hierarchy.
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

$ucsFacilities = [];

if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT id, name, image, description, location
             FROM facilities
             WHERE status = 1
             ORDER BY id ASC"
        );
        $ucsFacilities = $ucsStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        $ucsFacilities = [];
    }
}
?>
<section class="bg-white py-20 sm:py-24" aria-labelledby="campus-life-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- Section header -->
        <div class="mx-auto max-w-2xl text-center">
            <div class="flex items-center justify-center gap-3">
                <span class="h-px w-8 bg-blue-600" aria-hidden="true"></span>
                <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-blue-600">Campus Facilities</span>
                <span class="h-px w-8 bg-blue-600" aria-hidden="true"></span>
            </div>
            <h2 id="campus-life-heading" class="mt-4 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-gray-900 sm:text-4xl">Campus Life</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">
                Explore the facilities and environment that support academic and student life at UCSMTLA.
            </p>
        </div>

        <?php if (count($ucsFacilities) > 0): ?>
            <div class="mx-auto mt-12 max-w-4xl">
                <div class="divide-y divide-gray-200 border-t border-gray-200">
                    <?php foreach ($ucsFacilities as $ucsFacility): ?>
                        <?php
                        $ucsHasImage = false;
                        $ucsImageUrl = '';
                        if (!empty($ucsFacility['image'])) {
                            $ucsImageFile = __DIR__ . '/../../assets/' . ltrim($ucsFacility['image'], '/');
                            $ucsHasImage = is_file($ucsImageFile);
                            if ($ucsHasImage) {
                                $ucsImageUrl = ROOT_URL . '/assets/' . ltrim($ucsFacility['image'], '/');
                            }
                        }
                        ?>
                        <article class="flex flex-col gap-6 py-8 first:pt-0 last:pb-0 sm:flex-row sm:items-start sm:gap-8">
                            <?php if ($ucsHasImage): ?>
                                <div class="shrink-0 sm:w-48">
                                    <img src="<?php echo htmlspecialchars($ucsImageUrl); ?>" alt="<?php echo htmlspecialchars($ucsFacility['name']); ?>" class="aspect-[4/3] w-full rounded-lg object-cover sm:aspect-[3/2]">
                                </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-lg font-semibold tracking-tight text-gray-900">
                                        <?php echo htmlspecialchars($ucsFacility['name']); ?>
                                    </h3>
                                    <?php if (!empty($ucsFacility['location'])): ?>
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-[0.6875rem] font-medium text-gray-600">
                                            <?php echo htmlspecialchars($ucsFacility['location']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($ucsFacility['description'])): ?>
                                    <p class="mt-2 text-sm leading-6 text-gray-500">
                                        <?php echo htmlspecialchars($ucsFacility['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="mt-10 text-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/campus-life.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        View all facilities
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <p class="mt-12 text-center text-sm text-gray-500">Campus facility information is being updated. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>
