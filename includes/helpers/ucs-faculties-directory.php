<?php
/**
 * Shared Faculties, Departments & Administrative Units directory.
 *
 * The single source of truth for the Academic Structure tabbed module.
 * It is reused by the homepage Faculties section (pages/sections/
 * home-faculties.php) and the Navbar → Faculties module (pages/faculties.php)
 * so there is exactly one consistent design across the website.
 *
 * Layout: modern card grid with three tabs.
 *
 *   [ Faculties ] [ Departments ] [ Administrative Units ]
 *   ──────────────────────────────────────────────────────
 *   Card grid with icons, names, and descriptions
 *
 * The caller renders its own heading; this component renders the tab bar
 * and the card panels. All data comes from the database.
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

// Load administrative units if the caller has not already done so.
if (!isset($ucsAdminUnits)) {
    $ucsAdminUnits = [];
    if (isset($pdo)) {
        try {
            $ucsStmt = $pdo->query(
                "SELECT id, name, description
                 FROM administrative_units
                 WHERE status = 1
                 ORDER BY id ASC"
            );
            $ucsAdminUnits = $ucsStmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $ucsAdminUnits = [];
        }
    }
}

$ucsDirectoryHasFaculties   = count($ucsFaculties) > 0;
$ucsDirectoryHasDepartments = count($ucsDepartments) > 0;
$ucsDirectoryHasAdminUnits  = count($ucsAdminUnits) > 0;

// Icon map for administrative units by keyword.
$ucsAdminUnitIcons = [
    'library'    => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path><path d="M8 7h6"></path><path d="M8 11h8"></path>',
    'finance'    => '<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>',
    'admin'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
    'student'    => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5"></path>',
    'default'    => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><path d="M3 9h18"></path><path d="M9 21V9"></path>',
];

function ucs_admin_unit_icon($name, $iconMap)
{
    $lower = strtolower($name);
    foreach ($iconMap as $key => $path) {
        if ($key !== 'default' && strpos($lower, $key) !== false) {
            return $path;
        }
    }
    return $iconMap['default'];
}
?>
<div class="mt-8 sm:mt-10">
    <!-- Tabs — modern pill style -->
    <div class="border-b border-slate-200" role="tablist" aria-label="Academic structure">
        <div class="flex gap-2 -mb-px overflow-x-auto pb-px">
            <button type="button" role="tab" id="ucs-tab-faculties" aria-controls="ucs-panel-faculties" aria-selected="true" data-ucs-tab="faculties" class="ucs-tab">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5"></path>
                </svg>
                Faculties
                <?php if ($ucsDirectoryHasFaculties): ?>
                    <span class="ucs-tab-count ml-1.5 rounded-full px-2 py-0.5 text-xs font-bold"><?php echo count($ucsFaculties); ?></span>
                <?php endif; ?>
            </button>
            <button type="button" role="tab" id="ucs-tab-departments" aria-controls="ucs-panel-departments" aria-selected="false" data-ucs-tab="departments" class="ucs-tab">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                    <path d="M3 9h18"></path>
                    <path d="M9 21V9"></path>
                </svg>
                Departments
                <?php if ($ucsDirectoryHasDepartments): ?>
                    <span class="ucs-tab-count ml-1.5 rounded-full px-2 py-0.5 text-xs font-bold"><?php echo count($ucsDepartments); ?></span>
                <?php endif; ?>
            </button>
            <button type="button" role="tab" id="ucs-tab-admin-units" aria-controls="ucs-panel-admin-units" aria-selected="false" data-ucs-tab="admin-units" class="ucs-tab">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Administrative Units
                <?php if ($ucsDirectoryHasAdminUnits): ?>
                    <span class="ucs-tab-count ml-1.5 rounded-full px-2 py-0.5 text-xs font-bold"><?php echo count($ucsAdminUnits); ?></span>
                <?php endif; ?>
            </button>
        </div>
    </div>

    <!-- Content panels -->
    <div class="mt-8">
        <!-- Faculties panel -->
        <div id="ucs-panel-faculties" role="tabpanel" aria-labelledby="ucs-tab-faculties" data-ucs-panel="faculties">
            <?php if ($ucsDirectoryHasFaculties): ?>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($ucsFaculties as $ucsDirectoryFaculty): ?>
                        <?php
                        $ucsDirectoryFacultyId    = (int) $ucsDirectoryFaculty['id'];
                        $ucsDirectoryFacultyName  = $ucsDirectoryFaculty['name'] ?? '';
                        $ucsDirectoryFacultyDesc  = $ucsDirectoryFaculty['description'] ?? '';
                        $ucsDirectoryFacultyBadge = ucs_name_badge($ucsDirectoryFacultyName);
                        $ucsDirectoryFacultyPrev  = ucs_short_summary($ucsDirectoryFacultyDesc, 120);
                        ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/faculty-details.php?id=' . $ucsDirectoryFacultyId); ?>" class="group relative flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:border-blue-200 hover:shadow-md hover:-translate-y-0.5 sm:p-6">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-sm font-bold tracking-wide text-blue-700 ring-1 ring-blue-100 transition-colors duration-200 group-hover:bg-blue-600 group-hover:text-white group-hover:ring-0" aria-hidden="true">
                                    <?php echo htmlspecialchars($ucsDirectoryFacultyBadge); ?>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-semibold leading-tight tracking-tight text-slate-900 transition-colors group-hover:text-blue-700 sm:text-base">
                                        <?php echo htmlspecialchars($ucsDirectoryFacultyName); ?>
                                    </h3>
                                    <span class="text-xs font-medium text-slate-400">Academic Faculty</span>
                                </div>
                            </div>
                            <?php if ($ucsDirectoryFacultyPrev !== ''): ?>
                                <p class="flex-1 text-sm leading-relaxed text-slate-500">
                                    <?php echo htmlspecialchars($ucsDirectoryFacultyPrev); ?>
                                </p>
                            <?php endif; ?>
                            <div class="mt-4 flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition-colors group-hover:text-blue-700">
                                View Details
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5"></path>
                    </svg>
                    <p class="mt-3 text-sm text-slate-500">No faculty information is currently available.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Departments panel -->
        <div id="ucs-panel-departments" role="tabpanel" aria-labelledby="ucs-tab-departments" data-ucs-panel="departments" class="hidden">
            <?php if ($ucsDirectoryHasDepartments): ?>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($ucsDepartments as $ucsDirectoryDepartment): ?>
                        <?php
                        $ucsDirectoryDepartmentId    = (int) $ucsDirectoryDepartment['id'];
                        $ucsDirectoryDepartmentName  = $ucsDirectoryDepartment['name'] ?? '';
                        $ucsDirectoryDepartmentDesc  = $ucsDirectoryDepartment['description'] ?? '';
                        $ucsDirectoryDepartmentBadge = ucs_name_badge($ucsDirectoryDepartmentName);
                        $ucsDirectoryDepartmentPrev  = ucs_short_summary($ucsDirectoryDepartmentDesc, 120);
                        ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/department-details.php?id=' . $ucsDirectoryDepartmentId); ?>" class="group relative flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:border-emerald-200 hover:shadow-md hover:-translate-y-0.5 sm:p-6">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-sm font-bold tracking-wide text-emerald-700 ring-1 ring-emerald-100 transition-colors duration-200 group-hover:bg-emerald-600 group-hover:text-white group-hover:ring-0" aria-hidden="true">
                                    <?php echo htmlspecialchars($ucsDirectoryDepartmentBadge); ?>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-semibold leading-tight tracking-tight text-slate-900 transition-colors group-hover:text-emerald-700 sm:text-base">
                                        <?php echo htmlspecialchars($ucsDirectoryDepartmentName); ?>
                                    </h3>
                                    <span class="text-xs font-medium text-slate-400">Academic Department</span>
                                </div>
                            </div>
                            <?php if ($ucsDirectoryDepartmentPrev !== ''): ?>
                                <p class="flex-1 text-sm leading-relaxed text-slate-500">
                                    <?php echo htmlspecialchars($ucsDirectoryDepartmentPrev); ?>
                                </p>
                            <?php endif; ?>
                            <div class="mt-4 flex items-center gap-1.5 text-sm font-semibold text-emerald-600 transition-colors group-hover:text-emerald-700">
                                View Details
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                        <path d="M3 9h18"></path>
                        <path d="M9 21V9"></path>
                    </svg>
                    <p class="mt-3 text-sm text-slate-500">No department information is currently available.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Administrative Units panel -->
        <div id="ucs-panel-admin-units" role="tabpanel" aria-labelledby="ucs-tab-admin-units" data-ucs-panel="admin-units" class="hidden">
            <?php if ($ucsDirectoryHasAdminUnits): ?>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($ucsAdminUnits as $ucsDirectoryAdminUnit): ?>
                        <?php
                        $ucsDirectoryAdminUnitId   = (int) $ucsDirectoryAdminUnit['id'];
                        $ucsDirectoryAdminUnitName = $ucsDirectoryAdminUnit['name'] ?? '';
                        $ucsDirectoryAdminUnitDesc = $ucsDirectoryAdminUnit['description'] ?? '';
                        $ucsDirectoryAdminUnitPrev = ucs_short_summary($ucsDirectoryAdminUnitDesc, 120);
                        $ucsAdminUnitSvg = ucs_admin_unit_icon($ucsDirectoryAdminUnitName, $ucsAdminUnitIcons);
                        ?>
                        <div class="group relative flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:border-violet-200 hover:shadow-md hover:-translate-y-0.5 sm:p-6">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600 ring-1 ring-violet-100 transition-colors duration-200 group-hover:bg-violet-600 group-hover:text-white group-hover:ring-0" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <?php echo $ucsAdminUnitSvg; ?>
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-semibold leading-tight tracking-tight text-slate-900 transition-colors group-hover:text-violet-700 sm:text-base">
                                        <?php echo htmlspecialchars($ucsDirectoryAdminUnitName); ?>
                                    </h3>
                                    <span class="text-xs font-medium text-slate-400">Administrative Unit</span>
                                </div>
                            </div>
                            <?php if ($ucsDirectoryAdminUnitPrev !== ''): ?>
                                <p class="flex-1 text-sm leading-relaxed text-slate-500">
                                    <?php echo htmlspecialchars($ucsDirectoryAdminUnitPrev); ?>
                                </p>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/administrative-unit-details.php?id=' . $ucsDirectoryAdminUnitId); ?>" class="mt-4 flex items-center gap-1.5 text-sm font-semibold text-violet-600 transition-colors group-hover:text-violet-700">
                                View Details
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <p class="mt-3 text-sm text-slate-500">No administrative unit information is currently available.</p>
                </div>
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

    // Open the tab referenced by the URL hash (e.g. #departments, #admin-units).
    var hash = window.location.hash.replace('#', '');
    var validTabs = ['faculties', 'departments', 'admin-units'];
    if (validTabs.indexOf(hash) !== -1) {
        activate(hash);
    }
})();
</script>
