<?php
/**
 * Alumni self-service - create story (protected).
 *
 * Only a verified alumnus can access this page. It lets the alumnus submit
 * a story for admin review. The story is stored with status 'pending' and
 * must be approved by an admin before it appears publicly.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/alumni-validation.php';
require_once __DIR__ . '/../includes/helpers/alumni-stories-validation.php';

student_require_login();

$ucsProfile = alumni_current_profile($pdo);
if ($ucsProfile === null) {
    header('Location: ' . BASE_URL . '/alumni.php');
    exit;
}

$pageTitle    = 'Share Your Story';
$pageSubtitle = 'Submit your alumni experience for review by the university office.';

$ucsFlash = $_SESSION['alumni_story_flash'] ?? null;
unset($_SESSION['alumni_story_flash']);

$ucsErrors = $_SESSION['alumni_story_errors'] ?? [];
unset($_SESSION['alumni_story_errors']);

$ucsOld = $_SESSION['alumni_story_old'] ?? null;
unset($_SESSION['alumni_story_old']);

$ucsForm = [
    'title'        => $ucsOld['title'] ?? '',
    'summary'      => $ucsOld['summary'] ?? '',
    'content'      => $ucsOld['content'] ?? '',
    'career_field' => $ucsOld['career_field'] ?? '',
];

$ucsStudent = student_current_user();
$ucsDisplayName = (string) ($ucsStudent['name'] ?? 'Alumnus');

require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-1 bg-slate-50">
    <section class="py-12 sm:py-16" aria-labelledby="alumni-story-create-heading">
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
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Alumni Stories</p>
                    <h1 id="alumni-story-create-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">Share Your Story</h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600">
                        Write about your university experience, career journey, internship, first job, skills learned, challenges after graduation or advice for current students.
                    </p>
                </div>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            <div class="mt-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/alumni/story-submit.php'); ?>" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">

                    <div class="border-b border-gray-100 px-6 py-6 sm:px-8">
                        <h2 class="text-base font-semibold text-gray-900">Story Details</h2>
                        <p class="mt-1 text-sm text-gray-500">Your story will be reviewed by the university office before publication.</p>

                        <div class="mt-5 space-y-5">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ucsForm['title']); ?>" maxlength="255" placeholder="e.g. From UCSMTLA Student to Software Developer" required
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>

                            <div>
                                <label for="career_field" class="block text-sm font-medium text-gray-700">Career Field <span class="text-gray-400">(optional)</span></label>
                                <input type="text" id="career_field" name="career_field" value="<?php echo htmlspecialchars($ucsForm['career_field']); ?>" maxlength="255" placeholder="e.g. Software Development"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>

                            <div>
                                <label for="summary" class="block text-sm font-medium text-gray-700">Short Summary <span class="text-red-500">*</span></label>
                                <textarea id="summary" name="summary" rows="3" maxlength="500" placeholder="A short preview shown on the story card…" required
                                          class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['summary']); ?></textarea>
                                <p class="mt-1.5 text-xs text-gray-500">Maximum 500 characters.</p>
                            </div>

                            <div>
                                <label for="content" class="block text-sm font-medium text-gray-700">Story Content <span class="text-red-500">*</span></label>
                                <textarea id="content" name="content" rows="12" placeholder="Write your career journey — skills learned, internships, professional advice and lessons learned…" required
                                          class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['content']); ?></textarea>
                            </div>

                            <div>
                                <label for="cover_image" class="block text-sm font-medium text-gray-700">Cover Image <span class="text-gray-400">(optional)</span></label>
                                <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.gif,.webp"
                                       class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 transition-colors hover:file:bg-blue-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                <p class="mt-2 text-xs text-gray-500">JPG, PNG, GIF or WebP. Maximum size 5 MB.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                            Cancel
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 2 11 13"></path>
                                <path d="M22 2 15 22l-4-9-9-4Z"></path>
                            </svg>
                            Submit for Review
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
