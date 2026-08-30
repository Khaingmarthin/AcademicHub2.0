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

$ucsNotifications = [];
$ucsUnreadCount = 0;
require_once __DIR__ . '/../includes/helpers/notification-helper.php';
if ($ucsStudent) {
    $ucsUnreadCount = ucs_unread_notification_count($pdo, $ucsStudent['id']);
    $ucsNotifications = ucs_fetch_notifications($pdo, $ucsStudent['id'], 10);
}

try {
    $ucsStmt = $pdo->prepare(
        "SELECT s.student_id, s.name, s.email, s.status,
                s.student_status, s.graduation_year,
                c.classroom_name, c.year_level, c.section,
                c.academic_year_id AS classroom_academic_year_id,
                c.major_id AS classroom_major_id,
                c.id AS classroom_id,
                m.name AS major_name,
                ay.year_name AS academic_year
         FROM students s
         JOIN classrooms c ON c.id = s.classroom_id
         LEFT JOIN majors m ON m.id = c.major_id
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
            "SELECT c.course_code, c.course_name,
                    t.name AS teacher_name, tca.semester
             FROM courses c
             LEFT JOIN teacher_course_assignments tca
                ON tca.course_id = c.id
                AND tca.classroom_id = :classroom_id
             LEFT JOIN teachers t ON t.id = tca.teacher_id
             WHERE c.academic_year_id = :academic_year_id
               AND c.major_id = :major_id
               AND c.year_level = :year_level
               AND c.status = TRUE
             ORDER BY c.course_code ASC"
        );
        $ucsStmt->execute([
            ':academic_year_id' => $ucsDetails['classroom_academic_year_id'],
            ':major_id'         => $ucsDetails['classroom_major_id'],
            ':year_level'       => $ucsDetails['year_level'],
            ':classroom_id'     => $ucsDetails['classroom_id'],
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
$ucsCourseContext = implode(' \u2022 ', $ucsCourseContextParts);

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
<main class="flex-1 bg-slate-50">
    <section class="py-12 sm:py-16" aria-labelledby="student-dashboard-heading">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            <!-- Welcome -->
            <div class="relative rounded-lg border border-slate-200 bg-white px-6 py-6 sm:px-8">
                <!-- Notification bell -->
                <div class="absolute right-4 top-4 sm:right-6 sm:top-6">
                    <button type="button" id="notification-toggle" class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-300" aria-label="Notifications" aria-expanded="false" aria-controls="notification-dropdown">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <?php if ($ucsUnreadCount > 0): ?>
                            <span id="notification-badge" class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[0.625rem] font-bold text-white"><?php echo $ucsUnreadCount > 99 ? '99+' : $ucsUnreadCount; ?></span>
                        <?php else: ?>
                            <span id="notification-badge" class="hidden absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[0.625rem] font-bold text-white">0</span>
                        <?php endif; ?>
                    </button>
                    <div id="notification-dropdown" class="hidden absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg sm:w-96">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-900">Notifications</span>
                            <?php if ($ucsUnreadCount > 0): ?>
                                <button type="button" id="mark-all-read" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Mark all as read</button>
                            <?php endif; ?>
                        </div>
                        <ul id="notification-list" class="max-h-80 overflow-y-auto">
                            <?php if (empty($ucsNotifications)): ?>
                                <li class="px-4 py-8 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-slate-500">No notifications yet.</p>
                                </li>
                            <?php else: ?>
                                <?php foreach ($ucsNotifications as $ucsNotif): ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($ucsNotif['link']); ?>" class="flex gap-3 px-4 py-3 transition-colors duration-150 hover:bg-slate-50<?php echo (int) $ucsNotif['is_read'] === 0 ? ' bg-blue-50/50' : ''; ?>">
                                            <span class="mt-0.5 shrink-0">
                                                <?php if ($ucsNotif['type'] === 'news'): ?>
                                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path><path d="M18 14h-8"></path><path d="M15 18h-5"></path><path d="M10 6h8v4h-8V6Z"></path></svg>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-medium text-slate-900 <?php echo (int) $ucsNotif['is_read'] === 0 ? 'font-semibold' : ''; ?>"><?php echo htmlspecialchars($ucsNotif['title']); ?></span>
                                                <span class="mt-0.5 block text-xs text-slate-500 line-clamp-2"><?php echo htmlspecialchars($ucsNotif['message']); ?></span>
                                                <span class="mt-1 block text-xs text-slate-400"><?php echo htmlspecialchars($ucsNotif['created_at']); ?></span>
                                            </span>
                                            <?php if ((int) $ucsNotif['is_read'] === 0): ?>
                                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Student Portal</p>
                <h1 id="student-dashboard-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    <?php echo htmlspecialchars($ucsGreeting . ', ' . $ucsDisplayName); ?>
                </h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Here&rsquo;s an overview of your academic information.
                </p>
            </div>

            <!-- Academic overview -->
            <section class="mt-10" aria-labelledby="academic-overview-heading">
                <h2 id="academic-overview-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Academic Overview</h2>

                <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <?php if ($ucsDetails !== null): ?>
                        <dl class="grid grid-cols-1 gap-px bg-slate-200 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Student ID</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-sm font-bold text-slate-700"><?php echo htmlspecialchars($ucsDetails['student_id']); ?></span>
                                </dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Name</dt>
                                <dd class="mt-1 break-all text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['name']); ?></dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Programme</dt>
                                <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['major_name'] ?? ''); ?></dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Year Level</dt>
                                <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['year_level']); ?></dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Section</dt>
                                <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['section'] !== '' && $ucsDetails['section'] !== null ? $ucsDetails['section'] : '\u2014'); ?></dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Classroom</dt>
                                <dd class="mt-1 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsDetails['classroom_name']); ?></dd>
                            </div>
                            <div class="bg-white px-6 py-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Academic Status</dt>
                                <dd class="mt-1">
                                    <?php if (($ucsDetails['student_status'] ?? '') === 'graduated'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <path d="m9 11 3 3L22 4"></path>
                                            </svg>
                                            Graduated<?php echo !empty($ucsDetails['graduation_year']) ? ' \u00b7 Class of ' . htmlspecialchars((string) $ucsDetails['graduation_year']) : ''; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-sm font-bold text-blue-700 ring-1 ring-blue-200">Active</span>
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
                <h2 id="quick-access-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Quick Access</h2>

                <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/student-profile.php'); ?>" class="group flex items-center gap-4 px-5 py-4 transition-colors duration-150 hover:bg-slate-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors duration-150 group-hover:bg-blue-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-blue-700">My Profile</span>
                            <span class="mt-0.5 block text-sm text-slate-500">View your student details</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-300 transition-all duration-150 group-hover:translate-x-0.5 group-hover:text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>

                    <?php if (($ucsDetails['student_status'] ?? '') !== 'graduated'): ?>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/student-timetable.php'); ?>" class="group flex items-center gap-4 border-t border-slate-200 px-5 py-4 transition-colors duration-150 hover:bg-slate-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors duration-150 group-hover:bg-blue-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 2v4M16 2v4M3 10h18"></path>
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-blue-700">My Timetable</span>
                            <span class="mt-0.5 block text-sm text-slate-500">View your class timetable</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-300 transition-all duration-150 group-hover:translate-x-0.5 group-hover:text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>
                    <?php endif; ?>

                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="group flex items-center gap-4 border-t border-slate-200 px-5 py-4 transition-colors duration-150 hover:bg-slate-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors duration-150 group-hover:bg-blue-600 group-hover:text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-blue-700">Career Discussions</span>
                            <span class="mt-0.5 block text-sm text-slate-500">Ask questions and share advice</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-300 transition-all duration-150 group-hover:translate-x-0.5 group-hover:text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>

                    <?php if ($ucsAlumniProfile !== null): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsAlumniProfile['id']); ?>" class="group flex items-center gap-4 border-t border-slate-200 px-5 py-4 transition-colors duration-150 hover:bg-slate-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors duration-150 group-hover:bg-blue-600 group-hover:text-white" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
                                    <path d="M22 10v6"></path>
                                    <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-900 group-hover:text-blue-700">My Alumni Profile</span>
                                <span class="mt-0.5 block text-sm text-slate-500">View and manage your alumni profile</span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-300 transition-all duration-150 group-hover:translate-x-0.5 group-hover:text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 18l6-6-6-6"></path>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php if ($ucsAlumniProfile !== null): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="group flex items-center gap-4 border-t border-slate-200 px-5 py-4 transition-colors duration-150 hover:bg-slate-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors duration-150 group-hover:bg-blue-600 group-hover:text-white" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 11h.01M15 11h.01"></path>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-900 group-hover:text-blue-700">Alumni Dashboard</span>
                                <span class="mt-0.5 block text-sm text-slate-500">Community overview, opportunities and events</span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-300 transition-all duration-150 group-hover:translate-x-0.5 group-hover:text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 18l6-6-6-6"></path>
                            </svg>
                        </a>
                    <?php elseif (($ucsDetails['student_status'] ?? '') === 'graduated'): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-join.php'); ?>" class="group flex items-center gap-4 border-t border-slate-200 px-5 py-4 transition-colors duration-150 hover:bg-slate-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors duration-150 group-hover:bg-blue-600 group-hover:text-white" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <line x1="19" y1="8" x2="19" y2="14"></line>
                                    <line x1="22" y1="11" x2="16" y2="11"></line>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-900 group-hover:text-blue-700">Join Alumni Community</span>
                                <span class="mt-0.5 block text-sm text-slate-500">Apply to create your alumni profile</span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-300 transition-all duration-150 group-hover:translate-x-0.5 group-hover:text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 18l6-6-6-6"></path>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/actions/student/logout.php'); ?>" class="group flex items-center gap-4 border-t border-slate-200 px-5 py-4 transition-colors duration-150 hover:bg-red-50 sm:px-6 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 transition-colors duration-150 group-hover:bg-red-100 group-hover:text-red-700" aria-hidden="true">
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-300 transition-all duration-150 group-hover:translate-x-0.5 group-hover:text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>
                </div>
            </section>

            <!-- My courses -->
            <?php if (($ucsDetails['student_status'] ?? '') !== 'graduated'): ?>
            <section class="mt-10" aria-labelledby="my-courses-heading">
                <div class="flex items-center justify-between">
                    <h2 id="my-courses-heading" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">My Courses</h2>
                    <?php if ($ucsCourses): ?>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
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
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <div class="hidden border-b border-slate-200 bg-slate-50 px-6 py-3 sm:flex sm:items-center sm:gap-6">
                            <span class="w-36 shrink-0 text-xs font-bold uppercase tracking-wider text-slate-500">Course Code</span>
                            <span class="flex-1 text-xs font-bold uppercase tracking-wider text-slate-500">Course Name</span>
                            <span class="w-48 text-xs font-bold uppercase tracking-wider text-slate-500">Teacher</span>
                            <span class="w-36 text-xs font-bold uppercase tracking-wider text-slate-500">Semester</span>
                        </div>
                        <ul class="divide-y divide-slate-200">
                            <?php foreach ($ucsCourses as $ucsCourse): ?>
                                <li class="flex flex-col gap-0.5 px-6 py-4 transition-colors duration-150 hover:bg-slate-50 sm:flex-row sm:items-center sm:gap-6">
                                    <span class="w-36 shrink-0 text-sm font-bold text-blue-600"><?php echo htmlspecialchars($ucsCourse['course_code']); ?></span>
                                    <span class="flex-1 text-sm font-medium text-slate-800"><?php echo htmlspecialchars($ucsCourse['course_name']); ?></span>
                                    <span class="w-48 text-sm text-slate-600"><?php echo htmlspecialchars($ucsCourse['teacher_name'] ?? '—'); ?></span>
                                    <span class="w-36 text-sm text-slate-500"><?php echo htmlspecialchars($ucsCourse['semester'] ?? '—'); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="mt-4 rounded-lg border border-slate-200 bg-white px-6 py-10 text-center">
                        <p class="text-sm leading-6 text-slate-500">No courses are currently available for your class.</p>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
