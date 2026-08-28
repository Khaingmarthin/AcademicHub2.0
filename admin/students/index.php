<?php
/**
 * Admin Students module - list.
 *
 * Students are displayed in separate Active / Graduated tabs.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/classroom-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageSubtitle = 'Manage current students.';
$activeNav    = 'students';

$ucsFlash = $_SESSION['student_flash'] ?? null;
unset($_SESSION['student_flash']);

// Academic year context.
$ucsActiveYear     = ucs_admin_active_academic_year($pdo);
$ucsActiveYearId   = $ucsActiveYear !== null ? (int) $ucsActiveYear['id'] : 0;
$ucsActiveYearName = $ucsActiveYear !== null ? (string) $ucsActiveYear['year_name'] : '';

$ucsAcademicYears  = ucs_admin_academic_years($pdo);
$ucsYearIds        = array_map('intval', array_column($ucsAcademicYears, 'id'));

$ucsYearId = filter_var($_GET['year'] ?? '', FILTER_VALIDATE_INT);
if ($ucsYearId === false || ($ucsYearId !== 0 && !in_array($ucsYearId, $ucsYearIds, true))) {
    $ucsYearId = 0;
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

// Filters.
$ucsQuery      = trim((string) ($_GET['q'] ?? ''));
$ucsYearLevel  = trim((string) ($_GET['year_level'] ?? ''));
$ucsMajorId    = filter_var($_GET['major'] ?? '', FILTER_VALIDATE_INT);
$ucsClassroomId = filter_var($_GET['classroom'] ?? '', FILTER_VALIDATE_INT);

if (!in_array($ucsYearLevel, CLASSROOM_YEAR_LEVELS, true)) {
    $ucsYearLevel = '';
}

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

$ucsHasFilters = $ucsQuery !== '' || $ucsYearLevel !== '' || $ucsMajorId > 0 || $ucsClassroomId > 0 || $ucsYearId > 0;

// Data query.
$ucsStudents = [];
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

    $ucsWhereSql = count($ucsConditions) > 0 ? ' WHERE ' . implode(' AND ', $ucsConditions) : '';
    $ucsStmt = $pdo->prepare(
        "SELECT st.id, st.student_id, st.roll_number, st.name, st.email, st.status,
                st.student_status, st.graduation_year, st.created_at,
                cl.classroom_name, cl.year_level, cl.section,
                m.name AS major_name
         FROM students st
         JOIN classrooms cl ON cl.id = st.classroom_id
         LEFT JOIN majors m ON m.id = cl.major_id"
        . $ucsWhereSql . "
         ORDER BY cl.year_level ASC, (cl.section IS NULL) ASC, cl.section ASC, st.name ASC"
    );
    $ucsStmt->execute($ucsParams);
    $ucsStudents = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsStudents = [];
}

// Split students by status for tabs.
$ucsActiveStudents    = array_values(array_filter($ucsStudents, fn($s) => $s['student_status'] === 'active'));
$ucsGraduatedStudents = array_values(array_filter($ucsStudents, fn($s) => $s['student_status'] === 'graduated'));
$ucsActiveCount    = count($ucsActiveStudents);
$ucsGraduatedCount = count($ucsGraduatedStudents);
$ucsTotalCount     = count($ucsStudents);

$hidePageHeader = true;
require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<style>
    .tab-active-indicator{transition:transform .25s cubic-bezier(.4,0,.2,1),width .25s cubic-bezier(.4,0,.2,1)}
    .stat-card{transition:transform .15s ease,box-shadow .15s ease}
    .stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
    .stat-card.active{ring:2px}
</style>

<!-- Page Header -->
<div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Student Management</h1>
        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($pageSubtitle); ?></p>
    </div>
    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/create.php'); ?>" class="mt-3 inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 sm:mt-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
        Add Student
    </a>
</div>

<!-- Summary Stats — Clickable -->
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <button type="button" onclick="switchTab('active')" id="stat-active"
            class="stat-card group rounded-xl border border-emerald-200 bg-white p-5 shadow-sm text-left focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 tab-stat" data-tab="active">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 transition-colors group-hover:bg-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="min-w-0">
                <p class="text-3xl font-bold text-emerald-700 tab-count" data-tab="active"><?php echo number_format($ucsActiveCount); ?></p>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Students</p>
            </div>
            <div class="ml-auto">
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Current</span>
            </div>
        </div>
    </button>
    <button type="button" onclick="switchTab('graduated')" id="stat-graduated"
            class="stat-card group rounded-xl border border-slate-200 bg-white p-5 shadow-sm text-left focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 tab-stat" data-tab="graduated">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 transition-colors group-hover:bg-blue-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 6 3 12 0v-5"></path></svg>
            </div>
            <div class="min-w-0">
                <p class="text-3xl font-bold text-blue-700 tab-count" data-tab="graduated"><?php echo number_format($ucsGraduatedCount); ?></p>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Graduated Students</p>
            </div>
            <div class="ml-auto">
                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20">Archive</span>
            </div>
        </div>
    </button>
</div>

<?php if ($ucsFlash !== null): ?>
    <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'; ?> mb-6 rounded-lg px-4 py-3" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
        <p class="flex items-start gap-2 text-sm font-medium">
            <?php if ($ucsFlash['type'] === 'error'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
            <?php endif; ?>
            <?php echo htmlspecialchars($ucsFlash['message']); ?>
        </p>
    </div>
<?php endif; ?>

<?php if ($ucsActiveYear === null): ?>
    <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        </div>
        <h2 class="mt-4 text-lg font-semibold text-slate-900">No active academic year</h2>
        <p class="mt-2 text-sm text-slate-500">Students are managed under the active academic year. Activate an academic year to get started.</p>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Manage Academic Years
        </a>
    </div>
    <?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<!-- Search + Filters -->
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php'); ?>" role="search" class="flex flex-col gap-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full lg:w-auto">
                <div class="relative w-full sm:w-64 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                    <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search by name, ID, roll, or email..." aria-label="Search students"
                           class="block w-full rounded-lg border border-slate-300 bg-slate-50 py-2 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-1 shrink-0">
                    Search
                </button>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/create.php'); ?>" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                    Add Student
                </a>
            </div>
            <?php if ($ucsHasFilters): ?>
                <div class="flex shrink-0">
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php'); ?>" class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-1">
                        Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="h-px w-full bg-slate-100"></div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="year-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Year</label>
                <select id="year-filter" name="year" onchange="this.form.submit()" aria-label="Filter by academic year"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Years</option>
                    <?php foreach ($ucsAcademicYears as $ucsYearOption): ?>
                        <option value="<?php echo (int) $ucsYearOption['id']; ?>" <?php echo $ucsYearId === (int) $ucsYearOption['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsYearOption['year_name']); ?></option>
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
                <label for="classroom-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Classroom</label>
                <select id="classroom-filter" name="classroom" onchange="this.form.submit()" aria-label="Filter by classroom"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Classrooms</option>
                    <?php foreach ($ucsClassrooms as $ucsClassroomOption): ?>
                        <option value="<?php echo (int) $ucsClassroomOption['id']; ?>" <?php echo (int) $ucsClassroomId === (int) $ucsClassroomOption['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsClassroomOption['classroom_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
</div>

<!-- Tab Bar -->
<div class="mb-6">
    <div class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-100/60 p-0.5 shadow-sm">
        <button type="button" onclick="switchTab('active')" id="tab-btn-active"
                class="tab-btn relative inline-flex items-center justify-center gap-1 rounded-md px-2.5 py-1 text-[11px] font-semibold transition-all focus:outline-none"
                data-tab="active">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            Active
            <span class="inline-flex items-center justify-center rounded-full px-1 py-px text-[9px] font-bold leading-none tab-badge" data-tab="active"><?php echo $ucsActiveCount; ?></span>
        </button>
        <button type="button" onclick="switchTab('graduated')" id="tab-btn-graduated"
                class="tab-btn relative inline-flex items-center justify-center gap-1 rounded-md px-2.5 py-1 text-[11px] font-semibold transition-all focus:outline-none"
                data-tab="graduated">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 6 3 12 0v-5"></path></svg>
            Graduated
            <span class="inline-flex items-center justify-center rounded-full px-1 py-px text-[9px] font-bold leading-none tab-badge" data-tab="graduated"><?php echo $ucsGraduatedCount; ?></span>
        </button>
    </div>
</div>

<!-- ======================== ACTIVE TAB ======================== -->
<div id="panel-active" class="tab-panel">
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50/80 to-white px-5 py-4">
        <div class="flex items-center gap-2">
            <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <h2 class="text-sm font-semibold text-slate-900">Active Students</h2>
        </div>
        <p class="mt-0.5 text-xs text-slate-500"><?php echo $ucsActiveCount . ' student' . ($ucsActiveCount === 1 ? '' : 's') . ($ucsHasFilters ? ' matching' : ''); ?></p>
    </div>

    <?php if (empty($ucsActiveStudents)): ?>
        <div class="flex flex-col items-center justify-center p-12 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-slate-900">
                <?php echo $ucsHasFilters ? 'No matching active students' : 'No active students yet'; ?>
            </h3>
            <p class="mt-2 max-w-sm text-sm text-slate-500">
                <?php echo $ucsHasFilters ? 'Try adjusting your search or filter criteria.' : 'Active students will appear here once they are enrolled.'; ?>
            </p>
            <div class="mt-6 flex items-center gap-3">
                <?php if ($ucsHasFilters): ?>
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php'); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        Clear Filters
                    </a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/create.php'); ?>" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                        Add Student
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="w-full overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-emerald-100 bg-emerald-50/50">
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Student</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Major</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Year / Class</th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($ucsActiveStudents as $ucsStudent): ?>
                        <?php echo renderStudentRow($ucsStudent); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>

<!-- ======================== GRADUATED TAB ======================== -->
<div id="panel-graduated" class="tab-panel hidden">
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-blue-100 bg-gradient-to-r from-blue-50/80 to-white px-5 py-4">
        <div class="flex items-center gap-2">
            <div class="h-2 w-2 rounded-full bg-blue-500"></div>
            <h2 class="text-sm font-semibold text-slate-900">Graduated Students</h2>
        </div>
        <p class="mt-0.5 text-xs text-slate-500"><?php echo $ucsGraduatedCount . ' student' . ($ucsGraduatedCount === 1 ? '' : 's') . ($ucsHasFilters ? ' matching' : ''); ?></p>
    </div>

    <?php if (empty($ucsGraduatedStudents)): ?>
        <div class="flex flex-col items-center justify-center p-12 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 6 3 12 0v-5"></path>
                </svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-slate-900">
                <?php echo $ucsHasFilters ? 'No matching graduated students' : 'No graduated students yet'; ?>
            </h3>
            <p class="mt-2 max-w-sm text-sm text-slate-500">
                <?php echo $ucsHasFilters ? 'Try adjusting your search or filter criteria.' : 'Students who graduate will appear here.'; ?>
            </p>
            <?php if ($ucsHasFilters): ?>
                <div class="mt-6">
                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php'); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="w-full overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-blue-100 bg-blue-50/50">
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-blue-700">Student</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-blue-700">Major</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-blue-700">Year / Class</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-blue-700">Grad. Year</th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-blue-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($ucsGraduatedStudents as $ucsStudent): ?>
                        <?php echo renderStudentRow($ucsStudent, true); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>

<!-- View Student Modal -->
<div id="studentModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="studentModalTitle">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeStudentModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-2xl rounded-xl border border-slate-200 bg-white shadow-xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="studentModalTitle" class="text-base font-semibold text-slate-900">Student Details</h3>
                <button type="button" onclick="closeStudentModal()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close modal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
                </button>
            </div>
            <div class="px-6 py-5">
                <div id="studentModalAvatar" class="mb-5 flex items-center gap-4">
                    <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-blue-50 text-lg font-bold text-blue-600 ring-1 ring-inset ring-blue-100"></span>
                    <div class="min-w-0">
                        <p id="modalStudentName" class="text-xl font-semibold text-slate-900 truncate"></p>
                        <p id="modalStudentId" class="text-sm text-slate-500"></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Personal Information</h4>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">Roll Number</dt>
                                <dd id="modalRollNumber" class="mt-0.5 font-medium text-slate-900"></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Email</dt>
                                <dd id="modalEmail" class="mt-0.5 font-medium text-slate-900 truncate"></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Account Status</dt>
                                <dd id="modalStatus" class="mt-0.5"></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Academic Status</dt>
                                <dd id="modalStudentStatus" class="mt-0.5"></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Information</h4>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">Major</dt>
                                <dd id="modalMajor" class="mt-0.5 font-medium text-slate-900"></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Year Level</dt>
                                <dd id="modalYearLevel" class="mt-0.5 font-medium text-slate-900"></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Section</dt>
                                <dd id="modalSection" class="mt-0.5 font-medium text-slate-900"></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Classroom</dt>
                                <dd id="modalClassroom" class="mt-0.5 font-medium text-slate-900"></dd>
                            </div>
                            <div id="modalGraduationYearWrap">
                                <dt class="text-slate-500">Graduation Year</dt>
                                <dd id="modalGraduationYear" class="mt-0.5 font-medium text-slate-900"></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button type="button" onclick="closeStudentModal()" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    /* ---------- Tab switching ---------- */
    var hash = window.location.hash.replace('#', '');
    var currentTab = (hash === 'graduated') ? 'graduated' : 'active';

    function applyTab(tab) {
        currentTab = tab;

        document.getElementById('panel-active').classList.toggle('hidden', tab !== 'active');
        document.getElementById('panel-graduated').classList.toggle('hidden', tab !== 'graduated');

        document.getElementById('tab-btn-active').classList.toggle('text-emerald-700', tab === 'active');
        document.getElementById('tab-btn-active').classList.toggle('bg-gradient-to-br', tab === 'active');
        document.getElementById('tab-btn-active').classList.toggle('from-emerald-50', tab === 'active');
        document.getElementById('tab-btn-active').classList.toggle('to-emerald-100/80', tab === 'active');
        document.getElementById('tab-btn-active').classList.toggle('shadow-sm', tab === 'active');
        document.getElementById('tab-btn-active').classList.toggle('text-slate-500', tab !== 'active');
        document.getElementById('tab-btn-active').classList.toggle('hover:text-slate-700', tab !== 'active');
        document.getElementById('tab-btn-active').classList.toggle('hover:bg-slate-50', tab !== 'active');

        document.getElementById('tab-btn-graduated').classList.toggle('text-blue-700', tab === 'graduated');
        document.getElementById('tab-btn-graduated').classList.toggle('bg-gradient-to-br', tab === 'graduated');
        document.getElementById('tab-btn-graduated').classList.toggle('from-blue-50', tab === 'graduated');
        document.getElementById('tab-btn-graduated').classList.toggle('to-blue-100/80', tab === 'graduated');
        document.getElementById('tab-btn-graduated').classList.toggle('shadow-sm', tab === 'graduated');
        document.getElementById('tab-btn-graduated').classList.toggle('text-slate-500', tab !== 'graduated');
        document.getElementById('tab-btn-graduated').classList.toggle('hover:text-slate-700', tab !== 'graduated');
        document.getElementById('tab-btn-graduated').classList.toggle('hover:bg-slate-50', tab !== 'graduated');

        var badges = document.querySelectorAll('.tab-badge');
        badges.forEach(function(b) {
            var bTab = b.getAttribute('data-tab');
            if (bTab === 'active') {
                b.classList.toggle('bg-emerald-100', tab === 'active');
                b.classList.toggle('text-emerald-700', tab === 'active');
                b.classList.toggle('bg-slate-100', tab !== 'active');
                b.classList.toggle('text-slate-500', tab !== 'active');
            } else {
                b.classList.toggle('bg-blue-100', tab === 'graduated');
                b.classList.toggle('text-blue-700', tab === 'graduated');
                b.classList.toggle('bg-slate-100', tab !== 'graduated');
                b.classList.toggle('text-slate-500', tab !== 'graduated');
            }
        });

        var stats = document.querySelectorAll('.tab-stat');
        stats.forEach(function(s) {
            var sTab = s.getAttribute('data-tab');
            if (sTab === 'active') {
                s.classList.toggle('border-emerald-300', tab === 'active');
                s.classList.toggle('ring-2', tab === 'active');
                s.classList.toggle('ring-emerald-500', tab === 'active');
                s.classList.toggle('border-slate-200', tab !== 'active');
            } else {
                s.classList.toggle('border-blue-300', tab === 'graduated');
                s.classList.toggle('ring-2', tab === 'graduated');
                s.classList.toggle('ring-blue-500', tab === 'graduated');
                s.classList.toggle('border-slate-200', tab !== 'graduated');
            }
        });

        window.history.replaceState(null, null, '#' + tab);
    }

    window.switchTab = function(tab) {
        applyTab(tab);
    };

    applyTab(currentTab);

    /* ---------- Modal ---------- */
    var modal = document.getElementById('studentModal');
    if (!modal) return;

    function getInitials(name) {
        var parts = (name || '').trim().split(/\s+/);
        var initials = '';
        for (var i = 0; i < parts.length && initials.length < 2; i++) {
            initials += (parts[i].charAt(0) || '').toUpperCase();
        }
        return initials;
    }

    window.openStudentModal = function(btn) {
        var data;
        try {
            data = JSON.parse(btn.getAttribute('data-student'));
        } catch (e) {
            return;
        }

        var avatar = document.getElementById('studentModalAvatar');
        if (avatar) {
            var span = avatar.querySelector('span');
            if (span) span.textContent = getInitials(data.name);
        }

        document.getElementById('modalStudentName').textContent = data.name || '';
        document.getElementById('modalStudentId').textContent = data.student_id || '';
        document.getElementById('modalRollNumber').textContent = data.roll_number || '\u2014';
        document.getElementById('modalEmail').textContent = data.email || '\u2014';
        document.getElementById('modalMajor').textContent = data.major || '\u2014';
        document.getElementById('modalYearLevel').textContent = data.year_level || '\u2014';
        document.getElementById('modalSection').textContent = data.section || '\u2014';

        var classroomEl = document.getElementById('modalClassroom');
        classroomEl.textContent = data.classroom || 'Not Assigned';
        if (!data.classroom) {
            classroomEl.classList.remove('font-medium', 'text-slate-900');
            classroomEl.classList.add('italic', 'text-slate-400');
        } else {
            classroomEl.classList.remove('italic', 'text-slate-400');
            classroomEl.classList.add('font-medium', 'text-slate-900');
        }

        var statusEl = document.getElementById('modalStatus');
        if (data.status === 'Active') {
            statusEl.innerHTML = '<span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>';
        } else {
            statusEl.innerHTML = '<span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20">Graduated</span>';
        }

        var studentStatusEl = document.getElementById('modalStudentStatus');
        if (data.student_status === 'Active') {
            studentStatusEl.innerHTML = '<span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>';
        } else {
            studentStatusEl.innerHTML = '<span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20">' + (data.student_status || 'Graduated') + '</span>';
        }

        var gradWrap = document.getElementById('modalGraduationYearWrap');
        if (data.graduation_year) {
            gradWrap.style.display = '';
            document.getElementById('modalGraduationYear').textContent = data.graduation_year;
        } else {
            gradWrap.style.display = 'none';
        }

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    window.closeStudentModal = function() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            window.closeStudentModal();
        }
    });
})();
</script>
<?php
/**
 * Render a single student table row.
 */
