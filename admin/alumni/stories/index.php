<?php
/**
 * Admin Alumni Stories module - list.
 */
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

admin_require_login();

$pageTitle    = 'Alumni Stories';
$pageSubtitle = 'Manage the stories that highlight graduate experiences and career journeys.';
$activeNav    = 'alumni';

$ucsFlash = $_SESSION['alumni_story_flash'] ?? null;
unset($_SESSION['alumni_story_flash']);

// --- Filters -----------------------------------------------------------------
$ucsQuery  = trim((string) ($_GET['q'] ?? ''));
$ucsStatus = in_array((string) ($_GET['status'] ?? ''), ['draft', 'published', 'unpublished'], true) ? (string) $_GET['status'] : '';
$ucsShow   = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage   = max(1, (int) ($_GET['page'] ?? 1));

$ucsWhere  = [];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped   = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[]   = '(st.title LIKE :q OR st.summary LIKE :q OR s.name LIKE :q)';
    $ucsParams[':q'] = '%' . $ucsEscaped . '%';
}
if ($ucsStatus !== '') {
    $ucsWhere[]   = 'st.status = :status';
    $ucsParams[':status'] = $ucsStatus;
}

$ucsWhereSql = count($ucsWhere) > 0 ? ' WHERE ' . implode(' AND ', $ucsWhere) : '';

$ucsStories   = [];
$ucsTotal     = 0;
$ucsTotalPages = 1;
try {
    $ucsCountStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM alumni_stories st
         JOIN alumni_profiles ap ON ap.id = st.alumni_profile_id
         JOIN students s ON s.id = ap.student_id"
        . $ucsWhereSql
    );
    $ucsCountStmt->execute($ucsParams);
    $ucsTotal = (int) $ucsCountStmt->fetchColumn();

    $ucsTotalPages = max(1, (int) ceil($ucsTotal / $ucsShow));
    $ucsPage       = min($ucsPage, $ucsTotalPages);
    $ucsOffset     = ($ucsPage - 1) * $ucsShow;

    $ucsStmt = $pdo->prepare(
        "SELECT st.id, st.title, st.summary, st.content, st.career_field,
                st.cover_image, st.publication_date, st.status, st.created_at,
                s.name AS student_name, s.graduation_year,
                m.name AS major_name
         FROM alumni_stories st
         JOIN alumni_profiles ap ON ap.id = st.alumni_profile_id
         JOIN students s ON s.id = ap.student_id
         JOIN classrooms cl ON cl.id = s.classroom_id
         JOIN majors m ON m.id = cl.major_id"
        . $ucsWhereSql . "
         ORDER BY st.publication_date DESC, st.id DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($ucsParams as $ucsKey => $ucsVal) {
        $ucsStmt->bindValue($ucsKey, $ucsVal);
    }
    $ucsStmt->bindValue(':limit', $ucsShow, PDO::PARAM_INT);
    $ucsStmt->bindValue(':offset', $ucsOffset, PDO::PARAM_INT);
    $ucsStmt->execute();
    $ucsStories = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsStories   = [];
    $ucsTotal     = 0;
    $ucsTotalPages = 1;
}

$ucsHasFilters = $ucsQuery !== '' || $ucsStatus !== '';

