<?php
/**
 * Public Alumni Stories page.
 *
 * Lists admin-published alumni stories that highlight graduate experiences,
 * achievements and career journeys. Only stories with status 'published'
 * whose editorial publication date has arrived are shown. Story content is
 * written by admins; it is never auto-generated from alumni profile fields.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';

$pageTitle = 'Alumni Stories';

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

$ucsStories = [];
try {
    $ucsStmt = $pdo->prepare(
        "SELECT st.id, st.title, st.summary, st.content, st.career_field,
                st.cover_image, st.publication_date, st.created_at,
                s.name AS student_name, s.graduation_year,
                m.name AS major_name,
                ap.visibility, ap.verification_status
         FROM alumni_stories st
         JOIN alumni_profiles ap ON ap.id = st.alumni_profile_id
         JOIN students s ON s.id = ap.student_id
         JOIN classrooms cl ON cl.id = s.classroom_id
         JOIN majors m ON m.id = cl.major_id
         WHERE st.status = 'published'
           AND (st.publication_date IS NULL OR st.publication_date <= CURDATE())
           AND ap.verification_status = 'verified'
           AND s.student_status = 'graduated'
         ORDER BY st.publication_date DESC, st.id DESC"
    );
    $ucsStmt->execute();
    $ucsStories = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsStories = [];
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="alumni-stories-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">UCSMTLA Alumni</p>
                <h1 id="alumni-stories-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Alumni Stories</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Real experiences, achievements and career journeys from UCSMTLA graduates.
                </p>
            </div>
        </div>
    </section>

    <!-- Stories listing -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="alumni-stories-listing-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Graduate Journeys</p>
                <h2 id="alumni-stories-listing-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Career Paths After Graduation</h2>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-gray-600">
                    Learn how UCSMTLA graduates built their careers — the skills they learned, their internship experiences, the advice they would give and the lessons they carried forward.
                </p>
            </div>

            <?php if (count($ucsStories) > 0): ?>
                <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                    <?php foreach ($ucsStories as $ucsStory): ?>
                        <?php
                        $ucsStoryUrl = BASE_URL . '/alumni-story-details.php?id=' . (int) $ucsStory['id'];

                        $ucsHasCover = false;
                        $ucsCoverUrl = '';
                        if (!empty($ucsStory['cover_image'])) {
                            $ucsCoverFile = dirname(__DIR__) . '/assets/' . ltrim($ucsStory['cover_image'], '/');
                            $ucsHasCover  = is_file($ucsCoverFile);
                            if ($ucsHasCover) {
                                $ucsCoverUrl = ROOT_URL . '/assets/' . ltrim($ucsStory['cover_image'], '/');
                            }
                        }

                        $ucsStorySummary = ucs_short_summary((string) ($ucsStory['summary'] ?? ''), 180);

                        $ucsStoryDate = !empty($ucsStory['publication_date'])
                            ? (string) $ucsStory['publication_date']
                            : (string) ($ucsStory['created_at'] ?? '');
                        ?>
                        <article class="ucs-reveal group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-900/10">
                            <a href="<?php echo htmlspecialchars($ucsStoryUrl); ?>" class="relative block aspect-[16/10] shrink-0 overflow-hidden bg-slate-100">
                                <?php if ($ucsHasCover): ?>
                                    <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars((string) $ucsStory['title']); ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                <?php else: ?>
                                    <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-700" aria-hidden="true"></div>
                                    <div class="absolute -top-8 -right-8 h-28 w-28 rounded-full bg-white/10" aria-hidden="true"></div>
                                    <div class="absolute -bottom-10 -left-10 h-28 w-28 rounded-full bg-white/10" aria-hidden="true"></div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-xl bg-white text-blue-600 shadow-md ring-1 ring-gray-100 transition-transform duration-200 group-hover:scale-110">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                            </svg>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </a>

                            <div class="flex flex-1 flex-col p-5 sm:p-6">
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                        <?php echo !empty($ucsStory['career_field']) ? htmlspecialchars((string) $ucsStory['career_field']) : 'Alumni Story'; ?>
                                    </span>
                                    <?php if ($ucsStoryDate !== ''): ?>
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M8 2v4M16 2v4M3 10h18"></path>
                                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                            </svg>
                                            <?php echo htmlspecialchars(date('M j, Y', strtotime($ucsStoryDate))); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="mt-3 text-lg font-bold leading-snug tracking-tight text-gray-900">
                                    <a href="<?php echo htmlspecialchars($ucsStoryUrl); ?>" class="transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        <?php echo htmlspecialchars((string) $ucsStory['title']); ?>
                                    </a>
                                </h3>

                                <p class="mt-2 text-sm font-medium text-gray-500">
                                    <?php echo htmlspecialchars((string) $ucsStory['student_name']); ?>
                                    <?php if (!empty($ucsStory['major_name']) || !empty($ucsStory['graduation_year'])): ?>
                                        <span class="text-gray-400">&middot;</span>
                                        <?php echo htmlspecialchars((string) $ucsStory['major_name']); ?>
                                        <?php if (!empty($ucsStory['graduation_year'])): ?>
                                            <span class="text-gray-400">Class of</span> <?php echo htmlspecialchars((string) $ucsStory['graduation_year']); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </p>

                                <?php if ($ucsStorySummary !== ''): ?>
                                    <p class="mt-3 flex-1 text-sm leading-6 text-gray-600">
                                        <?php echo htmlspecialchars($ucsStorySummary); ?>
                                    </p>
                                <?php endif; ?>

                                <a href="<?php echo htmlspecialchars($ucsStoryUrl); ?>" class="mt-4 inline-flex items-center gap-1.5 self-start text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    Read More
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mt-12 rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-gray-200">
                    <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold text-gray-900">No stories yet</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">
                        We are collecting graduate stories from the UCSMTLA community. Please check back soon.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Cross-link to the directory -->
            <div class="mt-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 px-6 py-10 text-center sm:px-10">
                <h3 class="text-xl font-extrabold tracking-tight text-white sm:text-2xl">Meet the graduates behind the stories</h3>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-blue-100">
                    Browse the public alumni directory to see verified profiles, current roles and the journeys of UCSMTLA graduates.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-blue-700 shadow-lg transition-all duration-200 hover:bg-blue-50 hover:shadow-xl focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    View Alumni Directory
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
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