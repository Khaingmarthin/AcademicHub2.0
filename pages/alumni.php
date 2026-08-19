<?php
/**
 * Public Alumni Directory page.
 *
 * Lists verified UCSMTLA alumni whose profiles are marked public. Visitors
 * can browse without logging in, search by name / profession / company, and
 * filter by graduation year, major and profession.
 *
 * Only public, non-sensitive information is ever shown. Passwords, phone
 * numbers, private email, addresses, NRC and other private student data are
 * never queried here.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/helpers/ucs-listing-helpers.php';

$pageTitle = 'Alumni';

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

// Base clause: only graduated, verified alumni with public visibility.
$ucsBaseWhere = "ap.verification_status = 'verified' AND ap.visibility = 'public' AND s.student_status = 'graduated'";

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
        "SELECT DISTINCT m.id, m.name " . $ucsBaseJoin . "
         WHERE " . $ucsBaseWhere . "
         ORDER BY m.name ASC"
    );
    $ucsMajors->execute();
    $ucsMajorOptions = $ucsMajors->fetchAll();

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
    $ucsProfessionOptions = [];
}

// Validate filter values against the derived lists before building the query.
$ucsYearIds = array_map('intval', $ucsYearOptions);
if ($ucsGraduationYear === false || !in_array($ucsGraduationYear, $ucsYearIds, true)) {
    $ucsGraduationYear = 0;
}
$ucsMajorIds = array_map('intval', array_column($ucsMajorOptions, 'id'));
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
    $ucsWhere[]  = '(s.name LIKE :q OR ap.current_job LIKE :q OR ap.company LIKE :q)';
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
                m.id AS major_id, m.name AS major_name
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
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="alumni-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">UCSMTLA</p>
                <h1 id="alumni-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Alumni</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    UCSMTLA graduates remain part of our academic and professional community.
                </p>
            </div>
        </div>
    </section>

    <!-- Directory -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="alumni-directory-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Our Alumni</p>
                <h2 id="alumni-directory-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Meet Our Graduates</h2>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-gray-600">
                    Discover the academic and professional journeys of UCSMTLA graduates across industries.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-stories.php'); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                        Read Alumni Stories
                    </a>
                </div>
            </div>

            <!-- Search + filters -->
            <form method="get" action="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" role="search" class="mt-10 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:p-6">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="relative flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="search" name="q" value="<?php echo htmlspecialchars($ucsQuery); ?>" placeholder="Search by name, profession or company…" aria-label="Search alumni by name, profession or company"
                               class="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Search
                    </button>
                </div>

                <div class="mt-4 grid gap-3 border-t border-gray-100 pt-4 sm:grid-cols-3">
                    <div>
                        <label for="alumni-year-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Graduation Year</label>
                        <select id="alumni-year-filter" name="graduation_year" aria-label="Filter by graduation year"
                                class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">All Years</option>
                            <?php foreach ($ucsYearOptions as $ucsYearOption): ?>
                                <option value="<?php echo (int) $ucsYearOption; ?>" <?php echo (int) $ucsGraduationYear === (int) $ucsYearOption ? 'selected' : ''; ?>><?php echo (int) $ucsYearOption; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="alumni-major-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Major</label>
                        <select id="alumni-major-filter" name="major" aria-label="Filter by major"
                                class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">All Majors</option>
                            <?php foreach ($ucsMajorOptions as $ucsMajorOption): ?>
                                <option value="<?php echo (int) $ucsMajorOption['id']; ?>" <?php echo (int) $ucsMajorId === (int) $ucsMajorOption['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $ucsMajorOption['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="alumni-profession-filter" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Profession</label>
                        <select id="alumni-profession-filter" name="profession" aria-label="Filter by profession"
                                class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
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
                    <div class="mt-4 border-t border-gray-100 pt-4 text-center">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                                <path d="M3 3v5h5"></path>
                            </svg>
                            Clear all filters
                        </a>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Result count -->
            <p class="mt-8 text-center text-sm font-medium text-gray-500" role="status">
                <?php echo $ucsHasFilters ? 'Showing' : 'Browsing'; ?>
                <span class="font-semibold text-gray-700"><?php echo count($ucsAlumni); ?></span>
                verified alumn<?php echo count($ucsAlumni) === 1 ? 'us' : 'i'; ?>
                <?php echo $ucsHasFilters ? ' matching your search' : 'from the UCSMTLA community'; ?>
            </p>

            <?php if (count($ucsAlumni) > 0): ?>
                <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                    <?php foreach ($ucsAlumni as $ucsAlumnus): ?>
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
                        ?>
                        <article class="ucs-reveal group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-900/10">
                            <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-700 px-6 pt-10 pb-14" aria-hidden="true">
                                <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-white/10" aria-hidden="true"></div>
                                <div class="absolute -bottom-10 -left-6 h-24 w-24 rounded-full bg-white/10" aria-hidden="true"></div>
                            </div>

                            <div class="-mt-10 flex flex-1 flex-col px-6 pb-6">
                                <div class="relative z-10">
                                    <?php if ($ucsHasPhoto): ?>
                                        <img src="<?php echo htmlspecialchars($ucsPhotoUrl); ?>" alt="<?php echo htmlspecialchars($ucsAlumnusName); ?>" class="h-20 w-20 rounded-2xl object-cover shadow-lg ring-4 ring-white">
                                    <?php else: ?>
                                        <span class="inline-flex h-20 w-20 items-center justify-center rounded-2xl bg-white text-xl font-extrabold text-blue-700 shadow-lg ring-4 ring-white" aria-hidden="true">
                                            <?php echo htmlspecialchars($ucsInitials); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-2">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <path d="m9 11 3 3L22 4"></path>
                                        </svg>
                                        Verified
                                    </span>
                                    <?php if (!empty($ucsAlumnus['graduation_year'])): ?>
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 ring-1 ring-gray-200">Class of <?php echo htmlspecialchars((string) $ucsAlumnus['graduation_year']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="mt-3 text-lg font-bold leading-snug tracking-tight text-gray-900">
                                    <a href="<?php echo htmlspecialchars($ucsProfileUrl); ?>" class="transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        <?php echo htmlspecialchars($ucsAlumnusName); ?>
                                    </a>
                                </h3>

                                <p class="mt-1 text-sm font-medium text-gray-500"><?php echo htmlspecialchars((string) ($ucsAlumnus['major_name'] ?? '')); ?></p>

                                <?php if (!empty($ucsAlumnus['current_job']) || !empty($ucsAlumnus['company'])): ?>
                                    <p class="mt-3 text-sm font-semibold text-gray-800">
                                        <?php echo htmlspecialchars((string) $ucsAlumnus['current_job']); ?>
                                        <?php if (!empty($ucsAlumnus['current_job']) && !empty($ucsAlumnus['company'])): ?><span class="font-normal text-gray-400"> at </span><?php endif; ?>
                                        <?php if (!empty($ucsAlumnus['company'])): ?><span class="text-gray-800"><?php echo htmlspecialchars((string) $ucsAlumnus['company']); ?></span><?php endif; ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ($ucsBioPreview !== ''): ?>
                                    <p class="mt-3 flex-1 text-sm leading-6 text-gray-600">
                                        <?php echo htmlspecialchars($ucsBioPreview); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="mt-5 flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                                    <div class="flex items-center gap-1.5">
                                        <?php foreach ($ucsLinks as $ucsLink): ?>
                                            <?php
                                            $ucsLinkLabel = '';
                                            $ucsLinkIcon  = '';
                                            if ($ucsLink[0] === 'linkedin') { $ucsLinkLabel = 'LinkedIn'; }
                                            if ($ucsLink[0] === 'github')   { $ucsLinkLabel = 'GitHub'; }
                                            if ($ucsLink[0] === 'website')  { $ucsLinkLabel = 'Website'; }
                                            ?>
                                            <a href="<?php echo htmlspecialchars($ucsLink[1]); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo htmlspecialchars($ucsLinkLabel . ' of ' . $ucsAlumnusName); ?>"
                                               class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                                <?php if ($ucsLink[0] === 'linkedin'): ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM7.119 20.452H3.554V9h3.565v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                                <?php elseif ($ucsLink[0] === 'github'): ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                                                <?php else: ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($ucsProfileUrl); ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        View Profile
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mt-10 rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-gray-200">
                    <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path>
                            <path d="M22 10v6"></path>
                            <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold text-gray-900">No alumni found</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">
                        <?php if ($ucsHasFilters): ?>
                            No verified public alumni match your search or filters. Try different keywords or clear the filters.
                        <?php else: ?>
                            We are preparing alumni profiles for our community. Please check back soon.
                        <?php endif; ?>
                    </p>
                    <?php if ($ucsHasFilters): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Clear all filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
    (function () {
        'use strict';
        document.documentElement.classList.add('ucs-js');

        var els = document.querySelectorAll('.ucs-reveal');
        if (!('IntersectionObserver' in window)) {
            els.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        els.forEach(function (el) { observer.observe(el); });
    })();
</script>

<?php
require_once '../includes/footer.php';
?>