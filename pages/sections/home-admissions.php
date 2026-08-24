<?php
/**
 * Homepage Admissions call-to-action section.
 *
 * Professional institutional CTA. Clean blue background with clear messaging.
 * No excessive gradients or decorative elements.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../../config/database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}

$ucsAdmissionTitle = '';
if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT admission_title
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsProfile = $ucsStmt->fetch();
        if ($ucsProfile && !empty($ucsProfile['admission_title'])) {
            $ucsAdmissionTitle = $ucsProfile['admission_title'];
        }
    } catch (PDOException $e) {
        $ucsAdmissionTitle = '';
    }
}
?>
<section class="bg-blue-600 py-16 sm:py-20" aria-labelledby="admissions-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <?php if ($ucsAdmissionTitle !== ''): ?>
                <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">
                    <?php echo htmlspecialchars($ucsAdmissionTitle); ?>
                </span>
            <?php endif; ?>

            <h2 id="admissions-heading" class="mt-5 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-white sm:text-4xl">
                Begin Your Journey at UCSMTLA
            </h2>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-blue-100">
                Explore our academic programmes and discover how you can become part of the UCSMTLA community.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/entrance-information.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:bg-blue-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    Explore Admissions
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:border-white/50 hover:bg-white/20 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    View Degree Programmes
                </a>
            </div>
        </div>
    </div>
</section>
