<?php
/**
 * Admin Admissions module - edit form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/admission-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Edit Admission';
$pageSubtitle = 'Update an admission announcement.';
$activeNav    = 'admission';

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, academic_year_id, title, description, requirements, important_dates,
                application_info, document_title, document_path, document_type, status
         FROM admissions
         WHERE id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsAdmission = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsAdmission = null;
}

if ($ucsAdmission === null || $ucsId === false || $ucsId < 1) {
    $_SESSION['admission_flash'] = ['type' => 'error', 'message' => 'Admission not found.'];
    header('Location: ' . ROOT_URL . '/admin/admission/index.php');
    exit;
}

$ucsErrors = $_SESSION['admission_errors'] ?? [];
unset($_SESSION['admission_errors']);

$ucsOld = $_SESSION['admission_old'] ?? null;
unset($_SESSION['admission_old']);

$ucsForm = [
    'academic_year_id'  => $ucsOld['academic_year_id'] ?? (int) $ucsAdmission['academic_year_id'],
    'title'             => $ucsOld['title'] ?? $ucsAdmission['title'],
    'description'       => $ucsOld['description'] ?? (string) $ucsAdmission['description'],
    'requirements'      => $ucsOld['requirements'] ?? (string) $ucsAdmission['requirements'],
    'important_dates'   => $ucsOld['important_dates'] ?? (string) $ucsAdmission['important_dates'],
    'application_info'  => $ucsOld['application_info'] ?? (string) $ucsAdmission['application_info'],
    'document_title'    => $ucsOld['document_title'] ?? (string) $ucsAdmission['document_title'],
    'status'            => $ucsOld['status'] ?? (int) $ucsAdmission['status'],
];

$ucsAcademicYears = ucs_admin_academic_years($pdo);

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
            <h2 class="text-base font-semibold text-gray-900">Edit Admission</h2>
            <p class="mt-1 text-sm text-gray-500">Update the admission details.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/admission-update.php'); ?>" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsAdmission['id']; ?>">

            <div class="space-y-6 px-6 py-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="academic_year_id" class="block text-sm font-medium text-gray-700">Academic Year <span class="text-red-500">*</span></label>
                        <select id="academic_year_id" name="academic_year_id" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <?php foreach ($ucsAcademicYears as $ucsYear): ?>
                                <option value="<?php echo (int) $ucsYear['id']; ?>" <?php echo (int) $ucsForm['academic_year_id'] === (int) $ucsYear['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ucsYear['year_name']); ?><?php echo $ucsYear['status'] === 'Active' ? ' (Active)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ucsForm['title']); ?>" required maxlength="255"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-gray-400">(optional)</span></label>
                    <textarea id="description" name="description" rows="4"
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['description']); ?></textarea>
                </div>

                <div>
                    <label for="requirements" class="block text-sm font-medium text-gray-700">Requirements <span class="text-gray-400">(optional)</span></label>
                    <textarea id="requirements" name="requirements" rows="4"
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['requirements']); ?></textarea>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="important_dates" class="block text-sm font-medium text-gray-700">Important Dates <span class="text-gray-400">(optional)</span></label>
                        <textarea id="important_dates" name="important_dates" rows="4"
                                  class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['important_dates']); ?></textarea>
                    </div>
                    <div>
                        <label for="application_info" class="block text-sm font-medium text-gray-700">Application Info <span class="text-gray-400">(optional)</span></label>
                        <textarea id="application_info" name="application_info" rows="4"
                                  class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['application_info']); ?></textarea>
                    </div>
                </div>

                <?php if (!empty($ucsAdmission['document_path'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Current Document</label>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-sm text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <path d="M7 10l5 5 5-5"></path>
                                    <path d="M12 15V3"></path>
                                </svg>
                                <?php echo htmlspecialchars((string) $ucsAdmission['document_title']); ?>
                                <span class="rounded bg-white px-1.5 py-0.5 text-[10px] font-semibold text-gray-400"><?php echo htmlspecialchars((string) $ucsAdmission['document_type']); ?></span>
                            </span>
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-red-600">
                                <input type="checkbox" name="remove_document" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-100">
                                Remove this document
                            </label>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="document_title" class="block text-sm font-medium text-gray-700">Document Title <span class="text-gray-400">(optional)</span></label>
                        <input type="text" id="document_title" name="document_title" value="<?php echo htmlspecialchars($ucsForm['document_title']); ?>" maxlength="255"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="document" class="block text-sm font-medium text-gray-700">Replace Document <span class="text-gray-400">(optional)</span></label>
                        <input type="file" id="document" name="document" accept=".pdf,.doc,.docx"
                               class="mt-2 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 transition-colors hover:file:bg-blue-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <p class="mt-2 text-xs text-gray-500">PDF, DOC or DOCX. Maximum size 5 MB.</p>
                    </div>
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
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/admission/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
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