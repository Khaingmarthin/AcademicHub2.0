<?php
/**
 * Public Faculties & Departments module.
 *
 * The single unified academic directory, reused by the Navbar → Faculties
 * link and the homepage Faculties section. Both use the same shared component
 * (includes/helpers/ucs-faculties-directory.php) so the design is consistent across
 * the website.
 *
 * Layout: editorial page header, then the shared tabbed directory —
 *
 *   Faculties & Departments
 *       ↓
 *   [ Faculties ] [ Departments ]
 *       ↓
 *   Structured editorial list
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
    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white" aria-labelledby="faculties-page-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <div class="max-w-3xl">
                <nav class="mb-6 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1.5">
                        <li><a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="transition-colors hover:text-slate-600">Home</a></li>
                        <li aria-hidden="true"><svg class="h-3 w-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg></li>
                        <li class="text-slate-600">Faculties &amp; Departments</li>
                    </ol>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Academic Structure</p>
                <h1 id="faculties-page-heading" class="mt-3 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.75rem] leading-[1.1]">
                    Faculties &amp; Departments
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                    Explore our academic community — the faculties and departments that support
                    teaching, learning, and academic development at <?php echo htmlspecialchars($ucsShortName); ?>.
                </p>
            </div>
        </div>
    </section>

    <!-- Shared tabbed directory -->
    <section class="bg-slate-50 py-12 sm:py-16 lg:py-20" aria-labelledby="directory-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="directory-heading" class="sr-only">Academic directory</h2>
            <?php include __DIR__ . '/../includes/helpers/ucs-faculties-directory.php'; ?>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>
