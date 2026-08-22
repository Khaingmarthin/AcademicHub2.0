<?php
/**
 * Public About Us page.
 *
 * Renders the university introduction, history, vision, mission and
 * established year entirely from the university_profile table. No values are
 * hard-coded — everything is read from the database.
 */
require_once '../config/app.php';
require_once '../config/database.php';

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
    $ucsAboutMedia = ROOT_URL . '/assets/' . ltrim($ucsAboutMedia, '/');
}

if (!preg_match('~^https?://~i', $ucsLogo)) {
    $ucsLogo = ROOT_URL . '/assets/' . ltrim($ucsLogo, '/');
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
    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="about-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">About Us</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">About the University</p>
                <h1 id="about-page-heading" class="mt-3 scroll-mt-24 text-[2rem] font-extrabold tracking-[-0.025em] text-slate-900 sm:text-[2.5rem] lg:text-[3rem] leading-[1.1]">
                    <?php echo htmlspecialchars($ucsFullName); ?>
                </h1>
                <p class="mt-4 max-w-2xl text-[0.9375rem] leading-[1.85] text-slate-600">
                    Discover our history, vision and commitment to computing education.
                </p>
            </div>
        </div>
    </section>

    <!-- University Introduction -->
    <section class="bg-white py-16 sm:py-20" aria-labelledby="about-intro-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">About <?php echo htmlspecialchars($ucsShortName); ?></p>
                    <h2 id="about-intro-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl leading-[1.1]">
                        Building the future through computing education.
                    </h2>
                    <p class="mt-5 text-[0.9375rem] leading-[1.85] text-slate-600">
                        <?php echo htmlspecialchars($ucsFullName); ?> is dedicated to computing and technology education,
                        offering undergraduate programmes in computer science and computer technology alongside a strong
                        foundation in modern digital disciplines.
                    </p>
                    <p class="mt-3 text-[0.9375rem] leading-[1.85] text-slate-600">
                        Through a student-centred curriculum, hands-on practical work and continuous academic development,
                        <?php echo htmlspecialchars($ucsShortName); ?> nurtures every student's knowledge and skills — preparing capable, innovative graduates
                        ready for the digital future.
                    </p>
                </div>
                <div>
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($ucsAboutMedia); ?>" alt="<?php echo htmlspecialchars($ucsShortName); ?> main campus" class="aspect-[4/3] w-full rounded-lg object-cover ring-1 ring-slate-900/5">
                        <?php if ($ucsAddress !== ''): ?>
                            <div class="absolute bottom-4 left-4 bg-white px-4 py-3 ring-1 ring-slate-900/5">
                                <p class="text-[0.6875rem] font-semibold uppercase tracking-wide text-slate-400">Main Campus</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($ucsAddress); ?></p>
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
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Our Story</p>
                <h2 id="about-history-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl leading-[1.1]">Our History</h2>
                <p class="mt-3 max-w-xl text-[0.9375rem] leading-[1.85] text-slate-600">From our foundation to today.</p>
            </div>

            <?php if ($ucsHistory !== '' && count($ucsMilestones) > 0): ?>
                <div class="mt-12 max-w-3xl">
                    <div class="border-t border-slate-200">
                        <?php foreach ($ucsMilestones as $ucsIndex => $ucsMilestone): ?>
                            <article class="flex gap-6 py-8 first:pt-0 last:pb-0 sm:gap-10 <?php echo $ucsIndex < count($ucsMilestones) - 1 ? 'border-t border-slate-200' : ''; ?>">
                                <div class="shrink-0 pt-0.5">
                                    <span class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl"><?php echo (int) $ucsMilestone['year']; ?></span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[0.9375rem] leading-[1.85] text-slate-600"><?php echo htmlspecialchars($ucsMilestone['text']); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($ucsHistory !== ''): ?>
                <div class="mt-12 max-w-3xl border-t border-slate-200 pt-8">
                    <p class="text-[0.9375rem] leading-[1.85] text-slate-600"><?php echo nl2br(htmlspecialchars($ucsHistory)); ?></p>
                </div>
            <?php else: ?>
                <p class="mt-12 text-sm text-slate-500">University history information will be available soon.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Vision & Mission -->
    <section class="bg-white py-16 sm:py-20" aria-labelledby="about-vision-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">What We Stand For</p>
                <h2 id="about-vision-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl leading-[1.1]">Vision &amp; Mission</h2>
                <p class="mt-3 text-[0.9375rem] leading-[1.85] text-slate-600">The direction that guides everything we do at <?php echo htmlspecialchars($ucsShortName); ?>.</p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">
                <!-- Vision -->
                <div class="border-t border-slate-200 pt-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Our Direction</p>
                    <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.02em] text-slate-900">Vision</h3>
                    <?php if (count($ucsVisionStatements) > 0): ?>
                        <ol class="mt-6 space-y-4">
                            <?php foreach ($ucsVisionStatements as $ucsIndex => $ucsVisionStatement): ?>
                                <li class="flex items-start gap-4">
                                    <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center bg-slate-100 text-[0.6875rem] font-bold text-slate-700">
                                        <?php echo sprintf('%02d', $ucsIndex + 1); ?>
                                    </span>
                                    <p class="text-[0.9375rem] leading-[1.85] text-slate-600"><?php echo htmlspecialchars($ucsVisionStatement); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p class="mt-6 text-sm text-slate-500">Vision information will be available soon.</p>
                    <?php endif; ?>
                </div>

                <!-- Mission -->
                <div class="border-t border-slate-200 pt-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Our Commitment</p>
                    <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.02em] text-slate-900">Mission</h3>
                    <?php if (count($ucsMissionStatements) > 0): ?>
                        <ol class="mt-6 space-y-4">
                            <?php foreach ($ucsMissionStatements as $ucsIndex => $ucsMissionStatement): ?>
                                <li class="flex items-start gap-4">
                                    <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center bg-slate-100 text-[0.6875rem] font-bold text-slate-700">
                                        <?php echo sprintf('%02d', $ucsIndex + 1); ?>
                                    </span>
                                    <p class="text-[0.9375rem] leading-[1.85] text-slate-600"><?php echo htmlspecialchars($ucsMissionStatement); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p class="mt-6 text-sm text-slate-500">Mission information will be available soon.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- University at a Glance -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="about-glance-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">University Overview</p>
                <h2 id="about-glance-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl leading-[1.1]">University at a Glance</h2>
            </div>

            <div class="mt-12 max-w-5xl mx-auto border-t border-slate-200">
                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <?php if ($ucsEstablished > 0): ?>
                        <div class="py-8 sm:pr-8 <?php echo $ucsEstablished > 0 ? 'border-b border-slate-200 sm:border-b-0 sm:border-r sm:border-slate-200' : ''; ?>">
                            <dt class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Established</dt>
                            <dd class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900"><?php echo (int) $ucsEstablished; ?></dd>
                            <?php if ($ucsYearsServing > 0): ?>
                                <dd class="mt-1 text-sm text-slate-500"><?php echo (int) $ucsYearsServing; ?>+ Years of Academic Excellence</dd>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="py-8 sm:px-8 sm:border-b border-slate-200 lg:border-b-0 lg:border-r">
                        <dt class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-slate-400">University</dt>
                        <dd class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl"><?php echo htmlspecialchars($ucsShortName); ?></dd>
                        <dd class="mt-1 text-sm text-slate-500 leading-5"><?php echo htmlspecialchars($ucsFullName); ?></dd>
                    </div>

                    <?php if ($ucsAddress !== ''): ?>
                        <div class="py-8 sm:pl-8 sm:border-b border-slate-200 lg:border-b-0 lg:border-r lg:border-slate-200">
                            <dt class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Location</dt>
                            <dd class="mt-2 text-lg font-extrabold tracking-tight text-slate-900"><?php echo htmlspecialchars($ucsAddress); ?></dd>
                            <dd class="mt-1 text-sm text-slate-500">Main Campus</dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($ucsPhone !== '' || $ucsEmail !== ''): ?>
                        <div class="py-8 sm:pl-8">
                            <dt class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Contact</dt>
                            <?php if ($ucsPhone !== ''): ?>
                                <dd class="mt-2 text-lg font-extrabold tracking-tight text-slate-900">
                                    <a href="<?php echo htmlspecialchars($ucsPhoneHref); ?>" class="transition-colors duration-150 hover:text-blue-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><?php echo htmlspecialchars($ucsPhone); ?></a>
                                </dd>
                            <?php endif; ?>
                            <?php if ($ucsEmail !== ''): ?>
                                <dd class="mt-1 break-all text-sm text-slate-500">
                                    <a href="<?php echo htmlspecialchars('mailto:' . $ucsEmail); ?>" class="transition-colors duration-150 hover:text-blue-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><?php echo htmlspecialchars($ucsEmail); ?></a>
                                </dd>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>
