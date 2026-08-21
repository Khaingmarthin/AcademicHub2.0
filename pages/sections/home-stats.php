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

$ucsStatIcons = [
    'Established' => '<path d="M3 22h18M6 18v-7M10 18v-7M14 18v-7M18 18v-7M12 3l9 5H3l9-5z"></path>',
    'Years'       => '<path d="M8 2v4M16 2v4M3 10h18"></path><rect x="3" y="4" width="18" height="18" rx="2"></rect>',
    'Students'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>',
    'Faculties'   => '<rect x="4" y="2" width="16" height="20" rx="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"></path>',
    'Graduates'   => '<circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>',
];

// Per-stat colour theme so each card keeps its own identity while the whole
// band still reads as one cohesive, professional system.
$ucsStatThemes = [
    'Established' => [
        'icon'   => 'bg-gradient-to-br from-blue-500 to-indigo-600 shadow-blue-500/30 ring-blue-100',
        'number' => 'text-blue-600',
        'dot'    => 'bg-blue-500',
        'bar'    => 'from-blue-500 to-indigo-600',
        'blob'   => 'from-blue-400 to-indigo-500',
        'glow'   => 'hover:shadow-blue-500/20',
    ],
    'Years'       => [
        'icon'   => 'bg-gradient-to-br from-amber-400 to-orange-500 shadow-amber-500/30 ring-amber-100',
        'number' => 'text-amber-600',
        'dot'    => 'bg-amber-500',
        'bar'    => 'from-amber-400 to-orange-500',
        'blob'   => 'from-amber-300 to-orange-400',
        'glow'   => 'hover:shadow-amber-500/20',
    ],
    'Students'    => [
        'icon'   => 'bg-gradient-to-br from-emerald-400 to-teal-600 shadow-emerald-500/30 ring-emerald-100',
        'number' => 'text-emerald-600',
        'dot'    => 'bg-emerald-500',
        'bar'    => 'from-emerald-400 to-teal-600',
        'blob'   => 'from-emerald-300 to-teal-500',
        'glow'   => 'hover:shadow-emerald-500/20',
    ],
    'Faculties'   => [
        'icon'   => 'bg-gradient-to-br from-violet-500 to-purple-600 shadow-violet-500/30 ring-violet-100',
        'number' => 'text-violet-600',
        'dot'    => 'bg-violet-500',
        'bar'    => 'from-violet-500 to-purple-600',
        'blob'   => 'from-violet-400 to-purple-500',
        'glow'   => 'hover:shadow-violet-500/20',
    ],
    'Graduates'   => [
        'icon'   => 'bg-gradient-to-br from-rose-500 to-pink-600 shadow-rose-500/30 ring-rose-100',
        'number' => 'text-rose-600',
        'dot'    => 'bg-rose-500',
        'bar'    => 'from-rose-500 to-pink-600',
        'blob'   => 'from-rose-400 to-pink-500',
        'glow'   => 'hover:shadow-rose-500/20',
    ],
];

$ucsRevealDelay = 0;
?>
<section class="relative overflow-hidden bg-gradient-to-b from-white via-blue-50/60 to-white py-16 sm:py-20" aria-labelledby="stats-heading">
    <!-- Soft decorative colour washes -->
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-blue-100/60 blur-3xl"></div>
        <div class="absolute -right-24 bottom-4 h-72 w-72 rounded-full bg-blue-100/50 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 ring-1 ring-blue-100">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-500" aria-hidden="true"></span>
                University Statistics
            </span>
            <h2 id="stats-heading" class="mt-5 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">UCSMTLA at a Glance</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">A growing community of learners and educators dedicated to excellence in computing.</p>
        </div>

        <dl class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-5 lg:gap-6">
            <?php foreach ($ucsStats as $ucsLabel => $ucsValue): ?>
                <?php $ucsTheme = $ucsStatThemes[$ucsLabel] ?? $ucsStatThemes['Established']; ?>
                <div class="ucs-reveal" style="transition-delay: <?php echo $ucsRevealDelay; ?>ms;">
                    <div class="group relative flex h-full flex-col items-center overflow-hidden rounded-3xl bg-white px-4 pb-7 pt-8 text-center shadow-sm ring-1 ring-slate-900/5 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl <?php echo $ucsTheme['glow']; ?> sm:px-6">
                        <!-- Coloured corner wash -->
                        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-gradient-to-br <?php echo $ucsTheme['blob']; ?> opacity-20 blur-2xl transition-opacity duration-300 group-hover:opacity-40" aria-hidden="true"></div>

                        <span class="relative inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br <?php echo $ucsTheme['icon']; ?> text-white shadow-lg ring-4 transition-transform duration-300 group-hover:-rotate-3 group-hover:scale-110" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <?php echo $ucsStatIcons[$ucsLabel] ?? ''; ?>
                            </svg>
                        </span>

                        <dd class="order-2 mt-5 text-4xl font-extrabold tracking-tight <?php echo $ucsTheme['number']; ?> sm:text-5xl"<?php echo $ucsValue === null ? ' title="Information unavailable"' : ''; ?>>
                            <?php echo $ucsValue === null ? '—' : htmlspecialchars($ucsValue); ?>
                        </dd>
                        <dt class="order-3 mt-2.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <span class="inline-block h-1.5 w-1.5 rounded-full <?php echo $ucsTheme['dot']; ?>" aria-hidden="true"></span>
                            <?php echo htmlspecialchars($ucsLabel); ?>
                        </dt>

                        <!-- Accent bar revealed on hover -->
                        <span class="absolute inset-x-6 bottom-0 h-1 rounded-t-full bg-gradient-to-r <?php echo $ucsTheme['bar']; ?> opacity-0 transition-all duration-300 group-hover:inset-x-4 group-hover:opacity-100" aria-hidden="true"></span>
                    </div>
                </div>
                <?php $ucsRevealDelay += 70; ?>
            <?php endforeach; ?>
        </dl>
    </div>
</section>

<script>
    (function () {
        'use strict';
        document.documentElement.classList.add('ucs-js');

        var els = document.querySelectorAll('.ucs-reveal');
        if (!('IntersectionObserver' in window)) {
            els.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        els.forEach(function (el) { observer.observe(el); });
    })();
</script>
