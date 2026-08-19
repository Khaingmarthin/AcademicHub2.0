<?php
/**
 * Public Career Opportunity detail page.
 *
 * Shows a single active, unexpired opportunity with the full description and
 * the poster's how-to-apply instructions. The poster is identified by name
 * with an Alumni badge; their personal contact channels are never shown.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/helpers/opportunity-validation.php';

$ucsOpportunityId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($ucsOpportunityId === false) {
    $ucsOpportunityId = null;
}

$ucsOpportunity = $ucsOpportunityId !== null ? opportunity_get($pdo, $ucsOpportunityId) : null;

if ($ucsOpportunity === null || !opportunity_is_public($ucsOpportunity)) {
    http_response_code(404);
    $pageTitle = 'Opportunity Not Found';
    require_once '../includes/header.php';
    ?>
    <main class="flex-1 bg-slate-50">
        <section class="py-24 text-center" aria-labelledby="opportunity-not-found-heading">
            <div class="mx-auto max-w-md px-4 sm:px-6">
                <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </span>
                <h1 id="opportunity-not-found-heading" class="mt-5 text-2xl font-extrabold tracking-tight text-gray-900">Opportunity Not Found</h1>
                <p class="mt-2 text-sm leading-6 text-gray-600">
                    This opportunity is no longer available or has been removed.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/career-opportunities.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
                    Browse Career Opportunities
                </a>
            </div>
        </section>
    </main>
    <?php
    require_once '../includes/footer.php';
    exit;
}

$pageTitle = (string) $ucsOpportunity['title'];

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

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

$ucsOpportunityUrl = BASE_URL . '/career-opportunities.php';
$ucsPostedAt       = date('F j, Y', strtotime((string) $ucsOpportunity['created_at']));
$ucsExpiryLabel    = !empty($ucsOpportunity['expires_at'])
    ? date('F j, Y', strtotime((string) $ucsOpportunity['expires_at']))
    : '';

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="opportunity-details-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
            <div class="hero-fade-up">
                <a href="<?php echo htmlspecialchars($ucsOpportunityUrl); ?>" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold text-blue-200 ring-1 ring-white/20 transition-colors duration-150 hover:bg-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    Back to Career Opportunities
                </a>
                <span class="mx-auto mt-5 block">
                    <span class="inline-flex items-center rounded-full bg-blue-600/90 px-3 py-1 text-xs font-semibold text-white">
                        <?php echo htmlspecialchars(opportunity_employment_label((string) $ucsOpportunity['employment_type'])); ?>
                    </span>
                </span>
                <h1 id="opportunity-details-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    <?php echo htmlspecialchars((string) $ucsOpportunity['title']); ?>
                </h1>
                <p class="mt-3 text-lg font-semibold text-blue-200"><?php echo htmlspecialchars((string) $ucsOpportunity['company']); ?></p>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="opportunity-details-body-heading">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <!-- Meta bar -->
                <div class="grid grid-cols-2 gap-px border-b border-gray-100 bg-gray-100 sm:grid-cols-4">
                    <div class="bg-white px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Type</p>
                        <p class="mt-1 text-sm font-bold text-gray-900"><?php echo htmlspecialchars(opportunity_employment_label((string) $ucsOpportunity['employment_type'])); ?></p>
                    </div>
                    <div class="bg-white px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Location</p>
                        <p class="mt-1 text-sm font-bold text-gray-900"><?php echo htmlspecialchars((string) ($ucsOpportunity['location'] !== '' ? $ucsOpportunity['location'] : '—')); ?></p>
                    </div>
                    <div class="bg-white px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Salary</p>
                        <p class="mt-1 text-sm font-bold text-gray-900"><?php echo htmlspecialchars((string) ($ucsOpportunity['salary_range'] !== '' ? $ucsOpportunity['salary_range'] : '—')); ?></p>
                    </div>
                    <div class="bg-white px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Deadline</p>
                        <p class="mt-1 text-sm font-bold text-gray-900"><?php echo htmlspecialchars($ucsExpiryLabel !== '' ? $ucsExpiryLabel : 'Open'); ?></p>
                    </div>
                </div>

                <div class="px-6 py-8 sm:px-8">
                    <h2 id="opportunity-details-body-heading" class="text-base font-bold text-gray-900">About this Opportunity</h2>
                    <div class="mt-4 whitespace-pre-line text-sm leading-7 text-gray-700">
                        <?php echo htmlspecialchars((string) $ucsOpportunity['description']); ?>
                    </div>

                    <?php if (trim((string) $ucsOpportunity['how_to_apply']) !== ''): ?>
                        <h2 class="mt-8 text-base font-bold text-gray-900">How to Apply</h2>
                        <div class="mt-4 whitespace-pre-line rounded-xl bg-blue-50 px-5 py-4 text-sm leading-7 text-blue-900 ring-1 ring-blue-100">
                            <?php echo htmlspecialchars((string) $ucsOpportunity['how_to_apply']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-gray-100 pt-5 text-xs text-gray-500">
                        <?php
                        $ucsPosterProfileUrl = null;
                        if (!empty($ucsOpportunity['poster_alumni_profile_id'])
                            && (string) ($ucsOpportunity['poster_profile_visibility'] ?? '') === 'public') {
                            $ucsPosterProfileUrl = BASE_URL . '/alumni-details.php?id=' . (int) $ucsOpportunity['poster_alumni_profile_id'];
                        }
                        ?>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-[11px] font-bold text-white" aria-hidden="true">
                                <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsOpportunity['poster_name']), 0, 1))); ?>
                            </span>
                            Shared by
                            <?php if ($ucsPosterProfileUrl !== null): ?>
                                <a href="<?php echo htmlspecialchars($ucsPosterProfileUrl); ?>" class="font-semibold text-blue-700 transition-colors duration-150 hover:text-blue-800 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <?php echo htmlspecialchars((string) $ucsOpportunity['poster_name']); ?>
                                </a>
                            <?php else: ?>
                                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars((string) $ucsOpportunity['poster_name']); ?></span>
                            <?php endif; ?>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">Alumni</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8 2v4M16 2v4M3 10h18"></path>
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            </svg>
                            Posted on <?php echo htmlspecialchars($ucsPostedAt); ?>
                        </span>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php?q=' . urlencode((string) $ucsOpportunity['title'])); ?>" class="inline-flex items-center gap-1.5 text-blue-700 transition-colors duration-150 hover:text-blue-800 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Discuss this opportunity
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="<?php echo htmlspecialchars($ucsOpportunityUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    Back to all opportunities
                </a>
            </div>
        </div>
    </section>
</main>
<?php
require_once '../includes/footer.php';
?>