<?php
/**
 * Student Timetable (protected).
 *
 * Displays the active timetable images for the authenticated student's classroom.
 * Timetables are uploaded by admins and associated with a classroom and semester.
 * Students can switch between semesters using tabs.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';

student_require_login();

$pageTitle = 'My Timetable';

$ucsStudent = student_current_user();
$ucsDetails = null;
$ucsTimetables = [];
$ucsActiveSemester = isset($_GET['semester']) ? trim((string) $_GET['semester']) : '';

try {
    $ucsStmt = $pdo->prepare(
        "SELECT s.classroom_id,
                c.classroom_name, c.year_level, c.section,
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

// Fetch all active timetables for the student's classroom.
if ($ucsDetails !== null) {
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id, semester, title, image, created_at
             FROM timetables
             WHERE classroom_id = :classroom_id
               AND status = 1
             ORDER BY
                CASE semester
                    WHEN 'First Semester' THEN 1
                    WHEN 'Second Semester' THEN 2
                    ELSE 3
                END ASC"
        );
        $ucsStmt->execute([':classroom_id' => $ucsDetails['classroom_id']]);
        $ucsTimetables = $ucsStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        $ucsTimetables = [];
    }
}

// Build semester index for quick lookup.
$ucsSemesterMap = [];
foreach ($ucsTimetables as $ucsTt) {
    $ucsSemesterMap[$ucsTt['semester']] = $ucsTt;
}

// Determine active semester: prefer query param, fall back to the first available.
$ucsAllSemesters = ['First Semester', 'Second Semester'];
if ($ucsActiveSemester === '' || !isset($ucsSemesterMap[$ucsActiveSemester])) {
    $ucsActiveSemester = !empty($ucsTimetables) ? $ucsTimetables[0]['semester'] : $ucsAllSemesters[0];
}

$ucsCurrentTimetable = $ucsSemesterMap[$ucsActiveSemester] ?? null;
$ucsTimetableUrl = '';
$ucsHasTimetable = false;

if ($ucsCurrentTimetable !== null && !empty($ucsCurrentTimetable['image'])) {
    $ucsTimetableFile = dirname(__DIR__) . '/assets/uploads/' . ltrim($ucsCurrentTimetable['image'], '/');
    $ucsHasTimetable  = is_file($ucsTimetableFile);
    if ($ucsHasTimetable) {
        $ucsTimetableUrl = ROOT_URL . '/assets/uploads/' . ltrim($ucsCurrentTimetable['image'], '/');
    }
}

// Helper: build semester tab URL.
$ucsSemesterUrl = function ($semester) {
    return BASE_URL . '/student-timetable.php?semester=' . urlencode($semester);
};

require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-1 bg-slate-50">
    <section class="py-12 sm:py-16" aria-labelledby="student-timetable-heading">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            <!-- Page header -->
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <nav class="mb-4 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                        <ol class="flex items-center gap-1.5">
                            <li><a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="transition-colors hover:text-slate-600">Dashboard</a></li>
                            <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                            <li class="text-slate-600">Timetable</li>
                        </ol>
                    </nav>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Student Portal</p>
                    <h1 id="student-timetable-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">My Timetable</h1>
                    <?php if ($ucsDetails !== null): ?>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            <?php echo htmlspecialchars($ucsDetails['classroom_name'] ?? ''); ?>
                            &mdash; <?php echo htmlspecialchars($ucsDetails['major_name'] ?? ''); ?>, <?php echo htmlspecialchars($ucsDetails['year_level'] ?? ''); ?>
                            <?php if ($ucsDetails['section'] !== '' && $ucsDetails['section'] !== null): ?>
                                , Section <?php echo htmlspecialchars($ucsDetails['section'] ?? ''); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                    Dashboard
                </a>
            </div>

            <!-- Semester tabs -->
            <div class="mt-8 border-b border-slate-200" role="tablist" aria-label="Semester selection">
                <nav class="flex gap-0 -mb-px" aria-label="Semester">
                    <?php foreach ($ucsAllSemesters as $ucsSem): ?>
                        <?php
                        $ucsIsCurrent = ($ucsActiveSemester === $ucsSem);
                        $ucsHasSem    = isset($ucsSemesterMap[$ucsSem]);
                        ?>
                        <a href="<?php echo htmlspecialchars($ucsSemesterUrl($ucsSem)); ?>"
                           role="tab"
                           aria-selected="<?php echo $ucsIsCurrent ? 'true' : 'false'; ?>"
                           class="<?php
                               if ($ucsIsCurrent) {
                                   echo 'border-b-2 border-blue-600 text-blue-600';
                               } elseif ($ucsHasSem) {
                                   echo 'border-b-2 border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700';
                               } else {
                                   echo 'border-b-2 border-transparent text-slate-300 cursor-not-allowed';
                               }
                           ?> inline-flex items-center gap-2 px-5 py-3 text-sm font-semibold transition-colors whitespace-nowrap">
                            <?php echo htmlspecialchars($ucsSem); ?>
                            <?php if ($ucsHasSem): ?>
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full <?php echo $ucsIsCurrent ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'; ?> text-[0.625rem] font-bold">
                                    1
                                </span>
                            <?php else: ?>
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-50 text-slate-300 text-[0.625rem] font-bold">
                                    0
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <?php if ($ucsHasTimetable && $ucsTimetableUrl !== ''): ?>
                <!-- Timetable toolbar -->
                <div class="mt-6 flex flex-col gap-4 rounded-lg border border-slate-200 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold text-slate-900"><?php echo htmlspecialchars($ucsCurrentTimetable['title'] ?? ''); ?></h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            <?php echo htmlspecialchars($ucsActiveSemester); ?>
                            &middot; <?php echo htmlspecialchars($ucsDetails['academic_year'] ?? ''); ?>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <a href="<?php echo htmlspecialchars($ucsTimetableUrl); ?>" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"></path>
                            </svg>
                            Open Full Size
                        </a>
                        <a href="<?php echo htmlspecialchars($ucsTimetableUrl); ?>" download
                           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Download
                        </a>
                    </div>
                </div>

                <!-- Timetable image -->
                <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <img
                        src="<?php echo htmlspecialchars($ucsTimetableUrl); ?>"
                        alt="<?php echo htmlspecialchars(($ucsCurrentTimetable['title'] ?? '') . ' \u2014 ' . $ucsActiveSemester); ?>"
                        class="w-full object-contain"
                        loading="lazy"
                    >
                </div>

                <!-- Image footer info -->
                <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                    <span>Posted <?php echo htmlspecialchars(date('M j, Y', strtotime($ucsCurrentTimetable['created_at']))); ?></span>
                    <span><?php echo htmlspecialchars($ucsDetails['classroom_name'] ?? ''); ?></span>
                </div>

            <?php else: ?>
                <!-- No timetable available -->
                <div class="mt-8 rounded-lg border border-slate-200 bg-white px-6 py-16 text-center sm:px-8">
                    <span class="inline-flex h-16 w-16 items-center justify-center rounded-lg bg-slate-100 text-slate-300" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                    </span>
                    <h2 class="mt-5 text-xl font-bold tracking-tight text-slate-900">No Timetable Available</h2>
                    <p class="mx-auto mt-3 max-w-md text-base leading-7 text-slate-500">
                        <?php if (!empty($ucsTimetables)): ?>
                            A timetable exists but is not available for the selected semester.
                        <?php else: ?>
                            Your class timetable has not been published yet. Please check back soon or contact the academic office.
                        <?php endif; ?>
                    </p>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="mt-7 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
