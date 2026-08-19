<?php
/**
 * Public Career Discussions listing page.
 *
 * A moderated, forum-style career community. Current students ask career
 * questions and verified alumni share professional experience. Only
 * discussions with status 'open' or 'closed' are listed (hidden discussions
 * are removed by moderators). Pinned discussions appear first. This is not
 * a chat system.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';
require_once __DIR__ . '/../includes/helpers/discussion-validation.php';

$pageTitle = 'Career Discussions';

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

$ucsCategories = discussion_load_categories($pdo, true);

// --- Filters ---------------------------------------------------------------
$ucsQuery      = trim((string) ($_GET['q'] ?? ''));
$ucsCategory   = (string) ($_GET['category'] ?? '');
$ucsShow       = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage       = max(1, (int) ($_GET['page'] ?? 1));

$ucsWhere  = ["d.status <> 'hidden'"];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[] = '(d.title LIKE :q OR d.content LIKE :q)';
    $ucsParams[':q'] = '%' . $ucsEscaped . '%';
}
if ($ucsCategory !== '') {
    $ucsWhere[]   = 'c.slug = :category';
    $ucsParams[':category'] = $ucsCategory;
}

$ucsWhereSql = ' WHERE ' . implode(' AND ', $ucsWhere);

$ucsDiscussions  = [];
$ucsTotal        = 0;
$ucsTotalPages   = 1;
try {
    $ucsCountStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM discussions d
         JOIN discussion_categories c ON c.id = d.category_id"
        . $ucsWhereSql
    );
    $ucsCountStmt->execute($ucsParams);
    $ucsTotal = (int) $ucsCountStmt->fetchColumn();

    $ucsTotalPages = max(1, (int) ceil($ucsTotal / $ucsShow));
    $ucsPage       = min($ucsPage, $ucsTotalPages);
    $ucsOffset     = ($ucsPage - 1) * $ucsShow;

    $ucsStmt = $pdo->prepare(
        "SELECT d.id, d.title, d.content, d.status, d.is_pinned, d.created_at,
                c.name AS category_name, c.slug AS category_slug,
                s.name AS author_name,
                (ap.id IS NOT NULL) AS is_alumni,
                (SELECT COUNT(*) FROM discussion_replies r
                  WHERE r.discussion_id = d.id AND r.status = 'visible') AS reply_count
         FROM discussions d
         JOIN discussion_categories c ON c.id = d.category_id
         JOIN students s ON s.id = d.author_student_id
         LEFT JOIN alumni_profiles ap
                ON ap.student_id = s.id AND ap.verification_status = 'verified'"
        . $ucsWhereSql
        . " ORDER BY d.is_pinned DESC, d.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($ucsParams as $ucsKey => $ucsVal) {
        $ucsStmt->bindValue($ucsKey, $ucsVal);
    }
    $ucsStmt->bindValue(':limit', $ucsShow, PDO::PARAM_INT);
    $ucsStmt->bindValue(':offset', $ucsOffset, PDO::PARAM_INT);
    $ucsStmt->execute();
    $ucsDiscussions = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsDiscussions = [];
    $ucsTotal       = 0;
    $ucsTotalPages  = 1;
}

$ucsFilterActive = $ucsQuery !== '' || $ucsCategory !== '';

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="career-discussions-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">UCSMTLA Career Community</p>
                <h1 id="career-discussions-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Career Discussions</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Ask career questions and get answers from verified UCSMTLA alumni who have been where you are now.
                </p>
            </div>
        </div>
    </section>

    <!-- Discussions listing -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="career-discussions-listing-heading">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <!-- Intro + ask CTA -->
            <div class="rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 px-6 py-8 sm:px-10">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 id="career-discussions-listing-heading" class="text-xl font-extrabold tracking-tight text-white sm:text-2xl">Have a career question?</h2>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-blue-100">
                            Whether it is your first software interview, building a portfolio or writing a CV — ask the community and learn from graduates working in the field.
                        </p>
                    </div>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussion-create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-blue-700 shadow-lg transition-all duration-200 hover:bg-blue-50 hover:shadow-xl focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                        </svg>
                        Ask a Career Question
                    </a>
                </div>
            </div>

            <!-- Search -->
            <form method="get" action="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="mt-8 flex w-full items-stretch" role="search">
                <div class="relative flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search career discussions…" aria-label="Search career discussions"
                           class="h-full w-full rounded-l-xl border border-r-0 border-gray-300 bg-white py-3 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
                <button type="submit"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-r-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Search
                </button>
            </form>

            <!-- Category filters -->
            <div class="mt-5 flex flex-wrap items-center gap-2">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>"
                   class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150 <?php echo $ucsCategory === '' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'; ?>">
                    All Topics
                </a>
                <?php foreach ($ucsCategories as $ucsCat): ?>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php?category=' . urlencode((string) $ucsCat['slug'])); ?>"
                       class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150 <?php echo $ucsCategory === (string) $ucsCat['slug'] ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'; ?>">
                        <?php echo htmlspecialchars((string) $ucsCat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Listing -->
            <?php if (empty($ucsDiscussions)): ?>
                <div class="mt-8 rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-gray-200">
                    <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold text-gray-900">
                        <?php echo $ucsFilterActive ? 'No matching discussions' : 'No discussions yet'; ?>
                    </h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">
                        <?php echo $ucsFilterActive
                            ? 'Try adjusting your search or choose a different topic.'
                            : 'Be the first to ask a career question and start the conversation with our alumni.'; ?>
                    </p>
                    <?php if ($ucsFilterActive): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="mt-8 text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900"><?php echo $ucsTotal; ?></span>
                    <?php echo $ucsTotal === 1 ? 'discussion' : 'discussions'; ?>
                    <?php if ($ucsTotalPages > 1): ?>
                        &middot; Page <span class="font-semibold text-gray-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-gray-900"><?php echo $ucsTotalPages; ?></span>
                    <?php endif; ?>
                </p>

                <ul class="mt-6 space-y-4">
                    <?php foreach ($ucsDiscussions as $ucsDiscussion): ?>
                        <?php
                        $ucsDiscussionUrl = BASE_URL . '/career-discussion-details.php?id=' . (int) $ucsDiscussion['id'];
                        $ucsPreview = ucs_short_summary((string) $ucsDiscussion['content'], 160);
                        ?>
                        <li class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:shadow-md sm:p-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                    <?php echo htmlspecialchars((string) $ucsDiscussion['category_name']); ?>
                                </span>
                                <?php if ((int) $ucsDiscussion['is_pinned'] === 1): ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 17v5M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1z"></path>
                                        </svg>
                                        Pinned
                                    </span>
                                <?php endif; ?>
                                <?php if ((string) $ucsDiscussion['status'] === 'closed'): ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 ring-1 ring-gray-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                        Closed
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="mt-3 text-lg font-bold leading-snug tracking-tight text-gray-900">
                                <a href="<?php echo htmlspecialchars($ucsDiscussionUrl); ?>" class="transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <?php echo htmlspecialchars((string) $ucsDiscussion['title']); ?>
                                </a>
                            </h3>

                            <?php if ($ucsPreview !== ''): ?>
                                <p class="mt-2 text-sm leading-6 text-gray-600"><?php echo htmlspecialchars($ucsPreview); ?></p>
                            <?php endif; ?>

                            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-gray-100 pt-4 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white" aria-hidden="true">
                                        <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsDiscussion['author_name']), 0, 1))); ?>
                                    </span>
                                    <?php echo htmlspecialchars((string) $ucsDiscussion['author_name']); ?>
                                    <?php if ((int) $ucsDiscussion['is_alumni'] === 1): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">Alumni</span>
                                    <?php endif; ?>
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M8 2v4M16 2v4M3 10h18"></path>
                                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    </svg>
                                    <?php echo htmlspecialchars(date('M j, Y', strtotime((string) $ucsDiscussion['created_at']))); ?>
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                    <?php echo (int) $ucsDiscussion['reply_count']; ?>
                                    <?php echo (int) $ucsDiscussion['reply_count'] === 1 ? 'reply' : 'replies'; ?>
                                </span>
                                <a href="<?php echo htmlspecialchars($ucsDiscussionUrl); ?>" class="ml-auto inline-flex items-center gap-1 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700">
                                    View Discussion
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($ucsTotalPages > 1): ?>
                    <?php
                    $ucsFilterParams = [];
                    if ($ucsQuery !== '')      { $ucsFilterParams['q'] = $ucsQuery; }
                    if ($ucsCategory !== '')   { $ucsFilterParams['category'] = $ucsCategory; }
                    $ucsFilterParams['show'] = $ucsShow;

                    $ucsPageLinks = [];
                    for ($ucsLinkPage = 1; $ucsLinkPage <= $ucsTotalPages; $ucsLinkPage++) {
                        if ($ucsTotalPages > 7 && $ucsLinkPage > 1 && $ucsLinkPage < $ucsTotalPages && abs($ucsLinkPage - $ucsPage) > 2) {
                            if (!in_array('ellipsis', $ucsPageLinks, true) && end($ucsPageLinks) !== 'ellipsis') {
                                $ucsPageLinks[] = 'ellipsis';
                            }
                            continue;
                        }
                        $ucsPageLinks[] = $ucsLinkPage;
                    }

                    $ucsPrevUrl = BASE_URL . '/career-discussions.php';
                    if ($ucsPage > 1) {
                        $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
                    }
                    $ucsNextUrl = BASE_URL . '/career-discussions.php';
                    if ($ucsPage < $ucsTotalPages) {
                        $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
                    }
                    $ucsPageLinkUrl = BASE_URL . '/career-discussions.php?';
                    ?>
                    <nav class="mt-8 flex flex-wrap items-center justify-between gap-3" aria-label="Discussions pagination">
                        <a href="<?php echo htmlspecialchars($ucsPrevUrl); ?>"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?> focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Previous
                        </a>
                        <div class="flex flex-wrap items-center gap-1">
                            <?php foreach ($ucsPageLinks as $ucsLinkPage): ?>
                                <?php if ($ucsLinkPage === 'ellipsis'): ?>
                                    <span class="px-1.5 text-sm text-gray-400">…</span>
                                <?php else: ?>
                                    <?php if ($ucsLinkPage === $ucsPage): ?>
                                        <span aria-current="page" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold text-white"><?php echo $ucsLinkPage; ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($ucsPageLinkUrl . http_build_query(array_merge(['page' => $ucsLinkPage], $ucsFilterParams))); ?>"
                                           class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50"><?php echo $ucsLinkPage; ?></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo htmlspecialchars($ucsNextUrl); ?>"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage >= $ucsTotalPages ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?> focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Next
                        </a>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php
require_once '../includes/footer.php';
?>