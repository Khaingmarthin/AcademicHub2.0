<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

// Student authentication state drives the header login button / student menu.
require_once __DIR__ . '/student-auth.php';
require_once __DIR__ . '/../config/database.php';
$ucsStudentUser = student_current_user();

// The public header always shows the "Student Login" button. The student
// account menu (name, avatar, dashboard links) is rendered only on the
// authenticated student pages, never on public pages — a live student
// session alone must not expose the student's information publicly. The
// session itself is left untouched so authenticated pages keep working.
$ucsStudentAreaPage = in_array(
    basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')),
    [
        'student-dashboard.php', 'student-profile.php', 'student-timetable.php',
        'alumni-edit.php', 'alumni-join.php', 'alumni-dashboard.php',
        'career-discussion-create.php',
        'career-discussions.php', 'career-discussion-details.php',
        'alumni.php', 'alumni-overview.php', 'alumni-details.php',
        'alumni-stories.php', 'alumni-story-details.php',
    ],
    true
);
$ucsShowStudentMenu = ($ucsStudentUser !== null) && $ucsStudentAreaPage;

// Verified alumni get an extra "Alumni Dashboard" entry in the student menu.
$ucsVerifiedAlumnus = false;
$ucsIsGraduated = false;
if ($ucsShowStudentMenu && isset($pdo)) {
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id FROM alumni_profiles
             WHERE student_id = :student_id AND verification_status = 'verified'
             LIMIT 1"
        );
        $ucsStmt->execute([':student_id' => $ucsStudentUser['id']]);
        $ucsVerifiedAlumnus = $ucsStmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        $ucsVerifiedAlumnus = false;
    }
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT student_status FROM students
             WHERE id = :id
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $ucsStudentUser['id']]);
        $ucsIsGraduated = $ucsStmt->fetchColumn() === 'graduated';
    } catch (PDOException $e) {
        $ucsIsGraduated = false;
    }
}

