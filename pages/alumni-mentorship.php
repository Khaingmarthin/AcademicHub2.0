<?php
/**
 * Public Alumni Mentorship page.
 *
 * Lists verified alumni who are currently available for mentorship. Only
 * public, non-sensitive information is shown; contact channels are never
 * revealed here and are only shared with accepted students.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';
require_once __DIR__ . '/../includes/helpers/mentorship-validation.php';

$pageTitle = 'Alumni Mentorship';

$ucsHeroMedia = 'images/front_view.jpg';
try {
    $ucsProfileStmt = $pdo->query(
        "SELECT hero_media
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
    $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';
} catch (PDOException $e) {
    // Keep the default hero media.
}

$ucsQuery = trim((string) ($_GET['q'] ?? ''));

$ucsMentors = [];
try {
    $ucsSql = "SELECT ap.id, ap.current_job, ap.company, ap.profile_photo,
                      s.name AS student_name, s.graduation_year
               FROM alumni_profiles ap
               JOIN students s ON s.id = ap.student_id
               WHERE ap.verification_status = 'verified'
                 AND ap.visibility = 'public'
                 AND ap.mentorship_suspended = 0
                 AND ap.mentorship_available = 1";
    $ucsParams = [];
    if ($ucsQuery !== '') {
        $ucsEscaped  = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
        $ucsSql     .= " AND (s.name LIKE :q OR ap.current_job LIKE :q OR ap.company LIKE :q)";
        $ucsParams[':q'] = '%' . $ucsEscaped . '%';
    }
    $ucsSql .= " ORDER BY s.name ASC";

    $ucsStmt = $pdo->prepare($ucsSql);
    $ucsStmt->execute($ucsParams);
    $ucsMentors = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsMentors = [];
}

// Areas per mentor for the cards.
$ucsMentorAreas = [];
foreach ($ucsMentors as $ucsMentor) {
    $ucsMentorAreas[(int) $ucsMentor['id']] = mentorship_load_profile_areas($pdo, (int) $ucsMentor['id']);
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="mentorship-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">UCSMTLA Alumni</p>
                <h1 id="mentorship-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Alumni Mentorship</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Connect with verified UCSMTLA graduates who are available to guide your career journey.
                </p>
            </div>
        </div>
    </section>

    <!-- Mentors -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="mentorship-directory-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Available Mentors</p>
                <h2 id="mentorship-directory-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Mentors Open to Guiding Students</h2>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-gray-600">
                    Request mentorship from a verified alumnus. Contact details are shared only after the mentor accepts your request.
                </p>
            </div>

            <!-- Search -->
            <form method="get" action="<?php echo htmlspecialchars(BASE_URL . '/alumni-mentorship.php'); ?>" role="search" class="mx-auto mt-10 max-w-2xl rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search by name, profession or company…" aria-label="Search mentors by name, profession or company"
                               class="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Search
                    </button>
                </div>
                <?php if ($ucsQuery !== ''): ?>
                    <div class="mt-4 border-t border-gray-100 pt-4 text-center">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-mentorship.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                                <path d="M3 3v5h5"></path>
                            </svg>
                            Clear search
                        </a>
                    </div>
                <?php endif; ?>
            </form>

            <p class="mt-8 text-center text-sm font-medium text-gray-500" role="status">
                <span class="font-semibold text-gray-700"><?php echo count($ucsMentors); ?></span>
                verified alumn<?php echo count($ucsMentors) === 1 ? 'us' : 'i'; ?> currently available
                <?php echo $ucsQuery !== '' ? ' matching your search' : ''; ?>
            </p>

            <?php if (count($ucsMentors) > 0): ?>
                <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                    <?php foreach ($ucsMentors as $ucsMentor): ?>
                        <?php
                        $ucsProfileUrl = BASE_URL . '/alumni-details.php?id=' . (int) $ucsMentor['id'];
                        $ucsAreas      = $ucsMentorAreas[(int) $ucsMentor['id']] ?? [];

                        $ucsHasPhoto = false;
                        $ucsPhotoUrl = '';
                        if (!empty($ucsMentor['profile_photo'])) {
                            $ucsPhotoFile = dirname(__DIR__) . '/assets/' . ltrim($ucsMentor['profile_photo'], '/');
                            $ucsHasPhoto  = is_file($ucsPhotoFile);
                            if ($ucsHasPhoto) {
                                $ucsPhotoUrl = ROOT_URL . '/assets/' . ltrim($ucsMentor['profile_photo'], '/');
                            }
                        }
                        $ucsMentorName = trim((string) ($ucsMentor['student_name'] ?? ''));
                        ?>
                        <article class="ucs-reveal group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-900/10">
                            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 to-teal-700 px-6 pt-10 pb-14" aria-hidden="true">
                                <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-white/10" aria-hidden="true"></div>
                                <div class="absolute -bottom-10 -left-6 h-24 w-24 rounded-full bg-white/10" aria-hidden="true"></div>
                            </div>

                            <div class="-mt-10 flex flex-1 flex-col px-6 pb-6">
                                <div class="relative z-10">
                                    <?php if ($ucsHasPhoto): ?>
                                        <img src="<?php echo htmlspecialchars($ucsPhotoUrl); ?>" alt="<?php echo htmlspecialchars($ucsMentorName); ?>" class="h-20 w-20 rounded-2xl object-cover shadow-lg ring-4 ring-white">
                                    <?php else: ?>
                                        <span class="inline-flex h-20 w-20 items-center justify-center rounded-2xl bg-white text-xl font-extrabold text-emerald-700 shadow-lg ring-4 ring-white" aria-hidden="true">
                                            <?php echo htmlspecialchars(ucs_avatar_initial($ucsMentorName)); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <path d="m9 11 3 3L22 4"></path>
                                        </svg>
                                        Available
                                    </span>
                                    <?php if (!empty($ucsMentor['graduation_year'])): ?>
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 ring-1 ring-gray-200">Class of <?php echo htmlspecialchars((string) $ucsMentor['graduation_year']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="mt-3 text-lg font-bold leading-snug tracking-tight text-gray-900">
                                    <a href="<?php echo htmlspecialchars($ucsProfileUrl); ?>" class="transition-colors duration-150 hover:text-emerald-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                                        <?php echo htmlspecialchars($ucsMentorName); ?>
                                    </a>
                                </h3>

                                <?php if (!empty($ucsMentor['current_job']) || !empty($ucsMentor['company'])): ?>
                                    <p class="mt-1 text-sm font-medium text-gray-500">
                                        <?php echo htmlspecialchars((string) $ucsMentor['current_job']); ?>
                                        <?php if (!empty($ucsMentor['current_job']) && !empty($ucsMentor['company'])): ?><span class="text-gray-400"> at </span><?php endif; ?>
                                        <?php if (!empty($ucsMentor['company'])): ?><?php echo htmlspecialchars((string) $ucsMentor['company']); ?><?php endif; ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($ucsAreas)): ?>
                                    <div class="mt-4 flex flex-wrap gap-1.5">
                                        <?php foreach (array_slice($ucsAreas, 0, 4) as $ucsArea): ?>
                                            <span class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700 ring-1 ring-violet-100">
                                                <?php echo htmlspecialchars((string) $ucsArea['name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($ucsAreas) > 4): ?>
                                            <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-gray-100">+<?php echo count($ucsAreas) - 4; ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-5 flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                                    <a href="<?php echo htmlspecialchars($ucsProfileUrl); ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 transition-colors duration-150 hover:text-emerald-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                                        View Profile
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsMentor['id']); ?>"
                                       class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        Request Mentorship
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mt-10 rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-gray-200">
                    <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold text-gray-900">
                        <?php echo $ucsQuery !== '' ? 'No mentors found' : 'No mentors available yet'; ?>
                    </h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">
                        <?php echo $ucsQuery !== ''
                            ? 'No available mentors match your search. Try different keywords.'
                            : 'Alumni are preparing their mentorship availability. Please check back soon.'; ?>
                    </p>
                    <?php if ($ucsQuery !== ''): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-mentorship.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Clear search
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

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

<?php
require_once '../includes/footer.php';
?>