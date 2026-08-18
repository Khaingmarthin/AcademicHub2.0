<?php
/**
 * Shared validation helpers for the Admin News module.
 *
 * Used by both the create and update handlers so the news input rules live
 * in exactly one place. Returns clean, normalised values plus a list of
 * human-readable validation errors.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

require_once __DIR__ . '/ucs-upload.php';

const NEWS_STATUSES        = ['Draft', 'Published', 'Expired'];
const NEWS_YEAR_LEVELS     = ['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Fifth Year'];
const NEWS_COVER_MAX_BYTES = 5242880; // 5 MB

/**
 * Build a URL-safe slug from a title.
 *
 * @param string $title Source string.
 * @return string Slug.
 */
function news_make_slug($title)
{
    $ucsSlug = strtolower(trim((string) $title));
    $ucsSlug = preg_replace('/[^a-z0-9]+/', '-', $ucsSlug);
    $ucsSlug = trim((string) $ucsSlug, '-');
    return $ucsSlug !== '' ? $ucsSlug : 'news';
}

/**
 * Resolve a slug to one that is unique across the news table.
 *
 * @param PDO      $pdo       Database connection.
 * @param string   $slug      Desired slug.
 * @param int|null $excludeId Row id to ignore when editing.
 * @return string Unique slug.
 */
function news_unique_slug($pdo, $slug, $excludeId = null)
{
    $ucsBase = $slug;
    $ucsSlug = $ucsBase;
    $ucsN    = 2;

    while (true) {
        $ucsStmt = $pdo->prepare("SELECT id FROM news WHERE slug = :slug LIMIT 1");
        $ucsStmt->execute([':slug' => $ucsSlug]);
        $ucsExistingId = $ucsStmt->fetchColumn();
        if ($ucsExistingId === false || (int) $ucsExistingId === (int) $excludeId) {
            return $ucsSlug;
        }
        $ucsSlug = $ucsBase . '-' . $ucsN;
        $ucsN++;
    }
}

/**
 * Validate and normalise news form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO      $pdo       Database connection used for FK + slug checks.
 * @param int|null $excludeId Row id to ignore when editing an existing news item.
 * @return array{clean:array, errors:array}
 */
function news_validate_input($input, $pdo, $excludeId = null)
{
    $errors = [];

    $categoryId  = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT);
    $title       = trim((string) ($input['title'] ?? ''));
    $content     = trim((string) ($input['content'] ?? ''));
    $status      = (string) ($input['status'] ?? 'Draft');
    $yearLevels  = $input['target_year_level'] ?? [];
    $sections    = $input['target_section'] ?? [];
    $targetYears = $input['target_academic_year_id'] ?? [];
    $targetMajors = $input['target_major_id'] ?? [];

    // ---- Foreign keys ---------------------------------------------------
    if ($categoryId === false || $categoryId < 1) {
        $errors[] = 'A category must be selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM categories WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $categoryId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected category does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the category. Please try again.';
        }
    }

    // ---- Title & content -------------------------------------------------
    if ($title === '') {
        $errors[] = 'Title is required.';
    } elseif (mb_strlen($title) > 255) {
        $errors[] = 'Title must be 255 characters or fewer.';
    }

    if ($content === '') {
        $errors[] = 'Content is required.';
    }

    // ---- Status ----------------------------------------------------------
    if (!in_array($status, NEWS_STATUSES, true)) {
        $errors[] = 'Invalid status selected.';
    }

    // ---- Cover image (optional) ------------------------------------------
    $ucsCoverImage = null;
    try {
        $ucsCoverImage = ucs_handle_upload('cover_image', ['jpg', 'jpeg', 'png', 'gif', 'webp'], NEWS_COVER_MAX_BYTES);
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    // ---- Targets -----------------------------------------------------------
    $ucsTargets = [];
    $ucsTargetCount = max(
        is_array($targetYears) ? count($targetYears) : 0,
        is_array($targetMajors) ? count($targetMajors) : 0,
        is_array($yearLevels) ? count($yearLevels) : 0,
        is_array($sections) ? count($sections) : 0
    );

    if ($ucsTargetCount > 0) {
        for ($ucsI = 0; $ucsI < $ucsTargetCount; $ucsI++) {
            $ucsTarget = [
                'academic_year_id' => is_array($targetYears) && isset($targetYears[$ucsI]) && $targetYears[$ucsI] !== '' ? (int) $targetYears[$ucsI] : null,
                'major_id'         => is_array($targetMajors) && isset($targetMajors[$ucsI]) && $targetMajors[$ucsI] !== '' ? (int) $targetMajors[$ucsI] : null,
                'year_level'       => is_array($yearLevels) && isset($yearLevels[$ucsI]) && $yearLevels[$ucsI] !== '' ? (string) $yearLevels[$ucsI] : null,
                'section'          => is_array($sections) && isset($sections[$ucsI]) ? trim((string) $sections[$ucsI]) : '',
            ];
            if ($ucsTarget['section'] === '') {
                $ucsTarget['section'] = null;
            }

            if ($ucsTarget['academic_year_id'] === null && $ucsTarget['major_id'] === null
                && $ucsTarget['year_level'] === null && $ucsTarget['section'] === null) {
                continue;
            }

            $ucsTarget['section'] = $ucsTarget['section'] !== null && mb_strlen($ucsTarget['section']) > 10
                ? mb_substr($ucsTarget['section'], 0, 10)
                : $ucsTarget['section'];

            $ucsTargets[] = $ucsTarget;
        }
    }

    return [
        'clean' => [
            'category_id'  => $categoryId,
            'title'        => $title,
            'content'      => $content,
            'status'       => $status,
            'cover_image'  => $ucsCoverImage,
            'targets'      => $ucsTargets,
        ],
        'errors' => $errors,
    ];
}

/**
 * Store a flash message for the News module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function news_flash($type, $message)
{
    $_SESSION['news_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}