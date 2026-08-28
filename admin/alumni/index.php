<?php
/**
 * Admin Alumni module - list, search, filter & verify.
 *
 * Lists alumni profiles joined with the existing student identity. Admin can
 * search, filter, view, edit and manage verification (verify / reject /
 * activate / deactivate). Only non-sensitive information is shown; student
 * passwords and private data are never queried here.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Alumni';
$pageSubtitle = 'Manage alumni profiles and verification.';
$activeNav    = 'alumni';

$ucsFlash = $_SESSION['alumni_flash'] ?? null;
unset($_SESSION['alumni_flash']);

// ---------------------------------------------------------------------
// Search + filters
// ---------------------------------------------------------------------
$ucsQuery          = trim((string) ($_GET['q'] ?? ''));
$ucsGraduationYear = filter_var($_GET['graduation_year'] ?? '', FILTER_VALIDATE_INT);
$ucsMajorId        = filter_var($_GET['major'] ?? '', FILTER_VALIDATE_INT);
$ucsStatus         = in_array((string) ($_GET['status'] ?? ''), ALUMNI_VERIFICATION_STATUSES, true) ? (string) $_GET['status'] : '';
$ucsShow           = in_array((string) ($_GET['show'] ?? ''), ['10', '25', '50'], true) ? (int) $_GET['show'] : 10;
$ucsPage           = max(1, (int) ($_GET['page'] ?? 1));

// Filter option lists.
$ucsMajors = ucs_admin_majors($pdo);
$ucsMajorIds = array_map('intval', array_column($ucsMajors, 'id'));
if ($ucsMajorId === false || !in_array($ucsMajorId, $ucsMajorIds, true)) {
    $ucsMajorId = 0;
}

$ucsGraduationYears = [];
try {
    $ucsGraduationYears = $pdo->query(
        "SELECT DISTINCT s.graduation_year
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         WHERE s.graduation_year IS NOT NULL
         ORDER BY s.graduation_year DESC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $ucsGraduationYears = [];
}
$ucsGraduationYearIds = array_map('intval', $ucsGraduationYears);
if ($ucsGraduationYear === false || !in_array($ucsGraduationYear, $ucsGraduationYearIds, true)) {
    $ucsGraduationYear = 0;
}

$ucsHasFilters = $ucsQuery !== '' || $ucsGraduationYear > 0 || $ucsMajorId > 0 || $ucsStatus !== '';

// ---------------------------------------------------------------------
// WHERE clause
// ---------------------------------------------------------------------
$ucsWhere  = [];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[] = '(s.name LIKE :q OR s.roll_number LIKE :q OR ap.company LIKE :q OR ap.current_job LIKE :q)';
    $ucsParams[':q'] = '%' . $ucsEscaped . '%';
}
if ($ucsGraduationYear > 0) {
    $ucsWhere[] = 's.graduation_year = :graduation_year';
    $ucsParams[':graduation_year'] = $ucsGraduationYear;
}
if ($ucsMajorId > 0) {
    $ucsWhere[] = 'cl.major_id = :major_id';
    $ucsParams[':major_id'] = $ucsMajorId;
}
if ($ucsStatus !== '') {
    $ucsWhere[] = 'ap.verification_status = :status';
    $ucsParams[':status'] = $ucsStatus;
}

$ucsWhereSql = count($ucsWhere) > 0 ? ' WHERE ' . implode(' AND ', $ucsWhere) : '';

// ---------------------------------------------------------------------
// Summary across all statuses (not scoped by filters).
// ---------------------------------------------------------------------
$ucsSummary = ['total' => 0, 'pending' => 0, 'verified' => 0, 'rejected' => 0];
try {
    $ucsStmt = $pdo->query(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(ap.verification_status = 'pending'), 0) AS pending,
                COALESCE(SUM(ap.verification_status = 'verified'), 0) AS verified,
                COALESCE(SUM(ap.verification_status = 'rejected'), 0) AS rejected
         FROM alumni_profiles ap"
    );
    $ucsRow = $ucsStmt->fetch() ?: [];
    $ucsSummary = [
        'total'    => (int) ($ucsRow['total'] ?? 0),
        'pending'  => (int) ($ucsRow['pending'] ?? 0),
        'verified' => (int) ($ucsRow['verified'] ?? 0),
        'rejected' => (int) ($ucsRow['rejected'] ?? 0),
    ];
} catch (PDOException $e) {
    // Keep zeroed summary when the database is unavailable.
}

// ---------------------------------------------------------------------
// Paginated list.
// ---------------------------------------------------------------------
$ucsProfiles  = [];
$ucsTotal     = 0;
$ucsTotalPages = 1;
try {
    $ucsBaseSql = "FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         LEFT JOIN classrooms cl ON cl.id = s.classroom_id
         LEFT JOIN majors m ON m.id = cl.major_id"
        . $ucsWhereSql;

    $ucsCountStmt = $pdo->prepare("SELECT COUNT(*) " . $ucsBaseSql);
    $ucsCountStmt->execute($ucsParams);
    $ucsTotal = (int) $ucsCountStmt->fetchColumn();

    $ucsTotalPages = max(1, (int) ceil($ucsTotal / $ucsShow));
    $ucsPage       = min($ucsPage, $ucsTotalPages);
    $ucsOffset     = ($ucsPage - 1) * $ucsShow;

    $ucsStmt = $pdo->prepare(
        "SELECT ap.id, ap.current_job, ap.company, ap.verification_status,
                ap.created_at,
                s.id AS student_id, s.name AS student_name,
                s.student_id AS student_code, s.roll_number,
                s.status AS account_status, s.student_status, s.graduation_year,
                m.name AS major_name
         "
        . $ucsBaseSql . "
         ORDER BY ap.created_at DESC, s.name ASC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($ucsParams as $ucsKey => $ucsVal) {
        $ucsStmt->bindValue($ucsKey, $ucsVal);
    }
    $ucsStmt->bindValue(':limit', $ucsShow, PDO::PARAM_INT);
    $ucsStmt->bindValue(':offset', $ucsOffset, PDO::PARAM_INT);
    $ucsStmt->execute();
    $ucsProfiles = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsProfiles  = [];
    $ucsTotal     = 0;
    $ucsTotalPages = 1;
}

$ucsResultLabel = $ucsTotal . ' alumn' . ($ucsTotal === 1 ? 'us' : 'i');
if ($ucsHasFilters) {
    $ucsResultLabel .= ' matching your filters';
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

<!-- Search + Add toolbar -->
<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/index.php'); ?>" role="search">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search by name, roll number, company or job..." aria-label="Search alumni"
                       class="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
        </div>

        <div class="mt-5 grid gap-4 border-t border-gray-100 pt-5 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="graduation-year-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Graduation Year</label>
                <select id="graduation-year-filter" name="graduation_year" onchange="this.form.submit()" aria-label="Filter by graduation year"
                        class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Years</option>
                    <?php foreach ($ucsGraduationYears as $ucsYearOption): ?>
                        <option value="<?php echo (int) $ucsYearOption; ?>" <?php echo $ucsGraduationYear === (int) $ucsYearOption ? 'selected' : ''; ?>><?php echo (int) $ucsYearOption; ?></option>
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
                <label for="status-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Verification</label>
                <select id="status-filter" name="status" onchange="this.form.submit()" aria-label="Filter by verification status"
                        class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $ucsStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="verified" <?php echo $ucsStatus === 'verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="rejected" <?php echo $ucsStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <div class="flex items-end">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/index.php'); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400 sm:w-auto">
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

<!-- Summary -->
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-sm shadow-blue-600/20" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
                <path d="M22 10v6"></path>
                <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['total'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Total Profiles</p>
        </div>
    </div>
    <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['pending'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Pending Verification</p>
        </div>
    </div>
    <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <path d="m9 11 3 3L22 4"></path>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['verified'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Verified Alumni</p>
        </div>
    </div>
    <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsSummary['rejected'])); ?></p>
            <p class="truncate text-sm font-medium text-gray-500">Rejected</p>
        </div>
    </div>
</div>

<?php if ($ucsSummary['pending'] > 0): ?>
    <div class="mt-4">
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/applications.php'); ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition-colors duration-150 hover:bg-amber-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            View <?php echo $ucsSummary['pending']; ?> Pending Application<?php echo $ucsSummary['pending'] !== 1 ? 's' : ''; ?>
        </a>
    </div>
<?php endif; ?>

<!-- Alumni table -->
<div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
    <div class="flex flex-col gap-1 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Alumni List</h2>
            <p class="mt-1 text-sm text-gray-500"><?php echo $ucsResultLabel; ?></p>
        </div>
        <form method="get" action="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/index.php'); ?>">
            <div class="flex items-center gap-2">
                <?php
                $ucsPerPageParams = [];
                if ($ucsQuery !== '')          { $ucsPerPageParams['q'] = $ucsQuery; }
                if ($ucsGraduationYear > 0)    { $ucsPerPageParams['graduation_year'] = $ucsGraduationYear; }
                if ($ucsMajorId > 0)           { $ucsPerPageParams['major'] = $ucsMajorId; }
                if ($ucsStatus !== '')         { $ucsPerPageParams['status'] = $ucsStatus; }
                ?>
                <?php foreach ($ucsPerPageParams as $ucsKey => $ucsVal): ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($ucsKey); ?>" value="<?php echo htmlspecialchars((string) $ucsVal); ?>">
                <?php endforeach; ?>
                <label for="show" class="text-xs font-semibold uppercase tracking-wider text-gray-500">Show</label>
                <select id="show" name="show" onchange="this.form.submit()" aria-label="Rows per page"
                        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <?php foreach ([10, 25, 50] as $ucsShowOption): ?>
                        <option value="<?php echo $ucsShowOption; ?>" <?php echo $ucsShow === $ucsShowOption ? 'selected' : ''; ?>><?php echo $ucsShowOption; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if (empty($ucsProfiles)): ?>
        <div class="p-10 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
                <path d="M22 10v6"></path>
                <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-800">No alumni profiles found</h3>
            <p class="mt-2 text-sm text-gray-500">
                <?php if ($ucsHasFilters): ?>
                    Try changing your search or filter criteria.
                <?php else: ?>
                    Create a profile for a graduated student to get started.
                <?php endif; ?>
            </p>
            <?php if ($ucsHasFilters): ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/index.php'); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Clear Filters
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="w-full">
            <table class="w-full table-fixed text-left text-[13px] text-gray-600">
                <thead class="border-b border-gray-100 bg-gray-50/50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="w-1/4 px-4 py-3">Alumnus</th>
                        <th scope="col" class="w-[15%] px-4 py-3">Major</th>
                        <th scope="col" class="w-[10%] px-4 py-3">Class of</th>
                        <th scope="col" class="w-1/5 px-4 py-3">Occupation</th>
                        <th scope="col" class="w-[10%] px-4 py-3 text-center">Status</th>
                        <th scope="col" class="w-[10%] px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($ucsProfiles as $ucsProfile): ?>
                        <?php
                        $ucsProfileName = (string) $ucsProfile['student_name'];
                        $ucsJsName      = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsProfileName);
                        $ucsIsPending   = $ucsProfile['verification_status'] === 'pending';
                        $ucsIsVerified  = $ucsProfile['verification_status'] === 'verified';
                        $ucsIsRejected  = $ucsProfile['verification_status'] === 'rejected';
                        ?>
                        <tr class="transition-colors hover:bg-gray-50/50">
                            <td class="px-4 py-3 truncate">
                                <div class="flex items-center gap-2">
                                    <span class="hidden sm:inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                        </svg>
                                    </span>
                                    <div class="min-w-0 flex-1 truncate">
                                        <p class="truncate font-semibold text-gray-900"><?php echo htmlspecialchars($ucsProfileName); ?></p>
                                        <p class="truncate text-[11px] text-gray-500"><?php echo htmlspecialchars((string) $ucsProfile['roll_number']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 truncate" title="<?php echo htmlspecialchars((string) $ucsProfile['major_name']); ?>">
                                <?php echo htmlspecialchars((string) $ucsProfile['major_name']); ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded bg-gray-100 px-2 py-0.5 font-mono text-xs font-semibold text-gray-800 ring-1 ring-gray-200"><?php echo htmlspecialchars((string) $ucsProfile['graduation_year']); ?></span>
                            </td>
                            <td class="px-4 py-3 truncate">
                                <?php if (!empty($ucsProfile['current_job']) || !empty($ucsProfile['company'])): ?>
                                    <p class="truncate font-medium text-gray-800" title="<?php echo htmlspecialchars((string) $ucsProfile['current_job']); ?>"><?php echo htmlspecialchars((string) $ucsProfile['current_job']); ?></p>
                                    <p class="truncate text-[11px] text-gray-500" title="<?php echo htmlspecialchars((string) $ucsProfile['company']); ?>"><?php echo htmlspecialchars((string) $ucsProfile['company']); ?></p>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($ucsIsVerified): ?>
                                    <span class="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200">Verified</span>
                                <?php elseif ($ucsIsRejected): ?>
                                    <span class="inline-flex items-center rounded bg-red-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-700 ring-1 ring-red-200">Rejected</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-700 ring-1 ring-amber-200">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/view.php?id=' . (int) $ucsProfile['id']); ?>" title="View <?php echo htmlspecialchars($ucsProfileName); ?>"
                                       class="inline-flex h-7 w-7 items-center justify-center rounded bg-blue-50 text-blue-600 transition-colors hover:bg-blue-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    
                                    <?php if ($ucsIsPending): ?>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-verify.php'); ?>" class="inline-flex">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsProfile['id']; ?>">
                                            <button type="submit" title="Verify"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded bg-emerald-50 text-emerald-600 transition-colors hover:bg-emerald-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/edit.php?id=' . (int) $ucsProfile['id']); ?>" title="Edit <?php echo htmlspecialchars($ucsProfileName); ?>"
                                       class="inline-flex h-7 w-7 items-center justify-center rounded bg-amber-50 text-amber-600 transition-colors hover:bg-amber-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                            <path d="m15 5 4 4"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($ucsTotalPages > 1): ?>
    <?php
    $ucsFilterParams = [];
    if ($ucsQuery !== '')          { $ucsFilterParams['q'] = $ucsQuery; }
    if ($ucsGraduationYear > 0)    { $ucsFilterParams['graduation_year'] = $ucsGraduationYear; }
    if ($ucsMajorId > 0)           { $ucsFilterParams['major'] = $ucsMajorId; }
    if ($ucsStatus !== '')         { $ucsFilterParams['status'] = $ucsStatus; }
    $ucsFilterParams['show'] = $ucsShow;

    $ucsPageLinks = [];
    for ($ucsLinkPage = 1; $ucsLinkPage <= $ucsTotalPages; $ucsLinkPage++) {
        if ($ucsTotalPages > 7 && $ucsLinkPage > 1 && $ucsLinkPage < $ucsTotalPages && abs($ucsLinkPage - $ucsPage) > 2) {
            if (!in_array('ellipsis', $ucsPageLinks, true) && end($ucsPageLinks) !== 'ellipsis') {
                $ucsPageLinks[] = 'ellipsis';
            }
            continue;
        }
        $ucsPageLinks[] = $ucsLinkPage;
    }

    $ucsPrevUrl = ROOT_URL . '/admin/alumni/index.php';
    if ($ucsPage > 1) {
        $ucsPrevUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage - 1], $ucsFilterParams));
    }
    $ucsNextUrl = ROOT_URL . '/admin/alumni/index.php';
    if ($ucsPage < $ucsTotalPages) {
        $ucsNextUrl .= '?' . http_build_query(array_merge(['page' => $ucsPage + 1], $ucsFilterParams));
    }
    $ucsPageLinkUrl = ROOT_URL . '/admin/alumni/index.php?';
    ?>
    <nav class="mt-6 flex flex-wrap items-center justify-between gap-3" aria-label="Alumni pagination">
        <a href="<?php echo htmlspecialchars($ucsPrevUrl); ?>"
           class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?> focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Previous
        </a>

        <div class="flex flex-wrap items-center gap-1">
            <?php foreach ($ucsPageLinks as $ucsLinkPage): ?>
                <?php if ($ucsLinkPage === 'ellipsis'): ?>
                    <span class="px-1.5 text-sm text-gray-400">…</span>
                <?php else: ?>
                    <?php if ($ucsLinkPage === $ucsPage): ?>
                        <span aria-current="page" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold text-white"><?php echo $ucsLinkPage; ?></span>
                    <?php else: ?>
                        <a href="<?php echo htmlspecialchars($ucsPageLinkUrl . http_build_query(array_merge(['page' => $ucsLinkPage], $ucsFilterParams))); ?>"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <?php echo $ucsLinkPage; ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <a href="<?php echo htmlspecialchars($ucsNextUrl); ?>"
           class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 <?php echo $ucsPage >= $ucsTotalPages ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50'; ?> focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            Next
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        </a>
    </nav>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>