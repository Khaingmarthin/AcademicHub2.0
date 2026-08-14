<?php
/**
 * Homepage Hero / Welcome section.
 *
 * Renders the UCSMTLA identity, tagline, call-to-action buttons and the
 * campus hero image. University name, short name and hero media are read
 * from the university_profile table; the current academic year is read from
 * the academic_years table (the row marked as Active).
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

// Reuse the existing database connection if the page already connected.
if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}

$ucsHero = null;
$ucsActiveYear = null;

if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT name, short_name, hero_media
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsHero = $ucsStmt->fetch() ?: null;

        $ucsAyStmt = $pdo->query(
            "SELECT year_name
             FROM academic_years
             WHERE status = 'Active'
             ORDER BY id DESC
             LIMIT 1"
        );
        $ucsActiveYear = $ucsAyStmt->fetchColumn() ?: null;
    } catch (PDOException $e) {
        $ucsHero = null;
        $ucsActiveYear = null;
    }
}

$ucsShortName = $ucsHero['short_name'] ?? 'UCSMTLA';
$ucsFullName  = $ucsHero['name'] ?? 'University of Computer Studies, Meiktila';
$ucsHeroMedia = $ucsHero['hero_media'] ?? 'images/front_view.jpg';

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = BASE_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

// Present the stored year (e.g. 2025-2026) with a proper en-dash.
$ucsAcademicYear = ($ucsActiveYear !== null && $ucsActiveYear !== '')
    ? 'Academic Year ' . str_replace('-', '–', $ucsActiveYear)
    : null;
?>
<section class="relative overflow-hidden bg-white" aria-labelledby="hero-heading">
    <!-- Decorative background -->
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-blue-100/70 blur-3xl"></div>
        <div class="absolute -left-24 bottom-0 h-64 w-64 rounded-full bg-indigo-100/60 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-16">

            <!-- Text -->
            <div class="hero-fade-up max-w-xl">
                <?php if ($ucsAcademicYear !== null): ?>
                    <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-blue-700 ring-1 ring-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                        <?php echo htmlspecialchars($ucsAcademicYear); ?>
                    </span>
                <?php endif; ?>

                <h1 id="hero-heading" class="mt-6 text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl">
                    Welcome to <span class="text-blue-600"><?php echo htmlspecialchars($ucsShortName); ?></span>
                </h1>
                <p class="mt-3 text-xl font-semibold text-gray-700">
                    <?php echo htmlspecialchars($ucsFullName); ?>
                </p>
                <p class="mt-4 text-base leading-7 text-gray-600 sm:text-lg sm:leading-8">
                    Empowering students with knowledge, skills, and innovation for the digital future.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:gap-4">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/25 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/30 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Explore Programmes
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/about.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-800 shadow-sm transition-all duration-200 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Discover UCSMTLA
                    </a>
                </div>
            </div>

            <!-- Hero image -->
            <div class="hero-fade-up relative" style="animation-delay: 0.15s">
                <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-tr from-blue-600/10 via-transparent to-indigo-500/10" aria-hidden="true"></div>
                <img src="<?php echo htmlspecialchars($ucsHeroMedia); ?>" alt="<?php echo htmlspecialchars($ucsShortName); ?> campus front view" class="relative aspect-[4/3] w-full rounded-2xl object-cover shadow-xl shadow-gray-900/10 ring-1 ring-gray-900/5">
            </div>
        </div>
    </div>
</section>