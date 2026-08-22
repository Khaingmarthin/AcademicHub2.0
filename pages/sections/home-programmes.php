<?php
/**
 * Homepage Academic Programmes section.
 *
 * Clean editorial list of degree programmes.
 * Structured layout with borders, clear hierarchy, no floating cards.
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
<section class="border-t border-slate-200 bg-white py-20 sm:py-24" aria-labelledby="programmes-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Academic Programmes</p>
            <h2 id="programmes-heading" class="mt-3 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-slate-900 sm:text-4xl">Degrees &amp; Programmes</h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600">
                Explore the academic programmes offered by the University of Computer Studies, Meiktila.
            </p>
        </div>

        <?php if (count($ucsProgrammes) > 0): ?>
            <div class="mx-auto mt-12 max-w-3xl">
                <div class="border border-slate-200 bg-white">
                    <?php foreach ($ucsProgrammes as $ucsIdx => $ucsProgramme): ?>
                        <?php $ucsIsFirst = $ucsIdx === 0; ?>
                        <article class="flex flex-col gap-2 px-6 py-5 sm:px-8 sm:py-6 <?php echo !$ucsIsFirst ? 'border-t border-slate-200' : ''; ?> transition-colors duration-150 hover:bg-slate-50/60">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <h3 class="text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
                                    <?php echo htmlspecialchars($ucsProgramme['name']); ?>
                                </h3>
                                <span class="text-xs font-medium text-slate-400">
                                    <?php echo htmlspecialchars($ucsProgramme['short_name']); ?>
                                </span>
                            </div>
                            <p class="text-sm leading-relaxed text-slate-500">
                                <?php echo htmlspecialchars($ucsProgramme['description']); ?>
                            </p>
                            <div class="mt-1 flex items-center gap-3">
                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    <?php echo htmlspecialchars($ucsProgramme['degree_name']); ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 text-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        View all programmes
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <p class="mt-12 text-center text-sm text-slate-500">Academic programme information is being updated. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>
