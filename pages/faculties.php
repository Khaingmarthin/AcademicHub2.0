<?php
/**
 * Public Faculties & Departments module.
 *
 * The single unified academic directory, reused by the Navbar → Faculties
 * link and the homepage Faculties section. Both use the same shared component
 * (includes/helpers/ucs-faculties-directory.php) so the design is consistent across
 * the website.
 *
 * Layout: page heading in the hero, then the shared tabbed directory —
 *
 *   Faculties & Departments
 *       ↓
 *   [ Faculties ] [ Departments ]
 *       ↓
 *   Selected content/cards
 *
 * All data is read from the database only — nothing is hard-coded. Full
 * descriptions remain available on the existing detail pages.
 */
require_once '../config/app.php';
require_once '../config/database.php';

$pageTitle = 'Faculties & Departments';

$ucsHeroMedia = 'images/front_view.jpg';
$ucsShortName = 'UCSMTLA';

try {
    $ucsProfileStmt = $pdo->query(
        "SELECT short_name, hero_media
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
    $ucsShortName  = $ucsProfileRow['short_name'] ?? 'UCSMTLA';
    $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';
} catch (PDOException $e) {
    $ucsShortName = 'UCSMTLA';
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="faculties-page-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-7xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:py-28">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Academic Structure</p>
            <h1 id="faculties-page-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Faculties &amp; Departments</h1>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                Explore our academic community — the faculties and departments that support teaching, learning, and academic development at <?php echo htmlspecialchars($ucsShortName); ?>.
            </p>
        </div>
    </section>

    <!-- Shared tabbed directory (tabs on top, content below) -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="directory-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="directory-heading" class="sr-only">Academic directory</h2>
            <?php include __DIR__ . '/../includes/helpers/ucs-faculties-directory.php'; ?>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>