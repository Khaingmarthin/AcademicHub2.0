<?php
/**
 * Public Alumni Profile page.
 *
 * Shows a verified alumni profile. Public visitors see verified profiles
 * that are marked public. The authenticated owner (verified alumnus) can
 * always view and edit their own profile regardless of visibility.
 *
 * Only public, non-sensitive information is ever displayed. Passwords,
 * phone numbers, private email, addresses, NRC and other private student
 * data are never queried here.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/alumni-validation.php';

$pageTitle = 'Alumni Profile';

$ucsHeroMedia = 'images/front_view.jpg';
$ucsProfile   = null;
$ucsProfileId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

// Is the current viewer the owner of this profile (an authenticated,
// verified alumnus whose profile id matches)?
$ucsCurrentProfile = null;
if ($ucsProfileId !== false && $ucsProfileId > 0) {
    $ucsCurrentProfile = alumni_current_profile($pdo);
}
$ucsIsOwner = $ucsCurrentProfile !== null
    && (int) $ucsCurrentProfile['id'] === $ucsProfileId;

try {
    $ucsProfileStmt = $pdo->query(
        "SELECT hero_media
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
    $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';

    if ($ucsProfileId !== false && $ucsProfileId > 0) {
        $ucsSql = "SELECT ap.id, ap.student_id, ap.current_job, ap.company, ap.professional_field,
                          ap.skills, ap.bio, ap.career_journey,
                          ap.profile_photo, ap.linkedin_url, ap.github_url,
                          ap.website_url, ap.updated_at,
                          s.name AS student_name, s.graduation_year,
                          m.name AS major_name
                   FROM alumni_profiles ap
                   JOIN students s ON s.id = ap.student_id
                   JOIN classrooms cl ON cl.id = s.classroom_id
                   JOIN majors m ON m.id = cl.major_id
                   WHERE ap.id = :id
                     AND ap.verification_status = 'verified'";

        // Public visitors only see profiles marked public; the owner always can.
        if (!$ucsIsOwner) {
            $ucsSql .= " AND ap.visibility = 'public'";
        }
        $ucsSql .= " LIMIT 1";

        $ucsStmt = $pdo->prepare($ucsSql);
        $ucsStmt->execute([':id' => $ucsProfileId]);
        $ucsProfile = $ucsStmt->fetch() ?: null;
    }
} catch (PDOException $e) {
    $ucsProfile = null;
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

if ($ucsProfile !== null) {
    $pageTitle = (string) ($ucsProfile['student_name'] ?? 'Alumni Profile');
}

if ($ucsProfile === null) {
    http_response_code(404);
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <?php if ($ucsProfile !== null): ?>
        <?php
        $ucsAlumnusName = trim((string) ($ucsProfile['student_name'] ?? ''));

        $ucsHasPhoto = false;
        $ucsPhotoUrl = '';
        if (!empty($ucsProfile['profile_photo'])) {
            $ucsPhotoFile = dirname(__DIR__) . '/assets/' . ltrim($ucsProfile['profile_photo'], '/');
            $ucsHasPhoto  = is_file($ucsPhotoFile);
            if ($ucsHasPhoto) {
                $ucsPhotoUrl = ROOT_URL . '/assets/' . ltrim($ucsProfile['profile_photo'], '/');
            }
        }

        $ucsInitials = 'A';
        if ($ucsAlumnusName !== '') {
            $ucsWords = preg_split('/\s+/', $ucsAlumnusName);
            $ucsFirst = substr($ucsWords[0] ?? 'A', 0, 1);
            $ucsLast  = count($ucsWords) > 1 ? substr(end($ucsWords), 0, 1) : '';
            $ucsInitials = strtoupper($ucsFirst . $ucsLast);
        }

        $ucsLinks = [];
        if (!empty($ucsProfile['linkedin_url'])) { $ucsLinks[] = ['linkedin', 'LinkedIn', (string) $ucsProfile['linkedin_url']]; }
        if (!empty($ucsProfile['github_url']))   { $ucsLinks[] = ['github', 'GitHub', (string) $ucsProfile['github_url']]; }
        if (!empty($ucsProfile['website_url']))  { $ucsLinks[] = ['website', 'Website', (string) $ucsProfile['website_url']]; }
        ?>

        <!-- Page hero -->
        <section class="relative overflow-hidden bg-gray-900" aria-labelledby="alumni-profile-heading">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
            <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">UCSMTLA Alumni</p>
                <h1 id="alumni-profile-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    <?php echo htmlspecialchars($ucsAlumnusName); ?>
                </h1>
                <?php if (!empty($ucsProfile['graduation_year'])): ?>
                    <p class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                        </svg>
                        Class of <?php echo htmlspecialchars((string) $ucsProfile['graduation_year']); ?>
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Profile -->
        <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="alumni-profile-details-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <?php if ($ucsIsOwner): ?>
                    <div class="mb-8 flex flex-col gap-3 rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            <p class="text-sm font-medium leading-6 text-blue-900">
                                This is your alumni profile. Only you can see it here when it is marked private.
                            </p>
                        </div>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-edit.php'); ?>"
                           class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                <path d="m15 5 4 4"></path>
                            </svg>
                            Edit Profile
                        </a>
                    </div>
                <?php endif; ?>

                <div class="grid gap-8 lg:grid-cols-3">
                    <!-- Identity card -->
                    <aside class="lg:col-span-1" aria-label="Alumni summary">
                        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                            <div class="h-24 bg-gradient-to-br from-blue-600 to-indigo-700" aria-hidden="true"></div>
                            <div class="flex flex-col items-center px-6 pb-8 text-center">
                                <div class="-mt-12">
                                    <?php if ($ucsHasPhoto): ?>
                                        <img src="<?php echo htmlspecialchars($ucsPhotoUrl); ?>" alt="Portrait of <?php echo htmlspecialchars($ucsAlumnusName); ?>" class="h-24 w-24 rounded-2xl object-cover shadow-lg ring-4 ring-white">
                                    <?php else: ?>
                                        <span class="inline-flex h-24 w-24 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-3xl font-extrabold text-white shadow-lg ring-4 ring-white" aria-hidden="true">
                                            <?php echo htmlspecialchars($ucsInitials); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h2 id="alumni-profile-details-heading" class="mt-4 text-xl font-bold tracking-tight text-gray-900">
                                    <?php echo htmlspecialchars($ucsAlumnusName); ?>
                                </h2>
                                <p class="mt-1 text-sm font-medium text-gray-500"><?php echo htmlspecialchars((string) ($ucsProfile['major_name'] ?? '')); ?></p>

                                <span class="mt-4 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <path d="m9 11 3 3L22 4"></path>
                                    </svg>
                                    Verified Alumni
                                </span>

                                <dl class="mt-6 w-full space-y-3 rounded-xl bg-gray-50 px-4 py-4 ring-1 ring-gray-100">
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Major</dt>
                                        <dd class="truncate text-sm font-semibold text-gray-800"><?php echo htmlspecialchars((string) ($ucsProfile['major_name'] ?? '—')); ?></dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Graduation</dt>
                                        <dd class="text-sm font-semibold text-gray-800">
                                            <?php if (!empty($ucsProfile['graduation_year'])): ?>
                                                Class of <?php echo htmlspecialchars((string) $ucsProfile['graduation_year']); ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </aside>

                    <!-- Details -->
                    <div class="space-y-8 lg:col-span-2">
                        <!-- Professional information -->
                        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Professional Information</h3>
                            <?php
                            $ucsHasProfessionInfo = !empty($ucsProfile['current_job'])
                                || !empty($ucsProfile['company'])
                                || !empty($ucsProfile['professional_field']);
                            ?>
                            <?php if ($ucsHasProfessionInfo): ?>
                                <dl class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Current Job</dt>
                                        <dd class="mt-1 text-base font-semibold text-gray-800"><?php echo htmlspecialchars((string) $ucsProfile['current_job']); ?></dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Company</dt>
                                        <dd class="mt-1 text-base font-semibold text-gray-800"><?php echo htmlspecialchars((string) $ucsProfile['company']); ?></dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Professional Field</dt>
                                        <dd class="mt-1 text-base font-medium text-gray-800"><?php echo htmlspecialchars((string) $ucsProfile['professional_field']); ?></dd>
                                    </div>
                                </dl>
                            <?php else: ?>
                                <p class="mt-4 text-sm leading-6 text-gray-500">Professional information has not been added yet.</p>
                            <?php endif; ?>
                        </div>

                        <!-- About -->
                        <?php if (trim((string) ($ucsProfile['bio'] ?? '')) !== ''): ?>
                            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">About</h3>
                                <div class="mt-4 space-y-4 text-base leading-7 text-gray-700 sm:text-lg sm:leading-8">
                                    <?php echo nl2br(htmlspecialchars((string) $ucsProfile['bio'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Career journey -->
                        <?php if (trim((string) ($ucsProfile['career_journey'] ?? '')) !== ''): ?>
                            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Career Journey</h3>
                                <div class="mt-4 space-y-4 text-base leading-7 text-gray-700">
                                    <?php echo nl2br(htmlspecialchars((string) $ucsProfile['career_journey'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Skills -->
                        <?php if (trim((string) ($ucsProfile['skills'] ?? '')) !== ''): ?>
                            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Skills</h3>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <?php
                                    $ucsSkillItems = preg_split('/[\r\n,]+/', (string) $ucsProfile['skills']);
                                    foreach ($ucsSkillItems as $ucsSkillItem) {
                                        $ucsSkillItem = trim($ucsSkillItem);
                                        if ($ucsSkillItem === '') {
                                            continue;
                                        }
                                        ?>
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 ring-1 ring-blue-100">
                                            <?php echo htmlspecialchars($ucsSkillItem); ?>
                                        </span>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Professional links -->
                        <?php if (!empty($ucsLinks)): ?>
                            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Professional Links</h3>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <?php foreach ($ucsLinks as $ucsLink): ?>
                                        <a href="<?php echo htmlspecialchars($ucsLink[2]); ?>" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                            <?php if ($ucsLink[0] === 'linkedin'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM7.119 20.452H3.554V9h3.565v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                            <?php elseif ($ucsLink[0] === 'github'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-800" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($ucsLink[1]); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="text-center">
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="group inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                                </svg>
                                Back to Alumni Directory
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Not found -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="alumni-not-found-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">UCSMTLA Alumni</p>
                <h1 id="alumni-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Profile Not Found</h1>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    The requested alumni profile could not be found or is not publicly available.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Browse Alumni
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>