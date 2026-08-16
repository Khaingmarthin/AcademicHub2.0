<?php
/**
 * Public About Us page.
 *
 * Renders the university introduction, history, vision, mission and
 * established year entirely from the university_profile table. No values are
 * hard-coded — everything is read from the database.
 */
require_once '../config/app.php';
require_once '../includes/database.php';

$pageTitle = 'About Us';

$ucsAbout = null;

try {
    $ucsStmt = $pdo->query(
        "SELECT name, short_name, history, vision, mission,
                established_year, logo, hero_media, address, phone, email
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsAbout = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsAbout = null;
}

$ucsFullName      = $ucsAbout['name'] ?? 'University of Computer Studies, Meiktila';
$ucsShortName     = $ucsAbout['short_name'] ?? 'UCSMTLA';
$ucsHistory       = trim((string) ($ucsAbout['history'] ?? ''));
$ucsVisionRaw     = trim((string) ($ucsAbout['vision'] ?? ''));
$ucsMissionRaw    = trim((string) ($ucsAbout['mission'] ?? ''));
$ucsEstablished   = (int) ($ucsAbout['established_year'] ?? 0);
$ucsAddress       = trim((string) ($ucsAbout['address'] ?? ''));
$ucsPhone         = trim((string) ($ucsAbout['phone'] ?? ''));
$ucsEmail         = trim((string) ($ucsAbout['email'] ?? ''));
$ucsAboutMedia    = $ucsAbout['hero_media'] ?? 'images/front_view.jpg';
$ucsLogo          = $ucsAbout['logo'] ?? 'images/logo.png';

if (!preg_match('~^https?://~i', $ucsAboutMedia)) {
    $ucsAboutMedia = BASE_URL . '/assets/' . ltrim($ucsAboutMedia, '/');
}

if (!preg_match('~^https?://~i', $ucsLogo)) {
    $ucsLogo = BASE_URL . '/assets/' . ltrim($ucsLogo, '/');
}

$ucsVisionStatements = array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $ucsVisionRaw))));
$ucsMissionStatements = array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $ucsMissionRaw))));

// Build history milestones by splitting the stored history into sentences and
// extracting the year mentioned in each one. The original wording is preserved;
// nothing is invented.
$ucsMilestones = [];
$ucsSentences  = preg_split('/(?<=[.!?])\s+/', $ucsHistory);
foreach ($ucsSentences as $ucsSentence) {
    $ucsSentence = trim($ucsSentence);
    if ($ucsSentence === '') {
        continue;
    }
    if (preg_match('/(?<!\d)(19|20)\d{2}(?!\d)/', $ucsSentence, $ucsYearMatch)) {
        $ucsMilestones[] = [
            'year' => (int) $ucsYearMatch[0],
            'text' => $ucsSentence,
        ];
    }
}

