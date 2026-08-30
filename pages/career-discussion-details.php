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

$ucsIsAlumni = $ucsUser !== null && discussion_is_verified_alumni($pdo, (int) $ucsUser['id']);

$ucsDiscussionId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

$ucsDiscussion = null;
$ucsReplies    = [];

if ($ucsDiscussionId !== false && $ucsDiscussionId > 0) {
    try {
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

require_once '../includes/header.php';
?>
<main class="flex-1">
    <!-- Breadcrumb -->
    <nav class="border-b border-slate-200 bg-white py-3" aria-label="Breadcrumb">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <ol class="flex items-center gap-1.5 text-sm text-slate-500">
                <li><a href="<?php echo htmlspecialchars(BASE_URL . '/'); ?>" class="font-medium text-slate-600 transition-colors hover:text-blue-600">Home</a></li>
                <li aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
                <li><a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="font-medium text-slate-600 transition-colors hover:text-blue-600">Career Discussions</a></li>
                <li aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
                <li aria-current="page" class="truncate font-semibold text-slate-900"><?php echo htmlspecialchars(mb_strimwidth($pageTitle, 0, 40, '…')); ?></li>
            </ol>
        </div>
    </nav>
    <?php if ($ucsDiscussion === null): ?>
        <!-- Not found -->
        <section class="bg-slate-50 py-20 sm:py-24" aria-labelledby="career-discussion-not-found-heading">
            <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Career Discussions</p>
                <h1 id="career-discussion-not-found-heading" class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Discussion Not Found</h1>
                <p class="mt-4 text-base leading-7 text-slate-600">
                    The requested discussion could not be found or is no longer available.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/career-discussions.php'); ?>" class="mt-8 inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Browse Career Discussions
                </a>
            </div>
        </section>
    <?php else: ?>
        <!-- Discussion header -->
        <section class="border-b border-slate-200 bg-white" aria-labelledby="career-discussion-details-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                        <?php echo htmlspecialchars((string) $ucsDiscussion['category_name']); ?>
                    </span>
                    <?php if ((int) $ucsDiscussion['is_pinned'] === 1): ?>
                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 17v5M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1z"></path>
                            </svg>
                            Pinned
                        </span>
                    <?php endif; ?>
                    <?php if ((string) $ucsDiscussion['status'] === 'closed'): ?>
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Closed
                        </span>
                    <?php endif; ?>
                </div>
                <h1 id="career-discussion-details-heading" class="mt-4 text-3xl font-extrabold tracking-[-0.025em] text-slate-900 sm:text-4xl lg:text-[2.5rem] leading-[1.15]">
                    <?php echo htmlspecialchars((string) $ucsDiscussion['title']); ?>
                </h1>
                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                    <?php
                    $ucsDiscussionAuthorProfileUrl = null;
                    if ((int) $ucsDiscussion['is_alumni'] === 1 && !empty($ucsDiscussion['alumni_profile_id'])) {
                        $ucsDiscussionAuthorProfileUrl = BASE_URL . '/alumni-details.php?id=' . (int) $ucsDiscussion['alumni_profile_id'];
                    }
                    ?>
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white" aria-hidden="true">
                            <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsDiscussion['author_name']), 0, 1))); ?>
                        </span>
                        <?php if ($ucsDiscussionAuthorProfileUrl !== null): ?>
                            <a href="<?php echo htmlspecialchars($ucsDiscussionAuthorProfileUrl); ?>" class="font-semibold text-slate-900 transition-colors hover:text-blue-600">
                                <?php echo htmlspecialchars((string) $ucsDiscussion['author_name']); ?>
                            </a>
                        <?php else: ?>
                            <span class="font-semibold text-slate-900"><?php echo htmlspecialchars((string) $ucsDiscussion['author_name']); ?></span>
                        <?php endif; ?>
                        <?php if ((int) $ucsDiscussion['is_alumni'] === 1): ?>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-100">Verified Alumni</span>
                        <?php endif; ?>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                        <?php echo htmlspecialchars(date('F j, Y', strtotime((string) $ucsDiscussion['created_at']))); ?>
                    </span>
                </div>
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
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-base font-extrabold text-white" aria-hidden="true">
                            <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsDiscussion['author_name']), 0, 1))); ?>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-bold text-slate-900"><?php echo htmlspecialchars((string) $ucsDiscussion['author_name']); ?></span>
                                <?php if ((int) $ucsDiscussion['is_alumni'] === 1): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-100">Verified Alumni</span>
                                <?php endif; ?>
                                <span class="text-slate-400">&middot;</span>
                                <span class="text-slate-500"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string) $ucsDiscussion['created_at']))); ?></span>
                            </div>
                            <div class="mt-4 space-y-4 text-base leading-7 text-slate-600">
                                <?php echo nl2br(htmlspecialchars((string) $ucsDiscussion['content'])); ?>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Replies -->
                <div id="replies" class="mt-10">
                    <h2 id="career-discussion-replies-heading" class="scroll-mt-24 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                        <?php echo count($ucsReplies); ?>
                        <?php echo count($ucsReplies) === 1 ? 'Reply' : 'Replies'; ?>
                    </h2>

                    <?php if (empty($ucsReplies)): ?>
                        <p class="mt-4 text-sm leading-6 text-slate-500">
                            No replies yet. Be the first to share your experience.
                        </p>
                    <?php else: ?>
                        <ul class="mt-6 space-y-5">
                            <?php foreach ($ucsReplies as $ucsReply): ?>
                                <li id="reply-<?php echo (int) $ucsReply['id']; ?>" class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                                    <div class="flex items-start gap-4">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl <?php echo (int) $ucsReply['is_alumni'] === 1 ? 'bg-emerald-600' : 'bg-slate-700'; ?> text-sm font-extrabold text-white" aria-hidden="true">
                                            <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsReply['author_name']), 0, 1))); ?>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                                <span class="font-bold text-slate-900"><?php echo htmlspecialchars((string) $ucsReply['author_name']); ?></span>
                                                <?php if ((int) $ucsReply['is_alumni'] === 1): ?>
                                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-100">Verified Alumni</span>
                                                <?php endif; ?>
                                                <span class="text-slate-400">&middot;</span>
                                                <span class="text-xs text-slate-500"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string) $ucsReply['created_at']))); ?></span>
                                            </div>
                                            <div class="mt-3 space-y-3 text-sm leading-6 text-slate-600 sm:text-base sm:leading-7">
                                                <?php echo nl2br(htmlspecialchars((string) $ucsReply['content'])); ?>
                                            </div>
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
                        <div class="rounded-lg bg-white p-6 text-center shadow-sm ring-1 ring-slate-200 sm:p-8">
                            <p class="text-sm font-semibold text-slate-700">Want to join the discussion?</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Log in as a student to reply, ask a follow-up question or share your own experience.</p>
                            <a href="<?php echo htmlspecialchars(BASE_URL . '/student-login.php'); ?>" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
                                Login
                            </a>
                        </div>
                    <?php elseif ((string) $ucsDiscussion['status'] === 'closed'): ?>
                        <div class="rounded-lg bg-slate-100 p-6 text-center ring-1 ring-slate-200 sm:p-8">
                            <p class="text-sm font-semibold text-slate-700">This discussion has been closed</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500">It is no longer accepting new replies.</p>
                        </div>
                    <?php else: ?>
                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                            <h3 class="text-lg font-extrabold tracking-tight text-slate-900">Join the conversation</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                <?php echo discussion_is_verified_alumni($pdo, (int) $ucsUser['id'])
                                    ? 'Reply with your own experience, advice or insights on this topic.'
                                    : 'Ask a follow-up question, share your thoughts or add what worked for you.'; ?>
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
                                          class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars((string) $ucsReplyOld); ?></textarea>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs text-slate-400">Be respectful and stay on topic.</p>
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

                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <?php if ($ucsIsAlumni): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/alumni-dashboard.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 12H5M12 19l-7-7 7-7"></path>
                            </svg>
                            Back to Dashboard
                        </a>
                    <?php elseif ($ucsUser !== null): ?>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 12H5M12 19l-7-7 7-7"></path>
                            </svg>
                            Back to Dashboard
                        </a>
                    <?php endif; ?>
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