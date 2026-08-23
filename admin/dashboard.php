<?php
/**
 * Admin Dashboard.
 *
 * Landing page after an admin logs in. Shows a welcome/hero card with the
 * active academic year, quick actions, live module statistics and the most
 * recently published university news. All figures are read from the
 * database; nothing is hard-coded.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

admin_require_login();

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Overview of the university portal.';
$activeNav    = 'dashboard';
$hidePageHeader = true;

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

$ucsFmtDate = function ($value) {
    if (empty($value)) {
        return null;
    }
    $ts = strtotime((string) $value);
    return $ts !== false ? date('j M Y', $ts) : (string) $value;
};

// Statistics cards data
$ucsStatCards = [
    [
        'label'   => 'Faculties',
        'value'   => $ucsStats['Faculties'] ?? 0,
        'url'     => '/admin/faculties/index.php',
        'icon'    => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>',
        'gradient' => 'from-blue-500 to-blue-600',
        'borderColor' => '#3b82f6',
        'textColor' => 'text-blue-600',
    ],
    [
        'label'   => 'Departments',
        'value'   => $ucsStats['Departments'] ?? 0,
        'url'     => '/admin/departments/index.php',
        'icon'    => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
        'gradient' => 'from-indigo-500 to-indigo-600',
        'borderColor' => '#6366f1',
        'textColor' => 'text-indigo-600',
    ],
    [
        'label'   => 'Courses',
        'value'   => $ucsStats['Courses'] ?? 0,
        'url'     => '/admin/courses/index.php',
        'icon'    => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>',
        'gradient' => 'from-emerald-500 to-emerald-600',
        'borderColor' => '#10b981',
        'textColor' => 'text-emerald-600',
    ],
    [
        'label'   => 'Students',
        'value'   => $ucsStats['Students'] ?? 0,
        'url'     => '/admin/students/index.php',
        'icon'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'gradient' => 'from-violet-500 to-purple-600',
        'borderColor' => '#8b5cf6',
        'textColor' => 'text-violet-600',
    ],
];

$ucsQuickActions = [
    [
        'label'   => 'Create Announcement',
        'subtext' => 'Post new news',
        'url'     => '/admin/news/index.php',
        'icon'    => '<path d="m3 11 18-5v12L3 13v-2z"></path><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"></path>',
        'bgColor' => 'bg-violet-50',
        'textColor' => 'text-violet-600',
    ],
    [
        'label'   => 'Upload Timetable',
        'subtext' => 'Upload PDF classes',
        'url'     => '/admin/timetables/index.php',
        'icon'    => '<path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M10 14h1"></path><path d="M14 14h1"></path>',
        'bgColor' => 'bg-blue-50',
        'textColor' => 'text-blue-600',
    ],
    [
        'label'   => 'Manage Alumni',
        'subtext' => 'Alumni network & records',
        'url'     => '/admin/alumni/index.php',
        'icon'    => '<path d="M18 21a8 8 0 0 0-16 0"></path><circle cx="10" cy="8" r="5"></circle><path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"></path>',
        'bgColor' => 'bg-amber-50',
        'textColor' => 'text-amber-600',
    ],
    [
        'label'   => 'Student Management',
        'subtext' => 'Rosters & groups',
        'url'     => '/admin/students/index.php',
        'icon'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'bgColor' => 'bg-teal-50',
        'textColor' => 'text-teal-600',
    ],
];

// Dynamic greeting based on the server time.
$ucsHour    = (int) date('G');
if ($ucsHour >= 5 && $ucsHour < 12) {
    $ucsGreeting = 'Good Morning';
} elseif ($ucsHour >= 12 && $ucsHour < 17) {
    $ucsGreeting = 'Good Afternoon';
} else {
    $ucsGreeting = 'Good Evening';
}

$ucsAdminName = 'Admin';
if (function_exists('admin_current_user')) {
    $ucsCurrent  = admin_current_user();
    if ($ucsCurrent !== null && trim((string) $ucsCurrent['name']) !== '') {
        $ucsAdminName = $ucsCurrent['name'];
    }
}

$ucsYearLabel = $ucsActiveYear['year_name'] ?? null;
$ucsToday     = date('l, d F Y');

require_once __DIR__ . '/../includes/admin-layout-top.php';
?>

<!-- Welcome / Hero Section -->
<section class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 p-6 shadow-lg shadow-blue-600/20 sm:p-7" aria-label="Welcome">
    <div class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute bottom-0 left-1/4 h-24 w-24 rounded-full bg-indigo-400/20 blur-2xl" aria-hidden="true"></div>
    <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="max-w-xl">
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-blue-200/80 mb-1">Welcome to UCSMTLA</p>
            <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                <?php echo htmlspecialchars($ucsGreeting . ', ' . $ucsAdminName); ?> <span class="inline-block" aria-hidden="true">&#x1F44B;</span>
            </h1>
            <p class="mt-1.5 text-sm font-medium text-blue-100">Academic Hub &ndash; UCSMTLA</p>
            <?php if ($ucsYearLabel !== null): ?>
                <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3.5 py-1.5 text-[11px] font-semibold text-white ring-1 ring-white/20 backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Academic Year: <?php echo htmlspecialchars($ucsYearLabel); ?> (Active)
                </p>
            <?php endif; ?>
        </div>
        <div class="shrink-0">
            <div class="rounded-xl bg-white/15 px-5 py-3 text-right ring-1 ring-white/15 backdrop-blur-md">
                <p class="text-[9px] font-bold uppercase tracking-[0.2em] text-blue-200/80">Today's Date</p>
                <p class="mt-1 text-base font-bold text-white"><?php echo htmlspecialchars($ucsToday); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Quick Actions -->
<section class="mt-5" aria-labelledby="quick-actions-heading">
    <h2 id="quick-actions-heading" class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Quick Actions</h2>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach ($ucsQuickActions as $ucsAction): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . $ucsAction['url']); ?>" class="group relative overflow-hidden rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-900/8 hover:ring-blue-200 focus:outline-none">
                <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full <?php echo $ucsAction['bgColor']; ?> opacity-0 transition-all duration-300 group-hover:scale-150 group-hover:opacity-100" aria-hidden="true"></div>
                <div class="relative z-10 flex flex-col items-start gap-3">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl <?php echo $ucsAction['bgColor']; ?> <?php echo $ucsAction['textColor']; ?> ring-1 ring-inset ring-current/5 transition-all duration-200 group-hover:scale-110 group-hover:shadow-md" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php echo $ucsAction['icon']; ?>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[13px] font-bold text-slate-800 group-hover:text-slate-900 transition-colors"><?php echo htmlspecialchars($ucsAction['label']); ?></p>
                        <p class="mt-0.5 text-[11px] text-slate-400 group-hover:text-slate-500 transition-colors"><?php echo htmlspecialchars($ucsAction['subtext']); ?></p>
                    </div>
                </div>
                <div class="absolute bottom-3 right-3 opacity-0 transition-all duration-200 group-hover:opacity-100 group-hover:translate-x-0 -translate-x-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 <?php echo $ucsAction['textColor']; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Statistics Overview -->
<section class="mt-5" aria-labelledby="stats-heading">
    <h2 id="stats-heading" class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Statistics Overview</h2>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach ($ucsStatCards as $ucsStat): ?>
            <a href="<?php echo htmlspecialchars(ROOT_URL . $ucsStat['url']); ?>" class="group relative overflow-hidden rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-900/8 focus:outline-none" style="border-left: 4px solid <?php echo $ucsStat['borderColor']; ?>">
                <div class="relative z-10 flex items-start justify-between">
                    <div>
                        <p class="text-2xl font-extrabold tracking-tight text-slate-800 group-hover:text-slate-900 transition-colors"><?php echo htmlspecialchars(number_format($ucsStat['value'])); ?></p>
                        <p class="mt-1 text-[12px] font-medium text-slate-400 group-hover:text-slate-500 transition-colors"><?php echo htmlspecialchars($ucsStat['label']); ?></p>
                    </div>
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br <?php echo $ucsStat['gradient']; ?> text-white shadow-md transition-all duration-200 group-hover:scale-110 group-hover:shadow-lg" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php echo $ucsStat['icon']; ?>
                        </svg>
                    </span>
                </div>
                <div class="absolute bottom-3 right-3 opacity-0 transition-all duration-200 group-hover:opacity-100 group-hover:translate-x-0 -translate-x-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 <?php echo $ucsStat['textColor']; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Recent Content -->
<section class="mt-5" aria-labelledby="recent-content-heading">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200/60 overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-5 py-3.5 border-b border-slate-100">
            <h2 id="recent-content-heading" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Recent Content</h2>
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>" class="text-[11px] font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700">
                View all &rarr;
            </a>
        </div>

        <?php if (!empty($ucsRecentNews)): ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($ucsRecentNews as $ucsNews): ?>
                    <?php $ucsPublished = $ucsFmtDate($ucsNews['published_at'] ?? null); ?>
                    <li class="px-5 py-3 transition-colors hover:bg-slate-50/50">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-700"><?php echo htmlspecialchars($ucsNews['title']); ?></p>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-600">
                                        <?php echo htmlspecialchars($ucsNews['category']); ?>
                                    </span>
                                    <?php if ($ucsPublished !== null): ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
            <p class="px-5 py-8 text-center text-xs text-slate-400">
                No published news yet.
            </p>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
