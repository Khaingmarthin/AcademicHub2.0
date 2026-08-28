<?php
/**
 * Public "Start a Career Discussion" page.
 *
 * Logged-in students (including verified alumni) start a new career
 * discussion. Guests are redirected to Student Login. The form posts to
 * actions/discussion/create.php and is protected by the student CSRF token.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/discussion-validation.php';

student_require_login();

$ucsUser = student_current_user();

$pageTitle = 'Start a Career Discussion';

$ucsErrors = $_SESSION['discussion_errors'] ?? [];
unset($_SESSION['discussion_errors']);

$ucsOld = $_SESSION['discussion_old'] ?? [];
unset($_SESSION['discussion_old']);

$ucsCategories = discussion_load_categories($pdo, true);

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

$ucsIsAlumni = discussion_is_verified_alumni($pdo, (int) $ucsUser['id']);

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Breadcrumb -->
    <nav class="border-b border-slate-200 bg-white py-3" aria-label="Breadcrumb">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <ol class="flex items-center gap-1.5 text-sm text-slate-500">
                <li><a href="<?php echo htmlspecialchars(BASE_URL . '/'); ?>" class="font-medium text-slate-600 transition-colors hover:text-blue-600">Home</a></li>
                <li aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
                <li><a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="font-medium text-slate-600 transition-colors hover:text-blue-600">Career Discussions</a></li>
                <li aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
                <li aria-current="page" class="font-semibold text-slate-900">Start a Discussion</li>
            </ol>
        </div>
    </nav>
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-slate-900" aria-labelledby="career-discussion-create-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Career Discussions</p>
                <h1 id="career-discussion-create-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Start a Career Discussion</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-slate-200 sm:text-lg sm:leading-8">
                    <?php echo $ucsIsAlumni
                        ? 'Share your career experience, offer advice, or start a topic that helps current students navigate their professional journey.'
                        : 'Browse discussions started by verified alumni and join the conversation with your questions and insights.'; ?>
                </p>
            </div>
        </div>
    </section>

    <!-- Form -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="career-discussion-form-heading">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-10">
                <h2 id="career-discussion-form-heading" class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Your discussion topic</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Share career advice, lessons from your professional journey, or a topic that would benefit current students. Remember this is a public, moderated community — please keep it respectful.
                </p>

                <?php if (!empty($ucsErrors)): ?>
                    <div class="mt-6 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
                        <ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
                            <?php foreach ($ucsErrors as $ucsError): ?>
                                <li><?php echo htmlspecialchars($ucsError); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/discussion/create.php'); ?>" class="mt-8 space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">

                    <div>
                        <label for="category_id" class="block text-sm font-semibold text-slate-700">Category</label>
                        <select id="category_id" name="category_id" required
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Select a category…</option>
                            <?php foreach ($ucsCategories as $ucsCat): ?>
                                <option value="<?php echo (int) $ucsCat['id']; ?>" <?php echo isset($ucsOld['category_id']) && (int) $ucsOld['category_id'] === (int) $ucsCat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $ucsCat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-semibold text-slate-700">Discussion title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars((string) ($ucsOld['title'] ?? '')); ?>" required maxlength="255" placeholder="e.g. How I landed my first software developer role after graduation"
                               class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-semibold text-slate-700">Description</label>
                        <textarea id="content" name="content" rows="7" required placeholder="Share the details — your background, what worked for you, lessons learned, and what advice you would give to students starting out."
                                  class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars((string) ($ucsOld['content'] ?? '')); ?></textarea>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <?php if ($ucsIsAlumni): ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                                    </svg>
                                    Back to Dashboard
                                </a>
                            <?php elseif ($ucsUser !== null): ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                                    </svg>
                                    Back to Dashboard
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50">
                                Cancel
                            </a>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                            </svg>
                            Post Discussion
                        </button>
                    </div>
                </form>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-slate-400">
                All discussions are moderated by UCSMTLA staff. Inappropriate content may be removed.
            </p>
        </div>
    </section>
</main>
<?php
require_once '../includes/footer.php';
?>