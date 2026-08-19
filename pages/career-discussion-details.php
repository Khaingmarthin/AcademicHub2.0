<?php
/**
 * Public Career Discussion Details page.
 *
 * Shows a single discussion (selected by id) with its replies. Hidden
 * discussions are treated as not found. Logged-in students and alumni can
 * reply (unless the discussion is closed) and can report inappropriate
 * discussions or replies for moderation.
 */
require_once '../config/app.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/helpers/discussion-validation.php';

$pageTitle = 'Career Discussion';

$ucsUser = student_current_user();

$ucsDiscussionId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

$ucsDiscussion = null;
$ucsReplies    = [];
$ucsHeroMedia  = 'images/front_view.jpg';

if ($ucsDiscussionId !== false && $ucsDiscussionId > 0) {
    try {
        $ucsProfileStmt = $pdo->query(
            "SELECT hero_media
             FROM university_profile
             ORDER BY id ASC
             LIMIT 1"
        );
        $ucsProfileRow = $ucsProfileStmt->fetch() ?: null;
        $ucsHeroMedia  = $ucsProfileRow['hero_media'] ?? 'images/front_view.jpg';

        $ucsStmt = $pdo->prepare(
            "SELECT d.id, d.title, d.content, d.category_id, d.status,
                    d.is_pinned, d.created_at,
                    c.name AS category_name, c.slug AS category_slug,
                    s.name AS author_name,
                    ap.id AS alumni_profile_id,
                    (ap.id IS NOT NULL) AS is_alumni
             FROM discussions d
             JOIN discussion_categories c ON c.id = d.category_id
             JOIN students s ON s.id = d.author_student_id
             LEFT JOIN alumni_profiles ap
                    ON ap.student_id = s.id AND ap.verification_status = 'verified'
             WHERE d.id = :id AND d.status <> 'hidden'
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $ucsDiscussionId]);
        $ucsDiscussion = $ucsStmt->fetch() ?: null;

        if ($ucsDiscussion !== null) {
            $ucsReplyStmt = $pdo->prepare(
                "SELECT r.id, r.content, r.created_at, r.author_student_id,
                        s.name AS author_name,
                        (ap.id IS NOT NULL) AS is_alumni
                 FROM discussion_replies r
                 JOIN students s ON s.id = r.author_student_id
                 LEFT JOIN alumni_profiles ap
                        ON ap.student_id = s.id AND ap.verification_status = 'verified'
                 WHERE r.discussion_id = :discussion_id AND r.status = 'visible'
                 ORDER BY r.created_at ASC, r.id ASC"
            );
            $ucsReplyStmt->execute([':discussion_id' => $ucsDiscussionId]);
            $ucsReplies = $ucsReplyStmt->fetchAll() ?: [];
        }
    } catch (PDOException $e) {
        $ucsDiscussion = null;
    }
}

if ($ucsDiscussion !== null) {
    $pageTitle = (string) $ucsDiscussion['title'];
}

$ucsFlash = $_SESSION['discussion_flash'] ?? null;
unset($_SESSION['discussion_flash']);

$ucsReplyErrors = $_SESSION['discussion_reply_errors'] ?? [];
unset($_SESSION['discussion_reply_errors']);
$ucsReplyOld = $_SESSION['discussion_reply_old'] ?? '';
unset($_SESSION['discussion_reply_old']);

if (!preg_match('~^https?://~i', $ucsHeroMedia)) {
    $ucsHeroMedia = ROOT_URL . '/assets/' . ltrim($ucsHeroMedia, '/');
}

