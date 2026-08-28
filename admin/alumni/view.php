<?php
/**
 * Admin Alumni module - view profile.
 *
 * Shows a single alumni profile joined with the existing student identity.
 * Only non-sensitive information is displayed; private data such as passwords,
 * contact details and address are never queried here.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';

admin_require_login();

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

$ucsProfile = null;
try {
    $ucsStmt = $pdo->prepare(
        "SELECT ap.id, ap.current_job, ap.company, ap.professional_field,
                ap.skills, ap.bio, ap.career_journey,
                ap.linkedin_url, ap.github_url, ap.website_url,
                ap.visibility, ap.verification_status,
                ap.created_at, ap.updated_at,
                s.id AS student_id, s.name AS student_name,
                s.student_id AS student_code, s.roll_number,
                s.status AS account_status, s.student_status, s.graduation_year,
                s.email,
                m.name AS major_name, cl.classroom_name
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         LEFT JOIN classrooms cl ON cl.id = s.classroom_id
         LEFT JOIN majors m ON m.id = cl.major_id
         WHERE ap.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsProfile = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsProfile = null;
}

if ($ucsProfile === null || $ucsId === false || $ucsId < 1) {
    alumni_flash('error', 'Alumni Profile not found.');
    header('Location: ' . ROOT_URL . '/admin/alumni/index.php');
    exit;
}

$ucsFlash = $_SESSION['alumni_flash'] ?? null;
unset($_SESSION['alumni_flash']);

$pageTitle    = 'Alumni Profile';
$pageSubtitle = 'View an alumnus profile.';
$activeNav    = 'alumni';

$ucsProfileName = (string) $ucsProfile['student_name'];
$ucsJsName      = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsProfileName);

$ucsIsPending  = $ucsProfile['verification_status'] === 'pending';
$ucsIsVerified = $ucsProfile['verification_status'] === 'verified';
$ucsIsRejected = $ucsProfile['verification_status'] === 'rejected';

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

<div class="grid gap-6 lg:grid-cols-3">
    <!-- Profile card -->
    <div class="lg:col-span-1">
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <div class="flex flex-col items-center border-b border-gray-100 px-6 py-8 text-center">
                <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-600/20" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </span>
                <h2 class="mt-4 text-lg font-bold text-gray-900"><?php echo htmlspecialchars($ucsProfileName); ?></h2>
                <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars((string) $ucsProfile['student_code']); ?> &middot; <?php echo htmlspecialchars((string) $ucsProfile['roll_number']); ?></p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    <?php if ($ucsIsVerified): ?>
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Verified</span>
                    <?php elseif ($ucsIsRejected): ?>
                        <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-red-200">Rejected</span>
                    <?php else: ?>
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Pending</span>
                    <?php endif; ?>
                    <?php if ($ucsProfile['visibility'] === 'public'): ?>
                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">Public</span>
                    <?php else: ?>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-500 ring-1 ring-gray-200">Private</span>
                    <?php endif; ?>

                </div>
            </div>

            <div class="space-y-4 px-6 py-6">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Major</p>
                        <p class="mt-0.5 text-sm font-medium text-gray-800"><?php echo htmlspecialchars((string) $ucsProfile['major_name']); ?></p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Academic Status</p>
                        <p class="mt-0.5 text-sm font-medium text-gray-800"><?php echo ucfirst(htmlspecialchars((string) $ucsProfile['student_status'])); ?></p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Graduation</p>
                        <p class="mt-0.5 text-sm font-medium text-gray-800">
                            <?php if (!empty($ucsProfile['graduation_year'])): ?>
                                Class of <?php echo htmlspecialchars((string) $ucsProfile['graduation_year']); ?>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 4v16h16"></path>
                        <path d="m4 20 4-8 4 4 4-6 4 4"></path>
                    </svg>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Created</p>
                        <p class="mt-0.5 text-sm font-medium text-gray-800"><?php echo htmlspecialchars(date('j M Y', strtotime((string) $ucsProfile['created_at']))); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Career Information</h2>
                    <p class="mt-1 text-sm text-gray-500">Current occupation and professional links shared by the alumnus.</p>
                </div>
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/edit.php?id=' . (int) $ucsProfile['id']); ?>"
                   class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                        <path d="m15 5 4 4"></path>
                    </svg>
                    Edit Profile
                </a>
            </div>

            <div class="grid gap-6 px-6 py-6 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Current Job</p>
                    <p class="mt-1.5 text-sm font-medium text-gray-800"><?php echo !empty($ucsProfile['current_job']) ? htmlspecialchars((string) $ucsProfile['current_job']) : '<span class="text-gray-400">—</span>'; ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Company / Organization</p>
                    <p class="mt-1.5 text-sm font-medium text-gray-800"><?php echo !empty($ucsProfile['company']) ? htmlspecialchars((string) $ucsProfile['company']) : '<span class="text-gray-400">—</span>'; ?></p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Professional Field</p>
                    <p class="mt-1.5 text-sm font-medium text-gray-800"><?php echo !empty($ucsProfile['professional_field']) ? htmlspecialchars((string) $ucsProfile['professional_field']) : '<span class="text-gray-400">—</span>'; ?></p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Skills</p>
                    <p class="mt-1.5 text-sm font-medium leading-relaxed text-gray-800"><?php echo !empty($ucsProfile['skills']) ? nl2br(htmlspecialchars((string) $ucsProfile['skills'])) : '<span class="text-gray-400">—</span>'; ?></p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Biography</p>
                    <p class="mt-1.5 text-sm font-medium leading-relaxed text-gray-800"><?php echo !empty($ucsProfile['bio']) ? nl2br(htmlspecialchars((string) $ucsProfile['bio'])) : '<span class="text-gray-400">—</span>'; ?></p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Career Journey</p>
                    <p class="mt-1.5 text-sm font-medium leading-relaxed text-gray-800"><?php echo !empty($ucsProfile['career_journey']) ? nl2br(htmlspecialchars((string) $ucsProfile['career_journey'])) : '<span class="text-gray-400">—</span>'; ?></p>
                </div>
            </div>

            <?php
            $ucsLinks = [];
            if (!empty($ucsProfile['linkedin_url'])) { $ucsLinks[] = ['linkedin', 'LinkedIn', (string) $ucsProfile['linkedin_url']]; }
            if (!empty($ucsProfile['github_url']))   { $ucsLinks[] = ['github', 'GitHub', (string) $ucsProfile['github_url']]; }
            if (!empty($ucsProfile['website_url']))  { $ucsLinks[] = ['globe', 'Website', (string) $ucsProfile['website_url']]; }
            ?>
            <?php if (!empty($ucsLinks)): ?>
                <div class="border-t border-gray-100 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Professional Links</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php foreach ($ucsLinks as $ucsLink): ?>
                            <a href="<?php echo htmlspecialchars($ucsLink[2]); ?>" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
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
        </div>

        <!-- Verification -->
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Verification</h2>
                    <p class="mt-1 text-sm text-gray-500">Verify to activate the alumni profile, or reject to deactivate it.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <?php if ($ucsIsPending || $ucsIsRejected): ?>
                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-verify.php'); ?>" class="inline-flex">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $ucsProfile['id']; ?>">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-emerald-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                                <?php echo $ucsIsRejected ? 'Activate' : 'Verify'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($ucsIsPending || $ucsIsVerified): ?>
                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-reject.php'); ?>" class="inline-flex"
                              onsubmit="return confirm('<?php echo $ucsIsVerified ? 'Deactivate' : 'Reject'; ?> alumnus &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $ucsProfile['id']; ?>">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 transition-colors duration-150 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                <?php echo $ucsIsVerified ? 'Deactivate' : 'Reject'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Alumni Email</p>
                <p class="mt-1.5 text-sm font-medium text-gray-800"><?php echo htmlspecialchars((string) $ucsProfile['email']); ?></p>
                <p class="mt-4 text-xs text-gray-400">Last updated <?php echo htmlspecialchars(date('j M Y', strtotime((string) $ucsProfile['updated_at']))); ?>.</p>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/index.php'); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Back to Alumni
            </a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>