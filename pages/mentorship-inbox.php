<?php
/**
 * Alumni Mentorship - Alumnus's mentorship inbox.
 *
 * Lists mentorship requests received by the logged-in alumnus's verified
 * profile, newest first. Pending requests can be accepted or declined;
 * accepted mentorships can be marked as completed here. Includes a quick
 * availability toggle. Suspended mentors are locked out of these controls.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/alumni-validation.php';
require_once __DIR__ . '/../includes/helpers/mentorship-validation.php';

student_require_login();

$ucsUser    = student_current_user();
$ucsProfile = alumni_current_profile($pdo);

if ($ucsProfile === null) {
    header('Location: ' . BASE_URL . '/alumni.php');
    exit;
}

$ucsProfileId = (int) $ucsProfile['id'];
$pageTitle    = 'Mentorship Inbox';

$ucsRequests = [];
try {
    $ucsStmt = $pdo->prepare(
        "SELECT mr.id, mr.message, mr.status, mr.created_at, mr.responded_at,
                s.name AS student_name, s.graduation_year
         FROM mentorship_requests mr
         JOIN students s ON s.id = mr.student_id
         WHERE mr.alumni_profile_id = :profile_id
         ORDER BY mr.created_at DESC, mr.id DESC"
    );
    $ucsStmt->execute([':profile_id' => $ucsProfileId]);
    $ucsRequests = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsRequests = [];
}

$ucsFlash         = $_SESSION['mentorship_flash'] ?? null;
unset($_SESSION['mentorship_flash']);

$ucsSuspended   = (int) $ucsProfile['mentorship_suspended'] === 1;
$ucsAvailable   = !$ucsSuspended && (int) $ucsProfile['mentorship_available'] === 1;

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
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="mentorship-inbox-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Alumni Mentorship</p>
                <h1 id="mentorship-inbox-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Mentorship Inbox</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Review mentorship requests from current students and manage your availability.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="mentorship-inbox-list-heading">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <?php if ($ucsFlash !== null): ?>
                <div class="mb-6 rounded-xl px-4 py-3 ring-1 <?php echo $ucsFlash['type'] === 'success' ? 'bg-emerald-50 ring-emerald-100' : 'bg-red-50 ring-red-100'; ?>" role="alert">
                    <p class="text-sm font-medium <?php echo $ucsFlash['type'] === 'success' ? 'text-emerald-800' : 'text-red-800'; ?>">
                        <?php echo htmlspecialchars((string) $ucsFlash['message']); ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Availability -->
            <?php if ($ucsSuspended): ?>
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
                    <p class="text-sm font-semibold text-red-800">Mentorship disabled</p>
                    <p class="mt-0.5 text-sm leading-6 text-red-700">
                        An administrator has disabled mentorship for your account. Contact the university to restore access.
                    </p>
                </div>
            <?php else: ?>
                <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl <?php echo $ucsAvailable ? 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200'; ?>" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                <?php echo $ucsAvailable ? 'Available for Mentorship' : 'Not Accepting Requests'; ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <?php echo $ucsAvailable
                                    ? 'Students can request mentorship from you.'
                                    : 'You are hidden from mentorship requests.'; ?>
                            </p>
                        </div>
                    </div>
                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/mentorship/availability.php'); ?>" class="shrink-0">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                        <input type="hidden" name="available" value="<?php echo $ucsAvailable ? '0' : '1'; ?>">
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl <?php echo $ucsAvailable ? 'border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50' : 'bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700'; ?> transition-colors duration-150 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <?php echo $ucsAvailable ? 'Mark Unavailable' : 'Start Accepting Requests'; ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (empty($ucsRequests)): ?>
                <div class="rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200 sm:p-12">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><path d="m22 7-10 5L2 7"></path></svg>
                    </span>
                    <h2 id="mentorship-inbox-list-heading" class="mt-5 text-xl font-extrabold tracking-tight text-gray-900">No Mentorship Requests</h2>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-gray-600">
                        You have no mentorship requests yet. Turn on availability so students can find you.
                    </p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($ucsRequests as $ucsRequest): ?>
                        <?php
                        $ucsStatus  = (string) $ucsRequest['status'];
                        $ucsCreated = new DateTime((string) $ucsRequest['created_at']);
                        ?>
                        <article class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200" aria-labelledby="mentorship-inbox-item-heading-<?php echo (int) $ucsRequest['id']; ?>">
                            <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h2 id="mentorship-inbox-item-heading-<?php echo (int) $ucsRequest['id']; ?>" class="truncate text-base font-bold text-gray-900">
                                            <?php echo htmlspecialchars((string) $ucsRequest['student_name']); ?>
                                        </h2>
                                        <p class="text-sm text-gray-500">
                                            Requested <?php echo htmlspecialchars($ucsCreated->format('d M Y')); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <?php if ($ucsStatus === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500" aria-hidden="true"></span>
                                            Pending
                                        </span>
                                    <?php elseif ($ucsStatus === 'accepted'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Accepted</span>
                                    <?php elseif ($ucsStatus === 'declined'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200">Declined</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 ring-1 ring-gray-200">Completed</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="px-6 py-5 sm:px-8">
                                <p class="text-sm leading-6 text-gray-700">
                                    <?php echo nl2br(htmlspecialchars((string) $ucsRequest['message'])); ?>
                                </p>

                                <?php if ($ucsStatus === 'pending' && !$ucsSuspended): ?>
                                    <div class="mt-5 flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row">
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/mentorship/respond.php'); ?>" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsRequest['id']; ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit"
                                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-emerald-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                                                Accept
                                            </button>
                                        </form>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/mentorship/respond.php'); ?>" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsRequest['id']; ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <button type="submit"
                                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                                Decline
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($ucsStatus === 'accepted' && !$ucsSuspended): ?>
                                    <div class="mt-5 flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-sm leading-6 text-gray-600">
                                            Accepted — the student can now see your shared contact details.
                                        </p>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/mentorship/complete.php'); ?>" class="shrink-0">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsRequest['id']; ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                                Mark as Completed
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>