<?php
/**
 * Homepage Academic Programmes section.
 *
 * Card-based layout with "Explore Programme" buttons.
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
            "SELECT id, name, short_name, degree_name, description
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
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Degrees &amp; Majors</p>
            <h2 id="programmes-heading" class="mt-3 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-slate-900 sm:text-4xl">Academic Programmes</h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600">
                Explore the academic programmes offered by the University of Computer Studies, Meiktila.
            </p>
        </div>

        <?php if (count($ucsProgrammes) > 0): ?>
            <div class="mx-auto mt-12 grid max-w-5xl gap-8 sm:grid-cols-2">
                <?php foreach ($ucsProgrammes as $ucsProgramme): ?>
                    <article class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-8 transition-all duration-200 hover:shadow-lg hover:border-slate-300">
                        <div class="flex items-start justify-between mb-6">
                            <span class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700">
                                <?php echo htmlspecialchars($ucsProgramme['degree_name']); ?>
                            </span>
                            <span class="text-sm font-medium text-slate-400">
                                <?php echo htmlspecialchars($ucsProgramme['short_name']); ?>
                            </span>
                        </div>

                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 mb-4">
                            <?php echo htmlspecialchars($ucsProgramme['name']); ?>
                        </h3>

                        <p class="text-sm leading-relaxed text-slate-500 mb-8 flex-1">
                            An undergraduate major focusing on <?php echo strtolower(htmlspecialchars($ucsProgramme['name'])); ?>, software development, algorithms and computing concepts.
                        </p>

                        <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-detail.php?id=' . (int) $ucsProgramme['id']); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-blue-700 hover:shadow-md focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Explore Programme
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="mt-12 text-center text-sm text-slate-500">Academic programme information is being updated. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>
