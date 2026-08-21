<?php
/**
 * Homepage Hero / Welcome section.
 *
 * Modern split-layout hero with floating visual cards, layered depth,
 * and animated accents. University data from university_profile table.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../../config/database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}

$ucsHero = null;
if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT name, short_name, hero_media, established_year
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsHero = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsHero = null;
    }
}

$ucsShortName = $ucsHero['short_name'] ?? 'UCSMTLA';
$ucsFullName  = $ucsHero['name'] ?? 'University of Computer Studies, Meiktila';
$ucsYear      = $ucsHero['established_year'] ?? '2007';
$ucsHeroMedia = ROOT_URL . '/assets/images/ucsmtla8.jpg';
?>
<section class="relative min-h-screen overflow-hidden bg-slate-950" aria-labelledby="hero-heading">

    <!-- ── Background image ── -->
    <div class="absolute inset-0">
        <img
            src="<?php echo htmlspecialchars($ucsHeroMedia); ?>"
            alt=""
            class="h-full w-full object-cover"
            loading="eager"
            fetchpriority="high"
        >
    </div>

    <!-- ── Gradient overlays ── -->
    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/80 to-slate-950/40" aria-hidden="true"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-slate-950/20" aria-hidden="true"></div>

    <!-- ── Decorative elements ── -->
    <div class="absolute -right-20 top-1/4 h-[500px] w-[500px] rounded-full bg-sky-500/8 blur-[150px]" aria-hidden="true"></div>
    <div class="absolute -left-20 bottom-1/4 h-[400px] w-[400px] rounded-full bg-indigo-500/6 blur-[120px]" aria-hidden="true"></div>

    <!-- ── Dot grid pattern ── -->
    <div class="absolute inset-0 opacity-[0.04]" aria-hidden="true" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 32px 32px;"></div>

    <!-- ── Content ── -->
    <div class="relative z-10 mx-auto flex min-h-screen items-center px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-7xl">
            <div class="hero-fade-up max-w-2xl lg:max-w-3xl">

                <!-- Main heading -->
                <h1 id="hero-heading" class="text-4xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-5xl lg:text-6xl xl:text-[4.25rem]">
                    <span class="block"><?php echo htmlspecialchars($ucsShortName); ?></span>
                    <span class="mt-3 block bg-gradient-to-r from-sky-300 via-blue-300 to-cyan-300 bg-clip-text text-transparent">
                        Academic Hub
                    </span>
                </h1>

                <!-- Subtitle -->
                <p class="mt-7 max-w-lg text-base leading-relaxed text-slate-300/90 sm:text-lg sm:leading-8">
                    <?php echo htmlspecialchars($ucsFullName); ?> &mdash;
                    Shaping the future of technology through quality education, innovative research, and practical skills development.
                </p>

                <!-- CTA buttons -->
                <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>"
                       class="group relative inline-flex h-14 items-center justify-center gap-2.5 overflow-hidden rounded-2xl bg-gradient-to-r from-sky-500 to-blue-600 px-9 text-sm font-semibold text-white shadow-xl shadow-sky-500/20 transition-all duration-300 hover:shadow-2xl hover:shadow-sky-500/30 hover:scale-[1.02] focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-300">
                        <span class="relative z-10">Explore Programmes</span>
                        <svg class="relative z-10 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                        <span class="absolute inset-0 bg-gradient-to-r from-sky-400 to-blue-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
                    </a>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/about.php'); ?>"
                       class="inline-flex h-14 items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white/5 px-9 text-sm font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:border-white/40 hover:bg-white/10 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        About Us
                        <svg class="h-4 w-4 opacity-60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 16v-4M12 8h.01"></path>
                        </svg>
                    </a>
                </div>

                <!-- Quick stats -->
                <div class="mt-14 flex items-center gap-8 border-t border-white/10 pt-8">
                    <div>
                        <div class="text-2xl font-extrabold text-white sm:text-3xl"><?php echo htmlspecialchars($ucsYear); ?></div>
                        <div class="mt-1 text-xs font-medium uppercase tracking-wider text-slate-400">Established</div>
                    </div>
                    <div class="h-12 w-px bg-white/10"></div>
                    <div>
                        <div class="text-2xl font-extrabold text-white sm:text-3xl">4</div>
                        <div class="mt-1 text-xs font-medium uppercase tracking-wider text-slate-400">Faculties</div>
                    </div>
                    <div class="h-12 w-px bg-white/10"></div>
                    <div>
                        <div class="text-2xl font-extrabold text-white sm:text-3xl">3</div>
                        <div class="mt-1 text-xs font-medium uppercase tracking-wider text-slate-400">Programmes</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Scroll indicator ── -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 hidden sm:flex flex-col items-center gap-2" aria-hidden="true">
        <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-white/30">Scroll</span>
        <div class="h-10 w-6 rounded-full border-2 border-white/15 flex justify-center pt-2">
            <div class="h-2 w-1 animate-bounce rounded-full bg-white/40"></div>
        </div>
    </div>

</section>
