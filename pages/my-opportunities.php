<?php
/**
 * Alumni self-management - my career opportunities (protected).
 *
 * Lists the career opportunities posted by the authenticated, verified
 * alumnus, with edit and delete actions. Each row shows whether the posting
 * is still publicly visible.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/alumni-validation.php';
require_once __DIR__ . '/../includes/helpers/opportunity-validation.php';

student_require_login();

$ucsProfile = alumni_current_profile($pdo);
if ($ucsProfile === null) {
    header('Location: ' . BASE_URL . '/alumni.php');
    exit;
}

$ucsStudentId = (int) $ucsProfile['student_id'];
$pageTitle    = 'My Career Opportunities';

$ucsFlash = $_SESSION['opportunity_flash'] ?? null;
unset($_SESSION['opportunity_flash']);

$ucsOpportunities = opportunity_load_own($pdo, $ucsStudentId);
$ucsActiveCount   = 0;
foreach ($ucsOpportunities as $ucsOpportunity) {
    if (opportunity_is_public($ucsOpportunity)) {
        $ucsActiveCount++;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-1 bg-slate-50">
    <section class="py-12 sm:py-16" aria-labelledby="my-opportunities-heading">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <?php if ($ucsFlash !== null): ?>
                <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100 text-red-700' : 'bg-emerald-50 ring-emerald-100 text-emerald-700'; ?> rounded-xl px-4 py-3 ring-1" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
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

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Career Community</p>
                    <h1 id="my-opportunities-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">My Career Opportunities</h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600">
                        Share real openings with current students and other alumni. Postings are publicly visible until their deadline.
                    </p>
                </div>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/opportunity-create.php'); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="M12 5v14"></path>
                    </svg>
                    Share Career Opportunity
                </a>
            </div>

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
                    <h3 class="mt-5 text-lg font-bold text-gray-900">No opportunities shared yet</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">
                        When you hear about a job, internship or freelance opening, share it here to help fellow UCSMTLA alumni and students.
                    </p>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/opportunity-create.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
                        Share Your First Opportunity
                    </a>
                </div>
            <?php else: ?>
                <div class="mt-8 flex items-center gap-3">
                    <span class="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 ring-4 ring-blue-100" aria-hidden="true"></span>
                    <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-900">Your Postings</h2>
                    <span class="h-px flex-1 bg-blue-100" aria-hidden="true"></span>
                    <span class="inline-flex shrink-0 items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-100">
                        <?php echo $ucsActiveCount; ?> Public
                    </span>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
                    <div class="hidden border-b border-blue-100 bg-blue-50/60 px-6 py-3 sm:flex sm:items-center sm:gap-6">
                        <span class="w-44 shrink-0 text-xs font-bold uppercase tracking-wider text-blue-700">Title</span>
                        <span class="w-36 shrink-0 text-xs font-bold uppercase tracking-wider text-blue-700">Company</span>
                        <span class="w-24 shrink-0 text-xs font-bold uppercase tracking-wider text-blue-700">Type</span>
                        <span class="w-28 shrink-0 text-xs font-bold uppercase tracking-wider text-blue-700">Posted</span>
                        <span class="flex-1 text-right text-xs font-bold uppercase tracking-wider text-blue-700">Status &amp; Actions</span>
                    </div>
                    <ul class="divide-y divide-blue-100">
                        <?php foreach ($ucsOpportunities as $ucsOpportunity): ?>
                            <?php
                            $ucsOpportunityUrl = BASE_URL . '/career-opportunity-details.php?id=' . (int) $ucsOpportunity['id'];
                            $ucsPublic = opportunity_is_public($ucsOpportunity);
                            ?>
                            <li class="flex flex-col gap-3 px-6 py-4 transition-colors duration-150 hover:bg-blue-50/60 sm:flex-row sm:items-center sm:gap-6">
                                <div class="w-44 shrink-0">
                                    <a href="<?php echo htmlspecialchars($ucsOpportunityUrl); ?>" class="text-sm font-bold text-blue-700 transition-colors hover:text-blue-800"><?php echo htmlspecialchars((string) $ucsOpportunity['title']); ?></a>
                                </div>
                                <span class="w-36 shrink-0 text-sm font-medium text-slate-800"><?php echo htmlspecialchars((string) $ucsOpportunity['company']); ?></span>
                                <span class="w-24 shrink-0 text-sm text-slate-500"><?php echo htmlspecialchars(opportunity_employment_label((string) $ucsOpportunity['employment_type'])); ?></span>
                                <span class="w-28 shrink-0 text-sm text-slate-500"><?php echo htmlspecialchars(date('M j, Y', strtotime((string) $ucsOpportunity['created_at']))); ?></span>
                                <div class="flex flex-1 flex-wrap items-center justify-end gap-2">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 <?php echo $ucsPublic ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-gray-100 text-gray-500 ring-gray-200'; ?>">
                                        <?php echo $ucsPublic ? 'Public' : (ucfirst((string) $ucsOpportunity['status']) === 'Hidden' ? 'Hidden' : 'Expired'); ?>
                                    </span>
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/opportunity-edit.php?id=' . (int) $ucsOpportunity['id']); ?>" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-amber-600 transition-colors duration-150 hover:bg-amber-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                            <path d="m15 5 4 4"></path>
                                        </svg>
                                        Edit
                                    </a>
                                    <a href="<?php echo htmlspecialchars($ucsOpportunityUrl); ?>" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:bg-blue-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        View
                                    </a>
                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/opportunity/delete.php'); ?>" class="inline-flex"
                                          onsubmit="return confirm('Delete this opportunity? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsOpportunity['id']; ?>">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-red-600 transition-colors duration-150 hover:bg-red-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18"></path>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>