<?php
/**
 * Admin Admissions module - create form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/admission-validation.php';
require_once __DIR__ . '/../../includes/helpers/ucs-admin-lists.php';

admin_require_login();

$pageTitle    = 'Add Admission';
$pageSubtitle = 'Create a new admission announcement.';
$activeNav    = 'admission';

$ucsErrors = $_SESSION['admission_errors'] ?? [];
unset($_SESSION['admission_errors']);

$ucsOld = $_SESSION['admission_old'] ?? null;
unset($_SESSION['admission_old']);

$ucsAcademicYears = ucs_admin_academic_years($pdo);

$ucsActiveYear    = ucs_admin_active_academic_year($pdo);
$ucsDefaultYearId = $ucsActiveYear !== null ? (int) $ucsActiveYear['id'] : null;

$ucsForm = [
    'academic_year_id'  => $ucsOld['academic_year_id'] ?? $ucsDefaultYearId,
    'title'             => $ucsOld['title'] ?? '',
    'description'       => $ucsOld['description'] ?? '',
    'requirements'      => $ucsOld['requirements'] ?? '',
    'important_dates'   => $ucsOld['important_dates'] ?? '',
    'application_info'  => $ucsOld['application_info'] ?? '',
    'document_title'    => $ucsOld['document_title'] ?? '',
    'status'            => $ucsOld['status'] ?? 1,
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
            <h2 class="text-base font-semibold text-gray-900">New Admission</h2>
            <p class="mt-1 text-sm text-gray-500">Share admission information for an academic year.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/admission-create.php'); ?>" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">

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
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ucsForm['title']); ?>" placeholder="e.g. College Admission for AY 2025-2026" required maxlength="255"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-gray-400">(optional)</span></label>
                    <textarea id="description" name="description" rows="4" placeholder="Brief overview of the admission…"
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['description']); ?></textarea>
                </div>

                <div>
                    <label for="requirements" class="block text-sm font-medium text-gray-700">Requirements <span class="text-gray-400">(optional)</span></label>
                    <textarea id="requirements" name="requirements" rows="4" placeholder="List of admission requirements…"
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['requirements']); ?></textarea>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="important_dates" class="block text-sm font-medium text-gray-700">Important Dates <span class="text-gray-400">(optional)</span></label>
                        <textarea id="important_dates" name="important_dates" rows="4" placeholder="Application deadlines, exams…"
                                  class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['important_dates']); ?></textarea>
                    </div>
                    <div>
                        <label for="application_info" class="block text-sm font-medium text-gray-700">Application Info <span class="text-gray-400">(optional)</span></label>
                        <textarea id="application_info" name="application_info" rows="4" placeholder="How to apply…"
                                  class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['application_info']); ?></textarea>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="document_title" class="block text-sm font-medium text-gray-700">Document Title <span class="text-gray-400">(optional)</span></label>
                        <input type="text" id="document_title" name="document_title" value="<?php echo htmlspecialchars($ucsForm['document_title']); ?>" placeholder="e.g. Admission Guidelines" maxlength="255"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="document" class="block text-sm font-medium text-gray-700">Document File <span class="text-gray-400">(optional)</span></label>
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
                    Create Admission
                </button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>