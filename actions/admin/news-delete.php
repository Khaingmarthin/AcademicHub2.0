<?php
/**
 * Admin News - Delete handler.
 *
 * Target rows are removed first to honour the schema's ON DELETE CASCADE.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/news-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/news/index.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    news_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT id, title, cover_image FROM news WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsNews = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsNews = null;
}

if ($ucsNews === null || $ucsId === false || $ucsId < 1) {
    news_flash('error', 'News article not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

// Fetch gallery image paths before deletion (CASCADE will remove rows).
$ucsGalleryPaths = [];
try {
    $ucsStmt = $pdo->prepare("SELECT image_path FROM news_images WHERE news_id = :news_id");
    $ucsStmt->execute([':news_id' => $ucsId]);
    $ucsGalleryPaths = $ucsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (PDOException $e) {
    $ucsGalleryPaths = [];
}

try {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM news_images WHERE news_id = :news_id")->execute([':news_id' => $ucsId]);
    $pdo->prepare("DELETE FROM news_targets WHERE news_id = :news_id")->execute([':news_id' => $ucsId]);
    $pdo->prepare("DELETE FROM news WHERE id = :id")->execute([':id' => $ucsId]);
    $pdo->commit();

    // Delete cover image file.
    $ucsCoverImage = (string) ($ucsNews['cover_image'] ?? '');
    if ($ucsCoverImage !== '' && strpos($ucsCoverImage, 'uploads/') === 0) {
        $ucsFilePath = UPLOAD_DIR . substr($ucsCoverImage, strlen('uploads/'));
        if (is_file($ucsFilePath)) {
            @unlink($ucsFilePath);
        }
    }

    // Delete gallery image files.
    foreach ($ucsGalleryPaths as $ucsGalPath) {
        if (!empty($ucsGalPath) && strpos($ucsGalPath, 'uploads/') === 0) {
            $ucsGalFile = UPLOAD_DIR . substr($ucsGalPath, strlen('uploads/'));
            if (is_file($ucsGalFile)) {
                @unlink($ucsGalFile);
            }
        }
    }

    news_flash('success', 'News article "' . $ucsNews['title'] . '" deleted successfully.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    news_flash('error', 'Unable to delete the news article. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;