<?php
/**
 * Public Entrance Information page.
 *
 * Displays admission information for the current (Active) academic year.
 * Editorial institutional layout with structured bordered sections.
 */
require_once '../config/app.php';
require_once '../config/database.php';

$pageTitle = 'Entrance Information';

$ucsHeroMedia  = 'images/front_view.jpg';
$ucsAdmission  = null;

try {
    $ucsProfileStmt = $pdo->query(
        "SELECT short_name, hero_media, admission_description
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
    $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';

    if ($ucsProfileRow !== null) {
        $ucsAdmission = $ucsProfileRow['admission_description'] ?? '';
    }
} catch (PDOException $e) {
    $ucsAdmission = null;
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="admissions-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Entrance Information</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admissions</p>
                <h1 id="admissions-page-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                    Entrance Information
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    Find important information about university entrance admission, requirements, and application procedures.
                </p>
            </div>
        </div>
    </section>

    <?php if (!empty($ucsAdmission)): ?>
        <!-- Admission content -->
        <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="admission-content-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <div class="border border-slate-200 bg-white">
                        <div class="border-b border-slate-200 px-6 py-4 sm:px-8">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admission Information</p>
                            <h2 id="admission-content-heading" class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Entrance Information</h2>
                        </div>
                        <div class="px-6 py-6 sm:px-8 sm:py-8 text-sm leading-relaxed text-slate-600">
                            <?php echo nl2br(htmlspecialchars($ucsAdmission)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final action -->
        <section class="border-t border-slate-200 bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="admission-cta-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <div class="border border-slate-200 bg-white px-6 py-8 sm:px-8 sm:py-10">
                        <h2 id="admission-cta-heading" class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Ready to Apply?</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            View the list of admitted students for the current academic year.
                        </p>
                        <div class="mt-5">
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/admitted-students.php'); ?>" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                View Admitted Students
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- No admission data -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="admission-unavailable-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admissions</p>
                    <h1 id="admission-unavailable-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl leading-[1.1]">Information Unavailable</h1>
                    <p class="mt-4 text-base leading-relaxed text-slate-600">
                        Admission information is currently unavailable. Please check back soon.
                    </p>
                    <div class="mt-8">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Return Home
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
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
