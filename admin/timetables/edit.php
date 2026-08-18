<?php
/**
 * Admin Timetables module - edit form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/timetable-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Edit Timetable';
$pageSubtitle = 'Update a timetable record.';
$activeNav    = 'timetables';

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, classroom_id, semester, title, image, status
         FROM timetables
         WHERE id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsTimetable = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsTimetable = null;
}

if ($ucsTimetable === null || $ucsId === false || $ucsId < 1) {
    $_SESSION['timetable_flash'] = ['type' => 'error', 'message' => 'Timetable not found.'];
    header('Location: ' . ROOT_URL . '/admin/timetables/index.php');
    exit;
}

$ucsErrors = $_SESSION['timetable_errors'] ?? [];
unset($_SESSION['timetable_errors']);

$ucsOld = $_SESSION['timetable_old'] ?? null;
unset($_SESSION['timetable_old']);

$ucsForm = [
    'classroom_id' => $ucsOld['classroom_id'] ?? (int) $ucsTimetable['classroom_id'],
    'semester'     => $ucsOld['semester'] ?? $ucsTimetable['semester'],
    'title'        => $ucsOld['title'] ?? $ucsTimetable['title'],
    'status'       => $ucsOld['status'] ?? (int) $ucsTimetable['status'],
];

$ucsCurrentImage = (string) $ucsTimetable['image'];
$ucsImagePath    = ROOT_URL . '/assets/' . ltrim($ucsCurrentImage, '/');

$ucsClassrooms = ucs_admin_classrooms($pdo);

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

<div class="mx-auto max-w-2xl">
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-6 py-5">
            <h2 class="text-base font-semibold text-gray-900">Edit Timetable</h2>
            <p class="mt-1 text-sm text-gray-500">Leave the image field empty to keep the current image.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/timetable-update.php'); ?>" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsTimetable['id']; ?>">

            <div class="space-y-6 px-6 py-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="classroom_id" class="block text-sm font-medium text-gray-700">Classroom <span class="text-red-500">*</span></label>
                        <select id="classroom_id" name="classroom_id" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Select a classroom…</option>
                            <?php
                            $ucsYearLabel = null;
                            foreach ($ucsClassrooms as $ucsClassroom):
                                $ucsGroupLabel = $ucsClassroom['academic_year'] . ($ucsClassroom['academic_year_status'] === 'Active' ? ' (Active)' : '');
                                if ($ucsGroupLabel !== $ucsYearLabel):
                                    if ($ucsYearLabel !== null): ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <optgroup label="<?php echo htmlspecialchars($ucsGroupLabel); ?>">
                                    <?php $ucsYearLabel = $ucsGroupLabel;
                                endif; ?>
                                <option value="<?php echo (int) $ucsClassroom['id']; ?>" <?php echo (int) $ucsForm['classroom_id'] === (int) $ucsClassroom['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ucsClassroom['classroom_name'] . ' — ' . $ucsClassroom['major_name']); ?>
                                </option>
                            <?php endforeach;
                            if ($ucsYearLabel !== null): ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label for="semester" class="block text-sm font-medium text-gray-700">Semester <span class="text-red-500">*</span></label>
                        <select id="semester" name="semester" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <?php foreach (TIMETABLE_SEMESTERS as $ucsSemester): ?>
                                <option value="<?php echo $ucsSemester; ?>" <?php echo $ucsForm['semester'] === $ucsSemester ? 'selected' : ''; ?>><?php echo $ucsSemester; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ucsForm['title']); ?>" required maxlength="255"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Current Image</label>
                    <div class="mt-2 flex items-center gap-4">
                        <span class="inline-flex h-20 w-32 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-200" aria-hidden="true">
                            <img src="<?php echo htmlspecialchars($ucsImagePath); ?>" alt="" class="h-full w-full object-cover">
                        </span>
                    </div>
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">Replace Image <span class="text-gray-400">(optional)</span></label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp"
                           class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 transition-colors hover:file:bg-blue-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <p class="mt-2 text-xs text-gray-500">JPG, PNG, GIF or WebP. Maximum size 2 MB.</p>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="1" <?php echo (int) $ucsForm['status'] === 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo (int) $ucsForm['status'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/timetables/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
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
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>