function renderStudentRow(array $ucsStudent, bool $showGradYear = false): string
{
    $ucsStudentName = (string) $ucsStudent['name'];
    $ucsStudentJson = htmlspecialchars(json_encode([
        'name'           => $ucsStudent['name'],
        'student_id'     => $ucsStudent['student_id'],
        'roll_number'    => $ucsStudent['roll_number'],
        'email'          => $ucsStudent['email'],
        'major'          => $ucsStudent['major_name'],
        'year_level'     => $ucsStudent['year_level'],
        'section'        => $ucsStudent['section'] ?? '',
        'classroom'      => $ucsStudent['classroom_name'] ?? '',
        'status'         => $ucsStudent['student_status'] === 'active' ? 'Active' : 'Graduated',
        'student_status' => ucfirst($ucsStudent['student_status']),
        'graduation_year'=> $ucsStudent['graduation_year'] ?? '',
    ], JSON_UNESCAPED_SLASHES));

    $ucsInitials = '';
    $ucsNameParts = explode(' ', trim($ucsStudentName));
    foreach ($ucsNameParts as $ucsPart) {
        $ucsInitials .= mb_strtoupper(mb_substr($ucsPart, 0, 1));
        if (mb_strlen($ucsInitials) >= 2) break;
    }

    $isGrad = $ucsStudent['student_status'] === 'graduated';
    $avatarBg  = $isGrad ? 'bg-blue-50' : 'bg-emerald-50';
    $avatarTxt = $isGrad ? 'text-blue-600' : 'text-emerald-600';

    $html = '<tr class="transition-colors hover:bg-slate-50/50">';
    // Student
    $html .= '<td class="px-4 py-3"><div class="flex items-center gap-3">';
    $html .= '<span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full ' . $avatarBg . ' text-[11px] font-bold ' . $avatarTxt . '" aria-hidden="true">' . htmlspecialchars($ucsInitials) . '</span>';
    $html .= '<div class="min-w-0"><p class="font-semibold text-slate-900 truncate max-w-[14rem]">' . htmlspecialchars($ucsStudentName) . '</p>';
    $html .= '<p class="text-[11px] text-slate-500 truncate max-w-[14rem]">' . htmlspecialchars((string) $ucsStudent['student_id']) . '</p></div>';
    $html .= '</div></td>';
    // Major
    $html .= '<td class="px-4 py-3 text-xs text-slate-600">' . htmlspecialchars((string) $ucsStudent['major_name']) . '</td>';
    // Year / Class
    $html .= '<td class="px-4 py-3"><p class="text-slate-900 font-medium text-xs">' . htmlspecialchars((string) $ucsStudent['year_level']) . '</p>';
    if (!empty($ucsStudent['section'])) {
        $html .= '<p class="text-[11px] text-slate-500">Sec ' . htmlspecialchars((string) $ucsStudent['section']) . '</p>';
    }
    $html .= '</td>';
    // Graduation Year (graduated tab only)
    if ($showGradYear) {
        $html .= '<td class="px-4 py-3 text-xs text-slate-600">' . htmlspecialchars((string) ($ucsStudent['graduation_year'] ?? '')) . '</td>';
    }
    // Actions
    $html .= '<td class="px-4 py-3 text-right"><div class="flex items-center justify-end gap-1.5">';
    $html .= '<button type="button" onclick="openStudentModal(this)" data-student="' . $ucsStudentJson . '" class="inline-flex items-center gap-1 rounded-md bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600 transition-colors hover:bg-slate-100 focus:outline-none" title="View ' . htmlspecialchars($ucsStudentName) . '">';
    $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>View</button>';
    $html .= '<a href="' . htmlspecialchars(ROOT_URL . '/admin/students/edit.php?id=' . (int) $ucsStudent['id']) . '" class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-600 transition-colors hover:bg-amber-100 focus:outline-none" title="Edit ' . htmlspecialchars($ucsStudentName) . '">';
    $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path><path d="m15 5 4 4"></path></svg>Edit</a>';
    $html .= '<form method="post" action="' . htmlspecialchars(ROOT_URL . '/actions/admin/student-delete.php') . '" class="inline-flex" onsubmit="return confirm(\'Are you sure you want to delete this student? This action cannot be undone.\');">';
    $html .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(admin_csrf_token()) . '">';
    $html .= '<input type="hidden" name="id" value="' . (int) $ucsStudent['id'] . '">';
    $html .= '<button type="submit" class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-500 transition-colors hover:bg-red-100 hover:text-red-600 focus:outline-none" title="Delete ' . htmlspecialchars($ucsStudentName) . '">';
    $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></button>';
    $html .= '</form>';
    $html .= '</div></td>';
    $html .= '</tr>';

    return $html;
}
?>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
