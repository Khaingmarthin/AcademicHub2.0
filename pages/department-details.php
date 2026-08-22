<?php
/**
 * Public Department Details page.
 *
 * Shows the full description of a single department record selected by id.
 * The record is read from the departments table only — no values are hard-coded.
 * Editorial layout with clear information hierarchy.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/helpers/ucs-listing-helpers.php';

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
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <?php if ($ucsDepartment !== null): ?>
        <!-- Page header -->
        <section class="border-b border-slate-200 bg-white" aria-labelledby="department-details-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
                <div class="max-w-3xl">
                    <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                        <ol class="flex items-center gap-1.5">
                            <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                            <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                            <li><a href="<?php echo htmlspecialchars(BASE_URL . '/faculties.php#departments'); ?>" class="transition-colors hover:text-slate-600">Departments</a></li>
                            <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                            <li class="text-slate-600"><?php echo htmlspecialchars($ucsDepartmentName); ?></li>
                        </ol>
                    </nav>
                    <div class="flex items-start gap-4">
                        <span class="mt-1 inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-bold tracking-wide text-slate-600" aria-hidden="true">
                            <?php echo htmlspecialchars($ucsDepartmentBadge); ?>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">University Department</p>
                            <h1 id="department-details-heading" class="mt-2 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.5rem] leading-[1.1]">
                                <?php echo htmlspecialchars($ucsDepartmentName); ?>
                            </h1>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Department content -->
        <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="department-description-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl">
                    <div class="border border-slate-200 bg-white">
                        <div class="border-b border-slate-200 px-6 py-4 sm:px-8">
                            <h2 id="department-description-heading" class="text-lg font-semibold tracking-tight text-slate-900">About this Department</h2>
                        </div>
                        <div class="px-6 py-6 sm:px-8 sm:py-8">
                            <?php if ($ucsDepartmentDesc !== ''): ?>
                                <div class="prose prose-slate max-w-none text-sm leading-relaxed text-slate-600">
                                    <?php echo nl2br(htmlspecialchars($ucsDepartmentDesc)); ?>
                                </div>
                            <?php else: ?>
                                <p class="text-sm leading-relaxed text-slate-500">Department information will be available soon.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center gap-6">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/faculties.php#departments'); ?>" class="group inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 12H5M12 19l-7-7 7-7"></path>
                            </svg>
                            Back to Departments
                        </a>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="group inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 transition-colors duration-150 hover:text-slate-800 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            Degree Programmes
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Not found -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="department-not-found-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">University Department</p>
                <h1 id="department-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Department Not Found</h1>
                <p class="mt-4 text-base leading-relaxed text-slate-600">
                    The requested department could not be found or is no longer available.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/faculties.php#departments'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-lg border border-blue-600 bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-all duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Browse Departments
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once '../includes/footer.php';
?>
