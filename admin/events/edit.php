<?php
/**
 * Admin Alumni Events module - edit form.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-event-validation.php';

admin_require_login();

$pageTitle    = 'Edit Alumni Event';
$pageSubtitle = 'Update an event for the Alumni & Career Community.';
$activeNav    = 'alumni-events';

$ucsEventId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($ucsEventId === false) {
    $ucsEventId = null;
}

$ucsEvent = null;
if ($ucsEventId !== null) {
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT e.*, a.name AS admin_name
             FROM alumni_events e
             JOIN admins a ON a.id = e.admin_id
             WHERE e.id = :id
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $ucsEventId]);
        $ucsEvent = $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        $ucsEvent = null;
    }
}

if ($ucsEvent === null) {
    header('Location: ' . ROOT_URL . '/admin/events/index.php');
    exit;
}

$ucsErrors = $_SESSION['alumni_event_errors'] ?? [];
unset($_SESSION['alumni_event_errors']);

$ucsOld = $_SESSION['alumni_event_old'] ?? null;
unset($_SESSION['alumni_event_old']);

$ucsForm = [
    'title'            => (string) ($ucsOld['title'] ?? $ucsEvent['title']),
    'description'      => (string) ($ucsOld['description'] ?? $ucsEvent['description']),
    'event_type'       => (string) ($ucsOld['event_type'] ?? $ucsEvent['event_type']),
    'venue'            => (string) ($ucsOld['venue'] ?? $ucsEvent['venue']),
    'starts_at'        => (string) ($ucsOld['starts_at'] ?? alumni_event_format_local_input((string) $ucsEvent['starts_at'])),
    'ends_at'          => (string) ($ucsOld['ends_at'] ?? ($ucsEvent['ends_at'] !== null ? alumni_event_format_local_input((string) $ucsEvent['ends_at']) : '')),
    'registration_link' => (string) ($ucsOld['registration_link'] ?? $ucsEvent['registration_link']),
    'status'           => (string) ($ucsOld['status'] ?? $ucsEvent['status']),
];

require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<?php if (!empty($ucsErrors)): ?>
    <div class="rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
        <p class="text-sm font-semibold text-red-700">Please fix the following:</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
            <?php foreach ($ucsErrors as $ucsError): ?>
                <li><?php echo htmlspecialchars($ucsError); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="mx-auto max-w-3xl">
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-6 py-5">
            <h2 class="text-base font-semibold text-gray-900">Event Details</h2>
            <p class="mt-1 text-sm text-gray-500">
                Created by <?php echo htmlspecialchars((string) $ucsEvent['admin_name']); ?>
                on <?php echo htmlspecialchars(date('M j, Y', strtotime((string) $ucsEvent['created_at']))); ?>.
            </p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/event-update.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsEvent['id']; ?>">

            <div class="space-y-6 px-6 py-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Event Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ucsForm['title']); ?>" required maxlength="255"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="event_type" class="block text-sm font-medium text-gray-700">Event Type <span class="text-red-500">*</span></label>
                        <select id="event_type" name="event_type" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <?php foreach (ALUMNI_EVENT_TYPES as $ucsType): ?>
                                <option value="<?php echo $ucsType; ?>" <?php echo $ucsForm['event_type'] === $ucsType ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(alumni_event_label($ucsType)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="published" <?php echo $ucsForm['status'] === 'published' ? 'selected' : ''; ?>>Published (visible publicly)</option>
                            <option value="cancelled" <?php echo $ucsForm['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled (shown as cancelled)</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="starts_at" class="block text-sm font-medium text-gray-700">Starts At <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="starts_at" name="starts_at" value="<?php echo htmlspecialchars($ucsForm['starts_at']); ?>" required
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="ends_at" class="block text-sm font-medium text-gray-700">Ends At <span class="text-gray-400">(optional)</span></label>
                        <input type="datetime-local" id="ends_at" name="ends_at" value="<?php echo htmlspecialchars($ucsForm['ends_at']); ?>"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div>
                    <label for="venue" class="block text-sm font-medium text-gray-700">Venue / Location <span class="text-gray-400">(optional)</span></label>
                    <input type="text" id="venue" name="venue" value="<?php echo htmlspecialchars($ucsForm['venue']); ?>" maxlength="255"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="6" required
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['description']); ?></textarea>
                </div>

                <div>
                    <label for="registration_link" class="block text-sm font-medium text-gray-700">Registration Link <span class="text-gray-400">(optional)</span></label>
                    <input type="url" id="registration_link" name="registration_link" value="<?php echo htmlspecialchars($ucsForm['registration_link']); ?>" maxlength="255"
                           placeholder="https://example.com/register"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/events/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>