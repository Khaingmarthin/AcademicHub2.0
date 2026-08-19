<?php
/**
 * Shared validation + support helpers for the Career Discussions module.
 *
 * Career Discussions is a moderated, forum-style community where logged-in
 * students (including verified alumni) ask career questions and alumni share
 * experience. Input rules for categories, discussions, replies and reports
 * live here so the create/update/report handlers reuse exactly one copy.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

const DISCUSSION_STATUSES        = ['open', 'closed', 'hidden'];
const DISCUSSION_REPLY_STATUSES  = ['visible', 'hidden'];
const DISCUSSION_REPORT_REASONS  = [
    'Inappropriate content',
    'Spam',
    'Harassment',
    'Misinformation',
    'Other',
];
const DISCUSSION_CATEGORY_STATUSES = ['active', 'inactive'];
const DISCUSSION_TITLE_MAX       = 255;
const DISCUSSION_CONTENT_MAX     = 100000;
const DISCUSSION_REPLY_MAX       = 10000;
const DISCUSSION_REASON_MAX      = 50;
const DISCUSSION_DETAILS_MAX     = 1000;
const DISCUSSION_CATEGORY_NAME_MAX = 191;
const DISCUSSION_CATEGORY_DESC_MAX = 1000;

/**
 * Store a flash message for the Career Discussions pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function discussion_flash($type, $message)
{
    $_SESSION['discussion_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}

/**
 * Build a URL-safe slug from a category name.
 *
 * @param string $name Source string.
 * @return string Slug.
 */
function discussion_make_slug($name)
{
    $ucsSlug = strtolower(trim((string) $name));
    $ucsSlug = preg_replace('/[^a-z0-9]+/', '-', $ucsSlug);
    $ucsSlug = trim((string) $ucsSlug, '-');
    return $ucsSlug !== '' ? $ucsSlug : 'category';
}

/**
 * Resolve a category slug to one unique across the categories table.
 *
 * @param PDO      $pdo       Database connection.
 * @param string   $slug      Desired slug.
 * @param int|null $excludeId Row id to ignore when editing.
 * @return string Unique slug.
 */
