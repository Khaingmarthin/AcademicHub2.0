<?php
/**
 * Admin Career Discussions module - view / moderate a single discussion.
 *
 * Shows the full discussion content and all replies (including hidden ones)
 * with per-reply moderation. The admin can change the discussion status,
 * pin/unpin, hide/restore, delete, and moderate individual replies.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/discussion-validation.php';

admin_require_login();

$ucsDiscussionId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

$ucsDiscussion = null;
$ucsReplies    = [];

if ($ucsDiscussionId !== false && $ucsDiscussionId > 0) {
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT d.id, d.title, d.content, d.category_id, d.status,
                    d.is_pinned, d.created_at,
                    c.name AS category_name,
                    s.name AS author_name, s.id AS author_student_id,
                    (ap.id IS NOT NULL) AS is_alumni
             FROM discussions d
             JOIN discussion_categories c ON c.id = d.category_id
             JOIN students s ON s.id = d.author_student_id
             LEFT JOIN alumni_profiles ap
                    ON ap.student_id = s.id AND ap.verification_status = 'verified'
             WHERE d.id = :id
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $ucsDiscussionId]);
        $ucsDiscussion = $ucsStmt->fetch() ?: null;

        if ($ucsDiscussion !== null) {
            $ucsReplyStmt = $pdo->prepare(
                "SELECT r.id, r.content, r.status AS reply_status, r.created_at,
                        r.author_student_id,
                        s.name AS author_name,
                        (ap.id IS NOT NULL) AS is_alumni
                 FROM discussion_replies r
                 JOIN students s ON s.id = r.author_student_id
                 LEFT JOIN alumni_profiles ap
                        ON ap.student_id = s.id AND ap.verification_status = 'verified'
                 WHERE r.discussion_id = :discussion_id
                 ORDER BY r.created_at ASC, r.id ASC"
            );
            $ucsReplyStmt->execute([':discussion_id' => $ucsDiscussionId]);
            $ucsReplies = $ucsReplyStmt->fetchAll() ?: [];
        }
    } catch (PDOException $e) {
        $ucsDiscussion = null;
    }
}

if ($ucsDiscussion === null || $ucsDiscussionId === false || $ucsDiscussionId < 1) {
    discussion_flash('error', 'Discussion not found.');
    header('Location: ' . ROOT_URL . '/admin/discussions/index.php');
    exit;
}

$ucsFlash = $_SESSION['discussion_flash'] ?? null;
unset($_SESSION['discussion_flash']);

$ucsDiscussionTitle = (string) $ucsDiscussion['title'];
$ucsJsName          = str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsDiscussionTitle);
$ucsDiscussionStatus = (string) $ucsDiscussion['status'];
$ucsIsPinned        = (int) $ucsDiscussion['is_pinned'] === 1;
$ucsIsOpen          = $ucsDiscussionStatus === 'open';
$ucsIsClosed        = $ucsDiscussionStatus === 'closed';
$ucsIsHidden        = $ucsDiscussionStatus === 'hidden';

$pageTitle    = htmlspecialchars($ucsDiscussionTitle);
$pageSubtitle = 'View and moderate this discussion.';
$activeNav    = 'discussions';

require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<?php if ($ucsFlash !== null): ?>
    <div class="<?php echo $ucsFlash['type'] === 'error' ? 'bg-red-50 ring-red-100 text-red-700' : 'bg-blue-50 ring-blue-100 text-blue-700'; ?> rounded-xl px-4 py-3 ring-1" role="<?php echo $ucsFlash['type'] === 'error' ? 'alert' : 'status'; ?>">
        <p class="flex items-start gap-2 text-sm font-medium">
            <?php if ($ucsFlash['type'] === 'error'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <path d="m9 11 3 3L22 4"></path>
                </svg>
            <?php endif; ?>
            <?php echo htmlspecialchars($ucsFlash['message']); ?>
        </p>
    </div>
<?php endif; ?>

<!-- Back link -->
<div class="mb-4">
    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/index.php'); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-blue-600 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 18-6-6 6-6"></path>
        </svg>
        Back to Discussions
    </a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <!-- Discussion content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Discussion header -->
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                        <?php echo htmlspecialchars((string) $ucsDiscussion['category_name']); ?>
                    </span>
                    <?php if ($ucsIsPinned): ?>
                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 17v5M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1z"></path>
                            </svg>
                            Pinned
                        </span>
                    <?php endif; ?>
                    <?php if ($ucsIsClosed): ?>
                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600 ring-1 ring-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Closed
                        </span>
                    <?php endif; ?>
                    <?php if ($ucsIsHidden): ?>
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                            Hidden
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="mt-3 text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">
                    <?php echo htmlspecialchars($ucsDiscussionTitle); ?>
                </h1>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white" aria-hidden="true">
                            <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsDiscussion['author_name']), 0, 1))); ?>
                        </span>
                        <span class="font-semibold text-gray-900"><?php echo htmlspecialchars((string) $ucsDiscussion['author_name']); ?></span>
                        <?php if ((int) $ucsDiscussion['is_alumni'] === 1): ?>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">Alumni</span>
                        <?php endif; ?>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        </svg>
                        <?php echo htmlspecialchars(date('F j, Y g:i A', strtotime((string) $ucsDiscussion['created_at']))); ?>
                    </span>
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="space-y-4 text-sm leading-7 text-gray-700 sm:text-base sm:leading-8">
                    <?php echo nl2br(htmlspecialchars((string) $ucsDiscussion['content'])); ?>
                </div>
            </div>
        </div>

        <!-- Replies -->
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-base font-semibold text-gray-900">
                    Replies (<?php echo count($ucsReplies); ?>)
                </h2>
            </div>
            <?php if (empty($ucsReplies)): ?>
                <div class="px-6 py-10 text-center">
                    <p class="text-sm text-gray-500">No replies yet.</p>
                </div>
            <?php else: ?>
                <ul class="divide-y divide-gray-50">
                    <?php foreach ($ucsReplies as $ucsReply): ?>
                        <?php
                        $ucsReplyIsHidden = (string) $ucsReply['reply_status'] === 'hidden';
                        ?>
                        <li id="reply-<?php echo (int) $ucsReply['id']; ?>" class="px-6 py-5 <?php echo $ucsReplyIsHidden ? 'bg-amber-50/50' : ''; ?>">
                            <div class="flex items-start gap-4">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl <?php echo (int) $ucsReply['is_alumni'] === 1 ? 'bg-emerald-600' : 'bg-gray-700'; ?> text-sm font-bold text-white" aria-hidden="true">
                                    <?php echo htmlspecialchars(strtoupper(substr(trim((string) $ucsReply['author_name']), 0, 1))); ?>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2 text-sm">
                                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars((string) $ucsReply['author_name']); ?></span>
                                        <?php if ((int) $ucsReply['is_alumni'] === 1): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">Alumni</span>
                                        <?php endif; ?>
                                        <?php if ($ucsReplyIsHidden): ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-200">Hidden</span>
                                        <?php endif; ?>
                                        <span class="text-gray-400">&middot;</span>
                                        <span class="text-xs text-gray-500"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string) $ucsReply['created_at']))); ?></span>
                                    </div>
                                    <div class="mt-2 space-y-2 text-sm leading-6 text-gray-600 sm:text-base sm:leading-7">
                                        <?php echo nl2br(htmlspecialchars((string) $ucsReply['content'])); ?>
                                    </div>
                                    <div class="mt-3">
                                        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-reply-moderation.php'); ?>" class="inline-flex">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $ucsReply['id']; ?>">
                                            <input type="hidden" name="return_to" value="view">
                                            <input type="hidden" name="discussion_id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                                            <?php if ($ucsReplyIsHidden): ?>
                                                <input type="hidden" name="action" value="visible">
                                                <button type="submit" title="Restore this reply"
                                                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-emerald-600 transition-colors duration-150 hover:bg-emerald-50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                                                    Restore
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="hidden">
                                                <button type="submit" title="Hide this reply from the public site"
                                                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-amber-600 transition-colors duration-150 hover:bg-amber-50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                                    Hide
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar: Moderation controls -->
    <div class="space-y-6 lg:col-span-1">
        <!-- Status & Actions -->
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-base font-semibold text-gray-900">Moderation</h2>
                <p class="mt-1 text-sm text-gray-500">Manage this discussion's visibility and state.</p>
            </div>
            <div class="space-y-3 px-6 py-5">
                <!-- Pin / Unpin -->
                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-pin.php'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                    <input type="hidden" name="return_to" value="view">
                    <input type="hidden" name="discussion_id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                    <input type="hidden" name="pinned" value="<?php echo $ucsIsPinned ? '0' : '1'; ?>">
                    <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold transition-colors duration-150 <?php echo $ucsIsPinned ? 'text-indigo-700 hover:bg-indigo-50 border-indigo-200' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 17v5M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1z"></path>
                        </svg>
                        <?php echo $ucsIsPinned ? 'Unpin Discussion' : 'Pin Discussion'; ?>
                    </button>
                </form>

                <!-- Close / Reopen -->
                <?php if ($ucsIsOpen): ?>
                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-status.php'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                        <input type="hidden" name="return_to" value="view">
                        <input type="hidden" name="discussion_id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                        <input type="hidden" name="action" value="closed">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Close Discussion
                        </button>
                    </form>
                <?php elseif ($ucsIsClosed): ?>
                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-status.php'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                        <input type="hidden" name="return_to" value="view">
                        <input type="hidden" name="discussion_id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                        <input type="hidden" name="action" value="open">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 transition-colors duration-150 hover:bg-emerald-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                            </svg>
                            Reopen Discussion
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Hide / Restore -->
                <?php if ($ucsIsHidden): ?>
                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-status.php'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                        <input type="hidden" name="return_to" value="view">
                        <input type="hidden" name="discussion_id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                        <input type="hidden" name="action" value="open">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 transition-colors duration-150 hover:bg-emerald-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg>
                            Restore Discussion
                        </button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-status.php'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                        <input type="hidden" name="return_to" value="view">
                        <input type="hidden" name="discussion_id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                        <input type="hidden" name="action" value="hidden">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-amber-200 bg-white px-4 py-2.5 text-sm font-semibold text-amber-700 transition-colors duration-150 hover:bg-amber-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                            Hide from Public
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Delete -->
                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-delete.php'); ?>"
                      onsubmit="return confirm('Delete discussion &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot; and all of its replies? This cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                    <input type="hidden" name="return_to" value="view">
                    <input type="hidden" name="discussion_id" value="<?php echo (int) $ucsDiscussion['id']; ?>">
                    <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 transition-colors duration-150 hover:bg-red-50 hover:text-red-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"></path>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                        </svg>
                        Delete Discussion
                    </button>
                </form>
            </div>
        </div>

        <!-- Discussion info -->
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-base font-semibold text-gray-900">Details</h2>
            </div>
            <div class="space-y-4 px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Status</p>
                    <p class="mt-1.5">
                        <?php if ($ucsIsOpen): ?>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">Open</span>
                        <?php elseif ($ucsIsClosed): ?>
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Closed</span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">Hidden</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Category</p>
                    <p class="mt-1.5 text-sm font-medium text-gray-800"><?php echo htmlspecialchars((string) $ucsDiscussion['category_name']); ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Author</p>
                    <p class="mt-1.5 text-sm font-medium text-gray-800">
                        <?php echo htmlspecialchars((string) $ucsDiscussion['author_name']); ?>
                        <?php if ((int) $ucsDiscussion['is_alumni'] === 1): ?>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">Alumni</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Replies</p>
                    <p class="mt-1.5 text-sm font-medium text-gray-800"><?php echo count($ucsReplies); ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Created</p>
                    <p class="mt-1.5 text-sm font-medium text-gray-800"><?php echo htmlspecialchars(date('j M Y, g:i A', strtotime((string) $ucsDiscussion['created_at']))); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>
