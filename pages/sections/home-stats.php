<?php
/**
 * Homepage University Statistics section.
 *
 * Minimal institutional statistics. Simple numbers with labels.
 * No colorful themed cards, no decorative blobs.
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

$ucsYearsCount = ($ucsEstablishedYear !== null)
    ? (int) date('Y') - $ucsEstablishedYear
    : null;

$ucsStats = [
    ['label' => 'Established', 'value' => ($ucsEstablishedYear !== null) ? (string) $ucsEstablishedYear : null],
    ['label' => 'Years of Excellence', 'value' => ($ucsYearsCount !== null) ? (string) $ucsYearsCount : null],
    ['label' => 'Students', 'value' => ($ucsStudentsCount !== null) ? number_format($ucsStudentsCount) : null],
    ['label' => 'Faculties', 'value' => ($ucsFacultiesCount !== null) ? number_format($ucsFacultiesCount) : null],
];
?>
<section class="border-y border-gray-200 bg-gray-50 py-12 sm:py-16" aria-labelledby="stats-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="stats-heading" class="sr-only">University Statistics</h2>
        <dl class="grid grid-cols-2 gap-8 sm:grid-cols-4">
            <?php foreach ($ucsStats as $ucsStat): ?>
                <div class="text-center">
                    <dd class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl"<?php echo $ucsStat['value'] === null ? ' title="Information unavailable"' : ''; ?>>
                        <?php echo $ucsStat['value'] === null ? '—' : htmlspecialchars($ucsStat['value']); ?>
                    </dd>
                    <dt class="mt-1 text-sm font-medium text-gray-500"><?php echo htmlspecialchars($ucsStat['label']); ?></dt>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>
