<?php
/**
 * Admin Academic Years module - edit form.
 *
 * Loads an existing academic year and pre-fills the form. If a previous edit
 * attempt failed validation, the submitted values are carried back instead.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$pageTitle    = 'Edit Academic Year';
$pageSubtitle = 'Update an academic year record.';
$activeNav    = 'academic-years';

$ucsYearId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, year_name, start_date, end_date, status
         FROM academic_years
         WHERE id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsYearId]);
    $ucsYear = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsYear = null;
}

if ($ucsYear === null || $ucsYearId === false || $ucsYearId < 1) {
    $_SESSION['academic_year_flash'] = [
        'type'    => 'error',
        'message' => 'Academic year not found.',
    ];
    header('Location: ' . ROOT_URL . '/admin/academic-years/index.php');
    exit;
}

$ucsErrors = $_SESSION['academic_year_errors'] ?? [];
unset($_SESSION['academic_year_errors']);

$ucsOld = $_SESSION['academic_year_old'] ?? null;
unset($_SESSION['academic_year_old']);

$ucsForm = [
    'year_name'  => $ucsOld['year_name'] ?? $ucsYear['year_name'],
    'start_date' => $ucsOld['start_date'] ?? ($ucsYear['start_date'] ?? ''),
    'end_date'   => $ucsOld['end_date'] ?? ($ucsYear['end_date'] ?? ''),
    'status'     => $ucsOld['status'] ?? $ucsYear['status'],
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
            <h2 class="text-base font-semibold text-gray-900">Edit Academic Year</h2>
            <p class="mt-1 text-sm text-gray-500">
                Use the label format <span class="font-medium text-gray-700">YYYY-YYYY</span> (for example 2027-2028). Dates are optional.
            </p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/academic-year-update.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsYear['id']; ?>">

            <div class="space-y-6 px-6 py-6">
                <div>
                    <label for="year_name" class="block text-sm font-medium text-gray-700">Academic Year Label <span class="text-red-500">*</span></label>
                    <input type="text" id="year_name" name="year_name" value="<?php echo htmlspecialchars($ucsForm['year_name']); ?>" placeholder="e.g. 2027-2028" required maxlength="20"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <p class="mt-1.5 text-xs text-gray-500">Standard label such as 2025-2026.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($ucsForm['start_date']); ?>"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($ucsForm['end_date']); ?>"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <?php foreach (['Preparation', 'Active', 'Archived'] as $ucsOption): ?>
                            <option value="<?php echo $ucsOption; ?>" <?php echo $ucsForm['status'] === $ucsOption ? 'selected' : ''; ?>>
                                <?php echo $ucsOption; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1.5 text-xs text-gray-500">Setting a year to <span class="font-medium text-gray-700">Active</span> archives the currently active academic year automatically.</p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/academic-years/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13.34"></path>
                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                        <path d="m15 5 4 4"></path>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
