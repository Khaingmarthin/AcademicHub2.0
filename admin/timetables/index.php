<?php
/**
 * Admin Timetables module - list.
 *
 * Timetables are managed across all academic years. Each classroom has one
 * timetable per semester. The page defaults to showing every year (All Years)
 * but lets the admin switch to a single academic year via a dropdown. It
 * supports combined search + semester + year level + major + classroom
 * filtering.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/timetable-validation.php';
require_once __DIR__ . '/../../includes/helpers/classroom-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Timetables';
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
// Data: filtered listing for the active academic year.
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
                m.name AS major_name
         FROM timetables tt
         JOIN classrooms cl ON cl.id = tt.classroom_id
         JOIN majors m ON m.id = cl.major_id"
        . $ucsWhereSql . "
         ORDER BY tt.semester ASC, cl.year_level ASC, (cl.section IS NULL) ASC, cl.section ASC, cl.classroom_name ASC"
    );
    $ucsStmt->execute($ucsParams);
    $ucsTimetables = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsTimetables = [];
}

$ucsTimetableCount = count($ucsTimetables);
$ucsResultLabel    = $ucsTimetableCount . ' timetable' . ($ucsTimetableCount === 1 ? '' : 's');
if ($ucsHasFilters) {
    $ucsResultLabel .= ' matching your filters';
}
if ($ucsYearId === 0) {
    $ucsResultLabel .= ' &middot; All Years';
} elseif ($ucsSelectedYearName !== '') {
    $ucsResultLabel .= ' &middot; ' . $ucsSelectedYearName;
    if ($ucsSelectedYearStatus !== '' && $ucsSelectedYearStatus !== 'Active') {
        $ucsResultLabel .= ' (' . $ucsSelectedYearStatus . ')';
    }
}

require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<?php if ($ucsFlash !== null): ?>
    <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100 text-red-700' : 'bg-blue-50 ring-blue-100 text-blue-700'; ?> rounded-xl px-4 py-3 ring-1" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
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
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <h2 class="mt-4 text-lg font-semibold text-gray-800">No active academic year</h2>
        <p class="mt-2 text-sm text-gray-500">Timetables are managed under the active academic year. Activate an academic year to get started.</p>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            Manage Academic Years
        </a>
    </div>
    <?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<!-- Search + Filters toolbar -->
<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/index.php'); ?>" role="search">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search timetable..." aria-label="Search timetable"
                       class="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>

            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Upload Timetable
            </a>
        </div>

        <div class="mt-5 grid gap-4 border-t border-gray-100 pt-5 sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <label for="year-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Academic Year</label>
                <select id="year-filter" name="year" onchange="this.form.submit()" aria-label="Filter by academic year"
                        class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
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
                <label for="semester-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Semester</label>
                <select id="semester-filter" name="semester" onchange="this.form.submit()" aria-label="Filter by semester"
                        class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Semesters</option>
                    <?php foreach (TIMETABLE_SEMESTERS as $ucsSemesterOption): ?>
                        <option value="<?php echo htmlspecialchars($ucsSemesterOption); ?>" <?php echo $ucsSemester === $ucsSemesterOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsSemesterOption); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="year-level-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Year Level</label>
                <select id="year-level-filter" name="year_level" onchange="this.form.submit()" aria-label="Filter by year level"
                        class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Year Levels</option>
                    <?php foreach (CLASSROOM_YEAR_LEVELS as $ucsYearLevelOption): ?>
                        <option value="<?php echo htmlspecialchars($ucsYearLevelOption); ?>" <?php echo $ucsYearLevel === $ucsYearLevelOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsYearLevelOption); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="major-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Major</label>
                <select id="major-filter" name="major" onchange="this.form.submit()" aria-label="Filter by major"
                        class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Majors</option>
                    <?php foreach ($ucsMajors as $ucsMajorOption): ?>
                        <option value="<?php echo (int) $ucsMajorOption['id']; ?>" <?php echo (int) $ucsMajorId === (int) $ucsMajorOption['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsMajorOption['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="classroom-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Section / Classroom</label>
                <select id="classroom-filter" name="classroom" onchange="this.form.submit()" aria-label="Filter by classroom or section"
                        class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Classrooms</option>
                    <?php foreach ($ucsClassrooms as $ucsClassroomOption): ?>
                        <option value="<?php echo (int) $ucsClassroomOption['id']; ?>" <?php echo (int) $ucsClassroomId === (int) $ucsClassroomOption['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ucsClassroomOption['classroom_name'] . (!empty($ucsClassroomOption['academic_year']) ? ' — ' . $ucsClassroomOption['academic_year'] : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/index.php'); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400 sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                        <path d="M3 3v5h5"></path>
                    </svg>
                    Clear Filters
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Timetable table -->
<div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
    <div class="flex flex-col gap-1 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Timetable List</h2>
            <p class="mt-1 text-sm text-gray-500"><?php echo $ucsResultLabel; ?></p>
        </div>
    </div>

    <?php if (empty($ucsTimetables)): ?>
        <div class="p-10 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-800">No timetables found</h3>
            <p class="mt-2 text-sm text-gray-500">
                <?php if ($ucsHasFilters): ?>
                    Try changing your search or filter criteria.
                <?php elseif ($ucsSelectedYearStatus !== '' && $ucsSelectedYearStatus !== 'Active'): ?>
                    No timetables were uploaded for this academic year.
                <?php else: ?>
                    Upload your first timetable to get started.
                <?php endif; ?>
            </p>
            <?php if ($ucsHasFilters): ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Clear Filters
                </a>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Upload Timetable
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Timetable</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Semester</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Year Level</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Major</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Section / Classroom</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($ucsTimetables as $ucsTimetable): ?>
                        <?php
                        $ucsTimetableTitle = (string) $ucsTimetable['title'];
                        $ucsJsName         = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsTimetableTitle);
                        $ucsImagePath      = ROOT_URL . '/assets/' . ltrim((string) $ucsTimetable['image'], '/');
                        ?>
                        <tr class="transition-colors hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200" aria-hidden="true">
                                        <img src="<?php echo htmlspecialchars($ucsImagePath); ?>" alt="" class="h-full w-full object-cover">
                                    </span>
                                    <div class="min-w-0">
                                        <p class="max-w-[16rem] truncate font-semibold text-gray-900"><?php echo htmlspecialchars($ucsTimetableTitle); ?></p>
                                        <p class="text-xs text-gray-500">Added <?php echo date('j M Y', strtotime((string) $ucsTimetable['created_at'])); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100"><?php echo htmlspecialchars((string) $ucsTimetable['semester']); ?></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-gray-600"><?php echo htmlspecialchars((string) $ucsTimetable['year_level']); ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-gray-600"><?php echo htmlspecialchars((string) $ucsTimetable['major_name']); ?></td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <?php if (!empty($ucsTimetable['section'])): ?>
                                        <span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-gray-800 ring-1 ring-gray-200"><?php echo htmlspecialchars((string) $ucsTimetable['section']); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                    <span class="text-xs text-gray-500"><?php echo htmlspecialchars((string) $ucsTimetable['classroom_name']); ?></span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/edit.php?id=' . (int) $ucsTimetable['id']); ?>" title="Edit <?php echo htmlspecialchars($ucsTimetableTitle); ?>" aria-label="Edit <?php echo htmlspecialchars($ucsTimetableTitle); ?>"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                            <path d="m15 5 4 4"></path>
                                        </svg>
                                    </a>

                                    <?php if ((int) $ucsTimetable['status'] !== 1): ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/timetable-activate.php'); ?>" class="inline-flex">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsTimetable['id']; ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                                Activate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/timetable-deactivate.php'); ?>" class="inline-flex"
                                              onsubmit="return confirm('Deactivate timetable &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsTimetable['id']; ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                                                Deactivate
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/timetable-delete.php'); ?>" class="inline-flex"
                                          onsubmit="return confirm('Delete timetable &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsTimetable['id']; ?>">
                                        <button type="submit" title="Delete <?php echo htmlspecialchars($ucsTimetableTitle); ?>" aria-label="Delete <?php echo htmlspecialchars($ucsTimetableTitle); ?>"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition-colors duration-150 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18"></path>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>