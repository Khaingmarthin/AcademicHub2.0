<?php
/**
 * Homepage Academic Programmes section.
 *
 * Displays the degree programmes stored in the majors table as modern
 * programme cards. Common/foundation majors (e.g. the first-year shared
 * major) are excluded so that only actual degree programmes are shown.
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

$ucsProgrammes = [];

if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT name, short_name, degree_name, description
             FROM majors
             WHERE status = 1
             ORDER BY id ASC"
        );
        $ucsProgrammes = $ucsStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        $ucsProgrammes = [];
    }
}

// Keep only actual degree programmes (exclude foundation/shared majors).
$ucsProgrammes = array_values(array_filter($ucsProgrammes, function ($ucsProgramme) {
    $ucsDegree = trim((string) ($ucsProgramme['degree_name'] ?? ''));
    return $ucsDegree !== '' && strcasecmp($ucsDegree, 'Common Major') !== 0;
}));
?>
<section class="bg-white py-16 sm:py-20" aria-labelledby="programmes-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Degrees &amp; Majors</p>
            <h2 id="programmes-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Academic Programmes</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">
                Explore the academic programmes offered by the University of Computer Studies, Meiktila.
            </p>
        </div>

        <?php if (count($ucsProgrammes) > 0): ?>
            <div class="mx-auto mt-12 grid max-w-4xl grid-cols-1 gap-6 sm:grid-cols-2 lg:gap-8">
                <?php foreach ($ucsProgrammes as $ucsProgramme): ?>
                    <article class="group flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 sm:p-8">
                        <div class="flex items-center justify-between gap-4">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                <?php echo htmlspecialchars($ucsProgramme['degree_name']); ?>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <?php echo htmlspecialchars($ucsProgramme['short_name']); ?>
                            </span>
                        </div>

                        <h3 class="mt-5 text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">
                            <?php echo htmlspecialchars($ucsProgramme['name']); ?>
                        </h3>

                        <p class="mt-3 flex-1 text-sm leading-6 text-gray-600">
                            <?php echo htmlspecialchars($ucsProgramme['description']); ?>
                        </p>

                        <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="mt-6 inline-flex items-center gap-2 self-start rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-md hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Explore Programme
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="mt-12 text-center text-sm text-gray-500">Academic programme information is being updated. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>