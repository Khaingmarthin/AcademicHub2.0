<?php
/**
 * Alumni self-management - edit own profile (protected).
 *
 * Only an authenticated, verified alumnus can access this page. It lets the
 * alumnus manage the public information shown on their profile: photo,
 * occupation, professional field, biography, career journey, skills,
 * professional links, visibility.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/alumni-validation.php';

student_require_login();

$ucsProfile = alumni_current_profile($pdo);
if ($ucsProfile === null) {
    header('Location: ' . BASE_URL . '/alumni.php');
    exit;
}

$pageTitle    = 'Edit My Alumni Profile';
$pageSubtitle = 'Manage the public information on your alumni profile.';

$ucsFlash = $_SESSION['student_alumni_flash'] ?? null;
unset($_SESSION['student_alumni_flash']);

$ucsErrors = $_SESSION['student_alumni_errors'] ?? [];
unset($_SESSION['student_alumni_errors']);

$ucsOld = $_SESSION['student_alumni_old'] ?? null;
unset($_SESSION['student_alumni_old']);

$ucsForm = [
    'current_job'          => $ucsOld['current_job'] ?? (string) $ucsProfile['current_job'],
    'company'              => $ucsOld['company'] ?? (string) $ucsProfile['company'],
    'professional_field'   => $ucsOld['professional_field'] ?? (string) $ucsProfile['professional_field'],
    'skills'               => $ucsOld['skills'] ?? (string) $ucsProfile['skills'],
    'bio'                  => $ucsOld['bio'] ?? (string) $ucsProfile['bio'],
    'career_journey'       => $ucsOld['career_journey'] ?? (string) $ucsProfile['career_journey'],
    'linkedin_url'         => $ucsOld['linkedin_url'] ?? (string) $ucsProfile['linkedin_url'],
    'github_url'           => $ucsOld['github_url'] ?? (string) $ucsProfile['github_url'],
    'website_url'          => $ucsOld['website_url'] ?? (string) $ucsProfile['website_url'],
    'visibility'           => $ucsOld['visibility'] ?? (string) $ucsProfile['visibility'],
];

$ucsStudent = student_current_user();

$ucsHasPhoto = false;
$ucsPhotoUrl = '';
if (!empty($ucsProfile['profile_photo'])) {
    $ucsPhotoFile = __DIR__ . '/../assets/' . ltrim($ucsProfile['profile_photo'], '/');
    $ucsHasPhoto  = is_file($ucsPhotoFile);
    if ($ucsHasPhoto) {
        $ucsPhotoUrl = ROOT_URL . '/assets/' . ltrim($ucsProfile['profile_photo'], '/');
    }
}

$ucsDisplayName = (string) $ucsStudent['name'];

require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-1 bg-slate-50">
    <section class="py-12 sm:py-16" aria-labelledby="alumni-edit-heading">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <?php if ($ucsFlash !== null): ?>
                <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100 text-red-700' : 'bg-emerald-50 ring-emerald-100 text-emerald-700'; ?> rounded-xl px-4 py-3 ring-1" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
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
                <div class="rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
                    <p class="text-sm font-semibold text-red-700">Please fix the following:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
                        <?php foreach ($ucsErrors as $ucsError): ?>
                            <li><?php echo htmlspecialchars($ucsError); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Alumni Profile</p>
                    <h1 id="alumni-edit-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">Edit My Alumni Profile</h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600">
                        Manage the information shown on your public alumni profile. Fields you mark as private are only visible to you.
                    </p>
                </div>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsProfile['id']); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                    Back to My Profile
                </a>
            </div>

            <div class="mt-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/alumni/update-profile.php'); ?>" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">

                    <!-- Photo -->
                    <div class="border-b border-gray-100 px-6 py-6 sm:px-8">
                        <h2 class="text-base font-semibold text-gray-900">Profile Photo</h2>
                        <p class="mt-1 text-sm text-gray-500">A clear, professional photo (JPG, PNG, WebP or GIF; max 2 MB).</p>
                        <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center">
                            <?php if ($ucsHasPhoto): ?>
                                <img src="<?php echo htmlspecialchars($ucsPhotoUrl); ?>" alt="Current profile photo" class="h-20 w-20 shrink-0 rounded-2xl object-cover shadow-sm ring-1 ring-gray-200">
                            <?php else: ?>
                                <span class="inline-flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-2xl font-extrabold text-white shadow-sm" aria-hidden="true">
                                    <?php echo htmlspecialchars(strtoupper(substr(trim($ucsDisplayName), 0, 1))); ?>
                                </span>
                            <?php endif; ?>
                            <div class="min-w-0 flex-1">
                                <label for="profile_photo" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <path d="M17 8l-5-5-5 5"></path>
                                        <path d="M12 3v12"></path>
                                    </svg>
                                    <?php echo $ucsHasPhoto ? 'Change photo' : 'Upload photo'; ?>
                                </label>
                                <input type="file" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,.gif" class="sr-only">
                                <?php if ($ucsHasPhoto): ?>
                                    <label class="mt-3 inline-flex cursor-pointer items-center gap-1.5 text-sm font-medium text-red-600 transition-colors hover:text-red-700">
                                        <input type="checkbox" name="remove_photo" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Remove current photo
                                    </label>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

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
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . (int) $ucsProfile['id']); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                            Cancel
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Save Changes
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