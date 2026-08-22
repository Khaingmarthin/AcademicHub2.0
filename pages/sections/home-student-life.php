<?php
/**
 * Homepage Student Life section.
 *
 * Clean editorial layout showing student activities.
 * Structured bordered list with clear hierarchy.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

$ucsStudentLifeItems = [
    [
        'title'       => 'Student Activities',
        'description' => 'Student clubs and societies bring the campus community together with activities and social events throughout the academic year.',
    ],
    [
        'title'       => 'University Events',
        'description' => 'Campus-wide celebrations and gatherings connect students, faculty, and staff and add energy to university life.',
    ],
    [
        'title'       => 'Competitions',
        'description' => 'Friendly competitions give students the chance to showcase their skills, creativity, and teamwork.',
    ],
    [
        'title'       => 'Seminars & Workshops',
        'description' => 'Seminars and workshops extend learning beyond the classroom with practical, hands-on sessions.',
    ],
];
?>
<section class="border-t border-slate-200 bg-slate-50 py-20 sm:py-24" aria-labelledby="student-life-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- Section header -->
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Beyond the Classroom</p>
            <h2 id="student-life-heading" class="mt-3 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-slate-900 sm:text-4xl">Student Life at UCSMTLA</h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600">
                Discover the activities, events, and experiences that make student life at UCSMTLA engaging and memorable.
            </p>
        </div>

        <div class="mx-auto mt-12 max-w-4xl">
            <div class="border border-slate-200 bg-white">
                <?php foreach ($ucsStudentLifeItems as $ucsIdx => $ucsItem): ?>
                    <?php $ucsIsFirst = $ucsIdx === 0; ?>
                    <article class="group px-6 py-5 sm:px-8 sm:py-6 <?php echo !$ucsIsFirst ? 'border-t border-slate-200' : ''; ?> transition-colors duration-150 hover:bg-slate-50/60">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <h3 class="text-base font-semibold tracking-tight text-slate-900">
                                <?php echo htmlspecialchars($ucsItem['title']); ?>
                            </h3>
                        </div>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                            <?php echo htmlspecialchars($ucsItem['description']); ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
