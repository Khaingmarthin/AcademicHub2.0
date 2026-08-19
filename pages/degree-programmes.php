<?php
/**
 * Public Degree Programmes page.
 *
 * Displays the degree programmes stored in the majors table. Common or
 * foundation majors (e.g. the first-year shared major) are excluded so that
 * only actual degree programmes are shown. The hero uses the existing
 * university campus image from university_profile.
 */
require_once '../config/app.php';
require_once '../config/database.php';

$pageTitle = 'Degree Programmes';

$ucsProgrammes = [];
$ucsShortName  = 'UCSMTLA';
$ucsHeroMedia  = 'images/front_view.jpg';

try {
    $ucsProfileStmt = $pdo->query(
        "SELECT short_name, hero_media
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
    $ucsShortName  = $ucsProfileRow['short_name'] ?? 'UCSMTLA';
    $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';

    $ucsStmt = $pdo->query(
        "SELECT id, name, short_name, degree_name, description
         FROM majors
         WHERE status = 1
         ORDER BY id ASC"
    );
    $ucsProgrammes = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsProgrammes = [];
}

// Keep only actual degree programmes (exclude foundation/shared majors).
$ucsProgrammes = array_values(array_filter($ucsProgrammes, function ($ucsProgramme) {
    $ucsDegree = trim((string) ($ucsProgramme['degree_name'] ?? ''));
    return $ucsDegree !== '' && strcasecmp($ucsDegree, 'Common Major') !== 0;
}));

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="degree-programmes-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Degrees &amp; Majors</p>
                <h1 id="degree-programmes-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    Degree Programmes
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg font-semibold leading-8 text-white sm:text-xl">
                    Explore Our Academic Opportunities
                </p>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Discover the undergraduate degree programmes offered by <?php echo htmlspecialchars($ucsShortName); ?>.
                </p>
            </div>
        </div>
    </section>

    <!-- Undergraduate Programmes -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="undergraduate-programmes-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Academic Study</p>
                <h2 id="undergraduate-programmes-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Undergraduate Programmes</h2>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    Our undergraduate programmes combine strong academic foundations with practical, hands-on
                    learning to prepare students for the modern technology field.
                </p>
            </div>

            <?php if (count($ucsProgrammes) > 0): ?>
                <div class="mx-auto mt-12 grid max-w-4xl grid-cols-1 gap-6 sm:grid-cols-2 lg:gap-8">
                    <?php foreach ($ucsProgrammes as $ucsProgramme): ?>
                        <article class="group flex min-w-0 flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 sm:p-8">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span class="inline-flex shrink-0 items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                    <?php echo htmlspecialchars($ucsProgramme['degree_name']); ?>
                                </span>
                                <span class="shrink-0 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <?php echo htmlspecialchars($ucsProgramme['short_name']); ?>
                                </span>
                            </div>

                            <h3 class="mt-5 text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">
                                <?php echo htmlspecialchars($ucsProgramme['name']); ?>
                            </h3>

                            <?php if (!empty($ucsProgramme['description'])): ?>
                                <p class="mt-3 flex-1 break-words text-sm leading-6 text-gray-600">
                                    <?php echo htmlspecialchars($ucsProgramme['description']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($ucsProgramme['id'])): ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php?major=' . (int) $ucsProgramme['id']); ?>" class="mt-3 inline-flex items-center gap-1.5 self-start text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    Related Alumni
                                </a>
                            <?php endif; ?>

                            <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="mt-6 inline-flex w-full items-center justify-center gap-2 self-start rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-md hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 sm:w-auto">
                                View Details
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mt-12 text-center text-sm text-gray-500">No degree programme information is currently available.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>