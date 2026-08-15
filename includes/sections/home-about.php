<?php
/**
 * Homepage About UCSMTLA section.
 *
 * Two-column layout: a large campus image on the left and a concise,
 * professional introduction to the university on the right. University
 * identity, campus image and address are read from the university_profile
 * table; the "Learn More" link points to the existing About page.
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
    $ucsAboutMedia = BASE_URL . '/assets/' . ltrim($ucsAboutMedia, '/');
}
?>
<section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="about-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-16">

            <!-- Campus image -->
            <div class="relative">
                <img src="<?php echo htmlspecialchars($ucsAboutMedia); ?>" alt="<?php echo htmlspecialchars($ucsShortName); ?> main campus" class="aspect-[4/3] w-full rounded-2xl object-cover shadow-xl shadow-gray-900/10 ring-1 ring-gray-900/5">
                <?php if ($ucsAddress !== ''): ?>
                    <div class="absolute bottom-4 left-4 rounded-xl bg-white/95 px-4 py-3 shadow-lg shadow-gray-900/10 ring-1 ring-gray-900/5 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Main Campus</p>
                        <p class="mt-0.5 text-sm font-bold text-gray-900"><?php echo htmlspecialchars($ucsAddress); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Introduction -->
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">About the University</p>
                <h2 id="about-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">About <?php echo htmlspecialchars($ucsShortName); ?></h2>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    <?php echo htmlspecialchars($ucsFullName); ?> is dedicated to computing and technology education,
                    offering undergraduate programmes in computer science and computer technology alongside a strong
                    foundation in modern digital disciplines.
                </p>
                <p class="mt-3 text-base leading-7 text-gray-600">
                    Through a student-centred curriculum, hands-on practical work and continuous academic development,
                    UCSMTLA nurtures every student's knowledge and skills — preparing capable, innovative graduates
                    ready for the digital future.
                </p>

                <a href="<?php echo htmlspecialchars(BASE_URL . '/about.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Learn More
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>