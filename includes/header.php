<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

// Student authentication state drives the header login button / student menu.
require_once __DIR__ . '/student-auth.php';
$ucsStudentUser = student_current_user();

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
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL); ?>/assets/css/style.css">
</head>
<body class="flex min-h-screen flex-col bg-gray-50 font-sans text-gray-900 antialiased">
    <header id="site-header" class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 backdrop-blur-sm transition-shadow duration-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">
                <!-- University logo -->
                <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="flex shrink-0 items-center gap-3" aria-label="UCSMTLA Academic Hub - Home">
                    <img src="<?php echo htmlspecialchars(BASE_URL . '/assets/images/logo.png'); ?>" alt="UCSMTLA Academic Hub logo" class="h-11 w-11 object-contain">
                    <span class="hidden leading-tight sm:block">
                        <span class="block text-lg font-bold tracking-tight text-gray-900">UCSMTLA</span>
                        <span class="block text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-700">Academic Hub</span>
                    </span>
                </a>

                <?php include __DIR__ . '/navbar.php'; ?>

                <!-- Right actions -->
                <div class="flex items-center gap-1.5">
                    <button type="button" id="search-toggle" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-600 transition-colors duration-150 hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Search the website" aria-expanded="false" aria-controls="search-panel">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>

                    <?php if ($ucsStudentUser === null): ?>
                        <!-- Student Login (all screens) -->
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/student-login.php'); ?>" class="inline-flex items-center gap-2 rounded-lg border border-blue-600 bg-white px-3 py-2 text-sm font-semibold text-blue-700 transition-colors duration-150 hover:bg-blue-600 hover:text-white focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Student Login
                        </a>
                    <?php else: ?>
                        <!-- Student menu (desktop) -->
                        <div class="group relative hidden lg:block">
                            <button type="button" class="inline-flex h-10 items-center gap-1.5 rounded-lg px-3 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" data-dropdown-toggle aria-expanded="false" aria-haspopup="true">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white" aria-hidden="true">
                                    <?php echo htmlspecialchars(ucs_avatar_initial($ucsStudentUser['name'])); ?>
                                </span>
                                <span class="max-w-[9rem] truncate"><?php echo htmlspecialchars($ucsStudentUser['name']); ?></span>
                                <svg class="nav-chevron h-3.5 w-3.5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div class="nav-dropdown nav-dropdown-right" role="menu" aria-label="Student account menu">
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="nav-dropdown-link" role="menuitem">Dashboard</a>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/student-profile.php'); ?>" class="nav-dropdown-link" role="menuitem">My Profile</a>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/student-timetable.php'); ?>" class="nav-dropdown-link" role="menuitem">My Timetable</a>
                                <a href="<?php echo htmlspecialchars(ROOT_URL . '/actions/student/logout.php'); ?>" class="nav-dropdown-link nav-dropdown-link-danger" role="menuitem">Logout</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="button" id="mobile-menu-toggle" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 transition-colors duration-150 hover:bg-gray-100 lg:hidden focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Open main menu" aria-expanded="false" aria-controls="mobile-menu">
                        <svg id="menu-icon-open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M3 6h18M3 12h18M3 18h18"></path>
                        </svg>
                        <svg id="menu-icon-close" xmlns="http://www.w3.org/2000/svg" class="hidden h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Search panel -->
            <div id="search-panel" class="search-panel border-t border-gray-100" role="search">
                <div>
                    <form action="<?php echo htmlspecialchars(BASE_URL . '/search.php'); ?>" method="get" class="px-0 pb-3 pt-3">
                        <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 transition-colors focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input id="site-search-input" type="search" name="q" placeholder="Search programmes, admissions, news..." autocomplete="off" class="w-full bg-transparent py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none">
                            <button type="button" id="search-close" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Close search">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden border-t border-gray-100 lg:hidden">
            <nav aria-label="Mobile navigation" class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <ul class="space-y-1">
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
                                <button type="button" class="flex w-full items-center justify-between rounded-lg px-3 py-3 text-base font-medium transition-colors hover:bg-blue-50 <?php echo $isActive ? 'text-blue-700' : 'text-gray-800'; ?>" data-mobile-submenu-toggle aria-expanded="false" aria-controls="mobile-submenu-<?php echo htmlspecialchars($item['label']); ?>">
                                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                                    <svg data-chevron xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div class="mobile-submenu" id="mobile-submenu-<?php echo htmlspecialchars($item['label']); ?>">
                                    <div>
                                        <ul class="mt-1 space-y-1 border-l border-gray-200 pl-4">
                                            <?php foreach ($item['children'] as $child): ?>
                                                <li>
                                                    <a href="<?php echo htmlspecialchars($child['url']); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-blue-50 hover:text-blue-700" data-nav-sections="<?php echo htmlspecialchars(ucs_nav_section($child['url'])); ?>"><?php echo htmlspecialchars($child['label']); ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        <?php else: ?>
                            <?php $isActive = ucs_nav_is_active($item['url'], $relativePath); ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($item['url']); ?>" class="block rounded-lg px-3 py-3 text-base font-medium <?php echo $isActive ? 'bg-blue-50 text-blue-700' : 'text-gray-800 transition-colors hover:bg-blue-50 hover:text-blue-700'; ?>" data-nav-sections="<?php echo htmlspecialchars(ucs_nav_sections($item['url'])); ?>"><?php echo htmlspecialchars($item['label']); ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($ucsStudentUser === null): ?>
                        <!-- Student Login (mobile) -->
                        <li class="mt-2 border-t border-gray-100 pt-2">
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/student-login.php'); ?>" class="flex items-center gap-2 rounded-lg px-3 py-3 text-base font-medium text-blue-700 transition-colors hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                Student Login
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Student account (mobile) -->
                        <li class="mt-2 border-t border-gray-100 pt-2">
                            <p class="px-3 pb-1 pt-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Student Account</p>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-blue-50 hover:text-blue-700">Dashboard</a>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/student-profile.php'); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-blue-50 hover:text-blue-700">My Profile</a>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/student-timetable.php'); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-blue-50 hover:text-blue-700">My Timetable</a>
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

            // --- Sticky header shadow on scroll ---
            if (header) {
                function onScroll() {
                    if (window.scrollY > 8) {
                        header.classList.add('shadow-md');
                    } else {
                        header.classList.remove('shadow-md');
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
            var navActiveUnderline = ['text-blue-700', 'after:absolute', 'after:inset-x-3', 'after:bottom-1', 'after:h-0.5', 'after:rounded-full', 'after:bg-blue-600'];

            function setNavActiveState(el, active) {
                var childLike = el.classList.contains('nav-dropdown-link') || !!el.closest('#mobile-menu');
                el.classList.toggle('text-blue-700', active);
                if (childLike) {
                    el.classList.toggle('bg-blue-50', active);
                } else {
                    navActiveUnderline.forEach(function (cls) {
                        el.classList.toggle(cls, active);
                    });
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