<?php
/**
 * Public Admitted Student List page.
 *
 * Displays the admitted student document (PDF) for the current (Active)
 * academic year. The document path comes from the admissions table — nothing
 * is invented. A browser PDF viewer (iframe) is used when the file exists;
 * otherwise a professional empty state is shown.
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
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="admitted-students-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Admissions</p>
                <h1 id="admitted-students-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Admitted Student List</h1>
                <?php if ($ucsYearName !== ''): ?>
                    <span class="mt-4 inline-flex items-center rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold text-white ring-1 ring-white/25 backdrop-blur">
                        Academic Year <?php echo htmlspecialchars($ucsYearName); ?>
                    </span>
                <?php endif; ?>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    View the official admission results for the <?php echo htmlspecialchars($ucsYearName !== '' ? $ucsYearName : 'current'); ?> academic year.
                </p>
            </div>
        </div>
    </section>

    <?php if ($ucsAdmission !== null): ?>
        <!-- Admission Results -->
        <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="admission-results-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100 sm:p-10">
                    <div class="text-center">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admission Results</p>
                        <h2 id="admission-results-heading" class="mt-3 scroll-mt-24 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                            <?php echo htmlspecialchars($ucsAdmission['document_title'] ?? 'Admitted Student List'); ?>
                        </h2>
                        <?php if (!empty($ucsAdmission['description'])): ?>
                            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-gray-600">
                                <?php echo htmlspecialchars($ucsAdmission['description']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if ($ucsHasPdf): ?>
                        <!-- PDF Viewer -->
                        <div class="mt-8 overflow-hidden rounded-xl border border-gray-200 shadow-sm">
                            <iframe
                                src="<?php echo htmlspecialchars($ucsPdfUrl); ?>"
                                title="<?php echo htmlspecialchars($ucsAdmission['document_title'] ?? 'Admitted Student List'); ?>"
                                class="h-[500px] w-full border-0 sm:h-[600px] lg:h-[700px]"
                                loading="lazy"
                            ></iframe>
                        </div>

                        <!-- Action buttons -->
                        <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                            <a href="<?php echo htmlspecialchars($ucsPdfUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15 3 21 3 21 9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                                View PDF
                            </a>
                            <a href="<?php echo htmlspecialchars($ucsPdfUrl); ?>" download class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-gray-700 ring-1 ring-gray-200 transition-all duration-200 hover:bg-gray-50 hover:text-gray-900 hover:ring-gray-300 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Download PDF
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Empty state: no PDF available -->
                        <div class="mt-8 rounded-xl border border-dashed border-gray-200 bg-slate-50 p-10 text-center">
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <path d="M14 2v6h6"></path>
                                </svg>
                            </span>
                            <p class="mt-4 text-base font-semibold text-gray-900">Document Not Available</p>
                            <p class="mt-2 text-sm text-gray-500">
                                The admitted student list has not been published yet.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Back link -->
                <div class="mt-8 text-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/entrance-information.php'); ?>" class="group inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"></path>
                        </svg>
                        Back to Entrance Information
                    </a>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- No admission data -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="admitted-not-found-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admissions</p>
                <h1 id="admitted-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Information Unavailable</h1>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    The admitted student list has not been published yet.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/entrance-information.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Entrance Information
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>