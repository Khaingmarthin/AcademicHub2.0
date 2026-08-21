<?php
/**
 * Admin Alumni Stories module - edit form.
 */
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers/alumni-stories-validation.php';
require_once __DIR__ . '/../../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Edit Alumni Story';
$pageSubtitle = 'Update the story and its publication settings.';
$activeNav    = 'alumni';

$ucsStoryId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT st.id, st.alumni_profile_id, st.title, st.summary, st.content,
                st.career_field, st.cover_image, st.publication_date, st.status,
                st.created_at, st.updated_at,
                s.name AS student_name
         FROM alumni_stories st
         JOIN alumni_profiles ap ON ap.id = st.alumni_profile_id
         JOIN students s ON s.id = ap.student_id
         WHERE st.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsStoryId]);
    $ucsStory = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsStory = null;
}

if ($ucsStory === null || $ucsStoryId === false || $ucsStoryId < 1) {
    alumni_story_flash('error', 'Alumni story not found.');
    header('Location: ' . ROOT_URL . '/admin/alumni/stories/index.php');
    exit;
}

$pageTitle = 'Edit: ' . (string) $ucsStory['title'];

$ucsErrors = $_SESSION['alumni_story_errors'] ?? [];
unset($_SESSION['alumni_story_errors']);

$ucsOld = $_SESSION['alumni_story_old'] ?? null;
unset($_SESSION['alumni_story_old']);

$ucsAlumni = ucs_admin_alumni_profiles($pdo);

$ucsForm = [
    'alumni_profile_id' => $ucsOld['alumni_profile_id'] ?? $ucsStory['alumni_profile_id'],
    'title'             => $ucsOld['title'] ?? $ucsStory['title'],
    'career_field'      => $ucsOld['career_field'] ?? (string) ($ucsStory['career_field'] ?? ''),
    'publication_date'  => $ucsOld['publication_date'] ?? (string) ($ucsStory['publication_date'] ?? ''),
    'summary'           => $ucsOld['summary'] ?? $ucsStory['summary'],
    'content'           => $ucsOld['content'] ?? $ucsStory['content'],
    'status'            => $ucsOld['status'] ?? $ucsStory['status'],
];

$ucsCoverImage  = (string) ($ucsStory['cover_image'] ?? '');
$ucsHasCover    = false;
$ucsCoverUrl    = '';
if ($ucsCoverImage !== '') {
    $ucsCoverFile = dirname(__DIR__, 3) . '/assets/' . ltrim($ucsCoverImage, '/');
    $ucsHasCover  = is_file($ucsCoverFile);
    if ($ucsHasCover) {
        $ucsCoverUrl = ROOT_URL . '/assets/' . ltrim($ucsCoverImage, '/');
    }
}

require_once __DIR__ . '/../../../includes/admin-layout-top.php';
?>
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

<div class="mx-auto max-w-3xl">
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-6 py-5">
            <h2 class="text-base font-semibold text-gray-900">Edit Alumni Story</h2>
            <p class="mt-1 text-sm text-gray-500">Editorial content is written by admins — it is never generated from the alumnus profile automatically.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-story-update.php'); ?>" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsStoryId; ?>">

            <div class="space-y-6 px-6 py-6">
                <div>
                    <label for="alumni_profile_id" class="block text-sm font-medium text-gray-700">Alumni <span class="text-red-500">*</span></label>
                    <select id="alumni_profile_id" name="alumni_profile_id" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Select an alumnus…</option>
                        <?php foreach ($ucsAlumni as $ucsAlumnus): ?>
                            <?php
                            $ucsLabel = trim((string) $ucsAlumnus['student_name']);
                            $ucsLabel .= !empty($ucsAlumnus['major_name']) ? ' — ' . htmlspecialchars((string) $ucsAlumnus['major_name']) : '';
                            $ucsLabel .= !empty($ucsAlumnus['graduation_year']) ? ' · Class of ' . htmlspecialchars((string) $ucsAlumnus['graduation_year']) : '';
                            ?>
                            <option value="<?php echo (int) $ucsAlumnus['id']; ?>" <?php echo (int) $ucsForm['alumni_profile_id'] === (int) $ucsAlumnus['id'] ? 'selected' : ''; ?>>
                                <?php echo $ucsLabel; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ucsForm['title']); ?>" placeholder="e.g. From UCSMTLA Student to Software Developer" required maxlength="255"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="career_field" class="block text-sm font-medium text-gray-700">Career Field <span class="text-gray-400">(optional)</span></label>
                        <input type="text" id="career_field" name="career_field" value="<?php echo htmlspecialchars($ucsForm['career_field']); ?>" maxlength="255" placeholder="e.g. Software Development"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="publication_date" class="block text-sm font-medium text-gray-700">Publication Date <span class="text-gray-400">(optional)</span></label>
                        <input type="date" id="publication_date" name="publication_date" value="<?php echo htmlspecialchars($ucsForm['publication_date']); ?>"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <p class="mt-1.5 text-xs text-gray-500">Defaults to today when publishing.</p>
                    </div>
                </div>

                <div>
                    <label for="summary" class="block text-sm font-medium text-gray-700">Short Summary <span class="text-red-500">*</span></label>
                    <textarea id="summary" name="summary" rows="3" maxlength="500" placeholder="A short preview shown on the story card…" required
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['summary']); ?></textarea>
                    <p class="mt-1.5 text-xs text-gray-500">Maximum 500 characters.</p>
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">Story Content <span class="text-red-500">*</span></label>
                    <textarea id="content" name="content" rows="12" placeholder="Write the career journey — skills learned, internships, professional advice and lessons learned…" required
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['content']); ?></textarea>
                </div>

                <div>
                    <label for="cover_image" class="block text-sm font-medium text-gray-700">Cover Image <span class="text-gray-400">(optional)</span></label>
                    <?php if ($ucsHasCover): ?>
                        <div class="mt-2 flex items-center gap-4 rounded-xl bg-gray-50 p-3 ring-1 ring-gray-100">
                            <img src="<?php echo htmlspecialchars($ucsCoverUrl); ?>" alt="Current cover image" class="h-20 w-32 shrink-0 rounded-lg object-cover">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-700"><?php echo htmlspecialchars(basename($ucsCoverImage)); ?></p>
                                <p class="text-xs text-gray-500">Current cover image.</p>
                                <label class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-red-600">
                                    <input type="checkbox" name="remove_cover" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    Remove this image
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.gif,.webp"
                           class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 transition-colors hover:file:bg-blue-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <p class="mt-2 text-xs text-gray-500">JPG, PNG, GIF or WebP. Maximum size 5 MB.</p>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <?php foreach (['draft', 'published', 'unpublished'] as $ucsStatus): ?>
                            <option value="<?php echo $ucsStatus; ?>" <?php echo $ucsForm['status'] === $ucsStatus ? 'selected' : ''; ?>><?php echo ucfirst($ucsStatus); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/stories/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                    Cancel
                </a>
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                    <button type="submit" name="save_mode" value="draft" data-set-status="draft"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                        Save Draft
                    </button>
                    <button type="submit" name="save_mode" value="published" data-set-status="published"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 2 11 13"></path>
                            <path d="M22 2 15 22l-4-9-9-4Z"></path>
                        </svg>
                        Publish Story
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var statusSelect = document.getElementById('status');
    document.querySelectorAll('[data-set-status]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            statusSelect.value = btn.getAttribute('data-set-status');
        });
    });
})();
</script>
<?php require_once __DIR__ . '/../../../includes/admin-layout-bottom.php'; ?>