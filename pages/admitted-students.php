<?php
/**
 * Public Admitted Student List page.
 *
 * Displays the admitted student document (PDF) for the current (Active)
 * academic year. Editorial layout with clean bordered panels.
 */
require_once '../config/app.php';
require_once '../config/database.php';

$pageTitle = 'Admitted Student List';

$ucsHeroMedia  = 'images/front_view.jpg';
$ucsAdmission  = null;
$ucsYearName   = '';
$ucsPdfUrl     = '';
$ucsHasPdf     = false;

try {
    $ucsProfileStmt = $pdo->query(
        "SELECT short_name, hero_media
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
    $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';

    // Fetch admission for the Active academic year; fall back to the latest record.
    $ucsStmt = $pdo->prepare(
        "SELECT a.id, a.title, a.description,
                a.document_title, a.document_path, a.document_type,
                ay.year_name, ay.status AS year_status
         FROM admissions a
         JOIN academic_years ay ON ay.id = a.academic_year_id
         WHERE a.status = 1 AND ay.status = 'Active'
         ORDER BY a.id DESC
         LIMIT 1"
    );
    $ucsStmt->execute();
    $ucsAdmission = $ucsStmt->fetch() ?: null;

    if ($ucsAdmission === null) {
        $ucsStmt = $pdo->prepare(
            "SELECT a.id, a.title, a.description,
                    a.document_title, a.document_path, a.document_type,
                    ay.year_name, ay.status AS year_status
             FROM admissions a
             JOIN academic_years ay ON ay.id = a.academic_year_id
             WHERE a.status = 1
             ORDER BY a.id DESC
             LIMIT 1"
        );
        $ucsStmt->execute();
        $ucsAdmission = $ucsStmt->fetch() ?: null;
    }

    if ($ucsAdmission !== null) {
        $ucsYearName = $ucsAdmission['year_name'] ?? '';

        // Verify the PDF exists on disk.
        if (!empty($ucsAdmission['document_path'])) {
            $ucsPdfFile = dirname(__DIR__) . '/assets/uploads/' . ltrim($ucsAdmission['document_path'], '/');
            $ucsHasPdf  = is_file($ucsPdfFile);
            if ($ucsHasPdf) {
                $ucsPdfUrl = ROOT_URL . '/assets/uploads/' . ltrim($ucsAdmission['document_path'], '/');
            }
        }
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
    <section class="border-b border-slate-200 bg-white" aria-labelledby="admitted-students-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/entrance-information.php'); ?>" class="transition-colors hover:text-slate-600">Entrance Information</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Admitted Student List</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admissions</p>
                <h1 id="admitted-students-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                    Admitted Student List
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    View the official admission results for the <?php echo htmlspecialchars($ucsYearName !== '' ? $ucsYearName : 'current'); ?> academic year.
                </p>
                <?php if ($ucsYearName !== ''): ?>
                    <div class="mt-5 inline-flex items-center gap-2 rounded-md bg-blue-50 px-3.5 py-2 text-sm font-semibold text-blue-700 ring-1 ring-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                        Academic Year <?php echo htmlspecialchars($ucsYearName); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($ucsAdmission !== null): ?>
        <!-- Admission Results -->
        <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="admission-results-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Document header -->
                <div class="border border-slate-200 bg-white">
                    <div class="border-b border-slate-200 px-6 py-4 sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admission Results</p>
                        <h2 id="admission-results-heading" class="mt-1 text-lg font-semibold tracking-tight text-slate-900">
                            <?php echo htmlspecialchars($ucsAdmission['document_title'] ?? 'Admitted Student List'); ?>
                        </h2>
                    </div>

                    <?php if (!empty($ucsAdmission['description'])): ?>
                        <div class="px-6 py-5 sm:px-8 text-sm leading-relaxed text-slate-600">
                            <?php echo htmlspecialchars($ucsAdmission['description']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($ucsHasPdf): ?>
                        <!-- PDF Viewer -->
                        <div class="border-t border-slate-200">
                            <iframe
                                src="<?php echo htmlspecialchars($ucsPdfUrl); ?>"
                                title="<?php echo htmlspecialchars($ucsAdmission['document_title'] ?? 'Admitted Student List'); ?>"
                                class="h-[500px] w-full border-0 sm:h-[600px] lg:h-[700px]"
                                loading="lazy"
                            ></iframe>
                        </div>

                        <!-- Action buttons -->
                        <div class="border-t border-slate-200 px-6 py-4 sm:px-8">
                            <div class="flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                                <a href="<?php echo htmlspecialchars($ucsPdfUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                        <polyline points="15 3 21 3 21 9"></polyline>
                                        <line x1="10" y1="14" x2="21" y2="3"></line>
                                    </svg>
                                    View PDF
                                </a>
                                <a href="<?php echo htmlspecialchars($ucsPdfUrl); ?>" download class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Empty state: no PDF available -->
                        <div class="border-t border-slate-200 px-6 py-12 sm:px-8 text-center">
                            <p class="text-base font-semibold text-slate-900">Document Not Available</p>
                            <p class="mt-2 text-sm text-slate-500">
                                The admitted student list has not been published yet.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Back link -->
                <div class="mt-8 flex items-center gap-6">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/entrance-information.php'); ?>" class="group inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"></path>
                        </svg>
                        Back to Entrance Information
                    </a>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php#location-contact-heading'); ?>" class="group inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 transition-colors duration-150 hover:text-slate-800 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Contact Admissions
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- No admission data -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="admitted-not-found-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admissions</p>
                <h1 id="admitted-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Information Unavailable</h1>
                <p class="mt-4 text-base leading-relaxed text-slate-600">
                    The admitted student list has not been published yet.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/entrance-information.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Entrance Information
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>