$ucsYearsServing = $ucsEstablished > 0 ? max(0, (int) date('Y') - $ucsEstablished) : 0;
$ucsPhoneHref    = $ucsPhone !== '' ? 'tel:+' . preg_replace('/\D/', '', $ucsPhone) : '';

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="about-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsAboutMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">About the University</p>
                <h1 id="about-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    About <span class="text-blue-400"><?php echo htmlspecialchars($ucsShortName); ?></span>
                </h1>
                <p class="mt-5 text-xl font-semibold text-white sm:text-2xl"><?php echo htmlspecialchars($ucsFullName); ?></p>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Discover our history, vision and commitment to computer education.
                </p>
            </div>
        </div>
    </section>

    <!-- University Introduction -->
    <section class="bg-white py-16 sm:py-20" aria-labelledby="about-intro-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div class="ucs-reveal order-2 lg:order-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">About UCSMTLA</p>
                    <h2 id="about-intro-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Building the future through computing education.</h2>
                    <p class="mt-4 text-base leading-7 text-gray-600">
                        <?php echo htmlspecialchars($ucsFullName); ?> is dedicated to computing and technology education,
                        offering undergraduate programmes in computer science and computer technology alongside a strong
                        foundation in modern digital disciplines.
                    </p>
                    <p class="mt-3 text-base leading-7 text-gray-600">
                        Through a student-centred curriculum, hands-on practical work and continuous academic development,
                        UCSMTLA nurtures every student's knowledge and skills — preparing capable, innovative graduates
                        ready for the digital future.
                    </p>
                </div>
                <div class="ucs-reveal order-1 lg:order-2">
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($ucsAboutMedia); ?>" alt="<?php echo htmlspecialchars($ucsShortName); ?> main campus" class="aspect-[4/3] w-full rounded-2xl object-cover shadow-xl shadow-gray-900/10 ring-1 ring-gray-900/5">
                        <?php if ($ucsAddress !== ''): ?>
                            <div class="absolute bottom-4 left-4 rounded-xl bg-white/95 px-4 py-3 shadow-lg shadow-gray-900/10 ring-1 ring-gray-900/5 backdrop-blur">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Main Campus</p>
                                <p class="mt-0.5 text-sm font-bold text-gray-900"><?php echo htmlspecialchars($ucsAddress); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- University History -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="about-history-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Our Story</p>
                <h2 id="about-history-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Our History</h2>
                <p class="mt-4 text-base leading-7 text-gray-600">From our foundation to today.</p>
            </div>

            <?php if ($ucsHistory !== '' && count($ucsMilestones) > 0): ?>
                <ol class="relative mx-auto mt-12 max-w-3xl">
                    <span class="absolute bottom-8 left-[19px] top-2 w-px bg-blue-200" aria-hidden="true"></span>
                    <?php foreach ($ucsMilestones as $ucsIndex => $ucsMilestone): ?>
                        <li class="ucs-reveal relative flex gap-5 pb-10 last:pb-0 sm:gap-7">
                            <span class="relative z-10 mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white ring-4 ring-slate-50">
                                <?php echo sprintf('%02d', $ucsIndex + 1); ?>
                            </span>
                            <div class="flex-1 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-gray-900/5 sm:p-7">
                                <p class="text-xl font-extrabold tracking-tight text-blue-600"><?php echo (int) $ucsMilestone['year']; ?></p>
                                <p class="mt-2 text-sm leading-6 text-gray-600 sm:text-base sm:leading-7"><?php echo htmlspecialchars($ucsMilestone['text']); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php elseif ($ucsHistory !== ''): ?>
                <div class="ucs-reveal mx-auto mt-12 max-w-3xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 sm:p-8">
                    <p class="text-base leading-7 text-gray-600"><?php echo nl2br(htmlspecialchars($ucsHistory)); ?></p>
                </div>
            <?php else: ?>
                <p class="mt-12 text-center text-sm text-gray-500">University history information will be available soon.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Vision & Mission -->
    <section class="bg-white py-16 sm:py-20" aria-labelledby="about-vision-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">What We Stand For</p>
                <h2 id="about-vision-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Vision &amp; Mission</h2>
                <p class="mt-4 text-base leading-7 text-gray-600">The direction that guides everything we do at <?php echo htmlspecialchars($ucsShortName); ?>.</p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-8">
                <!-- Vision -->
                <article class="ucs-reveal rounded-2xl bg-slate-50 p-8 ring-1 ring-gray-100">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Our Direction</p>
                            <h3 class="mt-0.5 text-2xl font-extrabold tracking-tight text-gray-900">Vision</h3>
                        </div>
                    </div>
                    <?php if (count($ucsVisionStatements) > 0): ?>
                        <ol class="mt-7 space-y-4">
                            <?php foreach ($ucsVisionStatements as $ucsIndex => $ucsVisionStatement): ?>
                                <li class="flex items-start gap-4">
                                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-xs font-bold text-blue-600 ring-1 ring-gray-100">
                                        <?php echo sprintf('%02d', $ucsIndex + 1); ?>
                                    </span>
                                    <p class="text-sm leading-6 text-gray-700"><?php echo htmlspecialchars($ucsVisionStatement); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p class="mt-7 text-sm text-gray-500">Vision information will be available soon.</p>
                    <?php endif; ?>
                </article>

                <!-- Mission -->
                <article class="ucs-reveal rounded-2xl bg-slate-50 p-8 ring-1 ring-gray-100">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4"></path>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Our Commitment</p>
                            <h3 class="mt-0.5 text-2xl font-extrabold tracking-tight text-gray-900">Mission</h3>
                        </div>
                    </div>
                    <?php if (count($ucsMissionStatements) > 0): ?>
                        <ol class="mt-7 space-y-4">
                            <?php foreach ($ucsMissionStatements as $ucsIndex => $ucsMissionStatement): ?>
                                <li class="flex items-start gap-4">
                                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-xs font-bold text-blue-600 ring-1 ring-gray-100">
                                        <?php echo sprintf('%02d', $ucsIndex + 1); ?>
                                    </span>
                                    <p class="text-sm leading-6 text-gray-700"><?php echo htmlspecialchars($ucsMissionStatement); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p class="mt-7 text-sm text-gray-500">Mission information will be available soon.</p>
                    <?php endif; ?>
                </article>
            </div>
        </div>
    </section>

    <!-- University at a Glance -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="about-glance-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">University Overview</p>
                <h2 id="about-glance-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">University at a Glance</h2>
            </div>

            <div class="ucs-reveal mx-auto mt-12 max-w-5xl rounded-3xl bg-blue-700 px-8 py-10 sm:px-10 sm:py-12">
                <dl class="grid grid-cols-1 gap-8 text-center sm:grid-cols-2 lg:grid-cols-4">
                    <?php if ($ucsEstablished > 0): ?>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-blue-200">Established</dt>
                            <dd class="mt-2 text-4xl font-extrabold tracking-tight text-white"><?php echo (int) $ucsEstablished; ?></dd>
                            <?php if ($ucsYearsServing > 0): ?>
                                <dd class="mt-1 text-sm font-medium text-blue-100"><?php echo (int) $ucsYearsServing; ?>+ Years of Academic Excellence</dd>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-blue-200">University</dt>
                        <dd class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl"><?php echo htmlspecialchars($ucsShortName); ?></dd>
                        <dd class="mt-1 text-sm font-medium leading-5 text-blue-100"><?php echo htmlspecialchars($ucsFullName); ?></dd>
                    </div>

                    <?php if ($ucsAddress !== ''): ?>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-blue-200">Location</dt>
                            <dd class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl"><?php echo htmlspecialchars($ucsAddress); ?></dd>
                            <dd class="mt-1 text-sm font-medium text-blue-100">Main Campus</dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($ucsPhone !== '' || $ucsEmail !== ''): ?>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-blue-200">Contact</dt>
                            <?php if ($ucsPhone !== ''): ?>
                                <dd class="mt-2 text-lg font-extrabold tracking-tight text-white">
                                    <a href="<?php echo htmlspecialchars($ucsPhoneHref); ?>" class="transition-colors duration-150 hover:text-blue-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-300"><?php echo htmlspecialchars($ucsPhone); ?></a>
                                </dd>
                            <?php endif; ?>
                            <?php if ($ucsEmail !== ''): ?>
                                <dd class="mt-1 break-all text-sm font-medium text-blue-100">
                                    <a href="<?php echo htmlspecialchars('mailto:' . $ucsEmail); ?>" class="transition-colors duration-150 hover:text-white focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-300"><?php echo htmlspecialchars($ucsEmail); ?></a>
                                </dd>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </section>
</main>

<script>
    (function () {
        'use strict';
        document.documentElement.classList.add('ucs-js');

        var els = document.querySelectorAll('.ucs-reveal');
        if (!('IntersectionObserver' in window)) {
            els.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        els.forEach(function (el) { observer.observe(el); });
    })();
</script>

<?php
require_once '../includes/footer.php';
?>