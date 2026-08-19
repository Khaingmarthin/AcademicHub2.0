<?php
/**
 * Public Career Opportunities listing page.
 *
 * A moderated job board for the Alumni & Career Community. Verified alumni
 * share career opportunities (jobs, internships, freelance work); students
 * and other alumni browse them. Only active, unexpired postings are listed
 * (hidden and expired postings are removed by admins or deadlines).
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';
require_once __DIR__ . '/../includes/helpers/opportunity-validation.php';

$pageTitle = 'Career Opportunities';

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

// --- Filters ---------------------------------------------------------------
$ucsQuery         = trim((string) ($_GET['q'] ?? ''));
$ucsEmployment    = in_array((string) ($_GET['type'] ?? ''), OPPORTUNITY_EMPLOYMENT_TYPES, true) ? (string) $_GET['type'] : '';
$ucsShow          = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage          = max(1, (int) ($_GET['page'] ?? 1));

$ucsListing = opportunity_load_public($pdo, $ucsQuery, $ucsEmployment, $ucsShow, $ucsPage);
$ucsOpportunities = $ucsListing['items'];
$ucsTotal         = $ucsListing['total'];
$ucsTotalPages    = $ucsListing['total_pages'];

$ucsFilterActive = $ucsQuery !== '' || $ucsEmployment !== '';

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="career-opportunities-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">UCSMTLA Career Community</p>
                <h1 id="career-opportunities-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Career Opportunities</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Jobs, internships and freelance work shared by verified UCSMTLA alumni who are hiring and know who is hiring.
                </p>
            </div>
        </div>
    </section>

    <!-- Opportunities listing -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="career-opportunities-listing-heading">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <!-- Search -->
            <form method="get" action="<?php echo htmlspecialchars(BASE_URL . '/career-opportunities.php'); ?>" class="flex w-full items-stretch" role="search">
                <div class="relative flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search by title, company or location…" aria-label="Search career opportunities"
                           class="h-full w-full rounded-l-xl border border-r-0 border-gray-300 bg-white py-3 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
                <button type="submit"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-r-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Search
                </button>
            </form>

            <!-- Employment type filters -->
            <div class="mt-5 flex flex-wrap items-center gap-2">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/career-opportunities.php'); ?>"
                   class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150 <?php echo $ucsEmployment === '' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'; ?>">
                    All Types
                </a>
                <?php foreach (OPPORTUNITY_EMPLOYMENT_TYPES as $ucsType): ?>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-opportunities.php?type=' . urlencode($ucsType)); ?>"
                       class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150 <?php echo $ucsEmployment === $ucsType ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'; ?>">
                        <?php echo htmlspecialchars(opportunity_employment_label($ucsType)); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Listing -->
            <?php if (empty($ucsOpportunities)): ?>
                <div class="mt-8 rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-gray-200">
                    <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold text-gray-900">
                        <?php echo $ucsFilterActive ? 'No matching opportunities' : 'No opportunities yet'; ?>
                    </h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">
                        <?php echo $ucsFilterActive
                            ? 'Try adjusting your search or choose a different employment type.'
                            : 'Be the first alumni to share a career opportunity with the community.'; ?>
                    </p>
                    <?php if ($ucsFilterActive): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/career-opportunities.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="mt-8 text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900"><?php echo $ucsTotal; ?></span>
                    <?php echo $ucsTotal === 1 ? 'opportunity' : 'opportunities'; ?>
                    <?php if ($ucsTotalPages > 1): ?>
                        &middot; Page <span class="font-semibold text-gray-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-gray-900"><?php echo $ucsTotalPages; ?></span>
                    <?php endif; ?>
                </p>

                <ul class="mt-6 space-y-4">
                    <?php foreach ($ucsOpportunities as $ucsOpportunity): ?>
                        <?php
                        $ucsOpportunityUrl = BASE_URL . '/career-opportunity-details.php?id=' . (int) $ucsOpportunity['id'];
                        $ucsPreview = ucs_short_summary((string) $ucsOpportunity['description'], 160);
                        ?>
                        <li class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:shadow-md sm:p-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                    <?php echo htmlspecialchars(opportunity_employment_label((string) $ucsOpportunity['employment_type'])); ?>
                                </span>
                                <?php if (!empty($ucsOpportunity['location'])): ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 ring-1 ring-gray-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <?php echo htmlspecialchars((string) $ucsOpportunity['location']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="mt-3 text-lg font-bold leading-snug tracking-tight text-gray-900">
                                <a href="<?php echo htmlspecialchars($ucsOpportunityUrl); ?>" class="transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <?php echo htmlspecialchars((string) $ucsOpportunity['title']); ?>
                                </a>
                            </h3>
                            <p class="mt-0.5 text-sm font-semibold text-slate-600"><?php echo htmlspecialchars((string) $ucsOpportunity['company']); ?></p>

                            <?php if ($ucsPreview !== ''): ?>
                                <p class="mt-2 text-sm leading-6 text-gray-600"><?php echo htmlspecialchars($ucsPreview); ?></p>
                            <?php endif; ?>

                            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-gray-100 pt-4 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white" aria-hidden="true">
                                        <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsOpportunity['poster_name']), 0, 1))); ?>
                                    </span>
                                    <?php echo htmlspecialchars((string) $ucsOpportunity['poster_name']); ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">Alumni</span>
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M8 2v4M16 2v4M3 10h18"></path>
                                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    </svg>
                                    Posted <?php echo htmlspecialchars(date('M j, Y', strtotime((string) $ucsOpportunity['created_at']))); ?>
                                </span>
                                <a href="<?php echo htmlspecialchars($ucsOpportunityUrl); ?>" class="ml-auto inline-flex items-center gap-1 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700">
                                    View Opportunity
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
                    if ($ucsEmployment !== '') { $ucsFilterParams['type'] = $ucsEmployment; }
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

                    $ucsPrevUrl = BASE_URL . '/career-opportunities.php';
                    if ($ucsPage > 1) {
                        $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
                    }
                    $ucsNextUrl = BASE_URL . '/career-opportunities.php';
                    if ($ucsPage < $ucsTotalPages) {
                        $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
                    }
                    $ucsPageLinkUrl = BASE_URL . '/career-opportunities.php?';
                    ?>
                    <nav class="mt-8 flex flex-wrap items-center justify-between gap-3" aria-label="Opportunities pagination">
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