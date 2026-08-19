<?php
/**
 * Admin Career Discussions - Categories list.
 */
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers/discussion-validation.php';

admin_require_login();

$pageTitle    = 'Discussion Categories';
$pageSubtitle = 'Manage the career topics used by the community.';
$activeNav    = 'discussions';

$ucsFlash = $_SESSION['discussion_flash'] ?? null;
unset($_SESSION['discussion_flash']);

$ucsCategories = [];
try {
    $ucsStmt = $pdo->query(
        "SELECT dc.id, dc.name, dc.slug, dc.description, dc.sort_order, dc.status,
                (SELECT COUNT(*) FROM discussions d WHERE d.category_id = dc.id) AS discussion_count
         FROM discussion_categories dc
         ORDER BY dc.sort_order ASC, dc.name ASC"
    );
    $ucsCategories = $ucsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $ucsCategories = [];
}

require_once __DIR__ . '/../../../includes/admin-layout-top.php';
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

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-gray-600">
        These categories appear as filters on the public Career Discussions page.
    </p>
    <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/categories/create.php'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14"></path>
            <path d="M12 5v14"></path>
        </svg>
        Add Category
    </a>
</div>

<?php if (empty($ucsCategories)): ?>
    <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 4h16v16H4z"></path>
            <path d="M9 4v16"></path>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-800">No categories yet</h3>
        <p class="mt-2 text-sm text-gray-500">Add your first discussion category to get started.</p>
    </div>
<?php else: ?>
    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-5 py-3.5">Category</th>
                    <th class="px-4 py-3.5">Slug</th>
                    <th class="px-4 py-3.5 text-center">Discussions</th>
                    <th class="px-4 py-3.5 text-center">Sort</th>
                    <th class="px-4 py-3.5">Status</th>
                    <th class="px-4 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($ucsCategories as $ucsCategory): ?>
                    <?php $ucsCatName = (string) $ucsCategory['name']; ?>
                    <tr class="transition-colors hover:bg-gray-50/60">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($ucsCatName); ?></p>
                            <?php if (!empty($ucsCategory['description'])): ?>
                                <p class="mt-0.5 max-w-sm text-xs leading-5 text-gray-500"><?php echo htmlspecialchars((string) $ucsCategory['description']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 text-xs text-gray-500"><?php echo htmlspecialchars((string) $ucsCategory['slug']); ?></td>
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-700"><?php echo (int) $ucsCategory['discussion_count']; ?></span>
                        </td>
                        <td class="px-4 py-4 text-center text-xs text-gray-500"><?php echo (int) $ucsCategory['sort_order']; ?></td>
                        <td class="px-4 py-4">
                            <?php if ((string) $ucsCategory['status'] === 'active'): ?>
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">Active</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap items-center justify-end gap-1">
                                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/categories/edit.php?id=' . (int) $ucsCategory['id']); ?>" title="Edit <?php echo htmlspecialchars($ucsCatName); ?>"
                                   class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-amber-600 transition-colors duration-150 hover:bg-amber-50">
                                    Edit
                                </a>
                                <?php if ((int) $ucsCategory['discussion_count'] === 0): ?>
                                    <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-category-delete.php'); ?>" class="inline-flex"
                                          onsubmit="return confirm('Delete category &quot;<?php echo htmlspecialchars(str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsCatName)); ?>&quot;? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $ucsCategory['id']; ?>">
                                        <button type="submit" title="Delete category"
                                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-red-600 transition-colors duration-150 hover:bg-red-50">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span class="px-2 py-1.5 text-xs text-gray-300" title="Categories in use cannot be deleted">Delete</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../../includes/admin-layout-bottom.php'; ?>