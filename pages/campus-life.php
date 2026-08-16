<?php
/**
 * Public Campus Life page.
 *
 * Renders a Campus Life hero alongside a database-driven listing of the
 * university's facilities. Only facilities with status = 1 are displayed and
 * every value comes from the facilities table — nothing is hard-coded. Long
 * descriptions are shortened to a concise preview; the full text stays
 * untouched in the database.
 */
require_once '../config/app.php';
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/ucs-listing-helpers.php';

$pageTitle = 'Campus Life';

$ucsProfile = null;

try {
    $ucsStmt = $pdo->query(
        "SELECT short_name, hero_media
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsProfile = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsProfile = null;
}

$ucsShortName = $ucsProfile['short_name'] ?? 'UCSMTLA';
$ucsHeroMedia = $ucsProfile['hero_media'] ?? 'images/front_view.jpg';

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = BASE_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

$ucsFacilities = [];

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

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="campus-life-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Life at <?php echo htmlspecialchars($ucsShortName); ?></p>
                <h1 id="campus-life-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Campus Life</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Discover the facilities, activities and environment that make life at UCSMTLA a welcoming university experience.
                </p>
            </div>
        </div>
    </section>

    <!-- Campus Facilities -->
    <section class="bg-white py-16 sm:py-20" aria-labelledby="campus-facilities-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Facilities</p>
                <h2 id="campus-facilities-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Campus Facilities</h2>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    The facilities and spaces that support learning and everyday university life.
                </p>
            </div>

            <?php if (count($ucsFacilities) > 0): ?>
                <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                    <?php foreach ($ucsFacilities as $ucsFacility): ?>
                        <?php
                        $ucsHasImage = false;
                        $ucsImageUrl = '';
                        if (!empty($ucsFacility['image'])) {
                            $ucsImageFile = __DIR__ . '/assets/' . ltrim($ucsFacility['image'], '/');
                            $ucsHasImage  = is_file($ucsImageFile);
                            if ($ucsHasImage) {
                                $ucsImageUrl = BASE_URL . '/assets/' . ltrim($ucsFacility['image'], '/');
                            }
                        }

                        // Concise 2-4 line preview; the full description stays in the database.
                        $ucsDescription = ucs_short_summary((string) ($ucsFacility['description'] ?? ''), 200);

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
                        <article class="ucs-reveal group flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-900/10">
                            <div class="relative aspect-[16/10] shrink-0 overflow-hidden bg-slate-100">
                                <?php if ($ucsHasImage): ?>
                                    <img src="<?php echo htmlspecialchars($ucsImageUrl); ?>" alt="<?php echo htmlspecialchars($ucsFacility['name']); ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/25 to-transparent" aria-hidden="true"></div>
                                <?php else: ?>
                                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-50" aria-hidden="true"></div>
                                    <div class="absolute -top-8 -right-8 h-28 w-28 rounded-full bg-blue-100/60" aria-hidden="true"></div>
                                    <div class="absolute -bottom-10 -left-10 h-28 w-28 rounded-full bg-indigo-100/60" aria-hidden="true"></div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-xl bg-white text-blue-600 shadow-md ring-1 ring-gray-100 transition-transform duration-200 group-hover:scale-110">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <?php echo $ucsFacilityIcon; ?>
                                            </svg>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex flex-1 flex-col p-5 sm:p-6">
                                <h3 class="text-lg font-bold tracking-tight text-gray-900 transition-colors duration-150 group-hover:text-blue-700">
                                    <?php echo htmlspecialchars($ucsFacility['name']); ?>
                                </h3>
                                <?php if ($ucsDescription !== ''): ?>
                                    <p class="mt-2 flex-1 text-sm leading-6 text-gray-600">
                                        <?php echo htmlspecialchars($ucsDescription); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($ucsFacility['location'])): ?>
                                    <div class="mt-auto flex items-center gap-1.5 border-t border-gray-100 pt-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            <?php echo htmlspecialchars($ucsFacility['location']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mt-12 text-center text-sm text-gray-500">Campus facility information is being updated. Please check back soon.</p>
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