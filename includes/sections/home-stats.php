<?php
/**
 * Homepage University Statistics section.
 *
 * Displays five institutional statistics. Values are read from the database:
 * established year (university_profile), years (calculated from the
 * established year), students (students table), faculties (faculties table)
 * and graduates. When a value is unavailable a clean dash fallback is shown.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

// Reuse the existing database connection if the page already connected.
if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}

$ucsEstablishedYear = null;
$ucsStudentsCount   = null;
$ucsFacultiesCount  = null;

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
    } catch (PDOException $e) {
        $ucsEstablishedYear = null;
        $ucsStudentsCount   = null;
        $ucsFacultiesCount  = null;
    }
}

// Years are always calculated from the established year to the current year.
$ucsYearsCount = ($ucsEstablishedYear !== null)
    ? (int) date('Y') - $ucsEstablishedYear
    : null;

$ucsStats = [
    'Established' => ($ucsEstablishedYear !== null) ? (string) $ucsEstablishedYear : null,
    'Years'       => ($ucsYearsCount !== null) ? (string) $ucsYearsCount : null,
    'Students'    => ($ucsStudentsCount !== null) ? number_format($ucsStudentsCount) : null,
    'Faculties'   => ($ucsFacultiesCount !== null) ? number_format($ucsFacultiesCount) : null,
    'Graduates'   => null, // No graduate data exists in the database yet.
];
?>
<section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="stats-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">University Statistics</p>
            <h2 id="stats-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">UCSMTLA at a Glance</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">A growing community of learners and educators dedicated to excellence in computing.</p>
        </div>

        <dl class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-5 lg:gap-8">
            <?php foreach ($ucsStats as $ucsLabel => $ucsValue): ?>
                <div class="flex flex-col items-center rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5">
                    <dt class="order-2 mt-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        <?php echo htmlspecialchars($ucsLabel); ?>
                    </dt>
                    <dd class="order-1 text-3xl font-extrabold tracking-tight text-blue-600 sm:text-4xl"<?php echo $ucsValue === null ? ' title="Information unavailable"' : ''; ?>>
                        <?php echo $ucsValue === null ? '—' : htmlspecialchars($ucsValue); ?>
                    </dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>