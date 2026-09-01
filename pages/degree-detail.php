<?php
/**
 * Public Degree Detail page.
 *
 * Shows full details for a single degree programme including
 * description, programme structure, and year breakdown.
 */
require_once '../config/app.php';
require_once '../config/database.php';

$ucsProgrammeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ucsProgramme   = null;
$ucsPageTitle   = 'Degree Programme';

if ($ucsProgrammeId > 0 && isset($pdo)) {
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id, name, short_name, degree_name, description
             FROM majors
             WHERE id = :id AND status = 1
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $ucsProgrammeId]);
        $ucsProgramme = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsProgramme = null;
    }
}

if ($ucsProgramme) {
    $ucsPageTitle = htmlspecialchars($ucsProgramme['name']) . ' - Degree Programme';
} else {
    $ucsPageTitle = 'Degree Programme Not Found';
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <?php if ($ucsProgramme): ?>
        <!-- Page header -->
        <section class="border-b border-slate-200 bg-white" aria-labelledby="degree-detail-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
                <div class="max-w-3xl">
                    <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                        <ol class="flex items-center gap-1.5">
                            <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                            <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                            <li><a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="transition-colors hover:text-slate-600">Degree Programmes</a></li>
                            <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                            <li class="text-slate-600"><?php echo htmlspecialchars($ucsProgramme['name']); ?></li>
                        </ol>
                    </nav>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Degree Programme</p>
                    <h1 id="degree-detail-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                        <?php echo htmlspecialchars($ucsProgramme['name']); ?>
                    </h1>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700">
                            <?php echo htmlspecialchars($ucsProgramme['degree_name']); ?>
                        </span>
                        <span class="text-sm text-slate-400">|</span>
                        <span class="text-sm text-slate-500">Posted on August 14, 2018 | by admin</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Programme detail -->
        <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="programme-detail-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl">
                    <!-- About the programme -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-8">
                        <p class="text-base leading-relaxed text-slate-700">
                            UCSMTLA's main offerings are four-year bachelor's programs in computer science and computer technology. The areas of study include artificial intelligence, bio-informatics, computer architecture, control applications, database systems, digital signal processing, image processing, Internet technologies, network security, operating systems, parallel and distributed computing, and software engineering.
                        </p>
                    </div>

                    <!-- Programme structure -->
                    <div class="mt-8">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 mb-4">Programme Structure</h2>
                        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50">
                                        <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Programme</th>
                                        <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Bachelor's</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-slate-200 last:border-b-0">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                            <?php echo htmlspecialchars($ucsProgramme['name']); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700">
                                                <?php echo htmlspecialchars($ucsProgramme['degree_name']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Year breakdown -->
                    <div class="mt-8">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 mb-4">Year Breakdown</h2>
                        <div class="rounded-2xl border border-slate-200 bg-white p-6">
                            <ul class="space-y-3">
                                <li class="flex items-center gap-3 text-sm text-slate-700">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-xs font-bold text-blue-700">1</span>
                                    Undergraduate First Year
                                </li>
                                <li class="flex items-center gap-3 text-sm text-slate-700">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-xs font-bold text-blue-700">2</span>
                                    Undergraduate Second Year
                                </li>
                                <li class="flex items-center gap-3 text-sm text-slate-700">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-xs font-bold text-blue-700">3</span>
                                    Undergraduate Third Year
                                </li>
                                <li class="flex items-center gap-3 text-sm text-slate-700">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-xs font-bold text-blue-700">4</span>
                                    Undergraduate Fourth Year
                                </li>
                                
                            </ul>
                        </div>
                    </div>

                    <!-- Back link -->
                    <div class="mt-8">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                            Back to Degree Programmes
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Programme not found -->
        <section class="border-b border-slate-200 bg-white" aria-labelledby="degree-not-found-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
                <div class="max-w-3xl">
                    <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                        <ol class="flex items-center gap-1.5">
                            <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                            <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                            <li><a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="transition-colors hover:text-slate-600">Degree Programmes</a></li>
                            <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                            <li class="text-slate-600">Not Found</li>
                        </ol>
                    </nav>
                    <h1 id="degree-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl">
                        Degree Programme Not Found
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                        The degree programme you are looking for does not exist or is no longer available.
                    </p>
                    <div class="mt-8">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                            Back to Degree Programmes
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>
