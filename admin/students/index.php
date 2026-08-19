<?php
/**
 * Admin Students module - list.
 *
 * Students are managed across all academic years: the page shows students
 * from every year by default (All Years) but lets the admin switch to a
 * single academic year via a dropdown. It supports combined search + year
 * level + major + classroom + status filtering and displays a small summary
 * computed from the real database. Passwords are never displayed.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/classroom-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Students';
$pageSubtitle = 'Manage students across academic years.';
$activeNav    = 'students';

$ucsFlash = $_SESSION['student_flash'] ?? null;
unset($_SESSION['student_flash']);

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
// Filters
// ---------------------------------------------------------------------
$ucsQuery      = trim((string) ($_GET['q'] ?? ''));
$ucsYearLevel  = trim((string) ($_GET['year_level'] ?? ''));
$ucsMajorId    = filter_var($_GET['major'] ?? '', FILTER_VALIDATE_INT);
$ucsClassroomId = filter_var($_GET['classroom'] ?? '', FILTER_VALIDATE_INT);
$ucsStatus     = trim((string) ($_GET['status'] ?? ''));

// Reject values that are not part of the allowed option lists.
if (!in_array($ucsYearLevel, CLASSROOM_YEAR_LEVELS, true)) {
    $ucsYearLevel = '';
}
if (!in_array($ucsStatus, ['', '1', '0'], true)) {
    $ucsStatus = '';
}

// Academic status (active / graduated) filters separately from the account
// status; the two concepts are distinct.
$ucsAcademicStatus = trim((string) ($_GET['academic_status'] ?? ''));
if (!in_array($ucsAcademicStatus, ['', 'active', 'graduated'], true)) {
    $ucsAcademicStatus = '';
}

// Majors and classrooms are loaded from the database so future records
// appear automatically in the filters.
$ucsMajors     = ucs_admin_majors($pdo);
$ucsClassrooms = ucs_admin_classrooms($pdo);

$ucsMajorIds     = array_map('intval', array_column($ucsMajors, 'id'));
$ucsClassroomIds = array_map('intval', array_column($ucsClassrooms, 'id'));
if ($ucsMajorId === false || !in_array($ucsMajorId, $ucsMajorIds, true)) {
    $ucsMajorId = 0;
}
if ($ucsClassroomId === false || !in_array($ucsClassroomId, $ucsClassroomIds, true)) {
    $ucsClassroomId = 0;
}

$ucsHasFilters = $ucsQuery !== '' || $ucsYearLevel !== '' || $ucsMajorId > 0 || $ucsClassroomId > 0 || $ucsStatus !== '' || $ucsAcademicStatus !== '';

// ---------------------------------------------------------------------
// Data: summary + filtered listing for the active academic year.
// ---------------------------------------------------------------------
$ucsStudents = [];
$ucsSummary  = ['total' => 0, 'active' => 0, 'inactive' => 0, 'graduated' => 0];

try {
    $ucsSummaryWhere  = $ucsYearId > 0 ? ' WHERE cl.academic_year_id = :ay' : '';
    $ucsSummaryParams = $ucsYearId > 0 ? [':ay' => $ucsYearId] : [];
    $ucsStmt = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(st.status = 1), 0) AS active,
                COALESCE(SUM(st.status = 0), 0) AS inactive,
                COALESCE(SUM(st.student_status = 'graduated'), 0) AS graduated
         FROM students st
         JOIN classrooms cl ON cl.id = st.classroom_id"
        . $ucsSummaryWhere
    );
    $ucsStmt->execute($ucsSummaryParams);
    $ucsSummaryRow = $ucsStmt->fetch() ?: [];
    $ucsSummary = [
        'total'     => (int) ($ucsSummaryRow['total'] ?? 0),
        'active'    => (int) ($ucsSummaryRow['active'] ?? 0),
        'inactive'  => (int) ($ucsSummaryRow['inactive'] ?? 0),
        'graduated' => (int) ($ucsSummaryRow['graduated'] ?? 0),
    ];
} catch (PDOException $e) {
    // Keep zeroed summary when the database is unavailable.
}

try {
    $ucsConditions = [];
    $ucsParams     = [];
    if ($ucsYearId > 0) {
        $ucsConditions[] = 'cl.academic_year_id = :ay';
        $ucsParams[':ay'] = $ucsYearId;
    }

    if ($ucsQuery !== '') {
        $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
        $ucsConditions[] = '(st.name LIKE :q_name OR st.student_id LIKE :q_id OR st.roll_number LIKE :q_roll OR st.email LIKE :q_email)';
        $ucsParams[':q_name']  = '%' . $ucsEscaped . '%';
        $ucsParams[':q_id']    = '%' . $ucsEscaped . '%';
        $ucsParams[':q_roll']  = '%' . $ucsEscaped . '%';
        $ucsParams[':q_email'] = '%' . $ucsEscaped . '%';
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
        $ucsConditions[] = 'st.classroom_id = :classroom_id';
        $ucsParams[':classroom_id'] = $ucsClassroomId;
    }

    if ($ucsStatus !== '') {
        $ucsConditions[] = 'st.status = :status';
        $ucsParams[':status'] = (int) $ucsStatus;
    }

    if ($ucsAcademicStatus !== '') {
        $ucsConditions[] = 'st.student_status = :academic_status';
        $ucsParams[':academic_status'] = $ucsAcademicStatus;
    }

    $ucsWhereSql = count($ucsConditions) > 0 ? ' WHERE ' . implode(' AND ', $ucsConditions) : '';
    $ucsStmt = $pdo->prepare(
        "SELECT st.id, st.student_id, st.roll_number, st.name, st.email, st.status,
                st.student_status, st.graduation_year, st.created_at,
                cl.classroom_name, cl.year_level, cl.section,
                m.name AS major_name
         FROM students st
         JOIN classrooms cl ON cl.id = st.classroom_id
         JOIN majors m ON m.id = cl.major_id"
        . $ucsWhereSql . "
         ORDER BY cl.year_level ASC, (cl.section IS NULL) ASC, cl.section ASC, st.name ASC"
    );
    $ucsStmt->execute($ucsParams);
    $ucsStudents = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsStudents = [];
}

$ucsStudentCount = count($ucsStudents);
$ucsResultLabel  = $ucsStudentCount . ' student' . ($ucsStudentCount === 1 ? '' : 's');
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
        <p class="mt-2 text-sm text-gray-500">Students are managed under the active academic year. Activate an academic year to get started.</p>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            Manage Academic Years
        </a>
    </div>
    <?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<!-- Search + Filters toolbar -->
<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php'); ?>" role="search">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search students..." aria-label="Search students"
                       class="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>

            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14"></path>
                    <path d="M12 5v14"></path>
                </svg>
                Add Student
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

            <div>
                <label for="status-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                <select id="status-filter" name="status" onchange="this.form.submit()" aria-label="Filter by status"
                        class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Status</option>
                    <option value="1" <?php echo $ucsStatus === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $ucsStatus === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <div>
                <label for="academic-status-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Academic Status</label>
                <select id="academic-status-filter" name="academic_status" onchange="this.form.submit()" aria-label="Filter by academic status"
                        class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Academic Statuses</option>
                    <option value="active" <?php echo $ucsAcademicStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="graduated" <?php echo $ucsAcademicStatus === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                </select>
            </div>

            <div class="flex items-end">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php'); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400 sm:w-auto">
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

<!-- Student summary -->
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-sm shadow-blue-600/20" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['total'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Total Students</p>
        </div>
    </div>
    <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <path d="m9 11 3 3L22 4"></path>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['active'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Active Students</p>
        </div>
    </div>
    <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['inactive'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Inactive Students</p>
        </div>
    </div>
    <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
                <path d="M22 10v6"></path>
                <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['graduated'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Graduated Students</p>
        </div>
    </div>
</div>

<!-- Student table -->
<div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
    <div class="flex flex-col gap-1 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Student List</h2>
            <p class="mt-1 text-sm text-gray-500"><?php echo $ucsResultLabel; ?></p>
        </div>
    </div>

    <?php if (empty($ucsStudents)): ?>
        <div class="p-10 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-800">No students found</h3>
            <p class="mt-2 text-sm text-gray-500">
                <?php if ($ucsHasFilters): ?>
                    Try changing your search or filter criteria.
                <?php elseif ($ucsSelectedYearStatus !== '' && $ucsSelectedYearStatus !== 'Active'): ?>
                    No students were enrolled for this academic year.
                <?php else: ?>
                    Add your first student to get started.
                <?php endif; ?>
            </p>
            <?php if ($ucsHasFilters): ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Clear Filters
                </a>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/create.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Add Student
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Student</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Roll Number</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Major</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Year Level</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Section</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Academic Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($ucsStudents as $ucsStudent): ?>
                        <?php
                        $ucsStudentName = (string) $ucsStudent['name'];
                        $ucsJsName      = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsStudentName);
                        ?>
                        <tr class="transition-colors hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="max-w-[15rem] truncate font-semibold text-gray-900"><?php echo htmlspecialchars($ucsStudentName); ?></p>
                                        <p class="max-w-[15rem] truncate text-xs text-gray-500"><?php echo htmlspecialchars((string) $ucsStudent['email']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs font-semibold text-gray-800 ring-1 ring-gray-200"><?php echo htmlspecialchars((string) $ucsStudent['roll_number']); ?></span>
                                <span class="mt-1 block text-xs text-gray-400">ID <?php echo htmlspecialchars((string) $ucsStudent['student_id']); ?></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-gray-600"><?php echo htmlspecialchars((string) $ucsStudent['major_name']); ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-gray-600"><?php echo htmlspecialchars((string) $ucsStudent['year_level']); ?></td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <?php if (!empty($ucsStudent['section'])): ?>
                                        <span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-gray-800 ring-1 ring-gray-200"><?php echo htmlspecialchars((string) $ucsStudent['section']); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                    <span class="text-xs text-gray-500"><?php echo htmlspecialchars((string) $ucsStudent['classroom_name']); ?></span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <?php if ((int) $ucsStudent['status'] === 1): ?>
                                    <span class="inline-flex items-center rounded-full bg-blue-600 px-2.5 py-0.5 text-xs font-semibold text-white">Active</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-500">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <?php if ($ucsStudent['student_status'] === 'graduated'): ?>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Graduated</span>
                                        <?php if (!empty($ucsStudent['graduation_year'])): ?>
                                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars((string) $ucsStudent['graduation_year']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <?php if ((int) $ucsStudent['status'] === 1 && $ucsStudent['student_status'] !== 'graduated'): ?>
                                        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/graduate.php?id=' . (int) $ucsStudent['id']); ?>" title="Confirm graduation for <?php echo htmlspecialchars($ucsStudentName); ?>" aria-label="Confirm graduation for <?php echo htmlspecialchars($ucsStudentName); ?>"
                                           class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-emerald-200 bg-white px-2.5 text-xs font-semibold text-emerald-700 transition-colors duration-150 hover:bg-emerald-50 hover:text-emerald-800 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
                                                <path d="M22 10v6"></path>
                                                <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
                                            </svg>
                                            Graduate
                                        </a>
                                    <?php endif; ?>

                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/edit.php?id=' . (int) $ucsStudent['id']); ?>" title="Edit <?php echo htmlspecialchars($ucsStudentName); ?>" aria-label="Edit <?php echo htmlspecialchars($ucsStudentName); ?>"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                            <path d="m15 5 4 4"></path>
                                        </svg>
                                    </a>

                                    <?php if ((int) $ucsStudent['status'] !== 1): ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/student-activate.php'); ?>" class="inline-flex">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsStudent['id']; ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                                Activate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/student-deactivate.php'); ?>" class="inline-flex"
                                              onsubmit="return confirm('Deactivate student &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsStudent['id']; ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                                                Deactivate
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/student-delete.php'); ?>" class="inline-flex"
                                          onsubmit="return confirm('Delete student &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsStudent['id']; ?>">
                                        <button type="submit" title="Delete <?php echo htmlspecialchars($ucsStudentName); ?>" aria-label="Delete <?php echo htmlspecialchars($ucsStudentName); ?>"
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