<?php
/**
 * Admin navigation definition.
 *
 * Single source of truth for the admin sidebar navigation. Pages set the
 * $activeNav variable to one of the item keys below to highlight the current
 * section and build the page breadcrumb.
 *
 * Returns: $ucsAdminNavItems
 *   - list of ['key', 'label', 'url', 'icon', 'danger?']
 *
 * Icon markup is an inline Lucide-style SVG (stroke = currentColor) so the
 * color follows the link text (active / inactive states).
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

$ucsAdminNavItems = [
    [
        'key'   => 'dashboard',
        'label' => 'Dashboard',
        'url'   => '/admin/dashboard.php',
        'icon'  => '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect>',
    ],
    [
        'key'   => 'news',
        'label' => 'News',
        'url'   => '/admin/news/index.php',
        'icon'  => '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path><path d="M18 14h-8"></path><path d="M15 18h-5"></path><path d="M10 6h8v4h-8V6Z"></path>',
    ],
    [
        'key'   => 'faculties-departments',
        'label' => 'Faculties & Departments',
        'url'   => '/admin/faculties-departments/index.php',
        'icon'  => '<path d="M14 22v-4a2 2 0 1 0-4 0v4"></path><path d="m18 10 3.447 1.724a1 1 0 0 1 .553.894V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7.382a1 1 0 0 1 .553-.894L6 10"></path><path d="M18 5v17"></path><path d="m4 6 8-4 8 4"></path><path d="M6 5v17"></path><circle cx="12" cy="9" r="2"></circle>',
    ],
    [
        'key'   => 'facilities',
        'label' => 'Facilities',
        'url'   => '/admin/facilities/index.php',
        'icon'  => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path>',
    ],
    [
        'key'   => 'majors',
        'label' => 'Majors',
        'url'   => '/admin/majors/index.php',
        'icon'  => '<circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>',
    ],
    [
        'key'   => 'courses',
        'label' => 'Courses',
        'url'   => '/admin/courses/index.php',
        'icon'  => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>',
    ],
    [
        'key'   => 'classrooms',
        'label' => 'Classrooms',
        'url'   => '/admin/classrooms/index.php',
        'icon'  => '<path d="M22 9 12 5 2 9l10 4 10-4z"></path><path d="M6 11.5V15c0 1.66 2.69 3 6 3s6-1.34 6-3v-3.5"></path><path d="M2 9v5"></path>',
    ],
    [
        'key'   => 'timetables',
        'label' => 'Timetables',
        'url'   => '/admin/timetables/index.php',
        'icon'  => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
    ],
    [
        'key'   => 'students',
        'label' => 'Students',
        'url'   => '/admin/students/index.php',
        'icon'  => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
    ],
    [
        'key'   => 'alumni',
        'label' => 'Alumni',
        'url'   => '/admin/alumni/index.php',
        'icon'  => '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path><path d="M22 10v6"></path><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>',
    ],
    [
        'key'   => 'discussions',
        'label' => 'Discussions',
        'url'   => '/admin/discussions/index.php',
        'icon'  => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>',
    ],
    [
        'key'   => 'discussion-reports',
        'label' => 'Discussion Reports',
        'url'   => '/admin/discussions/reports.php',
        'icon'  => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line>',
    ],

    [
        'key'   => 'admission',
        'label' => 'Admission',
        'url'   => '/admin/admission/index.php',
        'icon'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
    ],
    [
        'key'   => 'academic-years',
        'label' => 'Academic Years',
        'url'   => '/admin/academic-years/index.php',
        'icon'  => '<rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
    ],
    [
        'key'   => 'profile',
        'label' => 'Profile',
        'url'   => '/admin/profile.php',
        'icon'  => '<circle cx="12" cy="8" r="5"></circle><path d="M20 21a8 8 0 1 0-16 0"></path>',
    ],
    [
        'key'    => 'logout',
        'label'  => 'Logout',
        'url'    => '/admin/logout.php',
        'icon'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
        'danger' => true,
    ],
];
