<?php
/**
 * Public Departments page.
 *
 * Displays all active department records from the departments table,
 * reusing the existing database connection and the shared header/footer.
 */
require_once '../config/app.php';
require_once '../includes/database.php';

$pageTitle = 'Departments';

$ucsDepartments = [];

try {
    $ucsStmt = $pdo->query(
        "SELECT name, description
         FROM departments
         WHERE status = 1
         ORDER BY id ASC"
    );
    $ucsDepartments = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsDepartments = [];
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="departments-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Faculties &amp; Departments</p>
                <h1 id="departments-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Departments</h1>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    The departments that support teaching, learning, and academic development at UCSMTLA.
                </p>
            </div>

            <?php if (count($ucsDepartments) > 0): ?>
                <div class="mx-auto mt-12 grid max-w-5xl grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                    <?php foreach ($ucsDepartments as $ucsDepartment): ?>
                        <article class="group flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5">
                            <h2 class="text-lg font-bold tracking-tight text-gray-900">
                                <?php echo htmlspecialchars($ucsDepartment['name']); ?>
                            </h2>
                            <?php if (!empty($ucsDepartment['description'])): ?>
                                <p class="mt-3 flex-1 text-sm leading-6 text-gray-600">
                                    <?php echo htmlspecialchars($ucsDepartment['description']); ?>
                                </p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mt-12 text-center text-sm text-gray-500">Department information is being updated. Please check back soon.</p>
            <?php endif; ?>

            <div class="mt-10 text-center">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php#faculties-heading'); ?>" class="group inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                    View Faculties
                </a>
            </div>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>