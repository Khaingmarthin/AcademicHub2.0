<?php
/**
 * Homepage Faculties & Departments section.
 *
 * Displays the academic faculties stored in the faculties table as a clean
 * two-column card grid. Each card shows the faculty name and a short
 * description.
 *
 * The section is relationship-aware: if the departments table is linked to
 * faculties through a faculty_id column, each card also shows the number of
 * departments (calculated from the actual relationship) and a "View
 * Departments" action. When no relationship exists, that information is
 * omitted rather than invented, and all departments remain accessible through
 * the "View All Departments" link to the Departments page.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

// Reuse the existing database connection if the page already connected.
if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}

$ucsFaculties = [];
$ucsDepartmentCounts = [];
$ucsHasDepartmentRelation = false;

if (isset($pdo)) {
    try {
        // Detect whether departments are linked to faculties in the schema.
        $ucsRelationCol = $pdo->query(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'departments'
               AND COLUMN_NAME = 'faculty_id'"
        )->fetchColumn();
        $ucsHasDepartmentRelation = (int) $ucsRelationCol > 0;

        $ucsStmt = $pdo->query(
            "SELECT id, name, description
             FROM faculties
             WHERE status = 1
             ORDER BY id ASC"
        );
        $ucsFaculties = $ucsStmt->fetchAll() ?: [];

        if ($ucsHasDepartmentRelation) {
            $ucsDeptStmt = $pdo->query(
                "SELECT faculty_id, COUNT(*) AS total
                 FROM departments
                 WHERE status = 1
                 GROUP BY faculty_id"
            );
            foreach ($ucsDeptStmt->fetchAll() as $ucsRow) {
                $ucsDepartmentCounts[(int) $ucsRow['faculty_id']] = (int) $ucsRow['total'];
            }
        }
    } catch (PDOException $e) {
        $ucsFaculties = [];
        $ucsHasDepartmentRelation = false;
    }
}
?>
<section class="bg-white py-16 sm:py-20" aria-labelledby="faculties-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Academic Structure</p>
            <h2 id="faculties-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Faculties &amp; Departments</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">
                Discover the academic faculties and departments that support teaching, learning, and academic development at UCSMTLA.
            </p>
        </div>

        <?php if (count($ucsFaculties) > 0): ?>
            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:gap-8">
                <?php foreach ($ucsFaculties as $ucsFaculty): ?>
                    <article class="group flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 sm:p-8">
                        <h3 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">
                            <?php echo htmlspecialchars($ucsFaculty['name']); ?>
                        </h3>
                        <?php if (!empty($ucsFaculty['description'])): ?>
                            <p class="mt-3 flex-1 text-sm leading-6 text-gray-600 line-clamp-3">
                                <?php echo htmlspecialchars($ucsFaculty['description']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($ucsHasDepartmentRelation): ?>
                            <?php $ucsDeptTotal = $ucsDepartmentCounts[$ucsFaculty['id']] ?? 0; ?>
                            <p class="mt-5 text-sm font-semibold text-gray-500">
                                <?php echo $ucsDeptTotal . ' ' . ($ucsDeptTotal === 1 ? 'Department' : 'Departments'); ?>
                            </p>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/faculties.php?faculty=' . (int) $ucsFaculty['id'] . '#departments'); ?>" class="mt-3 inline-flex items-center gap-1.5 self-start text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                View Departments
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php#faculties-heading'); ?>" class="mt-5 inline-flex items-center gap-1.5 self-start text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                View Faculty
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="mt-10 text-center">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/departments.php'); ?>" class="group inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    View All Departments
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        <?php else: ?>
            <p class="mt-12 text-center text-sm text-gray-500">Faculty information is being updated. Please check back soon.</p>
        <?php endif; ?>
    </div>
</section>