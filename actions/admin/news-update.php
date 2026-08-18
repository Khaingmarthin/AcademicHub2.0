<?php
/**
 * Admin News - Update handler.
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

    $ucsPublishedAtSql = "published_at = CASE
                              WHEN :status = 'Published' AND (published_at IS NULL OR status <> 'Published') THEN NOW()
                              ELSE published_at
                          END";

    $pdo->beginTransaction();

    $ucsStmt = $pdo->prepare(
        "UPDATE news
         SET category_id = :category_id,
             title = :title,
             slug = :slug,
             content = :content,
             cover_image = :cover_image,
             {$ucsPublishedAtSql},
             status = :status
         WHERE id = :id"
    );
    $ucsStmt->execute([
        ':category_id' => $ucsClean['category_id'],
        ':title'       => $ucsClean['title'],
        ':slug'        => $ucsSlug,
        ':content'     => $ucsClean['content'],
        ':cover_image' => $ucsCoverImage,
        ':status'      => $ucsClean['status'],
        ':id'          => $ucsId,
    ]);

    $pdo->prepare("DELETE FROM news_targets WHERE news_id = :news_id")->execute([':news_id' => $ucsId]);

    if (!empty($ucsClean['targets'])) {
        $ucsStmt = $pdo->prepare(
            "INSERT INTO news_targets
                (news_id, academic_year_id, major_id, year_level, section)
             VALUES
                (:news_id, :academic_year_id, :major_id, :year_level, :section)"
        );
        foreach ($ucsClean['targets'] as $ucsTarget) {
            $ucsStmt->execute([
                ':news_id'          => $ucsId,
                ':academic_year_id' => $ucsTarget['academic_year_id'],
                ':major_id'         => $ucsTarget['major_id'],
                ':year_level'       => $ucsTarget['year_level'],
                ':section'          => $ucsTarget['section'],
            ]);
        }
    }

    $pdo->commit();

    if ($ucsClean['cover_image'] !== null || $ucsRemoveCover) {
        ucs_delete_upload($ucsExisting['cover_image'] ?? null);
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