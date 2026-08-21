<?php
/**
 * Homepage About UCSMTLA section.
 *
 * Editorial two-column layout with clean typography and whitespace.
 * University identity and campus image from database.
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

$ucsProfile = null;

if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT name, short_name, hero_media, address
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsProfile = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsProfile = null;
    }
}

$ucsShortName = $ucsProfile['short_name'] ?? 'UCSMTLA';
$ucsFullName  = $ucsProfile['name'] ?? 'University of Computer Studies, Meiktila';
$ucsAddress   = $ucsProfile['address'] ?? '';
$ucsAboutMedia = $ucsProfile['hero_media'] ?? 'images/front_view.jpg';

if (!preg_match('~^https?://~i', $ucsAboutMedia)) {
    $ucsAboutMedia = ROOT_URL . '/assets/' . ltrim($ucsAboutMedia, '/');
}
?>
<section class="bg-white py-20 sm:py-24" aria-labelledby="about-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-16">

            <!-- Campus image -->
            <div class="relative">
                <img src="<?php echo htmlspecialchars($ucsAboutMedia); ?>" alt="<?php echo htmlspecialchars($ucsShortName); ?> main campus" class="aspect-[4/3] w-full rounded-lg object-cover">
                <?php if ($ucsAddress !== ''): ?>
                    <div class="absolute bottom-4 left-4 rounded-lg bg-white px-4 py-2.5 shadow-sm ring-1 ring-gray-900/5">
                        <p class="text-[0.6875rem] font-semibold uppercase tracking-wide text-gray-500">Main Campus</p>
                        <p class="mt-0.5 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($ucsAddress); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Introduction -->
            <div>
                <div class="flex items-center gap-3">
                    <span class="h-px w-8 bg-blue-600" aria-hidden="true"></span>
                    <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-blue-600">About the University</span>
                </div>
                <h2 id="about-heading" class="mt-4 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-gray-900 sm:text-4xl">About <?php echo htmlspecialchars($ucsShortName); ?></h2>
                <p class="mt-5 text-base leading-7 text-gray-600">
                    <?php echo htmlspecialchars($ucsFullName); ?> is dedicated to computing and technology education,
                    offering undergraduate programmes in computer science and computer technology alongside a strong
                    foundation in modern digital disciplines.
                </p>
                <p class="mt-3 text-base leading-7 text-gray-600">
                    Through a student-centred curriculum, hands-on practical work and continuous academic development,
                    UCSMTLA nurtures every student's knowledge and skills — preparing capable, innovative graduates
                    ready for the digital future.
                </p>

                <a href="<?php echo htmlspecialchars(BASE_URL . '/about.php'); ?>" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Learn more about <?php echo htmlspecialchars($ucsShortName); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>
