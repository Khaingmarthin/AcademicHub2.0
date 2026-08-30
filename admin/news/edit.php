<?php
/**
 * Admin News module - edit form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/news-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Edit News';
$pageSubtitle = 'Update a news article.';
$activeNav    = 'news';

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, category_id, title, content, cover_image, published_at, status
         FROM news
         WHERE id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsNews = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsNews = null;
}

if ($ucsNews === null || $ucsId === false || $ucsId < 1) {
    $_SESSION['news_flash'] = ['type' => 'error', 'message' => 'News article not found.'];
    header('Location: ' . ROOT_URL . '/admin/news/index.php');
    exit;
}

$ucsErrors = $_SESSION['news_errors'] ?? [];
unset($_SESSION['news_errors']);

$ucsOld = $_SESSION['news_old'] ?? null;
unset($_SESSION['news_old']);

$ucsForm = [
    'category_id' => $ucsOld['category_id'] ?? (int) $ucsNews['category_id'],
    'title'       => $ucsOld['title'] ?? $ucsNews['title'],
    'content'     => $ucsOld['content'] ?? $ucsNews['content'],
    'publish_at'  => $ucsOld['publish_at'] ?? (!empty($ucsNews['published_at']) ? date('Y-m-d\TH:i', strtotime($ucsNews['published_at'])) : ''),
];

$ucsCategories = ucs_admin_categories($pdo);
$ucsMajors     = ucs_admin_majors($pdo);
$ucsYearLevels = NEWS_YEAR_LEVELS;
$ucsClassrooms = ucs_admin_active_year_classrooms($pdo);

$ucsTargets = [];
if (is_array($ucsOld['targets'] ?? null)) {
    $ucsTargets = $ucsOld['targets'];
} else {
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT classroom_id, major_id, year_level, section
             FROM news_targets
             WHERE news_id = :news_id
             ORDER BY id ASC"
        );
        $ucsStmt->execute([':news_id' => $ucsId]);
        $ucsTargets = $ucsStmt->fetchAll();
    } catch (PDOException $e) {
        $ucsTargets = [];
    }
}

$ucsCurrentCover = (string) ($ucsNews['cover_image'] ?? '');

// Fetch existing gallery images.
$ucsGalleryImages = [];
try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, image_path FROM news_images WHERE news_id = :news_id ORDER BY sort_order ASC"
    );
    $ucsStmt->execute([':news_id' => $ucsId]);
    $ucsGalleryImages = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsGalleryImages = [];
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
            <h2 class="text-base font-semibold text-gray-900">Edit News Article</h2>
            <p class="mt-1 text-sm text-gray-500">Leave the cover image empty to keep the current one.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/news-update.php'); ?>" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsNews['id']; ?>">

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
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Current Status</label>
                        <div class="mt-2 flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">
                            <?php
                            $ucsCurrentStatus = $ucsNews['status'];
                            $ucsStatusColors  = [
                                'Draft'     => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200',
                                'Published' => 'bg-green-50 text-green-700 ring-1 ring-green-200',
                                'Expired'   => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                            ];
                            ?>
                            <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide <?php echo $ucsStatusColors[$ucsCurrentStatus] ?? 'bg-gray-100 text-gray-500'; ?>">
                                <?php echo htmlspecialchars($ucsCurrentStatus); ?>
                            </span>
                            <span class="text-xs text-gray-500">Automatically derived from dates.</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="publish_at" class="block text-sm font-medium text-gray-700">Publish Date <span class="text-gray-400">(optional)</span></label>
                        <input type="datetime-local" id="publish_at" name="publish_at" value="<?php echo htmlspecialchars($ucsForm['publish_at']); ?>"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <p class="mt-1.5 text-xs text-gray-500">Status updates automatically based on dates.</p>
                    </div>
                    <div>
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ucsForm['title']); ?>" required maxlength="255"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">Content <span class="text-red-500">*</span></label>
                    <textarea id="content" name="content" rows="8" required
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['content']); ?></textarea>
                </div>

                <?php if ($ucsCurrentCover !== ''): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Current Cover Image</label>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <span class="inline-flex h-20 w-32 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-200" aria-hidden="true">
                                <img src="<?php echo htmlspecialchars(ROOT_URL . '/assets/' . ltrim($ucsCurrentCover, '/')); ?>" alt="" class="h-full w-full object-cover">
                            </span>
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-red-600">
                                <input type="checkbox" name="remove_cover" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-100">
                                Remove this image
                            </label>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="cover_image" class="block text-sm font-medium text-gray-700">Replace Cover Image <span class="text-gray-400">(optional)</span></label>
                    <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.gif,.webp"
                           class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 transition-colors hover:file:bg-blue-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <p class="mt-2 text-xs text-gray-500">JPG, PNG, GIF or WebP. Maximum size 5 MB.</p>
                </div>

                <?php if (!empty($ucsGalleryImages)): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Current Gallery Images</label>
                        <p class="mt-1 text-xs text-gray-500">Check the boxes to remove images you no longer want.</p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <?php foreach ($ucsGalleryImages as $ucsImg): ?>
                                <div class="relative inline-block">
                                    <span class="inline-flex h-24 w-24 items-center justify-center overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-200" aria-hidden="true">
                                        <img src="<?php echo htmlspecialchars(ROOT_URL . '/assets/' . ltrim($ucsImg['image_path'], '/')); ?>" alt="" class="h-full w-full object-cover">
                                    </span>
                                    <label class="absolute right-1 top-1 inline-flex cursor-pointer items-center justify-center h-6 w-6 rounded-full bg-red-600 text-white shadow-sm transition-colors hover:bg-red-700" title="Remove this image">
                                        <input type="checkbox" name="remove_gallery_ids[]" value="<?php echo (int) $ucsImg['id']; ?>" class="sr-only">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="gallery_images" class="block text-sm font-medium text-gray-700">Add More Gallery Images <span class="text-gray-400">(optional)</span></label>
                    <input type="file" id="gallery_images" name="gallery_images[]" accept=".jpg,.jpeg,.png,.gif,.webp" multiple
                           class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 transition-colors hover:file:bg-blue-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <p class="mt-2 text-xs text-gray-500">Select up to 10 additional images. JPG, PNG, GIF or WebP. Maximum 5 MB each.</p>
                    <div id="gallery-preview" class="mt-3 flex flex-wrap gap-3"></div>
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
                                                <option value="<?php echo (int) $ucsCl['id']; ?>"><?php echo htmlspecialchars($ucsCl['classroom_name'] . ' — ' . $ucsCl['major_name']); ?></option>
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
                                                    <option value="<?php echo (int) $ucsCl['id']; ?>" <?php echo (int) ($ucsTarget['classroom_id'] ?? 0) === (int) $ucsCl['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsCl['class_name']); ?> (<?php echo htmlspecialchars($ucsCl['year_level']); ?> - <?php echo htmlspecialchars($ucsCl['section']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <select name="target_major_id[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                                <option value="">Any major</option>
                                                <?php foreach ($ucsMajors as $ucsMajor): ?>
                                                    <option value="<?php echo (int) $ucsMajor['id']; ?>" <?php echo (int) ($ucsTarget['major_id'] ?? 0) === (int) $ucsMajor['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ucsMajor['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <select name="target_year_level[]" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                                <option value="">Any level</option>
                                                <?php foreach ($ucsYearLevels as $ucsLevel): ?>
                                                    <option value="<?php echo $ucsLevel; ?>" <?php echo (string) ($ucsTarget['year_level'] ?? '') === $ucsLevel ? 'selected' : ''; ?>><?php echo $ucsLevel; ?></option>
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
                    Save Changes
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
            html += '<option value="' + cl.id + '"' + (String(selected) === String(cl.id) ? ' selected' : '') + '>' + cl.classroom_name + ' \u2014 ' + cl.major_name + '</option>';
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

    var galleryInput = document.getElementById('gallery_images');
    var galleryPreview = document.getElementById('gallery-preview');
    if (galleryInput && galleryPreview) {
        galleryInput.addEventListener('change', function () {
            galleryPreview.innerHTML = '';
            var files = galleryInput.files;
            for (var i = 0; i < files.length; i++) {
                (function (file) {
                    if (!file.type.startsWith('image/')) return;
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        var span = document.createElement('span');
                        span.className = 'relative inline-block h-20 w-20 overflow-hidden rounded-lg ring-1 ring-gray-200';
                        span.innerHTML = '<img src="' + e.target.result + '" alt="" class="h-full w-full object-cover">';
                        galleryPreview.appendChild(span);
                    };
                    reader.readAsDataURL(file);
                })(files[i]);
            }
        });
    }
})();
</script>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>