<?php
/**
 * Alumni Mentorship - Request mentorship from an alumnus (student side).
 *
 * Logged-in students write a short message to a verified alumnus who is
 * currently available for mentorship. This is not a chat: the message is a
 * one-time request that the alumnus accepts or declines.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/mentorship-validation.php';

student_require_login();

$ucsUser        = student_current_user();
$ucsProfileId   = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$ucsProfile     = null;
$ucsPageState   = 'not_found';

if ($ucsProfileId !== false && $ucsProfileId > 0) {
    $ucsProfile = mentorship_mentor_profile($pdo, $ucsProfileId);
}

$pageTitle = 'Request Mentorship';

if ($ucsProfile !== null) {
    $ucsProfileId = (int) $ucsProfile['id'];
    if ((int) $ucsProfile['student_id'] === (int) $ucsUser['id']) {
        $ucsPageState = 'self';
    } elseif ((int) $ucsProfile['mentorship_suspended'] === 1) {
        $ucsPageState = 'unavailable';
    } elseif ((int) $ucsProfile['mentorship_available'] !== 1) {
        $ucsPageState = 'unavailable';
    } else {
        $ucsActiveRequest = mentorship_active_request($pdo, (int) $ucsUser['id'], $ucsProfileId);
        if ($ucsActiveRequest !== null) {
            $ucsPageState = 'already_requested';
        } else {
            $ucsPageState = 'form';
        }
    }
}

$ucsFlash = $_SESSION['mentorship_flash'] ?? null;
unset($_SESSION['mentorship_flash']);

$ucsErrors = $_SESSION['mentorship_errors'] ?? [];
unset($_SESSION['mentorship_errors']);

$ucsOld = $_SESSION['mentorship_old'] ?? [];
unset($_SESSION['mentorship_old']);

$ucsMentorshipAreas = $ucsProfile !== null
    ? mentorship_load_profile_areas($pdo, $ucsProfileId)
    : [];

$ucsHeroMedia = 'images/front_view.jpg';
try {
    $ucsProfileStmt = $pdo->query(
        "SELECT hero_media
         FROM university_profile
         ORDER BY id ASC
         LIMIT 1"
    );
    $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
    $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';
} catch (PDOException $e) {
    // Keep the default hero media.
}

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Page hero -->
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="mentorship-request-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Alumni Mentorship</p>
                <h1 id="mentorship-request-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Request Mentorship</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Send a short request to an alumnus who is available for career guidance.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="mentorship-request-form-heading">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <?php if ($ucsPageState === 'form'): ?>
                <?php
                $ucsHasPhoto = false;
                $ucsPhotoUrl = '';
                if (!empty($ucsProfile['profile_photo'])) {
                    $ucsPhotoFile = dirname(__DIR__) . '/assets/' . ltrim($ucsProfile['profile_photo'], '/');
                    $ucsHasPhoto  = is_file($ucsPhotoFile);
                    if ($ucsHasPhoto) {
                        $ucsPhotoUrl = ROOT_URL . '/assets/' . ltrim($ucsProfile['profile_photo'], '/');
                    }
                }
                ?>

                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200">
                    <!-- Mentor summary -->
                    <div class="flex items-center gap-4 border-b border-gray-100 bg-gray-50/70 px-6 py-5 sm:px-8">
                        <?php if ($ucsHasPhoto): ?>
                            <img src="<?php echo htmlspecialchars($ucsPhotoUrl); ?>" alt="Portrait of <?php echo htmlspecialchars((string) $ucsProfile['student_name']); ?>" class="h-14 w-14 rounded-2xl object-cover ring-2 ring-white">
                        <?php else: ?>
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-xl font-extrabold text-white ring-2 ring-white" aria-hidden="true">
                                <?php echo htmlspecialchars(strtoupper(mb_substr((string) $ucsProfile['student_name'], 0, 1))); ?>
                            </span>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <h2 id="mentorship-request-form-heading" class="truncate text-lg font-bold tracking-tight text-gray-900">
                                <?php echo htmlspecialchars((string) $ucsProfile['student_name']); ?>
                            </h2>
                            <p class="truncate text-sm text-gray-500">
                                <?php echo htmlspecialchars(trim((string) ($ucsProfile['current_job'] ?? '') . ($ucsProfile['company'] !== '' && $ucsProfile['current_job'] !== '' ? ' · ' : '') . (string) ($ucsProfile['company'] ?? ''))); ?>
                            </p>
                        </div>
                        <span class="ml-auto hidden shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 sm:inline-flex">
                            Available for Mentorship
                        </span>
                    </div>

                    <div class="px-6 py-6 sm:px-8">
                        <?php if (!empty($ucsMentorshipAreas)): ?>
                            <div class="mb-6 flex flex-wrap gap-2">
                                <?php foreach ($ucsMentorshipAreas as $ucsArea): ?>
                                    <span class="inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-xs font-medium text-violet-700 ring-1 ring-violet-100">
                                        <?php echo htmlspecialchars((string) $ucsArea['name']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($ucsFlash !== null): ?>
                            <div class="mb-6 rounded-xl px-4 py-3 ring-1 <?php echo $ucsFlash['type'] === 'success' ? 'bg-emerald-50 ring-emerald-100' : 'bg-red-50 ring-red-100'; ?>" role="alert">
                                <p class="text-sm font-medium <?php echo $ucsFlash['type'] === 'success' ? 'text-emerald-800' : 'text-red-800'; ?>">
                                    <?php echo htmlspecialchars((string) $ucsFlash['message']); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($ucsErrors)): ?>
                            <div class="mb-6 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
                                <ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
                                    <?php foreach ($ucsErrors as $ucsError): ?>
                                        <li><?php echo htmlspecialchars($ucsError); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/mentorship/request.php'); ?>" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                            <input type="hidden" name="alumni_profile_id" value="<?php echo (int) $ucsProfileId; ?>">

                            <div>
                                <label for="message" class="block text-sm font-semibold text-gray-700">Your message</label>
                                <p class="mt-1 text-sm leading-5 text-gray-500">
                                    Introduce yourself, your year and what kind of guidance you are looking for.
                                </p>
                                <textarea id="message" name="message" rows="6" required maxlength="2000"
                                          placeholder="e.g. I am a second-year Web Development student interested in becoming a frontend developer. I would really appreciate guidance on what to focus on next."
                                          class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars((string) ($ucsOld['message'] ?? '')); ?></textarea>
                            </div>

                            <div class="flex flex-col gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . $ucsProfileId); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                                    Cancel
                                </a>
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    Send Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($ucsPageState === 'already_requested' || $ucsPageState === 'self' || $ucsPageState === 'unavailable'): ?>
                <div class="rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200 sm:p-12">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 ring-1 ring-amber-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </span>
                    <h2 id="mentorship-request-form-heading" class="mt-5 text-xl font-extrabold tracking-tight text-gray-900">
                        <?php if ($ucsPageState === 'already_requested'): ?>
                            Request Already Sent
                        <?php elseif ($ucsPageState === 'self'): ?>
                            This Is Your Own Profile
                        <?php else: ?>
                            Not Currently Available
                        <?php endif; ?>
                    </h2>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-gray-600">
                        <?php if ($ucsPageState === 'already_requested'): ?>
                            You already have an active mentorship request with this alumnus. Track its status in your mentorship requests.
                        <?php elseif ($ucsPageState === 'self'): ?>
                            You cannot request mentorship from yourself. View your mentorship inbox instead.
                        <?php else: ?>
                            This alumnus is not accepting mentorship requests right now.
                        <?php endif; ?>
                    </p>
                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/my-mentorship.php'); ?>"
                           class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            My Mentorship
                        </a>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>"
                           class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                            Browse Alumni
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200 sm:p-12">
                    <h2 id="mentorship-request-form-heading" class="text-xl font-extrabold tracking-tight text-gray-900">Profile Not Found</h2>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-gray-600">
                        The requested alumni profile could not be found or is no longer available for mentorship.
                    </p>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>"
                       class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Browse Alumni
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>