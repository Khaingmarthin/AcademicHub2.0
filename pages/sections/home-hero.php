<?php
/**
 * Homepage Hero section.
 *
 * Bright, editorial university homepage introduction.
 * Strong academic headline, clear UCSMTLA identity, primary actions.
 * Inspired by institutional university website design.
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

$ucsHero = null;
if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT name, short_name, hero_media, established_year
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsHero = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsHero = null;
    }
}

$ucsShortName = $ucsHero['short_name'] ?? 'UCSMTLA';
$ucsFullName  = $ucsHero['name'] ?? 'University of Computer Studies, Meiktila';
$ucsHeroMedia = ROOT_URL . '/assets/images/ucsmtla8.jpg';
?>
<section class="relative overflow-hidden bg-slate-50" aria-labelledby="hero-heading">

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 py-8 sm:py-12 lg:grid-cols-2 lg:gap-12 lg:items-center lg:py-14">

            <!-- Text content -->
            <div class="max-w-xl">
                <!-- Main heading -->
                <h1 id="hero-heading" class="scroll-mt-24 text-3xl font-bold leading-[1.15] tracking-[-0.015em] text-gray-900 sm:text-4xl lg:text-[2.75rem]">
                    Shaping the future of
                    <span class="text-blue-600">technology</span>
                    through quality education
                </h1>

                <!-- Description -->
                <p class="mt-4 text-sm leading-7 text-gray-600 sm:text-base sm:leading-7">
                    <?php echo htmlspecialchars($ucsFullName); ?> is dedicated to computing and technology education, offering academic programmes that prepare students for the digital future.
                </p>

                <!-- Primary actions -->
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Explore Announcements
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/about.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:border-gray-400 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        About <?php echo htmlspecialchars($ucsShortName); ?>
                    </a>
                </div>
            </div>

            <!-- Hero image -->
            <div class="relative lg:order-last">
                <div class="relative overflow-hidden rounded-lg">
                    <img
                        src="<?php echo htmlspecialchars($ucsHeroMedia); ?>"
                        alt="<?php echo htmlspecialchars($ucsShortName); ?> campus"
                        class="aspect-[4/3] w-full object-cover"
                        loading="eager"
                        fetchpriority="high"
                    >
                </div>
            </div>

        </div>
    </div>

</section>
