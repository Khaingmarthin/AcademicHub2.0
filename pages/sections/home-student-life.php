<?php
/**
 * Homepage Student Life section.
 *
 * Simplified institutional layout showing student activities.
 * Clean typography, minimal visual elements.
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
<section class="border-t border-gray-200 bg-gray-50 py-20 sm:py-24" aria-labelledby="student-life-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- Section header -->
        <div class="mx-auto max-w-2xl text-center">
            <div class="flex items-center justify-center gap-3">
                <span class="h-px w-8 bg-blue-600" aria-hidden="true"></span>
                <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-blue-600">Beyond the Classroom</span>
                <span class="h-px w-8 bg-blue-600" aria-hidden="true"></span>
            </div>
            <h2 id="student-life-heading" class="mt-4 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-gray-900 sm:text-4xl">Student Life at UCSMTLA</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">
                Discover the activities, events, and experiences that make student life at UCSMTLA engaging and memorable.
            </p>
        </div>

        <div class="mx-auto mt-12 max-w-4xl">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                <?php foreach ($ucsStudentLifeItems as $ucsItem): ?>
                    <article class="flex flex-col gap-3">
                        <h3 class="text-lg font-semibold tracking-tight text-gray-900">
                            <?php echo htmlspecialchars($ucsItem['title']); ?>
                        </h3>
                        <p class="text-sm leading-6 text-gray-500">
                            <?php echo htmlspecialchars($ucsItem['description']); ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
