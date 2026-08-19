<?php
/**
 * Alumni Mentorship - Student's mentorship requests (student side).
 *
 * Lists every mentorship request the logged-in student has made, newest
 * first, with per-request status. On accepted requests the mentor's shared
 * contact channels are revealed here (and only here / on the mentor's own
 * inbox) - never on public pages. Students can mark an accepted mentorship
 * as completed or report a request for moderation.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/mentorship-validation.php';

student_require_login();

$ucsUser = student_current_user();
$pageTitle = 'My Mentorship';

$ucsRequests = [];
try {
    $ucsStmt = $pdo->prepare(
        "SELECT mr.id, mr.message, mr.status, mr.created_at, mr.responded_at,
                mr.completed_at,
                ap.id AS alumni_profile_id, ap.current_job, ap.company,
                ap.profile_photo, ap.mentorship_suspended,
                s.name AS mentor_name, s.graduation_year
         FROM mentorship_requests mr
         JOIN alumni_profiles ap ON ap.id = mr.alumni_profile_id
         JOIN students s ON s.id = ap.student_id
         WHERE mr.student_id = :student_id
         ORDER BY mr.created_at DESC, mr.id DESC"
    );
    $ucsStmt->execute([':student_id' => (int) $ucsUser['id']]);
    $ucsRequests = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsRequests = [];
}

$ucsFlash = $_SESSION['mentorship_flash'] ?? null;
unset($_SESSION['mentorship_flash']);

$ucsReportErrors = $_SESSION['mentorship_report_errors'] ?? [];
unset($_SESSION['mentorship_report_errors']);

$ucsReportOld = $_SESSION['mentorship_report_old'] ?? [];
unset($_SESSION['mentorship_report_old']);

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
    <section class="relative overflow-hidden bg-gray-900" aria-labelledby="my-mentorship-heading">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
            <div class="hero-fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Alumni Mentorship</p>
                <h1 id="my-mentorship-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">My Mentorship</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg sm:leading-8">
                    Track your mentorship requests and view contact details for accepted mentorships.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="my-mentorship-list-heading">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <?php if ($ucsFlash !== null): ?>
                <div class="mb-6 rounded-xl px-4 py-3 ring-1 <?php echo $ucsFlash['type'] === 'success' ? 'bg-emerald-50 ring-emerald-100' : 'bg-red-50 ring-red-100'; ?>" role="alert">
                    <p class="text-sm font-medium <?php echo $ucsFlash['type'] === 'success' ? 'text-emerald-800' : 'text-red-800'; ?>">
                        <?php echo htmlspecialchars((string) $ucsFlash['message']); ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (empty($ucsRequests)): ?>
                <div class="rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200 sm:p-12">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    <h2 id="my-mentorship-list-heading" class="mt-5 text-xl font-extrabold tracking-tight text-gray-900">No Mentorship Requests Yet</h2>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-gray-600">
                        Browse the alumni directory and request mentorship from verified alumni who are available.
                    </p>
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni.php'); ?>"
                       class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Browse Alumni
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($ucsRequests as $ucsIndex => $ucsRequest): ?>
                        <?php
                        $ucsStatus  = (string) $ucsRequest['status'];
                        $ucsProfileId = (int) $ucsRequest['alumni_profile_id'];
                        $ucsContact = null;
                        if ($ucsStatus === 'accepted' && (int) $ucsRequest['mentorship_suspended'] !== 1) {
                            $ucsContact = mentorship_reveal_contact($pdo, $ucsProfileId);
                        }
                        $ucsHasPhoto = false;
                        $ucsPhotoUrl = '';
                        if (!empty($ucsRequest['profile_photo'])) {
                            $ucsPhotoFile = dirname(__DIR__) . '/assets/' . ltrim($ucsRequest['profile_photo'], '/');
                            $ucsHasPhoto  = is_file($ucsPhotoFile);
                            if ($ucsHasPhoto) {
                                $ucsPhotoUrl = ROOT_URL . '/assets/' . ltrim($ucsRequest['profile_photo'], '/');
                            }
                        }
                        $ucsCreated = new DateTime((string) $ucsRequest['created_at']);
                        ?>
                        <article class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200" aria-labelledby="mentorship-item-heading-<?php echo (int) $ucsRequest['id']; ?>">
                            <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:px-8">
                                <div class="flex items-center gap-4">
                                    <?php if ($ucsHasPhoto): ?>
                                        <img src="<?php echo htmlspecialchars($ucsPhotoUrl); ?>" alt="Portrait of <?php echo htmlspecialchars((string) $ucsRequest['mentor_name']); ?>" class="h-12 w-12 rounded-xl object-cover ring-2 ring-gray-100">
                                    <?php else: ?>
                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 text-lg font-extrabold text-white" aria-hidden="true">
                                            <?php echo htmlspecialchars(strtoupper(mb_substr((string) $ucsRequest['mentor_name'], 0, 1))); ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="min-w-0">
                                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-details.php?id=' . $ucsProfileId); ?>"
                                           class="truncate text-base font-bold text-gray-900 transition-colors duration-150 hover:text-blue-600">
                                            <?php echo htmlspecialchars((string) $ucsRequest['mentor_name']); ?>
                                        </a>
                                        <p class="truncate text-sm text-gray-500">
                                            <?php echo htmlspecialchars(trim((string) ($ucsRequest['current_job'] ?? '') . ($ucsRequest['company'] !== '' && $ucsRequest['current_job'] !== '' ? ' · ' : '') . (string) ($ucsRequest['company'] ?? ''))); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
                                    <?php if ($ucsStatus === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500" aria-hidden="true"></span>
                                            Pending
                                        </span>
                                    <?php elseif ($ucsStatus === 'accepted'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                            Accepted
                                        </span>
                                    <?php elseif ($ucsStatus === 'declined'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200">
                                            Declined
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 ring-1 ring-gray-200">
                                            Completed
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-xs text-gray-400">
                                        <?php echo htmlspecialchars($ucsCreated->format('d M Y')); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="px-6 py-5 sm:px-8">
                                <p class="text-sm leading-6 text-gray-700">
                                    <?php echo nl2br(htmlspecialchars((string) $ucsRequest['message'])); ?>
                                </p>

                                <?php if ($ucsStatus === 'accepted'): ?>
                                    <?php if ((int) $ucsRequest['mentorship_suspended'] === 1): ?>
                                        <div class="mt-5 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100">
                                            <p class="text-sm font-medium text-red-800">Mentorship disabled by an administrator</p>
                                            <p class="mt-0.5 text-sm text-red-700">This mentorship was stopped because of an administrative action.</p>
                                        </div>
                                    <?php elseif (!empty($ucsContact)): ?>
                                        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">
                                            <p class="flex items-center gap-2 text-sm font-semibold text-emerald-900">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M22 2 11 13"></path>
                                                    <path d="M22 2 15 22l-4-9-9-4Z"></path>
                                                </svg>
                                                Mentorship Accepted — Contact Details
                                            </p>
                                            <p class="mt-1 text-sm text-emerald-800">
                                                Your mentor has shared these channels for getting in touch:
                                            </p>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                <?php foreach ($ucsContact as $ucsChannel): ?>
                                                    <?php if ($ucsChannel[0] === 'email'): ?>
                                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-800 ring-1 ring-emerald-200">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 5L2 7"></path></svg>
                                                            <?php echo htmlspecialchars((string) $ucsChannel[2]); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <a href="<?php echo htmlspecialchars((string) $ucsChannel[2]); ?>" target="_blank" rel="noopener noreferrer"
                                                           class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-800 ring-1 ring-emerald-200 transition-colors duration-150 hover:bg-emerald-100">
                                                            <?php if ($ucsChannel[0] === 'linkedin'): ?>
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM7.119 20.452H3.554V9h3.565v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                                            <?php elseif ($ucsChannel[0] === 'github'): ?>
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-800" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                                                            <?php else: ?>
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars((string) $ucsChannel[1]); ?>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="mt-5 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-500 ring-1 ring-gray-100">
                                            The mentor has not shared any additional contact channels yet. Their professional links are visible on their profile.
                                        </p>
                                    <?php endif; ?>

                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/mentorship/complete.php'); ?>" class="mt-4">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsRequest['id']; ?>">
                                        <button type="submit"
                                                class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                                            Mark as Completed
                                        </button>
                                    </form>
                                <?php elseif ($ucsStatus === 'declined'): ?>
                                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-sm leading-6 text-gray-600">
                                            Your request was declined. You are welcome to request mentorship again.
                                        </p>
                                        <a href="<?php echo htmlspecialchars(BASE_URL . '/mentorship-request.php?id=' . $ucsProfileId); ?>"
                                           class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                            Request Again
                                        </a>
                                    </div>
                                <?php elseif ($ucsStatus === 'completed'): ?>
                                    <p class="mt-5 flex items-center gap-2 text-sm text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                                        This mentorship has been completed.
                                    </p>
                                <?php endif; ?>

                                <!-- Report -->
                                <?php if ($ucsStatus === 'pending' || $ucsStatus === 'accepted' || $ucsStatus === 'declined'): ?>
                                    <?php
                                    $ucsShowReportForm = ($ucsReportOld['request_id'] ?? null) !== null
                                        && (int) ($ucsReportOld['request_id'] ?? 0) === (int) $ucsRequest['id'];
                                    $ucsReportSent = false;
                                    try {
                                        $ucsStmt = $pdo->prepare(
                                            "SELECT COUNT(*) FROM mentorship_reports
                                             WHERE request_id = :request_id AND reporter_student_id = :reporter_student_id"
                                        );
                                        $ucsStmt->execute([
                                            ':request_id'         => (int) $ucsRequest['id'],
                                            ':reporter_student_id' => (int) $ucsUser['id'],
                                        ]);
                                        $ucsReportSent = (int) $ucsStmt->fetchColumn() > 0;
                                    } catch (PDOException $e) {
                                        $ucsReportSent = false;
                                    }
                                    ?>
                                    <?php if ($ucsReportSent): ?>
                                        <p class="mt-4 text-xs font-medium text-gray-400">You have reported this mentorship request.</p>
                                    <?php else: ?>
                                        <details class="mt-4 border-t border-gray-100 pt-4">
                                            <summary class="cursor-pointer select-none text-xs font-semibold text-red-600 hover:text-red-700">
                                                Report this mentorship request
                                            </summary>
                                            <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/mentorship/report.php'); ?>" class="mt-4 space-y-4 rounded-xl bg-red-50/60 p-4 ring-1 ring-red-100">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                                                <input type="hidden" name="request_id" value="<?php echo (int) $ucsRequest['id']; ?>">

                                                <?php if (!empty($ucsReportErrors) && $ucsShowReportForm): ?>
                                                    <ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
                                                        <?php foreach ($ucsReportErrors as $ucsReportError): ?>
                                                            <li><?php echo htmlspecialchars($ucsReportError); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>

                                                <div>
                                                    <label for="reason-<?php echo (int) $ucsRequest['id']; ?>" class="block text-xs font-semibold text-gray-700">Reason</label>
                                                    <select id="reason-<?php echo (int) $ucsRequest['id']; ?>" name="reason" required
                                                            class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                                        <option value="">Select a reason…</option>
                                                        <?php foreach (MENTORSHIP_REPORT_REASONS as $ucsReason): ?>
                                                            <option value="<?php echo htmlspecialchars($ucsReason); ?>" <?php echo $ucsShowReportForm && ($ucsReportOld['reason'] ?? '') === $ucsReason ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($ucsReason); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="details-<?php echo (int) $ucsRequest['id']; ?>" class="block text-xs font-semibold text-gray-700">Additional details <span class="font-normal text-gray-400">(optional)</span></label>
                                                    <textarea id="details-<?php echo (int) $ucsRequest['id']; ?>" name="details" rows="3" maxlength="1000"
                                                              class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"><?php echo htmlspecialchars($ucsShowReportForm ? (string) ($ucsReportOld['details'] ?? '') : ''); ?></textarea>
                                                </div>

                                                <button type="submit"
                                                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-red-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                                    Submit Report
                                                </button>
                                            </form>
                                        </details>
                                    <?php endif; ?>
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