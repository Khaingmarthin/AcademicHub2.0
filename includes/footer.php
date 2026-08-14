<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

// Reuse the existing database connection if the page already connected.
if (!isset($pdo)) {
    $ucsDbFile = __DIR__ . '/database.php';
    if (file_exists($ucsDbFile)) {
        require_once $ucsDbFile;
    }
}

// University profile (contact details) from the database.
$ucsUniversity = null;
if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT name, short_name, logo, address, phone, email
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsUniversity = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsUniversity = null;
    }
}

$ucsName      = $ucsUniversity['name']       ?? APP_NAME;
$ucsShortName = $ucsUniversity['short_name'] ?? 'UCSMTLA';
$ucsLogo      = $ucsUniversity['logo']       ?? 'images/logo.png';
$ucsAddress   = $ucsUniversity['address']    ?? '';
$ucsPhone     = $ucsUniversity['phone']      ?? '';
$ucsEmail     = $ucsUniversity['email']      ?? '';

if (!preg_match('~^https?://~i', $ucsLogo)) {
    $ucsLogo = BASE_URL . '/assets/' . ltrim($ucsLogo, '/');
}

$ucsPhoneHref = $ucsPhone !== '' ? 'tel:+' . preg_replace('/\D/', '', $ucsPhone) : '';

$footerLinks = [
    'Home'              => BASE_URL . '/index.php',
    'About Us'          => BASE_URL . '/about.php',
    'Degree Programmes' => BASE_URL . '/degree-programmes.php',
    'Campus Life'       => BASE_URL . '/campus-life.php',
    'Admissions'        => BASE_URL . '/entrance-information.php',
    'Faculties'         => BASE_URL . '/faculties.php',
    'News'              => BASE_URL . '/news.php',
];
?>
<footer class="mt-12 bg-gray-900 text-gray-400">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-12 lg:gap-8">

            <!-- About the university -->
            <section class="lg:col-span-5" aria-labelledby="footer-university-heading">
                <h2 id="footer-university-heading" class="text-base font-semibold text-white">About the University</h2>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php'); ?>" class="mt-4 inline-flex items-center gap-3" aria-label="<?php echo htmlspecialchars($ucsShortName); ?> - Home">
                    <img src="<?php echo htmlspecialchars($ucsLogo); ?>" alt="<?php echo htmlspecialchars($ucsShortName); ?> logo" class="h-10 w-10 shrink-0 object-contain">
                    <span class="leading-tight">
                        <span class="block text-base font-bold tracking-tight text-white"><?php echo htmlspecialchars($ucsShortName); ?></span>
                        <span class="block text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-400">Academic Hub</span>
                    </span>
                </a>
                <p class="mt-3 max-w-sm text-sm leading-6">
                    <?php echo htmlspecialchars($ucsName); ?> provides accessible university, academic, admission, and campus information for students and the university community.
                </p>
            </section>

            <!-- Quick links -->
            <section class="lg:col-span-3" aria-labelledby="footer-quick-links-heading">
                <h2 id="footer-quick-links-heading" class="text-base font-semibold text-white">Quick Links</h2>
                <nav aria-label="Footer quick links">
                    <ul class="mt-4 space-y-2">
                        <?php foreach ($footerLinks as $ucsLabel => $ucsUrl): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($ucsUrl); ?>" class="text-sm transition-colors duration-150 hover:text-white"><?php echo htmlspecialchars($ucsLabel); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </section>

            <!-- Contact information -->
            <section class="md:col-span-2 lg:col-span-4" aria-labelledby="footer-contact-heading">
                <h2 id="footer-contact-heading" class="text-base font-semibold text-white">Contact Information</h2>
                <ul class="mt-4 space-y-3">
                    <?php if ($ucsAddress !== ''): ?>
                        <li class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-1 h-4 w-4 shrink-0 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span class="text-sm leading-6"><?php echo htmlspecialchars($ucsAddress); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if ($ucsPhone !== '' && $ucsPhoneHref !== ''): ?>
                        <li class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-1 h-4 w-4 shrink-0 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <a href="<?php echo htmlspecialchars($ucsPhoneHref); ?>" class="text-sm leading-6 transition-colors duration-150 hover:text-white"><?php echo htmlspecialchars($ucsPhone); ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if ($ucsEmail !== ''): ?>
                        <li class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-1 h-4 w-4 shrink-0 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="m22 7-10 5L2 7"></path>
                            </svg>
                            <a href="<?php echo htmlspecialchars('mailto:' . $ucsEmail); ?>" class="text-sm leading-6 transition-colors duration-150 hover:text-white"><?php echo htmlspecialchars($ucsEmail); ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </section>
        </div>
    </div>

    <div class="border-t border-gray-800">
        <div class="mx-auto max-w-7xl px-4 pt-5 pb-4 text-center text-xs text-gray-500 sm:px-6 lg:px-8">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($ucsShortName . ' Academic Hub'); ?>. All rights reserved.
        </div>
    </div>
</footer>
</body>
</html>