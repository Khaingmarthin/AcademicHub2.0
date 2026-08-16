<?php
/**
 * Homepage Hero / Welcome section.
 *
 * Renders the UCSMTLA identity, tagline and call-to-action buttons on top of
 * the full-width campus hero image with a uniform dark overlay.
 * University name, short name and hero media are read from the
 * university_profile table.
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

if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT name, short_name, hero_media
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
$ucsHeroMedia = BASE_URL . '/assets/images/ucsmtla.jpg';
?>
<section class="relative flex min-h-[calc(100vh-4rem)] items-center overflow-hidden bg-gray-900" aria-labelledby="hero-heading">
    <!-- Full background image -->
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>

    <!-- Uniform dark overlay -->
    <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>

    <!-- Hero content -->
    <div class="relative z-10 mx-auto w-full max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20">
        <div class="hero-fade-up">
            <h1 id="hero-heading" class="text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">
                Welcome to <span class="text-blue-400"><?php echo htmlspecialchars($ucsShortName); ?></span>
            </h1>
            <p class="mt-5 text-xl font-semibold text-white sm:text-2xl">
                <?php echo htmlspecialchars($ucsFullName); ?>
            </p>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                Empowering students with knowledge, skills, and innovation for the digital future.
            </p>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-center sm:gap-4">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/30 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400">
                    Explore Programmes
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/about.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-800 shadow-sm transition-all duration-200 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400">
                    Discover UCSMTLA
                </a>
            </div>
        </div>
    </div>
</section>
