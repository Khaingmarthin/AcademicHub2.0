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
    <!-- Hero Section -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="alumni-stories-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
            <div class="max-w-3xl">
                <nav class="mb-4 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Alumni Stories</li>
                    </ol>
                </nav>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-200/50">
                        <?php echo count($ucsStories); ?> <?php echo count($ucsStories) === 1 ? 'story' : 'stories'; ?>
                    </span>
                </div>
                <h1 id="alumni-stories-page-heading" class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl leading-tight">
                    Alumni Stories
                </h1>
                <p class="mt-3 max-w-2xl text-base leading-relaxed text-slate-600">
                    Real experiences, achievements and career journeys from UCSMTLA graduates who are shaping the future of technology and innovation.
                </p>
            </div>
        </div>
    </section>

    <!-- Stories Grid -->
    <section class="bg-slate-50 py-10 sm:py-14" aria-labelledby="alumni-stories-listing-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <?php if (count($ucsStories) > 0): ?>
                <!-- Featured Story (First) -->
                <?php
                $ucsFirstStory = $ucsStories[0];
                $ucsFirstUrl = BASE_URL . '/alumni-story-details.php?id=' . (int) $ucsFirstStory['id'];
                $ucsFirstHasCover = false;
                $ucsFirstCoverUrl = '';
                if (!empty($ucsFirstStory['cover_image'])) {
                    $ucsFirstFile = dirname(__DIR__) . '/assets/' . ltrim($ucsFirstStory['cover_image'], '/');
                    $ucsFirstHasCover = is_file($ucsFirstFile);
                    if ($ucsFirstHasCover) {
                        $ucsFirstCoverUrl = ROOT_URL . '/assets/' . ltrim($ucsFirstStory['cover_image'], '/');
                    }
                }
                $ucsFirstSummary = ucs_short_summary((string) ($ucsFirstStory['summary'] ?? ''), 200);
                $ucsFirstDate = !empty($ucsFirstStory['publication_date'])
                    ? (string) $ucsFirstStory['publication_date']
                    : (string) ($ucsFirstStory['created_at'] ?? '');
                ?>
                <a href="<?php echo htmlspecialchars($ucsFirstUrl); ?>" class="group block">
                    <article class="relative overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                        <div class="grid grid-cols-1 sm:grid-cols-[14rem_1fr] lg:grid-cols-[16rem_1fr]">
                            <?php if ($ucsFirstHasCover): ?>
                                <div class="relative h-48 overflow-hidden sm:h-auto">
                                    <img src="<?php echo htmlspecialchars($ucsFirstCoverUrl); ?>" alt="<?php echo htmlspecialchars((string) $ucsFirstStory['title']); ?>" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                </div>
                            <?php endif; ?>
                            <div class="flex flex-col justify-center p-6 sm:p-8">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                        <?php echo !empty($ucsFirstStory['career_field']) ? htmlspecialchars((string) $ucsFirstStory['career_field']) : 'Alumni Story'; ?>
                                    </span>
                                    <?php if ($ucsFirstDate !== ''): ?>
                                        <span class="text-xs text-slate-400"><?php echo htmlspecialchars(date('M j, Y', strtotime($ucsFirstDate))); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h2 class="mt-3 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl leading-snug group-hover:text-blue-700 transition-colors">
                                    <?php echo htmlspecialchars((string) $ucsFirstStory['title']); ?>
                                </h2>
                                <p class="mt-1.5 text-sm font-medium text-slate-500">
                                    <?php echo htmlspecialchars((string) $ucsFirstStory['student_name']); ?>
                                    <?php if (!empty($ucsFirstStory['major_name']) || !empty($ucsFirstStory['graduation_year'])): ?>
                                        <span class="text-slate-400">&middot;</span>
                                        <?php echo htmlspecialchars((string) $ucsFirstStory['major_name']); ?>
                                        <?php if (!empty($ucsFirstStory['graduation_year'])): ?>
                                            <span class="text-slate-400">Class of</span> <?php echo htmlspecialchars((string) $ucsFirstStory['graduation_year']); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </p>
                                <?php if ($ucsFirstSummary !== ''): ?>
                                    <p class="mt-3 text-sm leading-relaxed text-slate-500 line-clamp-2"><?php echo htmlspecialchars($ucsFirstSummary); ?></p>
                                <?php endif; ?>
                                <div class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors group-hover:text-blue-700">
                                    Read Full Story
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                                </div>
                            </div>
                        </div>
                    </article>
                </a>

                <?php if (count($ucsStories) > 1): ?>
                    <!-- Remaining Stories Grid -->
                    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach (array_slice($ucsStories, 1) as $ucsStory): ?>
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
                            $ucsStorySummary = ucs_short_summary((string) ($ucsStory['summary'] ?? ''), 100);
                            $ucsStoryDate = !empty($ucsStory['publication_date'])
                                ? (string) $ucsStory['publication_date']
                                : (string) ($ucsStory['created_at'] ?? '');
                            ?>
                            <a href="<?php echo htmlspecialchars($ucsStoryUrl); ?>" class="group block">
                                <article class="relative flex flex-col h-full overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
                                    <?php if ($ucsHasCover): ?>
                                        <div class="relative h-40 overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="<?php echo htmlspecialchars((string) $ucsStory['title']); ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                            <?php if (!empty($ucsStory['career_field'])): ?>
                                                <span class="absolute top-3 left-3 z-10 inline-flex items-center rounded-full bg-white/90 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700 backdrop-blur-sm">
                                                    <?php echo htmlspecialchars((string) $ucsStory['career_field']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="relative h-40 bg-gradient-to-br from-blue-500 to-indigo-600 flex flex-col items-center justify-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                            <?php if (!empty($ucsStory['career_field'])): ?>
                                                <span class="inline-flex items-center rounded-full bg-white/90 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700 backdrop-blur-sm">
                                                    <?php echo htmlspecialchars((string) $ucsStory['career_field']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex flex-col flex-1 p-5">
                                        <div class="flex items-center gap-2 text-[11px] font-medium text-slate-400">
                                            <?php if ($ucsStoryDate !== ''): ?>
                                                <time datetime="<?php echo htmlspecialchars($ucsStoryDate); ?>"><?php echo htmlspecialchars(date('M j, Y', strtotime($ucsStoryDate))); ?></time>
                                            <?php endif; ?>
                                        </div>

                                        <h3 class="mt-2 text-base font-bold tracking-tight text-slate-900 leading-snug group-hover:text-blue-700 transition-colors line-clamp-2">
                                            <?php echo htmlspecialchars((string) $ucsStory['title']); ?>
                                        </h3>

                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 text-[9px] font-bold text-white ring-1 ring-white">
                                                <?php echo mb_strtoupper(mb_substr((string) $ucsStory['student_name'], 0, 1)); ?>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-slate-700 truncate"><?php echo htmlspecialchars((string) $ucsStory['student_name']); ?></p>
                                                <p class="text-[11px] text-slate-400 truncate">
                                                    <?php echo htmlspecialchars((string) $ucsStory['major_name']); ?>
                                                    <?php if (!empty($ucsStory['graduation_year'])): ?>
                                                        &middot; Class of <?php echo htmlspecialchars((string) $ucsStory['graduation_year']); ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>

                                        <?php if ($ucsStorySummary !== ''): ?>
                                            <p class="mt-2.5 text-xs leading-relaxed text-slate-500 line-clamp-2 flex-1"><?php echo htmlspecialchars($ucsStorySummary); ?></p>
                                        <?php endif; ?>

                                        <div class="mt-3 pt-3 border-t border-slate-100">
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 transition-colors group-hover:text-blue-700">
                                                Read Story
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="mx-auto max-w-xl text-center py-12">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-slate-900">No stories published yet</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                        We are collecting inspiring stories from the UCSMTLA alumni community. Check back soon.
                    </p>
                </div>
            <?php endif; ?>

            <!-- CTA Section -->
            <div class="mt-12 rounded-2xl border border-slate-200 bg-white p-8 sm:p-10 text-center">
                <h3 class="text-xl font-bold tracking-tight text-slate-900">Meet the graduates behind the stories</h3>
                <p class="mx-auto mt-2 max-w-lg text-sm leading-relaxed text-slate-500">
                    Browse the public alumni directory to discover verified profiles and the inspiring journeys of UCSMTLA graduates.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-blue-700">
                    View Alumni Directory
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                </a>
            </div>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>