(function() {
    var toggle = document.getElementById('notification-toggle');
    var dropdown = document.getElementById('notification-dropdown');
    var markAllBtn = document.getElementById('mark-all-read');
    var badge = document.getElementById('notification-badge');
    var list = document.getElementById('notification-list');

    if (!toggle || !dropdown) return;

    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        var expanded = toggle.getAttribute('aria-expanded') === 'true';
        dropdown.classList.toggle('hidden');
        toggle.setAttribute('aria-expanded', String(!expanded));
    });

    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && !toggle.contains(e.target)) {
            dropdown.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            var csrfToken = '<?php echo htmlspecialchars(student_csrf_token(), ENT_QUOTES, "UTF-8"); ?>';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo htmlspecialchars(ROOT_URL . "/actions/student/mark-notifications-read.php", ENT_QUOTES, "UTF-8"); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-CSRF-Token', csrfToken);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    badge.classList.add('hidden');
                    badge.textContent = '0';
                    markAllBtn.remove();
                    var items = list.querySelectorAll('li a');
                    for (var i = 0; i < items.length; i++) {
                        items[i].classList.remove('bg-blue-50/50');
                        var dot = items[i].querySelector('.bg-blue-500');
                        if (dot) dot.remove();
                        var title = items[i].querySelector('.font-semibold');
                        if (title) title.classList.remove('font-semibold');
                    }
                }
            };
            xhr.send('csrf_token=' + encodeURIComponent(csrfToken));
        });
    }
})();
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
