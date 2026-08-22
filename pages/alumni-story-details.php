<?php
/**
 * Public Alumni Story Details page.
 *
 * Shows a single admin-published alumni story selected by id: cover image,
 * title, alumni information, career field, full story content and a link to
 * the featured alumnus' public profile when one is publicly visible.
 *
 * Editorial layout with structured information hierarchy.
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

        <!-- Page header -->
        <section class="border-b border-slate-200 bg-white" aria-labelledby="alumni-story-details-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
                <div class="max-w-3xl">
                    <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                        <ol class="flex items-center gap-1.5">
                            <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                            <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                            <li><a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-stories.php'); ?>" class="transition-colors hover:text-slate-600">Alumni Stories</a></li>
                            <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                            <li class="text-slate-600 max-w-[12rem] truncate"><?php echo htmlspecialchars((string) $ucsStory['title']); ?></li>
                        </ol>
                    </nav>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Alumni Stories</p>
                    <h1 id="alumni-story-details-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.5rem] leading-[1.1]">
                        <?php echo htmlspecialchars((string) $ucsStory['title']); ?>
                    </h1>
                    <?php if ($ucsStoryDate !== ''): ?>
                        <p class="mt-3 flex items-center gap-2 text-sm font-medium text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8 2v4M16 2v4M3 10h18"></path>
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            </svg>
                            <?php echo htmlspecialchars(date('F j, Y', strtotime($ucsStoryDate))); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Story content -->
        <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="alumni-story-article-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl">
                    <!-- Cover image -->
                    <?php if ($ucsHasCover): ?>
                        <div class="border border-slate-200 bg-white overflow-hidden">
                            <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars((string) $ucsStory['title']); ?>" class="aspect-[21/9] w-full object-cover">
                        </div>
                    <?php endif; ?>

                    <!-- Story body -->
                    <div class="mt-6 border border-slate-200 bg-white">
                        <div class="px-6 py-6 sm:px-8 sm:py-8">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                <?php if (!empty($ucsStory['career_field'])): ?>
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                        <?php echo htmlspecialchars((string) $ucsStory['career_field']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">Alumni Story</span>
                                <?php endif; ?>
                            </div>

                            <h2 id="alumni-story-article-heading" class="sr-only">Story Content</h2>

                            <?php if (trim((string) ($ucsStory['content'] ?? '')) !== ''): ?>
                                <div class="mt-6 space-y-4 text-sm leading-relaxed text-slate-600 sm:text-base sm:leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars((string) $ucsStory['content'])); ?>
                                </div>
                            <?php else: ?>
                                <p class="mt-6 text-sm leading-relaxed text-slate-500">The full story content is not available yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Alumni information — bordered panel -->
                    <div class="mt-6 border border-slate-200 bg-white">
                        <div class="border-b border-slate-200 px-6 py-4 sm:px-8">
                            <h3 class="text-sm font-semibold text-slate-900">About the Author</h3>
                        </div>
                        <div class="px-6 py-5 sm:px-8 sm:py-6">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-4">
                                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-sm font-bold text-blue-700 ring-1 ring-blue-100" aria-hidden="true">
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
                                        <p class="text-sm font-semibold text-slate-900">
                                            <?php if ($ucsProfileLinked): ?>
                                                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsStory['alumni_profile_id']); ?>" class="transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                                    <?php echo htmlspecialchars((string) $ucsStory['student_name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars((string) $ucsStory['student_name']); ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mt-0.5 text-sm text-slate-500">
                                            <?php echo htmlspecialchars((string) ($ucsStory['major_name'] ?? '')); ?>
                                            <?php if (!empty($ucsStory['graduation_year'])): ?>
                                                &middot; Class of <?php echo htmlspecialchars((string) $ucsStory['graduation_year']); ?>
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($ucsStory['current_job']) || !empty($ucsStory['company'])): ?>
                                            <p class="mt-0.5 text-sm text-slate-600">
                                                <?php echo htmlspecialchars((string) ($ucsStory['current_job'] ?? '')); ?>
                                                <?php if (!empty($ucsStory['current_job']) && !empty($ucsStory['company'])): ?><span class="text-slate-400"> at </span><?php endif; ?>
                                                <?php echo htmlspecialchars((string) ($ucsStory['company'] ?? '')); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($ucsProfileLinked): ?>
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsStory['alumni_profile_id']); ?>" class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        View Alumni Profile
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="mt-8 flex items-center gap-6">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-stories.php'); ?>" class="group inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 12H5M12 19l-7-7 7-7"></path>
                            </svg>
                            Back to Alumni Stories
                        </a>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="group inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 transition-colors duration-150 hover:text-slate-800 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Alumni Directory
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Not found -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="alumni-story-not-found-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Alumni Stories</p>
                <h1 id="alumni-story-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Story Not Found</h1>
                <p class="mt-4 text-base leading-relaxed text-slate-600">
                    The requested story could not be found or is no longer available.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-stories.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Browse Alumni Stories
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>
