<?php
/**
 * Homepage Campus Location & Contact section.
 *
 * Two-column layout with map and contact details.
 * Editorial institutional style with bordered panels and clear hierarchy.
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

$ucsProfile = null;
if (isset($pdo)) {
    try {
        $ucsStmt = $pdo->query(
            "SELECT name, address, phone, email
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsProfile = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsProfile = null;
    }
}

$ucsLocName      = $ucsProfile['name']    ?? APP_NAME;
$ucsLocAddress   = $ucsProfile['address'] ?? '';
$ucsLocPhone     = $ucsProfile['phone']   ?? '';
$ucsLocEmail     = $ucsProfile['email']   ?? '';
$ucsLocPhoneHref = $ucsLocPhone !== '' ? 'tel:+' . preg_replace('/\D/', '', $ucsLocPhone) : '';
$ucsLocMapUrl    = 'https://www.google.com/maps?q=' . rawurlencode('University of Computer Studies, Meiktila') . '&output=embed';
?>
<section class="border-t border-slate-200 bg-white py-20 sm:py-24" id="location-contact-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:gap-12">

            <!-- Campus location -->
            <section aria-labelledby="campus-location-heading">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Find Us</p>
                <h2 id="campus-location-heading" class="mt-3 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-slate-900">Campus Location</h2>
                <p class="mt-3 text-base leading-relaxed text-slate-600">
                    Find the university campus and plan your visit.
                </p>

                <div class="mt-6 overflow-hidden border border-slate-200">
                    <?php if ($ucsLocMapUrl !== ''): ?>
                        <iframe
                            src="<?php echo htmlspecialchars($ucsLocMapUrl); ?>"
                            title="Map showing the location of <?php echo htmlspecialchars($ucsLocName); ?>"
                            class="h-72 w-full border-0 sm:h-80 lg:h-full lg:min-h-[360px]"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    <?php else: ?>
                        <div class="flex h-72 items-center justify-center sm:h-80 bg-slate-50">
                            <p class="text-sm text-slate-400">Map unavailable.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Contact information -->
            <section aria-labelledby="location-contact-info-heading">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Contact</p>
                <h2 id="location-contact-info-heading" class="mt-3 scroll-mt-24 text-3xl font-bold tracking-[-0.02em] text-slate-900">Contact Information</h2>
                <p class="mt-3 text-base leading-relaxed text-slate-600">
                    Reach out to the university through the channels below.
                </p>

                <div class="mt-6 border border-slate-200 bg-white">
                    <div class="px-6 py-5 sm:px-8">
                        <div class="space-y-4">
                            <div class="flex items-start gap-4">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center text-blue-600" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"></path></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">University</p>
                                    <p class="mt-1 text-sm font-medium text-slate-900"><?php echo htmlspecialchars($ucsLocName); ?></p>
                                </div>
                            </div>

                            <?php if ($ucsLocAddress !== ''): ?>
                                <div class="border-t border-slate-200 pt-4 flex items-start gap-4">
                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center text-blue-600" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Address</p>
                                        <p class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars($ucsLocAddress); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($ucsLocPhone !== ''): ?>
                                <div class="border-t border-slate-200 pt-4 flex items-start gap-4">
                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center text-blue-600" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Phone</p>
                                        <a href="<?php echo htmlspecialchars($ucsLocPhoneHref); ?>" class="mt-1 block text-sm font-medium text-slate-700 transition-colors duration-150 hover:text-blue-600"><?php echo htmlspecialchars($ucsLocPhone); ?></a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($ucsLocEmail !== ''): ?>
                                <div class="border-t border-slate-200 pt-4 flex items-start gap-4">
                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center text-blue-600" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 5L2 7"></path></svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Email</p>
                                        <a href="<?php echo htmlspecialchars('mailto:' . $ucsLocEmail); ?>" class="mt-1 block text-sm font-medium text-slate-700 transition-colors duration-150 hover:text-blue-600"><?php echo htmlspecialchars($ucsLocEmail); ?></a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <a href="<?php echo htmlspecialchars('mailto:' . $ucsLocEmail); ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Contact Us
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </section>

        </div>
    </div>
</section>
