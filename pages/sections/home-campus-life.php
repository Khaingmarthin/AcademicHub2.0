<?php
/**
 * Homepage Campus Life & Facilities section.
 *
 * Editorial layout with facility images and structured list.
 * Institutional university style with consistent design language.
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
<section class="border-t border-slate-200 bg-white py-20 sm:py-24" aria-labelledby="campus-life-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- Section header -->
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Campus Facilities</p>
            <h2 id="campus-life-heading" class="mt-3 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-slate-900 sm:text-4xl">Campus Life</h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600">
                Explore the facilities and environment that support academic and student life at UCSMTLA.
            </p>
        </div>

        <?php if (count($ucsFacilities) > 0): ?>
            <div class="mx-auto mt-12 max-w-4xl">
                <div class="border border-slate-200 bg-white">
                    <?php foreach ($ucsFacilities as $ucsIdx => $ucsFacility): ?>
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
                        $ucsIsFirst = $ucsIdx === 0;
                        ?>
                        <article class="group grid grid-cols-1 gap-0 sm:grid-cols-[12rem_1fr] <?php echo !$ucsIsFirst ? 'border-t border-slate-200' : ''; ?> transition-colors duration-150 hover:bg-slate-50/60">
                            <?php if ($ucsHasImage): ?>
                                <div class="border-b border-slate-200 sm:border-b-0 sm:border-r">
                                    <img src="<?php echo htmlspecialchars($ucsImageUrl); ?>" alt="<?php echo htmlspecialchars($ucsFacility['name']); ?>" class="h-40 w-full object-cover sm:h-full">
                                </div>
                            <?php endif; ?>
                            <div class="px-6 py-5 sm:px-8 sm:py-6">
                                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <h3 class="text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
                                        <?php echo htmlspecialchars($ucsFacility['name']); ?>
                                    </h3>
                                    <?php if (!empty($ucsFacility['location'])): ?>
                                        <span class="text-xs font-medium text-slate-400">
                                            <?php echo htmlspecialchars($ucsFacility['location']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($ucsFacility['description'])): ?>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                                        <?php echo htmlspecialchars($ucsFacility['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 text-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/campus-life.php'); ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        View all facilities
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <p class="mt-12 text-center text-sm text-slate-500">Campus facility information is being updated. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>
