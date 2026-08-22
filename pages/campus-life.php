<?php
/**
 * Public Campus Life page.
 *
 * Editorial campus directory with image+text split sections for featured
 * facilities and structured bordered rows for the facilities listing.
 * All data comes from the database — nothing is hard-coded.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';

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
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
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
    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="campus-life-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Campus Life</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Life at <?php echo htmlspecialchars($ucsShortName); ?></p>
                <h1 id="campus-life-page-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                    Campus Life
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    Discover the facilities, activities and environment that make life at <?php echo htmlspecialchars($ucsShortName); ?> a welcoming university experience.
                </p>
            </div>
        </div>
    </section>

    <?php if (count($ucsFacilities) > 0): ?>
        <?php
        $ucsFirst = $ucsFacilities[0];
        $ucsFirstHasImage = false;
        $ucsFirstImageUrl = '';
        if (!empty($ucsFirst['image'])) {
            $ucsFirstImageFile = dirname(__DIR__) . '/assets/' . ltrim($ucsFirst['image'], '/');
            $ucsFirstHasImage = is_file($ucsFirstImageFile);
            if ($ucsFirstHasImage) {
                $ucsFirstImageUrl = ROOT_URL . '/assets/' . ltrim($ucsFirst['image'], '/');
            }
        }
        $ucsFirstDescription = ucs_short_summary((string) ($ucsFirst['description'] ?? ''), 300);
        ?>

        <!-- Featured facility — image + text split -->
        <section class="bg-white py-12 sm:py-16 lg:py-20" aria-labelledby="campus-featured-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="border border-slate-200 bg-white">
                    <div class="border-b border-slate-200 px-6 py-4 sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Featured</p>
                    </div>
                    <div class="grid grid-cols-1 gap-0 lg:grid-cols-2">
                        <div class="px-6 py-6 sm:px-8 sm:py-8 lg:py-10">
                            <h2 id="campus-featured-heading" class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                <?php echo htmlspecialchars($ucsFirst['name']); ?>
                            </h2>
                            <?php if (!empty($ucsFirst['location'])): ?>
                                <div class="mt-2.5 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <span class="text-xs font-medium text-slate-500"><?php echo htmlspecialchars($ucsFirst['location']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($ucsFirstDescription !== ''): ?>
                                <p class="mt-4 text-sm leading-relaxed text-slate-600"><?php echo htmlspecialchars($ucsFirstDescription); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="border-t border-slate-200 lg:border-t-0 lg:border-l">
                            <?php if ($ucsFirstHasImage): ?>
                                <img src="<?php echo htmlspecialchars($ucsFirstImageUrl); ?>" alt="<?php echo htmlspecialchars($ucsFirst['name']); ?>" class="aspect-[16/10] w-full object-cover lg:aspect-[4/3]">
                            <?php else: ?>
                                <div class="flex aspect-[16/10] items-center justify-center bg-slate-50 lg:aspect-[4/3]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 21h18M5 21V7l7-4 7 4v14M10 21v-6h4v6"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php if (count($ucsFacilities) > 1): ?>
            <!-- Remaining facilities — editorial list -->
            <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="campus-facilities-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Facilities</p>
                        <h2 id="campus-facilities-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl leading-[1.1]">
                            Campus Facilities
                        </h2>
                        <p class="mt-3 max-w-xl text-base leading-relaxed text-slate-600">
                            The facilities and spaces that support learning and everyday university life.
                        </p>
                    </div>

                    <div class="mt-10 max-w-4xl">
                        <div class="border border-slate-200 bg-white">
                            <?php foreach (array_slice($ucsFacilities, 1) as $ucsIdx => $ucsFacility): ?>
                                <?php
                                $ucsHasImage = false;
                                $ucsImageUrl = '';
                                if (!empty($ucsFacility['image'])) {
                                    $ucsImageFile = dirname(__DIR__) . '/assets/' . ltrim($ucsFacility['image'], '/');
                                    $ucsHasImage = is_file($ucsImageFile);
                                    if ($ucsHasImage) {
                                        $ucsImageUrl = ROOT_URL . '/assets/' . ltrim($ucsFacility['image'], '/');
                                    }
                                }
                                $ucsDescription = ucs_short_summary((string) ($ucsFacility['description'] ?? ''), 200);
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
                                            <h3 class="text-base font-semibold tracking-tight text-slate-900">
                                                <?php echo htmlspecialchars($ucsFacility['name']); ?>
                                            </h3>
                                            <?php if (!empty($ucsFacility['location'])): ?>
                                                <span class="text-xs font-medium text-slate-400">
                                                    <?php echo htmlspecialchars($ucsFacility['location']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($ucsDescription !== ''): ?>
                                            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                                                <?php echo htmlspecialchars($ucsDescription); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    <?php else: ?>
        <section class="bg-slate-50 py-12 sm:py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Facilities</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl leading-[1.1]">Campus Facilities</h2>
                    <p class="mt-3 text-sm text-slate-500">Campus facility information is being updated. Please check back soon.</p>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>
