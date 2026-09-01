<?php
/**
 * Homepage Student Life & Events section.
 *
 * Active gallery with three topic rows: University Events, Competitions,
 * and Seminars & Workshops. Each row has its own horizontal image carousel.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

$ucsTopics = [
    [
        'label'  => 'University Events',
        'images' => [
            'images/football.jpg',
            'images/football1.jpg',
            'images/football3.jpg',
            'images/football4.jpg',
            'images/football5.jpg',
            'images/football6.jpg',
            'images/football7.jpg',
        ],
    ],
    [
        'label'  => 'Competitions',
        'images' => [
            'images/ps.jpg',
            'images/ps2.jpg',
            'images/ps3.jpg',
            'images/ps4.jpg',
            'images/ps5.jpg',
            'images/ps6.jpg',
            'images/ps7.jpg',
            'images/ps8.jpg',
        ],
    ],
    [
        'label'  => 'Seminars & Workshops',
        'images' => [
            'images/knowledge1.jpg',
            'images/knowledge2.jpg',
            'images/knowledge3.jpg',
            'images/knowledge4.jpg',
        ],
    ],
];
?>
<section class="bg-white py-16 sm:py-20" aria-labelledby="student-life-heading">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

        <!-- Section header -->
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Beyond the Classroom</p>
            <h2 id="student-life-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Student Life at UCSMTLA</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">
                Discover the activities, events, and experiences that make student life at UCSMTLA engaging and memorable.
            </p>
        </div>

        <!-- Topic rows -->
        <div class="mt-12 space-y-10">
            <?php foreach ($ucsTopics as $ucsTopicIdx => $ucsTopic): ?>
                <?php $ucsImages = $ucsTopic['images']; ?>
                <div class="ucs-topic-row" data-topic-row="<?php echo $ucsTopicIdx; ?>">

                    <!-- Row header -->
                    <h3 class="mb-4 text-base font-bold tracking-tight text-gray-900 sm:text-lg"><?php echo htmlspecialchars($ucsTopic['label']); ?></h3>

                    <!-- Carousel with side arrows -->
                    <div class="relative flex items-center gap-4">

                        <!-- Left arrow -->
                        <button type="button" data-row-prev="<?php echo $ucsTopicIdx; ?>" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm transition-all duration-200 hover:border-blue-300 hover:text-blue-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Previous">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>

                        <!-- Track -->
                        <div class="overflow-hidden rounded-xl flex-1">
                            <div class="flex transition-transform duration-500 ease-in-out" data-row-track="<?php echo $ucsTopicIdx; ?>">
                                <?php foreach ($ucsImages as $ucsImg): ?>
                                    <div class="ucs-row-slide shrink-0 px-2" style="width:33.333%;">
                                        <div class="group relative overflow-hidden rounded-xl bg-gray-100" style="aspect-ratio:16/10;">
                                            <?php
                                            $ucsFile = __DIR__ . '/../../assets/' . ltrim($ucsImg, '/');
                                            $ucsUrl  = is_file($ucsFile) ? ROOT_URL . '/assets/' . ltrim($ucsImg, '/') : '';
                                            ?>
                                            <?php if ($ucsUrl !== ''): ?>
                                                <img src="<?php echo htmlspecialchars($ucsUrl); ?>" alt="<?php echo htmlspecialchars($ucsTopic['label']); ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                                            <?php else: ?>
                                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Right arrow -->
                        <button type="button" data-row-next="<?php echo $ucsTopicIdx; ?>" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm transition-all duration-200 hover:border-blue-300 hover:text-blue-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Next">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
(function () {
    'use strict';

    var rows = document.querySelectorAll('[data-topic-row]');
    if (!rows.length) return;

    rows.forEach(function (row) {
        var idx    = row.getAttribute('data-topic-row');
        var track  = row.querySelector('[data-row-track="' + idx + '"]');
        var slides = row.querySelectorAll('.ucs-row-slide');
        var prev   = row.querySelector('[data-row-prev="' + idx + '"]');
        var next   = row.querySelector('[data-row-next="' + idx + '"]');
        var total  = slides.length;
        var pos    = 0;

        function getVisible() {
            var w = window.innerWidth;
            if (w < 640) return 1;
            if (w < 1024) return 2;
            return 3;
        }

        function update() {
            var vis    = getVisible();
            var maxPos = Math.max(0, total - vis);
            if (pos > maxPos) pos = maxPos;
            var offset = pos * (100 / total);
            track.style.transform = 'translateX(-' + offset + '%)';
        }

        if (prev) prev.addEventListener('click', function () {
            var vis    = getVisible();
            var maxPos = Math.max(0, total - vis);
            pos = pos <= 0 ? maxPos : pos - 1;
            update();
        });

        if (next) next.addEventListener('click', function () {
            var vis    = getVisible();
            var maxPos = Math.max(0, total - vis);
            pos = pos >= maxPos ? 0 : pos + 1;
            update();
        });

        // Touch / swipe
        var touchX = 0;
        track.addEventListener('touchstart', function (e) { touchX = e.changedTouches[0].screenX; }, { passive: true });
        track.addEventListener('touchend', function (e) {
            var diff = touchX - e.changedTouches[0].screenX;
            if (Math.abs(diff) > 40) {
                var vis    = getVisible();
                var maxPos = Math.max(0, total - vis);
                if (diff > 0) { pos = pos >= maxPos ? 0 : pos + 1; }
                else          { pos = pos <= 0 ? maxPos : pos - 1; }
                update();
            }
        }, { passive: true });

        window.addEventListener('resize', update);
        update();
    });
})();
</script>