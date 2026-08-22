<?php
/**
 * Shared Faculties & Departments directory.
 *
 * The single source of truth for the Faculties & Departments tabbed module.
 * It is reused by the homepage Faculties section (pages/sections/
 * home-faculties.php) and the Navbar → Faculties module (pages/faculties.php)
 * so there is exactly one consistent design across the website.
 *
 * Layout: underline tabs at the top, then structured list below.
 *
 *   [ Faculties ] [ Departments ]
 *   ─────────────────────────────
 *   Editorial list items with borders
 *
 * The caller renders its own heading (hero on the module page, section header
 * on the homepage); this component renders the tab bar and the list panels.
 * All data comes from the database — nothing is hard-coded. Departments are
 * kept independent from faculties.
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

// Reuse the existing database connection if the caller already connected.
if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../../config/database.php';
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
<div class="mt-8 sm:mt-10">
    <!-- Tabs — underline style -->
    <div class="border-b border-slate-200" role="tablist" aria-label="Faculties and departments">
        <div class="flex gap-0 -mb-px">
            <button type="button" role="tab" id="ucs-tab-faculties" aria-controls="ucs-panel-faculties" aria-selected="true" data-ucs-tab="faculties" class="ucs-tab">
                Faculties
                <?php if ($ucsDirectoryHasFaculties): ?>
                    <span class="ucs-tab-count ml-1.5 rounded-full px-2 py-0.5 text-xs font-bold"><?php echo count($ucsFaculties); ?></span>
                <?php endif; ?>
            </button>
            <button type="button" role="tab" id="ucs-tab-departments" aria-controls="ucs-panel-departments" aria-selected="false" data-ucs-tab="departments" class="ucs-tab">
                Departments
                <?php if ($ucsDirectoryHasDepartments): ?>
                    <span class="ucs-tab-count ml-1.5 rounded-full px-2 py-0.5 text-xs font-bold"><?php echo count($ucsDepartments); ?></span>
                <?php endif; ?>
            </button>
        </div>
    </div>

    <!-- Content panels -->
    <div class="mt-8">
        <!-- Faculties panel -->
        <div id="ucs-panel-faculties" role="tabpanel" aria-labelledby="ucs-tab-faculties" data-ucs-panel="faculties">
            <?php if ($ucsDirectoryHasFaculties): ?>
                <div class="border border-slate-200 bg-white">
                    <?php foreach ($ucsFaculties as $ucsIdx => $ucsDirectoryFaculty): ?>
                        <?php
                        $ucsDirectoryFacultyId    = (int) $ucsDirectoryFaculty['id'];
                        $ucsDirectoryFacultyName  = $ucsDirectoryFaculty['name'] ?? '';
                        $ucsDirectoryFacultyDesc  = $ucsDirectoryFaculty['description'] ?? '';
                        $ucsDirectoryFacultyBadge = ucs_name_badge($ucsDirectoryFacultyName);
                        $ucsDirectoryFacultyPrev  = ucs_short_summary($ucsDirectoryFacultyDesc);
                        $ucsIsFirst = $ucsIdx === 0;
                        ?>
                        <article class="group flex items-start gap-5 px-6 py-5 sm:px-8 sm:py-6 <?php echo !$ucsIsFirst ? 'border-t border-slate-200' : ''; ?> transition-colors duration-150 hover:bg-slate-50/60">
                            <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-blue-50 text-xs font-bold tracking-wide text-blue-700 ring-1 ring-blue-100" aria-hidden="true">
                                <?php echo htmlspecialchars($ucsDirectoryFacultyBadge); ?>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <h3 class="text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
                                        <?php echo htmlspecialchars($ucsDirectoryFacultyName); ?>
                                    </h3>
                                    <span class="text-xs font-medium text-slate-400">Academic Faculty</span>
                                </div>
                                <?php if ($ucsDirectoryFacultyPrev !== ''): ?>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                                        <?php echo htmlspecialchars($ucsDirectoryFacultyPrev); ?>
                                    </p>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/faculty-details.php?id=' . $ucsDirectoryFacultyId); ?>" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    View Details
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="py-8 text-center text-sm text-slate-500">No faculty information is currently available.</p>
            <?php endif; ?>
        </div>

        <!-- Departments panel -->
        <div id="ucs-panel-departments" role="tabpanel" aria-labelledby="ucs-tab-departments" data-ucs-panel="departments" class="hidden">
            <?php if ($ucsDirectoryHasDepartments): ?>
                <div class="border border-slate-200 bg-white">
                    <?php foreach ($ucsDepartments as $ucsIdx => $ucsDirectoryDepartment): ?>
                        <?php
                        $ucsDirectoryDepartmentId    = (int) $ucsDirectoryDepartment['id'];
                        $ucsDirectoryDepartmentName  = $ucsDirectoryDepartment['name'] ?? '';
                        $ucsDirectoryDepartmentDesc  = $ucsDirectoryDepartment['description'] ?? '';
                        $ucsDirectoryDepartmentBadge = ucs_name_badge($ucsDirectoryDepartmentName);
                        $ucsDirectoryDepartmentPrev  = ucs_short_summary($ucsDirectoryDepartmentDesc);
                        $ucsIsFirst = $ucsIdx === 0;
                        ?>
                        <article class="group flex items-start gap-5 px-6 py-5 sm:px-8 sm:py-6 <?php echo !$ucsIsFirst ? 'border-t border-slate-200' : ''; ?> transition-colors duration-150 hover:bg-slate-50/60">
                            <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-slate-100 text-xs font-bold tracking-wide text-slate-600" aria-hidden="true">
                                <?php echo htmlspecialchars($ucsDirectoryDepartmentBadge); ?>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <h3 class="text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
                                        <?php echo htmlspecialchars($ucsDirectoryDepartmentName); ?>
                                    </h3>
                                    <span class="text-xs font-medium text-slate-400">University Department</span>
                                </div>
                                <?php if ($ucsDirectoryDepartmentPrev !== ''): ?>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                                        <?php echo htmlspecialchars($ucsDirectoryDepartmentPrev); ?>
                                    </p>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/department-details.php?id=' . $ucsDirectoryDepartmentId); ?>" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    View Details
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="py-8 text-center text-sm text-slate-500">No department information is currently available.</p>
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
