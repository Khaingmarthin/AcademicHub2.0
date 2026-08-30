<?php
/**
 * Public Alumni Overview hub page.
 *
 * The entry point to the Alumni & Career Community. It cross-links every
 * alumni feature (Directory, Stories, Career Discussions) so visitors,
 * current students and verified alumni can all find their way around from
 * one place.
 *
 * Editorial institutional layout with clean borders and structured CTAs.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';

$pageTitle = 'Alumni Overview';

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

// Verified-alumni status drives the "your space" section of the page.
$ucsLoggedInStudent = student_current_user();
$ucsIsVerifiedAlumnus = false;
if ($ucsLoggedInStudent !== null) {
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id FROM alumni_profiles
             WHERE student_id = :student_id AND verification_status = 'verified'
             LIMIT 1"
        );
        $ucsStmt->execute([':student_id' => $ucsLoggedInStudent['id']]);
        $ucsIsVerifiedAlumnus = $ucsStmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        $ucsIsVerifiedAlumnus = false;
    }
}

// Community stats derived only from public data.
$ucsStats = ['alumni' => 0, 'discussions' => 0, 'stories' => 0];
try {
    $ucsStats['alumni'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM alumni_profiles
         WHERE verification_status = 'verified' AND visibility = 'public'"
    )->fetchColumn();
} catch (PDOException $e) {
}
try {
    $ucsStats['discussions'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM discussions WHERE status <> 'hidden'"
    )->fetchColumn();
} catch (PDOException $e) {
}
try {
    $ucsStats['stories'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM alumni_stories
         WHERE status = 'published' AND (publication_date IS NULL OR publication_date <= NOW())"
    )->fetchColumn();
} catch (PDOException $e) {
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="alumni-overview-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Alumni Overview</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Alumni &amp; Career Community</p>
                <h1 id="alumni-overview-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                    Alumni Overview
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    UCSMTLA graduates stay connected through the directory, career discussions,
                    and stories that keep our community thriving.
                </p>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="border-b border-slate-200 bg-slate-50" aria-label="Alumni community statistics">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
            <dl class="grid grid-cols-3 gap-px overflow-hidden border border-slate-200 bg-slate-200">
                <div class="bg-white px-6 py-6 text-center">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Alumni</dt>
                    <dd class="mt-2 text-3xl font-extrabold tracking-tight text-blue-600"><?php echo (int) $ucsStats['alumni']; ?></dd>
                </div>
                <div class="bg-white px-6 py-6 text-center">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Career Discussions</dt>
                    <dd class="mt-2 text-3xl font-extrabold tracking-tight text-blue-600"><?php echo (int) $ucsStats['discussions']; ?></dd>
                </div>
                <div class="bg-white px-6 py-6 text-center">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Alumni Stories</dt>
                    <dd class="mt-2 text-3xl font-extrabold tracking-tight text-blue-600"><?php echo (int) $ucsStats['stories']; ?></dd>
                </div>
            </dl>
        </div>
    </section>

    <!-- Community features — editorial bordered list -->
    <section class="bg-white py-12 sm:py-16 lg:py-20" aria-labelledby="alumni-community-features-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Explore</p>
                <h2 id="alumni-community-features-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Ways to Connect</h2>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    Browse the community, share your experience, or find guidance — everything is open to students and alumni alike.
                </p>
            </div>

            <div class="mx-auto mt-10 max-w-4xl">
                <div class="border border-slate-200 bg-white">
                    <!-- Alumni Directory -->
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="group flex items-start gap-5 px-6 py-6 transition-colors duration-150 hover:bg-slate-50/60 sm:px-8 sm:py-7 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-blue-600">
                        <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-semibold tracking-tight text-slate-900 group-hover:text-blue-700">Alumni Directory</h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-500">
                                Search and browse verified UCSMTLA graduates by major, graduation year and profession.
                            </p>
                            <span class="mt-2.5 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600">
                                Browse the directory
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                            </span>
                        </div>
                    </a>

                    <!-- Alumni Stories -->
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-stories.php'); ?>" class="group flex items-start gap-5 border-t border-slate-200 px-6 py-6 transition-colors duration-150 hover:bg-slate-50/60 sm:px-8 sm:py-7 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-blue-600">
                        <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-semibold tracking-tight text-slate-900 group-hover:text-blue-700">Alumni Stories</h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-500">
                                Read the journeys, achievements and advice of graduates across industries.
                            </p>
                            <span class="mt-2.5 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600">
                                Read the stories
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                            </span>
                        </div>
                    </a>

                    <!-- Career Discussions -->
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="group flex items-start gap-5 border-t border-slate-200 px-6 py-6 transition-colors duration-150 hover:bg-slate-50/60 sm:px-8 sm:py-7 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-blue-600">
                        <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-semibold tracking-tight text-slate-900 group-hover:text-blue-700">Career Discussions</h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-500">
                                Browse career discussions started by verified alumni and join the conversation.
                            </p>
                            <span class="mt-2.5 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600">
                                Join the conversation
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                            </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php if ($ucsIsVerifiedAlumnus): ?>
        <!-- Verified alumni call to action -->
        <section class="border-t border-slate-200 bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="alumni-overview-cta-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="border border-slate-200 bg-white px-6 py-8 sm:px-10 sm:py-10 text-center sm:rounded-none">
                    <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                    </span>
                    <h2 id="alumni-overview-cta-heading" class="mt-5 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Welcome back, alumnus</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-slate-600">
                        Manage your profile, share your story, and start career discussions from your alumni dashboard.
                    </p>
                    <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Open Alumni Dashboard
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php elseif ($ucsLoggedInStudent !== null): ?>
        <!-- Current student call to action -->
        <section class="border-t border-slate-200 bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="alumni-overview-student-cta-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="border border-slate-200 bg-white px-6 py-8 sm:px-10 sm:py-10 text-center sm:rounded-none">
                    <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                    </span>
                    <h2 id="alumni-overview-student-cta-heading" class="mt-5 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Students, get involved</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-slate-600">
                        Browse alumni profiles, read career discussions, and join the conversation — all without leaving the community.
                    </p>
                    <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Browse Career Discussions
                        </a>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Browse Alumni
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Guest call to action -->
        <section class="border-t border-slate-200 bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="alumni-overview-guest-cta-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="border border-slate-200 bg-white px-6 py-8 sm:px-10 sm:py-10 text-center sm:rounded-none">
                    <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    <h2 id="alumni-overview-guest-cta-heading" class="mt-5 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Join the community</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-slate-600">
                        Log in to join career discussions, reply to alumni insights and connect with the UCSMTLA community.
                    </p>
                    <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/student-login.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Login
                        </a>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Browse Alumni
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php
require_once '../includes/footer.php';
?>
