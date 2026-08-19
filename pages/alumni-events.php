<?php
/**
 * Public Alumni Events listing page.
 *
 * University-organised events for the Alumni & Career Community (webinars,
 * workshops, networking nights, career fairs). Events are managed by admins;
 * this page lists published, not-yet-completed events, soonest first.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';
require_once __DIR__ . '/../includes/helpers/alumni-event-validation.php';

$pageTitle = 'Alumni Events';

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
$ucsQuery      = trim((string) ($_GET['q'] ?? ''));
$ucsEventType  = in_array((string) ($_GET['type'] ?? ''), ALUMNI_EVENT_TYPES, true) ? (string) $_GET['type'] : '';
$ucsShow       = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage       = max(1, (int) ($_GET['page'] ?? 1));

$ucsListing = alumni_event_load_public($pdo, $ucsQuery, $ucsEventType, $ucsShow, $ucsPage);
$ucsEvents  = $ucsListing['items'];
$ucsTotal   = $ucsListing['total'];
$ucsTotalPages = $ucsListing['total_pages'];

$ucsFilterActive = $ucsQuery !== '' || $ucsEventType !== '';

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="alumni-events-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">UCSMTLA Career Community</p>
                <h1 id="alumni-events-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Alumni Events</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Webinars, workshops, networking nights and career fairs organised by the university for alumni and students.
                </p>
            </div>
        </div>
    </section>

    <!-- Events listing -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="alumni-events-listing-heading">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <!-- Search -->
            <form method="get" action="<?php echo htmlspecialchars(BASE_URL . '/alumni-events.php'); ?>" class="flex w-full items-stretch" role="search">
                <div class="relative flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search events by title or venue…" aria-label="Search alumni events"
                           class="h-full w-full rounded-l-xl border border-r-0 border-gray-300 bg-white py-3 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
                <button type="submit"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-r-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Search
                </button>
            </form>

            <!-- Event type filters -->
            <div class="mt-5 flex flex-wrap items-center gap-2">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-events.php'); ?>"
                   class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150 <?php echo $ucsEventType === '' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'; ?>">
                    All Events
                </a>
                <?php foreach (ALUMNI_EVENT_TYPES as $ucsType): ?>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-events.php?type=' . urlencode($ucsType)); ?>"
                       class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150 <?php echo $ucsEventType === $ucsType ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'; ?>">
                        <?php echo htmlspecialchars(alumni_event_label($ucsType)); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Listing -->
            <?php if (empty($ucsEvents)): ?>
                <div class="mt-8 rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-gray-200">
                    <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold text-gray-900">
                        <?php echo $ucsFilterActive ? 'No matching events' : 'No upcoming events yet'; ?>
                    </h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">
                        <?php echo $ucsFilterActive
                            ? 'Try adjusting your search or choose a different event type.'
                            : 'Check back soon for the next alumni and career event.'; ?>
                    </p>
                    <?php if ($ucsFilterActive): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-events.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="mt-8 text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900"><?php echo $ucsTotal; ?></span>
                    <?php echo $ucsTotal === 1 ? 'event' : 'events'; ?>
                    <?php if ($ucsTotalPages > 1): ?>
                        &middot; Page <span class="font-semibold text-gray-900"><?php echo $ucsPage; ?></span> of <span class="font-semibold text-gray-900"><?php echo $ucsTotalPages; ?></span>
                    <?php endif; ?>
                </p>

                <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <?php foreach ($ucsEvents as $ucsEvent): ?>
                        <?php
                        $ucsState   = alumni_event_display_state($ucsEvent);
                        $ucsStart   = date('F j, Y', strtotime((string) $ucsEvent['starts_at']));
                        $ucsTime    = date('g:i A', strtotime((string) $ucsEvent['starts_at']));
                        $ucsEndsAt  = !empty($ucsEvent['ends_at']) ? date('g:i A', strtotime((string) $ucsEvent['ends_at'])) : '';
                        $ucsRegLink = trim((string) ($ucsEvent['registration_link'] ?? ''));
                        ?>
                        <article class="flex flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:shadow-md sm:p-6">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                    <?php echo htmlspecialchars(alumni_event_label((string) $ucsEvent['event_type'])); ?>
                                </span>
                                <?php if ($ucsState === 'ongoing'): ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                        Ongoing
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">Upcoming</span>
                                <?php endif; ?>
                            </div>

                            <h3 class="mt-3 text-lg font-bold leading-snug tracking-tight text-gray-900"><?php echo htmlspecialchars((string) $ucsEvent['title']); ?></h3>
                            <p class="mt-2 text-sm leading-6 text-gray-600"><?php echo htmlspecialchars(ucs_short_summary((string) $ucsEvent['description'], 150)); ?></p>

                            <div class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm text-gray-600">
                                <p class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M8 2v4M16 2v4M3 10h18"></path>
                                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    </svg>
                                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($ucsStart); ?></span>
                                    <span><?php echo htmlspecialchars($ucsTime . ($ucsEndsAt !== '' ? ' – ' . $ucsEndsAt : '')); ?></span>
                                </p>
                                <?php if (!empty($ucsEvent['venue'])): ?>
                                    <p class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <?php echo htmlspecialchars((string) $ucsEvent['venue']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if ($ucsRegLink !== ''): ?>
                                <a href="<?php echo htmlspecialchars($ucsRegLink); ?>" target="_blank" rel="noopener"
                                   class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    Register for this Event
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M7 17 17 7"></path>
                                        <path d="M7 7h10v10"></path>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($ucsTotalPages > 1): ?>
                    <?php
                    $ucsFilterParams = [];
                    if ($ucsQuery !== '')      { $ucsFilterParams['q'] = $ucsQuery; }
                    if ($ucsEventType !== '')  { $ucsFilterParams['type'] = $ucsEventType; }
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

                    $ucsPrevUrl = BASE_URL . '/alumni-events.php';
                    if ($ucsPage > 1) {
                        $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
                    }
                    $ucsNextUrl = BASE_URL . '/alumni-events.php';
                    if ($ucsPage < $ucsTotalPages) {
                        $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
                    }
                    $ucsPageLinkUrl = BASE_URL . '/alumni-events.php?';
                    ?>
                    <nav class="mt-8 flex flex-wrap items-center justify-between gap-3" aria-label="Events pagination">
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