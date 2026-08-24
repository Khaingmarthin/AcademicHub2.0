<?php
/**
 * Admin News module - create form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/news-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Add News';
$pageSubtitle = 'Create a new news article.';
$activeNav    = 'news';

$ucsErrors = $_SESSION['news_errors'] ?? [];
unset($_SESSION['news_errors']);

$ucsOld = $_SESSION['news_old'] ?? null;
unset($_SESSION['news_old']);

$ucsCategories = ucs_admin_categories($pdo);
$ucsMajors     = ucs_admin_majors($pdo);
$ucsYearLevels = NEWS_YEAR_LEVELS;
$ucsClassrooms = ucs_admin_active_year_classrooms($pdo);

$ucsForm = [
    'category_id' => $ucsOld['category_id'] ?? '',
    'title'       => $ucsOld['title'] ?? '',
    'content'     => $ucsOld['content'] ?? '',
    'publish_at'  => $ucsOld['publish_at'] ?? '',
];

$ucsTargets = [];
if (is_array($ucsOld['targets'] ?? null)) {
    $ucsTargets = $ucsOld['targets'];
}

require_once __DIR__ . '/../../includes/admin-layout-top.php';
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
            <h2 class="text-base font-semibold text-gray-900">New News Article</h2>
            <p class="mt-1 text-sm text-gray-500">Optionally target the article at specific years, majors and sections.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/news-create.php'); ?>" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">

            <div class="space-y-6 px-6 py-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Select a category…</option>
                            <?php foreach ($ucsCategories as $ucsCategory): ?>
                                <option value="<?php echo (int) $ucsCategory['id']; ?>" <?php echo (int) $ucsForm['category_id'] === (int) $ucsCategory['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ucsCategory['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div></div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="publish_at" class="block text-sm font-medium text-gray-700">Publish Date <span class="text-gray-400">(optional)</span></label>
                        <input type="datetime-local" id="publish_at" name="publish_at" value="<?php echo htmlspecialchars($ucsForm['publish_at']); ?>"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <p class="mt-1.5 text-xs text-gray-500">Leave empty for a draft. The status updates automatically based on dates.</p>
                    </div>
                    <div>
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ucsForm['title']); ?>" placeholder="e.g. Announcement of Midterm Examination" required maxlength="255"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">Content <span class="text-red-500">*</span></label>
                    <textarea id="content" name="content" rows="8" placeholder="Write the article body…" required
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['content']); ?></textarea>
                </div>

                <div>
                    <label for="cover_image" class="block text-sm font-medium text-gray-700">Cover Image <span class="text-gray-400">(optional)</span></label>
                    <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.gif,.webp"
                           class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 transition-colors hover:file:bg-blue-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <p class="mt-2 text-xs text-gray-500">JPG, PNG, GIF or WebP. Maximum size 5 MB.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-gray-700">Audience Targeting <span class="text-gray-400">(optional)</span></label>
                        <button type="button" id="ucs-add-target"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14"></path>
                                <path d="M12 5v14"></path>
                            </svg>
                            Add target
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Leave all fields empty for a target to reach everyone.</p>

                    <div id="ucs-targets" class="mt-4 space-y-3">
                        <?php if (empty($ucsTargets)): ?>
                            <div class="ucs-target-row rounded-xl border border-dashed border-gray-300 bg-gray-50/50 p-4">
                                <div class="grid gap-3 sm:grid-cols-4">
                                    <div>
                                        <select name="target_classroom_id[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            <option value="">Any classroom</option>
                                            <?php foreach ($ucsClassrooms as $ucsCl): ?>
                                                <option value="<?php echo (int) $ucsCl['id']; ?>"><?php echo htmlspecialchars($ucsCl['class_name']); ?> (<?php echo htmlspecialchars($ucsCl['year_level']); ?> - <?php echo htmlspecialchars($ucsCl['section']); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <select name="target_major_id[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            <option value="">Any major</option>
                                            <?php foreach ($ucsMajors as $ucsMajor): ?>
                                                <option value="<?php echo (int) $ucsMajor['id']; ?>"><?php echo htmlspecialchars($ucsMajor['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <select name="target_year_level[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            <option value="">Any level</option>
                                            <?php foreach ($ucsYearLevels as $ucsLevel): ?>
                                                <option value="<?php echo $ucsLevel; ?>"><?php echo $ucsLevel; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="flex gap-2">
                                        <input type="text" name="target_section[]" placeholder="Section" maxlength="10"
                                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                        <button type="button" class="ucs-remove-target inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition-colors duration-150 hover:bg-red-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600" aria-label="Remove target">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M18 6 6 18"></path>
                                                <path d="m6 6 12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($ucsTargets as $ucsIndex => $ucsTarget): ?>
                                <div class="ucs-target-row rounded-xl border border-dashed border-gray-300 bg-gray-50/50 p-4">
                                    <div class="grid gap-3 sm:grid-cols-4">
                                        <div>
                                            <select name="target_classroom_id[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                                <option value="">Any classroom</option>
                                                <?php foreach ($ucsClassrooms as $ucsCl): ?>
                                                    <option value="<?php echo (int) $ucsCl['id']; ?>" <?php echo (int) $ucsTarget['classroom_id'] === (int) $ucsCl['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsCl['class_name']); ?> (<?php echo htmlspecialchars($ucsCl['year_level']); ?> - <?php echo htmlspecialchars($ucsCl['section']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <select name="target_major_id[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                                <option value="">Any major</option>
                                                <?php foreach ($ucsMajors as $ucsMajor): ?>
                                                    <option value="<?php echo (int) $ucsMajor['id']; ?>" <?php echo (int) $ucsTarget['major_id'] === (int) $ucsMajor['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsMajor['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <select name="target_year_level[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                                <option value="">Any level</option>
                                                <?php foreach ($ucsYearLevels as $ucsLevel): ?>
                                                    <option value="<?php echo $ucsLevel; ?>" <?php echo (string) $ucsTarget['year_level'] === $ucsLevel ? 'selected' : ''; ?>><?php echo $ucsLevel; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="flex gap-2">
                                            <input type="text" name="target_section[]" placeholder="Section" maxlength="10" value="<?php echo htmlspecialchars((string) ($ucsTarget['section'] ?? '')); ?>"
                                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            <button type="button" class="ucs-remove-target inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition-colors duration-150 hover:bg-red-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600" aria-label="Remove target">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M18 6 6 18"></path>
                                                    <path d="m6 6 12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/news/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Create News Article
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var yearLevels = <?php echo json_encode($ucsYearLevels); ?>;
    var classrooms = <?php echo json_encode($ucsClassrooms); ?>;
    var majors = <?php echo json_encode($ucsMajors); ?>;

    function yearLevelOptions(selected) {
        var html = '<option value="">Any level</option>';
        yearLevels.forEach(function (level) {
            html += '<option value="' + level + '"' + (String(selected) === level ? ' selected' : '') + '>' + level + '</option>';
        });
        return html;
    }

    function classroomOptions(selected) {
        var html = '<option value="">Any classroom</option>';
        classrooms.forEach(function (cl) {
            html += '<option value="' + cl.id + '"' + (String(selected) === String(cl.id) ? ' selected' : '') + '>' + cl.class_name + ' (' + cl.year_level + ' - ' + cl.section + ')</option>';
        });
        return html;
    }

    function majorOptions(selected) {
        var html = '<option value="">Any major</option>';
        majors.forEach(function (major) {
            html += '<option value="' + major.id + '"' + (String(selected) === String(major.id) ? ' selected' : '') + '>' + major.name + '</option>';
        });
        return html;
    }

    function buildRow(selected) {
        selected = selected || {};
        var div = document.createElement('div');
        div.className = 'ucs-target-row rounded-xl border border-dashed border-gray-300 bg-gray-50/50 p-4';
        div.innerHTML =
            '<div class="grid gap-3 sm:grid-cols-4">' +
                '<div><select name="target_classroom_id[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">' + classroomOptions(selected.classroom_id) + '</select></div>' +
                '<div><select name="target_major_id[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">' + majorOptions(selected.major_id) + '</select></div>' +
                '<div><select name="target_year_level[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">' + yearLevelOptions(selected.year_level) + '</select></div>' +
                '<div class="flex gap-2">' +
                    '<input type="text" name="target_section[]" placeholder="Section" maxlength="10" value="' + (selected.section || '') + '" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">' +
                    '<button type="button" class="ucs-remove-target inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition-colors duration-150 hover:bg-red-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600" aria-label="Remove target">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>' +
                    '</button>' +
                '</div>' +
            '</div>';
        return div;
    }

    document.getElementById('ucs-add-target').addEventListener('click', function () {
        document.getElementById('ucs-targets').appendChild(buildRow({}));
    });

    document.getElementById('ucs-targets').addEventListener('click', function (e) {
        if (e.target.closest('.ucs-remove-target')) {
            var row = e.target.closest('.ucs-target-row');
            if (row) {
                row.parentNode.removeChild(row);
            }
        }
    });
})();
</script>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>