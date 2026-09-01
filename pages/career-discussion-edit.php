<?php
/**
 * Career Discussion Edit page.
 *
 * Allows the author of a discussion to edit its title, category and content.
 * Guests and non-authors are redirected away. Hidden discussions are treated
 * as not found.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/discussion-validation.php';

student_require_login();

$ucsUser = student_current_user();

$ucsDiscussionId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

$ucsDiscussion = null;

if ($ucsDiscussionId !== false && $ucsDiscussionId > 0) {
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT d.id, d.title, d.content, d.category_id, d.status,
                    d.author_student_id,
                    c.name AS category_name
             FROM discussions d
             JOIN discussion_categories c ON c.id = d.category_id
             WHERE d.id = :id AND d.status <> 'hidden'
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $ucsDiscussionId]);
        $ucsDiscussion = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsDiscussion = null;
    }
}

if ($ucsDiscussion === null) {
    discussion_flash('error', 'Discussion not found.');
    header('Location: ' . BASE_URL . '/career-discussions.php');
    exit;
}

if ((int) $ucsDiscussion['author_student_id'] !== (int) $ucsUser['id']) {
    discussion_flash('error', 'You can only edit your own discussions.');
    header('Location: ' . BASE_URL . '/career-discussion-details.php?id=' . $ucsDiscussionId);
    exit;
}

if ((string) $ucsDiscussion['status'] === 'closed') {
    discussion_flash('error', 'This discussion has been closed and cannot be edited.');
    header('Location: ' . BASE_URL . '/career-discussion-details.php?id=' . $ucsDiscussionId);
    exit;
}

$pageTitle = 'Edit Discussion';

$ucsErrors = $_SESSION['discussion_errors'] ?? [];
unset($_SESSION['discussion_errors']);

$ucsOld = $_SESSION['discussion_old'] ?? null;
unset($_SESSION['discussion_old']);

$ucsForm = [
    'category_id' => $ucsOld['category_id'] ?? (int) $ucsDiscussion['category_id'],
    'title'       => $ucsOld['title'] ?? $ucsDiscussion['title'],
    'content'     => $ucsOld['content'] ?? $ucsDiscussion['content'],
];

$ucsCategories = discussion_load_categories($pdo, true);

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
                <li aria-current="page" class="font-semibold text-slate-900">Edit Discussion</li>
            </ol>
        </div>
    </nav>

    <!-- Form -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="career-discussion-edit-heading">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-10">
                <h2 id="career-discussion-edit-heading" class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Edit your discussion</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Update the title, category or description of your discussion.
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

                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/discussion/update.php'); ?>" class="mt-8 space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">

                    <div>
                        <label for="category_id" class="block text-sm font-semibold text-slate-700">Category</label>
                        <select id="category_id" name="category_id" required
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Select a category...</option>
                            <?php foreach ($ucsCategories as $ucsCat): ?>
                                <option value="<?php echo (int) $ucsCat['id']; ?>" <?php echo (int) $ucsForm['category_id'] === (int) $ucsCat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $ucsCat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-semibold text-slate-700">Discussion title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars((string) $ucsForm['title']); ?>" required maxlength="255" placeholder="e.g. How I landed my first software developer role after graduation"
                               class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-semibold text-slate-700">Description</label>
                        <textarea id="content" name="content" rows="7" required placeholder="Share the details - your background, what worked for you, lessons learned, and what advice you would give to students starting out."
                                  class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars((string) $ucsForm['content']); ?></textarea>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussion-details.php?id=' . (int) $ucsDiscussion['id']); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50">
                            Cancel
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                <path d="m15 5 4 4"></path>
                            </svg>
                            Save Changes
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
