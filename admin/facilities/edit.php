<?php
/**
 * Admin Facilities module - edit form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_once __DIR__ . '/../../includes/helpers/ucs-upload.php';

admin_require_login();

$pageTitle    = 'Edit Facility';
$pageSubtitle = 'Update a campus facility record.';
$activeNav    = 'facilities';

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, name, image, description, location, status
         FROM facilities
         WHERE id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsFacility = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsFacility = null;
}

if ($ucsFacility === null || $ucsId === false || $ucsId < 1) {
    $_SESSION['facility_flash'] = ['type' => 'error', 'message' => 'Facility not found.'];
    header('Location: ' . ROOT_URL . '/admin/facilities/index.php');
    exit;
}

$ucsErrors = $_SESSION['facility_errors'] ?? [];
unset($_SESSION['facility_errors']);

$ucsOld = $_SESSION['facility_old'] ?? null;
unset($_SESSION['facility_old']);

$ucsForm = [
    'name'        => $ucsOld['name'] ?? $ucsFacility['name'],
    'image'       => $ucsOld['image'] ?? ($ucsFacility['image'] ?? ''),
    'description' => $ucsOld['description'] ?? ($ucsFacility['description'] ?? ''),
    'location'    => $ucsOld['location'] ?? ($ucsFacility['location'] ?? ''),
    'status'      => $ucsOld['status'] ?? (int) $ucsFacility['status'],
];

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
            <h2 class="text-base font-semibold text-gray-900">Edit Facility</h2>
            <p class="mt-1 text-sm text-gray-500">The image, description and location are optional.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/facility-update.php'); ?>" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsFacility['id']; ?>">

            <div class="space-y-6 px-6 py-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Facility Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($ucsForm['name']); ?>" placeholder="e.g. Computer Laboratory" required maxlength="255"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <?php if (!empty($ucsForm['image'])): ?>
                            <label class="block text-sm font-medium text-gray-700">Current Image</label>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <span class="inline-flex h-20 w-32 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-200" aria-hidden="true">
                                    <img src="<?php echo htmlspecialchars(ROOT_URL . '/assets/' . ltrim($ucsForm['image'], '/')); ?>" alt="" class="h-full w-full object-cover">
                                </span>
                                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-red-600">
                                    <input type="checkbox" name="remove_image" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-100">
                                    Remove this image
                                </label>
                            </div>
                        <?php endif; ?>
                        <label for="image" class="mt-3 block text-sm font-medium text-gray-700"><?php echo !empty($ucsForm['image']) ? 'Replace Image' : 'Facility Image'; ?> <span class="text-gray-400">(optional)</span></label>
                        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp"
                               class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 transition-colors hover:file:bg-blue-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <p class="mt-2 text-xs text-gray-500">JPG, PNG, GIF or WebP. Maximum size 5 MB. Leave empty to keep the current image.</p>
                    </div>
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($ucsForm['location']); ?>" placeholder="e.g. Main Campus" maxlength="255"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="4"
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['description']); ?></textarea>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="1" <?php echo (int) $ucsForm['status'] === 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo (int) $ucsForm['status'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <p class="mt-1.5 text-xs text-gray-500">Inactive facilities are hidden from the public facilities page.</p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/facilities/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
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