require_once '../includes/header.php';
?>
<main class="flex-1">
    <?php if ($ucsDiscussion === null): ?>
        <!-- Not found -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="career-discussion-not-found-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Career Discussions</p>
                <h1 id="career-discussion-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Discussion Not Found</h1>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    The requested discussion could not be found or is no longer available.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Browse Career Discussions
                </a>
            </div>
        </section>
    <?php else: ?>
        <!-- Page hero -->
        <section class="relative overflow-hidden bg-gray-900" aria-labelledby="career-discussion-details-heading">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($ucsHeroMedia); ?>');" aria-hidden="true"></div>
            <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/25">
                        <?php echo htmlspecialchars((string) $ucsDiscussion['category_name']); ?>
                    </span>
                    <?php if ((int) $ucsDiscussion['is_pinned'] === 1): ?>
                        <span class="inline-flex items-center rounded-full bg-indigo-500/20 px-3 py-1 text-xs font-semibold text-indigo-200 ring-1 ring-indigo-300/30">Pinned</span>
                    <?php endif; ?>
                    <?php if ((string) $ucsDiscussion['status'] === 'closed'): ?>
                        <span class="inline-flex items-center rounded-full bg-gray-500/20 px-3 py-1 text-xs font-semibold text-gray-200 ring-1 ring-gray-300/30">Closed</span>
                    <?php endif; ?>
                </div>
                <h1 id="career-discussion-details-heading" class="mx-auto mt-4 max-w-3xl text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    <?php echo htmlspecialchars((string) $ucsDiscussion['title']); ?>
                </h1>
                <p class="mt-5 inline-flex items-center gap-2 text-sm font-medium text-gray-200">
                    <?php
                    $ucsDiscussionAuthorProfileUrl = null;
                    if ((int) $ucsDiscussion['is_alumni'] === 1 && !empty($ucsDiscussion['alumni_profile_id'])) {
                        $ucsDiscussionAuthorProfileUrl = BASE_URL . '/alumni-details.php?id=' . (int) $ucsDiscussion['alumni_profile_id'];
                    }
                    ?>
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white text-xs font-bold text-blue-700" aria-hidden="true">
                        <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsDiscussion['author_name']), 0, 1))); ?>
                    </span>
                    <?php if ($ucsDiscussionAuthorProfileUrl !== null): ?>
                        <a href="<?php echo htmlspecialchars($ucsDiscussionAuthorProfileUrl); ?>" class="transition-colors duration-150 hover:text-white focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            <?php echo htmlspecialchars((string) $ucsDiscussion['author_name']); ?>
                        </a>
                    <?php else: ?>
                        <?php echo htmlspecialchars((string) $ucsDiscussion['author_name']); ?>
                    <?php endif; ?>
                    <?php if ((int) $ucsDiscussion['is_alumni'] === 1): ?>
                        <span class="inline-flex items-center rounded-full bg-emerald-500/20 px-2 py-0.5 text-[11px] font-bold text-emerald-200 ring-1 ring-emerald-300/30">Alumni</span>
                    <?php endif; ?>
                    &middot;
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                        <?php echo htmlspecialchars(date('F j, Y', strtotime((string) $ucsDiscussion['created_at']))); ?>
                    </span>
                </p>
            </div>
        </section>

        <!-- Body + replies -->
        <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="career-discussion-replies-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <?php if ($ucsFlash !== null): ?>
                    <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100 text-red-700' : 'bg-emerald-50 ring-emerald-100 text-emerald-700'; ?> mb-6 rounded-xl px-4 py-3 ring-1" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
                        <p class="text-sm font-medium"><?php echo htmlspecialchars($ucsFlash['message']); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Original question -->
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-base font-extrabold text-white" aria-hidden="true">
                            <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsDiscussion['author_name']), 0, 1))); ?>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-bold text-gray-900"><?php echo htmlspecialchars((string) $ucsDiscussion['author_name']); ?></span>
                                <?php if ((int) $ucsDiscussion['is_alumni'] === 1): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-100">Verified Alumni</span>
                                <?php endif; ?>
                                <span class="text-gray-400">&middot;</span>
                                <span class="text-gray-500"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string) $ucsDiscussion['created_at']))); ?></span>
                            </div>
                            <div class="mt-4 space-y-4 text-base leading-7 text-gray-600">
                                <?php echo nl2br(htmlspecialchars((string) $ucsDiscussion['content'])); ?>
                            </div>
                            <?php if ($ucsUser !== null): ?>
                                <div class="mt-4 border-t border-gray-100 pt-4">
                                    <details class="group">
                                        <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-xs font-semibold text-gray-500 transition-colors duration-150 hover:text-red-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                                <line x1="4" y1="22" x2="4" y2="15"></line>
                                            </svg>
                                            Report this discussion
                                        </summary>
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/discussion/report.php'); ?>" class="mt-3 space-y-3 rounded-xl bg-gray-50 p-4 ring-1 ring-gray-100">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                                            <input type="hidden" name="content_type" value="discussion">
                                            <input type="hidden" name="content_id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                                            <label for="report-reason-<?php echo (int) $ucsDiscussion['id']; ?>" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Reason</label>
                                            <select id="report-reason-<?php echo (int) $ucsDiscussion['id']; ?>" name="reason" required
                                                    class="mt-1 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                                <?php foreach (DISCUSSION_REPORT_REASONS as $ucsReason): ?>
                                                    <option value="<?php echo htmlspecialchars($ucsReason); ?>"><?php echo htmlspecialchars($ucsReason); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="report-details-<?php echo (int) $ucsDiscussion['id']; ?>" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Additional details (optional)</label>
                                            <textarea id="report-details-<?php echo (int) $ucsDiscussion['id']; ?>" name="details" rows="2" maxlength="1000" placeholder="Anything the moderators should know?"
                                                      class="mt-1 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"></textarea>
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-red-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                                Submit Report
                                            </button>
                                        </form>
                                    </details>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>

                <!-- Replies -->
                <div id="replies" class="mt-10">
                    <h2 id="career-discussion-replies-heading" class="scroll-mt-24 text-xl font-extrabold tracking-tight text-gray-900 sm:text-2xl">
                        <?php echo count($ucsReplies); ?>
                        <?php echo count($ucsReplies) === 1 ? 'Reply' : 'Replies'; ?>
                    </h2>

                    <?php if (empty($ucsReplies)): ?>
                        <p class="mt-4 text-sm leading-6 text-gray-500">
                            No replies yet. Be the first to share your experience.
                        </p>
                    <?php else: ?>
                        <ul class="mt-6 space-y-5">
                            <?php foreach ($ucsReplies as $ucsReply): ?>
                                <li id="reply-<?php echo (int) $ucsReply['id']; ?>" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:p-6">
                                    <div class="flex items-start gap-4">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl <?php echo (int) $ucsReply['is_alumni'] === 1 ? 'bg-emerald-600' : 'bg-gray-700'; ?> text-sm font-extrabold text-white" aria-hidden="true">
                                            <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsReply['author_name']), 0, 1))); ?>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                                <span class="font-bold text-gray-900"><?php echo htmlspecialchars((string) $ucsReply['author_name']); ?></span>
                                                <?php if ((int) $ucsReply['is_alumni'] === 1): ?>
                                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-100">Verified Alumni</span>
                                                <?php endif; ?>
                                                <span class="text-gray-400">&middot;</span>
                                                <span class="text-xs text-gray-500"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string) $ucsReply['created_at']))); ?></span>
                                            </div>
                                            <div class="mt-3 space-y-3 text-sm leading-6 text-gray-600 sm:text-base sm:leading-7">
                                                <?php echo nl2br(htmlspecialchars((string) $ucsReply['content'])); ?>
                                            </div>
                                            <?php if ($ucsUser !== null): ?>
                                                <div class="mt-3 border-t border-gray-100 pt-3">
                                                    <details class="group">
                                                        <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-xs font-semibold text-gray-500 transition-colors duration-150 hover:text-red-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                                                <line x1="4" y1="22" x2="4" y2="15"></line>
                                                            </svg>
                                                            Report
                                                        </summary>
                                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/discussion/report.php'); ?>" class="mt-3 space-y-3 rounded-xl bg-gray-50 p-4 ring-1 ring-gray-100">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                                                            <input type="hidden" name="content_type" value="reply">
                                                            <input type="hidden" name="content_id" value="<?php echo (int) $ucsReply['id']; ?>">
                                                            <label for="report-reason-r<?php echo (int) $ucsReply['id']; ?>" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Reason</label>
                                                            <select id="report-reason-r<?php echo (int) $ucsReply['id']; ?>" name="reason" required
                                                                    class="mt-1 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                                                <?php foreach (DISCUSSION_REPORT_REASONS as $ucsReason): ?>
                                                                    <option value="<?php echo htmlspecialchars($ucsReason); ?>"><?php echo htmlspecialchars($ucsReason); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <label for="report-details-r<?php echo (int) $ucsReply['id']; ?>" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Additional details (optional)</label>
                                                            <textarea id="report-details-r<?php echo (int) $ucsReply['id']; ?>" name="details" rows="2" maxlength="1000" placeholder="Anything the moderators should know?"
                                                                      class="mt-1 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"></textarea>
                                                            <button type="submit"
                                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-red-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                                                Submit Report
                                                            </button>
                                                        </form>
                                                    </details>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Reply form -->
                <div id="reply-form" class="mt-10 scroll-mt-24">
                    <?php if ($ucsUser === null): ?>
                        <div class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-gray-200 sm:p-8">
                            <p class="text-sm font-semibold text-gray-700">Want to join the discussion?</p>
                            <p class="mt-1 text-sm leading-6 text-gray-500">Log in as a student to reply or share your experience as an alumni.</p>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/student-login.php'); ?>" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
                                Student Login
                            </a>
                        </div>
                    <?php elseif ((string) $ucsDiscussion['status'] === 'closed'): ?>
                        <div class="rounded-2xl bg-gray-100 p-6 text-center ring-1 ring-gray-200 sm:p-8">
                            <p class="text-sm font-semibold text-gray-700">This discussion has been closed</p>
                            <p class="mt-1 text-sm leading-6 text-gray-500">It is no longer accepting new replies.</p>
                        </div>
                    <?php else: ?>
                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                            <h3 class="text-lg font-extrabold tracking-tight text-gray-900">Share your experience</h3>
                            <p class="mt-1 text-sm leading-6 text-gray-500">
                                <?php echo discussion_is_verified_alumni($pdo, (int) $ucsUser['id'])
                                    ? 'Answer the question with advice from your own experience.'
                                    : 'Reply with your thoughts, questions or what worked for you.'; ?>
                            </p>

                            <?php if (!empty($ucsReplyErrors)): ?>
                                <div class="mt-5 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
                                    <ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
                                        <?php foreach ($ucsReplyErrors as $ucsError): ?>
                                            <li><?php echo htmlspecialchars($ucsError); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/discussion/reply.php'); ?>" class="mt-5 space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(student_csrf_token()); ?>">
                                <input type="hidden" name="discussion_id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                                <label for="reply-content" class="sr-only">Your reply</label>
                                <textarea id="reply-content" name="content" rows="5" required placeholder="Write your reply…"
                                          class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars((string) $ucsReplyOld); ?></textarea>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs text-gray-400">Be respectful and stay on topic.</p>
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                        </svg>
                                        Post Reply
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-10 text-center">
                    <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="group inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-150 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150 group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"></path>
                        </svg>
                        Back to Career Discussions
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php
require_once '../includes/footer.php';
?>