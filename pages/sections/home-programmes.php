<?php
/**
 * Homepage Academic Programmes section.
 *
 * Clean editorial list of degree programmes.
 * Simple typography, clear hierarchy, no card-based layout.
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

$ucsProgrammes = array_values(array_filter($ucsProgrammes, function ($ucsProgramme) {
    $ucsDegree = trim((string) ($ucsProgramme['degree_name'] ?? ''));
    return $ucsDegree !== '' && strcasecmp($ucsDegree, 'Common Major') !== 0;
}));
?>
<section class="border-t border-gray-200 bg-white py-20 sm:py-24" aria-labelledby="programmes-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <div class="flex items-center justify-center gap-3">
                <span class="h-px w-8 bg-blue-600" aria-hidden="true"></span>
                <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-blue-600">Academic Programmes</span>
                <span class="h-px w-8 bg-blue-600" aria-hidden="true"></span>
            </div>
            <h2 id="programmes-heading" class="mt-4 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-gray-900 sm:text-4xl">Degrees &amp; Programmes</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">
                Explore the academic programmes offered by the University of Computer Studies, Meiktila.
            </p>
        </div>

        <?php if (count($ucsProgrammes) > 0): ?>
            <div class="mx-auto mt-12 max-w-3xl">
                <div class="divide-y divide-gray-200 border-t border-gray-200">
                    <?php foreach ($ucsProgrammes as $ucsProgramme): ?>
                        <article class="flex flex-col gap-3 py-8 first:pt-0 last:pb-0 sm:flex-row sm:items-baseline sm:justify-between sm:gap-8">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-lg font-semibold tracking-tight text-gray-900">
                                        <?php echo htmlspecialchars($ucsProgramme['name']); ?>
                                    </h3>
                                    <span class="hidden inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-[0.6875rem] font-medium text-gray-600 sm:inline-flex">
                                        <?php echo htmlspecialchars($ucsProgramme['short_name']); ?>
                                    </span>
                                </div>
                                <p class="mt-1.5 text-sm leading-6 text-gray-500">
                                    <?php echo htmlspecialchars($ucsProgramme['description']); ?>
                                </p>
                            </div>
                            <div class="shrink-0">
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                    <?php echo htmlspecialchars($ucsProgramme['degree_name']); ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="mt-10 text-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        View all programmes
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <p class="mt-12 text-center text-sm text-gray-500">Academic programme information is being updated. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>
