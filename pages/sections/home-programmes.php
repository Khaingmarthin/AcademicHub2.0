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
<section class="bg-white py-20 sm:py-24" aria-labelledby="programmes-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-600">Degrees &amp; Majors</p>
            <h2 id="programmes-heading" class="mt-4 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Academic Programmes</h2>
            <span class="mx-auto mt-5 block h-1 w-12 rounded-full bg-blue-600" aria-hidden="true"></span>
            <p class="mt-5 text-base leading-7 text-gray-600">
                Explore the academic programmes offered by the University of Computer Studies, Meiktila.
            </p>
        </div>

        <?php if (count($ucsProgrammes) > 0): ?>
            <div class="mx-auto mt-14 grid max-w-4xl grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8">
                <?php foreach ($ucsProgrammes as $ucsProgramme): ?>
                    <?php
                    $ucsProgTag = strtoupper(trim((string) ($ucsProgramme['short_name'] ?? '')));
                    if ($ucsProgTag === 'CS') {
                        $ucsProgWatermark = '<path d="M8 9l-3 3 3 3M16 9l3 3-3 3"></path><path d="M13 5l-2 14"></path>';
                    } elseif ($ucsProgTag === 'CT') {
                        $ucsProgWatermark = '<rect x="5" y="5" width="14" height="14" rx="2"></rect><rect x="9" y="9" width="6" height="6"></rect><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"></path>';
                    } else {
                        $ucsProgWatermark = '<path d="M12 3l9 5-9 5-9-5 9-5Z"></path><path d="M3 13.5 12 18l9-4.5"></path>';
                    }
                    ?>
                    <article class="group relative flex flex-col overflow-hidden rounded-2xl border-t-4 border-blue-600 bg-white p-6 shadow-sm ring-1 ring-slate-200/70 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-900/5 sm:p-8">
                        <!-- Subtle watermark icon for visual interest -->
                        <svg class="pointer-events-none absolute -right-4 -top-4 h-28 w-28 text-blue-50 transition-colors duration-300 group-hover:text-blue-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <?php echo $ucsProgWatermark; ?>
                        </svg>

                        <div class="relative flex items-center justify-between gap-4">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-3.5 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                <?php echo htmlspecialchars($ucsProgramme['degree_name']); ?>
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-600" aria-hidden="true"></span>
                                <?php echo htmlspecialchars($ucsProgramme['short_name']); ?>
                            </span>
                        </div>

                        <h3 class="relative mt-6 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                            <?php echo htmlspecialchars($ucsProgramme['name']); ?>
                        </h3>

                        <p class="relative mt-3 flex-1 text-sm leading-relaxed text-slate-600">
                            <?php echo htmlspecialchars($ucsProgramme['description']); ?>
                        </p>

                        <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="relative mt-7 inline-flex items-center justify-center gap-2 self-start rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Explore Programme
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="mt-14 text-center text-sm text-slate-500">Academic programme information is being updated. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>