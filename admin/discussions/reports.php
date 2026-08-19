<?php
/**
 * Admin Career Discussions - Reports moderation.
 *
 * Students and alumni report inappropriate discussions and replies. Admins
 * review each report, hide the offending content, and resolve or dismiss
 * the report.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

$pageTitle    = 'Discussion Reports';
$pageSubtitle = 'Review reports of inappropriate content in the career community.';
$activeNav    = 'discussion-reports';

$ucsFlash = $_SESSION['discussion_flash'] ?? null;
unset($_SESSION['discussion_flash']);

$ucsStatus = in_array((string) ($_GET['status'] ?? ''), ['open', 'resolved', 'dismissed'], true) ? (string) $_GET['status'] : 'open';

$ucsWhere  = ['rp.status = :status'];
$ucsParams = [':status' => $ucsStatus];
$ucsWhereSql = ' WHERE ' . implode(' AND ', $ucsWhere);

$ucsReports = [];
$ucsCounts  = ['open' => 0, 'resolved' => 0, 'dismissed' => 0];
try {
    foreach (['open', 'resolved', 'dismissed'] as $ucsCountStatus) {
        $ucsCountStmt = $pdo->prepare("SELECT COUNT(*) FROM discussion_reports WHERE status = :status");
        $ucsCountStmt->execute([':status' => $ucsCountStatus]);
        $ucsCounts[$ucsCountStatus] = (int) $ucsCountStmt->fetchColumn();
    }

    $ucsStmt = $pdo->prepare(
        "SELECT rp.id, rp.content_type, rp.content_id, rp.reason, rp.details,
                rp.status, rp.created_at, rp.resolved_at,
                s.name AS reporter_name,
                rp2.content AS reply_content, rp2.status AS reply_status,
                rs.name AS reply_author_name,
                d.id AS discussion_id, d.title AS discussion_title,
                d.status AS discussion_status,
                ds.name AS discussion_author_name,
                a.name AS resolver_name
         FROM discussion_reports rp
         JOIN students s ON s.id = rp.reporter_student_id
         LEFT JOIN discussion_replies rp2
                ON rp.content_type = 'reply' AND rp2.id = rp.content_id
         LEFT JOIN students rs ON rs.id = rp2.author_student_id
         LEFT JOIN discussions d
                ON (rp.content_type = 'discussion' AND d.id = rp.content_id)
                OR (rp.content_type = 'reply' AND d.id = rp2.discussion_id)
         LEFT JOIN students ds ON ds.id = d.author_student_id
         LEFT JOIN admins a ON a.id = rp.resolved_by_admin_id"
        . $ucsWhereSql
        . " ORDER BY rp.created_at DESC"
    );
    $ucsStmt->execute($ucsParams);
    $ucsReports = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsReports = [];
}

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

<!-- Status tabs -->
<div class="mb-4 flex flex-wrap items-center gap-2">
    <?php
    $ucsReportTabs = [
        'open'      => ['Open', $ucsCounts['open']],
        'resolved'  => ['Resolved', $ucsCounts['resolved']],
        'dismissed' => ['Dismissed', $ucsCounts['dismissed']],
    ];
    ?>
    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/reports.php'); ?>"
       class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition-colors duration-150 <?php echo $ucsStatus === 'open' ? 'border-blue-600 bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
        Open
        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-bold <?php echo $ucsStatus === 'open' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'; ?>"><?php echo $ucsCounts['open']; ?></span>
    </a>
    <?php foreach (['resolved' => 'Resolved', 'dismissed' => 'Dismissed'] as $ucsTabKey => $ucsTabLabel): ?>
        <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/reports.php?status=' . $ucsTabKey); ?>"
           class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition-colors duration-150 <?php echo $ucsStatus === $ucsTabKey ? 'border-blue-600 bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
            <?php echo $ucsTabLabel; ?>
            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-bold <?php echo $ucsStatus === $ucsTabKey ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'; ?>"><?php echo $ucsCounts[$ucsTabKey]; ?></span>
        </a>
    <?php endforeach; ?>
    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/index.php'); ?>" class="ml-auto inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
        Back to Discussions
    </a>
</div>

<?php if (empty($ucsReports)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
            <line x1="4" y1="22" x2="4" y2="15"></line>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">No <?php echo htmlspecialchars($ucsStatus); ?> reports</h3>
        <p class="mt-2 text-sm text-gray-500">
            <?php echo $ucsStatus === 'open' ? 'Reported content will appear here for review.' : 'There are no reports with this status.'; ?>
        </p>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($ucsReports as $ucsReport): ?>
            <?php
            $ucsReportIsOpen   = (string) $ucsReport['status'] === 'open';
            $ucsContentType    = (string) $ucsReport['content_type'];
            $ucsDiscussionUrl  = BASE_URL . '/career-discussion-details.php?id=' . (int) $ucsReport['discussion_id'];
            $ucsDiscussionStatus = (string) ($ucsReport['discussion_status'] ?? '');
            $ucsReplyStatus    = (string) ($ucsReport['reply_status'] ?? '');
            ?>
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full <?php echo $ucsContentType === 'discussion' ? 'bg-blue-50 text-blue-700 ring-blue-100' : 'bg-purple-50 text-purple-700 ring-purple-100'; ?> px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide ring-1">
                            <?php echo $ucsContentType === 'discussion' ? 'Discussion' : 'Reply'; ?>
                        </span>
                        <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-red-100"><?php echo htmlspecialchars($ucsReport['reason']); ?></span>
                        <?php if ($ucsReportIsOpen): ?>
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">Open</span>
                        <?php elseif ((string) $ucsReport['status'] === 'resolved'): ?>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">Resolved</span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Dismissed</span>
                        <?php endif; ?>
                        <span class="text-xs text-gray-400"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string) $ucsReport['created_at']))); ?></span>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <?php if ($ucsDiscussionStatus !== 'hidden'): ?>
                            <a href="<?php echo htmlspecialchars($ucsDiscussionUrl); ?>" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-blue-600 transition-colors duration-150 hover:bg-blue-50">View Discussion</a>
                        <?php endif; ?>
                        <?php if ($ucsReportIsOpen): ?>
                            <?php if ($ucsContentType === 'discussion'): ?>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-status.php'); ?>" class="inline-flex">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsReport['discussion_id']; ?>">
                                    <?php if ($ucsDiscussionStatus === 'hidden'): ?>
                                        <input type="hidden" name="action" value="open">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-emerald-600 transition-colors duration-150 hover:bg-emerald-50">Restore</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="hidden">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-amber-600 transition-colors duration-150 hover:bg-amber-50">Hide Discussion</button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-reply-moderation.php'); ?>" class="inline-flex">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $ucsReport['content_id']; ?>">
                                    <?php if ($ucsReplyStatus === 'hidden'): ?>
                                        <input type="hidden" name="action" value="visible">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-emerald-600 transition-colors duration-150 hover:bg-emerald-50">Restore Reply</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="hidden">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-amber-600 transition-colors duration-150 hover:bg-amber-50">Hide Reply</button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-report-resolve.php'); ?>" class="inline-flex">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                <input type="hidden" name="id" value="<?php echo (int) $ucsReport['id']; ?>">
                                <input type="hidden" name="action" value="resolve">
                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-green-700 transition-colors duration-150 hover:bg-green-50">Resolve</button>
                            </form>
                            <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-report-resolve.php'); ?>" class="inline-flex">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                <input type="hidden" name="id" value="<?php echo (int) $ucsReport['id']; ?>">
                                <input type="hidden" name="action" value="dismiss">
                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-gray-600 transition-colors duration-150 hover:bg-gray-100">Dismiss</button>
                            </form>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">
                                <?php if ((string) $ucsReport['status'] === 'resolved'): ?>Resolved<?php else: ?>Dismissed<?php endif; ?>
                                <?php if (!empty($ucsReport['resolver_name'])): ?> by <?php echo htmlspecialchars($ucsReport['resolver_name']); ?><?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reported content preview -->
                <div class="mt-4 rounded-xl bg-gray-50 p-4 ring-1 ring-gray-100">
                    <?php if ($ucsContentType === 'discussion'): ?>
                        <p class="text-sm font-semibold text-gray-800">
                            <?php echo htmlspecialchars((string) ($ucsReport['discussion_title'] ?? '')); ?>
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            by <?php echo htmlspecialchars((string) ($ucsReport['discussion_author_name'] ?? 'Unknown')); ?>
                            <?php if ($ucsDiscussionStatus === 'hidden'): ?> <span class="font-semibold text-amber-600">(hidden)</span><?php endif; ?>
                        </p>
                    <?php else: ?>
                        <p class="text-sm leading-6 text-gray-700">
                            <?php
                            $ucsReplyText = trim((string) ($ucsReport['reply_content'] ?? ''));
                            echo htmlspecialchars($ucsReplyText !== '' ? (strlen($ucsReplyText) > 200 ? substr($ucsReplyText, 0, 200) . '…' : $ucsReplyText) : '');
                            ?>
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            by <?php echo htmlspecialchars((string) ($ucsReport['reply_author_name'] ?? 'Unknown')); ?>
                            &middot; in &ldquo;<?php echo htmlspecialchars((string) ($ucsReport['discussion_title'] ?? '')); ?>&rdquo;
                            <?php if ($ucsReplyStatus === 'hidden'): ?> <span class="font-semibold text-amber-600">(hidden)</span><?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-gray-500">
                    <span>Reported by <span class="font-semibold text-gray-700"><?php echo htmlspecialchars((string) $ucsReport['reporter_name']); ?></span></span>
                    <?php if (!empty($ucsReport['details'])): ?>
                        <span class="inline-flex items-start gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            &ldquo;<?php echo htmlspecialchars((string) $ucsReport['details']); ?>&rdquo;
                        </span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>