<?php
/**
 * Admin News - Create handler.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/news-validation.php';

admin_require_login();

$ucsReturnUrl = ROOT_URL . '/admin/news/index.php';
$ucsFormUrl   = ROOT_URL . '/admin/news/create.php';

if (!admin_csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    news_flash('error', 'Your session has expired. Please try again.');
    header('Location: ' . $ucsReturnUrl);
    exit;
}

$ucsResult = news_validate_input($_POST, $pdo);
$ucsClean  = $ucsResult['clean'];
$ucsErrors = $ucsResult['errors'];

if (!empty($ucsErrors)) {
    ucs_delete_upload($ucsClean['cover_image'] ?? null);
    $_SESSION['news_errors'] = $ucsErrors;
    $_SESSION['news_old']    = $ucsClean;
    header('Location: ' . $ucsFormUrl);
    exit;
}

try {
    $ucsSlug = news_unique_slug($pdo, news_make_slug($ucsClean['title']));

    $pdo->beginTransaction();

    $ucsStmt = $pdo->prepare(
        "INSERT INTO news
            (category_id, admin_id, title, slug, content, cover_image, published_at, status)
         VALUES
            (:category_id, :admin_id, :title, :slug, :content, :cover_image,
             CASE WHEN :status = 'Published' THEN NOW() ELSE NULL END, :status)"
    );
    $ucsStmt->execute([
        ':category_id' => $ucsClean['category_id'],
        ':admin_id'    => (int) $_SESSION['admin_id'],
        ':title'       => $ucsClean['title'],
        ':slug'        => $ucsSlug,
        ':content'     => $ucsClean['content'],
        ':cover_image' => $ucsClean['cover_image'],
        ':status'      => $ucsClean['status'],
    ]);

    $ucsNewsId = (int) $pdo->lastInsertId();

    if (!empty($ucsClean['targets'])) {
        $ucsStmt = $pdo->prepare(
            "INSERT INTO news_targets
                (news_id, academic_year_id, major_id, year_level, section)
             VALUES
                (:news_id, :academic_year_id, :major_id, :year_level, :section)"
        );
        foreach ($ucsClean['targets'] as $ucsTarget) {
            $ucsStmt->execute([
                ':news_id'          => $ucsNewsId,
                ':academic_year_id' => $ucsTarget['academic_year_id'],
                ':major_id'         => $ucsTarget['major_id'],
                ':year_level'       => $ucsTarget['year_level'],
                ':section'          => $ucsTarget['section'],
            ]);
        }
    }

    $pdo->commit();

    news_flash('success', 'News article "' . $ucsClean['title'] . '" created successfully.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ((string) $e->getCode() === '23000') {
        news_flash('error', 'A news article with this slug already exists. Please try a different title.');
    } else {
        news_flash('error', 'Unable to create the news article. Please try again.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    news_flash('error', 'Unable to create the news article. Please try again.');
}

header('Location: ' . $ucsReturnUrl);
exit;