function discussion_unique_slug($pdo, $slug, $excludeId = null)
{
    $ucsBase = $slug;
    $ucsSlug = $ucsBase;
    $ucsN    = 2;

    while (true) {
        $ucsStmt = $pdo->prepare("SELECT id FROM discussion_categories WHERE slug = :slug LIMIT 1");
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
 * Validate and normalise discussion category form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO      $pdo       Database connection used for the unique checks.
 * @param int|null $excludeId Row id to ignore when editing an existing category.
 * @return array{clean:array, errors:array}
 */
function discussion_validate_category_input($input, $pdo, $excludeId = null)
{
    $errors   = [];
    $name     = trim((string) ($input['name'] ?? ''));
    $slug     = trim((string) ($input['slug'] ?? ''));
    $desc     = trim((string) ($input['description'] ?? ''));
    $sort     = filter_var($input['sort_order'] ?? null, FILTER_VALIDATE_INT);
    $status   = (string) ($input['status'] ?? 'active');

    if ($name === '') {
        $errors[] = 'Category name is required.';
    } elseif (mb_strlen($name) > DISCUSSION_CATEGORY_NAME_MAX) {
        $errors[] = 'Category name must be ' . DISCUSSION_CATEGORY_NAME_MAX . ' characters or fewer.';
    } else {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM discussion_categories WHERE name = :name LIMIT 1");
            $ucsStmt->execute([':name' => $name]);
            $ucsExistingId = $ucsStmt->fetchColumn();
            if ($ucsExistingId !== false && (int) $ucsExistingId !== (int) $excludeId) {
                $errors[] = 'A category with this name already exists.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the category name. Please try again.';
        }
    }

    if ($slug === '') {
        $slug = discussion_make_slug($name);
    }
    if (mb_strlen($slug) > 191) {
        $errors[] = 'Category slug must be 191 characters or fewer.';
    } else {
        $ucsSlug = discussion_unique_slug($pdo, $slug, $excludeId);
        if ($ucsSlug !== $slug) {
            $errors[] = 'A category with this slug already exists.';
        }
    }

    if (mb_strlen($desc) > DISCUSSION_CATEGORY_DESC_MAX) {
        $errors[] = 'The description must be ' . DISCUSSION_CATEGORY_DESC_MAX . ' characters or fewer.';
    }

    if ($sort === false || $sort < 0) {
        $errors[] = 'Please enter a valid sort order (0 or higher).';
    }

    if (!in_array($status, DISCUSSION_CATEGORY_STATUSES, true)) {
        $errors[] = 'Invalid status selected.';
    }

    return [
        'clean' => [
            'name'        => $name,
            'slug'        => $ucsSlug ?? $slug,
            'description' => $desc,
            'sort_order'  => $sort === false ? 0 : $sort,
            'status'      => $status,
        ],
        'errors' => $errors,
    ];
}

/**
 * Validate and normalise discussion form input (public "Ask a Career
 * Question" form).
 *
 * @param array $input Raw form values (e.g. $_POST).
 * @param PDO   $pdo   Database connection used for the category check.
 * @return array{clean:array, errors:array}
 */
function discussion_validate_discussion_input($input, $pdo)
{
    $errors     = [];
    $categoryId = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT);
    $title      = trim((string) ($input['title'] ?? ''));
    $content    = trim((string) ($input['content'] ?? ''));

    if ($categoryId === false || $categoryId < 1) {
        $errors[] = 'A category must be selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT id FROM discussion_categories WHERE id = :id AND status = 'active' LIMIT 1"
            );
            $ucsStmt->execute([':id' => $categoryId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected category does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the category. Please try again.';
        }
    }

    if ($title === '') {
        $errors[] = 'A question title is required.';
    } elseif (mb_strlen($title) > DISCUSSION_TITLE_MAX) {
        $errors[] = 'The title must be ' . DISCUSSION_TITLE_MAX . ' characters or fewer.';
    }

    if ($content === '') {
        $errors[] = 'Please describe your question or topic.';
    } elseif (mb_strlen($content) > DISCUSSION_CONTENT_MAX) {
        $errors[] = 'The description is too long. Please keep it under ' . DISCUSSION_CONTENT_MAX . ' characters.';
    }

    return [
        'clean' => [
            'category_id' => $categoryId,
            'title'       => $title,
            'content'     => $content,
        ],
        'errors' => $errors,
    ];
}

/**
 * Validate and normalise a reply comment.
 *
 * @param array $input Raw form values (e.g. $_POST).
 * @return array{clean:array, errors:array}
 */
function discussion_validate_reply_input($input)
{
    $errors  = [];
    $content = trim((string) ($input['content'] ?? ''));

    if ($content === '') {
        $errors[] = 'Please write a reply.';
    } elseif (mb_strlen($content) > DISCUSSION_REPLY_MAX) {
        $errors[] = 'The reply is too long. Please keep it under ' . DISCUSSION_REPLY_MAX . ' characters.';
    }

    return [
        'clean' => [
            'content' => $content,
        ],
        'errors' => $errors,
    ];
}

/**
 * Validate and normalise a report of a discussion or reply.
 *
 * @param array $input Raw form values (e.g. $_POST).
 * @param PDO   $pdo   Database connection used to confirm the content exists.
 * @return array{clean:array, errors:array}
 */
function discussion_validate_report_input($input, $pdo)
{
    $errors        = [];
    $contentType   = (string) ($input['content_type'] ?? '');
    $contentId     = filter_var($input['content_id'] ?? null, FILTER_VALIDATE_INT);
    $reason        = trim((string) ($input['reason'] ?? ''));
    $details       = trim((string) ($input['details'] ?? ''));

    if (!in_array($contentType, ['discussion', 'reply'], true)) {
        $errors[] = 'Invalid report target.';
    } elseif ($contentId === false || $contentId < 1) {
        $errors[] = 'Invalid report target.';
    } else {
        try {
            $ucsTable = $contentType === 'discussion' ? 'discussions' : 'discussion_replies';
            $ucsStmt  = $pdo->prepare("SELECT id FROM {$ucsTable} WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $contentId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The content you tried to report no longer exists.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the report target. Please try again.';
        }
    }

    if ($reason === '' || mb_strlen($reason) > DISCUSSION_REASON_MAX) {
        $errors[] = 'Please choose a reason.';
    }

    if (mb_strlen($details) > DISCUSSION_DETAILS_MAX) {
        $errors[] = 'The additional details must be ' . DISCUSSION_DETAILS_MAX . ' characters or fewer.';
    }

    return [
        'clean' => [
            'content_type' => $contentType,
            'content_id'   => $contentId,
            'reason'       => $reason,
            'details'      => $details,
        ],
        'errors' => $errors,
    ];
}

/**
 * Whether a student is a verified alumnus.
 *
 * Verified alumni may answer questions and are shown with an "Alumni" badge
 * in the community. The check reads the alumni_profiles row that exists for
 * every graduated student.
 *
 * @param PDO $pdo       Database connection.
 * @param int $studentId Student id.
 * @return bool
 */
function discussion_is_verified_alumni($pdo, $studentId)
{
    if ($studentId < 1) {
        return false;
    }

    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id FROM alumni_profiles
             WHERE student_id = :student_id AND verification_status = 'verified'
             LIMIT 1"
        );
        $ucsStmt->execute([':student_id' => $studentId]);
        return $ucsStmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Load the career categories.
 *
 * @param PDO $pdo        Database connection.
 * @param bool $onlyActive Restrict to active categories (public-facing use).
 * @return array
 */
function discussion_load_categories($pdo, $onlyActive = false)
{
    $ucsSql = "SELECT id, name, slug, description, sort_order, status
               FROM discussion_categories";
    if ($onlyActive) {
        $ucsSql .= " WHERE status = 'active'";
    }
    $ucsSql .= " ORDER BY sort_order ASC, name ASC";

    try {
        return $pdo->query($ucsSql)->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}