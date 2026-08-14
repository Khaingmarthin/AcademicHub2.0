<?php
/**
 * Homepage Admissions call-to-action section.
 *
 * A visually distinct gradient banner between Student Life and the Location &
 * Contact section. Uses the real active admission title from the admissions
 * table when available; no requirements, deadlines, fees, or dates are
 * invented.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/../database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}

// Real admission entry (latest active), used only as a label badge.
$ucsAdmissionTitle = '';
if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT title
             FROM admissions
             WHERE status = 1
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsAdmission = $ucsStmt->fetch();
        if ($ucsAdmission) {
            $ucsAdmissionTitle = $ucsAdmission['title'];
        }
    } catch (PDOException $e) {
        $ucsAdmissionTitle = '';
    }
}
?>
<section class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 py-16 sm:py-20 lg:py-24" aria-labelledby="admissions-heading">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-blue-400/20 blur-3xl"></div>
        <div class="absolute -bottom-28 -left-24 h-80 w-80 rounded-full bg-indigo-400/20 blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 h-40 w-40 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/5 blur-2xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <?php if ($ucsAdmissionTitle !== ''): ?>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-white ring-1 ring-white/25 backdrop-blur">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path>
                        <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path>
                        <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path>
                        <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path>
                    </svg>
                    <?php echo htmlspecialchars($ucsAdmissionTitle); ?>
                </span>
            <?php endif; ?>

            <h2 id="admissions-heading" class="mt-5 scroll-mt-24 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                Begin Your Journey at UCSMTLA
            </h2>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-blue-100 sm:text-lg">
                Explore our academic programmes and discover how you can become part of the UCSMTLA community.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                <a href="<?php echo htmlspecialchars(BASE_URL . '/entrance-information.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-blue-700 shadow-lg shadow-blue-900/20 transition-all duration-200 hover:bg-blue-50 hover:shadow-xl focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    Explore Admissions
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </a>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/degree-programmes.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/40 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition-all duration-200 hover:border-white hover:bg-white/20 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    View Degree Programmes
                </a>
            </div>
        </div>
    </div>
</section>