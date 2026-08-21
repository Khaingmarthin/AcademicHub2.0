<?php
/**
 * Homepage Faculties & Departments section.
 *
 * Section wrapper for the shared faculties directory component.
 * Clean editorial header with institutional styling.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../../config/database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}
?>
<section class="border-t border-gray-200 bg-white py-20 sm:py-24" aria-labelledby="faculties-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <div class="flex items-center justify-center gap-3">
                <span class="h-px w-8 bg-blue-600" aria-hidden="true"></span>
                <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-blue-600">Academic Structure</span>
                <span class="h-px w-8 bg-blue-600" aria-hidden="true"></span>
            </div>
            <h2 id="faculties-heading" class="mt-4 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-gray-900 sm:text-4xl">Faculties &amp; Departments</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">
                Discover the academic faculties and departments that support teaching, learning, and academic development at UCSMTLA.
            </p>
        </div>

        <?php include __DIR__ . '/../../includes/helpers/ucs-faculties-directory.php'; ?>
    </div>
</section>