require_once __DIR__ . '/../../../includes/admin-layout-top.php';
?>
<?php if ($ucsFlash !== null): ?>
    <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100 text-red-700' : 'bg-blue-50 ring-blue-100 text-blue-700'; ?> rounded-xl px-4 py-3 ring-1" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
        <p class="flex items-start gap-2 text-sm font-medium">
            <?php if ($ucsFlash['type'] === 'error'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <path d="m9 11 3 3L22 4"></path>
                </svg>
            <?php endif; ?>
            <?php echo htmlspecialchars($ucsFlash['message']); ?>
        </p>
    </div>
<?php endif; ?>

<!-- Search + Add -->
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/stories/index.php'); ?>" class="flex w-full max-w-xl items-stretch" role="search">
        <div class="relative flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search stories or alumni…" aria-label="Search stories or alumni"
                   class="h-full w-full rounded-l-xl border border-r-0 border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>
        <button type="submit"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-r-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            Search
        </button>
    </form>

    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/stories/create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14"></path>
            <path d="M12 5v14"></path>
        </svg>
        Add Story
    </a>
</div>

<!-- Filters -->
<div class="mb-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/stories/index.php'); ?>">
        <input type="hidden" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:max-w-xs">
                <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                <select id="status" name="status"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Statuses</option>
                    <?php foreach (['draft', 'published', 'unpublished'] as $ucsOptionStatus): ?>
                        <option value="<?php echo $ucsOptionStatus; ?>" <?php echo $ucsStatus === $ucsOptionStatus ? 'selected' : ''; ?>>
                            <?php echo ucfirst($ucsOptionStatus); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <label for="show" class="font-semibold text-gray-500">Show:</label>
                    <select id="show" name="show"
                            class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <?php foreach ([10, 25, 50] as $ucsShowOption): ?>
                            <option value="<?php echo $ucsShowOption; ?>" <?php echo $ucsShow === $ucsShowOption ? 'selected' : ''; ?>>
                                <?php echo $ucsShowOption; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span>entries</span>
                </div>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/stories/index.php'); ?>"
                   class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                    Reset Filters
                </a>
            </div>
        </div>
    </form>
</div>

<?php if (empty($ucsStories)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">
            <?php echo $ucsHasFilters ? 'No matching stories' : 'No alumni stories yet'; ?>
        </h3>
        <p class="mt-2 text-sm text-gray-500">
            <?php echo $ucsHasFilters ? 'Try adjusting or resetting your filters.' : 'Write the first story highlighting a graduate career journey.'; ?>
        </p>
        <?php if ($ucsHasFilters): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/stories/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Clear Filters
            </a>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/stories/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Add Story
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="mb-4 text-sm text-gray-600">
        Showing <span class="font-semibold text-gray-900"><?php echo $ucsTotal; ?></span>
        <?php echo $ucsTotal === 1 ? 'story' : 'stories'; ?>
        <?php if ($ucsTotalPages > 1): ?>
            &middot; Page <span class="font-semibold text-gray-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-gray-900"><?php echo $ucsTotalPages; ?></span>
        <?php endif; ?>
    </p>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Story</th>
                        <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Alumni</th>
                        <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Career Field</th>
                        <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                        <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($ucsStories as $ucsStory): ?>
                        <?php
                        $ucsStoryTitle = (string) $ucsStory['title'];
                        $ucsJsName     = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsStoryTitle);
                        $ucsStoryStatus = (string) $ucsStory['status'];
                        $ucsStoryDate   = !empty($ucsStory['publication_date']) ? (string) $ucsStory['publication_date'] : '';
                        $ucsPreview     = trim((string) strip_tags((string) $ucsStory['summary']));
                        if (strlen($ucsPreview) > 90) {
                            $ucsPreview = substr($ucsPreview, 0, 90) . '…';
                        }
                        $ucsStoryUrl = BASE_URL . '/alumni-story-details.php?id=' . (int) $ucsStory['id'];
                        ?>
                        <tr class="transition-colors duration-150 hover:bg-gray-50/60">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($ucsStoryTitle); ?></p>
                                <?php if ($ucsPreview !== ''): ?>
                                    <p class="mt-0.5 text-sm leading-relaxed text-gray-500"><?php echo htmlspecialchars($ucsPreview); ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars((string) $ucsStory['student_name']); ?></p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    <?php echo htmlspecialchars((string) ($ucsStory['major_name'] ?? '')); ?>
                                    <?php if (!empty($ucsStory['graduation_year'])): ?>
                                        &middot; Class of <?php echo htmlspecialchars((string) $ucsStory['graduation_year']); ?>
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                <?php echo !empty($ucsStory['career_field']) ? htmlspecialchars((string) $ucsStory['career_field']) : '—'; ?>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-gray-600">
                                <?php echo $ucsStoryDate !== '' ? htmlspecialchars(date('j M Y', strtotime($ucsStoryDate))) : '—'; ?>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <?php if ($ucsStoryStatus === 'published'): ?>
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">Published</span>
                                <?php elseif ($ucsStoryStatus === 'unpublished'): ?>
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">Unpublished</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center gap-1">
                                    <?php if ($ucsStoryStatus === 'published'): ?>
                                        <a href="<?php echo htmlspecialchars($ucsStoryUrl); ?>" target="_blank" rel="noopener" title="View <?php echo htmlspecialchars($ucsStoryTitle); ?>"
                                           class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:bg-blue-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            View
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/stories/edit.php?id=' . (int) $ucsStory['id']); ?>" title="Edit <?php echo htmlspecialchars($ucsStoryTitle); ?>"
                                       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-amber-600 transition-colors duration-150 hover:bg-amber-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                            <path d="m15 5 4 4"></path>
                                        </svg>
                                        Edit
                                    </a>
                                    <?php if ($ucsStoryStatus !== 'published'): ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-story-publish.php'); ?>" class="inline-flex"
                                              onsubmit="return confirm('Publish &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsStory['id']; ?>">
                                            <button type="submit" title="Publish <?php echo htmlspecialchars($ucsStoryTitle); ?>"
                                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-green-600 transition-colors duration-150 hover:bg-green-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M22 2 11 13"></path>
                                                    <path d="M22 2 15 22l-4-9-9-4Z"></path>
                                                </svg>
                                                Publish
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-story-unpublish.php'); ?>" class="inline-flex"
                                              onsubmit="return confirm('Unpublish &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsStory['id']; ?>">
                                            <button type="submit" title="Unpublish <?php echo htmlspecialchars($ucsStoryTitle); ?>"
                                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-slate-600 transition-colors duration-150 hover:bg-slate-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="m18 6-6 6-6-6"></path>
                                                    <path d="M12 12v10"></path>
                                                    <path d="M12 22c5 0 9-2.5 9-6 0-2.5-3-4-9-4s-9 1.5-9 4c0 3.5 4 6 9 6Z"></path>
                                                </svg>
                                                Unpublish
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-story-delete.php'); ?>" class="inline-flex"
                                          onsubmit="return confirm('Delete alumni story &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsStory['id']; ?>">
                                        <button type="submit" title="Delete <?php echo htmlspecialchars($ucsStoryTitle); ?>"
                                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-red-600 transition-colors duration-150 hover:bg-red-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18"></path>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($ucsTotalPages > 1): ?>
        <?php
        $ucsFilterParams = [];
        if ($ucsQuery !== '')  { $ucsFilterParams['q'] = $ucsQuery; }
        if ($ucsStatus !== '') { $ucsFilterParams['status'] = $ucsStatus; }
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

        $ucsPrevUrl = ROOT_URL . '/admin/alumni/stories/index.php';
        if ($ucsPage > 1) {
            $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
        }
        $ucsNextUrl = ROOT_URL . '/admin/alumni/stories/index.php';
        if ($ucsPage < $ucsTotalPages) {
            $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
        }
        $ucsPageLinkUrl = ROOT_URL . '/admin/alumni/stories/index.php?';
        ?>
        <nav class="mt-6 flex flex-wrap items-center justify-between gap-3" aria-label="Stories pagination">
            <a href="<?php echo htmlspecialchars($ucsPrevUrl); ?>"
               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?> focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
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
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                <?php echo $ucsLinkPage; ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <a href="<?php echo htmlspecialchars($ucsNextUrl); ?>"
               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage >= $ucsTotalPages ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?> focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </a>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/../../../includes/admin-layout-bottom.php'; ?>