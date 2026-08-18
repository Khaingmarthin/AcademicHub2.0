<?php
/**
 * Homepage Campus Life & Facilities section.
 *
 * Displays the facilities stored in the facilities table in an image-focused,
 * varied-size grid. Facilities whose image file exists on disk use a large
 * photograph with an elegant overlay; facilities without an available image
 * fall back to a gradient panel with an icon. All facilities come from the
 * database — nothing is invented.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

// Reuse the existing database connection if the page already connected.
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

// The first facility with an available photo becomes the large feature card.
$ucsFeaturedId = null;
foreach ($ucsFacilities as $ucsFacility) {
    $ucsImageFile = !empty($ucsFacility['image'])
        ? __DIR__ . '/../../assets/' . ltrim($ucsFacility['image'], '/')
        : '';
    if ($ucsImageFile !== '' && is_file($ucsImageFile)) {
        $ucsFeaturedId = $ucsFacility['id'];
        break;
    }
}
?>
<section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="campus-life-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Campus Facilities</p>
            <h2 id="campus-life-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Campus Life</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">
                Explore the facilities and environment that support academic and student life at UCSMTLA.
            </p>
        </div>

        <?php if (count($ucsFacilities) > 0): ?>
            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                <?php foreach ($ucsFacilities as $ucsFacility): ?>
                    <?php
                    $ucsIsFeatured = ($ucsFacility['id'] === $ucsFeaturedId);

                    $ucsHasImage = false;
                    $ucsImageUrl = '';
                    if (!empty($ucsFacility['image'])) {
                        $ucsImageFile = __DIR__ . '/../../assets/' . ltrim($ucsFacility['image'], '/');
                        $ucsHasImage = is_file($ucsImageFile);
                        if ($ucsHasImage) {
                            $ucsImageUrl = ROOT_URL . '/assets/' . ltrim($ucsFacility['image'], '/');
                        }
                    }

                    // Decorative icon used when no facility photo is available.
                    $ucsFacilityNameLower = strtolower((string) $ucsFacility['name']);
                    if (stripos($ucsFacilityNameLower, 'computer') !== false
                        || stripos($ucsFacilityNameLower, 'laboratory') !== false
                        || stripos($ucsFacilityNameLower, 'lab') !== false) {
                        $ucsFacilityIcon = '<rect x="2" y="3" width="20" height="14" rx="2"></rect><path d="M8 21h8M12 17v4"></path>';
                    } elseif (stripos($ucsFacilityNameLower, 'canteen') !== false
                        || stripos($ucsFacilityNameLower, 'food') !== false
                        || stripos($ucsFacilityNameLower, 'caf') !== false) {
                        $ucsFacilityIcon = '<path d="M17 8h1a4 4 0 1 1 0 8h-1M3 8h14v6a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8zM7 21h10"></path>';
                    } elseif (stripos($ucsFacilityNameLower, 'atm') !== false
                        || stripos($ucsFacilityNameLower, 'bank') !== false) {
                        $ucsFacilityIcon = '<rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path>';
                    } else {
                        $ucsFacilityIcon = '<path d="M3 21h18M5 21V7l7-4 7 4v14M10 21v-6h4v6"></path>';
                    }
                    ?>
                    <article class="group relative overflow-hidden rounded-2xl shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/10 <?php echo $ucsIsFeatured ? 'aspect-[16/10] sm:col-span-2 lg:col-span-2' : 'aspect-[4/3]'; ?>">
                        <?php if ($ucsHasImage): ?>
                            <img src="<?php echo htmlspecialchars($ucsImageUrl); ?>" alt="<?php echo htmlspecialchars($ucsFacility['name']); ?>" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/90 via-gray-900/35 to-gray-900/5" aria-hidden="true"></div>
                        <?php else: ?>
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-700" aria-hidden="true"></div>
                            <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/10" aria-hidden="true"></div>
                            <div class="absolute -bottom-12 -left-12 h-40 w-40 rounded-full bg-white/10" aria-hidden="true"></div>
                            <div class="absolute left-5 top-5 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20 backdrop-blur">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <?php echo $ucsFacilityIcon; ?>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                            <?php if (!empty($ucsFacility['location'])): ?>
                                <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/20 backdrop-blur">
                                    <?php echo htmlspecialchars($ucsFacility['location']); ?>
                                </span>
                            <?php endif; ?>
                            <h3 class="mt-2 text-lg font-bold tracking-tight text-white sm:text-xl">
                                <?php echo htmlspecialchars($ucsFacility['name']); ?>
                            </h3>
                            <?php if (!empty($ucsFacility['description'])): ?>
                                <p class="mt-1 text-sm leading-6 text-white/85 line-clamp-2">
                                    <?php echo htmlspecialchars($ucsFacility['description']); ?>
                                </p>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/campus-life.php'); ?>" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-200 transition-colors duration-150 hover:text-white focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                                Learn More
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="mt-12 text-center text-sm text-gray-500">Campus facility information is being updated. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>