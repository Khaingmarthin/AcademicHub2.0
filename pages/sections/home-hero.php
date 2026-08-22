<?php
/**
 * Homepage Hero section — editorial university introduction.
 *
 * Modern academic hero with visual depth, strong typography,
 * and institutional prestige. No dark overlays, no SaaS style.
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

    <!-- Subtle geometric accent — top-right corner -->
    <div class="pointer-events-none absolute -right-32 -top-32 h-64 w-64 rounded-full border border-blue-100/60" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full border border-blue-200/40" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 py-12 sm:py-16 lg:grid-cols-12 lg:items-center lg:gap-8 lg:py-20">

            <!-- Text content — takes 7 columns on desktop -->
            <div class="lg:col-span-7">

                <!-- Main heading — the dominant visual element -->
                <h1 id="hero-heading" class="scroll-mt-24 text-[2rem] font-extrabold leading-[1.1] tracking-[-0.025em] text-slate-900 sm:text-[2.5rem] lg:text-[3.25rem] lg:leading-[1.08]">
                    Shaping the future of
                    <span class="text-blue-600">technology</span>
                    through quality education
                </h1>

                <!-- Description -->
                <p class="mt-5 max-w-lg text-[0.9375rem] leading-7 text-slate-600 sm:text-base sm:leading-7">
                    <?php echo htmlspecialchars($ucsFullName); ?> is dedicated to computing and technology education, offering academic programmes that prepare students for the digital future.
                </p>

                <!-- Primary actions -->
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/news.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Explore Announcements
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/about.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Learn About <?php echo htmlspecialchars($ucsShortName); ?>
                    </a>
                </div>

            </div>

            <!-- Hero image — takes 5 columns on desktop -->
            <div class="lg:col-span-5">
                <div class="relative overflow-hidden rounded-lg ring-1 ring-slate-900/5">
                    <img
                        src="<?php echo htmlspecialchars($ucsHeroMedia); ?>"
                        alt="<?php echo htmlspecialchars($ucsShortName); ?> campus"
                        class="aspect-[4/3] w-full object-cover"
                        loading="eager"
                        fetchpriority="high"
                    >
                </div>
                <p class="mt-3 text-center text-[0.6875rem] font-medium tracking-wide text-slate-400"><?php echo htmlspecialchars($ucsShortName); ?> Campus</p>
            </div>

        </div>
    </div>

</section>
