<?php
/**
 * Homepage University Statistics section.
 *
 * Clean stat cards with icons matching the UCSMTLA brand.
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

$ucsEstablishedYear = null;
$ucsStudentsCount   = null;
$ucsFacultiesCount  = null;
$ucsGraduatesCount  = null;

if (isset($pdo)) {
    try {
        $ucsProfile = $pdo->query(
            "SELECT established_year
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        )->fetch();

        $ucsEstablishedYear = ($ucsProfile && $ucsProfile['established_year'] !== null)
            ? (int) $ucsProfile['established_year']
            : null;

        $ucsStudentsCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM students WHERE status = 1"
        )->fetchColumn();

        $ucsFacultiesCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM faculties WHERE status = 1"
        )->fetchColumn();

        $ucsGraduatesCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM students WHERE student_status = 'graduated'"
        )->fetchColumn();
    } catch (PDOException $e) {
        $ucsEstablishedYear = null;
        $ucsStudentsCount   = null;
        $ucsFacultiesCount  = null;
        $ucsGraduatesCount  = null;
    }
}

$ucsYearsCount = ($ucsEstablishedYear !== null)
    ? (int) date('Y') - $ucsEstablishedYear
    : null;
?>
<section class="border-y border-slate-200 bg-white py-14 sm:py-18" aria-labelledby="stats-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 id="stats-heading" class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                UCSMTLA at a Glance
            </h2>
            <p class="mx-auto mt-3 max-w-2xl text-base leading-relaxed text-slate-500">
                A growing community of learners and educators dedicated to excellence in computing.
            </p>
        </div>

        <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <!-- Established -->
            <div class="group flex flex-col items-center rounded-xl border border-slate-200 bg-white px-4 py-6 text-center transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <path d="M3 9h18"></path>
                        <path d="M9 3v18"></path>
                        <path d="M13 9h4"></path>
                        <path d="M13 13h4"></path>
                        <path d="M13 17h4"></path>
                    </svg>
                </span>
                <span class="mt-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Established</span>
                <span class="mt-1 text-2xl font-extrabold text-blue-600 sm:text-3xl"><?php echo $ucsEstablishedYear !== null ? htmlspecialchars((string) $ucsEstablishedYear) : '—'; ?></span>
            </div>

            <!-- Years -->
            <div class="group flex flex-col items-center rounded-xl border border-slate-200 bg-white px-4 py-6 text-center transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </span>
                <span class="mt-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Years</span>
                <span class="mt-1 text-2xl font-extrabold text-blue-600 sm:text-3xl"><?php echo $ucsYearsCount !== null ? htmlspecialchars((string) $ucsYearsCount) : '—'; ?></span>
            </div>

            <!-- Students -->
            <div class="group flex flex-col items-center rounded-xl border border-slate-200 bg-white px-4 py-6 text-center transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </span>
                <span class="mt-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Students</span>
                <span class="mt-1 text-2xl font-extrabold text-blue-600 sm:text-3xl"><?php echo $ucsStudentsCount !== null ? number_format($ucsStudentsCount) : '—'; ?></span>
            </div>

            <!-- Faculties -->
            <div class="group flex flex-col items-center rounded-xl border border-slate-200 bg-white px-4 py-6 text-center transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                </span>
                <span class="mt-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Faculties</span>
                <span class="mt-1 text-2xl font-extrabold text-blue-600 sm:text-3xl"><?php echo $ucsFacultiesCount !== null ? number_format($ucsFacultiesCount) : '—'; ?></span>
            </div>

            <!-- Graduates -->
            <div class="group flex flex-col items-center rounded-xl border border-slate-200 bg-white px-4 py-6 text-center transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                </span>
                <span class="mt-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Graduates</span>
                <span class="mt-1 text-2xl font-extrabold text-blue-600 sm:text-3xl"><?php echo $ucsGraduatesCount !== null ? number_format($ucsGraduatesCount) : '—'; ?></span>
            </div>
        </div>
    </div>
</section>
