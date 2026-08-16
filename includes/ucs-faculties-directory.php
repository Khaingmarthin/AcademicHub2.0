<?php
/**
 * Shared Faculties & Departments directory.
 *
 * The single source of truth for the Faculties & Departments tabbed module.
 * It is reused by the homepage Faculties section (pages/sections/
 * home-faculties.php) and the Navbar → Faculties module (pages/faculties.php)
 * so there is exactly one consistent design across the website.
 *
 * Layout: tabs at the top, then the selected content below.
 *
 *   [ Faculties ] [ Departments ]
 *   -------------------------------
 *   Selected content/cards
 *
 * The caller renders its own heading (hero on the module page, section header
 * on the homepage); this component renders the tab bar and the card panels.
 * All data comes from the database — nothing is hard-coded. Departments are
 * kept independent from faculties.
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

// Reuse the existing database connection if the caller already connected.
if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}

require_once __DIR__ . '/ucs-listing-helpers.php';

// Load faculties if the caller has not already done so.
if (!isset($ucsFaculties)) {
    $ucsFaculties = [];
    if (isset($pdo)) {
        try {
            $ucsStmt = $pdo->query(
                "SELECT id, name, description
                 FROM faculties
                 WHERE status = 1
                 ORDER BY id ASC"
            );
            $ucsFaculties = $ucsStmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $ucsFaculties = [];
        }
    }
}

// Load departments if the caller has not already done so.
if (!isset($ucsDepartments)) {
    $ucsDepartments = [];
    if (isset($pdo)) {
        try {
            $ucsStmt = $pdo->query(
                "SELECT id, name, description
                 FROM departments
                 WHERE status = 1
                 ORDER BY id ASC"
            );
            $ucsDepartments = $ucsStmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $ucsDepartments = [];
        }
    }
}

$ucsDirectoryHasFaculties   = count($ucsFaculties) > 0;
$ucsDirectoryHasDepartments = count($ucsDepartments) > 0;
?>
<div class="mt-10 sm:mt-12">
    <!-- Tabs -->
    <div class="flex justify-center">
        <div class="inline-flex flex-wrap items-center justify-center gap-2 rounded-2xl bg-white p-1.5 shadow-sm ring-1 ring-gray-100" role="tablist" aria-label="Faculties and departments">
            <button type="button" role="tab" id="ucs-tab-faculties" aria-controls="ucs-panel-faculties" aria-selected="true" data-ucs-tab="faculties" class="ucs-tab flex-1 lg:flex-none">
                Faculties
                <?php if ($ucsDirectoryHasFaculties): ?>
                    <span class="ucs-tab-count rounded-full px-2 py-0.5 text-xs font-bold"><?php echo count($ucsFaculties); ?></span>
                <?php endif; ?>
            </button>
            <button type="button" role="tab" id="ucs-tab-departments" aria-controls="ucs-panel-departments" aria-selected="false" data-ucs-tab="departments" class="ucs-tab flex-1 lg:flex-none">
                Departments
                <?php if ($ucsDirectoryHasDepartments): ?>
                    <span class="ucs-tab-count rounded-full px-2 py-0.5 text-xs font-bold"><?php echo count($ucsDepartments); ?></span>
                <?php endif; ?>
            </button>
        </div>
    </div>

    <!-- Content panels -->
    <div class="mt-10 sm:mt-12">
        <!-- Faculties panel -->
        <div id="ucs-panel-faculties" role="tabpanel" aria-labelledby="ucs-tab-faculties" data-ucs-panel="faculties">
            <?php if ($ucsDirectoryHasFaculties): ?>
                <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                    <?php foreach ($ucsFaculties as $ucsDirectoryFaculty): ?>
                        <?php
                        $ucsDirectoryFacultyId    = (int) $ucsDirectoryFaculty['id'];
                        $ucsDirectoryFacultyName  = $ucsDirectoryFaculty['name'] ?? '';
                        $ucsDirectoryFacultyDesc  = $ucsDirectoryFaculty['description'] ?? '';
                        $ucsDirectoryFacultyBadge = ucs_name_badge($ucsDirectoryFacultyName);
                        $ucsDirectoryFacultyPrev  = ucs_short_summary($ucsDirectoryFacultyDesc);
                        ?>
                        <article class="group flex min-w-0 flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 sm:p-8">
                            <div class="flex items-center gap-4">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold tracking-wide text-white" aria-hidden="true">
                                    <?php echo htmlspecialchars($ucsDirectoryFacultyBadge); ?>
                                </span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Academic Faculty</span>
                            </div>

                            <h3 class="mt-5 text-xl font-bold tracking-tight text-gray-900">
                                <?php echo htmlspecialchars($ucsDirectoryFacultyName); ?>
                            </h3>

                            <?php if ($ucsDirectoryFacultyPrev !== ''): ?>
                                <p class="mt-3 flex-1 break-words text-sm leading-6 text-gray-600">
                                    <?php echo htmlspecialchars($ucsDirectoryFacultyPrev); ?>
                                </p>
                            <?php endif; ?>

                            <a href="<?php echo htmlspecialchars(BASE_URL . '/faculty-details.php?id=' . $ucsDirectoryFacultyId); ?>" class="mt-6 inline-flex items-center gap-2 self-start text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                View Details
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mt-4 text-center text-sm text-gray-500">No faculty information is currently available.</p>
            <?php endif; ?>
        </div>

        <!-- Departments panel -->
        <div id="ucs-panel-departments" role="tabpanel" aria-labelledby="ucs-tab-departments" data-ucs-panel="departments" class="hidden">
            <?php if ($ucsDirectoryHasDepartments): ?>
                <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                    <?php foreach ($ucsDepartments as $ucsDirectoryDepartment): ?>
                        <?php
                        $ucsDirectoryDepartmentId    = (int) $ucsDirectoryDepartment['id'];
                        $ucsDirectoryDepartmentName  = $ucsDirectoryDepartment['name'] ?? '';
                        $ucsDirectoryDepartmentDesc  = $ucsDirectoryDepartment['description'] ?? '';
                        $ucsDirectoryDepartmentBadge = ucs_name_badge($ucsDirectoryDepartmentName);
                        $ucsDirectoryDepartmentPrev  = ucs_short_summary($ucsDirectoryDepartmentDesc);
                        ?>
                        <article class="group flex min-w-0 flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/5 sm:p-8">
                            <div class="flex items-center gap-4">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-sm font-bold tracking-wide text-blue-700 ring-1 ring-blue-100" aria-hidden="true">
                                    <?php echo htmlspecialchars($ucsDirectoryDepartmentBadge); ?>
                                </span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">University Department</span>
                            </div>

                            <h3 class="mt-5 text-xl font-bold tracking-tight text-gray-900">
                                <?php echo htmlspecialchars($ucsDirectoryDepartmentName); ?>
                            </h3>

                            <?php if ($ucsDirectoryDepartmentPrev !== ''): ?>
                                <p class="mt-3 flex-1 break-words text-sm leading-6 text-gray-600">
                                    <?php echo htmlspecialchars($ucsDirectoryDepartmentPrev); ?>
                                </p>
                            <?php endif; ?>

                            <a href="<?php echo htmlspecialchars(BASE_URL . '/department-details.php?id=' . $ucsDirectoryDepartmentId); ?>" class="mt-6 inline-flex items-center gap-2 self-start text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                View Details
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mt-4 text-center text-sm text-gray-500">No department information is currently available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var tabs   = document.querySelectorAll('[data-ucs-tab]');
    var panels = document.querySelectorAll('[data-ucs-panel]');

    function activate(name) {
        tabs.forEach(function (tab) {
            tab.setAttribute('aria-selected', tab.getAttribute('data-ucs-tab') === name ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-ucs-panel') !== name);
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var name = tab.getAttribute('data-ucs-tab');
            activate(name);
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '#' + name);
            }
        });
    });

    // Open the tab referenced by the URL hash (e.g. #departments).
    var hash = window.location.hash.replace('#', '');
    if (hash === 'faculties' || hash === 'departments') {
        activate(hash);
    }
})();
</script>