// Avatar initial without relying on the mbstring extension.
function ucs_avatar_initial($name)
{
    $name = trim((string) $name);
    if ($name === '') {
        return 'S';
    }
    return strtoupper(substr($name, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " - " . APP_NAME : APP_NAME; ?></title>
    <meta name="description" content="UCSMTLA Academic Hub - University information, academic programmes, admissions, news, and campus life.">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(ROOT_URL); ?>/assets/css/style.css">
</head>
<body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 antialiased">
    <header id="site-header" class="sticky top-0 z-50 border-b border-blue-100/80 bg-white/95 backdrop-blur-sm transition-all duration-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="header-inner flex h-[4.25rem] items-center justify-between gap-5">
                <!-- University logo -->
                <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="flex shrink-0 items-center gap-3 transition-opacity hover:opacity-80" aria-label="UCSMTLA Academic Hub - Home">
                    <img src="<?php echo htmlspecialchars(ROOT_URL . '/assets/images/logo.png'); ?>" alt="UCSMTLA Academic Hub logo" class="h-10 w-10 object-contain">
                    <span class="hidden leading-none sm:block">
                        <span class="block text-[1.05rem] font-bold tracking-[-0.01em] text-slate-800">UCSMTLA</span>
                        <span class="mt-px block text-[0.62rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Academic Hub</span>
                    </span>
                </a>

                <?php include __DIR__ . '/navbar.php'; ?>

                <!-- Right actions -->
                <div class="flex items-center gap-2">
                    <button type="button" id="search-toggle" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-300" aria-label="Search the website" aria-expanded="false" aria-controls="search-panel">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>

                    <?php if (!$ucsShowStudentMenu): ?>
                        <!-- Student Login (all screens) -->
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/student-login.php'); ?>" class="inline-flex h-9 items-center gap-2 rounded-lg bg-blue-600 px-4 text-[0.8125rem] font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Login
                        </a>
                    <?php else: ?>
                        <!-- Student menu (desktop) -->
                        <div class="group relative hidden lg:block">
                            <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-lg px-3 text-[0.8125rem] font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-100 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-300" data-dropdown-toggle aria-expanded="false" aria-haspopup="true">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-[0.6875rem] font-bold text-white" aria-hidden="true">
                                    <?php echo htmlspecialchars(ucs_avatar_initial($ucsStudentUser['name'])); ?>
                                </span>
                                <span class="max-w-[9rem] truncate"><?php echo htmlspecialchars($ucsStudentUser['name']); ?></span>
                                <svg class="nav-chevron h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div class="nav-dropdown nav-dropdown-right" role="menu" aria-label="Student account menu">
                                <?php if (!$ucsIsGraduated): ?>
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="nav-dropdown-link" role="menuitem">Dashboard</a>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/student-profile.php'); ?>" class="nav-dropdown-link" role="menuitem">My Profile</a>
                                <?php if (!$ucsIsGraduated): ?>
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/student-timetable.php'); ?>" class="nav-dropdown-link" role="menuitem">My Timetable</a>
                                <?php endif; ?>
                                <?php if ($ucsVerifiedAlumnus): ?>
                                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="nav-dropdown-link" role="menuitem">Alumni Dashboard</a>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars(ROOT_URL . '/actions/student/logout.php'); ?>" class="nav-dropdown-link nav-dropdown-link-danger" role="menuitem">Logout</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="button" id="mobile-menu-toggle" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700 lg:hidden focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-300" aria-label="Open main menu" aria-expanded="false" aria-controls="mobile-menu">
                        <svg id="menu-icon-open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M3 6h18M3 12h18M3 18h18"></path>
                        </svg>
                        <svg id="menu-icon-close" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Search panel -->
            <div id="search-panel" class="search-panel border-t border-blue-100/80" role="search">
                <div>
                    <form action="<?php echo htmlspecialchars(BASE_URL . '/search.php'); ?>" method="get" class="px-0 pb-3 pt-3">
                        <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 transition-all duration-150 focus-within:border-sky-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-sky-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input id="site-search-input" type="search" name="q" placeholder="Search programmes, admissions, news..." autocomplete="off" class="w-full bg-transparent py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none">
                            <button type="button" id="search-close" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-200 hover:text-slate-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-300" aria-label="Close search">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6 6 18M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden border-t border-blue-100/80 bg-white lg:hidden">
            <nav aria-label="Mobile navigation" class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <p class="px-2 pb-1.5 text-[0.6875rem] font-semibold uppercase tracking-[0.18em] text-slate-400">Menu</p>
                <ul class="space-y-0.5">
                    <?php foreach ($navItems as $item): ?>
                        <?php if (!empty($item['children'])): ?>
                            <?php
                            $isActive = false;
                            foreach ($item['children'] as $child) {
                                if (ucs_nav_is_active($child['url'], $relativePath)) {
                                    $isActive = true;
                                    break;
                                }
                            }
                            ?>
                            <li>
                                <button type="button" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-[0.9375rem] font-medium text-slate-700 transition-colors hover:bg-slate-100" data-mobile-submenu-toggle aria-expanded="false" aria-controls="mobile-submenu-<?php echo htmlspecialchars($item['label']); ?>">
                                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                                    <svg data-chevron xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div class="mobile-submenu" id="mobile-submenu-<?php echo htmlspecialchars($item['label']); ?>">
                                    <div>
                                        <ul class="mt-0.5 space-y-0.5 border-l border-slate-200 pl-4">
                                            <?php foreach ($item['children'] as $child): ?>
                                                <li>
                                                    <a href="<?php echo htmlspecialchars($child['url']); ?>" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900" data-nav-sections="<?php echo htmlspecialchars(ucs_nav_section($child['url'])); ?>"><?php echo htmlspecialchars($child['label']); ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        <?php else: ?>
                            <?php $isActive = ucs_nav_is_active($item['url'], $relativePath); ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($item['url']); ?>" class="block rounded-lg px-3 py-2.5 text-[0.9375rem] font-medium <?php echo $isActive ? 'bg-sky-50 text-sky-700' : 'text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900'; ?>" data-nav-sections="<?php echo htmlspecialchars(ucs_nav_sections($item['url'])); ?>"><?php echo htmlspecialchars($item['label']); ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (!$ucsShowStudentMenu): ?>
                        <!-- Student Login (mobile) -->
                        <li class="pt-3">
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/student-login.php'); ?>" class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-[0.9375rem] font-semibold text-white transition-colors hover:bg-blue-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                Login
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Student account (mobile) -->
                        <li class="mt-2 border-t border-slate-200 pt-3">
                            <p class="px-3 pb-1 pt-1 text-[0.6875rem] font-semibold uppercase tracking-wide text-slate-400">Student Account</p>
                            <?php if (!$ucsIsGraduated): ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900">Dashboard</a>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/student-profile.php'); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900">My Profile</a>
                            <?php if (!$ucsIsGraduated): ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/student-timetable.php'); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900">My Timetable</a>
                            <?php endif; ?>
                            <?php if ($ucsVerifiedAlumnus): ?>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900">Alumni Dashboard</a>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars(ROOT_URL . '/actions/student/logout.php'); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-50">Logout</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <script>
        (function () {
            'use strict';

            var header = document.getElementById('site-header');

            // --- Desktop dropdowns (hover handled by CSS; click works for touch) ---
            function closeAllDropdowns(except) {
                document.querySelectorAll('.group.open').forEach(function (group) {
                    if (group === except) return;
                    group.classList.remove('open');
                    var btn = group.querySelector('[data-dropdown-toggle]');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                });
            }

            document.querySelectorAll('[data-dropdown-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var group = btn.closest('.group');
                    var isOpen = group.classList.toggle('open');
                    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    closeAllDropdowns(group);
                });
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function (e) {
                document.querySelectorAll('.group.open').forEach(function (group) {
                    if (!group.contains(e.target)) {
                        group.classList.remove('open');
                        var btn = group.querySelector('[data-dropdown-toggle]');
                        if (btn) btn.setAttribute('aria-expanded', 'false');
                    }
                });
            });

            // --- Mobile menu ---
            var mobileToggle = document.getElementById('mobile-menu-toggle');
            var mobileMenu = document.getElementById('mobile-menu');
            var iconOpen = document.getElementById('menu-icon-open');
            var iconClose = document.getElementById('menu-icon-close');

            function closeMobileMenu() {
                if (!mobileMenu) return;
                mobileMenu.classList.add('hidden');
                if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'false');
                if (iconOpen) iconOpen.classList.remove('hidden');
                if (iconClose) iconClose.classList.add('hidden');
            }

            if (mobileToggle && mobileMenu) {
                mobileToggle.addEventListener('click', function () {
                    var isHidden = mobileMenu.classList.toggle('hidden');
                    mobileToggle.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
                    if (iconOpen) iconOpen.classList.toggle('hidden', !isHidden);
                    if (iconClose) iconClose.classList.toggle('hidden', isHidden);
                });
            }

            // --- Mobile submenus (click / tap) ---
            document.querySelectorAll('[data-mobile-submenu-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var sub = btn.nextElementSibling;
                    var isOpen = sub.classList.toggle('open');
                    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    var chevron = btn.querySelector('[data-chevron]');
                    if (chevron) chevron.classList.toggle('rotate-180', isOpen);
                });
            });

            // --- Search panel ---
            var searchToggle = document.getElementById('search-toggle');
            var searchPanel = document.getElementById('search-panel');
            var searchInput = document.getElementById('site-search-input');
            var searchClose = document.getElementById('search-close');

            function closeSearch() {
                if (!searchPanel) return;
                searchPanel.classList.remove('open');
                if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false');
            }

            if (searchToggle && searchPanel) {
                searchToggle.addEventListener('click', function () {
                    var isOpen = searchPanel.classList.toggle('open');
                    searchToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    if (isOpen && searchInput) {
                        setTimeout(function () { searchInput.focus(); }, 200);
                    }
                });
            }
            if (searchClose) {
                searchClose.addEventListener('click', closeSearch);
            }

            // --- Close menus on Escape ---
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                closeAllDropdowns(null);
                closeMobileMenu();
                closeSearch();
            });

            // --- Sticky header subtle shadow + compact height on scroll ---
            if (header) {
                function onScroll() {
                    if (window.scrollY > 8) {
                        header.classList.add('site-header-scrolled');
                    } else {
                        header.classList.remove('site-header-scrolled');
                    }
                }
                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();
            }

            // --- Close the mobile menu when a link inside it is selected ---
            if (mobileMenu) {
                mobileMenu.querySelectorAll('a').forEach(function (link) {
                    link.addEventListener('click', function () {
                        closeMobileMenu();
                    });
                });
            }

            // --- Close an open desktop dropdown when one of its links is selected ---
            document.querySelectorAll('.nav-dropdown-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    var group = link.closest('.group');
                    if (group) {
                        group.classList.remove('open');
                        var btn = group.querySelector('[data-dropdown-toggle]');
                        if (btn) btn.setAttribute('aria-expanded', 'false');
                    }
                });
            });

            // --- Highlight the nav item matching the current homepage section ---
            function setNavActiveState(el, active) {
                var childLike = el.classList.contains('nav-dropdown-link') || !!el.closest('#mobile-menu');
                el.classList.toggle('is-active', active && !childLike);
                if (childLike) {
                    el.classList.toggle('bg-slate-100', active);
                }
            }

            function syncNavActiveState() {
                var hash = window.location.hash.replace(/^#/, '');
                var pageFile = (window.location.pathname.split('/').pop() || '').split('?')[0];
                var isHomePage = pageFile === '' || pageFile === 'index.php' || pageFile === 'index';
                document.querySelectorAll('[data-nav-sections]').forEach(function (el) {
                    var sections = (el.getAttribute('data-nav-sections') || '').split(/\s+/).filter(Boolean);
                    if (hash === '') {
                        // No section selected: only manage the Home sentinel and leave
                        // any server-rendered page-level active state untouched.
                        if (sections.indexOf('home') !== -1) {
                            setNavActiveState(el, isHomePage);
                        }
                        return;
                    }
                    setNavActiveState(el, sections.indexOf(hash) !== -1);
                });
            }

            window.addEventListener('hashchange', syncNavActiveState);
            syncNavActiveState();
        })();
    </script>