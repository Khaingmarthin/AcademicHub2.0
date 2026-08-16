<?php
/**
 * Public Department Details page.
 *
 * Shows the full description of a single department record selected by id.
 * The record is read from the departments table only — no values are hard-coded.
 */
require_once '../config/app.php';
require_once '../includes/database.php';
require_once '../includes/ucs-listing-helpers.php';

$pageTitle = 'Department Details';

$ucsDepartmentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ucsDepartment   = null;
$ucsHeroMedia    = 'images/front_view.jpg';

if ($ucsDepartmentId > 0) {
    try {
        $ucsProfileStmt = $pdo->query(
            "SELECT hero_media
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
        $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';

        $ucsStmt = $pdo->prepare(
            "SELECT id, name, description, status
             FROM departments
             WHERE id = :id AND status = 1
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $ucsDepartmentId]);
        $ucsDepartment = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsDepartment = null;
    }
}

if ($ucsDepartment !== null) {
    $ucsDepartmentName  = $ucsDepartment['name'] ?? '';
    $ucsDepartmentDesc  = $ucsDepartment['description'] ?? '';
    $ucsDepartmentBadge = ucs_name_badge($ucsDepartmentName);
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = BASE_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <?php if ($ucsDepartment !== null): ?>
        <!-- Page hero -->
        <section class="relative overflow-hidden bg-gray-900" aria-labelledby="department-details-heading">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
            <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">University Department</p>
                <h1 id="department-details-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    <?php echo htmlspecialchars($ucsDepartmentName); ?>
                </h1>
            </div>
        </section>

        <!-- Full description -->
        <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="department-description-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-4xl overflow-hidden rounded-3xl bg-white shadow-lg shadow-gray-900/5 ring-1 ring-gray-100">
                    <div class="flex flex-col gap-6 p-8 sm:flex-row sm:items-start sm:p-10">
                        <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-lg font-bold tracking-wide text-blue-700 ring-1 ring-blue-100" aria-hidden="true">
                            <?php echo htmlspecialchars($ucsDepartmentBadge); ?>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">About this Department</p>
                            <h2 id="department-description-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900">Overview</h2>
                            <?php if ($ucsDepartmentDesc !== ''): ?>
                                <p class="mt-4 break-words text-base leading-7 text-gray-600">
                                    <?php echo nl2br(htmlspecialchars($ucsDepartmentDesc)); ?>
                                </p>
                            <?php else: ?>
                                <p class="mt-4 text-base leading-7 text-gray-600">Department information will be available soon.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-8 text-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/departments.php'); ?>" class="group inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"></path>
                        </svg>
                        Back to Departments
                    </a>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Not found -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="department-not-found-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">University Department</p>
                <h1 id="department-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Department Not Found</h1>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    The requested department could not be found or is no longer available.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/departments.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/25 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Browse Departments
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>