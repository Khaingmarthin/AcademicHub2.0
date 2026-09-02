<?php
/**
 * Public Courses page.
 *
 * Displays all courses grouped by academic year level (First Year through
 * Fifth Year) in the same layout used by the admin Courses module, including
 * summary stats, search, filters and per-level major tabs. Admin management
 * actions are intentionally omitted from this public view.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/helpers/course-validation.php';

$pageTitle = 'Courses';

$ucsQuery     = trim((string) ($_GET['q'] ?? ''));
$ucsSemester  = trim((string) ($_GET['semester'] ?? ''));
$ucsYearLevel = trim((string) ($_GET['year_level'] ?? ''));
$ucsMajorId   = filter_var($_GET['major'] ?? '', FILTER_VALIDATE_INT);
$ucsStatus    = trim((string) ($_GET['status'] ?? ''));

if (!in_array($ucsSemester, COURSE_SEMESTERS, true)) {
    $ucsSemester = '';
}
if (!in_array($ucsYearLevel, COURSE_YEAR_LEVELS, true)) {
    $ucsYearLevel = '';
}
if (!in_array($ucsStatus, ['active', 'inactive'], true)) {
    $ucsStatus = '';
}

$ucsMajors = [];
try {
    $ucsMajors = $pdo->query(
        "SELECT id, name
         FROM majors
         WHERE status = 1
         ORDER BY name ASC"
    )->fetchAll();
} catch (PDOException $e) {
    $ucsMajors = [];
}

$ucsMajorIds = array_map('intval', array_column($ucsMajors, 'id'));
if ($ucsMajorId === false || !in_array($ucsMajorId, $ucsMajorIds, true)) {
    $ucsMajorId = 0;
}

$ucsHasFilters = $ucsQuery !== '' || $ucsSemester !== '' || $ucsYearLevel !== '' || $ucsMajorId > 0 || $ucsStatus !== '';

$ucsSummary = ['total' => 0, 'year_levels' => 0, 'majors' => 0];
try {
    $ucsStmt = $pdo->query(
        "SELECT COUNT(*) AS total,
                COUNT(DISTINCT c.year_level) AS year_levels,
                COUNT(DISTINCT c.major_id) AS majors
         FROM courses c"
    );
    $ucsRow = $ucsStmt->fetch() ?: [];
    $ucsSummary = [
        'total'       => (int) ($ucsRow['total'] ?? 0),
        'year_levels' => (int) ($ucsRow['year_levels'] ?? 0),
        'majors'      => (int) ($ucsRow['majors'] ?? 0),
    ];
} catch (PDOException $e) {
}

$ucsCourses = [];
try {
    $ucsConditions = [];
    $ucsParams     = [];

    if ($ucsQuery !== '') {
        $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
        $ucsConditions[] = '(c.course_code LIKE :q_code OR c.course_name LIKE :q_name OR m.name LIKE :q_maj)';
        $ucsParams[':q_code'] = '%' . $ucsEscaped . '%';
        $ucsParams[':q_name'] = '%' . $ucsEscaped . '%';
        $ucsParams[':q_maj']  = '%' . $ucsEscaped . '%';
    }

    if ($ucsSemester !== '') {
        $ucsConditions[] = 'c.semester = :semester';
        $ucsParams[':semester'] = $ucsSemester;
    }

    if ($ucsYearLevel !== '') {
        $ucsConditions[] = 'c.year_level = :year_level';
        $ucsParams[':year_level'] = $ucsYearLevel;
    }

    if ($ucsMajorId > 0) {
        $ucsConditions[] = 'c.major_id = :major_id';
        $ucsParams[':major_id'] = $ucsMajorId;
    }

    if ($ucsStatus !== '') {
        $ucsConditions[] = 'c.status = :status';
        $ucsParams[':status'] = $ucsStatus === 'active' ? 1 : 0;
    }

    $ucsWhereSql = !empty($ucsConditions) ? ' WHERE ' . implode(' AND ', $ucsConditions) : '';
    $ucsStmt = $pdo->prepare(
        "SELECT c.id, c.course_code, c.course_name, c.year_level, c.semester,
                c.credit_hours, c.status,
                m.name AS major_name
         FROM courses c
         LEFT JOIN majors m ON m.id = c.major_id"
        . $ucsWhereSql . "
         ORDER BY FIELD(c.year_level, 'First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Fifth Year'), c.course_code ASC"
    );
    $ucsStmt->execute($ucsParams);
    $ucsCourses = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsCourses = [];
}

$ucsByLevel = [];
foreach ($ucsCourses as $ucsCourse) {
    $ucsLevelKey = (string) $ucsCourse['year_level'];
    if (!isset($ucsByLevel[$ucsLevelKey])) {
        $ucsByLevel[$ucsLevelKey] = [];
    }
    $ucsByLevel[$ucsLevelKey][] = $ucsCourse;
}

$ucsByLevelMajor = [];
foreach ($ucsByLevel as $ucsLvlName => $ucsLvlItems) {
    $ucsByLevelMajor[$ucsLvlName] = [];
    foreach ($ucsLvlItems as $ucsItem) {
        $ucsMaj = (string) ($ucsItem['major_name'] ?? 'General');
        if (!isset($ucsByLevelMajor[$ucsLvlName][$ucsMaj])) {
            $ucsByLevelMajor[$ucsLvlName][$ucsMaj] = 0;
        }
        $ucsByLevelMajor[$ucsLvlName][$ucsMaj]++;
    }
    ksort($ucsByLevelMajor[$ucsLvlName]);
}

require_once '../includes/header.php';
?>
<main class="flex-1 bg-slate-50">
    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="courses-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Courses</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Academic Programmes</p>
                <h1 id="courses-page-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                    Courses
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    Explore the courses offered at each academic year level, from foundation subjects in the
                    First Year through to advanced specialisation in the final years.
                </p>
            </div>
        </div>
    </section>

    <!-- Course directory -->
    <section class="py-12 sm:py-16 lg:py-20" aria-labelledby="courses-directory-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <!-- Summary Stats -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900"><?php echo number_format($ucsSummary['total']); ?></p>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Courses</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-emerald-700"><?php echo number_format($ucsSummary['year_levels']); ?></p>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Year Levels</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-amber-700"><?php echo number_format($ucsSummary['majors']); ?></p>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Majors</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search + Filters toolbar -->
            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="get" action="<?php echo htmlspecialchars(BASE_URL . '/courses.php'); ?>" role="search" class="flex flex-col gap-5">

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full lg:w-auto">
                            <div class="relative w-full sm:w-64 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                                <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search courses..." aria-label="Search courses"
                                       class="block w-full rounded-lg border border-slate-300 bg-slate-50 py-2 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-1 shrink-0">
                                Search
                            </button>
                            <?php if ($ucsHasFilters): ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/courses.php'); ?>" class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-1 shrink-0">
                                    Clear Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="h-px w-full bg-slate-100"></div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="major-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Major</label>
                            <select id="major-filter" name="major" onchange="this.form.submit()" aria-label="Filter by major"
                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">All Majors</option>
                                <?php foreach ($ucsMajors as $ucsMajorOption): ?>
                                    <option value="<?php echo (int) $ucsMajorOption['id']; ?>" <?php echo (int) $ucsMajorId === (int) $ucsMajorOption['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsMajorOption['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="year-level-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Year Level</label>
                            <select id="year-level-filter" name="year_level" onchange="this.form.submit()" aria-label="Filter by year level"
                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">All Year Levels</option>
                                <?php foreach (COURSE_YEAR_LEVELS as $ucsYearLevelOption): ?>
                                    <option value="<?php echo htmlspecialchars($ucsYearLevelOption); ?>" <?php echo $ucsYearLevel === $ucsYearLevelOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsYearLevelOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="semester-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Semester</label>
                            <select id="semester-filter" name="semester" onchange="this.form.submit()" aria-label="Filter by semester"
                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">All Semesters</option>
                                <?php foreach (COURSE_SEMESTERS as $ucsSemesterOption): ?>
                                    <option value="<?php echo htmlspecialchars($ucsSemesterOption); ?>" <?php echo $ucsSemester === $ucsSemesterOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsSemesterOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="status-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Status</label>
                            <select id="status-filter" name="status" onchange="this.form.submit()" aria-label="Filter by status"
                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $ucsStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $ucsStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (empty($ucsByLevel)): ?>
                <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">
                        <?php echo $ucsHasFilters ? 'No matching courses' : 'No courses yet'; ?>
                    </h3>
                    <p class="mt-2 text-sm text-slate-500">
                        <?php if ($ucsHasFilters): ?>
                            Try changing your search or filter criteria.
                        <?php else: ?>
                            No course information is currently available.
                        <?php endif; ?>
                    </p>
                    <?php if ($ucsHasFilters): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/courses.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>

                <div class="space-y-8">
                <?php foreach ($ucsByLevel as $ucsLevelName => $ucsLevelItems): ?>
                    <?php
                    $ucsLevelCount   = count($ucsLevelItems);
                    $ucsLevelActive  = 0;
                    $ucsLevelCredits = 0;
                    $ucsLevelFirst   = 0;
                    $ucsLevelSecond  = 0;
                    $ucsLevelNone    = 0;
                    foreach ($ucsLevelItems as $ucsLvlItem) {
                        if ((int) $ucsLvlItem['status'] === 1) $ucsLevelActive++;
                        $ucsLevelCredits += (int) ($ucsLvlItem['credit_hours'] ?? 0);
                        if ($ucsLvlItem['semester'] === 'First Semester') $ucsLevelFirst++;
                        elseif ($ucsLvlItem['semester'] === 'Second Semester') $ucsLevelSecond++;
                        else $ucsLevelNone++;
                    }
                    $ucsLevelInactive = $ucsLevelCount - $ucsLevelActive;
                    $ucsLevelSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower($ucsLevelName));
                    $ucsLevelMajors = $ucsByLevelMajor[$ucsLevelName] ?? [];
                    ?>

                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-col gap-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold tracking-tight text-slate-900"><?php echo htmlspecialchars($ucsLevelName); ?></h2>
                                    <p class="mt-0.5 text-xs font-medium text-slate-500">
                                        <?php echo $ucsLevelCount; ?> course<?php echo $ucsLevelCount === 1 ? '' : 's'; ?>
                                        &middot; <?php echo $ucsLevelActive; ?> active
                                        <?php if ($ucsLevelInactive > 0): ?>
                                            &middot; <?php echo $ucsLevelInactive; ?> inactive
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="hidden sm:flex items-center gap-4 text-xs font-medium text-slate-500">
                                    <?php if ($ucsLevelFirst > 0): ?>
                                    <span class="inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        <?php echo $ucsLevelFirst; ?> 1st sem
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($ucsLevelSecond > 0): ?>
                                    <span class="inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="m9 16 2 2 4-4"></path></svg>
                                        <?php echo $ucsLevelSecond; ?> 2nd sem
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($ucsLevelNone > 0): ?>
                                    <span class="inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <?php echo $ucsLevelNone; ?> shared
                                    </span>
                                    <?php endif; ?>
                                    <span class="inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <?php echo $ucsLevelCredits; ?> credits
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if (count($ucsLevelMajors) >= 2): ?>
                        <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-2.5">
                            <div class="flex items-center gap-1.5 overflow-x-auto" data-major-tabs="<?php echo htmlspecialchars($ucsLevelSlug); ?>">
                                <button type="button" onclick="switchMajorTab(this, '<?php echo htmlspecialchars($ucsLevelSlug); ?>')" data-major-tab="all" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all">
                                    All <span class="rounded-md bg-blue-500 px-1.5 py-0.5 text-[10px] leading-none text-white"><?php echo $ucsLevelCount; ?></span>
                                </button>
                                <?php foreach ($ucsLevelMajors as $ucsTabMajor => $ucsTabMajorCount):
                                    $ucsTabMajorSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $ucsTabMajor));
                                ?>
                                    <button type="button" onclick="switchMajorTab(this, '<?php echo htmlspecialchars($ucsLevelSlug); ?>')" data-major-tab="<?php echo htmlspecialchars($ucsTabMajorSlug); ?>" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition-all hover:bg-slate-50 hover:text-slate-900">
                                        <?php echo htmlspecialchars($ucsTabMajor); ?> <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] leading-none text-slate-600"><?php echo $ucsTabMajorCount; ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="divide-y divide-slate-100" data-major-list="<?php echo htmlspecialchars($ucsLevelSlug); ?>">
                            <?php foreach ($ucsLevelItems as $ucsCourse): ?>
                                <?php
                                $ucsCourseCode = (string) $ucsCourse['course_code'];
                                $ucsCourseName = (string) $ucsCourse['course_name'];
                                $ucsCourseMajor = (string) ($ucsCourse['major_name'] ?? 'General');
                                $ucsMajorSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $ucsCourseMajor));
                                ?>
                                <div data-major="<?php echo htmlspecialchars($ucsMajorSlug); ?>" class="group flex items-start gap-4 px-6 py-4 transition-colors hover:bg-slate-50/50 sm:items-center sm:gap-6">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 ring-1 ring-inset ring-slate-200/60">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start gap-2">
                                            <span class="inline-flex shrink-0 items-center rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-700"><?php echo htmlspecialchars($ucsCourseCode); ?></span>
                                            <h3 class="text-sm font-semibold text-slate-900 leading-snug" title="<?php echo htmlspecialchars($ucsCourseName); ?>"><?php echo htmlspecialchars($ucsCourseName); ?></h3>
                                            <?php if ((int) $ucsCourse['status'] === 1): ?>
                                                <span class="inline-flex shrink-0 items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>
                                            <?php else: ?>
                                                <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 ring-1 ring-inset ring-slate-500/20">Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                            <?php if (!empty($ucsCourse['major_name'])): ?>
                                            <span class="inline-flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path></svg>
                                                <?php echo htmlspecialchars($ucsCourse['major_name']); ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if (!empty($ucsCourse['semester'])): ?>
                                            <span class="inline-flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                                <?php echo htmlspecialchars($ucsCourse['semester']); ?>
                                            </span>
                                            <?php endif; ?>
                                            <span class="inline-flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                                <?php echo htmlspecialchars((string) $ucsCourse['credit_hours']); ?> credits
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    </section>
</main>

<script>
(function() {
    'use strict';
    window.switchMajorTab = function(btn, levelSlug) {
        var tabContainer = document.querySelector('[data-major-tabs="' + levelSlug + '"]');
        var listContainer = document.querySelector('[data-major-list="' + levelSlug + '"]');
        if (!tabContainer || !listContainer) return;

        var targetMajor = btn.getAttribute('data-major-tab');

        var tabs = tabContainer.querySelectorAll('button');
        tabs.forEach(function(t) {
            t.className = 'inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition-all hover:bg-slate-50 hover:text-slate-900';
        });
        btn.className = 'inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all';

        var rows = listContainer.querySelectorAll('[data-major]');
        rows.forEach(function(row) {
            if (targetMajor === 'all' || row.getAttribute('data-major') === targetMajor) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    };
})();
</script>

<?php
require_once '../includes/footer.php';
?>