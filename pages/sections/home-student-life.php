<?php
/**
 * Homepage Student Life & Events section.
 *
 * Showcases student activities, university events, competitions, seminars and
 * workshops, and sports activities. Real event/activity content is not yet
 * available in the database, so neutral placeholder items are rendered rather
 * than inventing UCSMTLA events. The items array below is the single source
 * for this content and can be swapped for database-driven data later. The
 * sports card uses a real campus photo where available.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

$ucsStudentLifeItems = [
    [
        'title'       => 'Student Activities',
        'description' => 'Student clubs and societies bring the campus community together with activities and social events throughout the academic year.',
        'image'       => null,
        'icon'        => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'span'        => '',
    ],
    [
        'title'       => 'University Events',
        'description' => 'Campus-wide celebrations and gatherings connect students, faculty, and staff and add energy to university life.',
        'image'       => null,
        'icon'        => '<rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path>',
        'span'        => '',
    ],
    [
        'title'       => 'Competitions',
        'description' => 'Friendly competitions give students the chance to showcase their skills, creativity, and teamwork.',
        'image'       => null,
        'icon'        => '<path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4z"></path><path d="M7 5H4v2a3 3 0 0 0 3 3M17 5h3v2a3 3 0 0 1-3 3"></path>',
        'span'        => '',
    ],
    [
        'title'       => 'Seminars & Workshops',
        'description' => 'Seminars and workshops extend learning beyond the classroom with practical, hands-on sessions.',
        'image'       => null,
        'icon'        => '<path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.4 1 2.3h6c0-.9.4-1.8 1-2.3A7 7 0 0 0 12 2z"></path>',
        'span'        => '',
    ],
    [
        'title'       => 'Sports Activities',
        'description' => 'Sports activities promote fitness, teamwork, and campus spirit on the university grounds.',
        'image'       => 'images/football-field.jpg',
        'icon'        => '',
        'span'        => 'sm:col-span-2 lg:col-span-2',
    ],
];
?>
<section class="bg-white py-16 sm:py-20" aria-labelledby="student-life-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Beyond the Classroom</p>
            <h2 id="student-life-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Student Life at UCSMTLA</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">
                Discover the activities, events, and experiences that make student life at UCSMTLA engaging and memorable.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
            <?php foreach ($ucsStudentLifeItems as $ucsItem): ?>
                <?php
                $ucsHasImage = false;
                $ucsImageUrl = '';
                if (!empty($ucsItem['image'])) {
                    $ucsImageFile = __DIR__ . '/../../assets/' . ltrim($ucsItem['image'], '/');
                    $ucsHasImage = is_file($ucsImageFile);
                    if ($ucsHasImage) {
                        $ucsImageUrl = ROOT_URL . '/assets/' . ltrim($ucsItem['image'], '/');
                    }
                }
                ?>
                <article class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 <?php echo htmlspecialchars($ucsItem['span']); ?>">
                    <div class="relative aspect-[16/10] overflow-hidden">
                        <?php if ($ucsHasImage): ?>
                            <img src="<?php echo htmlspecialchars($ucsImageUrl); ?>" alt="<?php echo htmlspecialchars($ucsItem['title']); ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/40 to-transparent" aria-hidden="true"></div>
                        <?php else: ?>
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-50" aria-hidden="true"></div>
                            <div class="absolute -top-8 -right-8 h-28 w-28 rounded-full bg-blue-100/60" aria-hidden="true"></div>
                            <div class="absolute -bottom-10 -left-10 h-28 w-28 rounded-full bg-indigo-100/60" aria-hidden="true"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-md ring-1 ring-gray-100 transition-transform duration-200 group-hover:scale-110">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <?php echo $ucsItem['icon']; ?>
                                    </svg>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-1 flex-col p-5 sm:p-6">
                        <h3 class="text-lg font-bold tracking-tight text-gray-900">
                            <?php echo htmlspecialchars($ucsItem['title']); ?>
                        </h3>
                        <p class="mt-2 flex-1 text-sm leading-6 text-gray-600">
                            <?php echo htmlspecialchars($ucsItem['description']); ?>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>