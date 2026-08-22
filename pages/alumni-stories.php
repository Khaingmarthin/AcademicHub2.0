<?php
/**
 * Public Alumni Stories page.
 *
 * Lists admin-published alumni stories that highlight graduate experiences,
 * achievements and career journeys. Only stories with status 'published'
 * whose editorial publication date has arrived are shown.
 *
 * Editorial layout with bordered story cards and clear hierarchy.
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
    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="alumni-stories-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Alumni Stories</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">UCSMTLA Alumni</p>
                <h1 id="alumni-stories-page-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                    Alumni Stories
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    Real experiences, achievements and career journeys from UCSMTLA graduates.
                </p>
            </div>
        </div>
    </section>

    <!-- Stories listing -->
    <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="alumni-stories-listing-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Graduate Journeys</p>
                <h2 id="alumni-stories-listing-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl leading-[1.1]">
                    Career Paths After Graduation
                </h2>
                <p class="mt-3 max-w-xl text-base leading-relaxed text-slate-600">
                    Learn how UCSMTLA graduates built their careers — the skills they learned, their internship experiences, the advice they would give and the lessons they carried forward.
                </p>
            </div>

            <?php if (count($ucsStories) > 0): ?>
                <div class="mx-auto mt-10 max-w-4xl">
                    <div class="border border-slate-200 bg-white">
                        <?php foreach ($ucsStories as $ucsIdx => $ucsStory): ?>
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

                            $ucsIsFirst = $ucsIdx === 0;
                            ?>
                            <article class="group <?php echo !$ucsIsFirst ? 'border-t border-slate-200' : ''; ?> transition-colors duration-150 hover:bg-slate-50/60">
                                <div class="grid grid-cols-1 gap-0 sm:grid-cols-[12rem_1fr]">
                                    <?php if ($ucsHasCover): ?>
                                        <div class="border-b border-slate-200 sm:border-b-0 sm:border-r">
                                            <a href="<?php echo htmlspecialchars($ucsStoryUrl); ?>" class="block">
                                                <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars((string) $ucsStory['title']); ?>" class="h-40 w-full object-cover sm:h-full">
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <div class="px-6 py-5 sm:px-8 sm:py-6">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                                <?php echo !empty($ucsStory['career_field']) ? htmlspecialchars((string) $ucsStory['career_field']) : 'Alumni Story'; ?>
                                            </span>
                                            <?php if ($ucsStoryDate !== ''): ?>
                                                <span class="text-xs font-medium text-slate-400">
                                                    <?php echo htmlspecialchars(date('M j, Y', strtotime($ucsStoryDate))); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <h3 class="mt-2.5 text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
                                            <a href="<?php echo htmlspecialchars($ucsStoryUrl); ?>" class="transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                                <?php echo htmlspecialchars((string) $ucsStory['title']); ?>
                                            </a>
                                        </h3>

                                        <p class="mt-1 text-sm font-medium text-slate-500">
                                            <?php echo htmlspecialchars((string) $ucsStory['student_name']); ?>
                                            <?php if (!empty($ucsStory['major_name']) || !empty($ucsStory['graduation_year'])): ?>
                                                <span class="text-slate-400">&middot;</span>
                                                <?php echo htmlspecialchars((string) $ucsStory['major_name']); ?>
                                                <?php if (!empty($ucsStory['graduation_year'])): ?>
                                                    <span class="text-slate-400">Class of</span> <?php echo htmlspecialchars((string) $ucsStory['graduation_year']); ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </p>

                                        <?php if ($ucsStorySummary !== ''): ?>
                                            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                                                <?php echo htmlspecialchars($ucsStorySummary); ?>
                                            </p>
                                        <?php endif; ?>

                                        <a href="<?php echo htmlspecialchars($ucsStoryUrl); ?>" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                            Read More
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="mx-auto mt-10 max-w-4xl border border-slate-200 bg-white px-6 py-14 text-center sm:px-8">
                    <h3 class="text-lg font-bold text-slate-900">No stories yet</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-500">
                        We are collecting graduate stories from the UCSMTLA community. Please check back soon.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Cross-link to the directory — bordered panel -->
            <div class="mx-auto mt-10 max-w-4xl border border-slate-200 bg-white px-6 py-8 text-center sm:px-10">
                <h3 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Meet the graduates behind the stories</h3>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-slate-500">
                    Browse the public alumni directory to see verified profiles, current roles and the journeys of UCSMTLA graduates.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    View Alumni Directory
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>
