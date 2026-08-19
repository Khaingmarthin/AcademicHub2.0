<?php
/**
 * Student Dashboard (protected).
 *
 * Greets the authenticated student and shows their academic summary from the
 * students table joined with their classroom, major and academic year.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';

student_require_login();

$pageTitle = 'Student Dashboard';

$ucsStudent = student_current_user();
$ucsDetails = null;

try {
    $ucsStmt = $pdo->prepare(
        "SELECT s.student_id, s.name, s.email, s.status,
                s.student_status, s.graduation_year,
                c.classroom_name, c.year_level, c.section,
                c.academic_year_id AS classroom_academic_year_id,
                c.major_id AS classroom_major_id,
                m.name AS major_name,
                ay.year_name AS academic_year
         FROM students s
         JOIN classrooms c ON c.id = s.classroom_id
         JOIN majors m ON m.id = c.major_id
         JOIN academic_years ay ON ay.id = c.academic_year_id
         WHERE s.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsStudent['id']]);
    $ucsDetails = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsDetails = null;
}

$ucsCourses = [];
if ($ucsDetails !== null) {
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT course_code, course_name
             FROM courses
             WHERE academic_year_id = :academic_year_id
               AND major_id = :major_id
               AND year_level = :year_level
               AND status = TRUE
             ORDER BY course_code ASC"
        );
        $ucsStmt->execute([
            ':academic_year_id' => $ucsDetails['classroom_academic_year_id'],
            ':major_id'         => $ucsDetails['classroom_major_id'],
            ':year_level'       => $ucsDetails['year_level'],
        ]);
        $ucsCourses = $ucsStmt->fetchAll();
    } catch (PDOException $e) {
        $ucsCourses = [];
    }
}

$ucsCourseContextParts = [];
if ($ucsDetails !== null) {
    if (!empty($ucsDetails['year_level'])) {
        $ucsCourseContextParts[] = $ucsDetails['year_level'];
    }
    if (!empty($ucsDetails['major_name'])) {
        $ucsCourseContextParts[] = $ucsDetails['major_name'];
    }
    if (!empty($ucsDetails['section'])) {
        $ucsCourseContextParts[] = 'Section ' . $ucsDetails['section'];
    }
}
$ucsCourseContext = implode(' • ', $ucsCourseContextParts);

// Verified alumni get a link to manage their alumni profile.
$ucsAlumniProfile = null;
try {
    $ucsStmt = $pdo->prepare(
        "SELECT id
         FROM alumni_profiles
         WHERE student_id = :id
           AND verification_status = 'verified'
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsStudent['id']]);
    $ucsAlumniProfile = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsAlumniProfile = null;
}

$ucsGreeting = 'Good ';
$ucsHour = (int) date('G');
if ($ucsHour < 12) {
    $ucsGreeting .= 'morning';
} elseif ($ucsHour < 17) {
    $ucsGreeting .= 'afternoon';
} else {
    $ucsGreeting .= 'evening';
}
$ucsDisplayName = $ucsStudent['name'] !== '' ? $ucsStudent['name'] : 'Student';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-1 bg-blue-50">
    <section class="py-12 sm:py-16" aria-labelledby="student-dashboard-heading">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <!-- Welcome -->
            <div class="relative overflow-hidden rounded-2xl border border-blue-100 bg-white px-6 py-6 shadow-sm sm:px-8">
                <span class="absolute inset-y-0 left-0 w-1 bg-blue-600" aria-hidden="true"></span>
                <span class="pointer-events-none absolute -right-10 -top-12 h-32 w-32 rounded-full bg-blue-50" aria-hidden="true"></span>
                <p class="relative text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">Student Portal</p>
                <h1 id="student-dashboard-heading" class="relative mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    <?php echo htmlspecialchars($ucsGreeting . ', ' . $ucsDisplayName); ?>
                </h1>
                <p class="relative mt-2 text-sm leading-6 text-slate-500">
                    Here&rsquo;s an overview of your academic information.
                </p>
            </div>

            <!-- Academic overview -->
            <section class="mt-10" aria-labelledby="academic-overview-heading">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 ring-4 ring-blue-100" aria-hidden="true"></span>
                    <h2 id="academic-overview-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-900">Academic Overview</h2>
                    <span class="h-px flex-1 bg-blue-100" aria-hidden="true"></span>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
                    <?php if ($ucsDetails !== null): ?>
                        <dl class="grid grid-cols-1 gap-px bg-blue-100 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Student ID</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-sm font-bold text-blue-700 ring-1 ring-blue-100"><?php echo htmlspecialchars($ucsDetails['student_id']); ?></span>
                                </dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Name</dt>
                                <dd class="mt-1 break-all text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['name']); ?></dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Programme</dt>
                                <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['major_name']); ?></dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Year Level</dt>
                                <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['year_level']); ?></dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Section</dt>
                                <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['section'] !== '' && $ucsDetails['section'] !== null ? $ucsDetails['section'] : '—'); ?></dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Classroom</dt>
                                <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['classroom_name']); ?></dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Academic Status</dt>
                                <dd class="mt-1">
                                    <?php if (($ucsDetails['student_status'] ?? '') === 'graduated'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <path d="m9 11 3 3L22 4"></path>
                                            </svg>
                                            Graduated<?php echo !empty($ucsDetails['graduation_year']) ? ' &middot; Class of ' . htmlspecialchars((string) $ucsDetails['graduation_year']) : ''; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-sm font-bold text-blue-700 ring-1 ring-blue-100">Active</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        </dl>
                    <?php else: ?>
                        <div class="bg-white px-6 py-8 sm:px-8">
                            <p class="text-sm leading-6 text-slate-500">
                                Your academic details could not be loaded at this time. Please try again later.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Quick access -->
            <section class="mt-10" aria-labelledby="quick-access-heading">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 ring-4 ring-blue-100" aria-hidden="true"></span>
                    <h2 id="quick-access-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-900">Quick Access</h2>
                    <span class="h-px flex-1 bg-blue-100" aria-hidden="true"></span>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/student-profile.php'); ?>" class="group flex items-center gap-4 px-5 py-4 transition-colors duration-150 hover:bg-blue-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 transition-colors duration-150 group-hover:bg-blue-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-blue-700">My Profile</span>
                            <span class="mt-0.5 block text-sm text-slate-500">View your student details</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-blue-200 transition-all duration-150 group-hover:translate-x-1 group-hover:text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>

                    <a href="<?php echo htmlspecialchars(BASE_URL . '/student-timetable.php'); ?>" class="group flex items-center gap-4 border-t border-blue-100 px-5 py-4 transition-colors duration-150 hover:bg-blue-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 transition-colors duration-150 group-hover:bg-blue-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 2v4M16 2v4M3 10h18"></path>
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-blue-700">My Timetable</span>
                            <span class="mt-0.5 block text-sm text-slate-500">View your class timetable</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-blue-200 transition-all duration-150 group-hover:translate-x-1 group-hover:text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>

                    <a href="<?php echo htmlspecialchars(BASE_URL . '/my-mentorship.php'); ?>" class="group flex items-center gap-4 border-t border-blue-100 px-5 py-4 transition-colors duration-150 hover:bg-blue-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-100 transition-colors duration-150 group-hover:bg-violet-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-violet-700">My Mentorship</span>
                            <span class="mt-0.5 block text-sm text-slate-500">Track requests and career mentors</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-violet-200 transition-all duration-150 group-hover:translate-x-1 group-hover:text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>

                    <!-- Career community (available to every current student) -->
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-opportunities.php'); ?>" class="group flex items-center gap-4 border-t border-blue-100 px-5 py-4 transition-colors duration-150 hover:bg-blue-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 transition-colors duration-150 group-hover:bg-emerald-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
                                <path d="M22 10v6"></path>
                                <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-emerald-700">Career Opportunities</span>
                            <span class="mt-0.5 block text-sm text-slate-500">Jobs and internships shared by alumni</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-200 transition-all duration-150 group-hover:translate-x-1 group-hover:text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>

                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="group flex items-center gap-4 border-t border-blue-100 px-5 py-4 transition-colors duration-150 hover:bg-blue-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 ring-1 ring-cyan-100 transition-colors duration-150 group-hover:bg-cyan-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-cyan-700">Career Discussions</span>
                            <span class="mt-0.5 block text-sm text-slate-500">Ask questions and share advice</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-cyan-200 transition-all duration-150 group-hover:translate-x-1 group-hover:text-cyan-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>

                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-events.php'); ?>" class="group flex items-center gap-4 border-t border-blue-100 px-5 py-4 transition-colors duration-150 hover:bg-blue-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100 transition-colors duration-150 group-hover:bg-amber-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 2v4M16 2v4M3 10h18"></path>
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-amber-700">Alumni Events</span>
                            <span class="mt-0.5 block text-sm text-slate-500">Webinars, workshops and networking nights</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-amber-200 transition-all duration-150 group-hover:translate-x-1 group-hover:text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>

                    <?php if ($ucsAlumniProfile !== null): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsAlumniProfile['id']); ?>" class="group flex items-center gap-4 border-t border-blue-100 px-5 py-4 transition-colors duration-150 hover:bg-blue-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 transition-colors duration-150 group-hover:bg-emerald-600 group-hover:text-white" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
                                    <path d="M22 10v6"></path>
                                    <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-900 group-hover:text-emerald-700">My Alumni Profile</span>
                                <span class="mt-0.5 block text-sm text-slate-500">View and manage your alumni profile</span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-200 transition-all duration-150 group-hover:translate-x-1 group-hover:text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 18l6-6-6-6"></path>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php if ($ucsAlumniProfile !== null): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="group flex items-center gap-4 border-t border-blue-100 px-5 py-4 transition-colors duration-150 hover:bg-blue-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 ring-1 ring-cyan-100 transition-colors duration-150 group-hover:bg-cyan-600 group-hover:text-white" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 11h.01M15 11h.01"></path>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-900 group-hover:text-cyan-700">Alumni Dashboard</span>
                                <span class="mt-0.5 block text-sm text-slate-500">Community overview, opportunities and events</span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-cyan-200 transition-all duration-150 group-hover:translate-x-1 group-hover:text-cyan-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 18l6-6-6-6"></path>
                            </svg>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/actions/student/logout.php'); ?>" class="group flex items-center gap-4 border-t border-blue-100 px-5 py-4 transition-colors duration-150 hover:bg-red-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100 transition-colors duration-150 group-hover:bg-red-100 group-hover:text-red-700" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-red-700">Logout</span>
                            <span class="mt-0.5 block text-sm text-slate-500">End your current session</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-blue-200 transition-all duration-150 group-hover:translate-x-1 group-hover:text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>
                </div>
            </section>

            <!-- My courses -->
            <section class="mt-10" aria-labelledby="my-courses-heading">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 ring-4 ring-blue-100" aria-hidden="true"></span>
                    <h2 id="my-courses-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-900">My Courses</h2>
                    <span class="h-px flex-1 bg-blue-100" aria-hidden="true"></span>
                    <?php if ($ucsCourses): ?>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-100">
                            <?php echo count($ucsCourses); ?> Courses
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($ucsDetails !== null): ?>
                    <p class="mt-3 text-sm leading-6 text-slate-500">
                        Courses for your current class and academic year.
                        <?php if ($ucsCourseContext !== ''): ?>
                            <span class="font-medium text-slate-700"><?php echo htmlspecialchars($ucsCourseContext); ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if ($ucsCourses): ?>
                    <div class="mt-5 overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
                        <div class="hidden border-b border-blue-100 bg-blue-50/60 px-6 py-3 sm:flex sm:items-center sm:gap-6">
                            <span class="w-40 shrink-0 text-xs font-bold uppercase tracking-wider text-blue-700">Course Code</span>
                            <span class="flex-1 text-xs font-bold uppercase tracking-wider text-blue-700">Course Name</span>
                        </div>
                        <ul class="divide-y divide-blue-100">
                            <?php foreach ($ucsCourses as $ucsCourse): ?>
                                <li class="flex flex-col gap-0.5 px-6 py-4 transition-colors duration-150 hover:bg-blue-50/60 sm:flex-row sm:items-center sm:gap-6">
                                    <span class="w-40 shrink-0 text-sm font-bold text-blue-700"><?php echo htmlspecialchars($ucsCourse['course_code']); ?></span>
                                    <span class="flex-1 text-sm font-medium text-slate-800"><?php echo htmlspecialchars($ucsCourse['course_name']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="mt-5 rounded-2xl border border-blue-100 bg-white px-6 py-10 text-center shadow-sm">
                        <p class="text-sm leading-6 text-slate-500">No courses are currently available for your class.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>