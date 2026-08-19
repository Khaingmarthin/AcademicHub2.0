<?php
/**
 * Public Alumni Story Details page.
 *
 * Shows a single admin-published alumni story selected by id: cover image,
 * title, alumni information, career field, full story content and a link to
 * the featured alumnus' public profile when one is publicly visible. Only
 * stories with status 'published' whose publication date has arrived are
 * shown.
 */
require_once '../config/app.php';
require_once '../config/database.php';

$pageTitle = 'Alumni Story';

$ucsStoryId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

$ucsStory    = null;
$ucsHeroMedia = 'images/front_view.jpg';

if ($ucsStoryId !== false && $ucsStoryId > 0) {
    try {
        $ucsProfileStmt = $pdo->query(
            "SELECT hero_media
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
        $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';

        $ucsStmt = $pdo->prepare(
            "SELECT st.id, st.title, st.summary, st.content, st.career_field,
                    st.cover_image, st.publication_date, st.created_at,
                    ap.id AS alumni_profile_id,
                    ap.current_job, ap.company,
                    ap.visibility, ap.verification_status,
                    s.name AS student_name, s.graduation_year,
                    m.name AS major_name
             FROM alumni_stories st
             JOIN alumni_profiles ap ON ap.id = st.alumni_profile_id
             JOIN students s ON s.id = ap.student_id
             JOIN classrooms cl ON cl.id = s.classroom_id
             JOIN majors m ON m.id = cl.major_id
             WHERE st.id = :id
               AND st.status = 'published'
               AND (st.publication_date IS NULL OR st.publication_date <= CURDATE())
               AND ap.verification_status = 'verified'
               AND s.student_status = 'graduated'
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $ucsStoryId]);
        $ucsStory = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsStory = null;
    }
}

if ($ucsStory !== null) {
    $pageTitle = (string) $ucsStory['title'];
}

// The related alumni profile is only linked when it is publicly visible.
$ucsProfileLinked = $ucsStory !== null
    && (string) ($ucsStory['verification_status'] ?? '') === 'verified'
    && (string) ($ucsStory['visibility'] ?? '') === 'public';

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <?php if ($ucsStory !== null): ?>
        <?php
        $ucsHasCover = false;
        $ucsCoverUrl = '';
        if (!empty($ucsStory['cover_image'])) {
            $ucsCoverFile = dirname(__DIR__) . '/assets/' . ltrim($ucsStory['cover_image'], '/');
            $ucsHasCover  = is_file($ucsCoverFile);
            if ($ucsHasCover) {
                $ucsCoverUrl = ROOT_URL . '/assets/' . ltrim($ucsStory['cover_image'], '/');
            }
        }

        $ucsStoryDate = !empty($ucsStory['publication_date'])
            ? (string) $ucsStory['publication_date']
            : (string) ($ucsStory['created_at'] ?? '');
        ?>
        <!-- Page hero -->
        <section class="relative overflow-hidden bg-gray-900" aria-labelledby="alumni-story-details-heading">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
            <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Alumni Stories</p>
                <h1 id="alumni-story-details-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    <?php echo htmlspecialchars((string) $ucsStory['title']); ?>
                </h1>
                <?php if ($ucsStoryDate !== ''): ?>
                    <p class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                        <?php echo htmlspecialchars(date('F j, Y', strtotime($ucsStoryDate))); ?>
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Story content -->
        <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="alumni-story-article-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <article class="overflow-hidden rounded-3xl bg-white shadow-lg shadow-gray-900/5 ring-1 ring-gray-100">
                    <?php if ($ucsHasCover): ?>
                        <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars((string) $ucsStory['title']); ?>" class="aspect-[21/9] w-full object-cover">
                    <?php else: ?>
                        <div class="flex aspect-[21/9] w-full items-center justify-center bg-gradient-to-br from-blue-600 to-indigo-700">
                            <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-md" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                </svg>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="p-6 sm:p-10">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <?php if (!empty($ucsStory['career_field'])): ?>
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                    <?php echo htmlspecialchars((string) $ucsStory['career_field']); ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">Alumni Story</span>
                            <?php endif; ?>
                        </div>

                        <h2 id="alumni-story-article-heading" class="mt-5 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                            <?php echo htmlspecialchars((string) $ucsStory['title']); ?>
                        </h2>

                        <!-- Alumni information -->
                        <div class="mt-6 flex flex-col gap-4 rounded-2xl bg-gray-50 p-5 ring-1 ring-gray-100 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-lg font-extrabold text-white" aria-hidden="true">
                                    <?php
                                    $ucsNameWords = preg_split('/\s+/', trim((string) ($ucsStory['student_name'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
                                    $ucsInitials  = 'A';
                                    if (count($ucsNameWords) > 0) {
                                        $ucsFirst = substr($ucsNameWords[0], 0, 1);
                                        $ucsLast  = count($ucsNameWords) > 1 ? substr(end($ucsNameWords), 0, 1) : '';
                                        $ucsInitials = strtoupper($ucsFirst . $ucsLast);
                                    }
                                    echo htmlspecialchars($ucsInitials);
                                    ?>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900">
                                        <?php if ($ucsProfileLinked): ?>
                                            <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsStory['alumni_profile_id']); ?>" class="transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                                <?php echo htmlspecialchars((string) $ucsStory['student_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars((string) $ucsStory['student_name']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mt-0.5 text-sm text-gray-500">
                                        <?php echo htmlspecialchars((string) ($ucsStory['major_name'] ?? '')); ?>
                                        <?php if (!empty($ucsStory['graduation_year'])): ?>
                                            &middot; Class of <?php echo htmlspecialchars((string) $ucsStory['graduation_year']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($ucsStory['current_job']) || !empty($ucsStory['company'])): ?>
                                        <p class="mt-0.5 text-sm font-medium text-gray-700">
                                            <?php echo htmlspecialchars((string) ($ucsStory['current_job'] ?? '')); ?>
                                            <?php if (!empty($ucsStory['current_job']) && !empty($ucsStory['company'])): ?><span class="font-normal text-gray-400">at</span><?php endif; ?>
                                            <?php echo htmlspecialchars((string) ($ucsStory['company'] ?? '')); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($ucsProfileLinked): ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsStory['alumni_profile_id']); ?>" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    View Alumni Profile
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (trim((string) ($ucsStory['content'] ?? '')) !== ''): ?>
                            <div class="mt-8 space-y-5 text-base leading-7 text-gray-600 sm:text-lg sm:leading-8">
                                <?php echo nl2br(htmlspecialchars((string) $ucsStory['content'])); ?>
                            </div>
                        <?php else: ?>
                            <p class="mt-8 text-base leading-7 text-gray-600">The full story content is not available yet.</p>
                        <?php endif; ?>
                    </div>
                </article>

                <div class="mt-8 text-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-stories.php'); ?>" class="group inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"></path>
                        </svg>
                        Back to Alumni Stories
                    </a>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Not found -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="alumni-story-not-found-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Alumni Stories</p>
                <h1 id="alumni-story-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Story Not Found</h1>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    The requested story could not be found or is no longer available.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-stories.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Browse Alumni Stories
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>