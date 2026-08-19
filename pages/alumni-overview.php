<?php
/**
 * Public Alumni Overview hub page.
 *
 * The entry point to the Alumni & Career Community. It cross-links every
 * alumni feature (Directory, Stories, Career Opportunities, Career
 * Discussions, Mentorship, Alumni Events) so visitors, current students and
 * verified alumni can all find their way around from one place.
 *
 * The page stays within the established public site design and only ever
 * queries public, non-sensitive data.
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
$ucsStats = ['alumni' => 0, 'opportunities' => 0, 'discussions' => 0, 'stories' => 0];
try {
    $ucsStats['alumni'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM alumni_profiles
         WHERE verification_status = 'verified' AND visibility = 'public'"
    )->fetchColumn();
} catch (PDOException $e) {
}
try {
    $ucsStats['opportunities'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM career_opportunities
         WHERE status = 'active' AND (expires_at IS NULL OR expires_at >= CURDATE())"
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
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="alumni-overview-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Alumni &amp; Career Community</p>
                <h1 id="alumni-overview-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Alumni Overview</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    UCSMTLA graduates stay connected through the directory, career opportunities, discussions,
                    mentorship and events that keep our community thriving.
                </p>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="border-b border-gray-100 bg-white" aria-label="Alumni community statistics">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
            <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-2xl bg-gray-100 ring-1 ring-gray-200 lg:grid-cols-4">
                <div class="bg-white px-6 py-6 text-center">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Alumni</dt>
                    <dd class="mt-2 text-3xl font-extrabold tracking-tight text-blue-600"><?php echo (int) $ucsStats['alumni']; ?></dd>
                </div>
                <div class="bg-white px-6 py-6 text-center">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Career Opportunities</dt>
                    <dd class="mt-2 text-3xl font-extrabold tracking-tight text-blue-600"><?php echo (int) $ucsStats['opportunities']; ?></dd>
                </div>
                <div class="bg-white px-6 py-6 text-center">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Career Discussions</dt>
                    <dd class="mt-2 text-3xl font-extrabold tracking-tight text-blue-600"><?php echo (int) $ucsStats['discussions']; ?></dd>
                </div>
                <div class="bg-white px-6 py-6 text-center">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Alumni Stories</dt>
                    <dd class="mt-2 text-3xl font-extrabold tracking-tight text-blue-600"><?php echo (int) $ucsStats['stories']; ?></dd>
                </div>
            </dl>
        </div>
    </section>

    <!-- Community features -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="alumni-community-features-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Explore</p>
                <h2 id="alumni-community-features-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Ways to Connect</h2>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-gray-600">
                    Browse the community, share your experience, or find guidance — everything is open to students and alumni alike.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Alumni Directory -->
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="group flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 transition-colors duration-150 group-hover:bg-blue-600 group-hover:text-white" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-base font-bold text-gray-900 group-hover:text-blue-700">Alumni Directory</h3>
                    <p class="mt-2 flex-1 text-sm leading-6 text-gray-600">
                        Search and browse verified UCSMTLA graduates by major, graduation year and profession.
                    </p>
                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600">
                        Browse the directory
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </span>
                </a>

                <!-- Alumni Stories -->
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-stories.php'); ?>" class="group flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 transition-colors duration-150 group-hover:bg-indigo-600 group-hover:text-white" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-base font-bold text-gray-900 group-hover:text-indigo-700">Alumni Stories</h3>
                    <p class="mt-2 flex-1 text-sm leading-6 text-gray-600">
                        Read the journeys, achievements and advice of graduates across industries.
                    </p>
                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600">
                        Read the stories
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </span>
                </a>

                <!-- Career Opportunities -->
                <a href="<?php echo htmlspecialchars(BASE_URL . '/career-opportunities.php'); ?>" class="group flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 transition-colors duration-150 group-hover:bg-emerald-600 group-hover:text-white" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
                            <path d="M22 10v6"></path>
                            <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-base font-bold text-gray-900 group-hover:text-emerald-700">Career Opportunities</h3>
                    <p class="mt-2 flex-1 text-sm leading-6 text-gray-600">
                        Jobs, internships and freelance openings shared by verified alumni.
                    </p>
                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600">
                        Browse opportunities
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </span>
                </a>

                <!-- Career Discussions -->
                <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="group flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-100 transition-colors duration-150 group-hover:bg-violet-600 group-hover:text-white" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-base font-bold text-gray-900 group-hover:text-violet-700">Career Discussions</h3>
                    <p class="mt-2 flex-1 text-sm leading-6 text-gray-600">
                        Ask career questions and get advice from students and verified alumni.
                    </p>
                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-violet-600">
                        Join the conversation
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </span>
                </a>

                <!-- Mentorship -->
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-mentorship.php'); ?>" class="group flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 ring-1 ring-cyan-100 transition-colors duration-150 group-hover:bg-cyan-600 group-hover:text-white" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-base font-bold text-gray-900 group-hover:text-cyan-700">Mentorship</h3>
                    <p class="mt-2 flex-1 text-sm leading-6 text-gray-600">
                        Request one-on-one career guidance from alumni who are open to mentoring.
                    </p>
                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-cyan-600">
                        Find a mentor
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </span>
                </a>

                <!-- Alumni Events -->
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-events.php'); ?>" class="group flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100 transition-colors duration-150 group-hover:bg-amber-600 group-hover:text-white" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-base font-bold text-gray-900 group-hover:text-amber-700">Alumni Events</h3>
                    <p class="mt-2 flex-1 text-sm leading-6 text-gray-600">
                        Webinars, workshops, networking nights and career fairs hosted by UCSMTLA.
                    </p>
                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-amber-600">
                        View the calendar
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </span>
                </a>
            </div>
        </div>
    </section>

    <?php if ($ucsIsVerifiedAlumnus): ?>
        <!-- Verified alumni call to action -->
        <section class="bg-white py-16 sm:py-20" aria-labelledby="alumni-overview-cta-heading">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
                <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>
                </span>
                <h2 id="alumni-overview-cta-heading" class="mt-6 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">Welcome back, alumnus</h2>
                <p class="mx-auto mt-3 max-w-2xl text-base leading-7 text-gray-600">
                    Manage your profile, share career opportunities, answer student questions and track your mentorship from your alumni dashboard.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Open Alumni Dashboard
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </a>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/opportunity-create.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Share a Career Opportunity
                    </a>
                </div>
            </div>
        </section>
    <?php elseif ($ucsLoggedInStudent !== null): ?>
        <!-- Current student call to action -->
        <section class="bg-white py-16 sm:py-20" aria-labelledby="alumni-overview-student-cta-heading">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
                <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                </span>
                <h2 id="alumni-overview-student-cta-heading" class="mt-6 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">Students, get involved</h2>
                <p class="mx-auto mt-3 max-w-2xl text-base leading-7 text-gray-600">
                    Browse alumni profiles and opportunities, ask career questions, and request mentorship — all without leaving the community.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/mentorship-request.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Find a Mentor
                    </a>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussion-create.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Ask a Career Question
                    </a>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Guest call to action -->
        <section class="bg-white py-16 sm:py-20" aria-labelledby="alumni-overview-guest-cta-heading">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
                <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </span>
                <h2 id="alumni-overview-guest-cta-heading" class="mt-6 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">Join the community</h2>
                <p class="mx-auto mt-3 max-w-2xl text-base leading-7 text-gray-600">
                    Log in to ask career questions, request mentorship, and share your experience as a verified UCSMTLA alumnus.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/student-login.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Student Login
                    </a>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Browse Alumni
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php
require_once '../includes/footer.php';
?>
