<?php
/**
 * Admin Timetables module - list.
 *
 * Timetables are managed across all academic years. Each classroom has one
 * timetable per semester. The page defaults to showing every year (All Years)
 * but lets the admin switch to a single academic year via a dropdown. It
 * supports combined search + semester + year level + major + classroom
 * filtering.
 *
 * Display: year-by-year grouped layout (no cards).
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/timetable-validation.php';
require_once __DIR__ . '/../../includes/helpers/classroom-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageSubtitle = 'Manage timetable uploads across academic years.';
$activeNav    = 'timetables';

$ucsFlash = $_SESSION['timetable_flash'] ?? null;
unset($_SESSION['timetable_flash']);

// ---------------------------------------------------------------------
// Academic year context: defaults to All Years, filterable per year.
// ---------------------------------------------------------------------
$ucsActiveYear     = ucs_admin_active_academic_year($pdo);
$ucsActiveYearId   = $ucsActiveYear !== null ? (int) $ucsActiveYear['id'] : 0;
$ucsActiveYearName = $ucsActiveYear !== null ? (string) $ucsActiveYear['year_name'] : '';

$ucsAcademicYears  = ucs_admin_academic_years($pdo);
$ucsYearIds        = array_map('intval', array_column($ucsAcademicYears, 'id'));

$ucsYearId = filter_var($_GET['year'] ?? '', FILTER_VALIDATE_INT);
if ($ucsYearId === false || ($ucsYearId !== 0 && !in_array($ucsYearId, $ucsYearIds, true))) {
    $ucsYearId = 0; // 0 = All Years
}

$ucsSelectedYear = null;
foreach ($ucsAcademicYears as $ucsYearRow) {
    if ((int) $ucsYearRow['id'] === $ucsYearId) {
        $ucsSelectedYear = $ucsYearRow;
        break;
    }
}
$ucsSelectedYearName   = $ucsSelectedYear !== null ? (string) $ucsSelectedYear['year_name'] : '';
$ucsSelectedYearStatus = $ucsSelectedYear !== null ? (string) $ucsSelectedYear['status'] : '';

// ---------------------------------------------------------------------
// Filter option lists
// ---------------------------------------------------------------------
$ucsMajors     = ucs_admin_majors($pdo);
$ucsClassrooms = ucs_admin_classrooms($pdo);

// ---------------------------------------------------------------------
// Filters
// ---------------------------------------------------------------------
$ucsQuery      = trim((string) ($_GET['q'] ?? ''));
$ucsSemester   = trim((string) ($_GET['semester'] ?? ''));
$ucsYearLevel  = trim((string) ($_GET['year_level'] ?? ''));
$ucsMajorId    = filter_var($_GET['major'] ?? '', FILTER_VALIDATE_INT);
$ucsClassroomId = filter_var($_GET['classroom'] ?? '', FILTER_VALIDATE_INT);

// Reject values that are not part of the allowed option lists.
if (!in_array($ucsSemester, TIMETABLE_SEMESTERS, true)) {
    $ucsSemester = '';
}
if (!in_array($ucsYearLevel, CLASSROOM_YEAR_LEVELS, true)) {
    $ucsYearLevel = '';
}

$ucsMajorIds       = array_map('intval', array_column($ucsMajors, 'id'));
$ucsClassroomIds   = array_map('intval', array_column($ucsClassrooms, 'id'));
if ($ucsMajorId === false || !in_array($ucsMajorId, $ucsMajorIds, true)) {
    $ucsMajorId = 0;
}
if ($ucsClassroomId === false || !in_array($ucsClassroomId, $ucsClassroomIds, true)) {
    $ucsClassroomId = 0;
}

$ucsHasFilters = $ucsQuery !== '' || $ucsSemester !== '' || $ucsYearLevel !== '' || $ucsMajorId > 0 || $ucsClassroomId > 0;

// ---------------------------------------------------------------------
// Data: filtered listing — includes academic year for grouping.
// ---------------------------------------------------------------------
$ucsTimetables = [];

try {
    $ucsConditions = [];
    $ucsParams     = [];
    if ($ucsYearId > 0) {
        $ucsConditions[] = 'cl.academic_year_id = :ay';
        $ucsParams[':ay'] = $ucsYearId;
    }

    if ($ucsQuery !== '') {
        $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
        $ucsConditions[] = '(tt.title LIKE :q_title OR cl.classroom_name LIKE :q_name OR cl.section LIKE :q_section OR m.name LIKE :q_major)';
        $ucsParams[':q_title']   = '%' . $ucsEscaped . '%';
        $ucsParams[':q_name']    = '%' . $ucsEscaped . '%';
        $ucsParams[':q_section'] = '%' . $ucsEscaped . '%';
        $ucsParams[':q_major']   = '%' . $ucsEscaped . '%';
    }

    if ($ucsSemester !== '') {
        $ucsConditions[] = 'tt.semester = :semester';
        $ucsParams[':semester'] = $ucsSemester;
    }

    if ($ucsYearLevel !== '') {
        $ucsConditions[] = 'cl.year_level = :year_level';
        $ucsParams[':year_level'] = $ucsYearLevel;
    }

    if ($ucsMajorId > 0) {
        $ucsConditions[] = 'cl.major_id = :major_id';
        $ucsParams[':major_id'] = $ucsMajorId;
    }

    if ($ucsClassroomId > 0) {
        $ucsConditions[] = 'tt.classroom_id = :classroom_id';
        $ucsParams[':classroom_id'] = $ucsClassroomId;
    }

    $ucsWhereSql = count($ucsConditions) > 0 ? ' WHERE ' . implode(' AND ', $ucsConditions) : '';
    $ucsStmt = $pdo->prepare(
        "SELECT tt.id, tt.title, tt.image, tt.semester, tt.status, tt.created_at,
                cl.classroom_name, cl.year_level, cl.section,
                m.name AS major_name,
                ay.year_name AS academic_year_name, ay.status AS academic_year_status
         FROM timetables tt
         JOIN classrooms cl ON cl.id = tt.classroom_id
         JOIN majors m ON m.id = cl.major_id
         JOIN academic_years ay ON ay.id = cl.academic_year_id"
        . $ucsWhereSql . "
         ORDER BY ay.start_date DESC, tt.semester ASC, cl.year_level ASC, (cl.section IS NULL) ASC, cl.section ASC, cl.classroom_name ASC"
    );
    $ucsStmt->execute($ucsParams);
    $ucsTimetables = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsTimetables = [];
}

// ---------------------------------------------------------------------
// Group timetables by academic year for year-by-year display.
// ---------------------------------------------------------------------
$ucsTimetablesByYear = [];
foreach ($ucsTimetables as $ucsTt) {
    $ucsYearKey = (string) $ucsTt['academic_year_name'];
    if (!isset($ucsTimetablesByYear[$ucsYearKey])) {
        $ucsTimetablesByYear[$ucsYearKey] = [
            'year_name' => $ucsYearKey,
            'status'    => (string) $ucsTt['academic_year_status'],
            'items'     => [],
        ];
    }
    $ucsTimetablesByYear[$ucsYearKey]['items'][] = $ucsTt;
}

$ucsTimetableCount = count($ucsTimetables);
$ucsActiveCount    = 0;
$ucsInactiveCount  = 0;
foreach ($ucsTimetables as $ucsTt) {
    if ((int) $ucsTt['status'] === 1) {
        $ucsActiveCount++;
    } else {
        $ucsInactiveCount++;
    }
}

$hidePageHeader = true;
require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>

<!-- Page Header -->
<div class="mb-6 flex flex-col gap-1">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Timetables</h1>
    <p class="text-sm text-slate-500"><?php echo htmlspecialchars($pageSubtitle); ?></p>
</div>

<!-- Summary Stats -->
<?php if ($ucsActiveYear !== null): ?>
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900"><?php echo $ucsTimetableCount; ?></p>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Timetables</p>
            </div>
        </div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-emerald-700"><?php echo $ucsActiveCount; ?></p>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active</p>
            </div>
        </div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-500"><?php echo $ucsInactiveCount; ?></p>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Inactive</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($ucsFlash !== null): ?>
    <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'; ?> mb-6 rounded-lg px-4 py-3" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
        <p class="flex items-start gap-2 text-sm font-medium">
            <?php if ($ucsFlash['type'] === 'error'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <path d="m9 11 3 3L22 4"></path>
                </svg>
            <?php endif; ?>
            <?php echo htmlspecialchars($ucsFlash['message']); ?>
        </p>
    </div>
<?php endif; ?>

<?php if ($ucsActiveYear === null): ?>
    <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <h2 class="mt-4 text-lg font-semibold text-slate-900">No active academic year</h2>
        <p class="mt-2 text-sm text-slate-500">Timetables are managed under the active academic year. Activate an academic year to get started.</p>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Manage Academic Years
        </a>
    </div>
    <?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<!-- Search + Filters toolbar -->
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/index.php'); ?>" role="search" class="flex flex-col gap-5">

        <!-- Top Row: Search & Actions -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full lg:w-auto">
                <div class="relative w-full sm:w-64 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                    <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search timetable..." aria-label="Search timetable"
                           class="block w-full rounded-lg border border-slate-300 bg-slate-50 py-2 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-1 shrink-0">
                    Search
                </button>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/create.php'); ?>" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Upload Timetable
                </a>
            </div>
            <?php if ($ucsHasFilters): ?>
                <div class="flex shrink-0">
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/index.php'); ?>" class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-1">
                        Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="h-px w-full bg-slate-100"></div>

        <!-- Bottom Row: Filters Grid -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="year-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</label>
                <select id="year-filter" name="year" onchange="this.form.submit()" aria-label="Filter by academic year"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Years</option>
                    <?php foreach ($ucsAcademicYears as $ucsYearOption): ?>
                        <?php
                        $ucsYearOptionStatus = (string) $ucsYearOption['status'];
                        $ucsYearOptionLabel  = (string) $ucsYearOption['year_name'];
                        if ($ucsYearOptionStatus !== '') {
                            $ucsYearOptionLabel .= ' (' . $ucsYearOptionStatus . ')';
                        }
                        ?>
                        <option value="<?php echo (int) $ucsYearOption['id']; ?>" <?php echo $ucsYearId === (int) $ucsYearOption['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsYearOptionLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="semester-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Semester</label>
                <select id="semester-filter" name="semester" onchange="this.form.submit()" aria-label="Filter by semester"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Semesters</option>
                    <?php foreach (TIMETABLE_SEMESTERS as $ucsSemesterOption): ?>
                        <option value="<?php echo htmlspecialchars($ucsSemesterOption); ?>" <?php echo $ucsSemester === $ucsSemesterOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsSemesterOption); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="year-level-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Year Level</label>
                <select id="year-level-filter" name="year_level" onchange="this.form.submit()" aria-label="Filter by year level"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Year Levels</option>
                    <?php foreach (CLASSROOM_YEAR_LEVELS as $ucsYearLevelOption): ?>
                        <option value="<?php echo htmlspecialchars($ucsYearLevelOption); ?>" <?php echo $ucsYearLevel === $ucsYearLevelOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsYearLevelOption); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
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
                <label for="classroom-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Section / Classroom</label>
                <select id="classroom-filter" name="classroom" onchange="this.form.submit()" aria-label="Filter by classroom or section"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Classrooms</option>
                    <?php foreach ($ucsClassrooms as $ucsClassroomOption): ?>
                        <option value="<?php echo (int) $ucsClassroomOption['id']; ?>" <?php echo (int) $ucsClassroomId === (int) $ucsClassroomOption['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ucsClassroomOption['classroom_name'] . (!empty($ucsClassroomOption['academic_year']) ? ' — ' . $ucsClassroomOption['academic_year'] : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
</div>

<!-- Year-by-Year Timetable Display -->
<?php if (empty($ucsTimetablesByYear)): ?>
    <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
        </div>
        <h3 class="mt-4 text-lg font-semibold text-slate-900">
            <?php echo $ucsHasFilters ? 'No matching timetables' : 'No timetables yet'; ?>
        </h3>
        <p class="mt-2 text-sm text-slate-500">
            <?php if ($ucsHasFilters): ?>
                Try changing your search or filter criteria.
            <?php else: ?>
                Upload your first timetable to get started.
            <?php endif; ?>
        </p>
        <?php if ($ucsHasFilters): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/index.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                Clear Filters
            </a>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/create.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Upload Timetable
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>

    <div class="space-y-8">
    <?php foreach ($ucsTimetablesByYear as $ucsYearGroup): ?>
        <?php
        $ucsYearGroupName    = $ucsYearGroup['year_name'];
        $ucsYearGroupStatus  = $ucsYearGroup['status'];
        $ucsYearGroupItems   = $ucsYearGroup['items'];
        $ucsYearGroupCount   = count($ucsYearGroupItems);
        $ucsYearGroupActive  = 0;
        foreach ($ucsYearGroupItems as $ucsYgItem) {
            if ((int) $ucsYgItem['status'] === 1) $ucsYearGroupActive++;
        }
        $ucsYearGroupInactive = $ucsYearGroupCount - $ucsYearGroupActive;
        ?>

        <!-- Year Section -->
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <!-- Year Header -->
            <div class="flex flex-col gap-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold tracking-tight text-slate-900"><?php echo htmlspecialchars($ucsYearGroupName); ?></h2>
                        <p class="mt-0.5 text-xs font-medium text-slate-500">
                            <?php echo $ucsYearGroupCount; ?> timetable<?php echo $ucsYearGroupCount === 1 ? '' : 's'; ?>
                            &middot; <?php echo $ucsYearGroupActive; ?> active
                            <?php if ($ucsYearGroupInactive > 0): ?>
                                &middot; <?php echo $ucsYearGroupInactive; ?> inactive
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php if ($ucsYearGroupStatus !== '' && $ucsYearGroupStatus !== 'Active'): ?>
                    <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20"><?php echo htmlspecialchars($ucsYearGroupStatus); ?></span>
                <?php elseif ($ucsYearGroupStatus === 'Active'): ?>
                    <span class="inline-flex shrink-0 items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active Year</span>
                <?php endif; ?>
            </div>

            <!-- Timetable List -->
            <div class="divide-y divide-slate-100">
                <?php foreach ($ucsYearGroupItems as $ucsTimetable): ?>
                    <?php
                    $ucsTimetableTitle = (string) $ucsTimetable['title'];
                    $ucsJsName         = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsTimetableTitle);
                    $ucsImagePath      = ROOT_URL . '/assets/' . ltrim((string) $ucsTimetable['image'], '/');
                    $ucsIsActive       = (int) $ucsTimetable['status'] === 1;
                    ?>
                    <div class="group flex flex-col gap-4 px-6 py-4 transition-colors hover:bg-slate-50/50 sm:flex-row sm:items-center sm:gap-6">
                        <!-- Thumbnail -->
                        <div class="relative h-20 w-32 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                            <img src="<?php echo htmlspecialchars($ucsImagePath); ?>" alt="<?php echo htmlspecialchars($ucsTimetableTitle); ?>" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                            <div class="absolute inset-0 rounded-lg ring-1 ring-inset ring-black/5"></div>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start gap-2">
                                <h3 class="text-sm font-semibold text-slate-900 leading-snug truncate" title="<?php echo htmlspecialchars($ucsTimetableTitle); ?>"><?php echo htmlspecialchars($ucsTimetableTitle); ?></h3>
                                <?php if ($ucsIsActive): ?>
                                    <span class="inline-flex shrink-0 items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>
                                <?php else: ?>
                                    <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 ring-1 ring-inset ring-slate-500/20">Inactive</span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                <span class="inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                                    <?php echo htmlspecialchars((string) $ucsTimetable['major_name']); ?>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    <?php echo htmlspecialchars((string) $ucsTimetable['year_level']); ?>
                                    <?php if (!empty($ucsTimetable['section'])): ?>
                                        — <?php echo htmlspecialchars((string) $ucsTimetable['section']); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <?php echo htmlspecialchars((string) $ucsTimetable['classroom_name']); ?>
                                </span>
                                <span class="inline-flex items-center gap-1 font-medium text-slate-600">
                                    <?php echo htmlspecialchars((string) $ucsTimetable['semester']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex shrink-0 items-center gap-2">
                            <?php if (!$ucsIsActive): ?>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/timetable-activate.php'); ?>" class="flex">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsTimetable['id']; ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-600 transition-colors hover:bg-emerald-100 hover:text-emerald-700" title="Activate">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"></path></svg>
                                        Activate
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/timetable-deactivate.php'); ?>" class="flex" onsubmit="return confirm('Deactivate timetable &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsTimetable['id']; ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-200 hover:text-slate-700" title="Deactivate">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg>
                                        Deactivate
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/edit.php?id=' . (int) $ucsTimetable['id']); ?>" class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-600 transition-colors hover:bg-amber-100 hover:text-amber-700" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path><path d="m15 5 4 4"></path></svg>
                                Edit
                            </a>
                            <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/timetable-delete.php'); ?>" class="flex" onsubmit="return confirm('Delete timetable &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? This permanently removes it and cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                <input type="hidden" name="id" value="<?php echo (int) $ucsTimetable['id']; ?>">
                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-100 hover:text-rose-700" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    </div>

<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
