<?php
/**
 * Admin Classrooms module - card-based management view.
 *
 * Displays classrooms in a responsive card grid with search, major,
 * year level, academic year and status filtering.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/classroom-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Classroom Management';
$pageSubtitle = 'Manage classrooms, sections and student allocations across academic years.';
$activeNav    = 'classrooms';

$ucsFlash = $_SESSION['classroom_flash'] ?? null;
unset($_SESSION['classroom_flash']);

$ucsActiveYear     = ucs_admin_active_academic_year($pdo);
$ucsActiveYearId   = $ucsActiveYear !== null ? (int) $ucsActiveYear['id'] : 0;
$ucsActiveYearName = $ucsActiveYear !== null ? (string) $ucsActiveYear['year_name'] : '';

$ucsAcademicYears = ucs_admin_academic_years($pdo);
$ucsYearIds       = array_map('intval', array_column($ucsAcademicYears, 'id'));

$ucsQuery     = trim((string) ($_GET['q'] ?? ''));
$ucsYearLevel = trim((string) ($_GET['year_level'] ?? ''));
$ucsMajorId   = filter_var($_GET['major'] ?? '', FILTER_VALIDATE_INT);
$ucsStatus    = trim((string) ($_GET['status'] ?? ''));
$ucsYearId    = filter_var($_GET['year'] ?? '', FILTER_VALIDATE_INT);

if ($ucsYearId === false || ($ucsYearId !== 0 && !in_array($ucsYearId, $ucsYearIds, true))) {
    $ucsYearId = 0;
}
if ($ucsMajorId === false || $ucsMajorId < 1) {
    $ucsMajorId = 0;
}
if (!in_array($ucsYearLevel, CLASSROOM_YEAR_LEVELS, true)) {
    $ucsYearLevel = '';
}
if (!in_array($ucsStatus, ['active', 'inactive'], true)) {
    $ucsStatus = '';
}

$ucsSelectedYear = null;
foreach ($ucsAcademicYears as $ucsYearRow) {
    if ((int) $ucsYearRow['id'] === $ucsYearId) {
        $ucsSelectedYear = $ucsYearRow;
        break;
    }
}

$ucsMajors = ucs_admin_majors($pdo);
$ucsHasFilters = $ucsQuery !== '' || $ucsYearLevel !== '' || $ucsMajorId > 0 || $ucsStatus !== '' || $ucsYearId > 0;

$ucsSummary = ['total' => 0, 'active' => 0, 'students' => 0, 'timetables' => 0];
try {
    $ucsSumWhere  = $ucsYearId > 0 ? ' WHERE cl.academic_year_id = :ay' : '';
    $ucsSumParams = $ucsYearId > 0 ? [':ay' => $ucsYearId] : [];
    $ucsStmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT cl.id) AS total,
                COALESCE(SUM(cl.status = 1), 0) AS active,
                COUNT(DISTINCT s.id) AS students,
                COUNT(DISTINCT t.id) AS timetables
         FROM classrooms cl
         LEFT JOIN students s ON s.classroom_id = cl.id
         LEFT JOIN timetables t ON t.classroom_id = cl.id"
        . $ucsSumWhere
    );
    $ucsStmt->execute($ucsSumParams);
    $ucsRow = $ucsStmt->fetch() ?: [];
    $ucsSummary = [
        'total'      => (int) ($ucsRow['total'] ?? 0),
        'active'     => (int) ($ucsRow['active'] ?? 0),
        'students'   => (int) ($ucsRow['students'] ?? 0),
        'timetables' => (int) ($ucsRow['timetables'] ?? 0),
    ];
} catch (PDOException $e) {
}

$ucsClassrooms = [];
try {
    $ucsConditions = [];
    $ucsParams     = [];

    if ($ucsYearId > 0) {
        $ucsConditions[] = 'cl.academic_year_id = :ay';
        $ucsParams[':ay'] = $ucsYearId;
    }
    if ($ucsMajorId > 0) {
        $ucsConditions[] = 'cl.major_id = :major_id';
        $ucsParams[':major_id'] = $ucsMajorId;
    }
    if ($ucsYearLevel !== '') {
        $ucsConditions[] = 'cl.year_level = :year_level';
        $ucsParams[':year_level'] = $ucsYearLevel;
    }
    if ($ucsStatus !== '') {
        $ucsConditions[] = 'cl.status = :status';
        $ucsParams[':status'] = $ucsStatus === 'active' ? 1 : 0;
    }
    if ($ucsQuery !== '') {
        $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
        $ucsConditions[] = '(cl.classroom_name LIKE :q OR m.name LIKE :q_maj)';
        $ucsParams[':q']     = '%' . $ucsEscaped . '%';
        $ucsParams[':q_maj'] = '%' . $ucsEscaped . '%';
    }

    $ucsWhereSql = !empty($ucsConditions) ? ' WHERE ' . implode(' AND ', $ucsConditions) : '';

    $ucsStmt = $pdo->prepare(
        "SELECT cl.id, cl.classroom_name, cl.year_level, cl.section, cl.status, cl.created_at,
                m.name AS major_name,
                ay.year_name AS academic_year_name, ay.status AS academic_year_status,
                COUNT(DISTINCT s.id) AS student_count,
                COUNT(DISTINCT t.id) AS timetable_count
         FROM classrooms cl
         JOIN majors m ON m.id = cl.major_id
         JOIN academic_years ay ON ay.id = cl.academic_year_id
         LEFT JOIN students s ON s.classroom_id = cl.id
         LEFT JOIN timetables t ON t.classroom_id = cl.id"
        . $ucsWhereSql . "
         GROUP BY cl.id, m.name, ay.year_name, ay.status
         ORDER BY ay.start_date DESC, cl.year_level ASC, cl.section ASC, cl.classroom_name ASC"
    );
    $ucsStmt->execute($ucsParams);
    $ucsClassrooms = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsClassrooms = [];
}

$ucsCount = count($ucsClassrooms);
$ucsResultLabel = $ucsCount . ' classroom' . ($ucsCount === 1 ? '' : 's');
if ($ucsHasFilters) {
    $ucsResultLabel .= ' found';
}

require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<?php if ($ucsFlash !== null): ?>
    <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-blue-50 border-blue-200 text-blue-700'; ?> rounded-lg px-4 py-3 " role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
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
    <div class="rounded-lg bg-white p-10 text-center ">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <h2 class="mt-4 text-lg font-semibold text-gray-800">No active academic year</h2>
        <p class="mt-2 text-sm text-gray-500">Classrooms are managed under the active academic year. Activate an academic year to get started.</p>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500">
            Manage Academic Years
        </a>
    </div>
    <?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<!-- Search + Filters toolbar -->
<div class="rounded-lg bg-white px-4 py-3 ">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/index.php'); ?>" role="search">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search classrooms..." aria-label="Search classrooms"
                       class="block w-full rounded-lg border border-gray-300 bg-slate-50 py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14"></path>
                    <path d="M12 5v14"></path>
                </svg>
                Add Classroom
            </a>
        </div>

        <div class="mt-3 grid gap-3 border-t border-gray-100 pt-5 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="year-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Academic Year</label>
                <select id="year-filter" name="year" onchange="this.form.submit()" aria-label="Filter by academic year"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
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
                <label for="major-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Major</label>
                <select id="major-filter" name="major" onchange="this.form.submit()" aria-label="Filter by major"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Majors</option>
                    <?php foreach ($ucsMajors as $ucsMajorOption): ?>
                        <option value="<?php echo (int) $ucsMajorOption['id']; ?>" <?php echo (int) $ucsMajorId === (int) $ucsMajorOption['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsMajorOption['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="year-level-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Year Level</label>
                <select id="year-level-filter" name="year_level" onchange="this.form.submit()" aria-label="Filter by year level"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Year Levels</option>
                    <?php foreach (CLASSROOM_YEAR_LEVELS as $ucsYearLevelOption): ?>
                        <option value="<?php echo htmlspecialchars($ucsYearLevelOption); ?>" <?php echo $ucsYearLevel === $ucsYearLevelOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsYearLevelOption); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="status-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                <select id="status-filter" name="status" onchange="this.form.submit()" aria-label="Filter by status"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $ucsStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $ucsStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <div class="flex items-end">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/index.php'); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-slate-400 sm:w-auto">
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

<!-- Summary stats -->
<div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-3 ">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 " aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-xl font-bold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['total'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Total Classrooms</p>
        </div>
    </div>
    <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-3 ">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 " aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <path d="m9 11 3 3L22 4"></path>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-xl font-bold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['active'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Active</p>
        </div>
    </div>
    <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-3 ">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 " aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-xl font-bold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['students'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Students</p>
        </div>
    </div>
    <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-3 ">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 " aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-xl font-bold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['timetables'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Timetables</p>
        </div>
    </div>
</div>

<!-- Classroom cards header -->
<div class="mt-6 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-base font-semibold text-gray-900">Classroom List</h2>
        <p class="mt-1 text-sm text-gray-500"><?php echo $ucsResultLabel; ?></p>
    </div>
</div>

<!-- Classroom card grid -->
<?php if (empty($ucsClassrooms)): ?>
    <div class="mt-4 rounded-lg bg-white p-10 text-center ">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">
            <?php echo $ucsHasFilters ? 'No matching classrooms' : 'No classrooms yet'; ?>
        </h3>
        <p class="mt-2 text-sm text-gray-500">
            <?php if ($ucsHasFilters): ?>
                Try changing your search or filter criteria.
            <?php else: ?>
                Add your first classroom to get started.
            <?php endif; ?>
        </p>
        <?php if ($ucsHasFilters): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500">
                Clear Filters
            </a>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500">
                Add Classroom
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($ucsClassrooms as $ucsClassroom): ?>
            <?php
            $ucsClassName = (string) $ucsClassroom['classroom_name'];
            $ucsJsName    = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsClassName);
            ?>
            <div class="rounded-lg border border-slate-200 bg-white p-4 transition-colors hover:border-slate-300">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 border border-blue-200">
                        <?php echo htmlspecialchars((string) $ucsClassroom['academic_year_name']); ?>
                    </span>
                    <?php if ((int) $ucsClassroom['status'] === 1): ?>
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200">Active</span>
                    <?php else: ?>
                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-500 border border-slate-200">Inactive</span>
                    <?php endif; ?>
                </div>

                <h3 class="truncate text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($ucsClassName); ?></h3>

                <div class="mt-2 space-y-1">
                    <p class="text-[11px] text-gray-500">Major: <span class="font-medium text-gray-700"><?php echo htmlspecialchars((string) $ucsClassroom['major_name']); ?></span></p>
                    <p class="text-[11px] text-gray-500">Year Level: <span class="font-medium text-gray-700"><?php echo htmlspecialchars((string) $ucsClassroom['year_level']); ?></span></p>
                    <p class="text-[11px] text-gray-500">Section: <span class="font-medium text-gray-700"><?php echo !empty($ucsClassroom['section']) ? htmlspecialchars((string) $ucsClassroom['section']) : '&mdash;'; ?></span></p>
                </div>

                <div class="mt-3 flex items-center gap-3 border-t border-slate-100 pt-3">
                    <span class="text-[11px] text-gray-500"><span class="font-semibold text-gray-700"><?php echo (int) $ucsClassroom['student_count']; ?></span> students</span>
                    <span class="text-[11px] text-gray-500"><span class="font-semibold text-gray-700"><?php echo (int) $ucsClassroom['timetable_count']; ?></span> timetables</span>
                </div>

                <div class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-3">
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php?classroom=' . (int) $ucsClassroom['id']); ?>" title="View students" aria-label="View students for <?php echo htmlspecialchars($ucsClassName); ?>"
                       class="inline-flex h-7 items-center gap-1 rounded-md border border-slate-200 bg-white px-2 text-[11px] font-semibold text-gray-600 transition-colors hover:bg-slate-50 hover:text-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        View
                    </a>
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/classrooms/edit.php?id=' . (int) $ucsClassroom['id']); ?>" title="Edit <?php echo htmlspecialchars($ucsClassName); ?>" aria-label="Edit <?php echo htmlspecialchars($ucsClassName); ?>"
                       class="inline-flex h-7 items-center gap-1 rounded-md border border-slate-200 bg-white px-2 text-[11px] font-semibold text-gray-600 transition-colors hover:bg-slate-50 hover:text-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                            <path d="m15 5 4 4"></path>
                        </svg>
                        Edit
                    </a>

                    <?php if ((int) $ucsClassroom['status'] !== 1): ?>
                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/classroom-activate.php'); ?>" class="inline-flex">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $ucsClassroom['id']; ?>">
                            <button type="submit"
                                    class="inline-flex h-7 items-center gap-1 rounded-md bg-blue-600 px-2 text-[11px] font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                Activate
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/classroom-deactivate.php'); ?>" class="inline-flex"
                              onsubmit="return confirm('Deactivate classroom &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $ucsClassroom['id']; ?>">
                            <button type="submit"
                                    class="inline-flex h-7 items-center gap-1 rounded-md border border-slate-200 bg-white px-2 text-[11px] font-semibold text-gray-600 transition-colors hover:bg-slate-50 hover:text-gray-900 focus:outline-none focus:ring-1 focus:ring-slate-400">
                                Deactivate
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/classroom-delete.php'); ?>" class="inline-flex ml-auto"
                          onsubmit="return confirm('Delete classroom &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? This permanently removes it and cannot be undone.');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $ucsClassroom['id']; ?>">
                        <button type="submit" title="Delete <?php echo htmlspecialchars($ucsClassName); ?>" aria-label="Delete <?php echo htmlspecialchars($ucsClassName); ?>"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-red-200 bg-white text-red-500 transition-colors hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-1 focus:ring-red-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18"></path>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>