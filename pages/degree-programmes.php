<?php
/**
 * Public Degree Programmes page.
 *
 * Displays the degree programmes stored in the majors table. Common or
 * foundation majors (e.g. the first-year shared major) are excluded so that
 * only actual degree programmes are shown. The page uses an editorial
 * structured layout with clear hierarchy.
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
    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="degree-programmes-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Degree Programmes</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Academic Programmes</p>
                <h1 id="degree-programmes-page-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                    Degree Programmes
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    Our undergraduate programmes combine strong academic foundations with practical, hands-on
                    learning to prepare students for the modern technology field.
                </p>
            </div>
        </div>
    </section>

    <!-- Programme directory -->
    <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="programmes-directory-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl">
                <div class="border-b border-slate-200 pb-3">
                    <h2 id="programmes-directory-heading" class="text-lg font-semibold tracking-tight text-slate-900">Undergraduate Programmes</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        <?php echo count($ucsProgrammes); ?> programme<?php echo count($ucsProgrammes) !== 1 ? 's' : ''; ?> offered by <?php echo htmlspecialchars($ucsShortName); ?>
                    </p>
                </div>

                <?php if (count($ucsProgrammes) > 0): ?>
                    <div class="mt-0 border border-slate-200 bg-white">
                        <?php foreach ($ucsProgrammes as $ucsProgramme): ?>
                            <article class="flex flex-col gap-3 px-6 py-5 sm:px-8 sm:py-6 border-t border-slate-200 first:border-t-0 transition-colors duration-150 hover:bg-slate-50/60">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
                                            <?php echo htmlspecialchars($ucsProgramme['name']); ?>
                                        </h3>
                                        <div class="mt-1 flex items-center gap-3">
                                            <span class="text-xs font-medium text-slate-400">
                                                <?php echo htmlspecialchars($ucsProgramme['short_name']); ?>
                                            </span>
                                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                                <?php echo htmlspecialchars($ucsProgramme['degree_name']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($ucsProgramme['description'])): ?>
                                    <p class="text-sm leading-relaxed text-slate-500">
                                        <?php echo htmlspecialchars($ucsProgramme['description']); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flex items-center gap-4">
                                    <?php if (!empty($ucsProgramme['id'])): ?>
                                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php?major=' . (int) $ucsProgramme['id']); ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                            View Alumni
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mt-8 border border-slate-200 bg-white px-6 py-12 text-center sm:px-8">
                        <p class="text-sm text-slate-500">No degree programme information is currently available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>
