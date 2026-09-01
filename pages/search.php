<?php
/**
 * Global Site Search page.
 *
 * Searches across news, alumni, and career discussions.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';

$pageTitle = 'Search Results';

$ucsSearchQuery = trim((string) ($_GET['q'] ?? ''));
$ucsResults     = ['news' => [], 'alumni' => [], 'discussions' => []];

if ($ucsSearchQuery !== '') {
    $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsSearchQuery);
    $ucsLike    = '%' . $ucsEscaped . '%';

    // Search news
    try {
        $ucsNewsStmt = $pdo->prepare(
            "SELECT n.id, n.title, n.slug, n.content, n.cover_image,
                    n.published_at, c.name AS category
             FROM news n
             LEFT JOIN categories c ON c.id = n.category_id
             WHERE n.status = 'Published'
               AND (n.title LIKE :q OR n.content LIKE :q)
             ORDER BY n.published_at DESC
             LIMIT 10"
        );
        $ucsNewsStmt->execute([':q' => $ucsLike]);
        $ucsResults['news'] = $ucsNewsStmt->fetchAll();
    } catch (PDOException $e) {
        $ucsResults['news'] = [];
    }

    // Search alumni
    try {
        $ucsAlumniStmt = $pdo->prepare(
            "SELECT ap.id, ap.current_job, ap.company, ap.bio, ap.profile_photo,
                    s.name AS student_name, s.graduation_year,
                    m.short_name AS major_short, m.name AS major_name
             FROM alumni_profiles ap
             JOIN students s ON s.id = ap.student_id
             JOIN classrooms cl ON cl.id = s.classroom_id
             JOIN majors m ON m.id = cl.major_id
             WHERE ap.verification_status = 'verified'
               AND ap.visibility = 'public'
               AND s.student_status = 'graduated'
               AND (s.name LIKE :q OR ap.current_job LIKE :q OR ap.company LIKE :q OR m.name LIKE :q OR m.short_name LIKE :q)
             ORDER BY s.name ASC
             LIMIT 10"
        );
        $ucsAlumniStmt->execute([':q' => $ucsLike]);
        $ucsResults['alumni'] = $ucsAlumniStmt->fetchAll();
    } catch (PDOException $e) {
        $ucsResults['alumni'] = [];
    }

    // Search discussions
    try {
        $ucsDiscStmt = $pdo->prepare(
            "SELECT d.id, d.title, d.content, d.created_at,
                    s.name AS author_name, dc.name AS category
             FROM discussions d
             JOIN students s ON s.id = d.author_student_id
             LEFT JOIN discussion_categories dc ON dc.id = d.category_id
             WHERE d.status = 'open'
               AND (d.title LIKE :q OR d.content LIKE :q)
             ORDER BY d.created_at DESC
             LIMIT 10"
        );
        $ucsDiscStmt->execute([':q' => $ucsLike]);
        $ucsResults['discussions'] = $ucsDiscStmt->fetchAll();
    } catch (PDOException $e) {
        $ucsResults['discussions'] = [];
    }
}

$ucsTotalResults = count($ucsResults['news']) + count($ucsResults['alumni']) + count($ucsResults['discussions']);

require_once '../includes/header.php';
?>
<main class="flex-1">
    <section class="border-b border-slate-200 bg-white" aria-labelledby="search-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
            <h1 id="search-heading" class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Search Results</h1>

            <form method="get" action="<?php echo htmlspecialchars(BASE_URL . '/search.php'); ?>" class="mt-6">
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="search" name="q" value="<?php echo htmlspecialchars($ucsSearchQuery); ?>" placeholder="Search the website…" aria-label="Search"
                               class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Search
                    </button>
                </div>
            </form>

            <?php if ($ucsSearchQuery !== ''): ?>
                <p class="mt-4 text-sm text-slate-500">
                    Found <span class="font-semibold text-slate-700"><?php echo $ucsTotalResults; ?></span> result<?php echo $ucsTotalResults === 1 ? '' : 's'; ?> for "<span class="font-semibold text-slate-700"><?php echo htmlspecialchars($ucsSearchQuery); ?></span>"
                </p>
            <?php endif; ?>
        </div>
    </section>

    <section class="bg-slate-50 py-10 sm:py-14" aria-labelledby="search-results-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="search-results-heading" class="sr-only">Search Results</h2>

            <?php if ($ucsSearchQuery === ''): ?>
                <div class="text-center py-16">
                    <p class="text-slate-500">Enter a search term to find content across the site.</p>
                </div>
            <?php elseif ($ucsTotalResults === 0): ?>
                <div class="text-center py-16 border border-slate-200 bg-white">
                    <h3 class="text-lg font-bold text-slate-900">No results found</h3>
                    <p class="mt-2 text-sm text-slate-500">Try different keywords or check your spelling.</p>
                </div>
            <?php else: ?>

                <!-- News Results -->
                <?php if (!empty($ucsResults['news'])): ?>
                    <div class="mb-10">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-4">News &amp; Updates (<?php echo count($ucsResults['news']); ?>)</h3>
                        <div class="border border-slate-200 bg-white divide-y divide-slate-200">
                            <?php foreach ($ucsResults['news'] as $ucsNews): ?>
                                <article class="px-6 py-4 hover:bg-slate-50 transition-colors">
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/news-details.php?id=' . (int) $ucsNews['id']); ?>" class="block">
                                        <div class="flex items-start gap-4">
                                            <?php if (!empty($ucsNews['cover_image'])): ?>
                                                <?php
                                                $ucsCoverFile = dirname(__DIR__) . '/assets/' . ltrim($ucsNews['cover_image'], '/');
                                                if (is_file($ucsCoverFile)):
                                                ?>
                                                    <img src="<?php echo htmlspecialchars(ROOT_URL . '/assets/' . ltrim($ucsNews['cover_image'], '/')); ?>" alt="" class="h-14 w-14 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-sm font-semibold text-slate-900 hover:text-blue-700 transition-colors truncate"><?php echo htmlspecialchars($ucsNews['title']); ?></h4>
                                                <?php if (!empty($ucsNews['category'])): ?>
                                                    <span class="mt-1 inline-block rounded bg-blue-50 px-2 py-0.5 text-[0.65rem] font-medium text-blue-700"><?php echo htmlspecialchars($ucsNews['category']); ?></span>
                                                <?php endif; ?>
                                                <p class="mt-1 text-xs text-slate-500 line-clamp-1"><?php echo htmlspecialchars(ucs_short_summary((string) $ucsNews['content'], 120)); ?></p>
                                            </div>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Alumni Results -->
                <?php if (!empty($ucsResults['alumni'])): ?>
                    <div class="mb-10">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-4">Alumni (<?php echo count($ucsResults['alumni']); ?>)</h3>
                        <div class="border border-slate-200 bg-white divide-y divide-slate-200">
                            <?php foreach ($ucsResults['alumni'] as $ucsAlum): ?>
                                <article class="px-6 py-4 hover:bg-slate-50 transition-colors">
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsAlum['id']); ?>" class="block">
                                        <div class="flex items-center gap-4">
                                            <?php
                                            $ucsHasPhoto = false;
                                            if (!empty($ucsAlum['profile_photo'])) {
                                                $ucsPhotoFile = dirname(__DIR__) . '/assets/' . ltrim($ucsAlum['profile_photo'], '/');
                                                $ucsHasPhoto = is_file($ucsPhotoFile);
                                            }
                                            ?>
                                            <?php if ($ucsHasPhoto): ?>
                                                <img src="<?php echo htmlspecialchars(ROOT_URL . '/assets/' . ltrim($ucsAlum['profile_photo'], '/')); ?>" alt="" class="h-10 w-10 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                                            <?php else: ?>
                                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-xs font-bold text-blue-700 ring-1 ring-blue-100"><?php echo strtoupper(substr($ucsAlum['student_name'] ?? 'A', 0, 1)); ?></span>
                                            <?php endif; ?>
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-sm font-semibold text-slate-900 hover:text-blue-700 transition-colors"><?php echo htmlspecialchars($ucsAlum['student_name'] ?? ''); ?></h4>
                                                <p class="text-xs text-slate-500"><?php echo htmlspecialchars(((string) ($ucsAlum['major_short'] ?? '') !== '' ? $ucsAlum['major_short'] . ' - ' : '') . ($ucsAlum['major_name'] ?? '')); ?><?php if (!empty($ucsAlum['graduation_year'])): ?> &middot; Class of <?php echo (int) $ucsAlum['graduation_year']; ?><?php endif; ?></p>
                                                <?php if (!empty($ucsAlum['current_job']) || !empty($ucsAlum['company'])): ?>
                                                    <p class="mt-0.5 text-xs text-slate-400"><?php echo htmlspecialchars(($ucsAlum['current_job'] ?? '') . (!empty($ucsAlum['current_job']) && !empty($ucsAlum['company']) ? ' at ' : '') . ($ucsAlum['company'] ?? '')); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Discussion Results -->
                <?php if (!empty($ucsResults['discussions'])): ?>
                    <div class="mb-10">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-4">Career Discussions (<?php echo count($ucsResults['discussions']); ?>)</h3>
                        <div class="border border-slate-200 bg-white divide-y divide-slate-200">
                            <?php foreach ($ucsResults['discussions'] as $ucsDisc): ?>
                                <article class="px-6 py-4 hover:bg-slate-50 transition-colors">
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussion-details.php?id=' . (int) $ucsDisc['id']); ?>" class="block">
                                        <h4 class="text-sm font-semibold text-slate-900 hover:text-blue-700 transition-colors"><?php echo htmlspecialchars($ucsDisc['title']); ?></h4>
                                        <p class="mt-1 text-xs text-slate-500 line-clamp-1"><?php echo htmlspecialchars(ucs_short_summary((string) $ucsDisc['content'], 120)); ?></p>
                                        <div class="mt-1.5 flex items-center gap-2 text-xs text-slate-400">
                                            <span><?php echo htmlspecialchars($ucsDisc['author_name'] ?? ''); ?></span>
                                            <?php if (!empty($ucsDisc['category'])): ?>
                                                <span>&middot;</span>
                                                <span class="text-blue-600"><?php echo htmlspecialchars($ucsDisc['category']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>
