<?php
/**
 * Alumni Dashboard (protected).
 *
 * A personalised landing page for verified alumni of the Alumni & Career
 * Community. Uses the existing student session (no separate authentication).
 * For users without a verified profile the page resolves their alumni
 * community state: active (not graduated) students are sent to the public
 * overview, graduates without a profile see the "join the community" flow,
 * and graduates with a pending or rejected profile see the matching state
 * view.
 *
 * The verified-alumni dashboard brings together the alumnus's own content:
 * their alumni profile, discussions they started, their discussion count,
 * and the latest alumni stories.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/alumni-validation.php';

student_require_login();

$ucsStudent = student_current_user();

$ucsProfile = alumni_current_profile($pdo);

if ($ucsProfile === null) {
    // -----------------------------------------------------------------
    // Alumni-community state machine for users without a verified profile.
    //   - active (not graduated)  -> public overview (not an alumnus yet)
    //   - graduated, no profile   -> "Join the alumni community" flow
    //   - graduated, pending      -> "profile under review" view
    //   - graduated, rejected     -> "resubmit application" view
    // -----------------------------------------------------------------
    $ucsAcademicStatus = 'active';
    $ucsProfileStatus  = null;
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT s.student_status, ap.verification_status AS profile_status
             FROM students s
             LEFT JOIN alumni_profiles ap ON ap.student_id = s.id
             WHERE s.id = :id
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => (int) $ucsStudent['id']]);
        $ucsState = $ucsStmt->fetch() ?: null;
        if ($ucsState !== null) {
            $ucsAcademicStatus = (string) ($ucsState['student_status'] ?? 'active');
            $ucsProfileStatus  = $ucsState['profile_status'] ?? null;
        }
    } catch (PDOException $e) {
        $ucsAcademicStatus = 'active';
        $ucsProfileStatus  = null;
    }

    $pageTitle = 'Alumni Dashboard';

    if ($ucsAcademicStatus !== 'graduated') {
        header('Location: ' . BASE_URL . '/alumni-overview.php');
        exit;
    }

    $ucsDisplayName = (string) $ucsStudent['name'];

    require_once __DIR__ . '/../includes/header.php';
    ?>
    <main class="flex-1 bg-blue-50">
        <section class="py-12 sm:py-16" aria-labelledby="alumni-dashboard-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="relative overflow-hidden rounded-lg border border-blue-100 bg-white px-6 py-8 shadow-sm sm:px-8">
                    <span class="absolute inset-y-0 left-0 w-1 bg-blue-600" aria-hidden="true"></span>
                    <span class="pointer-events-none absolute -right-10 -top-12 h-32 w-32 rounded-full bg-blue-50" aria-hidden="true"></span>
                    <p class="relative text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">Alumni &amp; Career Community</p>
                    <h1 id="alumni-dashboard-heading" class="relative mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                        <?php echo htmlspecialchars($ucsDisplayName); ?>
                    </h1>

                    <?php if ($ucsProfileStatus === null): ?>
                        <div class="relative mt-6">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg>
                                Graduated
                            </span>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                Congratulations on your graduation from UCSMTLA! Your alumni profile is the key to the Alumni &amp; Career Community
                                &mdash; connect with fellow graduates, participate in career discussions and share your experience with current students.
                            </p>
                            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-join.php'); ?>"
                                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    Create Your Alumni Profile
                                </a>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-overview.php'); ?>"
                                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
                                    Learn About the Community
                                </a>
                            </div>
                        </div>

                    <?php elseif ($ucsProfileStatus === 'pending'): ?>
                        <div class="relative mt-6">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 8v4"></path>
                                    <path d="M12 16h.01"></path>
                                </svg>
                                Profile Under Review
                            </span>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                Your alumni profile has been submitted and is awaiting verification by the university office.
                                You will get full access to the alumni community once it is approved.
                            </p>
                            <div class="mt-6 flex flex-wrap gap-3">
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-overview.php'); ?>"
                                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
                                    Explore the Community
                                </a>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>"
                                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
                                    Browse Career Discussions
                                </a>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="relative mt-6">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                Profile Not Approved
                            </span>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                Your alumni profile application was not approved. You can review your details and resubmit for another review.
                            </p>
                            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-join.php'); ?>"
                                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    Resubmit Application
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle  = 'Alumni Dashboard';

$ucsFlash = $_SESSION['alumni_flash'] ?? null;
unset($_SESSION['alumni_flash']);

// ---------------------------------------------------------------------
// Identity + academic context.
// ---------------------------------------------------------------------
$ucsMajorName      = '';
$ucsGraduationYear = '';
try {
    $ucsStmt = $pdo->prepare(
        "SELECT s.name AS student_name, s.graduation_year,
                m.name AS major_name
         FROM students s
         JOIN classrooms cl ON cl.id = s.classroom_id
         JOIN majors m ON m.id = cl.major_id
         WHERE s.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => (int) $ucsStudent['id']]);
    $ucsIdentity = $ucsStmt->fetch() ?: null;

    if ($ucsIdentity !== null) {
        $ucsMajorName      = (string) ($ucsIdentity['major_name'] ?? '');
        $ucsGraduationYear = (string) ($ucsIdentity['graduation_year'] ?? '');
    }
} catch (PDOException $e) {
    $ucsMajorName      = '';
    $ucsGraduationYear = '';
}

// ---------------------------------------------------------------------
// Stats.
// ---------------------------------------------------------------------
$ucsDiscussionCount = 0;
try {
    $ucsStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM discussions
         WHERE author_student_id = :student_id AND status <> 'hidden'"
    );
    $ucsStmt->execute([':student_id' => (int) $ucsStudent['id']]);
    $ucsDiscussionCount = (int) $ucsStmt->fetchColumn();
} catch (PDOException $e) {
    $ucsDiscussionCount = 0;
}

// Profile completion (10 tracked fields, excluding identity/academic data).
$ucsProfileFields = [
    'current_job',
    'company',
    'professional_field',
    'skills',
    'bio',
    'career_journey',
    'profile_photo',
    'linkedin_url',
    'github_url',
    'website_url',
];
$ucsProfileFilled = 0;
foreach ($ucsProfileFields as $ucsField) {
    if (trim((string) ($ucsProfile[$ucsField] ?? '')) !== '') {
        $ucsProfileFilled++;
    }
}
$ucsProfileCompletion = (int) round(($ucsProfileFilled / count($ucsProfileFields)) * 100);

// ---------------------------------------------------------------------
// Section content.
// ---------------------------------------------------------------------
$ucsLatestStories = [];
try {
    $ucsStmt = $pdo->prepare(
        "SELECT st.id, st.title, st.summary, st.career_field, st.publication_date
         FROM alumni_stories st
         WHERE st.status = 'published'
           AND (st.publication_date IS NULL OR st.publication_date <= CURDATE())
         ORDER BY st.publication_date DESC, st.id DESC
         LIMIT 3"
    );
    $ucsStmt->execute();
    $ucsLatestStories = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsLatestStories = [];
}

// ---------------------------------------------------------------------
// My discussions list.
// ---------------------------------------------------------------------
$ucsMyDiscussions = [];
try {
    $ucsStmt = $pdo->prepare(
        "SELECT d.id, d.title, d.status, d.is_pinned, d.created_at,
                c.name AS category_name,
                (SELECT COUNT(*) FROM discussion_replies r
                 WHERE r.discussion_id = d.id AND r.status = 'visible') AS reply_count
         FROM discussions d
         JOIN discussion_categories c ON c.id = d.category_id
         WHERE d.author_student_id = :student_id AND d.status <> 'hidden'
         ORDER BY d.is_pinned DESC, d.created_at DESC
         LIMIT 20"
    );
    $ucsStmt->execute([':student_id' => (int) $ucsStudent['id']]);
    $ucsMyDiscussions = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsMyDiscussions = [];
}

$ucsDisplayName = $ucsStudent['name'] !== '' ? $ucsStudent['name'] : 'Alumnus';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-1 bg-blue-50">
    <section class="py-12 sm:py-16" aria-labelledby="alumni-dashboard-heading">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <?php if ($ucsFlash !== null): ?>
                <div class="mb-6 rounded-xl px-4 py-3 ring-1 <?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100' : 'bg-emerald-50 ring-emerald-100'; ?>" role="alert">
                    <p class="text-sm font-medium <?php echo $ucsFlash['type'] === 'error' ? 'text-red-800' : 'text-emerald-800'; ?>">
                        <?php echo htmlspecialchars((string) $ucsFlash['message']); ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Welcome -->
            <div class="relative overflow-hidden rounded-lg border border-blue-100 bg-white px-6 py-6 shadow-sm sm:px-8">
                <span class="absolute inset-y-0 left-0 w-1 bg-blue-600" aria-hidden="true"></span>
                <span class="pointer-events-none absolute -right-10 -top-12 h-32 w-32 rounded-full bg-blue-50" aria-hidden="true"></span>
                <p class="relative text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">Alumni &amp; Career Community</p>
                <h1 id="alumni-dashboard-heading" class="relative mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    <?php echo htmlspecialchars($ucsDisplayName); ?>
                </h1>
                <div class="relative mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        Verified Alumni
                    </span>
                    <?php if ($ucsMajorName !== ''): ?>
                        <span class="text-sm font-medium text-slate-600"><?php echo htmlspecialchars($ucsMajorName); ?></span>
                    <?php endif; ?>
                    <?php if ($ucsGraduationYear !== ''): ?>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">Class of <?php echo htmlspecialchars($ucsGraduationYear); ?></span>
                    <?php endif; ?>
                </div>
                <p class="relative mt-3 text-sm leading-6 text-slate-500">
                    Welcome back. Here&rsquo;s what&rsquo;s happening across your alumni community.
                </p>
            </div>

            <!-- Quick actions -->
            <section class="mt-10" aria-labelledby="quick-actions-heading">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 ring-4 ring-blue-100" aria-hidden="true"></span>
                    <h2 id="quick-actions-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-900">Quick Actions</h2>
                    <span class="h-px flex-1 bg-blue-100" aria-hidden="true"></span>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-edit.php'); ?>" class="group rounded-lg border border-blue-100 bg-white p-5 shadow-sm transition-colors duration-150 hover:border-blue-200 hover:bg-blue-50/50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 transition-colors duration-150 group-hover:bg-blue-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path><path d="M22 10v6"></path><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path></svg>
                        </span>
                        <span class="mt-3 block text-sm font-semibold text-slate-900 group-hover:text-blue-700">Edit Profile</span>
                        <span class="mt-0.5 block text-sm text-slate-500">Update your alumni profile details</span>
                    </a>

                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsProfile['id']); ?>" class="group rounded-lg border border-blue-100 bg-white p-5 shadow-sm transition-colors duration-150 hover:border-blue-200 hover:bg-blue-50/50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 transition-colors duration-150 group-hover:bg-emerald-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </span>
                        <span class="mt-3 block text-sm font-semibold text-slate-900 group-hover:text-blue-700">View Public Profile</span>
                        <span class="mt-0.5 block text-sm text-slate-500">See how others see you</span>
                    </a>

                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-story-create.php'); ?>" class="group rounded-lg border border-blue-100 bg-white p-5 shadow-sm transition-colors duration-150 hover:border-blue-200 hover:bg-blue-50/50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 transition-colors duration-150 group-hover:bg-indigo-500 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                        </span>
                        <span class="mt-3 block text-sm font-semibold text-slate-900 group-hover:text-blue-700">Share Your Story</span>
                        <span class="mt-0.5 block text-sm text-slate-500">Write about your career journey</span>
                    </a>

                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="group rounded-lg border border-blue-100 bg-white p-5 shadow-sm transition-colors duration-150 hover:border-blue-200 hover:bg-blue-50/50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-100 transition-colors duration-150 group-hover:bg-violet-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        </span>
                        <span class="mt-3 block text-sm font-semibold text-slate-900 group-hover:text-blue-700">Ask / Join Career Discussion</span>
                        <span class="mt-0.5 block text-sm text-slate-500">Share advice with students and alumni</span>
                    </a>
                </div>
            </section>

            <!-- Stats -->
            <section class="mt-10" aria-labelledby="community-stats-heading">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 ring-4 ring-blue-100" aria-hidden="true"></span>
                    <h2 id="community-stats-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-900">Community Overview</h2>
                    <span class="h-px flex-1 bg-blue-100" aria-hidden="true"></span>
                </div>

                <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-blue-100 bg-white px-5 py-5 shadow-sm">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Discussions Started</dt>
                        <dd class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900"><?php echo $ucsDiscussionCount; ?></dd>
                    </div>
                    <div class="rounded-lg border border-blue-100 bg-white px-5 py-5 shadow-sm">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Profile Completion</dt>
                        <dd class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900"><?php echo $ucsProfileCompletion; ?>%</dd>
                    </div>
                </dl>
            </section>

            <!-- Content columns -->
            <div class="mt-10 grid grid-cols-1 gap-10 lg:grid-cols-2">
                <!-- Left column -->
                <div class="space-y-10">
                    <!-- My Alumni Profile -->
                    <section aria-labelledby="my-alumni-profile-heading">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 ring-4 ring-blue-100" aria-hidden="true"></span>
                            <h2 id="my-alumni-profile-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-900">My Alumni Profile</h2>
                            <span class="h-px flex-1 bg-blue-100" aria-hidden="true"></span>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-edit.php'); ?>" class="shrink-0 text-xs font-semibold text-blue-700 transition-colors duration-150 hover:text-blue-800">Edit</a>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm">
                            <?php $ucsHasProfileContent = false; ?>
                            <?php if (!empty($ucsProfile['current_job']) || !empty($ucsProfile['company'])): ?>
                                <?php $ucsHasProfileContent = true; ?>
                                <div class="flex items-center justify-between gap-4 border-b border-blue-100 px-6 py-4">
                                    <span class="text-sm text-slate-500">Current role</span>
                                    <span class="text-right text-sm font-semibold text-slate-900">
                                        <?php echo htmlspecialchars((string) $ucsProfile['current_job']); ?>
                                        <?php if (!empty($ucsProfile['current_job']) && !empty($ucsProfile['company'])): ?><span class="font-normal text-slate-400"> at </span><?php endif; ?>
                                        <?php if (!empty($ucsProfile['company'])): ?><?php echo htmlspecialchars((string) $ucsProfile['company']); ?><?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="flex items-center justify-between gap-4 px-6 py-4 <?php echo $ucsHasProfileContent ? 'border-b border-blue-100' : ''; ?>">
                                <span class="text-sm text-slate-500">Visibility</span>
                                <span class="text-sm font-semibold text-slate-900"><?php echo (string) $ucsProfile['visibility'] === 'public' ? 'Public directory' : 'Private only'; ?></span>
                            </div>
                            <div class="border-t border-blue-100 px-6 py-4">
                                <p class="text-sm leading-6 text-slate-500">
                                    Your profile is
                                    <span class="font-semibold text-blue-700"><?php echo $ucsProfileCompletion; ?>%</span>
                                    complete. Complete your job, skills and links to help fellow alumni and students find you.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right column -->
                <div class="space-y-10">
                    <!-- My Discussions -->
                    <section aria-labelledby="my-discussions-heading">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 ring-4 ring-blue-100" aria-hidden="true"></span>
                            <h2 id="my-discussions-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-900">My Discussions</h2>
                            <span class="h-px flex-1 bg-blue-100" aria-hidden="true"></span>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="shrink-0 text-xs font-semibold text-blue-700 transition-colors duration-150 hover:text-blue-800">Browse all</a>
                        </div>

                        <?php if (empty($ucsMyDiscussions)): ?>
                            <div class="mt-4 rounded-lg border border-blue-100 bg-white p-8 text-center shadow-sm">
                                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-violet-500 ring-1 ring-violet-100" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                </span>
                                <p class="mt-3 text-sm font-medium text-slate-700">No discussions yet</p>
                                <p class="mt-1 text-sm text-slate-500">Start a career discussion to share your experience.</p>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussion-create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                                    Start a discussion
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 space-y-3">
                                <?php foreach ($ucsMyDiscussions as $ucsMyDisc): ?>
                                    <?php
                                    $ucsMyDiscUrl = BASE_URL . '/career-discussion-details.php?id=' . (int) $ucsMyDisc['id'];
                                    $ucsMyDiscStatus = (string) $ucsMyDisc['status'];
                                    $ucsIsPinned = (int) $ucsMyDisc['is_pinned'] === 1;
                                    $ucsReplyCount = (int) $ucsMyDisc['reply_count'];
                                    ?>
                                    <div class="group relative rounded-lg border border-blue-100 bg-white p-4 shadow-sm transition-all duration-150 hover:border-blue-200 hover:shadow-md sm:p-5 <?php echo $ucsIsPinned ? 'border-l-4 border-l-indigo-400' : ''; ?>">
                                        <a href="<?php echo htmlspecialchars($ucsMyDiscUrl); ?>" class="absolute inset-0 z-0" aria-hidden="true"></a>
                                        <div class="relative z-10">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0 flex-1">
                                                    <!-- Title row -->
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <?php if ($ucsIsPinned): ?>
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M12 17v5M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1z"></path>
                                                            </svg>
                                                        <?php endif; ?>
                                                        <h3 class="truncate text-sm font-bold text-slate-900 group-hover:text-blue-700 transition-colors sm:text-base">
                                                            <?php echo htmlspecialchars((string) $ucsMyDisc['title']); ?>
                                                        </h3>
                                                    </div>

                                                    <!-- Meta row -->
                                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-bold text-blue-700 ring-1 ring-blue-100/80"><?php echo htmlspecialchars((string) $ucsMyDisc['category_name']); ?></span>
                                                        <?php if ($ucsMyDiscStatus === 'open'): ?>
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100/80">
                                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                                Open
                                                            </span>
                                                        <?php elseif ($ucsMyDiscStatus === 'closed'): ?>
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-500">
                                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                                Closed
                                                            </span>
                                                        <?php endif; ?>
                                                        <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                                            <?php echo $ucsReplyCount; ?> <?php echo $ucsReplyCount === 1 ? 'reply' : 'replies'; ?>
                                                        </span>
                                                        <span class="text-xs text-slate-400"><?php echo htmlspecialchars(date('M j, Y', strtotime((string) $ucsMyDisc['created_at']))); ?></span>
                                                    </div>
                                                </div>

                                                <!-- Actions -->
                                                <div class="relative z-10 flex shrink-0 items-center gap-2">
                                                    <a href="<?php echo htmlspecialchars($ucsMyDiscUrl); ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700" onclick="event.stopPropagation();">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                        View
                                                    </a>
                                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussion-details.php?id=' . (int) $ucsMyDisc['id'] . '#reply-form'); ?>" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-blue-700" onclick="event.stopPropagation();">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                                        Reply
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($ucsDiscussionCount > 20): ?>
                                <div class="mt-4 text-center">
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-700 transition-colors hover:text-blue-800">
                                        View all <?php echo $ucsDiscussionCount; ?> discussions
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </section>

                    <!-- Latest Alumni Stories -->
                    <section aria-labelledby="latest-stories-heading">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 ring-4 ring-blue-100" aria-hidden="true"></span>
                            <h2 id="latest-stories-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-900">Latest Alumni Stories</h2>
                            <span class="h-px flex-1 bg-blue-100" aria-hidden="true"></span>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-stories.php'); ?>" class="shrink-0 text-xs font-semibold text-blue-700 transition-colors duration-150 hover:text-blue-800">All stories</a>
                        </div>

                        <?php if (empty($ucsLatestStories)): ?>
                            <div class="mt-4 rounded-lg border border-blue-100 bg-white px-6 py-8 text-center shadow-sm">
                                <p class="text-sm leading-6 text-slate-500">No alumni stories have been published yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm">
                                <ul class="divide-y divide-blue-100">
                                    <?php foreach ($ucsLatestStories as $ucsStory): ?>
                                        <li>
                                            <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-story-details.php?id=' . (int) $ucsStory['id']); ?>" class="group flex items-center gap-4 px-6 py-4 transition-colors duration-150 hover:bg-blue-50/60 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-semibold text-slate-900 group-hover:text-blue-700"><?php echo htmlspecialchars((string) $ucsStory['title']); ?></span>
                                                    <?php if (!empty($ucsStory['career_field'])): ?>
                                                        <span class="mt-0.5 block truncate text-sm text-slate-500"><?php echo htmlspecialchars((string) $ucsStory['career_field']); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-blue-200 transition-all duration-150 group-hover:translate-x-1 group-hover:text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"></path></svg>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>