<?php
/**
 * Public Admissions page.
 *
 * Displays the admission requirements for graduate programmes in an
 * institutional editorial layout consistent with the rest of the site.
 */
require_once '../config/app.php';
require_once '../config/database.php';

$pageTitle = 'Admissions';
?>
<?php require_once '../includes/header.php'; ?>
<main class="flex-1">
    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="admissions-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Admissions</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admissions</p>
                <h1 id="admissions-page-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                    Admission Requirements for Graduate Program
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    The bachelor degree programme is a basic and challenging undergraduate programme with
                    area specialisations. It encompasses the basic IT technology, both applied and fundamental.
                </p>
            </div>
        </div>
    </section>

    <!-- Programme overview -->
    <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="programme-overview-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl space-y-6">
                <div class="border border-slate-200 bg-white">
                    <div class="border-b border-slate-200 px-6 py-4 sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Programme Overview</p>
                        <h2 id="programme-overview-heading" class="mt-1 text-lg font-semibold tracking-tight text-slate-900">About the Programme</h2>
                    </div>
                    <div class="px-6 py-6 sm:px-8 sm:py-8">
                        <p class="text-sm leading-7 text-slate-600">
                            The bachelor degree programme is a basic and challenging undergraduate programme with
                            area specialisations. It encompasses the basic IT technology, both applied and
                            fundamental. The programme also provides advanced and in-depth knowledge of IT to
                            prepare the students for challenges in IT career. The programme is full-time courses.
                        </p>
                    </div>
                </div>

                <div class="border border-slate-200 bg-white">
                    <div class="border-b border-slate-200 px-6 py-4 sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Eligibility</p>
                        <h2 class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Admission Criteria</h2>
                    </div>
                    <div class="px-6 py-6 sm:px-8 sm:py-8">
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M20 6 9 17l-5-5"></path>
                                    </svg>
                                </span>
                                <span class="text-sm leading-7 text-slate-600">
                                    Candidates must pass the matriculation exam held by government.
                                </span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M20 6 9 17l-5-5"></path>
                                    </svg>
                                </span>
                                <span class="text-sm leading-7 text-slate-600">
                                    Candidates must possess the total exam marks not less than the mark specified
                                    by the University.
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>