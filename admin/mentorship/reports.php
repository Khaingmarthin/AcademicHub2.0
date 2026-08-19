<?php
/**
 * Admin Alumni Mentorship - Reports moderation.
 *
 * Students report mentorship requests they have issues with. Admins review
 * each report, decide whether to resolve or dismiss it, and can suspend the
 * involved mentor's mentorship access directly from here.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/mentorship-validation.php';

admin_require_login();

$pageTitle    = 'Mentorship Reports';
$pageSubtitle = 'Review reports about mentorship requests.';
$activeNav    = 'mentorship';

$ucsFlash = $_SESSION['mentorship_flash'] ?? null;
unset($_SESSION['mentorship_flash']);

$ucsStatus = in_array((string) ($_GET['status'] ?? ''), MENTORSHIP_REPORT_STATUSES, true) ? (string) $_GET['status'] : 'open';

$ucsWhereSql = ' WHERE rp.status = :status';
$ucsParams   = [':status' => $ucsStatus];

$ucsReports = [];
$ucsCounts  = ['open' => 0, 'resolved' => 0, 'dismissed' => 0];
try {
    foreach (MENTORSHIP_REPORT_STATUSES as $ucsCountStatus) {
        $ucsCountStmt = $pdo->prepare("SELECT COUNT(*) FROM mentorship_reports WHERE status = :status");
        $ucsCountStmt->execute([':status' => $ucsCountStatus]);
        $ucsCounts[$ucsCountStatus] = (int) $ucsCountStmt->fetchColumn();
    }

    $ucsStmt = $pdo->prepare(
        "SELECT rp.id, rp.reason, rp.details, rp.status, rp.created_at,
                rp.resolved_at,
                s.name AS reporter_name,
                mr.id AS request_id, mr.message AS request_message,
                ms.name AS mentor_name, ap.id AS alumni_profile_id,
                ap.mentorship_suspended,
                a.name AS resolver_name
         FROM mentorship_reports rp
         JOIN students s ON s.id = rp.reporter_student_id
         JOIN mentorship_requests mr ON mr.id = rp.request_id
         JOIN alumni_profiles ap ON ap.id = mr.alumni_profile_id
         JOIN students ms ON ms.id = ap.student_id
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
    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/reports.php'); ?>"
       class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition-colors duration-150 <?php echo $ucsStatus === 'open' ? 'border-blue-600 bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
        Open
        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-bold <?php echo $ucsStatus === 'open' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'; ?>"><?php echo $ucsCounts['open']; ?></span>
    </a>
    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/reports.php?status=resolved'); ?>"
       class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition-colors duration-150 <?php echo $ucsStatus === 'resolved' ? 'border-blue-600 bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
        Resolved
        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-bold <?php echo $ucsStatus === 'resolved' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'; ?>"><?php echo $ucsCounts['resolved']; ?></span>
    </a>
    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/mentorship/reports.php?status=dismissed'); ?>"
       class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition-colors duration-150 <?php echo $ucsStatus === 'dismissed' ? 'border-blue-600 bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
        Dismissed
        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-bold <?php echo $ucsStatus === 'dismissed' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'; ?>"><?php echo $ucsCounts['dismissed']; ?></span>
    </a>
</div>

<?php if (empty($ucsReports)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
            <line x1="4" y1="22" x2="4" y2="15"></line>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">No <?php echo strtolower($ucsStatus); ?> reports</h3>
        <p class="mt-2 text-sm text-gray-500">There are no <?php echo strtolower($ucsStatus); ?> mentorship reports to review.</p>
    </div>
<?php else: ?>
    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <table class="w-full min-w-[950px] text-left text-sm">
            <thead class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-5 py-3.5">Report</th>
                    <th class="px-4 py-3.5">Reporter</th>
                    <th class="px-4 py-3.5">Mentor</th>
                    <th class="px-4 py-3.5">Status</th>
                    <th class="px-4 py-3.5">Created</th>
                    <th class="px-4 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($ucsReports as $ucsReport): ?>
                    <?php
                    $ucsMessageExcerpt = trim((string) strip_tags((string) $ucsReport['request_message']));
                    if (mb_strlen($ucsMessageExcerpt) > 80) {
                        $ucsMessageExcerpt = mb_substr($ucsMessageExcerpt, 0, 80) . '…';
                    }
                    $ucsSuspended   = (int) $ucsReport['mentorship_suspended'] === 1;
                    $ucsJsName      = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $ucsReport['mentor_name']);
                    ?>
                    <tr class="align-top transition-colors hover:bg-gray-50/60">
                        <td class="max-w-xs px-5 py-4">
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars((string) $ucsReport['reason']); ?></p>
                            <p class="mt-1 text-xs leading-5 text-gray-500">Request #<?php echo (int) $ucsReport['request_id']; ?></p>
                            <?php if (!empty($ucsReport['details'])): ?>
                                <p class="mt-1 text-xs leading-5 text-gray-600"><?php echo nl2br(htmlspecialchars((string) $ucsReport['details'])); ?></p>
                            <?php endif; ?>
                            <?php if ($ucsMessageExcerpt !== ''): ?>
                                <p class="mt-2 rounded-lg bg-gray-50 px-2.5 py-1.5 text-[11px] leading-5 text-gray-500 ring-1 ring-gray-100"><?php echo htmlspecialchars($ucsMessageExcerpt); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-700"><?php echo htmlspecialchars((string) $ucsReport['reporter_name']); ?></p>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-700"><?php echo htmlspecialchars((string) $ucsReport['mentor_name']); ?></p>
                            <?php if ($ucsSuspended): ?>
                                <span class="mt-1 inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700 ring-1 ring-red-100">Suspended</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <?php if ($ucsReport['status'] === 'open'): ?>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">Open</span>
                            <?php elseif ($ucsReport['status'] === 'resolved'): ?>
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">Resolved</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Dismissed</span>
                            <?php endif; ?>
                            <?php if (!empty($ucsReport['resolver_name'])): ?>
                                <p class="mt-1 text-[11px] text-gray-400">by <?php echo htmlspecialchars((string) $ucsReport['resolver_name']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-xs text-gray-500">
                            <?php echo htmlspecialchars(date('M j, Y', strtotime((string) $ucsReport['created_at']))); ?>
                        </td>
                        <td class="px-4 py-4">
                            <?php if ($ucsReport['status'] === 'open'): ?>
                                <div class="flex flex-wrap items-center justify-end gap-1">
                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/mentorship-report-resolve.php'); ?>" class="inline-flex">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsReport['id']; ?>">
                                        <input type="hidden" name="action" value="resolved">
                                        <button type="submit" title="Mark as resolved"
                                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-emerald-600 transition-colors duration-150 hover:bg-emerald-50">
                                            Resolve
                                        </button>
                                    </form>
                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/mentorship-report-resolve.php'); ?>" class="inline-flex">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsReport['id']; ?>">
                                        <input type="hidden" name="action" value="dismissed">
                                        <button type="submit" title="Dismiss this report"
                                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-gray-600 transition-colors duration-150 hover:bg-gray-100">
                                            Dismiss
                                        </button>
                                    </form>
                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/mentorship-suspend.php'); ?>" class="inline-flex"
                                          <?php if (!$ucsSuspended): ?>onsubmit="return confirm('Suspend mentorship for &quot;<?php echo htmlspecialchars($ucsJsName); ?>&quot;? Pending requests will be declined and open reports resolved.');"<?php endif; ?>>
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsReport['alumni_profile_id']; ?>">
                                        <input type="hidden" name="suspended" value="<?php echo $ucsSuspended ? '0' : '1'; ?>">
                                        <input type="hidden" name="from_reports" value="1">
                                        <button type="submit" title="<?php echo $ucsSuspended ? 'Restore mentorship access' : 'Suspend mentorship access'; ?>"
                                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold <?php echo $ucsSuspended ? 'text-emerald-600 transition-colors duration-150 hover:bg-emerald-50' : 'text-red-600 transition-colors duration-150 hover:bg-red-50'; ?>">
                                            <?php echo $ucsSuspended ? 'Restore' : 'Suspend Mentor'; ?>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>