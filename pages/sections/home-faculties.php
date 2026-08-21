<?php
/**
 * Homepage Faculties & Departments section.
 *
 * Surfaces the unified Faculties & Departments module on the homepage using
 * the exact same shared component as the Navbar → Faculties page
 * (includes/helpers/ucs-faculties-directory.php), so the design and content are
 * consistent everywhere. The section header is followed directly by the
 * shared tabbed directory:
 *
 *   Faculties & Departments
 *       ↓
 *   [ Faculties ] [ Departments ]
 *       ↓
 *   Selected content/cards
 *
 * All data is read from the database by the shared component.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

// Reuse the existing database connection if the page already connected.
if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../../config/database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}
?>
<section class="bg-blue-50/60 py-16 sm:py-20" aria-labelledby="faculties-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Academic Structure</p>
            <h2 id="faculties-heading" class="mt-3 scroll-mt-24 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Faculties &amp; Departments</h2>
            <p class="mt-4 text-base leading-7 text-gray-600">
                Discover the academic faculties and departments that support teaching, learning, and academic development at UCSMTLA.
            </p>
        </div>

        <?php include __DIR__ . '/../../includes/helpers/ucs-faculties-directory.php'; ?>
    </div>
</section>