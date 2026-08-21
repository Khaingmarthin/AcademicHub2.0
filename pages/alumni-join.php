<?php
/**
 * Alumni self-service - join the alumni community (protected).
 *
 * Lets an officially graduated student apply for an alumni profile by
 * submitting their career information. The application is stored with
 * verification_status 'pending' and is reviewed by an authorised admin.
 * A previously rejected application can be resubmitted from this page.
 * Graduated students who already have a pending or verified profile are
 * sent to the alumni dashboard instead.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/alumni-validation.php';

student_require_login();

$ucsStudent = student_current_user();

$ucsAcademicStatus = 'active';
$ucsProfileStatus  = null;
try {
    $ucsStmt = $pdo->prepare(
        "SELECT s.student_status, ap.verification_status AS profile_status
         FROM students s
         LEFT JOIN alumni_profiles ap ON ap.student_id = s.id
         WHERE s.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => (int) $ucsStudent['id']]);
    $ucsRow = $ucsStmt->fetch() ?: null;
    if ($ucsRow !== null) {
        $ucsAcademicStatus = (string) ($ucsRow['student_status'] ?? 'active');
        $ucsProfileStatus  = $ucsRow['profile_status'] ?? null;
    }
} catch (PDOException $e) {
    $ucsAcademicStatus = 'active';
    $ucsProfileStatus  = null;
}

// Non-graduates are not part of the alumni community.
if ($ucsAcademicStatus !== 'graduated') {
    header('Location: ' . BASE_URL . '/alumni-overview.php');
    exit;
}

// Already has a pending or verified profile -> the dashboard shows its state.
if ($ucsProfileStatus !== null && $ucsProfileStatus !== 'rejected') {
    header('Location: ' . BASE_URL . '/alumni-dashboard.php');
    exit;
}

$pageTitle    = 'Join the Alumni Community';
$pageSubtitle = 'Apply for your alumni profile at UCSMTLA.';

$ucsFlash = $_SESSION['student_alumni_flash'] ?? null;
unset($_SESSION['student_alumni_flash']);

$ucsErrors = $_SESSION['student_alumni_errors'] ?? [];
unset($_SESSION['student_alumni_errors']);

$ucsOld = $_SESSION['student_alumni_old'] ?? null;
unset($_SESSION['student_alumni_old']);

$ucsIsResubmit = $ucsProfileStatus === 'rejected';

$ucsForm = [
    'current_job'          => $ucsOld['current_job'] ?? '',
    'company'              => $ucsOld['company'] ?? '',
    'professional_field'   => $ucsOld['professional_field'] ?? '',
    'skills'               => $ucsOld['skills'] ?? '',
    'bio'                  => $ucsOld['bio'] ?? '',
    'career_journey'       => $ucsOld['career_journey'] ?? '',
    'linkedin_url'         => $ucsOld['linkedin_url'] ?? '',
    'github_url'           => $ucsOld['github_url'] ?? '',
    'website_url'          => $ucsOld['website_url'] ?? '',
    'visibility'           => $ucsOld['visibility'] ?? 'private',
];

$ucsDisplayName = (string) $ucsStudent['name'];

require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-1 bg-slate-50">
    <section class="py-12 sm:py-16" aria-labelledby="alumni-join-heading">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">UCSMTLA Alumni &amp; Career Community</p>
                    <h1 id="alumni-join-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                        <?php echo $ucsIsResubmit ? 'Resubmit Your Alumni Profile' : 'Join the Alumni Community'; ?>
                    </h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600">
                        <?php echo $ucsIsResubmit
                            ? 'Your previous application was not approved. Please review and resubmit your details for another review.'
                            : 'Congratulations on your graduation! Tell us a little about your career so we can set up your alumni profile.'; ?>
                    </p>
                </div>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-overview.php'); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                    Back to Overview
                </a>
            </div>

            <?php if ($ucsFlash !== null): ?>
                <div class="mt-6 <?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100 text-red-700' : 'bg-emerald-50 ring-emerald-100 text-emerald-700'; ?> rounded-xl px-4 py-3 ring-1" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
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

            <?php if (!empty($ucsErrors)): ?>
                <div class="mt-6 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
                    <ul class="list-disc space-y-1 pl-5 text-sm font-medium text-red-700">
                        <?php foreach ($ucsErrors as $ucsError): ?>
                            <li><?php echo htmlspecialchars((string) $ucsError); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="mt-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/alumni/join.php'); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">

                    <!-- Professional information -->
                    <div class="border-b border-gray-100 px-6 py-6 sm:px-8">
                        <h2 class="text-base font-semibold text-gray-900">Professional Information</h2>
                        <p class="mt-1 text-sm text-gray-500">Your current occupation and professional field.</p>
                        <div class="mt-5 grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="current_job" class="block text-sm font-medium text-gray-700">Current Job <span class="text-gray-400">(optional)</span></label>
                                <input type="text" id="current_job" name="current_job" value="<?php echo htmlspecialchars($ucsForm['current_job']); ?>" maxlength="255" placeholder="e.g. Software Engineer"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="company" class="block text-sm font-medium text-gray-700">Company <span class="text-gray-400">(optional)</span></label>
                                <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($ucsForm['company']); ?>" maxlength="255" placeholder="e.g. Acme Ltd"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="professional_field" class="block text-sm font-medium text-gray-700">Professional Field <span class="text-gray-400">(optional)</span></label>
                                <input type="text" id="professional_field" name="professional_field" value="<?php echo htmlspecialchars($ucsForm['professional_field']); ?>" maxlength="255" placeholder="e.g. Information Technology"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>
                    </div>

                    <!-- About -->
                    <div class="border-b border-gray-100 px-6 py-6 sm:px-8">
                        <h2 class="text-base font-semibold text-gray-900">About</h2>
                        <p class="mt-1 text-sm text-gray-500">A short biography introducing yourself.</p>
                        <label for="bio" class="sr-only">Biography</label>
                        <textarea id="bio" name="bio" rows="5" maxlength="5000" placeholder="Tell the community about yourself…"
                                  class="mt-4 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['bio']); ?></textarea>
                    </div>

                    <!-- Career journey -->
                    <div class="border-b border-gray-100 px-6 py-6 sm:px-8">
                        <h2 class="text-base font-semibold text-gray-900">Career Journey</h2>
                        <p class="mt-1 text-sm text-gray-500">Optional: highlight key roles, achievements or milestones in your career so far.</p>
                        <label for="career_journey" class="sr-only">Career journey</label>
                        <textarea id="career_journey" name="career_journey" rows="5" maxlength="5000" placeholder="Share your professional journey…"
                                  class="mt-4 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['career_journey']); ?></textarea>
                    </div>

                    <!-- Skills -->
                    <div class="border-b border-gray-100 px-6 py-6 sm:px-8">
                        <h2 class="text-base font-semibold text-gray-900">Skills</h2>
                        <p class="mt-1 text-sm text-gray-500">Comma-separated or line-separated skills, or any free-form text.</p>
                        <label for="skills" class="sr-only">Skills</label>
                        <textarea id="skills" name="skills" rows="4" maxlength="2000" placeholder="e.g. PHP, Laravel, Leadership"
                                  class="mt-4 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['skills']); ?></textarea>
                    </div>

                    <!-- Professional links -->
                    <div class="border-b border-gray-100 px-6 py-6 sm:px-8">
                        <h2 class="text-base font-semibold text-gray-900">Professional Links</h2>
                        <p class="mt-1 text-sm text-gray-500">Share a LinkedIn, GitHub or personal website. Optional.</p>
                        <div class="mt-5 grid gap-6 sm:grid-cols-3">
                            <div>
                                <label for="linkedin_url" class="block text-sm font-medium text-gray-700">LinkedIn URL</label>
                                <input type="url" id="linkedin_url" name="linkedin_url" value="<?php echo htmlspecialchars($ucsForm['linkedin_url']); ?>" maxlength="191" placeholder="https://linkedin.com/in/…"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="github_url" class="block text-sm font-medium text-gray-700">GitHub URL</label>
                                <input type="url" id="github_url" name="github_url" value="<?php echo htmlspecialchars($ucsForm['github_url']); ?>" maxlength="191" placeholder="https://github.com/…"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="website_url" class="block text-sm font-medium text-gray-700">Website URL</label>
                                <input type="url" id="website_url" name="website_url" value="<?php echo htmlspecialchars($ucsForm['website_url']); ?>" maxlength="191" placeholder="https://example.com"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>
                    </div>

                    <!-- Visibility -->
                    <div class="px-6 py-6 sm:px-8">
                        <h2 class="text-base font-semibold text-gray-900">Profile Settings</h2>
                        <div class="mt-5">
                            <label for="visibility" class="block text-sm font-medium text-gray-700">Profile Visibility</label>
                            <select id="visibility" name="visibility" required
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                <option value="private" <?php echo $ucsForm['visibility'] === 'private' ? 'selected' : ''; ?>>Private (hidden from the public directory)</option>
                                <option value="public" <?php echo $ucsForm['visibility'] === 'public' ? 'selected' : ''; ?>>Public (visible to students and visitors)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-overview.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                            Cancel
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <?php echo $ucsIsResubmit ? 'Resubmit Application' : 'Submit Application'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>