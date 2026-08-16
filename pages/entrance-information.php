<?php
/**
 * Public Entrance Information page.
 *
 * Displays admission information for the current (Active) academic year,
 * including description, requirements, important dates, and application info.
 * All values come from the admissions table joined with academic_years —
 * nothing is hard-coded.
 */
require_once '../config/app.php';
require_once '../includes/database.php';

$pageTitle = 'Entrance Information';

$ucsHeroMedia  = 'images/front_view.jpg';
$ucsAdmission  = null;
$ucsYearName   = '';

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
        "SELECT a.id, a.title, a.description, a.requirements,
                a.important_dates, a.application_info,
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
            "SELECT a.id, a.title, a.description, a.requirements,
                    a.important_dates, a.application_info,
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
    }
} catch (PDOException $e) {
    $ucsAdmission = null;
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = BASE_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="admissions-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-5xl px-4 py-14 text-center sm:px-6 sm:py-16">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Admissions</p>
                <h1 id="admissions-page-heading" class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">Entrance Information</h1>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Find important information about university entrance admission, requirements, and application procedures.
                </p>
                <?php if ($ucsYearName !== ''): ?>
                    <span class="mt-5 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold text-white ring-1 ring-white/25 backdrop-blur">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                        Academic Year <?php echo htmlspecialchars($ucsYearName); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($ucsAdmission !== null): ?>
        <!-- Main content -->
        <section class="bg-slate-50 py-12 sm:py-16" aria-labelledby="admission-page-content">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="scroll-mt-24 rounded-2xl bg-white px-6 py-10 shadow-sm ring-1 ring-gray-100 sm:px-10 sm:py-12">

                    <!-- Academic Year highlight -->
                    <?php if ($ucsYearName !== ''): ?>
                        <div class="mb-10 inline-flex items-center gap-3 rounded-xl bg-blue-50 px-5 py-4 ring-1 ring-blue-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8 2v4M16 2v4M3 10h18"></path>
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            </svg>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Academic Year</p>
                                <p class="mt-0.5 text-lg font-bold text-gray-900"><?php echo htmlspecialchars($ucsYearName); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Admission Overview -->
                    <div class="border-b border-gray-100 pb-10">
                        <h2 id="admission-page-content" class="text-xl font-semibold tracking-tight text-gray-900">Admission Overview</h2>
                        <?php if (!empty($ucsAdmission['title'])): ?>
                            <h3 class="mt-4 text-lg font-semibold tracking-tight text-gray-900"><?php echo htmlspecialchars($ucsAdmission['title']); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($ucsAdmission['description'])): ?>
                            <p class="mt-2 max-w-3xl text-base leading-7 text-gray-600"><?php echo htmlspecialchars($ucsAdmission['description']); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($ucsAdmission['requirements'])): ?>
                        <!-- Requirements -->
                        <div class="border-b border-gray-100 py-10">
                            <h2 class="flex items-center gap-2.5 text-lg font-semibold tracking-tight text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path>
                                </svg>
                                Admission Requirements
                            </h2>
                            <div class="mt-4 max-w-3xl text-base leading-7 text-gray-600">
                                <?php echo nl2br(htmlspecialchars($ucsAdmission['requirements'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($ucsAdmission['important_dates'])): ?>
                        <!-- Important Information -->
                        <div class="border-b border-gray-100 py-10">
                            <h2 class="flex items-center gap-2.5 text-lg font-semibold tracking-tight text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4M12 8h.01"></path>
                                </svg>
                                Important Information
                            </h2>
                            <div class="mt-4 max-w-3xl text-base leading-7 text-gray-600">
                                <?php echo nl2br(htmlspecialchars($ucsAdmission['important_dates'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($ucsAdmission['application_info'])): ?>
                        <!-- Application Information -->
                        <div class="py-10">
                            <h2 class="flex items-center gap-2.5 text-lg font-semibold tracking-tight text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2Z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                Application Information
                            </h2>
                            <div class="mt-4 max-w-3xl text-base leading-7 text-gray-600">
                                <?php echo nl2br(htmlspecialchars($ucsAdmission['application_info'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Admitted Student List CTA -->
                    <div class="mt-4 border-t border-gray-100 pt-8 text-center">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/admitted-students.php'); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-md hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            View Admitted Students
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- No admission data -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="admission-unavailable-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admissions</p>
                <h1 id="admission-unavailable-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Information Unavailable</h1>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    Admission information is currently unavailable. Please check back soon.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Return Home
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>