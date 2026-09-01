<?php
/**
 * Public Alumni Directory page.
 *
 * Lists verified UCSMTLA alumni whose profiles are marked public. Visitors
 * can browse without logging in, search by name / profession / company, and
 * filter by graduation year, major and profession.
 *
 * Editorial institutional layout with structured bordered profile rows.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';
require_once __DIR__ . '/../includes/helpers/alumni-validation.php';

$pageTitle = 'Alumni';

// Determine if the "Join Alumni Community" button should be shown.
// Only for logged-in graduated students who don't have an alumni profile yet.
$ucsShowJoinButton  = false;
$ucsShowDashboardBtn = false;
$ucsCurrentUser      = student_current_user();
if ($ucsCurrentUser !== null) {
    try {
        $ucsStateStmt = $pdo->prepare(
            "SELECT s.student_status, ap.verification_status AS profile_status
             FROM students s
             LEFT JOIN alumni_profiles ap ON ap.student_id = s.id
             WHERE s.id = :id
             LIMIT 1"
        );
        $ucsStateStmt->execute([':id' => (int) $ucsCurrentUser['id']]);
        $ucsStateRow = $ucsStateStmt->fetch() ?: null;
        if ($ucsStateRow !== null) {
            $ucsUserStatus    = (string) ($ucsStateRow['student_status'] ?? 'active');
            $ucsUserProfile   = $ucsStateRow['profile_status'] ?? null;
            if ($ucsUserStatus === 'graduated' && $ucsUserProfile === null) {
                $ucsShowJoinButton = true;
            } elseif ($ucsUserProfile === 'verified') {
                $ucsShowDashboardBtn = true;
            }
        }
    } catch (PDOException $e) {
        // Ignore – button stays hidden.
    }
}

$ucsHeroMedia = 'images/front_view.jpg';
try {
    $ucsProfileStmt = $pdo->query(
        "SELECT hero_media
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
    $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';
} catch (PDOException $e) {
    // Keep the default hero media.
}

// ---------------------------------------------------------------------
// Filters.
// ---------------------------------------------------------------------
$ucsQuery           = trim((string) ($_GET['q'] ?? ''));
$ucsGraduationYear  = filter_var($_GET['graduation_year'] ?? '', FILTER_VALIDATE_INT);
$ucsMajorId         = filter_var($_GET['major'] ?? '', FILTER_VALIDATE_INT);
$ucsProfession      = trim((string) ($_GET['profession'] ?? ''));

// Base clause: graduated, verified alumni — public visibility required,
// except for CT (Computer Technology) alumni who are always shown.
$ucsCTMajorId = 2;
$ucsBaseWhere = "ap.verification_status = 'verified' AND s.student_status = 'graduated'
                 AND (ap.visibility = 'public' OR cl.major_id = " . $ucsCTMajorId . ")";

// ---------------------------------------------------------------------
// Filter option lists (derived from the public verified directory only,
// so empty options are never offered).
// ---------------------------------------------------------------------
$ucsYearOptions       = [];
$ucsMajorOptions      = [];
$ucsProfessionOptions = [];

try {
    $ucsBaseJoin = "FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         JOIN classrooms cl ON cl.id = s.classroom_id
         JOIN majors m ON m.id = cl.major_id";

    $ucsYears = $pdo->prepare(
        "SELECT DISTINCT s.graduation_year " . $ucsBaseJoin . "
         WHERE " . $ucsBaseWhere . "
           AND s.graduation_year IS NOT NULL
         ORDER BY s.graduation_year DESC"
    );
    $ucsYears->execute();
    $ucsYearOptions = $ucsYears->fetchAll(PDO::FETCH_COLUMN);

    $ucsMajors = $pdo->prepare(
        "SELECT DISTINCT m.id, m.short_name, m.name " . $ucsBaseJoin . "
         WHERE " . $ucsBaseWhere . "
         ORDER BY m.name ASC"
    );
    $ucsMajors->execute();
    $ucsMajorOptions = $ucsMajors->fetchAll();

    // Also fetch all active majors so the dropdown always shows available options
    // (even if no verified public alumni exist for that major yet).
    $ucsAllMajors = $pdo->query(
        "SELECT id, short_name, name FROM majors WHERE status = 1 ORDER BY name ASC"
    );
    $ucsAllMajorOptions = $ucsAllMajors->fetchAll();

    $ucsJobs = $pdo->prepare(
        "SELECT DISTINCT ap.current_job " . $ucsBaseJoin . "
         WHERE " . $ucsBaseWhere . "
           AND ap.current_job IS NOT NULL
           AND TRIM(ap.current_job) <> ''
         ORDER BY ap.current_job ASC"
    );
    $ucsJobs->execute();
    $ucsProfessionOptions = $ucsJobs->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $ucsYearOptions       = [];
    $ucsMajorOptions      = [];
    $ucsAllMajorOptions   = [];
    $ucsProfessionOptions = [];
}

// Merge: prefer majors that have verified public alumni, then add any remaining active majors.
$ucsAllMajorIds    = array_column($ucsMajorOptions, 'id');
$ucsMergedMajors   = $ucsMajorOptions;
if (!empty($ucsAllMajorOptions)) {
    foreach ($ucsAllMajorOptions as $ucsAM) {
        if (!in_array((int) $ucsAM['id'], array_map('intval', $ucsAllMajorIds), true)) {
            $ucsMergedMajors[] = $ucsAM;
        }
    }
}

// Validate filter values against the derived lists before building the query.
$ucsYearIds = array_map('intval', $ucsYearOptions);
if ($ucsGraduationYear === false || !in_array($ucsGraduationYear, $ucsYearIds, true)) {
    $ucsGraduationYear = 0;
}
$ucsMajorIds = array_map('intval', array_column($ucsMergedMajors, 'id'));
if ($ucsMajorId === false || !in_array($ucsMajorId, $ucsMajorIds, true)) {
    $ucsMajorId = 0;
}
if ($ucsProfession !== '' && !in_array($ucsProfession, array_map('strval', $ucsProfessionOptions), true)) {
    $ucsProfession = '';
}

$ucsWhere  = [$ucsBaseWhere];
$ucsParams = [];

if ($ucsQuery !== '') {
    $ucsEscaped  = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $ucsQuery);
    $ucsWhere[]  = '(s.name LIKE :q OR ap.current_job LIKE :q OR ap.company LIKE :q OR m.name LIKE :q OR m.short_name LIKE :q)';
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
if ($ucsProfession !== '') {
    $ucsWhere[] = 'ap.current_job = :profession';
    $ucsParams[':profession'] = $ucsProfession;
}

$ucsWhereSql = ' WHERE ' . implode(' AND ', $ucsWhere);

// ---------------------------------------------------------------------
// Alumni listing.
// ---------------------------------------------------------------------
$ucsAlumni = [];
try {
    $ucsStmt = $pdo->prepare(
        "SELECT ap.id, ap.current_job, ap.company, ap.skills, ap.bio,
                ap.profile_photo, ap.linkedin_url, ap.github_url,
                ap.website_url, ap.updated_at,
                s.name AS student_name, s.graduation_year,
                m.id AS major_id, m.short_name AS major_short, m.name AS major_name
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         JOIN classrooms cl ON cl.id = s.classroom_id
         JOIN majors m ON m.id = cl.major_id"
        . $ucsWhereSql . "
         ORDER BY ap.updated_at DESC, s.name ASC"
    );
    $ucsStmt->execute($ucsParams);
    $ucsAlumni = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsAlumni = [];
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="alumni-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Alumni Directory</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Our Alumni</p>
                <h1 id="alumni-page-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                    Meet Our Graduates
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    Discover the academic and professional journeys of UCSMTLA graduates across industries.
                </p>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-stories.php'); ?>" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        Read Alumni Stories
                    </a>
                    <?php if ($ucsShowJoinButton): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-join.php'); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                            Join Alumni Community
                        </a>
                    <?php elseif ($ucsShowDashboardBtn): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                            Alumni Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Directory -->
    <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="alumni-directory-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="alumni-directory-heading" class="sr-only">Alumni Directory</h2>

            <!-- Search + filters — bordered panel -->
            <form method="get" action="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" role="search" class="border border-slate-200 bg-white px-6 py-5 sm:px-8 sm:py-6">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="relative flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search by name, major, profession or company…" aria-label="Search alumni by name, major, profession or company"
                               class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Search
                    </button>
                </div>

                <div class="mt-4 grid gap-3 border-t border-slate-200 pt-4 sm:grid-cols-3">
                    <div>
                        <label for="alumni-year-filter" class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Graduation Year</label>
                        <select id="alumni-year-filter" name="graduation_year" aria-label="Filter by graduation year"
                                onchange="this.form.submit()"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">All Years</option>
                            <?php foreach ($ucsYearOptions as $ucsYearOption): ?>
                                <option value="<?php echo (int) $ucsYearOption; ?>" <?php echo (int) $ucsGraduationYear === (int) $ucsYearOption ? 'selected' : ''; ?>><?php echo (int) $ucsYearOption; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="alumni-major-filter" class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Major</label>
                        <select id="alumni-major-filter" name="major" aria-label="Filter by major"
                                onchange="this.form.submit()"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">All Majors</option>
                            <?php foreach ($ucsMergedMajors as $ucsMajorOption): ?>
                                <option value="<?php echo (int) $ucsMajorOption['id']; ?>" <?php echo (int) $ucsMajorId === (int) $ucsMajorOption['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(((string) $ucsMajorOption['short_name'] !== '' ? (string) $ucsMajorOption['short_name'] . ' - ' : '') . (string) $ucsMajorOption['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="alumni-profession-filter" class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Profession</label>
                        <select id="alumni-profession-filter" name="profession" aria-label="Filter by profession"
                                onchange="this.form.submit()"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">All Professions</option>
                            <?php foreach ($ucsProfessionOptions as $ucsProfessionOption): ?>
                                <option value="<?php echo htmlspecialchars((string) $ucsProfessionOption); ?>" <?php echo $ucsProfession === (string) $ucsProfessionOption ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $ucsProfessionOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php
                $ucsHasFilters = $ucsQuery !== '' || $ucsGraduationYear > 0 || $ucsMajorId > 0 || $ucsProfession !== '';
                ?>
                <?php if ($ucsHasFilters): ?>
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <span class="text-xs font-medium text-slate-500">Active filters:</span>
                            <?php if ($ucsQuery !== ''): ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-100">
                                    Search: "<?php echo htmlspecialchars($ucsQuery); ?>"
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php?' . http_build_query(array_filter(['graduation_year' => $ucsGraduationYear ?: null, 'major' => $ucsMajorId ?: null, 'profession' => $ucsProfession ?: null]))); ?>" class="ml-0.5 text-blue-500 hover:text-blue-700" aria-label="Remove search filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            <?php if ($ucsGraduationYear > 0): ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-100">
                                    Year: <?php echo (int) $ucsGraduationYear; ?>
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php?' . http_build_query(array_filter(['q' => $ucsQuery ?: null, 'major' => $ucsMajorId ?: null, 'profession' => $ucsProfession ?: null]))); ?>" class="ml-0.5 text-blue-500 hover:text-blue-700" aria-label="Remove year filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            <?php if ($ucsMajorId > 0): ?>
                                <?php
                                $ucsSelectedMajorName = '';
                                foreach ($ucsMergedMajors as $ucsMO) {
                                    if ((int) $ucsMO['id'] === $ucsMajorId) {
                                        $ucsSelectedMajorName = ((string) $ucsMO['short_name'] !== '' ? (string) $ucsMO['short_name'] . ' - ' : '') . (string) $ucsMO['name'];
                                        break;
                                    }
                                }
                                ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-100">
                                    Major: <?php echo htmlspecialchars($ucsSelectedMajorName); ?>
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php?' . http_build_query(array_filter(['q' => $ucsQuery ?: null, 'graduation_year' => $ucsGraduationYear ?: null, 'profession' => $ucsProfession ?: null]))); ?>" class="ml-0.5 text-blue-500 hover:text-blue-700" aria-label="Remove major filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            <?php if ($ucsProfession !== ''): ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-100">
                                    Profession: <?php echo htmlspecialchars($ucsProfession); ?>
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php?' . http_build_query(array_filter(['q' => $ucsQuery ?: null, 'graduation_year' => $ucsGraduationYear ?: null, 'major' => $ucsMajorId ?: null]))); ?>" class="ml-0.5 text-blue-500 hover:text-blue-700" aria-label="Remove profession filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="text-xs font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                Clear all
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Result count -->
            <p class="mt-6 text-center text-sm font-medium text-slate-500" role="status">
                <?php echo $ucsHasFilters ? 'Showing' : 'Browsing'; ?>
                <span class="font-semibold text-slate-700"><?php echo count($ucsAlumni); ?></span>
                verified alumn<?php echo count($ucsAlumni) === 1 ? 'us' : 'i'; ?>
                <?php if ($ucsHasFilters): ?>
                    matching your search
                    <?php if ($ucsMajorId > 0): ?>
                        <?php
                        $ucsSelectedMajorName = '';
                        foreach ($ucsMergedMajors as $ucsMO) {
                            if ((int) $ucsMO['id'] === $ucsMajorId) {
                                $ucsSelectedMajorName = ((string) $ucsMO['short_name'] !== '' ? (string) $ucsMO['short_name'] . ' - ' : '') . (string) $ucsMO['name'];
                                break;
                            }
                        }
                        ?>
                        <?php if ($ucsSelectedMajorName !== ''): ?>
                            <span class="font-semibold text-blue-600">— <?php echo htmlspecialchars($ucsSelectedMajorName); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    from the UCSMTLA community
                <?php endif; ?>
            </p>

            <?php if (count($ucsAlumni) > 0): ?>
                <!-- Alumni profiles — bordered list -->
                <div class="mt-8 border border-slate-200 bg-white">
                    <?php foreach ($ucsAlumni as $ucsIdx => $ucsAlumnus): ?>
                        <?php
                        $ucsProfileUrl = BASE_URL . '/alumni-details.php?id=' . (int) $ucsAlumnus['id'];

                        $ucsHasPhoto = false;
                        $ucsPhotoUrl = '';
                        if (!empty($ucsAlumnus['profile_photo'])) {
                            $ucsPhotoFile = dirname(__DIR__) . '/assets/' . ltrim($ucsAlumnus['profile_photo'], '/');
                            $ucsHasPhoto  = is_file($ucsPhotoFile);
                            if ($ucsHasPhoto) {
                                $ucsPhotoUrl = ROOT_URL . '/assets/' . ltrim($ucsAlumnus['profile_photo'], '/');
                            }
                        }

                        $ucsAlumnusName = trim((string) ($ucsAlumnus['student_name'] ?? ''));
                        $ucsInitials = 'A';
                        if ($ucsAlumnusName !== '') {
                            $ucsWords = preg_split('/\s+/', $ucsAlumnusName);
                            $ucsFirst = substr($ucsWords[0] ?? 'A', 0, 1);
                            $ucsLast  = count($ucsWords) > 1 ? substr(end($ucsWords), 0, 1) : '';
                            $ucsInitials = strtoupper($ucsFirst . $ucsLast);
                        }

                        $ucsBioPreview = ucs_short_summary((string) ($ucsAlumnus['bio'] ?? ''), 140);

                        $ucsLinks = [];
                        if (!empty($ucsAlumnus['linkedin_url'])) { $ucsLinks[] = ['linkedin', (string) $ucsAlumnus['linkedin_url']]; }
                        if (!empty($ucsAlumnus['github_url']))   { $ucsLinks[] = ['github', (string) $ucsAlumnus['github_url']]; }
                        if (!empty($ucsAlumnus['website_url']))  { $ucsLinks[] = ['website', (string) $ucsAlumnus['website_url']]; }

                        $ucsIsFirst = $ucsIdx === 0;
                        ?>
                        <article class="group grid grid-cols-1 gap-0 sm:grid-cols-[7rem_1fr] <?php echo !$ucsIsFirst ? 'border-t border-slate-200' : ''; ?> transition-colors duration-150 hover:bg-slate-50/60">
                            <!-- Avatar column -->
                            <div class="flex items-center justify-center border-b border-slate-200 bg-slate-50 px-6 py-5 sm:border-b-0 sm:border-r sm:py-0">
                                <?php if ($ucsHasPhoto): ?>
                                    <img src="<?php echo htmlspecialchars($ucsPhotoUrl); ?>" alt="<?php echo htmlspecialchars($ucsAlumnusName); ?>" class="h-16 w-16 rounded-lg object-cover ring-1 ring-slate-200">
                                <?php else: ?>
                                    <span class="inline-flex h-16 w-16 items-center justify-center rounded-lg bg-blue-50 text-lg font-bold text-blue-700 ring-1 ring-blue-100" aria-hidden="true">
                                        <?php echo htmlspecialchars($ucsInitials); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <!-- Content column -->
                            <div class="px-6 py-5 sm:px-8 sm:py-6">
                                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <h3 class="text-base font-semibold tracking-tight text-slate-900">
                                        <a href="<?php echo htmlspecialchars($ucsProfileUrl); ?>" class="transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                            <?php echo htmlspecialchars($ucsAlumnusName); ?>
                                        </a>
                                    </h3>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[0.6875rem] font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                                            Verified
                                        </span>
                                        <?php if (!empty($ucsAlumnus['graduation_year'])): ?>
                                            <span class="text-xs font-medium text-slate-400">Class of <?php echo htmlspecialchars((string) $ucsAlumnus['graduation_year']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <p class="mt-1 text-sm font-medium text-slate-500"><?php echo htmlspecialchars(((string) ($ucsAlumnus['major_short'] ?? '') !== '' ? (string) $ucsAlumnus['major_short'] . ' - ' : '') . (string) ($ucsAlumnus['major_name'] ?? '')); ?></p>

                                <?php if (!empty($ucsAlumnus['current_job']) || !empty($ucsAlumnus['company'])): ?>
                                    <p class="mt-2 text-sm text-slate-700">
                                        <?php echo htmlspecialchars((string) $ucsAlumnus['current_job']); ?>
                                        <?php if (!empty($ucsAlumnus['current_job']) && !empty($ucsAlumnus['company'])): ?><span class="text-slate-400"> at </span><?php endif; ?>
                                        <?php if (!empty($ucsAlumnus['company'])): ?><span class="font-medium text-slate-700"><?php echo htmlspecialchars((string) $ucsAlumnus['company']); ?></span><?php endif; ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ($ucsBioPreview !== ''): ?>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                                        <?php echo htmlspecialchars($ucsBioPreview); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="mt-3 flex items-center gap-3">
                                    <?php foreach ($ucsLinks as $ucsLink): ?>
                                        <?php
                                        $ucsLinkLabel = '';
                                        if ($ucsLink[0] === 'linkedin') { $ucsLinkLabel = 'LinkedIn'; }
                                        if ($ucsLink[0] === 'github')   { $ucsLinkLabel = 'GitHub'; }
                                        if ($ucsLink[0] === 'website')  { $ucsLinkLabel = 'Website'; }
                                        ?>
                                        <a href="<?php echo htmlspecialchars($ucsLink[1]); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo htmlspecialchars($ucsLinkLabel . ' of ' . $ucsAlumnusName); ?>"
                                           class="inline-flex h-7 w-7 items-center justify-center rounded border border-slate-200 bg-white text-slate-500 transition-colors duration-150 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                            <?php if ($ucsLink[0] === 'linkedin'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM7.119 20.452H3.554V9h3.565v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                            <?php elseif ($ucsLink[0] === 'github'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>

                                    <a href="<?php echo htmlspecialchars($ucsProfileUrl); ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        View Profile
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mt-8 border border-slate-200 bg-white px-6 py-14 text-center sm:px-8">
                    <h3 class="text-lg font-bold text-slate-900">No alumni found</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-500">
                        <?php if ($ucsHasFilters): ?>
                            No verified public alumni match your search or filters. Try different keywords, select a different major, or clear the filters.
                        <?php else: ?>
                            We are preparing alumni profiles for our community. Please check back soon.
                        <?php endif; ?>
                    </p>
                    <?php if ($ucsHasFilters): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Clear all filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>
