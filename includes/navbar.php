<?php
/**
 * Public site navigation bar.
 * Contains the shared navigation data and renders the desktop nav.
 * The mobile menu is rendered in includes/header.php using the same $navItems data.
 *
 * All homepage content is organised as sections on the single public
 * landing page (public/index.php), so navigation items point at the existing
 * homepage section headings (index.php#...) which already provide the correct
 * scroll offset below the sticky navbar via scroll-mt-24.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

/**
 * Determine whether a nav URL matches the currently requested page.
 * Homepage section targets (index.php#...) are highlighted client-side from
 * the URL hash; only page-level targets are matched on the server.
 */
function ucs_nav_is_active($navUrl, $relativePath, array $alsoMatch = [])
{
    $navParts    = parse_url($navUrl);
    $navBasename = basename((string) ($navParts['path'] ?? ''));
    $navHash     = (string) ($navParts['fragment'] ?? '');

    // Section anchors on the homepage are never "active" server-side.
    if ($navHash !== '') {
        return false;
    }

    if (in_array($relativePath, $alsoMatch, true)) {
        return true;
    }

    if ($navBasename === 'index.php') {
        return $relativePath === '' || $relativePath === 'index.php';
    }

    return $relativePath === $navBasename;
}

/**
 * Return the homepage section id (URL fragment) a nav target points to,
 * or an empty string for plain page links.
 */
function ucs_nav_section($navUrl)
{
    return (string) parse_url($navUrl, PHP_URL_FRAGMENT);
}

/**
 * Return the section sentinel(s) a nav item should match against the URL
 * hash for client-side highlighting. Homepage links map to the "home"
 * sentinel; plain page links (no fragment) return an empty string so the
 * server-rendered active state is preserved.
 */
function ucs_nav_sections($navUrl)
{
    $section = ucs_nav_section($navUrl);
    if ($section !== '') {
        return $section;
    }
    $path = (string) parse_url($navUrl, PHP_URL_PATH);
    return basename($path) === 'index.php' ? 'home' : '';
}

$ucs_basePath = rtrim((string) parse_url(BASE_URL, PHP_URL_PATH), '/');
$ucs_requestPath = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

$relativePath = $ucs_requestPath;
if ($ucs_basePath !== '' && strpos($ucs_requestPath . '/', $ucs_basePath . '/') === 0) {
    $relativePath = substr($ucs_requestPath, strlen($ucs_basePath)) ?: '';
}
$relativePath = trim($relativePath, '/');

$navItems = [
    ['label' => 'Home', 'url' => BASE_URL . '/index.php'],
    ['label' => 'About Us', 'url' => BASE_URL . '/about.php'],
    [
        'label'    => 'Academics',
        'children' => [
            ['label' => 'Degree Programmes', 'url' => BASE_URL . '/degree-programmes.php'],
        ],
    ],
    ['label' => 'Faculties', 'url' => BASE_URL . '/faculties.php'],
    ['label' => 'Campus Life', 'url' => BASE_URL . '/index.php#campus-life-heading'],
    ['label' => 'News', 'url' => BASE_URL . '/index.php#announcements-heading'],
    ['label' => 'Announcements', 'url' => BASE_URL . '/index.php#announcements-heading'],
    ['label' => 'Admissions', 'url' => BASE_URL . '/index.php#admissions-heading'],
    ['label' => 'Contact', 'url' => BASE_URL . '/index.php#location-contact-heading'],
];

$navLinkBase = 'relative inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600';
$navLinkActive = 'text-blue-700 after:absolute after:inset-x-3 after:bottom-1 after:h-0.5 after:rounded-full after:bg-blue-600';
?>

<nav class="hidden lg:block" aria-label="Main navigation">
    <ul class="flex items-center gap-0.5">
        <?php foreach ($navItems as $item): ?>
            <?php if (!empty($item['children'])): ?>
                <?php
                $isActive = false;
                foreach ($item['children'] as $child) {
                    if (ucs_nav_is_active($child['url'], $relativePath, $child['match'] ?? [])) {
                        $isActive = true;
                        break;
                    }
                }
                $ucsParentSections = array_values(array_filter(array_map(
                    'ucs_nav_section',
                    array_map(function ($c) { return $c['url']; }, $item['children'])
                )));
                ?>
                <li class="group relative">
                    <button type="button" class="<?php echo $navLinkBase . ($isActive ? ' ' . $navLinkActive : ''); ?>" data-nav-sections="<?php echo htmlspecialchars(implode(' ', $ucsParentSections)); ?>" data-dropdown-toggle aria-expanded="false" aria-haspopup="true">
                        <?php echo htmlspecialchars($item['label']); ?>
                        <svg class="nav-chevron h-3.5 w-3.5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="nav-dropdown" role="menu" aria-label="<?php echo htmlspecialchars($item['label']); ?>">
                        <?php foreach ($item['children'] as $child): ?>
                            <a href="<?php echo htmlspecialchars($child['url']); ?>" class="nav-dropdown-link" data-nav-sections="<?php echo htmlspecialchars(ucs_nav_section($child['url'])); ?>" role="menuitem"><?php echo htmlspecialchars($child['label']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </li>
            <?php else: ?>
                <?php
                $isActive = ucs_nav_is_active($item['url'], $relativePath, $item['match'] ?? []);
                $ucsItemSections = ucs_nav_sections($item['url']);
                ?>
                <li>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" class="<?php echo $navLinkBase . ($isActive ? ' ' . $navLinkActive : ''); ?>" data-nav-sections="<?php echo htmlspecialchars($ucsItemSections); ?>"><?php echo htmlspecialchars($item['label']); ?></a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>