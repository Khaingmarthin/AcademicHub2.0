<?php
/**
 * Admin Dashboard.
 *
 * Landing page after an admin logs in. Shows the currently active academic
 * year, live module statistics, quick actions for the main admin modules and
 * the most recently published university news. All figures are read from the
 * database; nothing is hard-coded.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

admin_require_login();

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Overview of the university portal.';
$activeNav    = 'dashboard';

// ---------------------------------------------------------------------
// Data: current academic year, module statistics and recent news.
// ---------------------------------------------------------------------
$ucsActiveYear = null;
$ucsStats      = [];
$ucsRecentNews = [];

try {
    $ucsStmt = $pdo->query(
        "SELECT year_name, start_date, end_date, status
         FROM academic_years
         WHERE status = 'Active'
         ORDER BY start_date DESC, id DESC
         LIMIT 1"
    );
    $ucsActiveYear = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsActiveYear = null;
}

$ucsStatDefs = [
    'Faculties'   => 'faculties',
    'Departments' => 'departments',
    'Majors'      => 'majors',
    'Courses'     => 'courses',
    'Classrooms'  => 'classrooms',
    'Students'    => 'students',
];

foreach ($ucsStatDefs as $ucsLabel => $ucsTable) {
    try {
        $ucsStats[$ucsLabel] = (int) $pdo->query(
            "SELECT COUNT(*) FROM `$ucsTable` WHERE status = 1"
        )->fetchColumn();
    } catch (PDOException $e) {
        $ucsStats[$ucsLabel] = 0;
    }
}

try {
    $ucsStmt = $pdo->query(
        "SELECT n.id, n.title, n.published_at, c.name AS category
         FROM news n
         JOIN categories c ON c.id = n.category_id
         WHERE n.status = 'Published'
         ORDER BY n.published_at DESC, n.id DESC
         LIMIT 5"
    );
    $ucsRecentNews = $ucsStmt->fetchAll();
} catch (PDOException $e) {
    $ucsRecentNews = [];
}

// ---------------------------------------------------------------------
// Presentation helpers.
// ---------------------------------------------------------------------
$ucsFmtDate = function ($value) {
    if (empty($value)) {
        return null;
    }
    $ts = strtotime((string) $value);
    return $ts !== false ? date('j M Y', $ts) : (string) $value;
};

$ucsStatIcons = [
    'Faculties'   => '<line x1="3" y1="22" x2="21" y2="22"></line><line x1="6" y1="18" x2="6" y2="11"></line><line x1="10" y1="18" x2="10" y2="11"></line><line x1="14" y1="18" x2="14" y2="11"></line><line x1="18" y1="18" x2="18" y2="11"></line><polygon points="12 2 20 7 4 7"></polygon>',
    'Departments' => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
    'Majors'      => '<circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>',
    'Courses'     => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>',
    'Classrooms'  => '<path d="M22 9 12 5 2 9l10 4 10-4z"></path><path d="M6 11.5V15c0 1.66 2.69 3 6 3s6-1.34 6-3v-3.5"></path><path d="M2 9v5"></path>',
    'Students'    => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
];

$ucsQuickActions = [
    [
        'label' => 'Add Faculty',
        'url'   => '/admin/faculties/index.php',
        'icon'  => '<line x1="3" y1="22" x2="21" y2="22"></line><line x1="6" y1="18" x2="6" y2="11"></line><line x1="10" y1="18" x2="10" y2="11"></line><line x1="14" y1="18" x2="14" y2="11"></line><line x1="18" y1="18" x2="18" y2="11"></line><polygon points="12 2 20 7 4 7"></polygon>',
    ],
    [
        'label' => 'Add Department',
        'url'   => '/admin/departments/index.php',
        'icon'  => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
    ],
    [
        'label' => 'Add Course',
        'url'   => '/admin/courses/index.php',
        'icon'  => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>',
    ],
    [
        'label' => 'Add Student',
        'url'   => '/admin/students/index.php',
        'icon'  => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line>',
    ],
    [
        'label' => 'Add News',
        'url'   => '/admin/news/index.php',
        'icon'  => '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path><path d="M18 14h-8"></path><path d="M15 18h-5"></path><path d="M10 6h8v4h-8V6Z"></path>',
    ],
];

$ucsYearStart = $ucsFmtDate($ucsActiveYear['start_date'] ?? null);
$ucsYearEnd   = $ucsFmtDate($ucsActiveYear['end_date'] ?? null);

require_once __DIR__ . '/../includes/admin-layout-top.php';
?>

<!-- Current Academic Year -->
<section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 p-6 shadow-lg shadow-blue-900/20 sm:p-8" aria-label="Current Academic Year">
    <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
    <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Current Academic Year</p>
            <?php if ($ucsActiveYear !== null): ?>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    <?php echo htmlspecialchars($ucsActiveYear['year_name']); ?>
                </h2>
                <?php if ($ucsYearStart !== null && $ucsYearEnd !== null): ?>
                    <p class="mt-3 text-sm font-medium text-blue-100">
                        <?php echo htmlspecialchars($ucsYearStart . ' – ' . $ucsYearEnd); ?>
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-white">No active academic year</h2>
                <p class="mt-3 text-sm font-medium text-blue-100">
                    Set an academic year to Active in Academic Years to show it here.
                </p>
            <?php endif; ?>
        </div>

        <?php if ($ucsActiveYear !== null): ?>
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                    </svg>
                    Active
                </span>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition-colors duration-150 hover:bg-blue-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-300">
                    Manage
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Academic Overview -->
<section class="mt-8" aria-labelledby="academic-overview-heading">
    <div class="mb-4 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="12" y1="20" x2="12" y2="10"></line>
            <line x1="18" y1="20" x2="18" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="16"></line>
        </svg>
        <h2 id="academic-overview-heading" class="text-base font-semibold text-gray-900">Academic Overview</h2>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($ucsStats as $ucsLabel => $ucsCount): ?>
            <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <?php echo $ucsStatIcons[$ucsLabel] ?? ''; ?>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-extrabold tracking-tight text-gray-900"><?php echo htmlspecialchars(number_format($ucsCount)); ?></p>
                    <p class="truncate text-sm font-medium text-gray-500"><?php echo htmlspecialchars($ucsLabel); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Quick Actions + Recent Content -->
<div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Quick Actions -->
    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100" aria-labelledby="quick-actions-heading">
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"></path>
            </svg>
            <h2 id="quick-actions-heading" class="text-base font-semibold text-gray-900">Quick Actions</h2>
        </div>

        <div class="mt-4 flex flex-col gap-2">
            <?php foreach ($ucsQuickActions as $ucsAction): ?>
                <a href="<?php echo htmlspecialchars(ROOT_URL . $ucsAction['url']); ?>" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php echo $ucsAction['icon']; ?>
                        </svg>
                    </span>
                    <?php echo htmlspecialchars($ucsAction['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Recent Content -->
    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 lg:col-span-2" aria-labelledby="recent-content-heading">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
                    <path d="M18 14h-8"></path>
                    <path d="M15 18h-5"></path>
                    <path d="M10 6h8v4h-8V6Z"></path>
                </svg>
                <h2 id="recent-content-heading" class="text-base font-semibold text-gray-900">Recent Content</h2>
            </div>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>" class="text-sm font-medium text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                View all
            </a>
        </div>

        <?php if (!empty($ucsRecentNews)): ?>
            <ul class="mt-3 divide-y divide-blue-100">
                <?php foreach ($ucsRecentNews as $ucsNews): ?>
                    <?php $ucsPublished = $ucsFmtDate($ucsNews['published_at'] ?? null); ?>
                    <li class="py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($ucsNews['title']); ?></p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-700 ring-1 ring-blue-100">
                                        <?php echo htmlspecialchars($ucsNews['category']); ?>
                                    </span>
                                    <?php if ($ucsPublished !== null): ?>
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                            <?php echo htmlspecialchars($ucsPublished); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="mt-6 rounded-xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                No published news yet.
            </p>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/admin-layout-bottom.php'; ?>