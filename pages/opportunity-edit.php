<?php
/**
 * Alumni self-management - edit a career opportunity (protected).
 *
 * Only the authenticated, verified alumnus who posted the opportunity may
 * edit it. Ownership is enforced server-side.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/alumni-validation.php';
require_once __DIR__ . '/../includes/helpers/opportunity-validation.php';

student_require_login();

$ucsProfile = alumni_current_profile($pdo);
if ($ucsProfile === null) {
    header('Location: ' . BASE_URL . '/alumni.php');
    exit;
}

$ucsOpportunityId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($ucsOpportunityId === false) {
    $ucsOpportunityId = null;
}

$ucsStudentId = (int) $ucsProfile['student_id'];
$ucsOpportunity = $ucsOpportunityId !== null
    ? opportunity_get($pdo, $ucsOpportunityId)
    : null;

if ($ucsOpportunity === null || (int) $ucsOpportunity['posted_by_student_id'] !== $ucsStudentId) {
    header('Location: ' . BASE_URL . '/my-opportunities.php');
    exit;
}

$pageTitle    = 'Edit Career Opportunity';
$pageSubtitle = 'Update the details of your posting.';

$ucsErrors = $_SESSION['opportunity_errors'] ?? [];
unset($_SESSION['opportunity_errors']);

$ucsOld = $_SESSION['opportunity_old'] ?? null;
unset($_SESSION['opportunity_old']);

$ucsForm = [
    'title'           => (string) ($ucsOld['title'] ?? $ucsOpportunity['title']),
    'company'         => (string) ($ucsOld['company'] ?? $ucsOpportunity['company']),
    'location'        => (string) ($ucsOld['location'] ?? $ucsOpportunity['location']),
    'employment_type' => (string) ($ucsOld['employment_type'] ?? $ucsOpportunity['employment_type']),
    'salary_range'    => (string) ($ucsOld['salary_range'] ?? $ucsOpportunity['salary_range']),
    'description'     => (string) ($ucsOld['description'] ?? $ucsOpportunity['description']),
    'how_to_apply'    => (string) ($ucsOld['how_to_apply'] ?? $ucsOpportunity['how_to_apply']),
    'expires_at'      => (string) ($ucsOld['expires_at'] ?? $ucsOpportunity['expires_at']),
];

require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-1 bg-slate-50">
    <section class="py-12 sm:py-16" aria-labelledby="opportunity-edit-heading">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
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

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Career Community</p>
                    <h1 id="opportunity-edit-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">Edit Career Opportunity</h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600">
                        Update the details of your posting. Your changes go live immediately.
                    </p>
                </div>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/my-opportunities.php'); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                    Back to My Opportunities
                </a>
            </div>

            <div class="mt-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/opportunity/update.php'); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $ucsOpportunity['id']; ?>">

                    <div class="space-y-6 px-6 py-6 sm:px-8">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Job Title <span class="text-red-500">*</span></label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ucsForm['title']); ?>" required maxlength="255"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="company" class="block text-sm font-medium text-gray-700">Company / Employer <span class="text-red-500">*</span></label>
                                <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($ucsForm['company']); ?>" required maxlength="255"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="employment_type" class="block text-sm font-medium text-gray-700">Employment Type <span class="text-red-500">*</span></label>
                                <select id="employment_type" name="employment_type" required
                                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <?php foreach (OPPORTUNITY_EMPLOYMENT_TYPES as $ucsType): ?>
                                        <option value="<?php echo $ucsType; ?>" <?php echo $ucsForm['employment_type'] === $ucsType ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(opportunity_employment_label($ucsType)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700">Location <span class="text-gray-400">(optional)</span></label>
                                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($ucsForm['location']); ?>" maxlength="191"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="salary_range" class="block text-sm font-medium text-gray-700">Salary Range <span class="text-gray-400">(optional)</span></label>
                                <input type="text" id="salary_range" name="salary_range" value="<?php echo htmlspecialchars($ucsForm['salary_range']); ?>" maxlength="191"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="expires_at" class="block text-sm font-medium text-gray-700">Application Deadline <span class="text-gray-400">(optional)</span></label>
                                <input type="date" id="expires_at" name="expires_at" value="<?php echo htmlspecialchars($ucsForm['expires_at']); ?>"
                                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                            <textarea id="description" name="description" rows="6" required
                                      class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['description']); ?></textarea>
                        </div>

                        <div>
                            <label for="how_to_apply" class="block text-sm font-medium text-gray-700">How to Apply <span class="text-gray-400">(optional)</span></label>
                            <textarea id="how_to_apply" name="how_to_apply" rows="3"
                                      class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['how_to_apply']); ?></textarea>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/my-opportunities.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
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
    </section>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>