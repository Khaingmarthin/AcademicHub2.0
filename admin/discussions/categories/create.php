<?php
/**
 * Admin Career Discussions - Create category.
 */
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers/discussion-validation.php';

admin_require_login();

$pageTitle = 'Add Discussion Category';
$activeNav = 'discussions';

$ucsErrors = $_SESSION['discussion_category_errors'] ?? [];
unset($_SESSION['discussion_category_errors']);
$ucsOld = $_SESSION['discussion_category_old'] ?? [];
unset($_SESSION['discussion_category_old']);

require_once __DIR__ . '/../../../includes/admin-layout-top.php';
?>
<div class="mx-auto max-w-2xl">
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 sm:p-8">
        <?php if (!empty($ucsErrors)): ?>
            <div class="mb-6 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
                <ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
                    <?php foreach ($ucsErrors as $ucsError): ?>
                        <li><?php echo htmlspecialchars($ucsError); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/discussion-category-create.php'); ?>" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">

            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700">Category name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars((string) ($ucsOld['name'] ?? '')); ?>" required maxlength="191"
                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>

            <div>
                <label for="slug" class="block text-sm font-semibold text-gray-700">Slug</label>
                <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars((string) ($ucsOld['slug'] ?? '')); ?>" placeholder="e.g. career-advice" maxlength="191"
                       class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                <p class="mt-1.5 text-xs text-gray-400">Used in public category filter URLs. Leave blank to generate from the name.</p>
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700">Description</label>
                <textarea id="description" name="description" rows="3" maxlength="1000"
                          class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars((string) ($ucsOld['description'] ?? '')); ?></textarea>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="sort_order" class="block text-sm font-semibold text-gray-700">Sort order</label>
                    <input type="number" id="sort_order" name="sort_order" value="<?php echo (int) ($ucsOld['sort_order'] ?? 0); ?>" min="0"
                           class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="status" class="block text-sm font-semibold text-gray-700">Status</label>
                    <select id="status" name="status"
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="active" <?php echo ($ucsOld['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($ucsOld['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/discussions/categories/index.php'); ?>"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50">Cancel</a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Create Category
                </button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../../includes/admin-layout-bottom.php'; ?>