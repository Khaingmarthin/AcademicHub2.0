<?php
/**
 * Admin News - Update handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/news-validation.php';
require_once __DIR__ . '/../../includes/helpers/notification-helper.php';

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
        "SELECT id, title, slug, cover_image, status FROM news WHERE id = :id LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsExisting = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsExisting = null;
}

if ($ucsExisting === null || $ucsId === false || $ucsId < 1) {
    news_flash('error', 'News article not found.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = news_validate_input($_POST, $pdo, $ucsId);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    ucs_delete_upload($ucsClean['cover_image'] ?? null);
    foreach ($ucsClean['gallery_images'] ?? [] as $ucsImg) {
        ucs_delete_upload($ucsImg);
    }
    $_SESSION['news_errors'] = $ucsErrors;
    $_SESSION['news_old']    = $ucsClean;
    header('Location: ' . ROOT_URL . '/admin/news/edit.php?id=' . $ucsId);
    exit;
}

try {
    $ucsRemoveCover = isset($_POST['remove_cover']) && (string) $_POST['remove_cover'] === '1';

    $ucsSlug = news_unique_slug($pdo, news_make_slug($ucsClean['title']), $ucsId);
    $ucsCoverImage = $ucsClean['cover_image'] !== null
        ? $ucsClean['cover_image']
        : ($ucsRemoveCover ? null : $ucsExisting['cover_image']);

    $ucsPublishAt = $ucsClean['publish_at'];

    $pdo->beginTransaction();

    $ucsStmt = $pdo->prepare(
        "UPDATE news
         SET category_id = :category_id,
             title = :title,
             slug = :slug,
             content = :content,
             cover_image = :cover_image,
             published_at = :published_at,
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':category_id' => $ucsClean['category_id'],
        ':title'       => $ucsClean['title'],
        ':slug'        => $ucsSlug,
        ':content'     => $ucsClean['content'],
        ':cover_image' => $ucsCoverImage,
        ':published_at' => $ucsPublishAt,
        ':status'      => $ucsClean['status'],
        ':id'          => $ucsId,
    ]);

    $pdo->prepare("DELETE FROM news_targets WHERE news_id = :news_id")->execute([':news_id' => $ucsId]);

    if (!empty($ucsClean['targets'])) {
        $ucsStmt = $pdo->prepare(
            "INSERT INTO news_targets
                (news_id, classroom_id, major_id, year_level, section)
             VALUES
                (:news_id, :classroom_id, :major_id, :year_level, :section)"
        );
        foreach ($ucsClean['targets'] as $ucsTarget) {
            $ucsStmt->execute([
                ':news_id'       => $ucsId,
                ':classroom_id'  => $ucsTarget['classroom_id'],
                ':major_id'      => $ucsTarget['major_id'],
                ':year_level'    => $ucsTarget['year_level'],
                ':section'       => $ucsTarget['section'],
            ]);
        }
    }

    $pdo->commit();

    // Remove gallery images checked for deletion.
    $ucsRemoveGalleryIds = $_POST['remove_gallery_ids'] ?? [];
    if (!empty($ucsRemoveGalleryIds) && is_array($ucsRemoveGalleryIds)) {
        foreach ($ucsRemoveGalleryIds as $ucsRemoveId) {
            $ucsRemoveId = filter_var($ucsRemoveId, FILTER_VALIDATE_INT);
            if ($ucsRemoveId === false || $ucsRemoveId < 1) {
                continue;
            }
            try {
                $ucsStmt = $pdo->prepare("SELECT image_path FROM news_images WHERE id = :id AND news_id = :news_id LIMIT 1");
                $ucsStmt->execute([':id' => $ucsRemoveId, ':news_id' => $ucsId]);
                $ucsRemoveImg = $ucsStmt->fetch() ?: null;
                if ($ucsRemoveImg) {
                    ucs_delete_upload($ucsRemoveImg['image_path']);
                    $pdo->prepare("DELETE FROM news_images WHERE id = :id")->execute([':id' => $ucsRemoveId]);
                }
            } catch (PDOException $e) {
                // Best-effort cleanup.
            }
        }
    }

    // Insert new gallery images.
    if (!empty($ucsClean['gallery_images'])) {
        // Get current max sort_order for appending.
        $ucsMaxSort = 0;
        try {
            $ucsStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) FROM news_images WHERE news_id = :news_id");
            $ucsStmt->execute([':news_id' => $ucsId]);
            $ucsMaxSort = (int) $ucsStmt->fetchColumn() + 1;
        } catch (PDOException $e) {
            $ucsMaxSort = 0;
        }

        try {
            $ucsImgStmt = $pdo->prepare(
                "INSERT INTO news_images (news_id, image_path, sort_order)
                 VALUES (:news_id, :image_path, :sort_order)"
            );
            foreach ($ucsClean['gallery_images'] as $ucsIdx => $ucsImgPath) {
                $ucsImgStmt->execute([
                    ':news_id'    => $ucsId,
                    ':image_path' => $ucsImgPath,
                    ':sort_order' => $ucsMaxSort + $ucsIdx,
                ]);
            }
        } catch (PDOException $e) {
            // Gallery insert is best-effort.
        }
    }

    if ($ucsClean['cover_image'] !== null || $ucsRemoveCover) {
        ucs_delete_upload($ucsExisting['cover_image'] ?? null);
    }

    if ($ucsClean['status'] === 'Published' && $ucsExisting['status'] !== 'Published') {
        ucs_send_news_notification($pdo, $ucsId, $ucsClean['title'], $ucsClean['content'], $ucsSlug);
        ucs_create_news_notifications($pdo, $ucsId, $ucsClean['title'], $ucsClean['content'], $ucsSlug);
    }

    news_flash('success', 'News article "' . $ucsClean['title'] . '" updated successfully.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ((string) $e->getCode() === '23000') {
        news_flash('error', 'A news article with this slug already exists. Please try a different title.');
    } else {
        news_flash('error', 'Unable to update the news article. Please try again.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    news_flash('error', 'Unable to update the news article. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;