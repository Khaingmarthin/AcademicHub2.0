<?php
/**
 * Shared validation helpers for the Admin Alumni Stories module.
 *
 * Alumni Stories are admin-controlled editorial content: the story body,
 * summary, cover image and career field are written by admins and are never
 * auto-generated from alumni profile fields. Every story must be tied to an
 * existing alumni profile (the featured alumnus). Used by the create,
 * update, publish and unpublish handlers so the input rules live in one
 * place.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

require_once __DIR__ . '/ucs-upload.php';

const ALUMNI_STORY_STATUSES    = ['draft', 'pending', 'published', 'rejected', 'unpublished'];
const ALUMNI_STORY_COVER_MAX_BYTES = 5242880; // 5 MB

/**
 * Store a flash message for the Alumni Stories module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function alumni_story_flash($type, $message)
{
    $_SESSION['alumni_story_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}

/**
 * Validate and normalise alumni story form input.
 *
 * @param array    $input     Raw form values (e.g. $_POST).
 * @param PDO      $pdo       Database connection used for the FK check.
 * @param int|null $excludeId Story id to ignore when editing an existing story.
 * @return array{clean:array, errors:array}
 */
function alumni_story_validate_input($input, $pdo, $excludeId = null)
{
    $errors = [];

    $alumniProfileId = filter_var($input['alumni_profile_id'] ?? null, FILTER_VALIDATE_INT);
    $title           = trim((string) ($input['title'] ?? ''));
    $summary         = trim((string) ($input['summary'] ?? ''));
    $content         = trim((string) ($input['content'] ?? ''));
    $careerField     = trim((string) ($input['career_field'] ?? ''));
    $publicationDate = trim((string) ($input['publication_date'] ?? ''));
    $status          = (string) ($input['status'] ?? 'draft');

    // ---- Featured alumnus --------------------------------------------------
    if ($alumniProfileId === false || $alumniProfileId < 1) {
        $errors[] = 'An Alumni must be selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM alumni_profiles WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $alumniProfileId]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'The selected Alumni does not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the Alumni. Please try again.';
        }
    }

    // ---- Title & summary ---------------------------------------------------
    if ($title === '') {
        $errors[] = 'Title is required.';
    } elseif (mb_strlen($title) > 255) {
        $errors[] = 'Title must be 255 characters or fewer.';
    }

    if ($summary === '') {
        $errors[] = 'A short summary is required.';
    } elseif (mb_strlen($summary) > 500) {
        $errors[] = 'The summary must be 500 characters or fewer.';
    }

    // ---- Story content -----------------------------------------------------
    if ($content === '') {
        $errors[] = 'Story content is required.';
    } elseif (mb_strlen($content) > 100000) {
        $errors[] = 'Story content is too long. Please keep it under 100,000 characters.';
    }

    // ---- Career field ------------------------------------------------------
    if (mb_strlen($careerField) > 255) {
        $errors[] = 'Career field must be 255 characters or fewer.';
    }

    // ---- Publication date --------------------------------------------------
    $ucsCleanDate = null;
    if ($publicationDate !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $publicationDate)) {
            $errors[] = 'Please enter a valid publication date.';
        } else {
            $ucsDateParts = explode('-', $publicationDate);
            if (!checkdate((int) $ucsDateParts[1], (int) $ucsDateParts[2], (int) $ucsDateParts[0])) {
                $errors[] = 'Please enter a valid publication date.';
            } else {
                $ucsCleanDate = $publicationDate;
            }
        }
    }

    // ---- Status ------------------------------------------------------------
    if (!in_array($status, ALUMNI_STORY_STATUSES, true)) {
        $errors[] = 'Invalid status selected.';
    }

    // ---- Cover image (optional) --------------------------------------------
    $ucsCoverImage = null;
    try {
        $ucsCoverImage = ucs_handle_upload('cover_image', ['jpg', 'jpeg', 'png', 'gif', 'webp'], ALUMNI_STORY_COVER_MAX_BYTES);
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    return [
        'clean' => [
            'alumni_profile_id' => $alumniProfileId,
            'title'             => $title,
            'summary'           => $summary,
            'content'           => $content,
            'career_field'      => $careerField,
            'publication_date'  => $ucsCleanDate,
            'status'            => $status,
            'cover_image'       => $ucsCoverImage,
        ],
        'errors' => $errors,
    ];
}

/**
 * Validate and normalise an alumni self-service story submission.
 *
 * Verified alumni can submit stories for admin review. The story is stored
 * with status 'pending' and must then be approved or rejected by an admin.
 * Alumni cannot set their own status to 'published' directly.
 *
 * @param array $input Raw form values (e.g. $_POST).
 * @param PDO   $pdo   Database connection.
 * @return array{clean:array, errors:array}
 */
function alumni_story_submit_validate_input($input, $pdo)
{
    $errors = [];

    $studentId = (int) (student_current_user()['id'] ?? 0);
    if ($studentId < 1) {
        $errors[] = 'You must be logged in to submit a story.';
        return ['clean' => [], 'errors' => $errors];
    }

    $alumniProfileId = null;
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT ap.id FROM alumni_profiles ap
             WHERE ap.student_id = :student_id
               AND ap.verification_status = 'verified'
             LIMIT 1"
        );
        $ucsStmt->execute([':student_id' => $studentId]);
        $alumniProfileId = $ucsStmt->fetchColumn() ?: null;
    } catch (PDOException $e) {
        $errors[] = 'Unable to verify your alumni profile. Please try again.';
        return ['clean' => [], 'errors' => $errors];
    }

    if ($alumniProfileId === null) {
        $errors[] = 'Only verified alumni can submit stories.';
        return ['clean' => [], 'errors' => $errors];
    }

    $title       = trim((string) ($input['title'] ?? ''));
    $summary     = trim((string) ($input['summary'] ?? ''));
    $content     = trim((string) ($input['content'] ?? ''));
    $careerField = trim((string) ($input['career_field'] ?? ''));

    if ($title === '') {
        $errors[] = 'Title is required.';
    } elseif (mb_strlen($title) > 255) {
        $errors[] = 'Title must be 255 characters or fewer.';
    }

    if ($summary === '') {
        $errors[] = 'A short summary is required.';
    } elseif (mb_strlen($summary) > 500) {
        $errors[] = 'The summary must be 500 characters or fewer.';
    }

    if ($content === '') {
        $errors[] = 'Story content is required.';
    } elseif (mb_strlen($content) > 100000) {
        $errors[] = 'Story content is too long. Please keep it under 100,000 characters.';
    }

    if (mb_strlen($careerField) > 255) {
        $errors[] = 'Career field must be 255 characters or fewer.';
    }

    $ucsCoverImage = null;
    try {
        $ucsCoverImage = ucs_handle_upload('cover_image', ['jpg', 'jpeg', 'png', 'gif', 'webp'], ALUMNI_STORY_COVER_MAX_BYTES);
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    return [
        'clean' => [
            'alumni_profile_id' => (int) $alumniProfileId,
            'title'             => $title,
            'summary'           => $summary,
            'content'           => $content,
            'career_field'      => $careerField,
            'cover_image'       => $ucsCoverImage,
        ],
        'errors' => $errors,
    ];
}